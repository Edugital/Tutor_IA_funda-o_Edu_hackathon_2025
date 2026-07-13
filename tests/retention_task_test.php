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
use assignfeedback_aitutoria\task\cleanup_assessment_data;

/**
 * Advisory-data retention tests.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignfeedback_aitutoria\task\cleanup_assessment_data
 * @covers     \assignfeedback_aitutoria\local\repository\retention_repository
 */
final class retention_task_test extends \advanced_testcase {
    /**
     * Retention deletes expired private artifacts while preserving official feedback.
     */
    public function test_expired_engine_artifacts_are_deleted_only(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        set_config('allowaisuggestions', 1, 'assignfeedback_aitutoria');
        set_config('retentiondays', 30, 'assignfeedback_aitutoria');
        [$assignmentid, $gradeid] = $this->create_assignment_grade();
        $request = $this->request($assignmentid, $gradeid, assessment_service::MODE_ASSISTIVE);
        $queued = assessment_service::queue($request, new fixture_provider(), null, false);
        assessment_service::execute((int) $queued['job']->id, new fixture_provider());
        feedback_repository::save_human_feedback($assignmentid, $gradeid, 'Official teacher feedback.', false);

        $oldtime = time() - (31 * DAYSECS);
        $DB->set_field('assignfeedback_aitutoria_job', 'timemodified', $oldtime, ['id' => $queued['job']->id]);
        (new cleanup_assessment_data())->execute();

        $this->assertSame(0, $DB->count_records('assignfeedback_aitutoria_job'));
        $this->assertSame(0, $DB->count_records('assignfeedback_aitutoria_snp'));
        $this->assertSame(0, $DB->count_records('assignfeedback_aitutoria_crt'));
        $this->assertSame(0, $DB->count_records('assignfeedback_aitutoria_aud', ['jobid' => $queued['job']->id]));
        $feedback = $DB->get_record('assignfeedback_aitutoria', ['grade' => $gradeid], '*', MUST_EXIST);
        $this->assertSame('Official teacher feedback.', $feedback->feedbacktext);
    }

    /**
     * Retention is disabled when configured as zero.
     */
    public function test_zero_retention_does_not_delete_data(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        set_config('retentiondays', 0, 'assignfeedback_aitutoria');
        [$assignmentid, $gradeid] = $this->create_assignment_grade();
        $queued = assessment_service::queue(
            $this->request($assignmentid, $gradeid, assessment_service::MODE_SHADOW),
            new fixture_provider(),
            null,
            false
        );
        assessment_service::execute((int) $queued['job']->id, new fixture_provider());
        $DB->set_field(
            'assignfeedback_aitutoria_job',
            'timemodified',
            time() - (400 * DAYSECS),
            ['id' => $queued['job']->id]
        );

        (new cleanup_assessment_data())->execute();

        $this->assertSame(1, $DB->count_records('assignfeedback_aitutoria_job'));
        $this->assertSame(1, $DB->count_records('assignfeedback_aitutoria_snp'));
    }

    /**
     * Queued and processing jobs are never removed by retention.
     */
    public function test_active_jobs_are_preserved(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        set_config('retentiondays', 1, 'assignfeedback_aitutoria');
        [$assignmentid, $gradeid] = $this->create_assignment_grade();
        $queued = assessment_service::queue(
            $this->request($assignmentid, $gradeid, assessment_service::MODE_SHADOW),
            new fixture_provider(),
            null,
            false
        );
        $DB->set_field(
            'assignfeedback_aitutoria_job',
            'timemodified',
            time() - (10 * DAYSECS),
            ['id' => $queued['job']->id]
        );

        (new cleanup_assessment_data())->execute();

        $this->assertSame(1, $DB->count_records('assignfeedback_aitutoria_job'));
    }

    /**
     * Create assignment and grade fixtures.
     *
     * @return array{0: int, 1: int}
     */
    private function create_assignment_grade(): array {
        global $DB, $USER;

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $student = $this->getDataGenerator()->create_user();
        $gradeid = $DB->insert_record('assign_grades', (object) [
            'assignment' => $assign->id,
            'userid' => $student->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'grader' => $USER->id,
            'grade' => 70,
            'attemptnumber' => 0,
        ]);

        return [(int) $assign->id, (int) $gradeid];
    }

    /**
     * Build an assessment request fixture.
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
            'Submission retained only for the configured period.',
            [
                'version' => 'retention-v1',
                'criteria' => [[
                    'id' => 'clarity',
                    'title' => 'Clarity',
                    'weight' => 1,
                    'levels' => [
                        ['id' => 'developing', 'label' => 'Developing', 'score' => 1],
                        ['id' => 'proficient', 'label' => 'Proficient', 'score' => 2],
                    ],
                ]],
            ],
            ['mode' => $mode]
        );
    }
}
