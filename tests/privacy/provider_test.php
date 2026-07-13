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

use assignfeedback_aitutoria\local\assessment_service;
use assignfeedback_aitutoria\local\dto\assessment_request;
use assignfeedback_aitutoria\local\provider\fixture_provider;
use assignfeedback_aitutoria\local\repository\human_criterion_repository;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;
use mod_assign\privacy\assign_plugin_request_data;
use mod_assign\tests\provider_testcase;

/**
 * Privacy API tests for feedback and assessment engine artifacts.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignfeedback_aitutoria\privacy\provider
 */
final class provider_test extends provider_testcase {
    /**
     * Metadata describes all plugin data stores.
     */
    public function test_get_metadata_describes_engine_tables(): void {
        $collection = provider::get_metadata(new collection('assignfeedback_aitutoria'));
        $this->assertCount(6, $collection->get_collection());
    }

    /**
     * A student export contains snapshots, private results and human calibration.
     */
    public function test_export_feedback_user_data_includes_engine_artifacts(): void {
        global $DB, $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $assign = $this->create_instance(['course' => $course]);
        $grade = $this->create_grade((int) $assign->get_instance()->id, (int) $student->id, 72.0);
        $request = $this->request((int) $assign->get_instance()->id, (int) $grade->id);
        $queued = assessment_service::queue($request, new fixture_provider(), (int) $USER->id, false);
        assessment_service::execute((int) $queued['job']->id, new fixture_provider());
        human_criterion_repository::save_reviews(
            (int) $assign->get_instance()->id,
            (int) $grade->id,
            (int) $queued['job']->id,
            (int) $USER->id,
            $request->get_rubric(),
            ['clarity' => 'proficient']
        );

        $context = $assign->get_context();
        $exportdata = new assign_plugin_request_data($context, $assign, $grade, [], $student);
        provider::export_feedback_user_data($exportdata);

        $basepath = [get_string('privacy:path', 'assignfeedback_aitutoria')];
        $base = writer::with_context($context)->get_data($basepath);
        $jobpath = array_merge(
            $basepath,
            [get_string('privacy:assessmentjob', 'assignfeedback_aitutoria', $queued['job']->id)]
        );
        $data = writer::with_context($context)->get_data($jobpath);

        $this->assertCount(1, $base->humancriteria);
        $this->assertSame('clarity', $base->humancriteria[0]['criterionkey']);
        $this->assertSame('proficient', $base->humancriteria[0]['selectedlevel']);
        $this->assertSame('complete', $data->status);
        $this->assertSame('fixture', $data->provider);
        $this->assertSame($request->get_submissiontext(), $data->snapshot->submissiontext);
        $this->assertCount(2, $data->criteria);
        $this->assertNotEmpty($data->audit);
        $this->assertSame(1, $DB->count_records('assignfeedback_aitutoria_job'));
    }

    /**
     * Deleting one grade removes only that student's engine artifacts.
     */
    public function test_delete_feedback_for_grade_is_selective(): void {
        global $DB, $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $studentone = $this->getDataGenerator()->create_user();
        $studenttwo = $this->getDataGenerator()->create_user();
        $assign = $this->create_instance(['course' => $course]);
        $assignmentid = (int) $assign->get_instance()->id;
        $gradeone = $this->create_grade($assignmentid, (int) $studentone->id, 55.0);
        $gradetwo = $this->create_grade($assignmentid, (int) $studenttwo->id, 85.0);

        foreach ([$gradeone, $gradetwo] as $grade) {
            $request = $this->request($assignmentid, (int) $grade->id);
            $queued = assessment_service::queue($request, new fixture_provider(), null, false);
            assessment_service::execute((int) $queued['job']->id, new fixture_provider());
            human_criterion_repository::save_reviews(
                $assignmentid,
                (int) $grade->id,
                (int) $queued['job']->id,
                (int) $USER->id,
                $request->get_rubric(),
                ['clarity' => 'proficient']
            );
        }

        provider::delete_feedback_for_grade(new assign_plugin_request_data(
            $assign->get_context(),
            $assign,
            $gradeone,
            [],
            $studentone
        ));

        $this->assertSame(0, $DB->count_records('assignfeedback_aitutoria_job', ['grade' => $gradeone->id]));
        $this->assertSame(1, $DB->count_records('assignfeedback_aitutoria_job', ['grade' => $gradetwo->id]));
        $this->assertSame(0, $DB->count_records('assignfeedback_aitutoria_hcr', ['grade' => $gradeone->id]));
        $this->assertSame(1, $DB->count_records('assignfeedback_aitutoria_hcr', ['grade' => $gradetwo->id]));
    }

    /**
     * Context deletion removes all plugin data for the assignment.
     */
    public function test_delete_feedback_for_context_removes_all_engine_data(): void {
        global $DB, $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $assign = $this->create_instance(['course' => $course]);
        $assignmentid = (int) $assign->get_instance()->id;
        $grade = $this->create_grade($assignmentid, (int) $student->id, 60.0);
        $request = $this->request($assignmentid, (int) $grade->id);
        $queued = assessment_service::queue($request, new fixture_provider(), null, false);
        assessment_service::execute((int) $queued['job']->id, new fixture_provider());
        human_criterion_repository::save_reviews(
            $assignmentid,
            (int) $grade->id,
            (int) $queued['job']->id,
            (int) $USER->id,
            $request->get_rubric(),
            ['clarity' => 'proficient']
        );

        provider::delete_feedback_for_context(new assign_plugin_request_data($assign->get_context(), $assign));

        foreach (
            [
                'assignfeedback_aitutoria',
                'assignfeedback_aitutoria_job',
                'assignfeedback_aitutoria_snp',
                'assignfeedback_aitutoria_crt',
                'assignfeedback_aitutoria_hcr',
                'assignfeedback_aitutoria_aud',
            ] as $table
        ) {
            $this->assertSame(0, $DB->count_records($table));
        }
    }

    /**
     * Create an assignment grade record.
     *
     * @param int $assignmentid Assignment id.
     * @param int $userid Student user id.
     * @param float $numericgrade Initial grade.
     * @return \stdClass
     */
    private function create_grade(int $assignmentid, int $userid, float $numericgrade): \stdClass {
        global $DB, $USER;

        $record = (object) [
            'assignment' => $assignmentid,
            'userid' => $userid,
            'timecreated' => time(),
            'timemodified' => time(),
            'grader' => $USER->id,
            'grade' => $numericgrade,
            'attemptnumber' => 0,
        ];
        $record->id = $DB->insert_record('assign_grades', $record);
        return $record;
    }

    /**
     * Build a structured assessment request.
     *
     * @param int $assignmentid Assignment id.
     * @param int $gradeid Grade id.
     * @return assessment_request
     */
    private function request(int $assignmentid, int $gradeid): assessment_request {
        return new assessment_request(
            $assignmentid,
            $gradeid,
            'Student submission stored for a privacy export test.',
            [
                'version' => 'privacy-v1',
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
            ['mode' => assessment_service::MODE_SHADOW]
        );
    }
}
