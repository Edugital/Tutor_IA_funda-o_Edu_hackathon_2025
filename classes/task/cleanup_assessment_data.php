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

use assignfeedback_aitutoria\local\repository\retention_repository;

/**
 * Apply the configured advisory-data retention policy.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cleanup_assessment_data extends \core\task\scheduled_task {
    /**
     * Human-readable task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task:cleanupassessmentdata', 'assignfeedback_aitutoria');
    }

    /**
     * Delete expired private advisory artifacts in bounded batches.
     */
    public function execute(): void {
        $retentiondays = (int) get_config('assignfeedback_aitutoria', 'retentiondays');
        if ($retentiondays <= 0) {
            mtrace('AI tutoring advisory-data retention is disabled.');
            return;
        }

        $cutoff = time() - ($retentiondays * DAYSECS);
        $total = 0;
        do {
            $deleted = retention_repository::purge_expired($cutoff, 500);
            $total += $deleted;
        } while ($deleted === 500);

        mtrace("Deleted {$total} expired AI tutoring assessment job(s).");
    }
}
