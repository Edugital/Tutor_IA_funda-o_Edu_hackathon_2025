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
use assignfeedback_aitutoria\local\dto\criterion_result;

/**
 * GLM (Zhipu / Z.ai) OpenAI-compatible advisory provider for EBAC pilot.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class glm_provider implements provider_interface {
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
        $apikey = trim((string) ($apikey ?? get_config('assignfeedback_aitutoria', 'glmapikey')));
        $model = trim((string) ($model ?? get_config('assignfeedback_aitutoria', 'glmmodel')));
        $endpoint = trim((string) ($endpoint ?? get_config('assignfeedback_aitutoria', 'glmendpoint')));
        if ($apikey === '') {
            throw new \moodle_exception('missingglmkey', 'assignfeedback_aitutoria');
        }
        if ($model === '') {
            $model = 'glm-4-flash';
        }
        if ($endpoint === '') {
            $endpoint = 'https://open.bigmodel.cn/api/paas/v4/chat/completions';
        }
        $this->apikey = $apikey;
        $this->model = $model;
        $this->endpoint = $endpoint;
    }

    public function get_name(): string {
        return 'glm';
    }

    public function get_model(): string {
        return $this->model;
    }

    public function get_promptversion(): string {
        return 'ebac-di-assistive-glm-v1';
    }

    public function assess(assessment_request $request): assessment_result {
        $rubric = $request->get_rubric();
        $criteria = $rubric['criteria'] ?? [];
        if (empty($criteria)) {
            throw new \invalid_parameter_exception('GLM provider requires structured rubric criteria.');
        }

        $levelcatalog = [];
        foreach ($criteria as $criterion) {
            $levelcatalog[$criterion['id']] = array_map(
                static fn(array $level): string => (string) $level['id'],
                $criterion['levels']
            );
        }

        $schema = [
            'suggestion' => 'string feedback privado para o professor (pt_BR)',
            'criteria' => [[
                'criterionkey' => 'id',
                'proposedlevel' => 'level id permitido',
                'rationale' => 'string',
                'excerpt' => 'trecho do envio',
                'uncertainty' => 'low|medium|high',
            ]],
        ];

        $userprompt = "Rubrica:\n" . json_encode($rubric, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\n\nNíveis permitidos:\n" . json_encode($levelcatalog, JSON_UNESCAPED_UNICODE)
            . "\n\nEnvio do aluno:\n" . $request->get_submissiontext()
            . "\n\nResponda APENAS JSON neste schema:\n" . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $payload = [
            'model' => $this->model,
            'temperature' => 0.2,
            'max_tokens' => 1800,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Você é tutora de Design Instrucional da EBAC. Avalie de forma consultiva. '
                        . 'Responda somente JSON válido (sem markdown). Feedback em português do Brasil.',
                ],
                [
                    'role' => 'user',
                    'content' => $userprompt,
                ],
            ],
        ];

        $raw = $this->call_chat_completions($payload);
        $decoded = $this->parse_json_content($raw);
        $suggestion = trim((string) ($decoded['suggestion'] ?? ''));
        if ($suggestion === '') {
            throw new \moodle_exception('glmemptysuggestion', 'assignfeedback_aitutoria');
        }

        $incoming = $decoded['criteria'] ?? [];
        if (!is_array($incoming)) {
            throw new \moodle_exception('glmbadcriteria', 'assignfeedback_aitutoria');
        }

        $criterionresults = [];
        foreach ($criteria as $criterion) {
            $cid = (string) $criterion['id'];
            $row = null;
            foreach ($incoming as $item) {
                if (is_array($item) && (string) ($item['criterionkey'] ?? '') === $cid) {
                    $row = $item;
                    break;
                }
            }
            if ($row === null) {
                throw new \moodle_exception('glmmissingcriterion', 'assignfeedback_aitutoria', '', $cid);
            }
            $level = trim((string) ($row['proposedlevel'] ?? ''));
            if (!in_array($level, $levelcatalog[$cid], true)) {
                $level = $levelcatalog[$cid][0];
            }
            $rationale = trim((string) ($row['rationale'] ?? 'Critério revisado.'));
            if ($rationale === '') {
                $rationale = 'Critério revisado.';
            }
            $excerpt = trim((string) ($row['excerpt'] ?? ''));
            if ($excerpt === '') {
                $excerpt = \core_text::substr($request->get_submissiontext(), 0, 160);
            }
            $uncertainty = trim((string) ($row['uncertainty'] ?? 'high'));
            if (!in_array($uncertainty, ['low', 'medium', 'high'], true)) {
                $uncertainty = 'high';
            }
            $criterionresults[] = new criterion_result(
                $cid,
                $level,
                $rationale,
                [['excerpt' => $excerpt, 'location' => 'submission']],
                $uncertainty,
                true
            );
        }

        return new assessment_result(
            $this->get_name(),
            $this->get_model(),
            $this->get_promptversion(),
            $suggestion,
            $criterionresults,
            ['externalcall' => true, 'vendor' => 'glm']
        );
    }

    /**
     * @param array $payload Request body.
     * @return string Assistant content.
     */
    private function call_chat_completions(array $payload): string {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        $curl->setHeader([
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apikey,
        ]);
        $response = $curl->post($this->endpoint, json_encode($payload));
        $info = $curl->get_info();
        $httpcode = (int) ($info['http_code'] ?? 0);
        if ($httpcode < 200 || $httpcode >= 300) {
            throw new \moodle_exception(
                'glmhttp',
                'assignfeedback_aitutoria',
                '',
                $httpcode . ': ' . \core_text::substr((string) $response, 0, 240)
            );
        }
        try {
            $decoded = json_decode((string) $response, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \moodle_exception('glmbadjson', 'assignfeedback_aitutoria', '', $exception->getMessage());
        }
        $text = trim((string) ($decoded['choices'][0]['message']['content'] ?? ''));
        if ($text === '') {
            throw new \moodle_exception('glmemptycontent', 'assignfeedback_aitutoria');
        }
        return $text;
    }

    /**
     * @param string $raw Model text.
     * @return array
     */
    private function parse_json_content(string $raw): array {
        $raw = trim($raw);
        if (preg_match('/\{.*\}/s', $raw, $matches)) {
            $raw = $matches[0];
        }
        try {
            $decoded = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \moodle_exception('glmbadjson', 'assignfeedback_aitutoria', '', $exception->getMessage());
        }
        if (!is_array($decoded)) {
            throw new \moodle_exception('glmbadjson', 'assignfeedback_aitutoria', '', 'not an object');
        }
        return $decoded;
    }
}
