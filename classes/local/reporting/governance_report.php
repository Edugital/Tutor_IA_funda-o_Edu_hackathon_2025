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

        [$where, $params] = self::build_where('timecreated', $assignmentid, $timefrom, $timeto, 'job');
        $jobs = $DB->get_records_sql(
            'SELECT * FROM {assignfeedback_aitutoria_job}' . $where . ' ORDER BY id ASC',
            $params
        );

        $statuscounts = self::initial_counts(['queued', 'processing', 'complete', 'failed']);
        $providercounts = [];
        $percentages = [];
        $jobids = [];
        $corruptscoringrecords = 0;
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
                    $corruptscoringrecords++;
                }
            }
        }

        $uncertaintycounts = self::initial_counts(['low', 'medium', 'high']);
        $reviewrequired = 0;
        $criteria = [];
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
        }

        [$feedbackwhere, $feedbackparams] = self::build_where(
            'timemodified',
            $assignmentid,
            $timefrom,
            $timeto,
            'feedback'
        );
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

        [$calibrationwhere, $calibrationparams] = self::build_where(
            'timemodified',
            $assignmentid,
            $timefrom,
            $timeto,
            'calibration'
        );
        $humancriteria = $DB->get_records_sql(
            'SELECT * FROM {assignfeedback_aitutoria_hcr}' . $calibrationwhere . ' ORDER BY id ASC',
            $calibrationparams
        );
        $compared = 0;
        $matched = 0;
        $mismatched = 0;
        $percriterion = [];
        foreach ($humancriteria as $criterion) {
            if (!isset($percriterion[$criterion->criterionkey])) {
                $percriterion[$criterion->criterionkey] = [
                    'total' => 0,
                    'compared' => 0,
                    'matched' => 0,
                    'mismatched' => 0,
                    'agreementrate' => null,
                ];
            }
            $percriterion[$criterion->criterionkey]['total']++;
            if ($criterion->matchesai === null) {
                continue;
            }
            $compared++;
            $percriterion[$criterion->criterionkey]['compared']++;
            if ((int) $criterion->matchesai === 1) {
                $matched++;
                $percriterion[$criterion->criterionkey]['matched']++;
            } else {
                $mismatched++;
                $percriterion[$criterion->criterionkey]['mismatched']++;
            }
        }
        foreach ($percriterion as &$metrics) {
            if ($metrics['compared'] > 0) {
                $metrics['agreementrate'] = round(($metrics['matched'] / $metrics['compared']) * 100, 2);
            }
        }
        unset($metrics);
        ksort($percriterion, SORT_STRING);
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
                'corruptscoringrecords' => $corruptscoringrecords,
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
            'calibration' => [
                'total' => count($humancriteria),
                'compared' => $compared,
                'matched' => $matched,
                'mismatched' => $mismatched,
                'agreementrate' => $compared > 0 ? round(($matched / $compared) * 100, 2) : null,
                'percriterion' => $percriterion,
            ],
        ];
    }

    /**
     * Create a filtered SQL suffix and parameters.
     *
     * @param string $timefield Timestamp field.
     * @param int|null $assignmentid Assignment filter.
     * @param int|null $timefrom Lower timestamp.
     * @param int|null $timeto Upper timestamp.
     * @param string $prefix Parameter prefix.
     * @return array{0: string, 1: array}
     */
    private static function build_where(
        string $timefield,
        ?int $assignmentid,
        ?int $timefrom,
        ?int $timeto,
        string $prefix
    ): array {
        $conditions = [];
        $params = [];
        if ($assignmentid !== null) {
            $conditions[] = 'assignment = :' . $prefix . 'assignment';
            $params[$prefix . 'assignment'] = $assignmentid;
        }
        if ($timefrom !== null) {
            $conditions[] = $timefield . ' >= :' . $prefix . 'timefrom';
            $params[$prefix . 'timefrom'] = $timefrom;
        }
        if ($timeto !== null) {
            $conditions[] = $timefield . ' < :' . $prefix . 'timeto';
            $params[$prefix . 'timeto'] = $timeto;
        }

        return [empty($conditions) ? '' : ' WHERE ' . implode(' AND ', $conditions), $params];
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
