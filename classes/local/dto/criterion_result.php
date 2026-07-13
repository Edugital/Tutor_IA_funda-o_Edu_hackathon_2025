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
 * Advisory result for one rubric criterion.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class criterion_result {
    /** @var string Criterion identifier. */
    private string $criterionkey;

    /** @var string Proposed rubric level identifier. */
    private string $proposedlevel;

    /** @var string Human-readable rationale. */
    private string $rationale;

    /** @var array Evidence excerpts and locations. */
    private array $evidence;

    /** @var string Uncertainty classification. */
    private string $uncertainty;

    /** @var bool Whether policy requires human review. */
    private bool $requireshuman;

    /**
     * Constructor.
     *
     * @param string $criterionkey Criterion identifier.
     * @param string $proposedlevel Proposed rubric level identifier.
     * @param string $rationale Human-readable rationale.
     * @param array $evidence Evidence excerpts and locations.
     * @param string $uncertainty One of low, medium or high.
     * @param bool $requireshuman Whether review is mandatory.
     */
    public function __construct(
        string $criterionkey,
        string $proposedlevel,
        string $rationale,
        array $evidence,
        string $uncertainty = 'high',
        bool $requireshuman = true
    ) {
        $criterionkey = trim($criterionkey);
        $proposedlevel = trim($proposedlevel);
        $rationale = trim($rationale);
        $uncertainty = trim($uncertainty);

        if ($criterionkey === '' || $proposedlevel === '' || $rationale === '') {
            throw new \invalid_parameter_exception('Criterion results require a criterion, level and rationale.');
        }
        if (!in_array($uncertainty, ['low', 'medium', 'high'], true)) {
            throw new \invalid_parameter_exception('Uncertainty must be low, medium or high.');
        }

        foreach ($evidence as $item) {
            if (!is_array($item) || empty(trim((string) ($item['excerpt'] ?? '')))) {
                throw new \invalid_parameter_exception('Every evidence item requires a non-empty excerpt.');
            }
        }

        $this->criterionkey = $criterionkey;
        $this->proposedlevel = $proposedlevel;
        $this->rationale = $rationale;
        $this->evidence = array_values($evidence);
        $this->uncertainty = $uncertainty;
        $this->requireshuman = $requireshuman;
    }

    /** @return string Criterion identifier. */
    public function get_criterionkey(): string {
        return $this->criterionkey;
    }

    /** @return string Proposed rubric level identifier. */
    public function get_proposedlevel(): string {
        return $this->proposedlevel;
    }

    /** @return string Rationale. */
    public function get_rationale(): string {
        return $this->rationale;
    }

    /** @return array Evidence. */
    public function get_evidence(): array {
        return $this->evidence;
    }

    /** @return string Uncertainty. */
    public function get_uncertainty(): string {
        return $this->uncertainty;
    }

    /** @return bool Whether human review is mandatory. */
    public function requires_human(): bool {
        return $this->requireshuman;
    }

    /**
     * Convert to a serializable structure.
     *
     * @return array
     */
    public function to_array(): array {
        return [
            'criterionkey' => $this->criterionkey,
            'proposedlevel' => $this->proposedlevel,
            'rationale' => $this->rationale,
            'evidence' => $this->evidence,
            'uncertainty' => $this->uncertainty,
            'requireshuman' => $this->requireshuman,
        ];
    }
}
