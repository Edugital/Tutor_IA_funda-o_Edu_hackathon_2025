<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Administrative settings for AI Tutoring feedback.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

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
