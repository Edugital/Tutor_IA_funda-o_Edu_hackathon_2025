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
        $collection->add_database_table('assignfeedback_aitutoria', [
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
        ], 'privacy:metadata:tablesummary');
        $collection->add_database_table('assignfeedback_aitutoria_job', [
            'assignment' => 'privacy:metadata:assignment',
            'grade' => 'privacy:metadata:grade',
            'status' => 'privacy:metadata:jobstatus',
            'provider' => 'privacy:metadata:provider',
            'model' => 'privacy:metadata:model',
            'promptversion' => 'privacy:metadata:promptversion',
            'suggestiontext' => 'privacy:metadata:aisuggestion',
            'scoringjson' => 'privacy:metadata:scoring',
            'lasterror' => 'privacy:metadata:lasterror',
            'timecreated' => 'privacy:metadata:timecreated',
            'timemodified' => 'privacy:metadata:timemodified',
        ], 'privacy:metadata:jobsummary');
        $collection->add_database_table('assignfeedback_aitutoria_snp', [
            'jobid' => 'privacy:metadata:jobid',
            'submissionhash' => 'privacy:metadata:submissionhash',
            'submissiontext' => 'privacy:metadata:submissiontext',
            'rubricjson' => 'privacy:metadata:rubricjson',
            'policyjson' => 'privacy:metadata:policyjson',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:snapshotsummary');
        $collection->add_database_table('assignfeedback_aitutoria_crt', [
            'jobid' => 'privacy:metadata:jobid',
            'criterionkey' => 'privacy:metadata:criterionkey',
            'proposedlevel' => 'privacy:metadata:proposedlevel',
            'rationale' => 'privacy:metadata:rationale',
            'evidencejson' => 'privacy:metadata:evidence',
            'uncertainty' => 'privacy:metadata:uncertainty',
            'requireshuman' => 'privacy:metadata:requireshuman',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:criterionsummary');
        $collection->add_database_table('assignfeedback_aitutoria_hcr', [
            'assignment' => 'privacy:metadata:assignment',
            'grade' => 'privacy:metadata:grade',
            'jobid' => 'privacy:metadata:jobid',
            'criterionkey' => 'privacy:metadata:criterionkey',
            'selectedlevel' => 'privacy:metadata:selectedlevel',
            'aiproposedlevel' => 'privacy:metadata:proposedlevel',
            'matchesai' => 'privacy:metadata:matchesai',
            'reviewerid' => 'privacy:metadata:reviewerid',
            'timecreated' => 'privacy:metadata:timecreated',
            'timemodified' => 'privacy:metadata:timemodified',
        ], 'privacy:metadata:humancriterionsummary');
        $collection->add_database_table('assignfeedback_aitutoria_aud', [
            'assignment' => 'privacy:metadata:assignment',
            'grade' => 'privacy:metadata:grade',
            'jobid' => 'privacy:metadata:jobid',
            'action' => 'privacy:metadata:auditaction',
            'actorid' => 'privacy:metadata:actorid',
            'payloadjson' => 'privacy:metadata:auditpayload',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:auditsummary');

        return $collection;
    }

    /** @param int $userid User id. @param contextlist $contextlist Context list. */
    public static function get_context_for_userid_within_feedback(int $userid, contextlist $contextlist) {
        // Contexts are provided by mod_assign grades.
    }

    /** @param useridlist $useridlist User id list. */
    public static function get_student_user_ids(useridlist $useridlist) {
        // Student ids are provided by mod_assign grades.
    }

    /** @param \core_privacy\local\request\userlist $userlist User list. */
    public static function get_userids_from_context(\core_privacy\local\request\userlist $userlist) {
        // No additional user lookup is required.
    }

    /**
     * Export feedback and private assessment artifacts for a student's grade.
     *
     * @param assign_plugin_request_data $exportdata Export request.
     */
    public static function export_feedback_user_data(assign_plugin_request_data $exportdata) {
        global $DB;

        $gradeid = (int) $exportdata->get_pluginobject()->id;
        $assignmentid = (int) $exportdata->get_assignid();
        $basepath = array_merge($exportdata->get_subcontext(), [get_string('privacy:path', 'assignfeedback_aitutoria')]);
        $record = feedback_repository::get_by_grade($gradeid);
        $humancriteria = $DB->get_records('assignfeedback_aitutoria_hcr', ['grade' => $gradeid], 'criterionkey ASC');

        if ($record || !empty($humancriteria)) {
            $data = (object) [
                'feedbacktext' => $record->feedbacktext ?? null,
                'aisuggestion' => $record->aisuggestion ?? null,
                'aistatus' => $record->aistatus ?? null,
                'decision' => $record->decision ?? null,
                'model' => $record->model ?? null,
                'promptversion' => $record->promptversion ?? null,
                'rubricversion' => $record->rubricversion ?? null,
                'timecreated' => $record ? transform::datetime($record->timecreated) : null,
                'timemodified' => $record ? transform::datetime($record->timemodified) : null,
                'humancriteria' => array_values(array_map(
                    static fn(\stdClass $criterion): array => [
                        'criterionkey' => $criterion->criterionkey,
                        'selectedlevel' => $criterion->selectedlevel,
                        'aiproposedlevel' => $criterion->aiproposedlevel,
                        'matchesai' => $criterion->matchesai === null ? null : (bool) $criterion->matchesai,
                        'reviewerid' => $criterion->reviewerid,
                        'timecreated' => transform::datetime($criterion->timecreated),
                        'timemodified' => transform::datetime($criterion->timemodified),
                    ],
                    $humancriteria
                )),
            ];
            writer::with_context($exportdata->get_context())->export_data($basepath, $data);
        }

        $jobs = $DB->get_records('assignfeedback_aitutoria_job', [
            'assignment' => $assignmentid,
            'grade' => $gradeid,
        ], 'id ASC');
        foreach ($jobs as $job) {
            $snapshot = $DB->get_record('assignfeedback_aitutoria_snp', ['jobid' => $job->id]);
            $criteria = $DB->get_records('assignfeedback_aitutoria_crt', ['jobid' => $job->id], 'id ASC');
            $audit = $DB->get_records('assignfeedback_aitutoria_aud', [
                'assignment' => $assignmentid,
                'grade' => $gradeid,
                'jobid' => $job->id,
            ], 'id ASC');
            $jobdata = (object) [
                'status' => $job->status,
                'provider' => $job->provider,
                'model' => $job->model,
                'promptversion' => $job->promptversion,
                'suggestion' => $job->suggestiontext,
                'scoring' => self::decode_json($job->scoringjson),
                'attempts' => (int) $job->attempts,
                'lasterror' => $job->lasterror,
                'timecreated' => transform::datetime($job->timecreated),
                'timemodified' => transform::datetime($job->timemodified),
                'snapshot' => $snapshot ? (object) [
                    'submissionhash' => $snapshot->submissionhash,
                    'submissiontext' => $snapshot->submissiontext,
                    'rubric' => self::decode_json($snapshot->rubricjson),
                    'rubricversion' => $snapshot->rubricversion,
                    'policy' => self::decode_json($snapshot->policyjson),
                    'timecreated' => transform::datetime($snapshot->timecreated),
                ] : null,
                'criteria' => array_values(array_map(
                    static fn(\stdClass $criterion): array => [
                        'criterionkey' => $criterion->criterionkey,
                        'proposedlevel' => $criterion->proposedlevel,
                        'rationale' => $criterion->rationale,
                        'evidence' => self::decode_json($criterion->evidencejson),
                        'uncertainty' => $criterion->uncertainty,
                        'requireshuman' => (bool) $criterion->requireshuman,
                        'timecreated' => transform::datetime($criterion->timecreated),
                    ],
                    $criteria
                )),
                'audit' => array_values(array_map(
                    static fn(\stdClass $event): array => [
                        'action' => $event->action,
                        'payload' => self::decode_json($event->payloadjson),
                        'timecreated' => transform::datetime($event->timecreated),
                    ],
                    $audit
                )),
            ];
            $path = array_merge($basepath, [get_string('privacy:assessmentjob', 'assignfeedback_aitutoria', $job->id)]);
            writer::with_context($exportdata->get_context())->export_data($path, $jobdata);
        }
    }

    /** @param assign_plugin_request_data $requestdata Deletion request. */
    public static function delete_feedback_for_context(assign_plugin_request_data $requestdata) {
        self::delete_engine_data((int) $requestdata->get_assignid());
    }

    /** @param assign_plugin_request_data $requestdata Deletion request. */
    public static function delete_feedback_for_grade(assign_plugin_request_data $requestdata) {
        self::delete_engine_data((int) $requestdata->get_assignid(), [(int) $requestdata->get_pluginobject()->id]);
    }

    /** @param assign_plugin_request_data $deletedata Deletion request. */
    public static function delete_feedback_for_grades(assign_plugin_request_data $deletedata) {
        $gradeids = array_map('intval', $deletedata->get_gradeids());
        if (!empty($gradeids)) {
            self::delete_engine_data((int) $deletedata->get_assignid(), $gradeids);
        }
    }

    /**
     * Delete all grade-linked artifacts in foreign-key order.
     *
     * @param int $assignmentid Assignment id.
     * @param int[]|null $gradeids Grade ids or null for all assignment grades.
     */
    private static function delete_engine_data(int $assignmentid, ?array $gradeids = null): void {
        global $DB;

        $params = ['assignment' => $assignmentid];
        $where = 'assignment = :assignment';
        if ($gradeids !== null) {
            [$gradesql, $gradeparams] = $DB->get_in_or_equal($gradeids, SQL_PARAMS_NAMED, 'grade');
            $where .= " AND grade {$gradesql}";
            $params += $gradeparams;
        }

        $DB->delete_records_select('assignfeedback_aitutoria_hcr', $where, $params);
        $jobids = $DB->get_fieldset_select('assignfeedback_aitutoria_job', 'id', $where, $params);
        if (!empty($jobids)) {
            [$jobsql, $jobparams] = $DB->get_in_or_equal($jobids, SQL_PARAMS_NAMED, 'job');
            $DB->delete_records_select('assignfeedback_aitutoria_aud', "jobid {$jobsql}", $jobparams);
            $DB->delete_records_select('assignfeedback_aitutoria_crt', "jobid {$jobsql}", $jobparams);
            $DB->delete_records_select('assignfeedback_aitutoria_snp', "jobid {$jobsql}", $jobparams);
        }
        $DB->delete_records_select('assignfeedback_aitutoria_aud', $where, $params);
        $DB->delete_records_select('assignfeedback_aitutoria_job', $where, $params);
        $DB->delete_records_select('assignfeedback_aitutoria', $where, $params);
    }

    /**
     * Decode stored JSON without breaking a privacy export.
     *
     * @param string|null $json Stored JSON.
     * @return mixed
     */
    private static function decode_json(?string $json) {
        if ($json === null || trim($json) === '') {
            return null;
        }
        try {
            return json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return ['error' => 'Stored JSON could not be decoded.'];
        }
    }
}
