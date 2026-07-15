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
        if ($name === '') {
            $name = self::default_name();
        }

        if ($name === 'glm') {
            return new glm_provider();
        }
        if ($name === 'anthropic') {
            return new anthropic_provider();
        }
        if ($name === 'openai') {
            return new openai_provider();
        }
        if ($name === 'fixture' && defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            return new fixture_provider();
        }

        throw new \coding_exception('No production assessment provider is configured: ' . $name);
    }

    /**
     * Default production provider name when configured.
     *
     * @return string
     */
    public static function default_name(): string {
        $configured = trim((string) get_config('assignfeedback_aitutoria', 'defaultprovider'));
        $available = self::available();
        if ($configured !== '' && isset($available[$configured])) {
            return $configured;
        }
        // Prefer GLM when both exist (Anthropic may be quota-limited on shared EBAC keys).
        if (isset($available['glm'])) {
            return 'glm';
        }
        if (isset($available['anthropic'])) {
            return 'anthropic';
        }
        if (isset($available['openai'])) {
            return 'openai';
        }
        return '';
    }

    /**
     * List providers available in the current runtime.
     *
     * @return array<string, string>
     */
    public static function available(): array {
        $out = [];
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            $out['fixture'] = 'Deterministic PHPUnit fixture';
        }
        if (trim((string) get_config('assignfeedback_aitutoria', 'glmapikey')) !== '') {
            $out['glm'] = 'GLM / Zhipu (EBAC)';
        }
        if (trim((string) get_config('assignfeedback_aitutoria', 'anthropicapikey')) !== '') {
            $out['anthropic'] = 'Anthropic Claude (EBAC)';
        }
        if (trim((string) get_config('assignfeedback_aitutoria', 'openaiapikey')) !== '') {
            $out['openai'] = 'OpenAI (stub — configure production calls)';
        }
        return $out;
    }
}
