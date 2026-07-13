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

use assignfeedback_aitutoria\local\dto\assessment_request;

/**
 * Generates deterministic request fingerprints without storing raw text.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class idempotency {
    /**
     * Create a stable request key.
     *
     * @param assessment_request $request Assessment request.
     * @param string $provider Provider identifier.
     * @return string SHA-256 fingerprint.
     */
    public static function request_key(assessment_request $request, string $provider): string {
        $provider = trim($provider);
        if ($provider === '') {
            throw new \invalid_parameter_exception('Provider identifier is required for idempotency.');
        }

        $payload = [
            'assignmentid' => $request->get_assignmentid(),
            'gradeid' => $request->get_gradeid(),
            'submissionhash' => hash('sha256', $request->get_submissiontext()),
            'rubric' => $request->get_rubric(),
            'policy' => $request->get_policy(),
            'provider' => $provider,
        ];

        return hash('sha256', self::canonical_json($payload));
    }

    /**
     * Encode data using stable associative-key ordering.
     *
     * @param mixed $value Value to encode.
     * @return string Canonical JSON.
     */
    public static function canonical_json($value): string {
        try {
            return json_encode(
                self::normalize($value),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
        } catch (\JsonException) {
            throw new \invalid_parameter_exception('Could not create an idempotency fingerprint.');
        }
    }

    /**
     * Recursively sort associative arrays while preserving lists.
     *
     * @param mixed $value Value to normalize.
     * @return mixed
     */
    private static function normalize($value) {
        if (!is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map([self::class, 'normalize'], $value);
        }

        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            $value[$key] = self::normalize($item);
        }

        return $value;
    }
}
