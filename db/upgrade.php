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
 * Upgrade steps for AI Tutoring feedback.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the plugin while preserving known legacy configuration.
 *
 * @param int $oldversion Previously installed plugin version.
 * @return bool
 */
function xmldb_assignfeedback_aitutoria_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026071300) {
        $table = new xmldb_table('assignfeedback_aitutoria');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('assignment', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('grade', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('feedbacktext', XMLDB_TYPE_TEXT, null, null, null);
            $table->add_field('feedbackformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('aisuggestion', XMLDB_TYPE_TEXT, null, null, null);
            $table->add_field('aistatus', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'not_requested');
            $table->add_field('decision', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'manual');
            $table->add_field('model', XMLDB_TYPE_CHAR, '100', null, null);
            $table->add_field('promptversion', XMLDB_TYPE_CHAR, '50', null, null);
            $table->add_field('rubricversion', XMLDB_TYPE_CHAR, '50', null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('assignment', XMLDB_KEY_FOREIGN, ['assignment'], 'assign', ['id']);
            $table->add_key('grade', XMLDB_KEY_FOREIGN_UNIQUE, ['grade'], 'assign_grades', ['id']);
            $table->add_index('aistatus', XMLDB_INDEX_NOTUNIQUE, ['aistatus']);

            $dbman->create_table($table);
        }

        $legacytable = new xmldb_table('assignfeedback_aitut_cfg');
        if ($dbman->table_exists($legacytable)) {
            $records = $DB->get_records('assignfeedback_aitut_cfg');

            foreach ($records as $record) {
                $assignmentid = (int) $record->assignment;
                $mapping = [
                    'rubric' => property_exists($record, 'rubric') ? (string) $record->rubric : '',
                    'enablehistory' => property_exists($record, 'enablehistory') ? (string) $record->enablehistory : '1',
                    'enablepdf' => property_exists($record, 'enablepdf') ? (string) $record->enablepdf : '0',
                    'mode' => 'human_review',
                ];

                if (!empty($record->autograde)) {
                    $mapping['legacy_autograde_was_enabled'] = '1';
                }

                foreach ($mapping as $name => $value) {
                    $conditions = [
                        'assignment' => $assignmentid,
                        'plugin' => 'aitutoria',
                        'subtype' => 'assignfeedback',
                        'name' => $name,
                    ];

                    if (!$DB->record_exists('assign_plugin_config', $conditions)) {
                        $config = (object) ($conditions + ['value' => $value]);
                        $DB->insert_record('assign_plugin_config', $config);
                    }
                }
            }
        }

        upgrade_plugin_savepoint(true, 2026071300, 'assignfeedback', 'aitutoria');
    }

    if ($oldversion < 2026071301) {
        $legacyrecoverytable = new xmldb_table('assignfeedback_aitut_fb');
        $canonicaltable = new xmldb_table('assignfeedback_aitutoria');

        if ($dbman->table_exists($legacyrecoverytable) && !$dbman->table_exists($canonicaltable)) {
            $dbman->rename_table($legacyrecoverytable, 'assignfeedback_aitutoria');
        }

        upgrade_plugin_savepoint(true, 2026071301, 'assignfeedback', 'aitutoria');
    }

    if ($oldversion < 2026071302) {
        $jobtable = new xmldb_table('assignfeedback_aitutoria_job');
        if (!$dbman->table_exists($jobtable)) {
            $jobtable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $jobtable->add_field('assignment', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $jobtable->add_field('grade', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $jobtable->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'queued');
            $jobtable->add_field('idempotencykey', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
            $jobtable->add_field('provider', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
            $jobtable->add_field('model', XMLDB_TYPE_CHAR, '100', null, null);
            $jobtable->add_field('promptversion', XMLDB_TYPE_CHAR, '50', null, null);
            $jobtable->add_field('suggestiontext', XMLDB_TYPE_TEXT, null, null, null);
            $jobtable->add_field('scoringjson', XMLDB_TYPE_TEXT, null, null, null);
            $jobtable->add_field('attempts', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
            $jobtable->add_field('maxattempts', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '3');
            $jobtable->add_field('lasterror', XMLDB_TYPE_TEXT, null, null, null);
            $jobtable->add_field('timeavailable', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $jobtable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $jobtable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $jobtable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $jobtable->add_key('assignment', XMLDB_KEY_FOREIGN, ['assignment'], 'assign', ['id']);
            $jobtable->add_key('grade', XMLDB_KEY_FOREIGN, ['grade'], 'assign_grades', ['id']);
            $jobtable->add_index('idempotencykey', XMLDB_INDEX_UNIQUE, ['idempotencykey']);
            $jobtable->add_index('statusavailable', XMLDB_INDEX_NOTUNIQUE, ['status', 'timeavailable']);
            $jobtable->add_index('assignmentgrade', XMLDB_INDEX_NOTUNIQUE, ['assignment', 'grade']);
            $dbman->create_table($jobtable);
        }

        $snapshottable = new xmldb_table('assignfeedback_aitutoria_snp');
        if (!$dbman->table_exists($snapshottable)) {
            $snapshottable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $snapshottable->add_field('jobid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $snapshottable->add_field('submissionhash', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
            $snapshottable->add_field('submissiontext', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $snapshottable->add_field('rubricjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $snapshottable->add_field('rubricversion', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);
            $snapshottable->add_field('policyjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $snapshottable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $snapshottable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $snapshottable->add_key(
                'jobid',
                XMLDB_KEY_FOREIGN_UNIQUE,
                ['jobid'],
                'assignfeedback_aitutoria_job',
                ['id']
            );
            $dbman->create_table($snapshottable);
        }

        $criteriontable = new xmldb_table('assignfeedback_aitutoria_crt');
        if (!$dbman->table_exists($criteriontable)) {
            $criteriontable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $criteriontable->add_field('jobid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $criteriontable->add_field('criterionkey', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
            $criteriontable->add_field('proposedlevel', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
            $criteriontable->add_field('rationale', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $criteriontable->add_field('evidencejson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $criteriontable->add_field('uncertainty', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'high');
            $criteriontable->add_field('requireshuman', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $criteriontable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $criteriontable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $criteriontable->add_key(
                'jobid',
                XMLDB_KEY_FOREIGN,
                ['jobid'],
                'assignfeedback_aitutoria_job',
                ['id']
            );
            $criteriontable->add_index('jobcriterion', XMLDB_INDEX_UNIQUE, ['jobid', 'criterionkey']);
            $dbman->create_table($criteriontable);
        }

        $audittable = new xmldb_table('assignfeedback_aitutoria_aud');
        if (!$dbman->table_exists($audittable)) {
            $audittable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $audittable->add_field('assignment', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $audittable->add_field('grade', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $audittable->add_field('jobid', XMLDB_TYPE_INTEGER, '10', null, null);
            $audittable->add_field('action', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);
            $audittable->add_field('actorid', XMLDB_TYPE_INTEGER, '10', null, null);
            $audittable->add_field('payloadjson', XMLDB_TYPE_TEXT, null, null, null);
            $audittable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $audittable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $audittable->add_key('assignment', XMLDB_KEY_FOREIGN, ['assignment'], 'assign', ['id']);
            $audittable->add_key('grade', XMLDB_KEY_FOREIGN, ['grade'], 'assign_grades', ['id']);
            $audittable->add_key(
                'jobid',
                XMLDB_KEY_FOREIGN,
                ['jobid'],
                'assignfeedback_aitutoria_job',
                ['id']
            );
            $audittable->add_index('assignmentgrade', XMLDB_INDEX_NOTUNIQUE, ['assignment', 'grade']);
            $audittable->add_index('action', XMLDB_INDEX_NOTUNIQUE, ['action']);
            $dbman->create_table($audittable);
        }

        upgrade_plugin_savepoint(true, 2026071302, 'assignfeedback', 'aitutoria');
    }

    return true;
}
