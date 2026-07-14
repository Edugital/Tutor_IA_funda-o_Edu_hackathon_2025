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

use assignfeedback_aitutoria\local\repository\audit_repository;

/**
 * Minimal audit trail tests.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignfeedback_aitutoria\local\repository\audit_repository
 */
final class audit_repository_test extends \advanced_testcase {
    /**
     * Submission, suggestion, evidence and credentials are recursively redacted.
     */
    public function test_sensitive_payload_is_redacted(): void {
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

        audit_repository::record(
            (int) $assign->id,
            (int) $gradeid,
            'redaction_test',
            null,
            (int) $USER->id,
            [
                'provider' => 'fixture',
                'submissiontext' => 'Student personal data.',
                'nested' => [
                    'token' => 'secret-token',
                    'evidence' => ['Sensitive excerpt.'],
                ],
            ]
        );

        $record = $DB->get_record('assignfeedback_aitutoria_aud', [], '*', MUST_EXIST);
        $payload = json_decode($record->payloadjson, true, 64, JSON_THROW_ON_ERROR);

        $this->assertSame('fixture', $payload['provider']);
        $this->assertSame('[redacted]', $payload['submissiontext']);
        $this->assertSame('[redacted]', $payload['nested']['token']);
        $this->assertSame('[redacted]', $payload['nested']['evidence']);
        $this->assertStringNotContainsString('Student personal data', $record->payloadjson);
        $this->assertStringNotContainsString('secret-token', $record->payloadjson);
        $this->assertStringNotContainsString('Sensitive excerpt', $record->payloadjson);
    }
}
