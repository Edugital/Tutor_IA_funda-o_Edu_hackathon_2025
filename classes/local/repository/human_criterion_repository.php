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

use assignfeedback_aitutoria\local\rubric_parser;

/**
 * Persistence boundary for final human rubric selections.
 *
 * These records support calibration only and never change Moodle grades.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class human_criterion_repository {
    /** Database table. */
    private const TABLE = 'assignfeedback_aitutoria_hcr';

    /**
     * Save final human selections for the structured rubric.
     *
     * Empty selections remove an existing human review for the criterion.
     *
     * @param int $assignmentid Assignment id.
     * @param int $gradeid Grade id.
     * @param int|null $jobid Related completed job id.
     * @param int $reviewerid Reviewer user id.
     * @param array $rubric Structured rubric.
     * @param array<string, string> $selections Criterion id to selected level id.
     * @return array{saved: int, removed: int, matched: int, mismatched: int}
     */
    public static function save_reviews(
        int $assignmentid,
        int $gradeid,
        ?int $jobid,
        int $reviewerid,
        array $rubric,
        array $selections
    ): array {
        global $DB;

        if (!$DB->record_exists('assign_grades', ['id' => $gradeid, 'assignment' => $assignmentid])) {
            throw new \invalid_parameter_exception('The grade does not belong to the assignment.');
        }
        if (
            $jobid !== null
            && !$DB->record_exists(
                'assignfeedback_aitutoria_job',
                [
                    'id' => $jobid,
                    'assignment' => $assignmentid,
                    'grade' => $gradeid,
                    'status' => job_repository::STATUS_COMPLETE,
                ]
            )
        ) {
            throw new \invalid_parameter_exception('Criterion reviews may link only to a completed matching job.');
        }

        $rubric = rubric_parser::validate($rubric);
        $criteria = [];
        foreach ($rubric['criteria'] as $criterion) {
            $levels = [];
            foreach ($criterion['levels'] as $level) {
                $levels[$level['id']] = true;
            }
            $criteria[$criterion['id']] = $levels;
        }

        foreach ($selections as $criterionkey => $selectedlevel) {
            if (!isset($criteria[$criterionkey])) {
                throw new \invalid_parameter_exception('Human review contains an unknown rubric criterion.');
            }
            $selectedlevel = trim((string) $selectedlevel);
            if ($selectedlevel !== '' && !isset($criteria[$criterionkey][$selectedlevel])) {
                throw new \invalid_parameter_exception('Human review contains an unknown rubric level.');
            }
        }

        $ailevels = [];
        if ($jobid !== null) {
            $airecords = $DB->get_records(
                'assignfeedback_aitutoria_crt',
                ['jobid' => $jobid],
                '',
                'criterionkey,proposedlevel'
            );
            foreach ($airecords as $airecord) {
                $ailevels[$airecord->criterionkey] = $airecord->proposedlevel;
            }
        }

        $summary = ['saved' => 0, 'removed' => 0, 'matched' => 0, 'mismatched' => 0];
        $transaction = $DB->start_delegated_transaction();
        $now = time();

        foreach ($criteria as $criterionkey => $levels) {
            $selectedlevel = trim((string) ($selections[$criterionkey] ?? ''));
            $existing = $DB->get_record(self::TABLE, [
                'grade' => $gradeid,
                'criterionkey' => $criterionkey,
            ]);

            if ($selectedlevel === '') {
                if ($existing) {
                    $DB->delete_records(self::TABLE, ['id' => $existing->id]);
                    $summary['removed']++;
                }
                continue;
            }

            $aiproposedlevel = $ailevels[$criterionkey] ?? null;
            $matchesai = $aiproposedlevel === null ? null : (int) ($aiproposedlevel === $selectedlevel);
            if ($matchesai === 1) {
                $summary['matched']++;
            } elseif ($matchesai === 0) {
                $summary['mismatched']++;
            }

            $record = $existing ?: (object) [
                'assignment' => $assignmentid,
                'grade' => $gradeid,
                'criterionkey' => $criterionkey,
                'timecreated' => $now,
            ];
            $record->jobid = $jobid;
            $record->selectedlevel = $selectedlevel;
            $record->aiproposedlevel = $aiproposedlevel;
            $record->matchesai = $matchesai;
            $record->reviewerid = $reviewerid;
            $record->timemodified = $now;

            if (empty($record->id)) {
                $record->id = $DB->insert_record(self::TABLE, $record);
            } else {
                $DB->update_record(self::TABLE, $record);
            }
            $summary['saved']++;
        }

        $transaction->allow_commit();
        return $summary;
    }

    /**
     * Return final human criterion reviews for a grade.
     *
     * @param int $gradeid Grade id.
     * @return \stdClass[] Indexed by criterion key.
     */
    public static function get_for_grade(int $gradeid): array {
        global $DB;

        $records = $DB->get_records(self::TABLE, ['grade' => $gradeid], 'criterionkey ASC');
        $indexed = [];
        foreach ($records as $record) {
            $indexed[$record->criterionkey] = $record;
        }

        return $indexed;
    }

    /**
     * Return the latest completed assessment job for a grade.
     *
     * @param int $assignmentid Assignment id.
     * @param int $gradeid Grade id.
     * @return \stdClass|false
     */
    public static function get_latest_completed_job(int $assignmentid, int $gradeid) {
        global $DB;

        return $DB->get_record_sql(
            "SELECT *
               FROM {assignfeedback_aitutoria_job}
              WHERE assignment = :assignment
                AND grade = :grade
                AND status = :status
           ORDER BY timemodified DESC, id DESC",
            [
                'assignment' => $assignmentid,
                'grade' => $gradeid,
                'status' => job_repository::STATUS_COMPLETE,
            ],
            IGNORE_MULTIPLE
        );
    }
}
