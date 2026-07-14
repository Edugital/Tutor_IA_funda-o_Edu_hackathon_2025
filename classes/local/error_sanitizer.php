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

namespace assignfeedback_aitutoria\local;

/**
 * Redact credentials and common personal identifiers from operational errors.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class error_sanitizer {
    /**
     * Sanitize an error message for storage and administrative display.
     *
     * @param string $message Raw error message.
     * @return string Sanitized message.
     */
    public static function sanitize(string $message): string {
        $message = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $message) ?? '';
        $message = preg_replace('/\s+/u', ' ', trim($message)) ?? '';

        $patterns = [
            '/\bsk-[A-Za-z0-9_-]{12,}\b/u',
            '/\b(?:api[_-]?key|token|secret|authorization)\s*[:=]\s*[^\s,;]+/iu',
            '/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/iu',
            '/\b\d{3}\.\d{3}\.\d{3}-\d{2}\b/u',
            '/\b\+?\d{1,3}[\s.-]?\(?\d{2,3}\)?[\s.-]?\d{4,5}[\s.-]?\d{4}\b/u',
        ];
        foreach ($patterns as $pattern) {
            $message = preg_replace($pattern, '[redacted]', $message) ?? $message;
        }

        if ($message === '') {
            return 'Assessment processing failed without a diagnostic message.';
        }

        return \core_text::substr($message, 0, 500);
    }
}
