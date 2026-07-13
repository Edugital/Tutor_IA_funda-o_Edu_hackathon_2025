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
 * Administrative governance and health dashboard.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');

use assignfeedback_aitutoria\local\reporting\governance_report;
use assignfeedback_aitutoria\local\reporting\health_report;

require_login();
$context = context_system::instance();
require_capability('assignfeedback/aitutoria:viewgovernance', $context);

$assignmentid = optional_param('assignmentid', 0, PARAM_INT);
$format = optional_param('format', 'html', PARAM_ALPHA);
$urlparams = $assignmentid > 0 ? ['assignmentid' => $assignmentid] : [];
$url = new moodle_url('/mod/assign/feedback/aitutoria/report.php', $urlparams);
$health = health_report::build();
$governance = governance_report::build($assignmentid > 0 ? $assignmentid : null);

if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="aitutoria-governance-report.json"');
    echo json_encode(
        ['health' => $health, 'governance' => $governance],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    exit;
}

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('governancereport', 'assignfeedback_aitutoria'));
$PAGE->set_heading(get_string('governancereport', 'assignfeedback_aitutoria'));
$downloadurl = new moodle_url(
    '/mod/assign/feedback/aitutoria/report.php',
    $urlparams + ['format' => 'json']
);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('governancereport', 'assignfeedback_aitutoria'));
echo html_writer::div(
    html_writer::link(
        $downloadurl,
        get_string('downloadreportjson', 'assignfeedback_aitutoria'),
        ['class' => 'btn btn-secondary mb-3']
    )
);

echo $OUTPUT->heading(get_string('healthstatus', 'assignfeedback_aitutoria'), 3);
$statusclass = $health['status'] === 'ok'
    ? 'alert alert-success'
    : ($health['status'] === 'warning' ? 'alert alert-warning' : 'alert alert-danger');
echo html_writer::div(
    get_string('healthstatus_' . $health['status'], 'assignfeedback_aitutoria'),
    $statusclass
);
if (!empty($health['issues'])) {
    $items = [];
    foreach ($health['issues'] as $issue) {
        $items[] = html_writer::tag(
            'strong',
            s(strtoupper($issue['severity']))
        ) . ': ' . s($issue['message']);
    }
    echo html_writer::alist($items);
}

$healthtable = new html_table();
$healthtable->head = [
    get_string('metric', 'assignfeedback_aitutoria'),
    get_string('value', 'assignfeedback_aitutoria'),
];
$healthtable->data = [
    [
        get_string('allowaisuggestions', 'assignfeedback_aitutoria'),
        $health['settings']['allowaisuggestions'] ? get_string('yes') : get_string('no'),
    ],
    [
        get_string('retentiondays', 'assignfeedback_aitutoria'),
        (string) $health['settings']['retentiondays'],
    ],
    [
        get_string('processingtimeoutminutes', 'assignfeedback_aitutoria'),
        (string) $health['settings']['processingtimeoutminutes'],
    ],
    [
        get_string('institutionalframework', 'assignfeedback_aitutoria'),
        $health['framework']['configured']
            ? (string) $health['framework']['version']
            : get_string('notconfigured', 'assignfeedback_aitutoria'),
    ],
    [
        get_string('frameworkcompetencies', 'assignfeedback_aitutoria'),
        (string) $health['framework']['competencycount'],
    ],
    [
        get_string('productionproviders', 'assignfeedback_aitutoria'),
        empty($health['providers'])
            ? get_string('none', 'assignfeedback_aitutoria')
            : s(implode(', ', array_keys($health['providers']))),
    ],
    [
        get_string('staleprocessingjobs', 'assignfeedback_aitutoria'),
        (string) $health['jobs']['staleprocessing'],
    ],
    [
        get_string('overduequeuedjobs', 'assignfeedback_aitutoria'),
        (string) $health['jobs']['overduequeued'],
    ],
];
echo html_writer::table($healthtable);

