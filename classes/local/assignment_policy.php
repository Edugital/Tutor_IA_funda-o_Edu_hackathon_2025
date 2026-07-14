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
 * Assignment-level assessment mode and rubric policy.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class assignment_policy {
    /** Assessment processing is disabled. */
    public const MODE_DISABLED = 'disabled';

    /** Results are private and used only for calibration. */
    public const MODE_SHADOW = 'shadow';

    /** Results are private but available to teachers for review. */
    public const MODE_ASSISTIVE = 'assistive';

    /**
     * Normalize a configured assessment mode.
     *
     * @param string $mode Configured mode.
     * @return string Valid mode.
     */
    public static function normalize_mode(string $mode): string {
        $mode = trim($mode);
        if ($mode === '') {
            return self::MODE_DISABLED;
        }
        if (!in_array($mode, self::modes(), true)) {
            throw new \invalid_parameter_exception('Unknown assignment assessment mode.');
        }

        return $mode;
    }

    /**
     * Return supported modes.
     *
     * @return string[]
     */
    public static function modes(): array {
        return [self::MODE_DISABLED, self::MODE_SHADOW, self::MODE_ASSISTIVE];
    }

    /**
     * Parse and validate an activity rubric against the site framework.
     *
     * @param string $json Structured rubric JSON.
     * @param string|null $frameworkjson Optional institutional framework JSON.
     * @return array Normalized rubric.
     */
    public static function parse_rubric(string $json, ?string $frameworkjson = null): array {
        $rubric = rubric_parser::parse($json);
        $competencyids = [];

        $frameworkjson = trim((string) $frameworkjson);
        if ($frameworkjson !== '') {
            $framework = institutional_framework_parser::parse($frameworkjson);
            foreach ($framework['competencies'] as $competency) {
                $competencyids[$competency['id']] = true;
            }
        }

        foreach ($rubric['criteria'] as $criterion) {
            $competencyid = trim((string) ($criterion['competencyid'] ?? ''));
            if ($competencyid === '') {
                continue;
            }
            if (empty($competencyids)) {
                throw new \invalid_parameter_exception(
                    'Activity criteria reference competencies, but no institutional framework is configured.'
                );
            }
            if (!isset($competencyids[$competencyid])) {
                throw new \invalid_parameter_exception(
                    'Unknown institutional competency referenced by activity criterion: ' . $competencyid
                );
            }
        }

        return $rubric;
    }

    /**
     * Create canonical JSON for storage.
     *
     * @param string $json Structured rubric JSON.
     * @param string|null $frameworkjson Optional institutional framework JSON.
     * @return string Canonical rubric JSON.
     */
    public static function canonical_rubric_json(string $json, ?string $frameworkjson = null): string {
        return idempotency::canonical_json(self::parse_rubric($json, $frameworkjson));
    }
}
