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
 * English strings for AI Tutoring feedback.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['acceptsuggestion'] = 'Accept the AI suggestion as the published feedback';
$string['acceptsuggestion_help'] = 'Selecting this option is an explicit human decision. Review the full suggestion before saving.';
$string['aisuggestion'] = 'Unpublished AI suggestion';
$string['allowaisuggestions'] = 'Allow AI suggestions';
$string['allowaisuggestions_help'] = 'Permit an internal provider layer to store unpublished AI suggestions for human review. This does not enable automatic grading or automatic publication.';
$string['default'] = 'Enabled by default';
$string['default_help'] = 'Enable AI tutoring feedback by default for new assignments. The recovery baseline keeps this disabled.';
$string['feedback'] = 'Feedback for the student';
$string['feedback_help'] = 'Write or edit the feedback that will be published to the student.';
$string['humancontrol'] = 'Human control';
$string['humancontrol_help'] = 'AI output is advisory only. A human grader must review and explicitly save the feedback. This plugin never changes a numeric grade automatically.';
$string['pluginname'] = 'AI tutoring feedback';
$string['privacy:metadata:aistatus'] = 'The processing status of an AI suggestion.';
$string['privacy:metadata:aisuggestion'] = 'An unpublished AI-generated suggestion awaiting human review.';
$string['privacy:metadata:assignment'] = 'The assignment associated with the feedback.';
$string['privacy:metadata:decision'] = 'Whether the human grader wrote, accepted, or overrode an AI suggestion.';
$string['privacy:metadata:feedbacktext'] = 'The feedback published after human review.';
$string['privacy:metadata:grade'] = 'The Moodle grade record associated with the student.';
$string['privacy:metadata:model'] = 'The model identifier used to create the suggestion.';
$string['privacy:metadata:promptversion'] = 'The prompt or template version used to create the suggestion.';
$string['privacy:metadata:rubricversion'] = 'The rubric version associated with the suggestion.';
$string['privacy:metadata:tablesummary'] = 'Stores human-reviewed feedback and optional unpublished AI suggestions.';
$string['privacy:metadata:timecreated'] = 'When the feedback record was created.';
$string['privacy:metadata:timemodified'] = 'When the feedback record was last modified.';
$string['privacy:path'] = 'AI tutoring feedback';
$string['publicationcontrol'] = 'Publication control';
$string['publicationcontrol_help'] = 'Only the text saved by the human grader is shown to the student. Stored AI suggestions remain private until explicitly accepted or rewritten.';
$string['rubric'] = 'Assessment rubric and guidance';
$string['rubric_help'] = 'Describe the criteria and expected evidence. This baseline stores the rubric with the assignment and always requires human review.';
$string['rubricversion'] = 'Rubric version';
$string['rubricversion_help'] = 'A stable identifier for the rubric used in this assignment, such as 2026.1 or v3.';
