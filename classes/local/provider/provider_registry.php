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

namespace assignfeedback_aitutoria\local\provider;

/**
 * Registry for explicitly supported assessment providers.
 *
 * No external provider is enabled in this foundation release.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider_registry {
    /**
     * Resolve a provider by stable name.
     *
     * @param string $name Provider name.
     * @return provider_interface
     */
    public static function get(string $name): provider_interface {
        $name = trim($name);

        if ($name === 'fixture' && defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            return new fixture_provider();
        }

        throw new \coding_exception('No production assessment provider is configured.');
    }

    /**
     * List providers available in the current runtime.
     *
     * @return array<string, string>
     */
    public static function available(): array {
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            return ['fixture' => 'Deterministic PHPUnit fixture'];
        }

        return [];
    }
}
