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
use assignfeedback_aitutoria\local\decision_policy;
use assignfeedback_aitutoria\local\dto\assessment_request;
use assignfeedback_aitutoria\local\feedback_repository;
use assignfeedback_aitutoria\local\provider\fixture_provider;

/**
 * Human review persistence and cleanup tests.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignfeedback_aitutoria\local\feedback_repository
 */
final class human_review_test extends \advanced_testcase {
    /**
     * Rejecting a suggestion never publishes the AI text or changes the grade.
     */
    public function test_reject_persists_human_text_only(): void {
        global $DB;

        [$assignmentid, $gradeid] = $this->create_assignment_grade(67.0);
        set_config('allowaisuggestions', 1, 'assignfeedback_aitutoria');
        feedback_repository::store_ai_suggestion(
            $assignmentid,
            $gradeid,
            'Private AI suggestion.',
            'fixture-model',
            'prompt-v1',
            'rubric-v1'
        );

        $record = feedback_repository::save_human_feedback(
            $assignmentid,
            $gradeid,
            'Teacher-authored feedback.',
            false,
            decision_policy::ACTION_REJECT
        );
        $grade = $DB->get_record('assign_grades', ['id' => $gradeid], '*', MUST_EXIST);

        $this->assertSame('Teacher-authored feedback.', $record->feedbacktext);
        $this->assertSame('Private AI suggestion.', $record->aisuggestion);
        $this->assertSame('rejected_ai', $record->decision);
        $this->assertSame('rejected', $record->aistatus);
        $this->assertSame(67.0, (float) $grade->grade);
    }

    /**
     * Escalation records state while publishing neither suggestion nor empty filler text.
     */
    public function test_escalation_keeps_suggestion_private(): void {
        global $DB;

        [$assignmentid, $gradeid] = $this->create_assignment_grade(42.0);
        set_config('allowaisuggestions', 1, 'assignfeedback_aitutoria');
        feedback_repository::store_ai_suggestion($assignmentid, $gradeid, 'Private AI suggestion.');

        $record = feedback_repository::save_human_feedback(
            $assignmentid,
            $gradeid,
            '',
            false,
            decision_policy::ACTION_ESCALATE
        );
        $grade = $DB->get_record('assign_grades', ['id' => $gradeid], '*', MUST_EXIST);

        $this->assertSame('', $record->feedbacktext);
        $this->assertSame('escalated', $record->decision);
        $this->assertSame('escalated', $record->aistatus);
        $this->assertSame(42.0, (float) $grade->grade);
    }

    /**
     * Deleting an assignment removes all engine artifacts in foreign-key order.
     */
    public function test_assignment_cleanup_removes_all_plugin_records(): void {
        global $DB;

        [$assignmentid, $gradeid] = $this->create_assignment_grade();
        $request = new assessment_request(
            $assignmentid,
            $gradeid,
            'A submission used to verify cascading cleanup.',
            $this->rubric(),
            ['mode' => assessment_service::MODE_SHADOW]
        );
        $queued = assessment_service::queue($request, new fixture_provider(), null, false);
        assessment_service::execute((int) $queued['job']->id, new fixture_provider());

        feedback_repository::delete_for_assignment($assignmentid);

        foreach ([
            'assignfeedback_aitutoria',
            'assignfeedback_aitutoria_job',
            'assignfeedback_aitutoria_snp',
            'assignfeedback_aitutoria_crt',
            'assignfeedback_aitutoria_aud',
        ] as $table) {
            $this->assertSame(0, $DB->count_records($table));
        }
    }

    /**
     * Create an assignment and grade fixture.
     *
     * @param float $numericgrade Initial numeric grade.
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
     * Build a two-criterion rubric fixture.
     *
     * @return array
     */
    private function rubric(): array {
        return [
            'version' => 'v1',
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
