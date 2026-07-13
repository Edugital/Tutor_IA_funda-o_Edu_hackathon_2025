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
use assignfeedback_aitutoria\local\provider\fixture_provider;
use assignfeedback_aitutoria\task\process_assessment;

/**
 * Adhoc assessment task tests.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignfeedback_aitutoria\task\process_assessment
 */
final class process_assessment_task_test extends \advanced_testcase {
    /**
     * Equivalent queue requests produce one job and one adhoc task.
     */
    public function test_queue_creates_one_adhoc_task(): void {
        [$assignmentid, $gradeid] = $this->create_assignment_grade();
        $request = $this->request($assignmentid, $gradeid);

        $first = assessment_service::queue($request, new fixture_provider(), null, true);
        $second = assessment_service::queue($request, new fixture_provider(), null, true);
        $tasks = \core\task\manager::get_adhoc_tasks(process_assessment::class);

        $this->assertTrue($first['created']);
        $this->assertFalse($second['created']);
        $this->assertCount(1, $tasks);
        $this->assertSame((int) $first['job']->id, (int) $tasks[0]->get_custom_data()->jobid);
    }

    /**
     * A queued task exits safely when its assignment data was deleted first.
     */
    public function test_task_ignores_deleted_job(): void {
        global $DB;

        [$assignmentid, $gradeid] = $this->create_assignment_grade();
        $queued = assessment_service::queue(
            $this->request($assignmentid, $gradeid),
            new fixture_provider(),
            null,
            true
        );
        $tasks = \core\task\manager::get_adhoc_tasks(process_assessment::class);
        $this->assertCount(1, $tasks);

        $DB->delete_records('assignfeedback_aitutoria_aud', ['jobid' => $queued['job']->id]);
        $DB->delete_records('assignfeedback_aitutoria_snp', ['jobid' => $queued['job']->id]);
        $DB->delete_records('assignfeedback_aitutoria_job', ['id' => $queued['job']->id]);

        $tasks[0]->execute();
        $this->assertTrue(true);
    }

    /**
     * Create assignment and grade fixtures.
     *
     * @return array{0: int, 1: int}
     */
    private function create_assignment_grade(): array {
        global $DB, $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $student = $this->getDataGenerator()->create_user();
        $gradeid = $DB->insert_record('assign_grades', (object) [
            'assignment' => $assign->id,
            'userid' => $student->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'grader' => $USER->id,
            'grade' => 50,
            'attemptnumber' => 0,
        ]);

        return [(int) $assign->id, (int) $gradeid];
    }

    /**
     * Build an assessment request fixture.
     *
     * @param int $assignmentid Assignment id.
     * @param int $gradeid Grade id.
     * @return assessment_request
     */
    private function request(int $assignmentid, int $gradeid): assessment_request {
        return new assessment_request(
            $assignmentid,
            $gradeid,
            'Submission for the adhoc task test.',
            [
                'version' => 'v1',
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
            ['mode' => assessment_service::MODE_SHADOW]
        );
    }
}
