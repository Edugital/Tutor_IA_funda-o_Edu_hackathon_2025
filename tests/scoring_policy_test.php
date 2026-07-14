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

use assignfeedback_aitutoria\local\dto\criterion_result;
use assignfeedback_aitutoria\local\scoring_policy;

/**
 * Deterministic advisory scoring tests.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignfeedback_aitutoria\local\scoring_policy
 */
final class scoring_policy_test extends \advanced_testcase {
    /**
     * Rubric levels and weights produce a reproducible percentage.
     */
    public function test_weighted_percentage_uses_rubric_scores(): void {
        $result = scoring_policy::calculate($this->rubric(), [
            $this->criterion('clarity', 'proficient'),
            $this->criterion('evidence', 'advanced'),
        ]);

        $this->assertSame(80.0, $result['percentage']);
        $this->assertSame(80.0, $result['weightedscore']);
        $this->assertSame(100.0, $result['totalweight']);
        $this->assertTrue($result['criteria'][0]['requireshuman']);
    }

    /**
     * A provider cannot introduce a level absent from the rubric.
     */
    public function test_unknown_level_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        scoring_policy::calculate($this->rubric(), [
            $this->criterion('clarity', 'invented'),
            $this->criterion('evidence', 'advanced'),
        ]);
    }

    /**
     * All rubric criteria are required for a calculation.
     */
    public function test_missing_criterion_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        scoring_policy::calculate($this->rubric(), [
            $this->criterion('clarity', 'proficient'),
        ]);
    }

    /**
     * Create the shared rubric fixture.
     *
     * @return array
     */
    private function rubric(): array {
        return [
            'version' => 'v1',
            'criteria' => [
                [
                    'id' => 'clarity',
                    'title' => 'Clarity',
                    'weight' => 60,
                    'levels' => [
                        ['id' => 'developing', 'label' => 'Developing', 'score' => 1],
                        ['id' => 'proficient', 'label' => 'Proficient', 'score' => 2],
                        ['id' => 'advanced', 'label' => 'Advanced', 'score' => 3],
                    ],
                ],
                [
                    'id' => 'evidence',
                    'title' => 'Evidence',
                    'weight' => 40,
                    'levels' => [
                        ['id' => 'developing', 'label' => 'Developing', 'score' => 1],
                        ['id' => 'proficient', 'label' => 'Proficient', 'score' => 2],
                        ['id' => 'advanced', 'label' => 'Advanced', 'score' => 3],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create a criterion result fixture.
     *
     * @param string $criterionkey Criterion id.
     * @param string $level Level id.
     * @return criterion_result
     */
    private function criterion(string $criterionkey, string $level): criterion_result {
        return new criterion_result(
            $criterionkey,
            $level,
            'Fixture rationale.',
            [['excerpt' => 'Evidence excerpt.', 'location' => 'paragraph 1']],
            'medium',
            true
        );
    }
}
