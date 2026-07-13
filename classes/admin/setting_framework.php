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

namespace assignfeedback_aitutoria\admin;

use assignfeedback_aitutoria\local\idempotency;
use assignfeedback_aitutoria\local\institutional_framework_parser;

/**
 * Validated site-level institutional competency framework setting.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class setting_framework extends \admin_setting_configtextarea {
    /**
     * Validate the framework before storage.
     *
     * @param string $data Submitted JSON.
     * @return true|string True when valid, otherwise an error message.
     */
    public function validate($data) {
        $data = trim((string) $data);
        if ($data === '') {
            return true;
        }

        try {
            institutional_framework_parser::parse($data);
            return true;
        } catch (\invalid_parameter_exception $exception) {
            return get_string(
                'institutionalframeworkvalidationerror',
                'assignfeedback_aitutoria',
                $exception->getMessage()
            );
        }
    }

    /**
     * Store a canonical JSON representation after validation.
     *
     * @param string $data Submitted JSON.
     * @return string Empty string on success or an error message.
     */
    public function write_setting($data) {
        $data = trim((string) $data);
        if ($data !== '') {
            $validated = $this->validate($data);
            if ($validated !== true) {
                return $validated;
            }
            $data = idempotency::canonical_json(institutional_framework_parser::parse($data));
        }

        return parent::write_setting($data);
    }
}
