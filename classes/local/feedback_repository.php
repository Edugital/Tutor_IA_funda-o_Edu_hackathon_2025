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

/**
 * Persistence boundary for AI Tutoring feedback.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class feedback_repository {
    /** Database table. */
    private const TABLE = 'assignfeedback_aitutoria';

    /**
     * Fetch a record by Moodle assignment grade id.
     *
     * @param int $gradeid Grade id.
     * @return \stdClass|false
     */
    public static function get_by_grade(int $gradeid) {
        global $DB;

        return $DB->get_record(self::TABLE, ['grade' => $gradeid]);
    }

    /**
     * Save feedback after a human decision.
     *
     * @param int $assignmentid Assignment instance id.
     * @param int $gradeid Grade id.
     * @param string $humantext Human-authored feedback.
     * @param bool $acceptsuggestion Legacy explicit acceptance flag.
     * @param string $reviewaction Complete Human in Control action.
     * @return \stdClass Saved record.
     */
    public static function save_human_feedback(
        int $assignmentid,
        int $gradeid,
        string $humantext,
        bool $acceptsuggestion,
        string $reviewaction = ''
    ): \stdClass {
        global $DB;

        self::require_valid_grade($assignmentid, $gradeid);

        $record = self::get_by_grade($gradeid);
        $suggestion = $record ? (string) ($record->aisuggestion ?? '') : '';
        $reviewaction = trim($reviewaction);
        if ($reviewaction === '') {
            $reviewaction = $acceptsuggestion
                ? decision_policy::ACTION_ACCEPT
                : decision_policy::ACTION_MANUAL;
        }
        $resolved = decision_policy::resolve_review($humantext, $suggestion, $reviewaction);
        $now = time();

        if (!$record) {
            $record = (object) [
                'assignment' => $assignmentid,
                'grade' => $gradeid,
                'aisuggestion' => null,
                'model' => null,
                'promptversion' => null,
                'rubricversion' => null,
                'timecreated' => $now,
            ];
        }

        $record->feedbacktext = $resolved['text'];
        $record->feedbackformat = FORMAT_PLAIN;
        $record->aistatus = $resolved['aistatus'];
        $record->decision = $resolved['decision'];
        $record->timemodified = $now;

        if (empty($record->id)) {
            $record->id = $DB->insert_record(self::TABLE, $record);
        } else {
            $DB->update_record(self::TABLE, $record);
        }

        return $record;
    }

    /**
     * Store an unpublished suggestion for later human review.
     *
     * No user-facing feedback or numeric grade is changed by this method.
     * The method is intentionally internal; a future provider/task layer must
     * call it only after authorization, policy and data-governance checks.
     *
     * @param int $assignmentid Assignment instance id.
     * @param int $gradeid Grade id.
     * @param string $suggestion Suggested feedback.
     * @param string $model Provider model identifier.
     * @param string $promptversion Prompt/template version.
     * @param string $rubricversion Rubric version.
     * @return \stdClass Saved record.
     */
    public static function store_ai_suggestion(
        int $assignmentid,
        int $gradeid,
        string $suggestion,
        string $model = '',
        string $promptversion = '',
        string $rubricversion = ''
    ): \stdClass {
        global $DB;

        if (!get_config('assignfeedback_aitutoria', 'allowaisuggestions')) {
            throw new \coding_exception('AI suggestions are disabled at site level.');
        }

        self::require_valid_grade($assignmentid, $gradeid);

        $suggestion = trim($suggestion);
        if ($suggestion === '') {
            throw new \invalid_parameter_exception('AI suggestion must not be empty.');
        }

        $record = self::get_by_grade($gradeid);
        $now = time();

        if (!$record) {
            $record = (object) [
                'assignment' => $assignmentid,
                'grade' => $gradeid,
                'feedbacktext' => null,
                'feedbackformat' => FORMAT_PLAIN,
                'decision' => 'manual',
                'timecreated' => $now,
            ];
        }

        $record->aisuggestion = $suggestion;
        $record->aistatus = 'ready';
        $record->model = \core_text::substr(trim($model), 0, 100) ?: null;
        $record->promptversion = \core_text::substr(trim($promptversion), 0, 50) ?: null;
        $record->rubricversion = \core_text::substr(trim($rubricversion), 0, 50) ?: null;
        $record->timemodified = $now;

        if (empty($record->id)) {
            $record->id = $DB->insert_record(self::TABLE, $record);
        } else {
            $DB->update_record(self::TABLE, $record);
        }

        return $record;
    }

    /**
     * Delete all plugin data for an assignment in foreign-key order.
     *
     * @param int $assignmentid Assignment instance id.
     */
    public static function delete_for_assignment(int $assignmentid): void {
        global $DB;

        $DB->delete_records('assignfeedback_aitutoria_hcr', ['assignment' => $assignmentid]);
        $jobids = $DB->get_fieldset_select(
            'assignfeedback_aitutoria_job',
            'id',
            'assignment = :assignment',
            ['assignment' => $assignmentid]
        );
        if (!empty($jobids)) {
            [$jobsql, $jobparams] = $DB->get_in_or_equal($jobids, SQL_PARAMS_NAMED, 'job');
            $DB->delete_records_select('assignfeedback_aitutoria_aud', "jobid {$jobsql}", $jobparams);
            $DB->delete_records_select('assignfeedback_aitutoria_crt', "jobid {$jobsql}", $jobparams);
            $DB->delete_records_select('assignfeedback_aitutoria_snp', "jobid {$jobsql}", $jobparams);
        }

        $DB->delete_records('assignfeedback_aitutoria_aud', ['assignment' => $assignmentid]);
        $DB->delete_records('assignfeedback_aitutoria_job', ['assignment' => $assignmentid]);
        $DB->delete_records(self::TABLE, ['assignment' => $assignmentid]);
    }

    /**
     * Ensure the grade belongs to the supplied assignment.
     *
     * @param int $assignmentid Assignment instance id.
     * @param int $gradeid Grade id.
     */
    private static function require_valid_grade(int $assignmentid, int $gradeid): void {
        global $DB;

        if (!$DB->record_exists('assign_grades', ['id' => $gradeid, 'assignment' => $assignmentid])) {
            throw new \invalid_parameter_exception('The grade does not belong to the assignment.');
        }
    }
}
