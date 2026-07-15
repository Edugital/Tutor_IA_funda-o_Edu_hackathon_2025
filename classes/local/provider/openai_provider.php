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

use assignfeedback_aitutoria\local\dto\assessment_request;
use assignfeedback_aitutoria\local\dto\assessment_result;

/**
 * OpenAI advisory provider stub (pluggable slot for RFP / future production).
 *
 * Mirrors GLM/Anthropic registry wiring. Refuses to run without an API key and
 * does not publish grades. Production calls are intentionally not implemented
 * in this release — configure openaiapikey and replace the stub assess() body
 * when an authorized OpenAI/Azure endpoint is approved.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class openai_provider implements provider_interface {
    /** @var string API key. */
    private string $apikey;

    /** @var string Model id. */
    private string $model;

    /** @var string API base URL without trailing slash. */
    private string $endpoint;

    /**
     * @param string|null $apikey Optional override.
     * @param string|null $model Optional model override.
     * @param string|null $endpoint Optional endpoint override.
     */
    public function __construct(?string $apikey = null, ?string $model = null, ?string $endpoint = null) {
        $apikey = trim((string) ($apikey ?? get_config('assignfeedback_aitutoria', 'openaiapikey')));
        $model = trim((string) ($model ?? get_config('assignfeedback_aitutoria', 'openaimodel')));
        $endpoint = trim((string) ($endpoint ?? get_config('assignfeedback_aitutoria', 'openaiendpoint')));
        if ($apikey === '') {
            throw new \moodle_exception('missingopenaikey', 'assignfeedback_aitutoria');
        }
        if ($model === '') {
            $model = 'gpt-4o-mini';
        }
        if ($endpoint === '') {
            $endpoint = 'https://api.openai.com/v1/chat/completions';
        }
        $this->apikey = $apikey;
        $this->model = $model;
        $this->endpoint = $endpoint;
    }

    public function get_name(): string {
        return 'openai';
    }

    public function get_model(): string {
        return $this->model;
    }

    public function get_promptversion(): string {
        return 'ebac-di-assistive-openai-stub-v1';
    }

    public function assess(assessment_request $request): assessment_result {
        // Keep HIC intact: never invent feedback. Stub refuses live calls until
        // product/ops authorize a production OpenAI integration.
        unset($request);
        throw new \moodle_exception(
            'openaistub',
            'assignfeedback_aitutoria',
            '',
            $this->model . ' @ ' . $this->endpoint
        );
    }
}
