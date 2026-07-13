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

use assignfeedback_aitutoria\local\rubric_parser;

/**
 * Structured rubric parser tests.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignfeedback_aitutoria\local\rubric_parser
 */
final class rubric_parser_test extends \advanced_testcase {
    /**
     * A valid rubric is normalized and levels are ordered by score.
     */
    public function test_valid_rubric_is_normalized(): void {
        $rubric = rubric_parser::parse(json_encode([
            'version' => '2026.1',
            'title' => 'Writing rubric',
            'criteria' => [[
                'id' => 'clarity',
                'title' => 'Clarity',
                'weight' => 60,
                'competencyid' => 'communication',
                'levels' => [
                    ['id' => 'advanced', 'label' => 'Advanced', 'score' => 3],
                    ['id' => 'developing', 'label' => 'Developing', 'score' => 1],
                    ['id' => 'proficient', 'label' => 'Proficient', 'score' => 2],
                ],
            ]],
        ], JSON_THROW_ON_ERROR));

        $this->assertSame('2026.1', $rubric['version']);
        $this->assertSame('clarity', $rubric['criteria'][0]['id']);
        $this->assertSame('developing', $rubric['criteria'][0]['levels'][0]['id']);
        $this->assertSame('advanced', $rubric['criteria'][0]['levels'][2]['id']);
        $this->assertSame(60.0, $rubric['criteria'][0]['weight']);
    }

    /**
     * Criterion identifiers must be unique.
     */
    public function test_duplicate_criterion_is_rejected(): void {
        $criterion = [
            'id' => 'clarity',
            'title' => 'Clarity',
            'weight' => 1,
            'levels' => [
                ['id' => 'low', 'label' => 'Low', 'score' => 0],
                ['id' => 'high', 'label' => 'High', 'score' => 1],
            ],
        ];

        $this->expectException(\invalid_parameter_exception::class);
        rubric_parser::validate([
            'version' => 'v1',
            'criteria' => [$criterion, $criterion],
        ]);
    }

    /**
     * Invalid JSON is rejected before assessment processing.
     */
    public function test_invalid_json_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        rubric_parser::parse('{invalid');
    }
}
