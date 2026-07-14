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

use assignfeedback_aitutoria\local\dto\assessment_request;
use assignfeedback_aitutoria\local\idempotency;

/**
 * Persistence boundary for immutable assessment input snapshots.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class snapshot_repository {
    /** Database table. */
    private const TABLE = 'assignfeedback_aitutoria_snp';

    /**
     * Create a snapshot once for a job.
     *
     * @param int $jobid Job id.
     * @param assessment_request $request Assessment request.
     * @return \stdClass Snapshot record.
     */
    public static function create(int $jobid, assessment_request $request): \stdClass {
        global $DB;

        $existing = self::get_by_job($jobid);
        if ($existing) {
            return $existing;
        }

        $record = (object) [
            'jobid' => $jobid,
            'submissionhash' => hash('sha256', $request->get_submissiontext()),
            'submissiontext' => $request->get_submissiontext(),
            'rubricjson' => idempotency::canonical_json($request->get_rubric()),
            'rubricversion' => (string) ($request->get_rubric()['version'] ?? ''),
            'policyjson' => idempotency::canonical_json($request->get_policy()),
            'timecreated' => time(),
        ];
        $record->id = $DB->insert_record(self::TABLE, $record);

        return $record;
    }

    /**
     * Return a snapshot by job id.
     *
     * @param int $jobid Job id.
     * @return \stdClass|false
     */
    public static function get_by_job(int $jobid) {
        global $DB;

        return $DB->get_record(self::TABLE, ['jobid' => $jobid]);
    }

    /**
     * Reconstruct an immutable request from a snapshot and job.
     *
     * @param \stdClass $job Job record.
     * @return assessment_request
     */
    public static function to_request(\stdClass $job): assessment_request {
        $snapshot = self::get_by_job((int) $job->id);
        if (!$snapshot) {
            throw new \coding_exception('Assessment job snapshot is missing.');
        }

        try {
            $rubric = json_decode($snapshot->rubricjson, true, 64, JSON_THROW_ON_ERROR);
            $policy = json_decode($snapshot->policyjson, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new \coding_exception('Assessment job snapshot is corrupted.');
        }

        return new assessment_request(
            (int) $job->assignment,
            (int) $job->grade,
            (string) $snapshot->submissiontext,
            $rubric,
            $policy
        );
    }
}
