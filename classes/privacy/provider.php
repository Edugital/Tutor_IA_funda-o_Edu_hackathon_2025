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

namespace assignfeedback_aitutoria\privacy;

use assignfeedback_aitutoria\local\feedback_repository;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use mod_assign\privacy\assign_plugin_request_data;
use mod_assign\privacy\useridlist;

/**
 * Privacy provider for AI Tutoring feedback.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \mod_assign\privacy\assignfeedback_provider,
    \mod_assign\privacy\assignfeedback_user_provider {
    /**
     * Describe stored personal data.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'assignfeedback_aitutoria',
            [
                'assignment' => 'privacy:metadata:assignment',
                'grade' => 'privacy:metadata:grade',
                'feedbacktext' => 'privacy:metadata:feedbacktext',
                'aisuggestion' => 'privacy:metadata:aisuggestion',
                'aistatus' => 'privacy:metadata:aistatus',
                'decision' => 'privacy:metadata:decision',
                'model' => 'privacy:metadata:model',
                'promptversion' => 'privacy:metadata:promptversion',
                'rubricversion' => 'privacy:metadata:rubricversion',
                'timecreated' => 'privacy:metadata:timecreated',
                'timemodified' => 'privacy:metadata:timemodified',
            ],
            'privacy:metadata:tablesummary'
        );

        return $collection;
    }

    /**
     * Contexts are obtained from mod_assign grade records.
     *
     * @param int $userid User id.
     * @param contextlist $contextlist Context list.
     */
    public static function get_context_for_userid_within_feedback(int $userid, contextlist $contextlist) {
        // No additional context lookup is required.
    }

    /**
     * Student ids are obtained from mod_assign grade records.
     *
     * @param useridlist $useridlist User id list.
     */
    public static function get_student_user_ids(useridlist $useridlist) {
        // No additional user lookup is required.
    }

    /**
     * No additional user-id lookup is required.
     *
     * @param \core_privacy\local\request\userlist $userlist User list.
     */
    public static function get_userids_from_context(\core_privacy\local\request\userlist $userlist) {
        // No additional user lookup is required.
    }

    /**
     * Export feedback associated with a student's grade.
     *
     * @param assign_plugin_request_data $exportdata Export request.
     */
    public static function export_feedback_user_data(assign_plugin_request_data $exportdata) {
        $gradeid = (int) $exportdata->get_pluginobject()->id;
        $record = feedback_repository::get_by_grade($gradeid);

        if (!$record) {
            return;
        }

        $path = array_merge(
            $exportdata->get_subcontext(),
            [get_string('privacy:path', 'assignfeedback_aitutoria')]
        );

        $data = (object) [
            'feedbacktext' => $record->feedbacktext,
            'aisuggestion' => $record->aisuggestion,
            'aistatus' => $record->aistatus,
            'decision' => $record->decision,
            'model' => $record->model,
            'promptversion' => $record->promptversion,
            'rubricversion' => $record->rubricversion,
            'timecreated' => transform::datetime($record->timecreated),
            'timemodified' => transform::datetime($record->timemodified),
        ];

        writer::with_context($exportdata->get_context())->export_data($path, $data);
    }

    /**
     * Delete all feedback in an assignment context.
     *
     * @param assign_plugin_request_data $requestdata Deletion request.
     */
    public static function delete_feedback_for_context(assign_plugin_request_data $requestdata) {
        $assign = $requestdata->get_assign();
        $plugin = $assign->get_plugin_by_type('assignfeedback', 'aitutoria');
        $plugin->delete_instance();
    }

    /**
     * Delete feedback for one grade.
     *
     * @param assign_plugin_request_data $requestdata Deletion request.
     */
    public static function delete_feedback_for_grade(assign_plugin_request_data $requestdata) {
        global $DB;

        $DB->delete_records('assignfeedback_aitutoria', [
            'assignment' => $requestdata->get_assignid(),
            'grade' => $requestdata->get_pluginobject()->id,
        ]);
    }

    /**
     * Delete feedback for a set of grades.
     *
     * @param assign_plugin_request_data $deletedata Deletion request.
     */
    public static function delete_feedback_for_grades(assign_plugin_request_data $deletedata) {
        global $DB;

        if (empty($deletedata->get_gradeids())) {
            return;
        }

        [$sql, $params] = $DB->get_in_or_equal($deletedata->get_gradeids(), SQL_PARAMS_NAMED);
        $params['assignment'] = $deletedata->get_assignid();

        $DB->delete_records_select(
            'assignfeedback_aitutoria',
            "assignment = :assignment AND grade {$sql}",
            $params
        );
    }
}
