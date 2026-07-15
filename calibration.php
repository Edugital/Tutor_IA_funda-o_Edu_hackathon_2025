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
 * Light calibration dashboard (7 / 30 day Human-in-Control decision rates).
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');

use assignfeedback_aitutoria\local\reporting\governance_report;

require_login();
$context = context_system::instance();
require_capability('assignfeedback/aitutoria:viewgovernance', $context);

$assignmentid = optional_param('assignmentid', 0, PARAM_INT);
$format = optional_param('format', 'html', PARAM_ALPHA);
$urlparams = $assignmentid > 0 ? ['assignmentid' => $assignmentid] : [];
$url = new moodle_url('/mod/assign/feedback/aitutoria/calibration.php', $urlparams);

$now = time();
$filterassignment = $assignmentid > 0 ? $assignmentid : null;
$periods = [
    '7d' => [
        'label' => get_string('calibrationperiod7d', 'assignfeedback_aitutoria'),
        'timefrom' => $now - (DAYSECS * 7),
        'timeto' => $now + 1,
    ],
    '30d' => [
        'label' => get_string('calibrationperiod30d', 'assignfeedback_aitutoria'),
        'timefrom' => $now - (DAYSECS * 30),
        'timeto' => $now + 1,
    ],
];

$reports = [];
foreach ($periods as $key => $period) {
    $reports[$key] = governance_report::build(
        $filterassignment,
        $period['timefrom'],
        $period['timeto']
    );
}

if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="aitutoria-calibration.json"');
    echo json_encode(
        ['periods' => $reports, 'generatedat' => $now],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    exit;
}

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('calibrationreport', 'assignfeedback_aitutoria'));
$PAGE->set_heading(get_string('calibrationreport', 'assignfeedback_aitutoria'));

$reporturl = new moodle_url('/mod/assign/feedback/aitutoria/report.php', $urlparams);
$downloadurl = new moodle_url(
    '/mod/assign/feedback/aitutoria/calibration.php',
    $urlparams + ['format' => 'json']
);

/**
 * Render percentage of a decision among reviewed-with-AI rows.
 *
 * @param array $humanreview governance_report humanreview block.
 * @param string $decision Decision key.
 * @return string
 */
$pct = static function (array $humanreview, string $decision): string {
    $reviewed = (int) ($humanreview['reviewedwithai'] ?? 0);
    $count = (int) ($humanreview['decisions'][$decision] ?? 0);
    if ($reviewed <= 0) {
        return '-';
    }
    return round(($count / $reviewed) * 100, 2) . '%';
};

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('calibrationreport', 'assignfeedback_aitutoria'));
echo html_writer::div(
    html_writer::link($reporturl, get_string('opengovernancereport', 'assignfeedback_aitutoria'), ['class' => 'btn btn-secondary mb-2 mr-2'])
        . ' '
        . html_writer::link($downloadurl, get_string('downloadreportjson', 'assignfeedback_aitutoria'), ['class' => 'btn btn-secondary mb-2']),
    'mb-3'
);
echo html_writer::div(get_string('calibrationreport_help', 'assignfeedback_aitutoria'), 'text-muted mb-3');

foreach ($periods as $key => $period) {
    $gov = $reports[$key];
    $human = $gov['humanreview'];
    echo html_writer::start_div('aitutoria-calibration-period');
    echo $OUTPUT->heading($period['label'], 3);
    echo html_writer::tag(
        'p',
        get_string('calibrationwindow', 'assignfeedback_aitutoria', (object) [
            'from' => userdate($period['timefrom']),
            'to' => userdate($now),
        ]),
        ['class' => 'text-muted']
    );

    $table = new html_table();
    $table->head = [
        get_string('metric', 'assignfeedback_aitutoria'),
        get_string('count', 'assignfeedback_aitutoria'),
        get_string('percentofreviewed', 'assignfeedback_aitutoria'),
    ];
    $table->data = [
        [
            get_string('reviewedwithai', 'assignfeedback_aitutoria'),
            (string) $human['reviewedwithai'],
            $human['reviewedwithai'] > 0 ? '100%' : '-',
        ],
        [
            get_string('decision_accepted_ai', 'assignfeedback_aitutoria'),
            (string) $human['decisions']['accepted_ai'],
            $pct($human, 'accepted_ai'),
        ],
        [
            get_string('decision_overridden_ai', 'assignfeedback_aitutoria'),
            (string) $human['decisions']['overridden_ai'],
            $pct($human, 'overridden_ai'),
        ],
        [
            get_string('decision_rejected_ai', 'assignfeedback_aitutoria'),
            (string) $human['decisions']['rejected_ai'],
            $pct($human, 'rejected_ai'),
        ],
        [
            get_string('decision_escalated', 'assignfeedback_aitutoria'),
            (string) $human['decisions']['escalated'],
            $pct($human, 'escalated'),
        ],
        [
            get_string('acceptancerate', 'assignfeedback_aitutoria'),
            $human['acceptancerate'] === null ? '-' : $human['acceptancerate'] . '%',
            '',
        ],
        [
            get_string('agreementrate', 'assignfeedback_aitutoria'),
            $gov['calibration']['agreementrate'] === null
                ? '-'
                : $gov['calibration']['agreementrate'] . '%',
            get_string('calibrationcompared', 'assignfeedback_aitutoria')
                . ': '
                . (string) $gov['calibration']['compared'],
        ],
    ];
    echo html_writer::table($table);
    echo html_writer::end_div();
}

echo $OUTPUT->footer();
