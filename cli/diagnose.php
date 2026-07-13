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

/**
 * Diagnose the installed AI Tutoring feedback plugin.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'json' => false,
    ],
    [
        'h' => 'help',
        'j' => 'json',
    ]
);

if (!empty($unrecognized)) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("Unknown options:\n  {$unrecognized}");
}

if ($options['help']) {
    $help = <<<HELP
Diagnose the installed AI Tutoring assignment feedback plugin.

Options:
-h, --help   Show this help.
-j, --json   Emit machine-readable JSON.

Example:
php mod/assign/feedback/aitutoria/cli/diagnose.php --json
HELP;
    echo $help . PHP_EOL;
    exit(0);
}

$component = 'assignfeedback_aitutoria';
$pluginman = core_plugin_manager::instance();
$plugininfo = $pluginman->get_plugin_info($component);
$dbman = $DB->get_manager();
$requiredtables = [
    'assignfeedback_aitutoria',
    'assignfeedback_aitutoria_job',
    'assignfeedback_aitutoria_snp',
    'assignfeedback_aitutoria_crt',
    'assignfeedback_aitutoria_aud',
];
$tablechecks = [];
$critical = [];

foreach ($requiredtables as $tablename) {
    $exists = $dbman->table_exists(new xmldb_table($tablename));
    $tablechecks[$tablename] = $exists;
    if (!$exists) {
        $critical[] = "Missing database table: {$tablename}";
    }
}

if (!$plugininfo) {
    $critical[] = 'Plugin is not registered by Moodle.';
}

$scheduledtask = core\task\manager::get_scheduled_task(
    assignfeedback_aitutoria\task\cleanup_assessment_data::class
);
$providers = assignfeedback_aitutoria\local\provider\provider_registry::available();
$jobcounts = [];
if ($tablechecks['assignfeedback_aitutoria_job']) {
    foreach (['queued', 'processing', 'complete', 'failed'] as $status) {
        $jobcounts[$status] = $DB->count_records('assignfeedback_aitutoria_job', ['status' => $status]);
    }
}

$report = [
    'status' => empty($critical) ? 'ok' : 'critical',
    'component' => $component,
    'pluginversiondisk' => $plugininfo ? $plugininfo->versiondisk : null,
    'pluginversiondb' => get_config($component, 'version'),
    'release' => $plugininfo ? $plugininfo->release : null,
    'moodleversion' => $CFG->version,
    'moodlerelease' => $CFG->release,
    'phpversion' => PHP_VERSION,
    'dbfamily' => $DB->get_dbfamily(),
    'tables' => $tablechecks,
    'settings' => [
        'defaultenabled' => (bool) get_config($component, 'default'),
        'allowaisuggestions' => (bool) get_config($component, 'allowaisuggestions'),
        'retentiondays' => (int) get_config($component, 'retentiondays'),
    ],
    'productionproviders' => $providers,
    'scheduledretentiontask' => $scheduledtask !== false,
    'jobcounts' => $jobcounts,
    'critical' => $critical,
];

if ($options['json']) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} else {
    mtrace('AI Tutoring feedback diagnostic');
    mtrace('Status: ' . strtoupper($report['status']));
    mtrace('Release: ' . ($report['release'] ?? 'not registered'));
    mtrace('Moodle: ' . $report['moodlerelease']);
    mtrace('PHP: ' . $report['phpversion']);
    mtrace('Database family: ' . $report['dbfamily']);
    mtrace('AI suggestions enabled: ' . ($report['settings']['allowaisuggestions'] ? 'yes' : 'no'));
    mtrace('Retention days: ' . $report['settings']['retentiondays']);
    mtrace('Production providers: ' . (empty($providers) ? 'none' : implode(', ', array_keys($providers))));
    foreach ($tablechecks as $tablename => $exists) {
        mtrace("Table {$tablename}: " . ($exists ? 'ok' : 'missing'));
    }
    foreach ($jobcounts as $status => $count) {
        mtrace("Jobs {$status}: {$count}");
    }
    foreach ($critical as $message) {
        mtrace("CRITICAL: {$message}");
    }
}

exit(empty($critical) ? 0 : 1);
