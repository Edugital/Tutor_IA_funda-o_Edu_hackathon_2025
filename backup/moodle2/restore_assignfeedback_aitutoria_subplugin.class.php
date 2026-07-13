<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Restore support for AI Tutoring assignment feedback.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Restores grade-level feedback records.
 */
class restore_assignfeedback_aitutoria_subplugin extends restore_subplugin {

    /**
     * Define restore paths.
     *
     * @return restore_path_element[]
     */
    protected function define_grade_subplugin_structure() {
        $name = $this->get_namefor('grade');
        $path = $this->get_pathfor('/feedback_aitutoria');

        return [new restore_path_element($name, $path)];
    }

    /**
     * Restore one feedback record.
     *
     * @param mixed $data Restored XML data.
     */
    public function process_assignfeedback_aitutoria_grade($data) {
        global $DB;

        $data = (object) $data;
        $data->assignment = $this->get_new_parentid('assign');
        $data->grade = $this->get_mappingid('grade', $data->grade);

        if (empty($data->grade)) {
            return;
        }

        unset($data->id);
        $DB->insert_record('assignfeedback_aitut_fb', $data);
    }
}
