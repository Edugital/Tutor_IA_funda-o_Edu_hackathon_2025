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

/**
 * Provider-independent assessment contract.
 *
 * Implementations return advisory results only. They must not publish
 * feedback or update Moodle grades.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface provider_interface {
    /**
     * Stable provider identifier.
     *
     * @return string
     */
    public function get_name(): string;

    /**
     * Model identifier used for provenance.
     *
     * @return string
     */
    public function get_model(): string;

    /**
     * Prompt/template version used for provenance.
     *
     * @return string
     */
    public function get_promptversion(): string;

    /**
     * Generate an unpublished advisory assessment.
     *
     * @param assessment_request $request Immutable assessment request.
     * @return assessment_result
     */
    public function assess(assessment_request $request): assessment_result;
}
