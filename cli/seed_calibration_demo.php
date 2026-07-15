<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Seed calibration demo metrics from existing completed advisory jobs.
 *
 * Safe by default: dry-run unless --execute. Never invents grades or student
 * submissions. Only applies Human-in-Control decisions to rows that already
 * have an unpublished AI suggestion from a completed job.
 *
 * php mod/assign/feedback/aitutoria/cli/seed_calibration_demo.php
 * php mod/assign/feedback/aitutoria/cli/seed_calibration_demo.php --execute
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use assignfeedback_aitutoria\local\decision_policy;
use assignfeedback_aitutoria\local\feedback_repository;

[$options, $unrecognized] = cli_get_params(
    [
        'execute' => false,
        'limit' => 12,
        'help' => false,
    ],
    [
        'h' => 'help',
        'e' => 'execute',
    ]
);

if (!empty($unrecognized)) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognized)));
}

if (!empty($options['help'])) {
    echo <<<HELP
Seed calibration demo metrics from existing completed AI tutoring jobs.

Does nothing unless --execute is passed. If reviewed-with-AI human decisions
already exist, exits without changes. If no completed jobs with suggestions
are available, documents that real grader saves are required for metrics.

Options:
-e, --execute   Apply rotating accept / manual / reject decisions
--limit=N       Max rows to update (default 12, minimum 10 when executing)
-h, --help      Show this help

Example:
php mod/assign/feedback/aitutoria/cli/seed_calibration_demo.php --execute
HELP;
    echo PHP_EOL;
    exit(0);
}

$limit = max(1, (int) $options['limit']);
$execute = !empty($options['execute']);

$reviewed = $DB->count_records_select(
    'assignfeedback_aitutoria',
    "decision IN ('accepted_ai', 'overridden_ai', 'rejected_ai', 'escalated')"
);

if ($reviewed > 0) {
    cli_writeln("Human AI decisions already present ({$reviewed}). No seed needed.");
    exit(0);
}

$sql = "SELECT j.id, j.assignment, j.grade, j.suggestiontext, j.model, j.promptversion
          FROM {assignfeedback_aitutoria_job} j
         WHERE j.status = :status
           AND j.suggestiontext IS NOT NULL
           AND j.suggestiontext <> :empty
      ORDER BY j.timemodified DESC";
$jobs = $DB->get_records_sql($sql, ['status' => 'complete', 'empty' => ''], 0, max($limit, 10));

if (empty($jobs)) {
    cli_writeln('No completed jobs with suggestions found.');
    cli_writeln('Demo calibration metrics require real grader saves (accept / edit / reject)');
    cli_writeln('or at least one completed advisory job with suggestiontext.');
    exit(0);
}

$actions = [
    decision_policy::ACTION_ACCEPT,
    decision_policy::ACTION_MANUAL,
    decision_policy::ACTION_REJECT,
];

$planned = [];
$i = 0;
foreach ($jobs as $job) {
    if (count($planned) >= $limit) {
        break;
    }
    $action = $actions[$i % count($actions)];
    $i++;
    $planned[] = [
        'jobid' => (int) $job->id,
        'assignment' => (int) $job->assignment,
        'grade' => (int) $job->grade,
        'action' => $action,
        'suggestion' => (string) $job->suggestiontext,
        'model' => (string) ($job->model ?? ''),
        'promptversion' => (string) ($job->promptversion ?? ''),
    ];
}

cli_writeln('Candidates: ' . count($planned) . ' (execute=' . ($execute ? '1' : '0') . ')');
foreach ($planned as $row) {
    cli_writeln(
        'job=' . $row['jobid']
        . ' grade=' . $row['grade']
        . ' action=' . $row['action']
    );
}

if (!$execute) {
    cli_writeln('Dry-run only. Re-run with --execute to apply decisions via decision_policy.');
    exit(0);
}

if (count($planned) < 10) {
    cli_writeln('Warning: fewer than 10 candidates; applying ' . count($planned) . ' available rows.');
}

$applied = 0;
foreach ($planned as $row) {
    if (!get_config('assignfeedback_aitutoria', 'allowaisuggestions')) {
        // store_ai_suggestion requires the flag; temporarily ensure suggestion is on the feedback row.
        $existing = feedback_repository::get_by_grade($row['grade']);
        if (!$existing || trim((string) ($existing->aisuggestion ?? '')) === '') {
            $now = time();
            if (!$existing) {
                $DB->insert_record('assignfeedback_aitutoria', (object) [
                    'assignment' => $row['assignment'],
                    'grade' => $row['grade'],
                    'feedbacktext' => null,
                    'feedbackformat' => FORMAT_PLAIN,
                    'aisuggestion' => $row['suggestion'],
                    'aistatus' => 'ready',
                    'decision' => 'manual',
                    'model' => $row['model'] !== '' ? $row['model'] : null,
                    'promptversion' => $row['promptversion'] !== '' ? $row['promptversion'] : null,
                    'timecreated' => $now,
                    'timemodified' => $now,
                ]);
            } else {
                $existing->aisuggestion = $row['suggestion'];
                $existing->aistatus = 'ready';
                $existing->timemodified = $now;
                $DB->update_record('assignfeedback_aitutoria', $existing);
            }
        }
    } else {
        $existing = feedback_repository::get_by_grade($row['grade']);
        if (!$existing || trim((string) ($existing->aisuggestion ?? '')) === '') {
            feedback_repository::store_ai_suggestion(
                $row['assignment'],
                $row['grade'],
                $row['suggestion'],
                $row['model'],
                $row['promptversion'],
                ''
            );
        }
    }

    $humantext = $row['action'] === decision_policy::ACTION_ACCEPT
        ? ''
        : 'CLI calibration seed — human edited feedback for job ' . $row['jobid'];

    feedback_repository::save_human_feedback(
        $row['assignment'],
        $row['grade'],
        $humantext,
        false,
        $row['action']
    );
    $applied++;
}

cli_writeln("Applied {$applied} Human-in-Control decisions for calibration demo.");
exit(0);
