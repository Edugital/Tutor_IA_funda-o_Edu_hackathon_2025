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

namespace assignfeedback_aitutoria\local\dto;

/**
 * Advisory provider output awaiting human review.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class assessment_result {
    /** @var string Provider identifier. */
    private string $provider;

    /** @var string Model identifier. */
    private string $model;

    /** @var string Prompt/template version. */
    private string $promptversion;

    /** @var string Suggested feedback summary. */
    private string $suggestion;

    /** @var criterion_result[] Criterion-level results. */
    private array $criteria;

    /** @var array Provider metadata safe for audit. */
    private array $metadata;

    /**
     * Constructor.
     *
     * @param string $provider Provider identifier.
     * @param string $model Model identifier.
     * @param string $promptversion Prompt/template version.
     * @param string $suggestion Suggested feedback.
     * @param criterion_result[] $criteria Criterion-level results.
     * @param array $metadata Provider metadata safe for audit.
     */
    public function __construct(
        string $provider,
        string $model,
        string $promptversion,
        string $suggestion,
        array $criteria,
        array $metadata = []
    ) {
        $provider = trim($provider);
        $model = trim($model);
        $promptversion = trim($promptversion);
        $suggestion = trim($suggestion);

        if ($provider === '' || $model === '' || $promptversion === '' || $suggestion === '') {
            throw new \invalid_parameter_exception('Assessment results require provider provenance and suggestion text.');
        }
        if (empty($criteria)) {
            throw new \invalid_parameter_exception('Assessment results require at least one criterion result.');
        }
        foreach ($criteria as $criterion) {
            if (!$criterion instanceof criterion_result) {
                throw new \invalid_parameter_exception('Criteria must contain criterion_result instances.');
            }
        }

        $this->provider = $provider;
        $this->model = $model;
        $this->promptversion = $promptversion;
        $this->suggestion = $suggestion;
        $this->criteria = array_values($criteria);
        $this->metadata = $metadata;
    }

    /** @return string Provider identifier. */
    public function get_provider(): string {
        return $this->provider;
    }

    /** @return string Model identifier. */
    public function get_model(): string {
        return $this->model;
    }

    /** @return string Prompt/template version. */
    public function get_promptversion(): string {
        return $this->promptversion;
    }

    /** @return string Suggested feedback. */
    public function get_suggestion(): string {
        return $this->suggestion;
    }

    /** @return criterion_result[] Criterion results. */
    public function get_criteria(): array {
        return $this->criteria;
    }

    /** @return array Provider metadata. */
    public function get_metadata(): array {
        return $this->metadata;
    }

    /**
     * Convert to a serializable structure.
     *
     * @return array
     */
    public function to_array(): array {
        return [
            'provider' => $this->provider,
            'model' => $this->model,
            'promptversion' => $this->promptversion,
            'suggestion' => $this->suggestion,
            'criteria' => array_map(
                static fn(criterion_result $criterion): array => $criterion->to_array(),
                $this->criteria
            ),
            'metadata' => $this->metadata,
        ];
    }
}
