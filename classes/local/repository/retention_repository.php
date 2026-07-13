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

/**
 * Deletes expired advisory artifacts while preserving official feedback.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class retention_repository {
    /**
     * Delete completed or permanently failed jobs older than the cutoff.
     *
     * @param int $cutoff Unix timestamp.
     * @param int $batchsize Maximum jobs removed per transaction.
     * @return int Number of deleted jobs.
     */
    public static function purge_expired(int $cutoff, int $batchsize = 500): int {
        global $DB;

        if ($cutoff < 1 || $batchsize < 1 || $batchsize > 5000) {
            throw new \invalid_parameter_exception('Retention cutoff and batch size must be valid.');
        }

        $sql = "SELECT id
                  FROM {assignfeedback_aitutoria_job}
                 WHERE status IN (:complete, :failed)
                   AND timemodified < :cutoff
              ORDER BY id ASC";
        $jobids = array_keys($DB->get_records_sql(
            $sql,
            [
                'complete' => job_repository::STATUS_COMPLETE,
                'failed' => job_repository::STATUS_FAILED,
                'cutoff' => $cutoff,
            ],
            0,
            $batchsize
        ));

        if (empty($jobids)) {
            return 0;
        }

        [$jobsql, $params] = $DB->get_in_or_equal($jobids, SQL_PARAMS_NAMED, 'expiredjob');
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records_select('assignfeedback_aitutoria_aud', "jobid {$jobsql}", $params);
        $DB->delete_records_select('assignfeedback_aitutoria_crt', "jobid {$jobsql}", $params);
        $DB->delete_records_select('assignfeedback_aitutoria_snp', "jobid {$jobsql}", $params);
        $DB->delete_records_select('assignfeedback_aitutoria_job', "id {$jobsql}", $params);
        $transaction->allow_commit();

        return count($jobids);
    }
}
