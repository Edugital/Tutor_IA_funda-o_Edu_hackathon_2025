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

namespace assignfeedback_aitutoria\task;

use assignfeedback_aitutoria\local\assessment_service;
use assignfeedback_aitutoria\local\repository\job_repository;

/**
 * Process one private advisory assessment job.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class process_assessment extends \core\task\adhoc_task {
    /**
     * Execute the queued job.
     */
    public function execute(): void {
        $customdata = $this->get_custom_data();
        $jobid = (int) ($customdata->jobid ?? 0);
        if ($jobid < 1) {
            throw new \coding_exception('Assessment task requires a job id.');
        }

        try {
            assessment_service::execute($jobid);
        } catch (\Throwable $exception) {
            $job = job_repository::get($jobid);
            if ($job->status === job_repository::STATUS_QUEUED) {
                $delay = max(1, (int) $job->timeavailable - time());
                $this->set_fail_delay($delay);
            }
            throw $exception;
        }
    }
}
