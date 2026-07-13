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
 * Parser for a versioned institutional competency framework.
 *
 * The framework is site-level context for future assessment and tutoring.
 * It does not contain student data and does not change Moodle grades.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class institutional_framework_parser {
    /**
     * Parse a framework JSON document.
     *
     * @param string $json Framework JSON.
     * @return array Normalized framework.
     */
    public static function parse(string $json): array {
        $json = trim($json);
        if ($json === '') {
            throw new \invalid_parameter_exception('Institutional framework JSON cannot be empty.');
        }

        try {
            $framework = json_decode($json, true, 96, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \invalid_parameter_exception(
                'Institutional framework JSON is invalid: ' . $exception->getMessage()
            );
        }

        if (!is_array($framework)) {
            throw new \invalid_parameter_exception('Institutional framework must be a JSON object.');
        }

        return self::validate($framework);
    }

    /**
     * Validate and normalize a framework structure.
     *
     * @param array $framework Framework structure.
     * @return array Normalized framework.
     */
    public static function validate(array $framework): array {
        $version = trim((string) ($framework['version'] ?? ''));
        $competencies = $framework['competencies'] ?? null;

        if ($version === '' || !is_array($competencies) || empty($competencies)) {
            throw new \invalid_parameter_exception(
                'Institutional frameworks require a version and at least one competency.'
            );
        }

        $competencyids = [];
        $normalizedcompetencies = [];
        foreach ($competencies as $competency) {
            if (!is_array($competency)) {
                throw new \invalid_parameter_exception('Every competency must be an object.');
            }

            $id = trim((string) ($competency['id'] ?? ''));
            $title = trim((string) ($competency['title'] ?? ''));
            if ($id === '' || $title === '') {
                throw new \invalid_parameter_exception('Every competency requires id and title.');
            }
            if (isset($competencyids[$id])) {
                throw new \invalid_parameter_exception('Competency identifiers must be unique.');
            }
            $competencyids[$id] = true;

            $indicators = self::normalize_named_items(
                $competency['indicators'] ?? [],
                'indicator',
                true
            );
            $levels = self::normalize_levels($competency['levels'] ?? []);
            $methods = self::normalize_string_list($competency['methods'] ?? [], 'methods');
            $evidence = self::normalize_string_list($competency['evidence'] ?? [], 'evidence');

            $normalizedcompetencies[] = [
                'id' => $id,
                'title' => $title,
                'description' => trim((string) ($competency['description'] ?? '')),
                'indicators' => $indicators,
                'levels' => $levels,
                'methods' => $methods,
                'evidence' => $evidence,
            ];
        }

        return [
            'version' => $version,
            'title' => trim((string) ($framework['title'] ?? '')),
            'description' => trim((string) ($framework['description'] ?? '')),
            'competencies' => $normalizedcompetencies,
        ];
    }

    /**
     * Normalize named objects containing id, title and description.
     *
     * @param mixed $items Item list.
     * @param string $label Human-readable item label.
     * @param bool $required Whether at least one item is required.
     * @return array
     */
    private static function normalize_named_items($items, string $label, bool $required): array {
        if (!is_array($items) || ($required && empty($items))) {
            throw new \invalid_parameter_exception(
                'Every competency requires at least one ' . $label . '.'
            );
        }

        $ids = [];
        $normalized = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                throw new \invalid_parameter_exception('Every ' . $label . ' must be an object.');
            }

            $id = trim((string) ($item['id'] ?? ''));
            $title = trim((string) ($item['title'] ?? ''));
            if ($id === '' || $title === '') {
                throw new \invalid_parameter_exception(
                    'Every ' . $label . ' requires id and title.'
                );
            }
            if (isset($ids[$id])) {
                throw new \invalid_parameter_exception(
                    ucfirst($label) . ' identifiers must be unique within a competency.'
                );
            }
            $ids[$id] = true;
            $normalized[] = [
                'id' => $id,
                'title' => $title,
                'description' => trim((string) ($item['description'] ?? '')),
            ];
        }

        return $normalized;
    }

    /**
     * Normalize optional competency levels.
     *
     * @param mixed $levels Level list.
     * @return array
     */
    private static function normalize_levels($levels): array {
        if ($levels === [] || $levels === null) {
            return [];
        }
        if (!is_array($levels) || count($levels) < 2) {
            throw new \invalid_parameter_exception(
                'Competency levels must contain at least two objects when provided.'
            );
        }

        $ids = [];
        $normalized = [];
        foreach ($levels as $level) {
            if (!is_array($level)) {
                throw new \invalid_parameter_exception('Every competency level must be an object.');
            }

            $id = trim((string) ($level['id'] ?? ''));
            $label = trim((string) ($level['label'] ?? ''));
            $score = $level['score'] ?? null;
            if ($id === '' || $label === '' || !is_numeric($score) || (float) $score < 0) {
                throw new \invalid_parameter_exception(
                    'Every competency level requires id, label and non-negative score.'
                );
            }
            if (isset($ids[$id])) {
                throw new \invalid_parameter_exception(
                    'Competency level identifiers must be unique within a competency.'
                );
            }
            $ids[$id] = true;
            $normalized[] = [
                'id' => $id,
                'label' => $label,
                'score' => (float) $score,
                'description' => trim((string) ($level['description'] ?? '')),
            ];
        }

        usort(
            $normalized,
            static fn(array $first, array $second): int => $first['score'] <=> $second['score']
        );

        return $normalized;
    }

    /**
     * Normalize a list of non-empty strings.
     *
     * @param mixed $items String list.
     * @param string $label List label.
     * @return string[]
     */
    private static function normalize_string_list($items, string $label): array {
        if ($items === [] || $items === null) {
            return [];
        }
        if (!is_array($items)) {
            throw new \invalid_parameter_exception($label . ' must be a list.');
        }

        $normalized = [];
        foreach ($items as $item) {
            $item = trim((string) $item);
            if ($item === '') {
                throw new \invalid_parameter_exception($label . ' cannot contain empty items.');
            }
            $normalized[] = $item;
        }

        return array_values(array_unique($normalized));
    }
}
