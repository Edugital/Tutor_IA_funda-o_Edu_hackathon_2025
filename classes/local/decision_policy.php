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

namespace assignfeedback_aitutoria\local;

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
    /** Human-authored or edited feedback. */
    public const ACTION_MANUAL = 'manual';

    /** Explicit acceptance of the private suggestion. */
    public const ACTION_ACCEPT = 'accept';

    /** Explicit rejection of the private suggestion. */
    public const ACTION_REJECT = 'reject';

    /** Escalation for additional human review. */
    public const ACTION_ESCALATE = 'escalate';

    /**
     * Resolve the legacy boolean acceptance contract.
     *
     * @param string $humantext Human-authored or human-edited feedback.
     * @param string $suggestion Optional AI suggestion.
     * @param bool $acceptsuggestion Explicit human acceptance.
     * @return array{text: string, decision: string, aistatus: string}
     */
    public static function resolve(string $humantext, string $suggestion, bool $acceptsuggestion): array {
        return self::resolve_review(
            $humantext,
            $suggestion,
            $acceptsuggestion ? self::ACTION_ACCEPT : self::ACTION_MANUAL
        );
    }

    /**
     * Resolve a complete Human in Control review action.
     *
     * @param string $humantext Human-authored or human-edited feedback.
     * @param string $suggestion Optional AI suggestion.
     * @param string $action Review action.
     * @return array{text: string, decision: string, aistatus: string}
     */
    public static function resolve_review(string $humantext, string $suggestion, string $action): array {
        $humantext = trim($humantext);
        $suggestion = trim($suggestion);
        $action = trim($action);

        if (!in_array(
            $action,
            [self::ACTION_MANUAL, self::ACTION_ACCEPT, self::ACTION_REJECT, self::ACTION_ESCALATE],
            true
        )) {
            throw new \invalid_parameter_exception('Unknown Human in Control review action.');
        }

        if ($action !== self::ACTION_MANUAL && $suggestion === '') {
            throw new \coding_exception('An AI review action requires an available suggestion.');
        }

        if ($action === self::ACTION_ACCEPT) {
            return [
                'text' => $suggestion,
                'decision' => 'accepted_ai',
                'aistatus' => 'accepted',
            ];
        }

        if ($action === self::ACTION_REJECT) {
            return [
                'text' => $humantext,
                'decision' => 'rejected_ai',
                'aistatus' => 'rejected',
            ];
        }

        if ($action === self::ACTION_ESCALATE) {
            return [
                'text' => $humantext,
                'decision' => 'escalated',
                'aistatus' => 'escalated',
            ];
        }

        if ($suggestion !== '') {
            return [
                'text' => $humantext,
                'decision' => 'overridden_ai',
                'aistatus' => 'overridden',
            ];
        }

        return [
            'text' => $humantext,
            'decision' => 'manual',
            'aistatus' => 'not_requested',
        ];
    }
}
