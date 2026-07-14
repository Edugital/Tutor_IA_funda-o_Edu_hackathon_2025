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

use assignfeedback_aitutoria\local\dto\assessment_request;
use assignfeedback_aitutoria\local\idempotency;

/**
 * Request idempotency tests.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignfeedback_aitutoria\local\idempotency
 */
final class idempotency_test extends \advanced_testcase {
    /**
     * Associative key order does not alter the request fingerprint.
     */
    public function test_associative_order_is_canonical(): void {
        $first = $this->request(
            ['version' => 'v1', 'criteria' => [], 'metadata' => ['b' => 2, 'a' => 1]],
            ['review' => true, 'mode' => 'shadow']
        );
        $second = $this->request(
            ['metadata' => ['a' => 1, 'b' => 2], 'criteria' => [], 'version' => 'v1'],
            ['mode' => 'shadow', 'review' => true]
        );

        $this->assertSame(
            idempotency::request_key($first, 'fixture'),
            idempotency::request_key($second, 'fixture')
        );
    }

    /**
     * A changed submission produces a different job key.
     */
    public function test_submission_change_changes_key(): void {
        $first = $this->request([], [], 'First response.');
        $second = $this->request([], [], 'Second response.');

        $this->assertNotSame(
            idempotency::request_key($first, 'fixture'),
            idempotency::request_key($second, 'fixture')
        );
    }

    /**
     * List order remains meaningful in canonical JSON.
     */
    public function test_list_order_is_preserved(): void {
        $first = idempotency::canonical_json(['criteria' => ['a', 'b']]);
        $second = idempotency::canonical_json(['criteria' => ['b', 'a']]);

        $this->assertNotSame($first, $second);
    }

    /**
     * Build an assessment request fixture.
     *
     * @param array $rubric Rubric payload.
     * @param array $policy Policy payload.
     * @param string $submission Submission text.
     * @return assessment_request
     */
    private function request(
        array $rubric,
        array $policy,
        string $submission = 'A student response.'
    ): assessment_request {
        return new assessment_request(10, 20, $submission, $rubric, $policy);
    }
}
