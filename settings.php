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
 * Administrative settings for AI Tutoring feedback.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_heading(
    'assignfeedback_aitutoria/policyheading',
    new lang_string('settings:policy', 'assignfeedback_aitutoria'),
    new lang_string('settings:policy_help', 'assignfeedback_aitutoria')
));

$settings->add(new admin_setting_heading(
    'assignfeedback_aitutoria/generalheading',
    new lang_string('settings:general', 'assignfeedback_aitutoria'),
    ''
));

$settings->add(new admin_setting_configcheckbox(
    'assignfeedback_aitutoria/default',
    new lang_string('default', 'assignfeedback_aitutoria'),
    new lang_string('default_help', 'assignfeedback_aitutoria'),
    0
));

$settings->add(new admin_setting_configcheckbox(
    'assignfeedback_aitutoria/allowaisuggestions',
    new lang_string('allowaisuggestions', 'assignfeedback_aitutoria'),
    new lang_string('allowaisuggestions_help', 'assignfeedback_aitutoria'),
    0
));

$settings->add(new admin_setting_configtext(
    'assignfeedback_aitutoria/retentiondays',
    new lang_string('retentiondays', 'assignfeedback_aitutoria'),
    new lang_string('retentiondays_help', 'assignfeedback_aitutoria'),
    0,
    PARAM_INT
));

$settings->add(new admin_setting_configtext(
    'assignfeedback_aitutoria/processingtimeoutminutes',
    new lang_string('processingtimeoutminutes', 'assignfeedback_aitutoria'),
    new lang_string('processingtimeoutminutes_help', 'assignfeedback_aitutoria'),
    15,
    PARAM_INT
));

$settings->add(new admin_setting_heading(
    'assignfeedback_aitutoria/frameworkheading',
    new lang_string('settings:framework', 'assignfeedback_aitutoria'),
    new lang_string('settings:framework_help', 'assignfeedback_aitutoria')
));

$settings->add(new \assignfeedback_aitutoria\admin\setting_framework(
    'assignfeedback_aitutoria/institutionalframeworkjson',
    new lang_string('institutionalframework', 'assignfeedback_aitutoria'),
    new lang_string('institutionalframework_help', 'assignfeedback_aitutoria'),
    '',
    PARAM_RAW,
    '100',
    '24'
));

$reporturl = new moodle_url('/mod/assign/feedback/aitutoria/report.php');
$settings->add(new admin_setting_description(
    'assignfeedback_aitutoria/governancereportlink',
    new lang_string('governancereport', 'assignfeedback_aitutoria'),
    html_writer::link(
        $reporturl,
        get_string('opengovernancereport', 'assignfeedback_aitutoria'),
        ['class' => 'btn btn-secondary']
    )
));
