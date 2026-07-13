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
use assignfeedback_aitutoria\local\repository\human_criterion_repository;

/**
 * Human criterion calibration persistence tests.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignfeedback_aitutoria\local\repository\human_criterion_repository
 */
final class human_criterion_repository_test extends \advanced_testcase {
    /**
     * Human selections store matches and mismatches without changing the grade.
     */
    public function test_reviews_calibrate_without_writing_grade(): void {
        global $DB, $USER;

        [$assignmentid, $gradeid] = $this->create_assignment_grade(78.0);
        $request = new assessment_request(
            $assignmentid,
            $gradeid,
            'Submission for calibration.',
            $this->rubric(),
            ['mode' => assessment_service::MODE_SHADOW]
        );
        $queued = assessment_service::queue($request, new fixture_provider(), null, false);
        assessment_service::execute((int) $queued['job']->id, new fixture_provider());

        $summary = human_criterion_repository::save_reviews(
            $assignmentid,
            $gradeid,
            (int) $queued['job']->id,
            (int) $USER->id,
            $this->rubric(),
            ['clarity' => 'proficient', 'evidence' => 'developing']
        );

        $grade = $DB->get_record('assign_grades', ['id' => $gradeid], '*', MUST_EXIST);
        $records = human_criterion_repository::get_for_grade($gradeid);
        $this->assertSame(2, $summary['saved']);
        $this->assertSame(1, $summary['matched']);
        $this->assertSame(1, $summary['mismatched']);
        $this->assertSame(1, (int) $records['clarity']->matchesai);
        $this->assertSame(0, (int) $records['evidence']->matchesai);
        $this->assertSame(78.0, (float) $grade->grade);
    }

    /**
     * Invalid levels are rejected before persistence.
     */
    public function test_unknown_level_is_rejected(): void {
        global $USER;

        [$assignmentid, $gradeid] = $this->create_assignment_grade();
        $this->expectException(\invalid_parameter_exception::class);
        human_criterion_repository::save_reviews(
            $assignmentid,
            $gradeid,
            null,
            (int) $USER->id,
            $this->rubric(),
            ['clarity' => 'invented']
        );
    }

    /**
     * Clearing a selection removes its calibration record.
     */
    public function test_empty_selection_removes_existing_review(): void {
        global $DB, $USER;

        [$assignmentid, $gradeid] = $this->create_assignment_grade();
        human_criterion_repository::save_reviews(
            $assignmentid,
            $gradeid,
            null,
            (int) $USER->id,
            $this->rubric(),
            ['clarity' => 'proficient']
        );
        $summary = human_criterion_repository::save_reviews(
            $assignmentid,
            $gradeid,
            null,
            (int) $USER->id,
            $this->rubric(),
            ['clarity' => '']
        );

        $this->assertSame(1, $summary['removed']);
        $this->assertSame(0, $DB->count_records('assignfeedback_aitutoria_hcr', ['grade' => $gradeid]));
    }

    /**
     * Create assignment and grade fixtures.
     *
     * @param float $numericgrade Initial grade.
     * @return array{0: int, 1: int}
     */
    private function create_assignment_grade(float $numericgrade = 50.0): array {
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
            'grade' => $numericgrade,
            'attemptnumber' => 0,
        ]);

        return [(int) $assign->id, (int) $gradeid];
    }

    /**
     * Build a two-criterion rubric.
     *
     * @return array
     */
    private function rubric(): array {
        return [
            'version' => 'calibration-v1',
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
        ];
    }
}
