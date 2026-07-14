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
 * Backup support for AI Tutoring assignment feedback.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines grade-level backup data for the subplugin.
 */
class backup_assignfeedback_aitutoria_subplugin extends backup_subplugin {
    /**
     * Define feedback and private assessment artifacts attached to a grade.
     *
     * @return backup_subplugin_element
     */
    protected function define_grade_subplugin_structure() {
        $subplugin = $this->get_subplugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $feedback = new backup_nested_element(
            'feedback_aitutoria',
            null,
            [
                'grade',
                'feedbacktext',
                'feedbackformat',
                'aisuggestion',
                'aistatus',
                'decision',
                'model',
                'promptversion',
                'rubricversion',
                'timecreated',
                'timemodified',
            ]
        );
        $jobs = new backup_nested_element('assessment_jobs');
        $job = new backup_nested_element(
            'assessment_job',
            ['id'],
            [
                'grade',
                'status',
                'idempotencykey',
                'provider',
                'model',
                'promptversion',
                'suggestiontext',
                'scoringjson',
                'attempts',
                'maxattempts',
                'lasterror',
                'timeavailable',
                'timecreated',
                'timemodified',
            ]
        );
        $snapshot = new backup_nested_element(
            'assessment_snapshot',
            ['id'],
            [
                'jobid',
                'submissionhash',
                'submissiontext',
                'rubricjson',
                'rubricversion',
                'policyjson',
                'timecreated',
            ]
        );
        $criteria = new backup_nested_element('assessment_criteria');
        $criterion = new backup_nested_element(
            'assessment_criterion',
            ['id'],
            [
                'jobid',
                'criterionkey',
                'proposedlevel',
                'rationale',
                'evidencejson',
                'uncertainty',
                'requireshuman',
                'timecreated',
            ]
        );
        $humancriteria = new backup_nested_element('human_criteria');
        $humancriterion = new backup_nested_element(
            'human_criterion',
            ['id'],
            [
                'grade',
                'jobid',
                'criterionkey',
                'selectedlevel',
                'aiproposedlevel',
                'matchesai',
                'reviewerid',
                'timecreated',
                'timemodified',
            ]
        );
        $auditevents = new backup_nested_element('audit_events');
        $auditevent = new backup_nested_element(
            'audit_event',
            ['id'],
            [
                'grade',
                'jobid',
                'action',
                'actorid',
                'payloadjson',
                'timecreated',
            ]
        );

        $subplugin->add_child($wrapper);
        $wrapper->add_child($feedback);
        $wrapper->add_child($jobs);
        $jobs->add_child($job);
        $job->add_child($snapshot);
        $job->add_child($criteria);
        $criteria->add_child($criterion);
        $wrapper->add_child($humancriteria);
        $humancriteria->add_child($humancriterion);
        $wrapper->add_child($auditevents);
        $auditevents->add_child($auditevent);

        $feedback->set_source_table('assignfeedback_aitutoria', ['grade' => backup::VAR_PARENTID]);
        $job->set_source_table('assignfeedback_aitutoria_job', ['grade' => backup::VAR_PARENTID]);
        $snapshot->set_source_table('assignfeedback_aitutoria_snp', ['jobid' => backup::VAR_PARENTID]);
        $criterion->set_source_table('assignfeedback_aitutoria_crt', ['jobid' => backup::VAR_PARENTID]);
        $humancriterion->set_source_table('assignfeedback_aitutoria_hcr', ['grade' => backup::VAR_PARENTID]);
        $humancriterion->annotate_ids('user', 'reviewerid');
        $auditevent->set_source_table('assignfeedback_aitutoria_aud', ['grade' => backup::VAR_PARENTID]);
        $auditevent->annotate_ids('user', 'actorid');

        return $subplugin;
    }
}
