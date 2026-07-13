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

$string['acceptancerate'] = 'AI suggestion acceptance rate';
$string['acceptsuggestion'] = 'Accept the AI suggestion as the published feedback';
$string['acceptsuggestion_help'] = 'Selecting this option is an explicit human decision. Review the full suggestion before saving.';
$string['agreementrate'] = 'AI-human agreement rate';
$string['aisuggestion'] = 'Unpublished AI suggestion';
$string['aitutoria:viewgovernance'] = 'View AI tutoring governance and health reports';
$string['allowaisuggestions'] = 'Allow AI suggestions';
$string['allowaisuggestions_help'] = 'Permit an internal provider layer to store unpublished AI suggestions for human review. This does not enable automatic grading or automatic publication.';
$string['assessmentmode'] = 'Advisory assessment mode';
$string['assessmentmode_assistive'] = 'Assistive: show private suggestions to teachers';
$string['assessmentmode_disabled'] = 'Disabled';
$string['assessmentmode_help'] = 'Shadow mode stores private calibration results. Assistive mode also makes the suggestion available to the teacher. Neither mode publishes feedback or changes a numeric grade automatically.';
$string['assessmentmode_shadow'] = 'Shadow: process privately for calibration';
$string['assignmentid'] = 'Assignment ID';
$string['attempts'] = 'Attempts';
$string['averageadvisorypercentage'] = 'Average advisory percentage';
$string['calibrationcompared'] = 'AI-human criterion comparisons';
$string['calibrationmatched'] = 'Matching criterion selections';
$string['calibrationmismatched'] = 'Divergent criterion selections';
$string['calibrationreviews'] = 'Final human criterion selections';
$string['count'] = 'Count';
$string['criterion_notassessed'] = 'Not assessed';
$string['criterionagreement'] = 'Agreement by criterion';
$string['criterionreview'] = 'Final human rubric selections';
$string['criterionreview_help'] = 'Select the final level for each criterion. These selections support calibration and governance only; they never change the Moodle numeric grade.';
$string['default'] = 'Enabled by default';
$string['default_help'] = 'Enable AI tutoring feedback by default for new assignments. The recovery baseline keeps this disabled.';
$string['downloadreportjson'] = 'Download JSON report';
$string['feedback'] = 'Feedback for the student';
$string['feedback_help'] = 'Write or edit the feedback that will be published to the student.';
$string['frameworkcompetencies'] = 'Institutional competencies';
$string['governancemetrics'] = 'Governance metrics';
$string['governancereport'] = 'AI tutoring governance and health';
$string['healthstatus'] = 'Operational health';
$string['healthstatus_critical'] = 'Critical configuration or database problems were detected.';
$string['healthstatus_ok'] = 'The plugin health checks did not detect critical or warning conditions.';
$string['healthstatus_warning'] = 'The plugin is operational, but warning conditions require attention.';
$string['humancontrol'] = 'Human control';
$string['humancontrol_help'] = 'AI output is advisory only. A human grader must review and explicitly save the feedback. This plugin never changes a numeric grade automatically.';
$string['humanreviews'] = 'Human review records';
$string['institutionalframework'] = 'Institutional competency framework (JSON)';
$string['institutionalframework_help'] = 'Define the site-wide versioned competency framework used as institutional context for assessment and tutoring. Each competency requires an ID, title and at least one indicator. Optional levels, methods and expected evidence may also be supplied.';
$string['institutionalframeworkvalidationerror'] = 'Invalid institutional framework: {$a}';
$string['invalidstructuredrubric'] = 'The structured rubric is invalid: {$a}';
$string['jobid'] = 'Job ID';
$string['jobstatus'] = 'Assessment job status';
$string['metric'] = 'Metric';
$string['none'] = 'None';
$string['notconfigured'] = 'Not configured';
$string['opengovernancereport'] = 'Open governance and health dashboard';
$string['overduequeuedjobs'] = 'Overdue queued jobs';
$string['pluginname'] = 'AI tutoring feedback';
$string['privacy:assessmentjob'] = 'Assessment job {$a}';
$string['privacy:metadata:actorid'] = 'The user who initiated a recorded human or system action.';
$string['privacy:metadata:aistatus'] = 'The processing status of an AI suggestion.';
$string['privacy:metadata:aisuggestion'] = 'An unpublished AI-generated suggestion awaiting human review.';
$string['privacy:metadata:assignment'] = 'The assignment associated with the feedback.';
$string['privacy:metadata:auditaction'] = 'The stable identifier of an audited action.';
$string['privacy:metadata:auditpayload'] = 'Redacted metadata associated with an audited action.';
$string['privacy:metadata:auditsummary'] = 'Stores a minimal redacted audit trail for processing and human decisions.';
$string['privacy:metadata:criterionkey'] = 'The stable identifier of the assessed rubric criterion.';
$string['privacy:metadata:criterionsummary'] = 'Stores criterion-level recommendations, rationales, evidence and uncertainty.';
$string['privacy:metadata:decision'] = 'Whether the human grader wrote, accepted, or overrode an AI suggestion.';
$string['privacy:metadata:evidence'] = 'Evidence excerpts and locations supporting a criterion recommendation.';
$string['privacy:metadata:feedbacktext'] = 'The feedback published after human review.';
$string['privacy:metadata:grade'] = 'The Moodle grade record associated with the student.';
$string['privacy:metadata:humancriterionsummary'] = 'Stores final human rubric selections and whether they match the advisory AI level.';
$string['privacy:metadata:jobid'] = 'The advisory assessment job associated with the record.';
$string['privacy:metadata:jobstatus'] = 'The processing status of an advisory assessment job.';
$string['privacy:metadata:jobsummary'] = 'Stores idempotent advisory assessment jobs and their private results.';
$string['privacy:metadata:lasterror'] = 'The most recent sanitized processing error.';
$string['privacy:metadata:matchesai'] = 'Whether the final human level matches the advisory AI level.';
$string['privacy:metadata:model'] = 'The model identifier used to create the suggestion.';
$string['privacy:metadata:policyjson'] = 'The effective Human in Control policy snapshot.';
$string['privacy:metadata:promptversion'] = 'The prompt or template version used to create the suggestion.';
$string['privacy:metadata:proposedlevel'] = 'The rubric level proposed for a criterion.';
$string['privacy:metadata:provider'] = 'The assessment provider identifier.';
$string['privacy:metadata:rationale'] = 'The rationale supporting a criterion recommendation.';
$string['privacy:metadata:requireshuman'] = 'Whether the criterion recommendation requires human review.';
$string['privacy:metadata:reviewerid'] = 'The user who selected the final rubric level.';
$string['privacy:metadata:rubricjson'] = 'The structured rubric snapshot used for the assessment.';
$string['privacy:metadata:rubricversion'] = 'The rubric version associated with the suggestion.';
$string['privacy:metadata:scoring'] = 'The deterministic advisory score calculated from rubric levels.';
$string['privacy:metadata:selectedlevel'] = 'The final rubric level selected by a human reviewer.';
$string['privacy:metadata:snapshotsummary'] = 'Stores immutable submission, rubric and policy snapshots for reproducibility.';
$string['privacy:metadata:submissionhash'] = 'A cryptographic hash used to identify the submission snapshot.';
$string['privacy:metadata:submissiontext'] = 'The student submission text processed by the advisory assessment.';
$string['privacy:metadata:tablesummary'] = 'Stores human-reviewed feedback and optional unpublished AI suggestions.';
$string['privacy:metadata:timecreated'] = 'When the feedback record was created.';
$string['privacy:metadata:timemodified'] = 'When the feedback record was last modified.';
$string['privacy:metadata:uncertainty'] = 'The uncertainty classification of a criterion recommendation.';
$string['privacy:path'] = 'AI tutoring feedback';
$string['processingtimeoutminutes'] = 'Processing timeout (minutes)';
$string['processingtimeoutminutes_help'] = 'Jobs remaining in processing beyond this period are flagged as stale in health diagnostics. This setting does not automatically publish or grade anything.';
$string['productionproviders'] = 'Production providers';
$string['provider'] = 'Provider';
$string['publicationcontrol'] = 'Publication control';
$string['publicationcontrol_help'] = 'Only the text saved by the human grader is shown to the student. Stored AI suggestions remain private until explicitly accepted or rewritten.';
$string['recentfailures'] = 'Recent permanent failures';
$string['requiringhumanreview'] = 'Criterion results requiring human review';
$string['retentiondays'] = 'Advisory assessment retention (days)';
$string['retentiondays_help'] = 'Delete completed and permanently failed assessment jobs, submission snapshots, evidence and job audit events after this many days. Official feedback and human decisions are preserved. Use 0 to disable automatic deletion.';
$string['reviewaction'] = 'Decision about the AI suggestion';
$string['reviewaction_accept'] = 'Accept the suggestion as the feedback';
$string['reviewaction_escalate'] = 'Escalate for additional human review';
$string['reviewaction_help'] = 'Choose an explicit action. Rejecting or escalating never publishes the AI suggestion.';
$string['reviewaction_manual'] = 'Use my own or edited feedback';
$string['reviewaction_reject'] = 'Reject the suggestion';
$string['rubric'] = 'Assessment rubric and guidance';
$string['rubric_help'] = 'Describe the criteria and expected evidence. This baseline stores the rubric with the assignment and always requires human review.';
$string['rubricversion'] = 'Rubric version';
$string['rubricversion_help'] = 'A stable identifier for the rubric used in this assignment, such as 2026.1 or v3.';
$string['settings:framework'] = 'Institutional competencies';
$string['settings:framework_help'] = 'The institutional framework is site-wide context. It does not contain student data and does not enable automatic grading.';
$string['settings:general'] = 'General and operational controls';
$string['staleprocessingjobs'] = 'Stale processing jobs';
$string['structuredrubric'] = 'Structured activity rubric (JSON)';
$string['structuredrubric_help'] = 'Provide a versioned JSON rubric with criteria, positive weights and at least two levels per criterion. Criteria may reference institutional competencies using competencyid.';
$string['task:cleanupassessmentdata'] = 'Delete expired AI tutoring assessment artifacts';
$string['totalcriteria'] = 'Criterion results';
$string['totaljobs'] = 'Assessment jobs';
$string['value'] = 'Value';
