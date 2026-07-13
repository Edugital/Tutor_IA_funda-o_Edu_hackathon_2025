<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace assignfeedback_aitutoria\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Deterministic human-in-control decision policy.
 *
 * This class never changes a numeric grade. It only decides which feedback
 * text is published after an explicit human action.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class decision_policy {

    /**
     * Resolve the feedback to publish.
     *
     * @param string $humantext Human-authored or human-edited feedback.
     * @param string $suggestion Optional AI suggestion.
     * @param bool $acceptsuggestion Explicit human acceptance.
     * @return array{text: string, decision: string}
     */
    public static function resolve(string $humantext, string $suggestion, bool $acceptsuggestion): array {
        $humantext = trim($humantext);
        $suggestion = trim($suggestion);

        if ($acceptsuggestion) {
            if ($suggestion === '') {
                throw new \coding_exception('An AI suggestion cannot be accepted when no suggestion is available.');
            }

            return [
                'text' => $suggestion,
                'decision' => 'accepted_ai',
            ];
        }

        if ($suggestion !== '') {
            return [
                'text' => $humantext,
                'decision' => 'overridden_ai',
            ];
        }

        return [
            'text' => $humantext,
            'decision' => 'manual',
        ];
    }
}