echo $OUTPUT->heading(get_string('jobstatus', 'assignfeedback_aitutoria'), 3);
$jobtable = new html_table();
$jobtable->head = [
    get_string('status'),
    get_string('count', 'assignfeedback_aitutoria'),
];
foreach ($health['jobs']['status'] as $status => $count) {
    $jobtable->data[] = [s($status), (string) $count];
}
echo html_writer::table($jobtable);

if (!empty($health['jobs']['recentfailures'])) {
    echo $OUTPUT->heading(get_string('recentfailures', 'assignfeedback_aitutoria'), 3);
    $failuretable = new html_table();
    $failuretable->head = [
        get_string('jobid', 'assignfeedback_aitutoria'),
        get_string('assignmentid', 'assignfeedback_aitutoria'),
        get_string('provider', 'assignfeedback_aitutoria'),
        get_string('attempts', 'assignfeedback_aitutoria'),
        get_string('error'),
        get_string('lastmodified'),
    ];
    foreach ($health['jobs']['recentfailures'] as $failure) {
        $failuretable->data[] = [
            (string) $failure['jobid'],
            (string) $failure['assignmentid'],
            s($failure['provider']),
            $failure['attempts'] . '/' . $failure['maxattempts'],
            s($failure['error']),
            userdate($failure['timemodified']),
        ];
    }
    echo html_writer::table($failuretable);
}

echo $OUTPUT->heading(get_string('governancemetrics', 'assignfeedback_aitutoria'), 3);
$governancetable = new html_table();
$governancetable->head = [
    get_string('metric', 'assignfeedback_aitutoria'),
    get_string('value', 'assignfeedback_aitutoria'),
];
$governancetable->data = [
    [
        get_string('totaljobs', 'assignfeedback_aitutoria'),
        (string) $governance['jobs']['total'],
    ],
    [
        get_string('averageadvisorypercentage', 'assignfeedback_aitutoria'),
        $governance['jobs']['averageadvisorypercentage'] === null
            ? '-'
            : $governance['jobs']['averageadvisorypercentage'] . '%',
    ],
    [
        get_string('totalcriteria', 'assignfeedback_aitutoria'),
        (string) $governance['criteria']['total'],
    ],
    [
        get_string('requiringhumanreview', 'assignfeedback_aitutoria'),
        (string) $governance['criteria']['requiringhumanreview'],
    ],
    [
        get_string('humanreviews', 'assignfeedback_aitutoria'),
        (string) $governance['humanreview']['total'],
    ],
    [
        get_string('acceptancerate', 'assignfeedback_aitutoria'),
        $governance['humanreview']['acceptancerate'] === null
            ? '-'
            : $governance['humanreview']['acceptancerate'] . '%',
    ],
    [
        get_string('calibrationreviews', 'assignfeedback_aitutoria'),
        (string) $governance['calibration']['total'],
    ],
    [
        get_string('calibrationcompared', 'assignfeedback_aitutoria'),
        (string) $governance['calibration']['compared'],
    ],
    [
        get_string('agreementrate', 'assignfeedback_aitutoria'),
        $governance['calibration']['agreementrate'] === null
            ? '-'
            : $governance['calibration']['agreementrate'] . '%',
    ],
];
echo html_writer::table($governancetable);

if (!empty($governance['calibration']['percriterion'])) {
    echo $OUTPUT->heading(get_string('criterionagreement', 'assignfeedback_aitutoria'), 3);
    $criteriontable = new html_table();
    $criteriontable->head = [
        get_string('criterion', 'gradingform_rubric'),
        get_string('calibrationcompared', 'assignfeedback_aitutoria'),
        get_string('calibrationmatched', 'assignfeedback_aitutoria'),
        get_string('calibrationmismatched', 'assignfeedback_aitutoria'),
        get_string('agreementrate', 'assignfeedback_aitutoria'),
    ];
    foreach ($governance['calibration']['percriterion'] as $criterionkey => $metrics) {
        $criteriontable->data[] = [
            s($criterionkey),
            (string) $metrics['compared'],
            (string) $metrics['matched'],
            (string) $metrics['mismatched'],
            $metrics['agreementrate'] === null ? '-' : $metrics['agreementrate'] . '%',
        ];
    }
    echo html_writer::table($criteriontable);
}

echo $OUTPUT->footer();
