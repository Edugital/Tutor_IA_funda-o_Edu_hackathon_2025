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

namespace assignfeedback_aitutoria\local\repository;

use assignfeedback_aitutoria\local\idempotency;

/**
 * Minimal audit trail with explicit payload redaction.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class audit_repository {
    /** Database table. */
    private const TABLE = 'assignfeedback_aitutoria_aud';

    /** Fields never permitted in the audit payload. */
    private const SENSITIVE_KEYS = [
        'submission',
        'submissiontext',
        'feedbacktext',
        'suggestion',
        'suggestiontext',
        'rationale',
        'evidence',
        'evidencejson',
        'apikey',
        'token',
        'secret',
    ];

    /**
     * Record a processing or human-decision event.
     *
     * @param int $assignmentid Assignment id.
     * @param int $gradeid Grade id.
     * @param string $action Stable action identifier.
     * @param int|null $jobid Job id.
     * @param int|null $actorid Moodle user id.
     * @param array $payload Minimal non-sensitive metadata.
     * @return int Audit record id.
     */
    public static function record(
        int $assignmentid,
        int $gradeid,
        string $action,
        ?int $jobid = null,
        ?int $actorid = null,
        array $payload = []
    ): int {
        global $DB;

        $action = trim($action);
        if ($assignmentid < 1 || $gradeid < 1 || $action === '') {
            throw new \invalid_parameter_exception('Audit records require assignment, grade and action.');
        }

        $payload = self::redact($payload);

        return $DB->insert_record(self::TABLE, (object) [
            'assignment' => $assignmentid,
            'grade' => $gradeid,
            'jobid' => $jobid,
            'action' => \core_text::substr($action, 0, 50),
            'actorid' => $actorid,
            'payloadjson' => empty($payload) ? null : idempotency::canonical_json($payload),
            'timecreated' => time(),
        ]);
    }

    /**
     * Return audit records for one grade.
     *
     * @param int $assignmentid Assignment id.
     * @param int $gradeid Grade id.
     * @return \stdClass[]
     */
    public static function get_for_grade(int $assignmentid, int $gradeid): array {
        global $DB;

        return $DB->get_records(
            self::TABLE,
            ['assignment' => $assignmentid, 'grade' => $gradeid],
            'id ASC'
        );
    }

    /**
     * Recursively redact disallowed fields.
     *
     * @param mixed $value Payload value.
     * @return mixed Redacted value.
     */
    public static function redact($value) {
        if (!is_array($value)) {
            return $value;
        }

        $redacted = [];
        foreach ($value as $key => $item) {
            $normalizedkey = strtolower((string) $key);
            if (in_array($normalizedkey, self::SENSITIVE_KEYS, true)) {
                $redacted[$key] = '[redacted]';
                continue;
            }
            $redacted[$key] = self::redact($item);
        }

        return $redacted;
    }
}
