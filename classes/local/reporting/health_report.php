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

use assignfeedback_aitutoria\local\error_sanitizer;
use assignfeedback_aitutoria\local\institutional_framework_parser;
use assignfeedback_aitutoria\local\provider\provider_registry;

/**
 * Build privacy-preserving operational health diagnostics.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class health_report {
    /** Required database tables. */
    private const TABLES = [
        'assignfeedback_aitutoria',
        'assignfeedback_aitutoria_job',
        'assignfeedback_aitutoria_snp',
        'assignfeedback_aitutoria_crt',
        'assignfeedback_aitutoria_hcr',
        'assignfeedback_aitutoria_aud',
    ];

    /**
     * Build the current site-level health report.
     *
     * @return array
     */
    public static function build(): array {
        global $DB;

        $dbman = $DB->get_manager();
        $tables = [];
        $issues = [];
        foreach (self::TABLES as $tablename) {
            $exists = $dbman->table_exists(new \xmldb_table($tablename));
            $tables[$tablename] = $exists;
            if (!$exists) {
                $issues[] = [
                    'severity' => 'critical',
                    'code' => 'missing_table',
                    'message' => 'Missing database table: ' . $tablename,
                ];
            }
        }

        $jobcounts = ['queued' => 0, 'processing' => 0, 'complete' => 0, 'failed' => 0];
        $staleprocessing = 0;
        $overduequeued = 0;
        $recentfailures = [];
        $timeoutminutes = (int) get_config('assignfeedback_aitutoria', 'processingtimeoutminutes');
        if ($timeoutminutes < 1) {
            $timeoutminutes = 15;
        }
        if ($tables['assignfeedback_aitutoria_job']) {
            foreach (array_keys($jobcounts) as $status) {
                $jobcounts[$status] = $DB->count_records('assignfeedback_aitutoria_job', ['status' => $status]);
            }
            $staleprocessing = $DB->count_records_select(
                'assignfeedback_aitutoria_job',
                'status = :status AND timemodified < :cutoff',
                ['status' => 'processing', 'cutoff' => time() - ($timeoutminutes * MINSECS)]
            );
            $overduequeued = $DB->count_records_select(
                'assignfeedback_aitutoria_job',
                'status = :status AND timeavailable > 0 AND timeavailable < :now',
                ['status' => 'queued', 'now' => time()]
            );
            $failures = $DB->get_records(
                'assignfeedback_aitutoria_job',
                ['status' => 'failed'],
                'timemodified DESC',
                'id,assignment,provider,model,attempts,maxattempts,lasterror,timemodified',
                0,
                10
            );
            foreach ($failures as $failure) {
                $recentfailures[] = [
                    'jobid' => (int) $failure->id,
                    'assignmentid' => (int) $failure->assignment,
                    'provider' => (string) $failure->provider,
                    'model' => (string) ($failure->model ?? ''),
                    'attempts' => (int) $failure->attempts,
                    'maxattempts' => (int) $failure->maxattempts,
                    'error' => error_sanitizer::sanitize((string) ($failure->lasterror ?? '')),
                    'timemodified' => (int) $failure->timemodified,
                ];
            }
        }

        if ($staleprocessing > 0) {
            $issues[] = [
                'severity' => 'warning',
                'code' => 'stale_processing_jobs',
                'message' => $staleprocessing . ' processing job(s) exceeded the configured timeout.',
            ];
        }
        if ($overduequeued > 0) {
            $issues[] = [
                'severity' => 'warning',
                'code' => 'overdue_queued_jobs',
                'message' => $overduequeued . ' queued job(s) are overdue for processing.',
            ];
        }

        $frameworkstatus = self::framework_status();
        if ($frameworkstatus['configured'] && !$frameworkstatus['valid']) {
            $issues[] = [
                'severity' => 'critical',
                'code' => 'invalid_institutional_framework',
                'message' => $frameworkstatus['error'],
            ];
        }

        $providers = provider_registry::available();
        if (empty($providers)) {
            $issues[] = [
                'severity' => 'info',
                'code' => 'no_production_provider',
                'message' => 'No production assessment provider is enabled.',
            ];
        }

        $critical = count(array_filter(
            $issues,
            static fn(array $issue): bool => $issue['severity'] === 'critical'
        ));
        $warnings = count(array_filter(
            $issues,
            static fn(array $issue): bool => $issue['severity'] === 'warning'
        ));

        return [
            'status' => $critical > 0 ? 'critical' : ($warnings > 0 ? 'warning' : 'ok'),
            'tables' => $tables,
            'settings' => [
                'defaultenabled' => (bool) get_config('assignfeedback_aitutoria', 'default'),
                'allowaisuggestions' => (bool) get_config('assignfeedback_aitutoria', 'allowaisuggestions'),
                'retentiondays' => (int) get_config('assignfeedback_aitutoria', 'retentiondays'),
                'processingtimeoutminutes' => $timeoutminutes,
            ],
            'framework' => $frameworkstatus,
            'providers' => $providers,
            'jobs' => [
                'status' => $jobcounts,
                'staleprocessing' => $staleprocessing,
                'overduequeued' => $overduequeued,
                'recentfailures' => $recentfailures,
            ],
            'issues' => $issues,
        ];
    }

    /**
     * Inspect the configured institutional framework.
     *
     * @return array
     */
    private static function framework_status(): array {
        $json = trim((string) get_config('assignfeedback_aitutoria', 'institutionalframeworkjson'));
        if ($json === '') {
            return [
                'configured' => false,
                'valid' => true,
                'version' => null,
                'competencycount' => 0,
                'error' => null,
            ];
        }
        try {
            $framework = institutional_framework_parser::parse($json);
            return [
                'configured' => true,
                'valid' => true,
                'version' => $framework['version'],
                'competencycount' => count($framework['competencies']),
                'error' => null,
            ];
        } catch (\invalid_parameter_exception $exception) {
            return [
                'configured' => true,
                'valid' => false,
                'version' => null,
                'competencycount' => 0,
                'error' => error_sanitizer::sanitize($exception->getMessage()),
            ];
        }
    }
}
