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
use assignfeedback_aitutoria\local\dto\assessment_result;
use assignfeedback_aitutoria\local\idempotency;
use assignfeedback_aitutoria\local\provider\provider_interface;

/**
 * Persistence boundary for idempotent assessment jobs.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class job_repository {
    /** Database table. */
    private const TABLE = 'assignfeedback_aitutoria_job';

    /** Job is waiting for execution. */
    public const STATUS_QUEUED = 'queued';

    /** Job is being processed under a lock. */
    public const STATUS_PROCESSING = 'processing';

    /** Job finished successfully. */
    public const STATUS_COMPLETE = 'complete';

    /** Job exhausted its retry policy. */
    public const STATUS_FAILED = 'failed';

    /**
     * Return a job by id.
     *
     * @param int $jobid Job id.
     * @return \stdClass
     */
    public static function get(int $jobid): \stdClass {
        global $DB;

        return $DB->get_record(self::TABLE, ['id' => $jobid], '*', MUST_EXIST);
    }

    /**
     * Return a job by idempotency key.
     *
     * @param string $key Idempotency key.
     * @return \stdClass|false
     */
    public static function get_by_key(string $key) {
        global $DB;

        return $DB->get_record(self::TABLE, ['idempotencykey' => $key]);
    }

    /**
     * Create one job or return the existing equivalent job.
     *
     * @param assessment_request $request Assessment request.
     * @param provider_interface $provider Provider contract.
     * @param int $maxattempts Maximum processing attempts.
     * @return array{job: \stdClass, created: bool}
     */
    public static function create_or_get(
        assessment_request $request,
        provider_interface $provider,
        int $maxattempts = 3
    ): array {
        global $DB;

        if ($maxattempts < 1 || $maxattempts > 10) {
            throw new \invalid_parameter_exception('Maximum attempts must be between 1 and 10.');
        }

        $key = idempotency::request_key($request, $provider->get_name());
        $existing = self::get_by_key($key);
        if ($existing) {
            return ['job' => $existing, 'created' => false];
        }

        $now = time();
        $record = (object) [
            'assignment' => $request->get_assignmentid(),
            'grade' => $request->get_gradeid(),
            'status' => self::STATUS_QUEUED,
            'idempotencykey' => $key,
            'provider' => $provider->get_name(),
            'model' => $provider->get_model(),
            'promptversion' => $provider->get_promptversion(),
            'suggestiontext' => null,
            'scoringjson' => null,
            'attempts' => 0,
            'maxattempts' => $maxattempts,
            'lasterror' => null,
            'timeavailable' => $now,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        try {
            $record->id = $DB->insert_record(self::TABLE, $record);
            return ['job' => $record, 'created' => true];
        } catch (\dml_write_exception) {
            $existing = self::get_by_key($key);
            if (!$existing) {
                throw new \dml_write_exception('Could not create or recover the idempotent assessment job.');
            }
            return ['job' => $existing, 'created' => false];
        }
    }

    /**
     * Mark a locked job as processing and increment attempts.
     *
     * @param int $jobid Job id.
     * @return \stdClass Updated job.
     */
    public static function mark_processing(int $jobid): \stdClass {
        global $DB;

        $job = self::get($jobid);
        if ($job->status === self::STATUS_COMPLETE) {
            return $job;
        }
        if ((int) $job->attempts >= (int) $job->maxattempts) {
            throw new \coding_exception('Assessment job has exhausted its retry policy.');
        }

        $job->status = self::STATUS_PROCESSING;
        $job->attempts = (int) $job->attempts + 1;
        $job->lasterror = null;
        $job->timemodified = time();
        $DB->update_record(self::TABLE, $job);

        return $job;
    }

    /**
     * Persist a successful private result.
     *
     * @param int $jobid Job id.
     * @param assessment_result $result Provider result.
     * @param array $scoring Deterministic advisory scoring.
     */
    public static function mark_complete(int $jobid, assessment_result $result, array $scoring): void {
        global $DB;

        $DB->update_record(self::TABLE, (object) [
            'id' => $jobid,
            'status' => self::STATUS_COMPLETE,
            'model' => $result->get_model(),
            'promptversion' => $result->get_promptversion(),
            'suggestiontext' => $result->get_suggestion(),
            'scoringjson' => idempotency::canonical_json($scoring),
            'lasterror' => null,
            'timemodified' => time(),
        ]);
    }

    /**
     * Record a failure and determine whether another attempt is allowed.
     *
     * @param int $jobid Job id.
     * @param string $error Sanitized error message.
     * @param int $retrydelay Delay before retry in seconds.
     * @return bool True when the job may be retried.
     */
    public static function mark_failed(int $jobid, string $error, int $retrydelay = 60): bool {
        global $DB;

        $job = self::get($jobid);
        $retry = (int) $job->attempts < (int) $job->maxattempts;
        $job->status = $retry ? self::STATUS_QUEUED : self::STATUS_FAILED;
        $job->lasterror = \core_text::substr(trim($error), 0, 2000);
        $job->timeavailable = $retry ? time() + max(0, $retrydelay) : 0;
        $job->timemodified = time();
        $DB->update_record(self::TABLE, $job);

        return $retry;
    }
}
