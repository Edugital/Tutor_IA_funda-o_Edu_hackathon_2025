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
 * Assignment feedback implementation for AI Tutoring.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignfeedback_aitutoria\local\assessment_service;
use assignfeedback_aitutoria\local\assignment_policy;
use assignfeedback_aitutoria\local\decision_policy;
use assignfeedback_aitutoria\local\feedback_repository;
use assignfeedback_aitutoria\local\request_factory;
use assignfeedback_aitutoria\local\repository\audit_repository;
use assignfeedback_aitutoria\local\repository\human_criterion_repository;

/**
 * Human-in-control assignment feedback plugin.
 *
 * AI suggestions remain unpublished until a human accepts, edits, rejects,
 * or escalates them. This plugin never writes a numeric Moodle grade.
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
        $structuredrubric = $this->get_config('structuredrubric');
        if ($structuredrubric === false) {
            $structuredrubric = '';
        }
        $assessmentmode = $this->get_config('assessmentmode');
        if ($assessmentmode === false) {
            $assessmentmode = assignment_policy::MODE_DISABLED;
        }

        $mform->addElement(
            'select',
            'assignfeedback_aitutoria_assessmentmode',
            get_string('assessmentmode', 'assignfeedback_aitutoria'),
            [
                assignment_policy::MODE_DISABLED => get_string(
                    'assessmentmode_disabled',
                    'assignfeedback_aitutoria'
                ),
                assignment_policy::MODE_SHADOW => get_string(
                    'assessmentmode_shadow',
                    'assignfeedback_aitutoria'
                ),
                assignment_policy::MODE_ASSISTIVE => get_string(
                    'assessmentmode_assistive',
                    'assignfeedback_aitutoria'
                ),
            ]
        );
        $mform->addHelpButton(
            'assignfeedback_aitutoria_assessmentmode',
            'assessmentmode',
            'assignfeedback_aitutoria'
        );
        $mform->setDefault('assignfeedback_aitutoria_assessmentmode', $assessmentmode);
        $mform->hideIf(
            'assignfeedback_aitutoria_assessmentmode',
            'assignfeedback_aitutoria_enabled',
            'notchecked'
        );

        $mform->addElement(
            'textarea',
            'assignfeedback_aitutoria_rubric',
            get_string('rubric', 'assignfeedback_aitutoria'),
            ['rows' => 8, 'cols' => 80]
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
            'textarea',
            'assignfeedback_aitutoria_structuredrubric',
            get_string('structuredrubric', 'assignfeedback_aitutoria'),
            ['rows' => 18, 'cols' => 100]
        );
        $mform->addHelpButton(
            'assignfeedback_aitutoria_structuredrubric',
            'structuredrubric',
            'assignfeedback_aitutoria'
        );
        $mform->setType('assignfeedback_aitutoria_structuredrubric', PARAM_RAW);
        $mform->setDefault('assignfeedback_aitutoria_structuredrubric', $structuredrubric);
        $mform->hideIf(
            'assignfeedback_aitutoria_structuredrubric',
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
        $assessmentmode = isset($data->assignfeedback_aitutoria_assessmentmode)
            ? assignment_policy::normalize_mode((string) $data->assignfeedback_aitutoria_assessmentmode)
            : assignment_policy::MODE_DISABLED;
        $structuredrubric = isset($data->assignfeedback_aitutoria_structuredrubric)
            ? trim((string) $data->assignfeedback_aitutoria_structuredrubric)
            : '';

        if ($structuredrubric !== '') {
            try {
                $structuredrubric = assignment_policy::canonical_rubric_json(
                    $structuredrubric,
                    (string) get_config('assignfeedback_aitutoria', 'institutionalframeworkjson')
                );
                $parsedrubric = assignment_policy::parse_rubric(
                    $structuredrubric,
                    (string) get_config('assignfeedback_aitutoria', 'institutionalframeworkjson')
                );
                if ($rubricversion === '') {
                    $rubricversion = $parsedrubric['version'];
                }
            } catch (invalid_parameter_exception $exception) {
                throw new moodle_exception(
                    'invalidstructuredrubric',
                    'assignfeedback_aitutoria',
                    '',
                    $exception->getMessage()
                );
            }
        }

        $this->set_config('rubric', $rubric);
        $this->set_config('structuredrubric', $structuredrubric);
        $this->set_config('rubricversion', $rubricversion);
        $this->set_config('assessmentmode', $assessmentmode);
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
        $structuredrubric = $this->get_structured_rubric();
        $humanreviews = human_criterion_repository::get_for_grade((int) $grade->id);

        $mform->addElement('header', 'assignfeedback_aitutoria_header', $this->get_name());
        if (
            get_config('assignfeedback_aitutoria', 'allowaisuggestions')
            && $this->get_config('assessmentmode') !== assignment_policy::MODE_DISABLED
        ) {
            $generateurl = new moodle_url('/mod/assign/feedback/aitutoria/generate.php', [
                'id' => $this->assignment->get_course_module()->id,
                'userid' => $userid,
                'confirm' => 1,
                'sesskey' => sesskey(),
            ]);
            $mform->addElement(
                'static',
                'assignfeedback_aitutoria_generate_link',
                get_string('generatesuggestion', 'assignfeedback_aitutoria'),
                html_writer::link(
                    $generateurl,
                    get_string('generatesuggestionbutton', 'assignfeedback_aitutoria'),
                    ['class' => 'btn btn-secondary']
                ) . html_writer::div(
                    get_string('generatesuggestion_help', 'assignfeedback_aitutoria'),
                    'form-text text-muted mt-1'
                )
            );
            $mform->addElement(
                'advcheckbox',
                'assignfeedback_aitutoria_requestai',
                get_string('requestaisuggestion', 'assignfeedback_aitutoria'),
                get_string('requestaisuggestion_help', 'assignfeedback_aitutoria'),
                [],
                [0, 1]
            );
            $mform->setDefault('assignfeedback_aitutoria_requestai', 0);
        }
        if ($rubric !== '') {
            $mform->addElement(
                'static',
                'assignfeedback_aitutoria_rubric_display',
                get_string('rubric', 'assignfeedback_aitutoria'),
                format_text($rubric, FORMAT_PLAIN, ['context' => $this->assignment->get_context()])
            );
        }
        if ($structuredrubric !== null) {
            $mform->addElement(
                'static',
                'assignfeedback_aitutoria_structuredrubric_display',
                get_string('structuredrubric', 'assignfeedback_aitutoria'),
                $this->render_structured_rubric($structuredrubric)
            );
            $mform->addElement(
                'static',
                'assignfeedback_aitutoria_criterionreview_notice',
                get_string('criterionreview', 'assignfeedback_aitutoria'),
                get_string('criterionreview_help', 'assignfeedback_aitutoria')
            );
            foreach ($structuredrubric['criteria'] as $criterion) {
                $fieldname = self::criterion_field_name($criterion['id']);
                $options = ['' => get_string('criterion_notassessed', 'assignfeedback_aitutoria')];
                foreach ($criterion['levels'] as $level) {
                    $options[$level['id']] = $level['label'];
                }
                $mform->addElement('select', $fieldname, $criterion['title'], $options);
                $default = isset($humanreviews[$criterion['id']])
                    ? (string) $humanreviews[$criterion['id']]->selectedlevel
                    : '';
                $mform->setDefault($fieldname, $default);
            }
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
                'select',
                'assignfeedback_aitutoria_reviewaction',
                get_string('reviewaction', 'assignfeedback_aitutoria'),
                [
                    decision_policy::ACTION_MANUAL => get_string('reviewaction_manual', 'assignfeedback_aitutoria'),
                    decision_policy::ACTION_ACCEPT => get_string('reviewaction_accept', 'assignfeedback_aitutoria'),
                    decision_policy::ACTION_REJECT => get_string('reviewaction_reject', 'assignfeedback_aitutoria'),
                    decision_policy::ACTION_ESCALATE => get_string('reviewaction_escalate', 'assignfeedback_aitutoria'),
                ]
            );
            $mform->addHelpButton(
                'assignfeedback_aitutoria_reviewaction',
                'reviewaction',
                'assignfeedback_aitutoria'
            );
            $mform->setDefault('assignfeedback_aitutoria_reviewaction', decision_policy::ACTION_MANUAL);
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
        global $USER;

        $feedback = isset($data->assignfeedback_aitutoria_feedback)
            ? (string) $data->assignfeedback_aitutoria_feedback
            : '';
        $acceptsuggestion = !empty($data->assignfeedback_aitutoria_acceptsuggestion);
        $reviewaction = isset($data->assignfeedback_aitutoria_reviewaction)
            ? clean_param($data->assignfeedback_aitutoria_reviewaction, PARAM_ALPHA)
            : '';
        $assignmentid = (int) $this->assignment->get_instance()->id;
        $userid = (int) $grade->userid;

        if (
            !empty($data->assignfeedback_aitutoria_requestai)
            && get_config('assignfeedback_aitutoria', 'allowaisuggestions')
        ) {
            try {
                $built = request_factory::from_user_submission($assignmentid, $userid);
                $queued = assessment_service::queue($built['request'], $built['provider'], (int) $USER->id, false);
                assessment_service::execute((int) $queued['job']->id, $built['provider']);
            } catch (\Throwable $exception) {
                debugging('aitutoria requestai failed: ' . $exception->getMessage(), DEBUG_DEVELOPER);
                throw new \moodle_exception(
                    'generatefailed',
                    'assignfeedback_aitutoria',
                    '',
                    $exception->getMessage()
                );
            }
        }

        $record = feedback_repository::save_human_feedback(
            $assignmentid,
            (int) $grade->id,
            $feedback,
            $acceptsuggestion,
            $reviewaction
        );
        audit_repository::record(
            $assignmentid,
            (int) $grade->id,
            'human_review_saved',
            null,
            (int) $USER->id,
            [
                'decision' => $record->decision,
                'aistatus' => $record->aistatus,
                'model' => $record->model ?? '',
                'promptversion' => $record->promptversion ?? '',
                'rubricversion' => $record->rubricversion ?? '',
            ]
        );

        $structuredrubric = $this->get_structured_rubric();
        if ($structuredrubric !== null) {
            $selections = [];
            foreach ($structuredrubric['criteria'] as $criterion) {
                $fieldname = self::criterion_field_name($criterion['id']);
                $selections[$criterion['id']] = isset($data->{$fieldname})
                    ? clean_param($data->{$fieldname}, PARAM_ALPHANUMEXT)
                    : '';
            }
            $latestjob = human_criterion_repository::get_latest_completed_job(
                $assignmentid,
                (int) $grade->id
            );
            $summary = human_criterion_repository::save_reviews(
                $assignmentid,
                (int) $grade->id,
                $latestjob ? (int) $latestjob->id : null,
                (int) $USER->id,
                $structuredrubric,
                $selections
            );
            audit_repository::record(
                $assignmentid,
                (int) $grade->id,
                'human_criteria_saved',
                $latestjob ? (int) $latestjob->id : null,
                (int) $USER->id,
                $summary + ['rubricversion' => $structuredrubric['version']]
            );
        }

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
        $reviewaction = isset($data->assignfeedback_aitutoria_reviewaction)
            ? clean_param($data->assignfeedback_aitutoria_reviewaction, PARAM_ALPHA)
            : decision_policy::ACTION_MANUAL;
        if (
            $stored !== $submitted
            || !empty($data->assignfeedback_aitutoria_acceptsuggestion)
            || $reviewaction !== decision_policy::ACTION_MANUAL
        ) {
            return true;
        }

        $structuredrubric = $this->get_structured_rubric();
        if ($structuredrubric === null) {
            return false;
        }
        $humanreviews = human_criterion_repository::get_for_grade((int) $grade->id);
        foreach ($structuredrubric['criteria'] as $criterion) {
            $fieldname = self::criterion_field_name($criterion['id']);
            $submittedlevel = isset($data->{$fieldname})
                ? clean_param($data->{$fieldname}, PARAM_ALPHANUMEXT)
                : '';
            $storedlevel = isset($humanreviews[$criterion['id']])
                ? (string) $humanreviews[$criterion['id']]->selectedlevel
                : '';
            if ($submittedlevel !== $storedlevel) {
                return true;
            }
        }

        return false;
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
     * Return the feedback format used by the gradebook.
     *
     * @param stdClass $grade Grade record.
     * @return int Feedback format.
     */
    public function format_for_gradebook(stdClass $grade) {
        return FORMAT_PLAIN;
    }

    /**
     * Return the published feedback text used by the gradebook.
     *
     * @param stdClass $grade Grade record.
     * @return string Published feedback.
     */
    public function text_for_gradebook(stdClass $grade) {
        $record = feedback_repository::get_by_grade((int) $grade->id);
        return $record ? (string) $record->feedbacktext : '';
    }

    /**
     * Delete all plugin data for this assignment.
     *
     * @return bool
     */
    public function delete_instance() {
        feedback_repository::delete_for_assignment((int) $this->assignment->get_instance()->id);
        return true;
    }

    /**
     * Determine whether a grade has no published feedback.
     *
     * @param stdClass $grade Grade record.
     * @return bool
     */
    public function is_empty(stdClass $grade) {
        return $this->view($grade) === '';
    }

    /**
     * Return plugin configuration for assignment external functions.
     *
     * @return array
     */
    public function get_config_for_external() {
        return (array) $this->get_config();
    }

    /**
     * Return the validated structured rubric for this assignment.
     *
     * @return array|null Structured rubric or null when unavailable.
     */
    private function get_structured_rubric(): ?array {
        $json = trim((string) ($this->get_config('structuredrubric') ?: ''));
        if ($json === '') {
            return null;
        }
        try {
            return assignment_policy::parse_rubric(
                $json,
                (string) get_config('assignfeedback_aitutoria', 'institutionalframeworkjson')
            );
        } catch (invalid_parameter_exception) {
            return null;
        }
    }

    /**
     * Render a compact teacher-facing summary of a structured rubric.
     *
     * @param array $rubric Structured rubric.
     * @return string Safe HTML.
     */
    private function render_structured_rubric(array $rubric): string {
        $items = [];
        foreach ($rubric['criteria'] as $criterion) {
            $levels = array_map(
                static fn(array $level): string => s($level['label']) . ' (' . $level['score'] . ')',
                $criterion['levels']
            );
            $label = html_writer::tag('strong', s($criterion['title']))
                . ' — ' . get_string('weight', 'grades') . ': ' . format_float($criterion['weight'], 2);
            if ($criterion['competencyid'] !== '') {
                $label .= ' — ' . s($criterion['competencyid']);
            }
            $label .= html_writer::div(implode(' · ', $levels), 'text-muted small');
            $items[] = $label;
        }
        return html_writer::div(
            html_writer::tag('strong', s($rubric['version'])) . html_writer::alist($items),
            'assignfeedback-aitutoria-structured-rubric'
        );
    }

    /**
     * Build a stable Moodle form field name for a criterion id.
     *
     * @param string $criterionkey Criterion identifier.
     * @return string Field name.
     */
    private static function criterion_field_name(string $criterionkey): string {
        return 'assignfeedback_aitutoria_criterion_' . substr(hash('sha256', $criterionkey), 0, 16);
    }
}
