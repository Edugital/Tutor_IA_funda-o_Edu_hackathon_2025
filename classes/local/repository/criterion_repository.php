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

namespace assignfeedback_aitutoria\local\repository;

use assignfeedback_aitutoria\local\dto\criterion_result;
use assignfeedback_aitutoria\local\idempotency;

/**
 * Persistence boundary for criterion-level evidence and recommendations.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class criterion_repository {
    /** Database table. */
    private const TABLE = 'assignfeedback_aitutoria_crt';

    /**
     * Replace all criterion results for one job atomically.
     *
     * @param int $jobid Job id.
     * @param criterion_result[] $criteria Criterion results.
     */
    public static function replace_for_job(int $jobid, array $criteria): void {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records(self::TABLE, ['jobid' => $jobid]);
        $now = time();

        foreach ($criteria as $criterion) {
            if (!$criterion instanceof criterion_result) {
                throw new \invalid_parameter_exception('Criterion persistence requires criterion_result instances.');
            }

            $DB->insert_record(self::TABLE, (object) [
                'jobid' => $jobid,
                'criterionkey' => $criterion->get_criterionkey(),
                'proposedlevel' => $criterion->get_proposedlevel(),
                'rationale' => $criterion->get_rationale(),
                'evidencejson' => idempotency::canonical_json($criterion->get_evidence()),
                'uncertainty' => $criterion->get_uncertainty(),
                'requireshuman' => $criterion->requires_human() ? 1 : 0,
                'timecreated' => $now,
            ]);
        }

        $transaction->allow_commit();
    }

    /**
     * Return criterion records for a job.
     *
     * @param int $jobid Job id.
     * @return \stdClass[]
     */
    public static function get_for_job(int $jobid): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['jobid' => $jobid], 'id ASC');
    }
}
