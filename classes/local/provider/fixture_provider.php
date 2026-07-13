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

namespace assignfeedback_aitutoria\local\provider;

use assignfeedback_aitutoria\local\dto\assessment_request;
use assignfeedback_aitutoria\local\dto\assessment_result;
use assignfeedback_aitutoria\local\dto\criterion_result;

/**
 * Deterministic provider available only to automated tests.
 *
 * This provider never calls an external service and never writes grades.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class fixture_provider implements provider_interface {
    /** @var string Fixed level returned for every criterion. */
    private string $level;

    /**
     * Constructor.
     *
     * @param string $level Fixed level identifier.
     */
    public function __construct(string $level = 'proficient') {
        $level = trim($level);
        if ($level === '') {
            throw new \invalid_parameter_exception('Fixture provider level cannot be empty.');
        }
        $this->level = $level;
    }

    /** @return string Provider identifier. */
    public function get_name(): string {
        return 'fixture';
    }

    /** @return string Model identifier. */
    public function get_model(): string {
        return 'deterministic-fixture-v1';
    }

    /** @return string Prompt version. */
    public function get_promptversion(): string {
        return 'fixture-prompt-v1';
    }

    /**
     * Generate deterministic advisory results.
     *
     * @param assessment_request $request Assessment request.
     * @return assessment_result
     */
    public function assess(assessment_request $request): assessment_result {
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            throw new \coding_exception('The fixture provider is restricted to PHPUnit.');
        }

        $criteria = [];
        foreach ($request->get_rubric()['criteria'] ?? [] as $criterion) {
            $criteria[] = new criterion_result(
                (string) $criterion['id'],
                $this->level,
                'Deterministic fixture rationale for automated testing.',
                [[
                    'excerpt' => \core_text::substr($request->get_submissiontext(), 0, 120),
                    'location' => 'submission',
                ]],
                'high',
                true
            );
        }

        return new assessment_result(
            $this->get_name(),
            $this->get_model(),
            $this->get_promptversion(),
            'Deterministic suggestion generated for automated testing only.',
            $criteria,
            ['externalcall' => false]
        );
    }
}
