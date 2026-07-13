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

namespace assignfeedback_aitutoria\local\reporting;

/**
 * Build aggregate governance metrics without returning student content.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class governance_report {
    /**
     * Build an aggregate report.
     *
     * @param int|null $assignmentid Optional assignment filter.
     * @param int|null $timefrom Optional inclusive lower timestamp.
     * @param int|null $timeto Optional exclusive upper timestamp.
     * @return array
     */
    public static function build(
        ?int $assignmentid = null,
        ?int $timefrom = null,
        ?int $timeto = null
    ): array {
        global $DB;

        $conditions = [];
        $params = [];
        if ($assignmentid !== null) {
            $conditions[] = 'assignment = :assignment';
            $params['assignment'] = $assignmentid;
        }
        if ($timefrom !== null) {
            $conditions[] = 'timecreated >= :timefrom';
            $params['timefrom'] = $timefrom;
        }
        if ($timeto !== null) {
            $conditions[] = 'timecreated < :timeto';
            $params['timeto'] = $timeto;
        }
        $where = empty($conditions) ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $jobs = $DB->get_records_sql(
            'SELECT * FROM {assignfeedback_aitutoria_job}' . $where . ' ORDER BY id ASC',
            $params
        );

        $statuscounts = self::initial_counts(['queued', 'processing', 'complete', 'failed']);
        $providercounts = [];
        $percentages = [];
        $jobids = [];

        foreach ($jobs as $job) {
            $jobids[] = (int) $job->id;
            $statuscounts[$job->status] = ($statuscounts[$job->status] ?? 0) + 1;
            $providercounts[$job->provider] = ($providercounts[$job->provider] ?? 0) + 1;
            if (!empty($job->scoringjson)) {
                try {
                    $scoring = json_decode($job->scoringjson, true, 64, JSON_THROW_ON_ERROR);
                    if (isset($scoring['percentage']) && is_numeric($scoring['percentage'])) {
                        $percentages[] = (float) $scoring['percentage'];
                    }
                } catch (\JsonException) {
                    // Corrupt historical metrics are ignored but do not expose content.
                }
            }
        }

        $uncertaintycounts = self::initial_counts(['low', 'medium', 'high']);
        $reviewrequired = 0;
        if (!empty($jobids)) {
            [$jobsql, $jobparams] = $DB->get_in_or_equal($jobids, SQL_PARAMS_NAMED, 'reportjob');
            $criteria = $DB->get_records_select(
                'assignfeedback_aitutoria_crt',
                "jobid {$jobsql}",
                $jobparams,
                'id ASC'
            );
            foreach ($criteria as $criterion) {
                $uncertaintycounts[$criterion->uncertainty] =
                    ($uncertaintycounts[$criterion->uncertainty] ?? 0) + 1;
                if (!empty($criterion->requireshuman)) {
                    $reviewrequired++;
                }
            }
        } else {
            $criteria = [];
        }

        $feedbackconditions = [];
        $feedbackparams = [];
        if ($assignmentid !== null) {
            $feedbackconditions[] = 'assignment = :feedbackassignment';
            $feedbackparams['feedbackassignment'] = $assignmentid;
        }
        if ($timefrom !== null) {
            $feedbackconditions[] = 'timemodified >= :feedbacktimefrom';
            $feedbackparams['feedbacktimefrom'] = $timefrom;
        }
        if ($timeto !== null) {
            $feedbackconditions[] = 'timemodified < :feedbacktimeto';
            $feedbackparams['feedbacktimeto'] = $timeto;
        }
        $feedbackwhere = empty($feedbackconditions)
            ? ''
            : ' WHERE ' . implode(' AND ', $feedbackconditions);
        $feedbackrecords = $DB->get_records_sql(
            'SELECT id, decision FROM {assignfeedback_aitutoria}' . $feedbackwhere,
            $feedbackparams
        );
        $decisioncounts = self::initial_counts([
            'manual',
            'accepted_ai',
            'overridden_ai',
            'rejected_ai',
            'escalated',
        ]);
        foreach ($feedbackrecords as $feedback) {
            $decisioncounts[$feedback->decision] = ($decisioncounts[$feedback->decision] ?? 0) + 1;
        }

        $reviewedwithai = $decisioncounts['accepted_ai']
            + $decisioncounts['overridden_ai']
            + $decisioncounts['rejected_ai']
            + $decisioncounts['escalated'];
        $acceptancerate = $reviewedwithai > 0
            ? round(($decisioncounts['accepted_ai'] / $reviewedwithai) * 100, 2)
            : null;

        ksort($providercounts, SORT_STRING);

        return [
            'filters' => [
                'assignmentid' => $assignmentid,
                'timefrom' => $timefrom,
                'timeto' => $timeto,
            ],
            'jobs' => [
                'total' => count($jobs),
                'status' => $statuscounts,
                'providers' => $providercounts,
                'averageadvisorypercentage' => empty($percentages)
                    ? null
                    : round(array_sum($percentages) / count($percentages), 2),
            ],
            'criteria' => [
                'total' => count($criteria),
                'uncertainty' => $uncertaintycounts,
                'requiringhumanreview' => $reviewrequired,
            ],
            'humanreview' => [
                'total' => count($feedbackrecords),
                'decisions' => $decisioncounts,
                'reviewedwithai' => $reviewedwithai,
                'acceptancerate' => $acceptancerate,
            ],
        ];
    }

    /**
     * Create an ordered zero-filled count map.
     *
     * @param string[] $keys Count keys.
     * @return array<string, int>
     */
    private static function initial_counts(array $keys): array {
        return array_fill_keys($keys, 0);
    }
}
