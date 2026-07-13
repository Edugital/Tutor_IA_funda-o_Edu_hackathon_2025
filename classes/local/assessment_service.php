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

namespace assignfeedback_aitutoria\local;

use assignfeedback_aitutoria\local\dto\assessment_request;
use assignfeedback_aitutoria\local\provider\provider_interface;
use assignfeedback_aitutoria\local\provider\provider_registry;
use assignfeedback_aitutoria\local\repository\audit_repository;
use assignfeedback_aitutoria\local\repository\criterion_repository;
use assignfeedback_aitutoria\local\repository\job_repository;
use assignfeedback_aitutoria\local\repository\snapshot_repository;
use assignfeedback_aitutoria\task\process_assessment;

/**
 * Orchestrates private advisory assessment processing.
 *
 * The service never writes numeric Moodle grades. Shadow-mode results remain
 * private to the job tables. Assistive-mode suggestions are copied only to the
 * teacher review surface and still require explicit human publication.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class assessment_service {
    /** Results are stored for calibration but not shown in the grading form. */
    public const MODE_SHADOW = 'shadow';

    /** Suggestions are shown privately to teachers for review. */
    public const MODE_ASSISTIVE = 'assistive';

    /**
     * Queue one assessment or return its existing idempotent job.
     *
     * @param assessment_request $request Assessment input.
     * @param provider_interface $provider Provider implementation.
     * @param int|null $actorid User who initiated the request.
     * @param bool $queuetask Whether to enqueue the Moodle adhoc task.
     * @return array{job: \stdClass, created: bool}
     */
    public static function queue(
        assessment_request $request,
        provider_interface $provider,
        ?int $actorid = null,
        bool $queuetask = true
    ): array {
        $normalizedrequest = self::normalize_request($request);
        $created = job_repository::create_or_get($normalizedrequest, $provider);
        $job = $created['job'];

        if (!$created['created']) {
            return $created;
        }

        snapshot_repository::create((int) $job->id, $normalizedrequest);
        audit_repository::record(
            $normalizedrequest->get_assignmentid(),
            $normalizedrequest->get_gradeid(),
            'job_queued',
            (int) $job->id,
            $actorid,
            [
                'provider' => $provider->get_name(),
                'model' => $provider->get_model(),
                'mode' => $normalizedrequest->get_policy()['mode'],
                'rubricversion' => $normalizedrequest->get_rubric()['version'],
            ]
        );

        if ($queuetask) {
            $task = new process_assessment();
            $task->set_custom_data(['jobid' => (int) $job->id]);
            $task->set_attempts_available((int) $job->maxattempts);
            \core\task\manager::queue_adhoc_task($task, true);
        }

        return $created;
    }

    /**
     * Execute one job under a distributed lock.
     *
     * @param int $jobid Job id.
     * @param provider_interface|null $provider Optional injected provider for tests.
     * @return \stdClass Completed job.
     */
    public static function execute(int $jobid, ?provider_interface $provider = null): \stdClass {
        $lockfactory = \core\lock\lock_config::get_lock_factory('assignfeedback_aitutoria_job');
        $lock = $lockfactory->get_lock('job:' . $jobid, 10);
        if (!$lock) {
            throw new \moodle_exception('locktimeout');
        }

        try {
            $job = job_repository::get($jobid);
            if ($job->status === job_repository::STATUS_COMPLETE) {
                return $job;
            }
            if ((int) $job->timeavailable > time()) {
                throw new \coding_exception('Assessment job is not yet available for retry.');
            }

            $provider = $provider ?? provider_registry::get((string) $job->provider);
            if ($provider->get_name() !== $job->provider) {
                throw new \coding_exception('Assessment provider does not match the queued job.');
            }

            $job = job_repository::mark_processing($jobid);
            $request = snapshot_repository::to_request($job);
            $result = $provider->assess($request);

            if ($result->get_provider() !== $job->provider) {
                throw new \coding_exception('Assessment result provenance does not match the queued provider.');
            }

            $scoring = scoring_policy::calculate($request->get_rubric(), $result->get_criteria());
            criterion_repository::replace_for_job($jobid, $result->get_criteria());
            job_repository::mark_complete($jobid, $result, $scoring);

            $mode = $request->get_policy()['mode'];
            if ($mode === self::MODE_ASSISTIVE) {
                feedback_repository::store_ai_suggestion(
                    $request->get_assignmentid(),
                    $request->get_gradeid(),
                    $result->get_suggestion(),
                    $result->get_model(),
                    $result->get_promptversion(),
                    (string) $request->get_rubric()['version']
                );
            }

            audit_repository::record(
                $request->get_assignmentid(),
                $request->get_gradeid(),
                'job_completed',
                $jobid,
                null,
                [
                    'provider' => $result->get_provider(),
                    'model' => $result->get_model(),
                    'promptversion' => $result->get_promptversion(),
                    'rubricversion' => $request->get_rubric()['version'],
                    'mode' => $mode,
                    'percentage' => $scoring['percentage'],
                    'criterioncount' => count($result->get_criteria()),
                ]
            );

            return job_repository::get($jobid);
        } catch (\Throwable $exception) {
            $job = job_repository::get($jobid);
            if ($job->status !== job_repository::STATUS_COMPLETE) {
                $retry = job_repository::mark_failed($jobid, $exception->getMessage());
                audit_repository::record(
                    (int) $job->assignment,
                    (int) $job->grade,
                    $retry ? 'job_retry_scheduled' : 'job_failed',
                    $jobid,
                    null,
                    [
                        'exceptionclass' => get_class($exception),
                        'attempts' => (int) $job->attempts,
                        'maxattempts' => (int) $job->maxattempts,
                    ]
                );
            }
            throw $exception;
        } finally {
            $lock->release();
        }
    }

    /**
     * Validate rubric and effective mode before persistence.
     *
     * @param assessment_request $request Original request.
     * @return assessment_request Normalized request.
     */
    private static function normalize_request(assessment_request $request): assessment_request {
        $rubric = rubric_parser::validate($request->get_rubric());
        $policy = $request->get_policy();
        $mode = trim((string) ($policy['mode'] ?? ''));

        if (!in_array($mode, [self::MODE_SHADOW, self::MODE_ASSISTIVE], true)) {
            throw new \invalid_parameter_exception('Assessment mode must be shadow or assistive.');
        }

        $policy['mode'] = $mode;
        $policy['requireshuman'] = true;
        $policy['allowgradewrite'] = false;

        return new assessment_request(
            $request->get_assignmentid(),
            $request->get_gradeid(),
            $request->get_submissiontext(),
            $rubric,
            $policy
        );
    }
}
