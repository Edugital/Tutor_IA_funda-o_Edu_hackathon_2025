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

namespace assignfeedback_aitutoria;

use assignfeedback_aitutoria\local\assessment_service;
use assignfeedback_aitutoria\local\dto\assessment_request;
use assignfeedback_aitutoria\local\feedback_repository;
use assignfeedback_aitutoria\local\provider\fixture_provider;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/tests/backup_restore_base_testcase.php');

/**
 * Full course backup and restore tests.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class backup_restore_test extends \core_backup_backup_restore_base_testcase {
    /**
     * Completed assessment data survives course backup and restore.
     */
    public function test_completed_engine_data_is_restored_with_new_mappings(): void {
        global $DB, $USER;

        $source = $this->getDataGenerator()->create_course(['shortname' => 'source-ai-course']);
        $destination = $this->getDataGenerator()->create_course(['shortname' => 'destination-ai-course']);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $source->id, 'student');
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course' => $source->id,
            'name' => 'Restorable AI assignment',
        ]);
        $this->enable_plugin((int) $assign->id);
        $gradeid = $DB->insert_record('assign_grades', (object) [
            'assignment' => $assign->id,
            'userid' => $student->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'grader' => $USER->id,
            'grade' => 74,
            'attemptnumber' => 0,
        ]);
        $request = $this->request((int) $assign->id, (int) $gradeid, assessment_service::MODE_SHADOW);
        $queued = assessment_service::queue($request, new fixture_provider(), (int) $USER->id, false);
        assessment_service::execute((int) $queued['job']->id, new fixture_provider());
        feedback_repository::save_human_feedback(
            (int) $assign->id,
            (int) $gradeid,
            'Official feedback preserved by backup.',
            false
        );

        $backupid = $this->perform_backup($source);
        $this->perform_restore($backupid, $destination);

        $restoredassign = $DB->get_record(
            'assign',
            ['course' => $destination->id, 'name' => 'Restorable AI assignment'],
            '*',
            MUST_EXIST
        );
        $restoredgrade = $DB->get_record(
            'assign_grades',
            ['assignment' => $restoredassign->id, 'userid' => $student->id],
            '*',
            MUST_EXIST
        );
        $restoredfeedback = $DB->get_record(
            'assignfeedback_aitutoria',
            ['assignment' => $restoredassign->id, 'grade' => $restoredgrade->id],
            '*',
            MUST_EXIST
        );
        $restoredjob = $DB->get_record(
            'assignfeedback_aitutoria_job',
            ['assignment' => $restoredassign->id, 'grade' => $restoredgrade->id],
            '*',
            MUST_EXIST
        );

        $this->assertSame('Official feedback preserved by backup.', $restoredfeedback->feedbacktext);
        $this->assertSame('complete', $restoredjob->status);
        $this->assertNotSame($queued['job']->idempotencykey, $restoredjob->idempotencykey);
        $this->assertNotEmpty($restoredjob->suggestiontext);
        $this->assertNotEmpty($restoredjob->scoringjson);
        $this->assertTrue($DB->record_exists('assignfeedback_aitutoria_snp', ['jobid' => $restoredjob->id]));
        $this->assertSame(2, $DB->count_records('assignfeedback_aitutoria_crt', ['jobid' => $restoredjob->id]));
        $this->assertGreaterThanOrEqual(
            2,
            $DB->count_records('assignfeedback_aitutoria_aud', [
                'assignment' => $restoredassign->id,
                'grade' => $restoredgrade->id,
            ])
        );
        $this->assertSame(74.0, (float) $restoredgrade->grade);
    }

    /**
     * Incomplete jobs are restored as stopped records, never automatically resumed.
     */
    public function test_queued_job_is_restored_as_failed_requiring_explicit_reprocessing(): void {
        global $DB, $USER;

        $source = $this->getDataGenerator()->create_course(['shortname' => 'source-queued-course']);
        $destination = $this->getDataGenerator()->create_course(['shortname' => 'destination-queued-course']);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $source->id, 'student');
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course' => $source->id,
            'name' => 'Queued AI assignment',
        ]);
        $this->enable_plugin((int) $assign->id);
        $gradeid = $DB->insert_record('assign_grades', (object) [
            'assignment' => $assign->id,
            'userid' => $student->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'grader' => $USER->id,
            'grade' => 63,
            'attemptnumber' => 0,
        ]);
        assessment_service::queue(
            $this->request((int) $assign->id, (int) $gradeid, assessment_service::MODE_SHADOW),
            new fixture_provider(),
            null,
            false
        );

        $backupid = $this->perform_backup($source);
        $this->perform_restore($backupid, $destination);

        $restoredassign = $DB->get_record(
            'assign',
            ['course' => $destination->id, 'name' => 'Queued AI assignment'],
            '*',
            MUST_EXIST
        );
        $restoredgrade = $DB->get_record(
            'assign_grades',
            ['assignment' => $restoredassign->id, 'userid' => $student->id],
            '*',
            MUST_EXIST
        );
        $restoredjob = $DB->get_record(
            'assignfeedback_aitutoria_job',
            ['assignment' => $restoredassign->id, 'grade' => $restoredgrade->id],
            '*',
            MUST_EXIST
        );

        $this->assertSame('failed', $restoredjob->status);
        $this->assertSame(0, (int) $restoredjob->timeavailable);
        $this->assertSame('Restored job requires explicit reprocessing.', $restoredjob->lasterror);
        $this->assertEmpty(\core\task\manager::get_adhoc_tasks(
            \assignfeedback_aitutoria\task\process_assessment::class
        ));
    }

    /**
     * Enable the feedback plugin for an assignment so backup includes it.
     *
     * @param int $assignmentid Assignment id.
     */
    private function enable_plugin(int $assignmentid): void {
        global $DB;

        $DB->insert_record('assign_plugin_config', (object) [
            'assignment' => $assignmentid,
            'plugin' => 'aitutoria',
            'subtype' => 'assignfeedback',
            'name' => 'enabled',
            'value' => '1',
        ]);
    }

    /**
     * Build a structured assessment request.
     *
     * @param int $assignmentid Assignment id.
     * @param int $gradeid Grade id.
     * @param string $mode Assessment mode.
     * @return assessment_request
     */
    private function request(int $assignmentid, int $gradeid, string $mode): assessment_request {
        return new assessment_request(
            $assignmentid,
            $gradeid,
            'Submission included in a full backup and restore test.',
            [
                'version' => 'backup-v1',
                'criteria' => [
                    [
                        'id' => 'clarity',
                        'title' => 'Clarity',
                        'weight' => 50,
                        'levels' => [
                            ['id' => 'developing', 'label' => 'Developing', 'score' => 1],
                            ['id' => 'proficient', 'label' => 'Proficient', 'score' => 2],
                        ],
                    ],
                    [
                        'id' => 'evidence',
                        'title' => 'Evidence',
                        'weight' => 50,
                        'levels' => [
                            ['id' => 'developing', 'label' => 'Developing', 'score' => 1],
                            ['id' => 'proficient', 'label' => 'Proficient', 'score' => 2],
                        ],
                    ],
                ],
            ],
            ['mode' => $mode]
        );
    }
}
