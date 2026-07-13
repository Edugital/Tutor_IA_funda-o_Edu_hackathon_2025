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
 * Export aggregate Human in Control governance metrics.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'assignmentid' => null,
        'from' => null,
        'to' => null,
    ],
    [
        'h' => 'help',
        'a' => 'assignmentid',
    ]
);

if (!empty($unrecognized)) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}

if ($options['help']) {
    $help = <<<HELP
Export aggregate AI Tutoring governance metrics as JSON.

Options:
-h, --help                 Show this help.
-a, --assignmentid=ID      Restrict to one assignment instance.
--from=YYYY-MM-DD           Inclusive lower date in the site timezone.
--to=YYYY-MM-DD             Exclusive upper date in the site timezone.

Example:
php mod/assign/feedback/aitutoria/cli/governance_report.php --assignmentid=42 --from=2026-01-01
HELP;
    echo $help . PHP_EOL;
    exit(0);
}

$assignmentid = $options['assignmentid'] === null ? null : (int) $options['assignmentid'];
if ($assignmentid !== null && $assignmentid < 1) {
    cli_error('Assignment id must be a positive integer.');
}

$parsemidnight = static function (?string $date, string $option): ?int {
    if ($date === null || trim($date) === '') {
        return null;
    }
    $date = trim($date);
    $datetime = DateTimeImmutable::createFromFormat('!Y-m-d', $date, core_date::get_server_timezone_object());
    $errors = DateTimeImmutable::getLastErrors();
    if (!$datetime || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
        cli_error("{$option} must use YYYY-MM-DD.");
    }
    return $datetime->getTimestamp();
};

$timefrom = $parsemidnight($options['from'], '--from');
$timeto = $parsemidnight($options['to'], '--to');
if ($timefrom !== null && $timeto !== null && $timefrom >= $timeto) {
    cli_error('--from must be earlier than --to.');
}

$report = assignfeedback_aitutoria\local\reporting\governance_report::build(
    $assignmentid,
    $timefrom,
    $timeto
);

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
