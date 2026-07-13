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

use assignfeedback_aitutoria\local\dto\assessment_request;
use assignfeedback_aitutoria\local\provider\fixture_provider;
use assignfeedback_aitutoria\local\provider\provider_registry;

/**
 * Provider contract and provenance tests.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \assignfeedback_aitutoria\local\provider\fixture_provider
 * @covers     \assignfeedback_aitutoria\local\provider\provider_registry
 * @covers     \assignfeedback_aitutoria\local\dto\assessment_result
 * @covers     \assignfeedback_aitutoria\local\dto\criterion_result
 */
final class provider_contract_test extends \advanced_testcase {
    /**
     * Fixture output is complete, traceable and always requires review.
     */
    public function test_fixture_result_has_provenance_and_evidence(): void {
        $provider = new fixture_provider('proficient');
        $result = $provider->assess(new assessment_request(
            10,
            20,
            'The student supports the argument with a concrete example.',
            $this->rubric(),
            ['mode' => 'shadow']
        ));

        $this->assertSame('fixture', $result->get_provider());
        $this->assertSame('deterministic-fixture-v1', $result->get_model());
        $this->assertSame('fixture-prompt-v1', $result->get_promptversion());
        $this->assertCount(2, $result->get_criteria());
        $this->assertTrue($result->get_criteria()[0]->requires_human());
        $this->assertNotEmpty($result->get_criteria()[0]->get_evidence());
        $this->assertFalse($result->get_metadata()['externalcall']);
    }

    /**
     * The registry exposes only the deterministic fixture in PHPUnit.
     */
    public function test_registry_is_closed_by_default(): void {
        $this->assertArrayHasKey('fixture', provider_registry::available());
        $this->assertInstanceOf(fixture_provider::class, provider_registry::get('fixture'));

        $this->expectException(\coding_exception::class);
        provider_registry::get('unknown-provider');
    }

    /**
     * Build a structured rubric fixture.
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
                    'weight' => 50,
                    'levels' => [
                        ['id' => 'developing', 'label' => 'Developing', 'score' => 1],
                        ['id' => 'proficient', 'label' => 'Proficient', 'score' => 2],
                    ],
                ],
                [
                    'id' => 'evidence',
                    'title' => 'Evidence',
                    'weight' => 50,
                    'levels' => [
                        ['id' => 'developing', 'label' => 'Developing', 'score' => 1],
                        ['id' => 'proficient', 'label' => 'Proficient', 'score' => 2],
                    ],
                ],
            ],
        ];
    }
}
