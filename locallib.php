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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Assignment feedback implementation for AI Tutoring.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignfeedback_aitutoria\local\feedback_repository;

/**
 * Human-in-control assignment feedback plugin.
 *
 * The recovery baseline publishes only feedback explicitly saved by a human
 * grader. AI suggestions, when enabled and produced by a future provider
 * layer, remain unpublished until accepted or edited by the grader.
 */
class assign_feedback_aitutoria extends assign_feedback_plugin {
    /**
     * Plugin display name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'assignfeedback_aitutoria');
    }

    /**
     * Add assignment-level settings.
     *
     * @param MoodleQuickForm $mform Assignment settings form.
     */
    public function get_settings(MoodleQuickForm $mform) {
        $rubric = $this->get_config('rubric');
        if ($rubric === false) {
            $rubric = '';
        }

        $rubricversion = $this->get_config('rubricversion');
        if ($rubricversion === false) {
            $rubricversion = '';
        }

        $mform->addElement(
            'textarea',
            'assignfeedback_aitutoria_rubric',
            get_string('rubric', 'assignfeedback_aitutoria'),
            ['rows' => 12, 'cols' => 80]
        );
        $mform->addHelpButton(
            'assignfeedback_aitutoria_rubric',
            'rubric',
            'assignfeedback_aitutoria'
        );
        $mform->setType('assignfeedback_aitutoria_rubric', PARAM_RAW);
        $mform->setDefault('assignfeedback_aitutoria_rubric', $rubric);
        $mform->hideIf(
            'assignfeedback_aitutoria_rubric',
            'assignfeedback_aitutoria_enabled',
            'notchecked'
        );

        $mform->addElement(
            'text',
            'assignfeedback_aitutoria_rubricversion',
            get_string('rubricversion', 'assignfeedback_aitutoria')
        );
        $mform->addHelpButton(
            'assignfeedback_aitutoria_rubricversion',
            'rubricversion',
            'assignfeedback_aitutoria'
        );
        $mform->setType('assignfeedback_aitutoria_rubricversion', PARAM_TEXT);
        $mform->setDefault('assignfeedback_aitutoria_rubricversion', $rubricversion);
        $mform->hideIf(
            'assignfeedback_aitutoria_rubricversion',
            'assignfeedback_aitutoria_enabled',
            'notchecked'
        );

        $mform->addElement(
            'static',
            'assignfeedback_aitutoria_hicnotice',
            get_string('humancontrol', 'assignfeedback_aitutoria'),
            get_string('humancontrol_help', 'assignfeedback_aitutoria')
        );
        $mform->hideIf(
            'assignfeedback_aitutoria_hicnotice',
            'assignfeedback_aitutoria_enabled',
            'notchecked'
        );
    }

    /**
     * Save assignment-level settings.
     *
     * @param stdClass $data Submitted settings.
     * @return bool
     */
    public function save_settings(stdClass $data) {
        $rubric = isset($data->assignfeedback_aitutoria_rubric)
            ? trim((string) $data->assignfeedback_aitutoria_rubric)
            : '';
        $rubricversion = isset($data->assignfeedback_aitutoria_rubricversion)
            ? clean_param($data->assignfeedback_aitutoria_rubricversion, PARAM_TEXT)
            : '';

        $this->set_config('rubric', $rubric);
        $this->set_config('rubricversion', $rubricversion);
        $this->set_config('mode', 'human_review');

        return true;
    }

