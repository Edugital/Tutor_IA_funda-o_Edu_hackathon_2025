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

namespace assignfeedback_aitutoria\local;

use assignfeedback_aitutoria\local\dto\assessment_request;
use assignfeedback_aitutoria\local\provider\provider_interface;
use assignfeedback_aitutoria\local\provider\provider_registry;

/**
 * Builds assessment requests from live assignment submissions.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class request_factory {
    /**
     * Build a request for one student grade using assignment plugin config.
     *
     * @param int $assignmentid Assign instance id.
     * @param int $userid Student user id.
     * @return array{request: assessment_request, provider: provider_interface, gradeid: int}
     */
    public static function from_user_submission(int $assignmentid, int $userid): array {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $cm = get_coursemodule_from_instance('assign', $assignmentid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $assign = new \assign($context, $cm, get_course($cm->course));

        $plugin = null;
        foreach ($assign->get_feedback_plugins() as $candidate) {
            if ($candidate->get_type() === 'aitutoria') {
                $plugin = $candidate;
                break;
            }
        }
        if (!$plugin || !$plugin->is_enabled()) {
            throw new \moodle_exception('pluginisdisabled', 'assignfeedback_aitutoria');
        }

        $mode = assignment_policy::normalize_mode((string) $plugin->get_config('assessmentmode'));
        if ($mode === assignment_policy::MODE_DISABLED) {
            throw new \invalid_parameter_exception('Assessment mode is disabled for this assignment.');
        }

        $structuredrubric = (string) $plugin->get_config('structuredrubric');
        $rubric = assignment_policy::parse_rubric(
            $structuredrubric,
            (string) get_config('assignfeedback_aitutoria', 'institutionalframeworkjson')
        );

        $submission = $assign->get_user_submission($userid, false);
        if (!$submission || $submission->status === ASSIGN_SUBMISSION_STATUS_NEW) {
            throw new \moodle_exception('submissionnotready', 'assignfeedback_aitutoria');
        }

        $text = self::extract_onlinetext($assign, $submission);
        if (trim($text) === '') {
            throw new \moodle_exception('submissionempty', 'assignfeedback_aitutoria');
        }

        $grade = $assign->get_user_grade($userid, true);
        $request = new assessment_request(
            $assignmentid,
            (int) $grade->id,
            $text,
            $rubric,
            ['mode' => $mode === assignment_policy::MODE_SHADOW
                ? assessment_service::MODE_SHADOW
                : assessment_service::MODE_ASSISTIVE]
        );

        $providername = provider_registry::default_name();
        if ($providername === '') {
            throw new \coding_exception('No production assessment provider is configured.');
        }

        return [
            'request' => $request,
            'provider' => provider_registry::get($providername),
            'gradeid' => (int) $grade->id,
        ];
    }

    /**
     * @param \assign $assign Assignment.
     * @param \stdClass $submission Submission.
     * @return string Plain-ish submission text.
     */
    private static function extract_onlinetext(\assign $assign, \stdClass $submission): string {
        global $DB;
        foreach ($assign->get_submission_plugins() as $plugin) {
            if ($plugin->get_type() !== 'onlinetext' || !$plugin->is_enabled()) {
                continue;
            }
            $record = $DB->get_record('assignsubmission_onlinetext', [
                'assignment' => $assign->get_instance()->id,
                'submission' => $submission->id,
            ]);
            if (!$record) {
                return '';
            }
            return html_to_text((string) $record->onlinetext, 0, false);
        }
        return '';
    }
}
