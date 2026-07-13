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
 * Restore support for AI Tutoring assignment feedback.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restores grade-level feedback and private assessment artifacts.
 */
class restore_assignfeedback_aitutoria_subplugin extends restore_subplugin {
    /**
     * Define restore paths.
     *
     * @return restore_path_element[]
     */
    protected function define_grade_subplugin_structure() {
        return [
            new restore_path_element(
                $this->get_namefor('grade'),
                $this->get_pathfor('/feedback_aitutoria')
            ),
            new restore_path_element(
                $this->get_namefor('assessment_job'),
                $this->get_pathfor('/assessment_jobs/assessment_job')
            ),
            new restore_path_element(
                $this->get_namefor('assessment_snapshot'),
                $this->get_pathfor('/assessment_jobs/assessment_job/assessment_snapshot')
            ),
            new restore_path_element(
                $this->get_namefor('assessment_criterion'),
                $this->get_pathfor('/assessment_jobs/assessment_job/assessment_criteria/assessment_criterion')
            ),
            new restore_path_element(
                $this->get_namefor('audit_event'),
                $this->get_pathfor('/audit_events/audit_event')
            ),
        ];
    }

    /**
     * Restore one feedback record.
     *
     * @param mixed $data Restored XML data.
     */
    public function process_assignfeedback_aitutoria_grade($data) {
        global $DB;

        $data = (object) $data;
        $data->assignment = $this->get_new_parentid('assign');
        $data->grade = $this->get_mappingid('grade', $data->grade);

        if (empty($data->grade)) {
            return;
        }

        unset($data->id);
        $DB->insert_record('assignfeedback_aitutoria', $data);
    }

    /**
     * Restore one advisory assessment job.
     *
     * @param mixed $data Restored XML data.
     */
    public function process_assignfeedback_aitutoria_assessment_job($data) {
        global $DB;

        $data = (object) $data;
        $oldid = (int) $data->id;
        $data->assignment = $this->get_new_parentid('assign');
        $data->grade = $this->get_mappingid('grade', $data->grade);

        if (empty($data->grade)) {
            return;
        }

        $data->idempotencykey = hash(
            'sha256',
            $data->idempotencykey . ':' . $data->assignment . ':' . $data->grade . ':' . $oldid
        );
        if ($data->status !== 'complete') {
            $data->status = 'failed';
            $data->lasterror = 'Restored job requires explicit reprocessing.';
            $data->timeavailable = 0;
        }

        unset($data->id);
        $newid = $DB->insert_record('assignfeedback_aitutoria_job', $data);
        $this->set_mapping('assignfeedback_aitutoria_job', $oldid, $newid);
    }

    /**
     * Restore one immutable assessment snapshot.
     *
     * @param mixed $data Restored XML data.
     */
    public function process_assignfeedback_aitutoria_assessment_snapshot($data) {
        global $DB;

        $data = (object) $data;
        $data->jobid = $this->get_mappingid('assignfeedback_aitutoria_job', $data->jobid);
        if (empty($data->jobid)) {
            return;
        }

        unset($data->id);
        $DB->insert_record('assignfeedback_aitutoria_snp', $data);
    }

    /**
     * Restore one criterion-level advisory result.
     *
     * @param mixed $data Restored XML data.
     */
    public function process_assignfeedback_aitutoria_assessment_criterion($data) {
        global $DB;

        $data = (object) $data;
        $data->jobid = $this->get_mappingid('assignfeedback_aitutoria_job', $data->jobid);
        if (empty($data->jobid)) {
            return;
        }

        unset($data->id);
        $DB->insert_record('assignfeedback_aitutoria_crt', $data);
    }

    /**
     * Restore one audit event with mapped user and job identifiers.
     *
     * @param mixed $data Restored XML data.
     */
    public function process_assignfeedback_aitutoria_audit_event($data) {
        global $DB;

        $data = (object) $data;
        $data->assignment = $this->get_new_parentid('assign');
        $data->grade = $this->get_mappingid('grade', $data->grade);
        if (empty($data->grade)) {
            return;
        }

        if (!empty($data->jobid)) {
            $data->jobid = $this->get_mappingid('assignfeedback_aitutoria_job', $data->jobid, null);
        }
        if (!empty($data->actorid)) {
            $data->actorid = $this->get_mappingid('user', $data->actorid, null);
        }

        unset($data->id);
        $DB->insert_record('assignfeedback_aitutoria_aud', $data);
    }
}