    /**
     * Add fields to the grading form.
     *
     * @param stdClass|null $grade Grade record.
     * @param MoodleQuickForm $mform Grading form.
     * @param stdClass $data Form data.
     * @param int $userid Student user id.
     * @return bool
     */
    public function get_form_elements_for_user($grade, MoodleQuickForm $mform, stdClass $data, $userid) {
        if (!$grade) {
            return false;
        }

        $record = feedback_repository::get_by_grade((int) $grade->id);
        $rubric = (string) ($this->get_config('rubric') ?: '');

        $mform->addElement('header', 'assignfeedback_aitutoria_header', $this->get_name());

        if ($rubric !== '') {
            $mform->addElement(
                'static',
                'assignfeedback_aitutoria_rubric_display',
                get_string('rubric', 'assignfeedback_aitutoria'),
                format_text($rubric, FORMAT_PLAIN, ['context' => $this->assignment->get_context()])
            );
        }

        if ($record && $record->aistatus === 'ready' && trim((string) $record->aisuggestion) !== '') {
            $mform->addElement(
                'static',
                'assignfeedback_aitutoria_suggestion_display',
                get_string('aisuggestion', 'assignfeedback_aitutoria'),
                format_text(
                    $record->aisuggestion,
                    FORMAT_PLAIN,
                    ['context' => $this->assignment->get_context()]
                )
            );
            $mform->addElement(
                'advcheckbox',
                'assignfeedback_aitutoria_acceptsuggestion',
                get_string('acceptsuggestion', 'assignfeedback_aitutoria')
            );
            $mform->addHelpButton(
                'assignfeedback_aitutoria_acceptsuggestion',
                'acceptsuggestion',
                'assignfeedback_aitutoria'
            );
        }

        $data->assignfeedback_aitutoria_feedback = $record ? (string) $record->feedbacktext : '';

        $mform->addElement(
            'textarea',
            'assignfeedback_aitutoria_feedback',
            get_string('feedback', 'assignfeedback_aitutoria'),
            ['rows' => 12, 'cols' => 80]
        );
        $mform->setType('assignfeedback_aitutoria_feedback', PARAM_RAW);
        $mform->setDefault(
            'assignfeedback_aitutoria_feedback',
            $record ? (string) $record->feedbacktext : ''
        );
        $mform->addHelpButton(
            'assignfeedback_aitutoria_feedback',
            'feedback',
            'assignfeedback_aitutoria'
        );

        $mform->addElement(
            'static',
            'assignfeedback_aitutoria_review_notice',
            get_string('publicationcontrol', 'assignfeedback_aitutoria'),
            get_string('publicationcontrol_help', 'assignfeedback_aitutoria')
        );

        return true;
    }

    /**
     * Save feedback after explicit human review.
     *
     * @param stdClass $grade Grade record.
     * @param stdClass $data Submitted grading data.
     * @return bool
     */
    public function save(stdClass $grade, stdClass $data) {
        $feedback = isset($data->assignfeedback_aitutoria_feedback)
            ? (string) $data->assignfeedback_aitutoria_feedback
            : '';
        $acceptsuggestion = !empty($data->assignfeedback_aitutoria_acceptsuggestion);

        feedback_repository::save_human_feedback(
            (int) $this->assignment->get_instance()->id,
            (int) $grade->id,
            $feedback,
            $acceptsuggestion
        );

        return true;
    }

    /**
     * Determine whether submitted feedback differs from stored feedback.
     *
     * @param stdClass $grade Grade record.
     * @param stdClass $data Submitted data.
     * @return bool
     */
    public function is_feedback_modified(stdClass $grade, stdClass $data) {
        $record = feedback_repository::get_by_grade((int) $grade->id);
        $stored = $record ? trim((string) $record->feedbacktext) : '';
        $submitted = isset($data->assignfeedback_aitutoria_feedback)
            ? trim((string) $data->assignfeedback_aitutoria_feedback)
            : '';

        return $stored !== $submitted || !empty($data->assignfeedback_aitutoria_acceptsuggestion);
    }

    /**
     * Short feedback for grading tables.
     *
     * @param stdClass $grade Grade record.
     * @param bool $showviewlink Whether Moodle should render a full-view link.
     * @return string
     */
    public function view_summary(stdClass $grade, &$showviewlink) {
        $full = $this->view($grade);
        $short = shorten_text($full, 140);
        $showviewlink = $short !== $full;

        return $short;
    }

    /**
     * Full feedback shown to the student.
     *
     * @param stdClass $grade Grade record.
     * @return string
     */
    public function view(stdClass $grade) {
        $record = feedback_repository::get_by_grade((int) $grade->id);
        if (!$record || trim((string) $record->feedbacktext) === '') {
            return '';
        }

        return format_text(
            $record->feedbacktext,
            (int) $record->feedbackformat,
            ['context' => $this->assignment->get_context()]
        );
    }

    /**
     * Feedback format for gradebook synchronization.
     *
     * @param stdClass $grade Grade record.
     * @return int
     */
    public function format_for_gradebook(stdClass $grade) {
        return FORMAT_PLAIN;
    }

    /**
     * Feedback text for gradebook synchronization.
     *
     * @param stdClass $grade Grade record.
     * @return string
     */
    public function text_for_gradebook(stdClass $grade) {
        $record = feedback_repository::get_by_grade((int) $grade->id);

        return $record ? (string) $record->feedbacktext : '';
    }

    /**
     * Delete all plugin data for the assignment.
     *
     * @return bool
     */
    public function delete_instance() {
        feedback_repository::delete_for_assignment((int) $this->assignment->get_instance()->id);

        return true;
    }

    /**
     * Whether a grade has no published feedback.
     *
     * @param stdClass $grade Grade record.
     * @return bool
     */
    public function is_empty(stdClass $grade) {
        return $this->view($grade) === '';
    }

    /**
     * Configuration exposed through assignment external functions.
     *
     * @return array
     */
    public function get_config_for_external() {
        return (array) $this->get_config();
    }
}
