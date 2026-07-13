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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

namespace assignfeedback_aitutoria;

use assignfeedback_aitutoria\local\decision_policy;

/**
 * Tests for the deterministic human-in-control policy.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignfeedback_aitutoria\local\decision_policy
 */
final class decision_policy_test extends \advanced_testcase {
    /**
     * Manual feedback remains manual when no suggestion exists.
     */
    public function test_manual_feedback_without_suggestion(): void {
        $result = decision_policy::resolve('Human feedback', '', false);

        $this->assertSame('Human feedback', $result['text']);
        $this->assertSame('manual', $result['decision']);
    }

    /**
     * A suggestion is published only after explicit acceptance.
     */
    public function test_explicit_acceptance_publishes_suggestion(): void {
        $result = decision_policy::resolve('Draft', 'Reviewed suggestion', true);

        $this->assertSame('Reviewed suggestion', $result['text']);
        $this->assertSame('accepted_ai', $result['decision']);
    }

    /**
     * Human text wins when a suggestion exists but is not accepted.
     */
    public function test_human_override_is_recorded(): void {
        $result = decision_policy::resolve('Edited by teacher', 'AI suggestion', false);

        $this->assertSame('Edited by teacher', $result['text']);
        $this->assertSame('overridden_ai', $result['decision']);
    }

    /**
     * Accepting an absent suggestion is rejected.
     */
    public function test_cannot_accept_missing_suggestion(): void {
        $this->expectException(\coding_exception::class);

        decision_policy::resolve('Text', '', true);
    }
}
