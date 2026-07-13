<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Upgrade steps for AI Tutoring feedback.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

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
        // Create a new canonical table instead of assuming the shape of any
        // feedback table that may have existed only on the legacy server.
        $table = new xmldb_table('assignfeedback_aitut_fb');

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

        // Migrate the only legacy table whose schema is documented. Keep the
        // source table untouched until a production migration is verified.
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
                    // Preserve evidence that autograde had been configured,
                    // without enabling it in the recovery implementation.
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

    return true;
}
