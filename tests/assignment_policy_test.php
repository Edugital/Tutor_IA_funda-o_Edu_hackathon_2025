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

use assignfeedback_aitutoria\local\assignment_policy;

/**
 * Assignment policy and competency mapping tests.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignfeedback_aitutoria\local\assignment_policy
 */
final class assignment_policy_test extends \advanced_testcase {
    /**
     * Empty mode defaults to disabled and known modes remain unchanged.
     */
    public function test_modes_are_normalized(): void {
        $this->assertSame(assignment_policy::MODE_DISABLED, assignment_policy::normalize_mode(''));
        $this->assertSame(assignment_policy::MODE_SHADOW, assignment_policy::normalize_mode('shadow'));
        $this->assertSame(assignment_policy::MODE_ASSISTIVE, assignment_policy::normalize_mode('assistive'));
    }

    /**
     * Unknown modes are rejected.
     */
    public function test_unknown_mode_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        assignment_policy::normalize_mode('automatic-grading');
    }

    /**
     * A rubric criterion may reference an existing institutional competency.
     */
    public function test_valid_competency_mapping_is_accepted(): void {
        $rubric = assignment_policy::parse_rubric(
            $this->rubric('critical-thinking'),
            $this->framework()
        );

        $this->assertSame('critical-thinking', $rubric['criteria'][0]['competencyid']);
    }

    /**
     * Unknown institutional competency references are rejected.
     */
    public function test_unknown_competency_mapping_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        assignment_policy::parse_rubric($this->rubric('unknown'), $this->framework());
    }

    /**
     * Referencing a competency without a site framework is rejected.
     */
    public function test_mapping_without_framework_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        assignment_policy::parse_rubric($this->rubric('critical-thinking'), '');
    }

    /**
     * A rubric without competency mappings remains valid without a framework.
     */
    public function test_unmapped_rubric_does_not_require_framework(): void {
        $rubric = assignment_policy::parse_rubric($this->rubric(''), '');
        $this->assertSame('', $rubric['criteria'][0]['competencyid']);
    }

    /**
     * Build a structured activity rubric fixture.
     *
     * @param string $competencyid Institutional competency id.
     * @return string
     */
    private function rubric(string $competencyid): string {
        return json_encode([
            'version' => 'activity-v1',
            'criteria' => [[
                'id' => 'argument-quality',
                'title' => 'Argument quality',
                'weight' => 1,
                'competencyid' => $competencyid,
                'levels' => [
                    ['id' => 'developing', 'label' => 'Developing', 'score' => 1],
                    ['id' => 'proficient', 'label' => 'Proficient', 'score' => 2],
                ],
            ]],
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Build an institutional framework fixture.
     *
     * @return string
     */
    private function framework(): string {
        return json_encode([
            'version' => 'institution-v1',
            'competencies' => [[
                'id' => 'critical-thinking',
                'title' => 'Critical thinking',
                'indicators' => [[
                    'id' => 'argument-quality',
                    'title' => 'Builds a reasoned argument',
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);
    }
}
