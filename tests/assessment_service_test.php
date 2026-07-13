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
use assignfeedback_aitutoria\local\dto\assessment_result;
use assignfeedback_aitutoria\local\provider\fixture_provider;
use assignfeedback_aitutoria\local\provider\provider_interface;
use assignfeedback_aitutoria\local\repository\job_repository;

/**
 * Assessment orchestration and persistence tests.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignfeedback_aitutoria\local\assessment_service
 * @covers     \assignfeedback_aitutoria\local\repository\job_repository
 * @covers     \assignfeedback_aitutoria\local\repository\snapshot_repository
 * @covers     \assignfeedback_aitutoria\local\repository\criterion_repository
 * @covers     \assignfeedback_aitutoria\local\repository\audit_repository
 */
final class assessment_service_test extends \advanced_testcase {
    /**
     * Equivalent requests create one job and one immutable snapshot.
     */
    public function test_queue_is_idempotent(): void {
        global $DB;

        [$assignmentid, $gradeid] = $this->create_assignment_grade();
        $request = $this->request($assignmentid, $gradeid, assessment_service::MODE_SHADOW);
        $provider = new fixture_provider();

        $first = assessment_service::queue($request, $provider, null, false);
        $second = assessment_service::queue($request, $provider, null, false);

        $this->assertTrue($first['created']);
        $this->assertFalse($second['created']);
        $this->assertSame($first['job']->id, $second['job']->id);
        $this->assertSame(1, $DB->count_records('assignfeedback_aitutoria_job'));
        $this->assertSame(1, $DB->count_records('assignfeedback_aitutoria_snp'));
        $this->assertSame(1, $DB->count_records('assignfeedback_aitutoria_aud'));
    }

    /**
     * Shadow mode stores private results without changing grade or review feedback.
     */
    public function test_shadow_mode_never_writes_grade_or_teacher_suggestion(): void {
        global $DB;

        [$assignmentid, $gradeid] = $this->create_assignment_grade(73.0);
        $request = $this->request($assignmentid, $gradeid, assessment_service::MODE_SHADOW);
        $queued = assessment_service::queue($request, new fixture_provider(), null, false);

        $completed = assessment_service::execute((int) $queued['job']->id, new fixture_provider());
        $grade = $DB->get_record('assign_grades', ['id' => $gradeid], '*', MUST_EXIST);

        $this->assertSame(job_repository::STATUS_COMPLETE, $completed->status);
        $this->assertSame(73.0, (float) $grade->grade);
        $this->assertNotEmpty($completed->suggestiontext);
        $this->assertSame(2, $DB->count_records('assignfeedback_aitutoria_crt'));
        $this->assertSame(0, $DB->count_records('assignfeedback_aitutoria'));
        $this->assertSame(2, $DB->count_records('assignfeedback_aitutoria_aud'));
    }

    /**
     * Assistive mode copies a private suggestion to the teacher review surface only.
     */
    public function test_assistive_mode_requires_human_publication(): void {
        global $DB;

        set_config('allowaisuggestions', 1, 'assignfeedback_aitutoria');
        [$assignmentid, $gradeid] = $this->create_assignment_grade(81.0);
        $request = $this->request($assignmentid, $gradeid, assessment_service::MODE_ASSISTIVE);
        $queued = assessment_service::queue($request, new fixture_provider(), null, false);

        assessment_service::execute((int) $queued['job']->id, new fixture_provider());

        $grade = $DB->get_record('assign_grades', ['id' => $gradeid], '*', MUST_EXIST);
        $feedback = $DB->get_record('assignfeedback_aitutoria', ['grade' => $gradeid], '*', MUST_EXIST);

        $this->assertSame(81.0, (float) $grade->grade);
        $this->assertSame('ready', $feedback->aistatus);
        $this->assertSame('manual', $feedback->decision);
        $this->assertEmpty($feedback->feedbacktext);
        $this->assertNotEmpty($feedback->aisuggestion);
    }

    /**
     * Failed providers follow the bounded retry policy and end in failed state.
     */
    public function test_failed_provider_exhausts_retry_policy(): void {
        global $DB;

        [$assignmentid, $gradeid] = $this->create_assignment_grade();
        $provider = $this->failing_provider();
        $queued = assessment_service::queue(
            $this->request($assignmentid, $gradeid, assessment_service::MODE_SHADOW),
            $provider,
            null,
            false
        );
        $jobid = (int) $queued['job']->id;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $DB->set_field('assignfeedback_aitutoria_job', 'timeavailable', 0, ['id' => $jobid]);
            try {
                assessment_service::execute($jobid, $provider);
                $this->fail('The failing provider must throw.');
            } catch (\runtime_exception $exception) {
                $this->assertSame('Synthetic provider failure.', $exception->getMessage());
            }
        }

        $job = job_repository::get($jobid);
        $this->assertSame(job_repository::STATUS_FAILED, $job->status);
        $this->assertSame(3, (int) $job->attempts);
        $this->assertSame(4, $DB->count_records('assignfeedback_aitutoria_aud'));
    }

    /**
     * Create an assignment and one grade record.
     *
     * @param float $numericgrade Initial numeric grade.
     * @return array{0: int, 1: int}
     */
    private function create_assignment_grade(float $numericgrade = 50.0): array {
        global $DB, $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'name' => 'AI tutoring test assignment',
        ]);
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
     * Build a normalized assessment request.
     *
     * @param int $assignmentid Assignment id.
     * @param int $gradeid Grade id.
     * @param string $mode Processing mode.
     * @return assessment_request
     */
    private function request(int $assignmentid, int $gradeid, string $mode): assessment_request {
        return new assessment_request(
            $assignmentid,
            $gradeid,
            'The response presents a clear claim and supports it with evidence.',
            [
                'version' => '2026.1',
                'criteria' => [
                    [
                        'id' => 'clarity',
                        'title' => 'Clarity',
                        'weight' => 60,
                        'levels' => [
                            ['id' => 'developing', 'label' => 'Developing', 'score' => 1],
                            ['id' => 'proficient', 'label' => 'Proficient', 'score' => 2],
                        ],
                    ],
                    [
                        'id' => 'evidence',
                        'title' => 'Evidence',
                        'weight' => 40,
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

    /**
     * Create a provider that always fails.
     *
     * @return provider_interface
     */
    private function failing_provider(): provider_interface {
        return new class implements provider_interface {
            /**
             * Return the failing fixture provider name.
             *
             * @return string Provider name.
             */
            public function get_name(): string {
                return 'failing-fixture';
            }

            /**
             * Return the failing fixture model name.
             *
             * @return string Model name.
             */
            public function get_model(): string {
                return 'failure-v1';
            }

            /**
             * Return the failing fixture prompt version.
             *
             * @return string Prompt version.
             */
            public function get_promptversion(): string {
                return 'failure-prompt-v1';
            }

            /**
             * Always fail.
             *
             * @param assessment_request $request Assessment request.
             * @return assessment_result
             */
            public function assess(assessment_request $request): assessment_result {
                throw new \runtime_exception('Synthetic provider failure.');
            }
        };
    }
}
