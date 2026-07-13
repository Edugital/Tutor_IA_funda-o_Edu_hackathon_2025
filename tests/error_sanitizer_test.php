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

use assignfeedback_aitutoria\local\error_sanitizer;

/**
 * Operational error sanitizer tests.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignfeedback_aitutoria\local\error_sanitizer
 */
final class error_sanitizer_test extends \advanced_testcase {
    /**
     * Credentials and common personal identifiers are removed.
     */
    public function test_sensitive_values_are_redacted(): void {
        $message = 'token=abc123456789 user@example.org CPF 123.456.789-10 phone +55 11 99999-9999 sk-secretvalue123456';
        $sanitized = error_sanitizer::sanitize($message);

        $this->assertStringNotContainsString('abc123456789', $sanitized);
        $this->assertStringNotContainsString('user@example.org', $sanitized);
        $this->assertStringNotContainsString('123.456.789-10', $sanitized);
        $this->assertStringNotContainsString('99999-9999', $sanitized);
        $this->assertStringNotContainsString('sk-secretvalue123456', $sanitized);
        $this->assertStringContainsString('[redacted]', $sanitized);
    }

    /**
     * Empty diagnostics receive a stable fallback message.
     */
    public function test_empty_message_has_fallback(): void {
        $this->assertSame(
            'Assessment processing failed without a diagnostic message.',
            error_sanitizer::sanitize("\x00\x01")
        );
    }
}
