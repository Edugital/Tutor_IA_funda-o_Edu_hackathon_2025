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
 * Queue and optionally execute an advisory assessment for one student.
 *
 * php mod/assign/feedback/aitutoria/cli/queue_assessment.php --assignmentid=10 --userid=6 --execute
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use assignfeedback_aitutoria\local\assessment_service;
use assignfeedback_aitutoria\local\request_factory;

list($options, $unrecognized) = cli_get_params(
    [
        'assignmentid' => null,
        'userid' => null,
        'execute' => false,
        'help' => false,
    ],
    [
        'h' => 'help',
        'e' => 'execute',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if (!empty($options['help']) || empty($options['assignmentid']) || empty($options['userid'])) {
    $help = "Queue AI Tutoring advisory assessment for one student submission.

Options:
--assignmentid=INT   Assign instance id (not cmid)
--userid=INT         Student user id
--execute            Run the job immediately (sync) instead of only queueing adhoc
-h, --help           Print help
";
    echo $help;
    exit(0);
}

if (!get_config('assignfeedback_aitutoria', 'allowaisuggestions')) {
    cli_error('allowaisuggestions is disabled. Enable it before queueing production suggestions.');
}

$assignmentid = (int) $options['assignmentid'];
$userid = (int) $options['userid'];
$built = request_factory::from_user_submission($assignmentid, $userid);
$queued = assessment_service::queue($built['request'], $built['provider'], null, empty($options['execute']));
$job = $queued['job'];

cli_writeln('jobid=' . $job->id . ' created=' . ($queued['created'] ? '1' : '0') . ' status=' . $job->status);

if (!empty($options['execute'])) {
    $completed = assessment_service::execute((int) $job->id, $built['provider']);
    cli_writeln('executed status=' . $completed->status);
    if (!empty($completed->failurereason)) {
        cli_writeln('failurereason=' . $completed->failurereason);
    }
}

exit(0);
