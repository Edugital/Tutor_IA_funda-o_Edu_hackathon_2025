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
use assignfeedback_aitutoria\local\reporting\governance_report;

/**
 * Aggregate governance report tests.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignfeedback_aitutoria\local\reporting\governance_report
 */
final class governance_report_test extends \advanced_testcase {
    /**
     * Report aggregates jobs, criteria and explicit human decisions without content.
     */
    public function test_report_aggregates_governance_metrics(): void {
        global $DB, $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        set_config('allowaisuggestions', 1, 'assignfeedback_aitutoria');
        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $students = [
            $this->getDataGenerator()->create_user(),
            $this->getDataGenerator()->create_user(),
        ];
        $decisions = [decision_policy::ACTION_ACCEPT, decision_policy::ACTION_REJECT];

        foreach ($students as $index => $student) {
            $gradeid = $DB->insert_record('assign_grades', (object) [
                'assignment' => $assign->id,
                'userid' => $student->id,
                'timecreated' => time(),
                'timemodified' => time(),
                'grader' => $USER->id,
                'grade' => 70 + $index,
                'attemptnumber' => 0,
            ]);
            $request = new assessment_request(
                (int) $assign->id,
                (int) $gradeid,
                'Submission used for aggregate reporting.',
                $this->rubric(),
                ['mode' => assessment_service::MODE_ASSISTIVE]
            );
            $queued = assessment_service::queue($request, new fixture_provider(), null, false);
            assessment_service::execute((int) $queued['job']->id, new fixture_provider());
            feedback_repository::save_human_feedback(
                (int) $assign->id,
                (int) $gradeid,
                $index === 0 ? '' : 'Teacher feedback after rejecting the suggestion.',
                false,
                $decisions[$index]
            );
        }

        $report = governance_report::build((int) $assign->id);

        $this->assertSame(2, $report['jobs']['total']);
        $this->assertSame(2, $report['jobs']['status']['complete']);
        $this->assertSame(2, $report['jobs']['providers']['fixture']);
        $this->assertSame(100.0, $report['jobs']['averageadvisorypercentage']);
        $this->assertSame(4, $report['criteria']['total']);
        $this->assertSame(4, $report['criteria']['uncertainty']['high']);
        $this->assertSame(4, $report['criteria']['requiringhumanreview']);
        $this->assertSame(1, $report['humanreview']['decisions']['accepted_ai']);
        $this->assertSame(1, $report['humanreview']['decisions']['rejected_ai']);
        $this->assertSame(50.0, $report['humanreview']['acceptancerate']);
        $this->assertArrayNotHasKey('submissiontext', $report);
    }

    /**
     * Empty periods return stable zero-filled metrics.
     */
    public function test_empty_report_has_stable_shape(): void {
        $this->resetAfterTest(true);

        $report = governance_report::build(null, time() + DAYSECS, time() + (2 * DAYSECS));

        $this->assertSame(0, $report['jobs']['total']);
        $this->assertSame(0, $report['criteria']['total']);
        $this->assertSame(0, $report['humanreview']['total']);
        $this->assertNull($report['jobs']['averageadvisorypercentage']);
        $this->assertNull($report['humanreview']['acceptancerate']);
    }

    /**
     * Build the reporting rubric fixture.
     *
     * @return array
     */
    private function rubric(): array {
        return [
            'version' => 'report-v1',
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
