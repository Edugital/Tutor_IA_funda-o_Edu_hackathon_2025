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
 * Parser and validator for versioned structured rubrics.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class rubric_parser {
    /**
     * Parse a rubric JSON document.
     *
     * @param string $json Rubric JSON.
     * @return array Normalized rubric.
     */
    public static function parse(string $json): array {
        $json = trim($json);
        if ($json === '') {
            throw new \invalid_parameter_exception('Structured rubric JSON cannot be empty.');
        }

        try {
            $rubric = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \invalid_parameter_exception('Structured rubric JSON is invalid: ' . $exception->getMessage());
        }

        if (!is_array($rubric)) {
            throw new \invalid_parameter_exception('Structured rubric must be a JSON object.');
        }

        return self::validate($rubric);
    }

    /**
     * Validate and normalize a rubric structure.
     *
     * @param array $rubric Rubric structure.
     * @return array Normalized rubric.
     */
    public static function validate(array $rubric): array {
        $version = trim((string) ($rubric['version'] ?? ''));
        $criteria = $rubric['criteria'] ?? null;

        if ($version === '' || !is_array($criteria) || empty($criteria)) {
            throw new \invalid_parameter_exception('Structured rubrics require a version and criteria.');
        }

        $criterionids = [];
        $normalizedcriteria = [];

        foreach ($criteria as $criterion) {
            if (!is_array($criterion)) {
                throw new \invalid_parameter_exception('Every rubric criterion must be an object.');
            }

            $id = trim((string) ($criterion['id'] ?? ''));
            $title = trim((string) ($criterion['title'] ?? ''));
            $weight = $criterion['weight'] ?? null;
            $levels = $criterion['levels'] ?? null;

            if ($id === '' || $title === '' || !is_numeric($weight) || (float) $weight <= 0) {
                throw new \invalid_parameter_exception('Every criterion requires id, title and positive weight.');
            }
            if (isset($criterionids[$id])) {
                throw new \invalid_parameter_exception('Criterion identifiers must be unique.');
            }
            if (!is_array($levels) || count($levels) < 2) {
                throw new \invalid_parameter_exception('Every criterion requires at least two levels.');
            }

            $criterionids[$id] = true;
            $levelids = [];
            $normalizedlevels = [];

            foreach ($levels as $level) {
                if (!is_array($level)) {
                    throw new \invalid_parameter_exception('Every rubric level must be an object.');
                }

                $levelid = trim((string) ($level['id'] ?? ''));
                $label = trim((string) ($level['label'] ?? ''));
                $score = $level['score'] ?? null;

                if ($levelid === '' || $label === '' || !is_numeric($score) || (float) $score < 0) {
                    throw new \invalid_parameter_exception('Every level requires id, label and non-negative score.');
                }
                if (isset($levelids[$levelid])) {
                    throw new \invalid_parameter_exception('Level identifiers must be unique within a criterion.');
                }

                $levelids[$levelid] = true;
                $normalizedlevels[] = [
                    'id' => $levelid,
                    'label' => $label,
                    'score' => (float) $score,
                    'description' => trim((string) ($level['description'] ?? '')),
                ];
            }

            usort(
                $normalizedlevels,
                static fn(array $first, array $second): int => $first['score'] <=> $second['score']
            );

            $normalizedcriteria[] = [
                'id' => $id,
                'title' => $title,
                'weight' => (float) $weight,
                'description' => trim((string) ($criterion['description'] ?? '')),
                'competencyid' => trim((string) ($criterion['competencyid'] ?? '')),
                'levels' => $normalizedlevels,
            ];
        }

        return [
            'version' => $version,
            'title' => trim((string) ($rubric['title'] ?? '')),
            'criteria' => $normalizedcriteria,
        ];
    }
}
