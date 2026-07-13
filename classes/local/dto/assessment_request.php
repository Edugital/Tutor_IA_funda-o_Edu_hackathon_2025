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
 * Immutable input passed to an assessment provider.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class assessment_request {
    /** @var int Assignment id. */
    private int $assignmentid;

    /** @var int Grade id. */
    private int $gradeid;

    /** @var string Submission text snapshot. */
    private string $submissiontext;

    /** @var array Structured rubric. */
    private array $rubric;

    /** @var array Effective policy. */
    private array $policy;

    /**
     * Constructor.
     *
     * @param int $assignmentid Assignment id.
     * @param int $gradeid Grade id.
     * @param string $submissiontext Submission text snapshot.
     * @param array $rubric Structured rubric.
     * @param array $policy Effective policy.
     */
    public function __construct(
        int $assignmentid,
        int $gradeid,
        string $submissiontext,
        array $rubric,
        array $policy
    ) {
        $submissiontext = trim($submissiontext);
        if ($assignmentid < 1 || $gradeid < 1 || $submissiontext === '') {
            throw new \invalid_parameter_exception('Assessment requests require assignment, grade and submission text.');
        }

        $this->assignmentid = $assignmentid;
        $this->gradeid = $gradeid;
        $this->submissiontext = $submissiontext;
        $this->rubric = $rubric;
        $this->policy = $policy;
    }

    /**
     * Return the assignment id.
     *
     * @return int Assignment id.
     */
    public function get_assignmentid(): int {
        return $this->assignmentid;
    }

    /**
     * Return the grade id.
     *
     * @return int Grade id.
     */
    public function get_gradeid(): int {
        return $this->gradeid;
    }

    /**
     * Return the immutable submission text snapshot.
     *
     * @return string Submission text.
     */
    public function get_submissiontext(): string {
        return $this->submissiontext;
    }

    /**
     * Return the structured rubric snapshot.
     *
     * @return array Structured rubric.
     */
    public function get_rubric(): array {
        return $this->rubric;
    }

    /**
     * Return the effective policy snapshot.
     *
     * @return array Effective policy.
     */
    public function get_policy(): array {
        return $this->policy;
    }
}
