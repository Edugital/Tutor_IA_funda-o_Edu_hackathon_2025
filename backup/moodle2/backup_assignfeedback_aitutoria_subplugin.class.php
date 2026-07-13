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
 * Backup support for AI Tutoring assignment feedback.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines grade-level backup data for the subplugin.
 */
class backup_assignfeedback_aitutoria_subplugin extends backup_subplugin {
    /**
     * Define feedback records attached to an assignment grade.
     *
     * @return backup_subplugin_element
     */
    protected function define_grade_subplugin_structure() {
        $subplugin = $this->get_subplugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $record = new backup_nested_element(
            'feedback_aitutoria',
            null,
            [
                'grade',
                'feedbacktext',
                'feedbackformat',
                'aisuggestion',
                'aistatus',
                'decision',
                'model',
                'promptversion',
                'rubricversion',
                'timecreated',
                'timemodified',
            ]
        );

        $subplugin->add_child($wrapper);
        $wrapper->add_child($record);

        $record->set_source_table(
            'assignfeedback_aitutoria',
            ['grade' => backup::VAR_PARENTID]
        );

        return $subplugin;
    }
}
