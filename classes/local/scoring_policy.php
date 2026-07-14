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

use assignfeedback_aitutoria\local\dto\criterion_result;

/**
 * Deterministic advisory scoring derived from rubric levels.
 *
 * This policy never writes to Moodle grades. It produces a reviewable
 * calculation that a human may use as evidence.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class scoring_policy {
    /**
     * Calculate a normalized advisory percentage.
     *
     * Provider-supplied numeric scores are intentionally ignored. The selected
     * level is mapped to the versioned rubric and calculated deterministically.
     *
     * @param array $rubric Validated structured rubric.
     * @param criterion_result[] $results Criterion-level provider results.
     * @return array{percentage: float, weightedscore: float, totalweight: float, criteria: array}
     */
    public static function calculate(array $rubric, array $results): array {
        $rubric = rubric_parser::validate($rubric);
        $resultsbycriterion = [];

        foreach ($results as $result) {
            if (!$result instanceof criterion_result) {
                throw new \invalid_parameter_exception('Scoring requires criterion_result instances.');
            }
            $criterionkey = $result->get_criterionkey();
            if (isset($resultsbycriterion[$criterionkey])) {
                throw new \invalid_parameter_exception('Criterion results must be unique.');
            }
            $resultsbycriterion[$criterionkey] = $result;
        }

        $weightedscore = 0.0;
        $totalweight = 0.0;
        $breakdown = [];

        foreach ($rubric['criteria'] as $criterion) {
            $criterionkey = $criterion['id'];
            if (!isset($resultsbycriterion[$criterionkey])) {
                throw new \invalid_parameter_exception('Every rubric criterion requires an advisory result.');
            }

            $result = $resultsbycriterion[$criterionkey];
            $levels = [];
            $maximumscore = 0.0;

            foreach ($criterion['levels'] as $level) {
                $levels[$level['id']] = (float) $level['score'];
                $maximumscore = max($maximumscore, (float) $level['score']);
            }

            $proposedlevel = $result->get_proposedlevel();
            if (!array_key_exists($proposedlevel, $levels)) {
                throw new \invalid_parameter_exception('Proposed levels must exist in the versioned rubric.');
            }
            if ($maximumscore <= 0) {
                throw new \invalid_parameter_exception('Every criterion requires a positive maximum score.');
            }

            $weight = (float) $criterion['weight'];
            $levelscore = $levels[$proposedlevel];
            $normalized = $levelscore / $maximumscore;
            $weighted = $normalized * $weight;

            $weightedscore += $weighted;
            $totalweight += $weight;
            $breakdown[] = [
                'criterionkey' => $criterionkey,
                'level' => $proposedlevel,
                'rubricscore' => $levelscore,
                'maximumscore' => $maximumscore,
                'weight' => $weight,
                'weightedscore' => round($weighted, 6),
                'uncertainty' => $result->get_uncertainty(),
                'requireshuman' => $result->requires_human(),
            ];
        }

        if (count($resultsbycriterion) !== count($rubric['criteria'])) {
            throw new \invalid_parameter_exception('Results contain criteria not present in the rubric.');
        }

        return [
            'percentage' => round(($weightedscore / $totalweight) * 100, 2),
            'weightedscore' => round($weightedscore, 6),
            'totalweight' => round($totalweight, 6),
            'criteria' => $breakdown,
        ];
    }
}
