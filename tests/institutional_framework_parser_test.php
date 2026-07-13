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

use assignfeedback_aitutoria\local\institutional_framework_parser;

/**
 * Institutional competency framework parser tests.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignfeedback_aitutoria\local\institutional_framework_parser
 */
final class institutional_framework_parser_test extends \advanced_testcase {
    /**
     * A complete framework is normalized deterministically.
     */
    public function test_valid_framework_is_normalized(): void {
        $framework = institutional_framework_parser::parse(json_encode([
            'version' => '2026.1',
            'title' => 'Institutional competencies',
            'competencies' => [[
                'id' => 'critical-thinking',
                'title' => 'Critical thinking',
                'indicators' => [[
                    'id' => 'evidence-use',
                    'title' => 'Uses evidence',
                ]],
                'levels' => [
                    ['id' => 'advanced', 'label' => 'Advanced', 'score' => 3],
                    ['id' => 'developing', 'label' => 'Developing', 'score' => 1],
                ],
                'methods' => ['case analysis', 'peer review', 'case analysis'],
                'evidence' => ['reasoned argument'],
            ]],
        ], JSON_THROW_ON_ERROR));

        $this->assertSame('2026.1', $framework['version']);
        $this->assertSame('critical-thinking', $framework['competencies'][0]['id']);
        $this->assertSame('developing', $framework['competencies'][0]['levels'][0]['id']);
        $this->assertSame(['case analysis', 'peer review'], $framework['competencies'][0]['methods']);
    }

    /**
     * Duplicate competency identifiers are rejected.
     */
    public function test_duplicate_competency_is_rejected(): void {
        $competency = [
            'id' => 'communication',
            'title' => 'Communication',
            'indicators' => [['id' => 'clarity', 'title' => 'Clarity']],
        ];

        $this->expectException(\invalid_parameter_exception::class);
        institutional_framework_parser::validate([
            'version' => 'v1',
            'competencies' => [$competency, $competency],
        ]);
    }

    /**
     * Every competency requires at least one indicator.
     */
    public function test_competency_without_indicator_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        institutional_framework_parser::validate([
            'version' => 'v1',
            'competencies' => [[
                'id' => 'communication',
                'title' => 'Communication',
                'indicators' => [],
            ]],
        ]);
    }

    /**
     * Invalid JSON is rejected.
     */
    public function test_invalid_json_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        institutional_framework_parser::parse('{invalid');
    }
}
