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
 * One-click advisory AI suggestion generation for teachers.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

use assignfeedback_aitutoria\local\assessment_service;
use assignfeedback_aitutoria\local\request_factory;

$cmid = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/assign:grade', $context);
require_sesskey();

$assign = new assign($context, $cm, $course);
$returnurl = new moodle_url('/mod/assign/view.php', [
    'id' => $cm->id,
    'action' => 'grader',
    'userid' => $userid,
]);

if (!get_config('assignfeedback_aitutoria', 'allowaisuggestions')) {
    throw new moodle_exception('allowaisuggestions', 'assignfeedback_aitutoria');
}

$plugin = null;
foreach ($assign->get_feedback_plugins() as $candidate) {
    if ($candidate->get_type() === 'aitutoria' && $candidate->is_enabled()) {
        $plugin = $candidate;
        break;
    }
}
if (!$plugin) {
    throw new moodle_exception('pluginisdisabled', 'assignfeedback_aitutoria');
}

$PAGE->set_url('/mod/assign/feedback/aitutoria/generate.php', [
    'id' => $cm->id,
    'userid' => $userid,
]);
$PAGE->set_title(get_string('generatesuggestion', 'assignfeedback_aitutoria'));
$PAGE->set_heading($course->fullname);

// Sesskey + grading capability are enough for the teacher action; optional confirm page remains available.
if (!$confirm) {
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('generatesuggestionconfirm', 'assignfeedback_aitutoria'),
        new moodle_url('/mod/assign/feedback/aitutoria/generate.php', [
            'id' => $cm->id,
            'userid' => $userid,
            'confirm' => 1,
            'sesskey' => sesskey(),
        ]),
        $returnurl
    );
    echo $OUTPUT->footer();
    exit;
}

try {
    $built = request_factory::from_user_submission((int) $assign->get_instance()->id, $userid);
    $queued = assessment_service::queue($built['request'], $built['provider'], (int) $USER->id, false);
    $job = assessment_service::execute((int) $queued['job']->id, $built['provider']);
    if ($job->status !== 'complete') {
        throw new moodle_exception(
            'generatefailed',
            'assignfeedback_aitutoria',
            $returnurl,
            (string) ($job->lasterror ?? $job->status)
        );
    }
    redirect(
        $returnurl,
        get_string('generatesuggestionsuccess', 'assignfeedback_aitutoria'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
} catch (Throwable $exception) {
    redirect(
        $returnurl,
        get_string('generatefailed', 'assignfeedback_aitutoria', $exception->getMessage()),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}
