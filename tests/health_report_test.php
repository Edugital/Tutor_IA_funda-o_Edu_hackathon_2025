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

use assignfeedback_aitutoria\local\reporting\health_report;

/**
 * Administrative health report tests.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignfeedback_aitutoria\local\reporting\health_report
 */
final class health_report_test extends \advanced_testcase {
    /**
     * Health report flags stale work and redacts stored diagnostics.
     */
    public function test_health_report_detects_stale_and_failed_jobs(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        set_config('processingtimeoutminutes', 15, 'assignfeedback_aitutoria');
        set_config('institutionalframeworkjson', $this->framework(), 'assignfeedback_aitutoria');
        [$assignmentid, $gradeid] = $this->create_assignment_grade();
        $now = time();

        $DB->insert_record('assignfeedback_aitutoria_job', (object) [
            'assignment' => $assignmentid,
            'grade' => $gradeid,
            'status' => 'failed',
            'idempotencykey' => hash('sha256', 'failed-job'),
            'provider' => 'external-fixture',
            'model' => 'fixture-v1',
            'promptversion' => 'prompt-v1',
            'attempts' => 3,
            'maxattempts' => 3,
            'lasterror' => 'token=secretvalue123456 learner@example.org',
            'timeavailable' => 0,
            'timecreated' => $now - HOURSECS,
            'timemodified' => $now - HOURSECS,
        ]);
        $DB->insert_record('assignfeedback_aitutoria_job', (object) [
            'assignment' => $assignmentid,
            'grade' => $gradeid,
            'status' => 'processing',
            'idempotencykey' => hash('sha256', 'stale-job'),
            'provider' => 'external-fixture',
            'model' => 'fixture-v1',
            'promptversion' => 'prompt-v1',
            'attempts' => 1,
            'maxattempts' => 3,
            'timeavailable' => 0,
            'timecreated' => $now - HOURSECS,
            'timemodified' => $now - HOURSECS,
        ]);

        $report = health_report::build();

        $this->assertSame('warning', $report['status']);
        $this->assertSame(1, $report['jobs']['status']['failed']);
        $this->assertSame(1, $report['jobs']['status']['processing']);
        $this->assertSame(1, $report['jobs']['staleprocessing']);
        $this->assertTrue($report['framework']['valid']);
        $this->assertSame(1, $report['framework']['competencycount']);
        $this->assertStringNotContainsString('secretvalue123456', $report['jobs']['recentfailures'][0]['error']);
        $this->assertStringNotContainsString('learner@example.org', $report['jobs']['recentfailures'][0]['error']);
    }

    /**
     * Invalid stored framework configuration is reported as critical.
     */
    public function test_invalid_framework_is_critical(): void {
        $this->resetAfterTest(true);
        set_config('institutionalframeworkjson', '{invalid', 'assignfeedback_aitutoria');

        $report = health_report::build();

        $this->assertSame('critical', $report['status']);
        $this->assertFalse($report['framework']['valid']);
        $this->assertSame('invalid_institutional_framework', $report['issues'][0]['code']);
    }

    /**
     * Create assignment and grade records.
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
            'grade' => 50,
            'attemptnumber' => 0,
        ]);

        return [(int) $assign->id, (int) $gradeid];
    }

    /**
     * Build a valid institutional framework.
     *
     * @return string
     */
    private function framework(): string {
        return json_encode([
            'version' => 'health-v1',
            'competencies' => [[
                'id' => 'communication',
                'title' => 'Communication',
                'indicators' => [[
                    'id' => 'clarity',
                    'title' => 'Communicates clearly',
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);
    }
}
