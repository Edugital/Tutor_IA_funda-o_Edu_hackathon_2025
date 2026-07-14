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
 * Claude (Anthropic) advisory provider for EBAC pilot.
 *
 * Reads API key from plugin config only (never hardcode secrets).
 * Returns unpublished suggestions; never writes Moodle grades.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class anthropic_provider implements provider_interface {
    /** @var string Anthropic API key. */
    private string $apikey;

    /** @var string Model id. */
    private string $model;

    /**
     * @param string|null $apikey Optional override (tests).
     * @param string|null $model Optional model override.
     */
    public function __construct(?string $apikey = null, ?string $model = null) {
        $apikey = trim((string) ($apikey ?? get_config('assignfeedback_aitutoria', 'anthropicapikey')));
        $model = trim((string) ($model ?? get_config('assignfeedback_aitutoria', 'anthropicmodel')));
        if ($apikey === '') {
            throw new \moodle_exception('missinganthropickey', 'assignfeedback_aitutoria');
        }
        if ($model === '') {
            $model = 'claude-sonnet-4-20250514';
        }
        $this->apikey = $apikey;
        $this->model = $model;
    }

    public function get_name(): string {
        return 'anthropic';
    }

    public function get_model(): string {
        return $this->model;
    }

    public function get_promptversion(): string {
        return 'ebac-di-assistive-v1';
    }

    public function assess(assessment_request $request): assessment_result {
        $rubric = $request->get_rubric();
        $criteria = $rubric['criteria'] ?? [];
        if (empty($criteria)) {
            throw new \invalid_parameter_exception('Anthropic provider requires structured rubric criteria.');
        }

        $levelcatalog = [];
        foreach ($criteria as $criterion) {
            $levelcatalog[$criterion['id']] = array_map(
                static fn(array $level): string => (string) $level['id'],
                $criterion['levels']
            );
        }

        $payload = [
            'model' => $this->model,
            'max_tokens' => 1800,
            'temperature' => 0.2,
            'system' => 'Você é tutora de Design Instrucional da EBAC. Avalie envios de alunos de forma consultiva. '
                . 'Responda APENAS JSON válido (sem markdown) no schema pedido. Nunca invente critérios. '
                . 'Feedback em português do Brasil, tom profissional e acionável. Não atribua nota numérica final.',
            'messages' => [[
                'role' => 'user',
                'content' => $this->build_user_prompt($request, $levelcatalog),
            ]],
        ];

        $raw = $this->call_messages_api($payload);
        $decoded = $this->parse_json_content($raw);

        $suggestion = trim((string) ($decoded['suggestion'] ?? ''));
        if ($suggestion === '') {
            throw new \moodle_exception('anthropicemptysuggestion', 'assignfeedback_aitutoria');
        }

        $criterionresults = [];
        $incoming = $decoded['criteria'] ?? [];
        if (!is_array($incoming)) {
            throw new \moodle_exception('anthropicbadcriteria', 'assignfeedback_aitutoria');
        }

        foreach ($criteria as $criterion) {
            $cid = (string) $criterion['id'];
            $row = null;
            foreach ($incoming as $item) {
                if (!is_array($item)) {
                    continue;
                }
                if ((string) ($item['criterionkey'] ?? '') === $cid) {
                    $row = $item;
                    break;
                }
            }
            if ($row === null) {
                throw new \moodle_exception('anthropicmissingcriterion', 'assignfeedback_aitutoria', '', $cid);
            }

            $level = trim((string) ($row['proposedlevel'] ?? ''));
            if (!in_array($level, $levelcatalog[$cid], true)) {
                $level = $levelcatalog[$cid][0];
            }
            $rationale = trim((string) ($row['rationale'] ?? ''));
            if ($rationale === '') {
                $rationale = 'Critério revisado; detalhe insuficiente no retorno do modelo.';
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
                [[
                    'excerpt' => $excerpt,
                    'location' => 'submission',
                ]],
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
            [
                'externalcall' => true,
                'vendor' => 'anthropic',
            ]
        );
    }

    /**
     * @param assessment_request $request Request.
     * @param array $levelcatalog Criterion id => level ids.
     * @return string
     */
    private function build_user_prompt(assessment_request $request, array $levelcatalog): string {
        $rubric = $request->get_rubric();
        $schema = [
            'suggestion' => 'string feedback privado para o professor revisar (pt_BR)',
            'criteria' => [[
                'criterionkey' => 'id do critério',
                'proposedlevel' => 'um dos level ids permitidos',
                'rationale' => 'string',
                'excerpt' => 'trecho curto do envio',
                'uncertainty' => 'low|medium|high',
            ]],
        ];

        return "Rubrica estruturada (JSON):\n" . json_encode($rubric, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "\n\nNíveis permitidos por critério:\n" . json_encode($levelcatalog, JSON_UNESCAPED_UNICODE)
            . "\n\nEnvio do aluno:\n" . $request->get_submissiontext()
            . "\n\nResponda exatamente neste schema JSON:\n" . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * @param array $payload Anthropic request body.
     * @return string Assistant text content.
     */
    private function call_messages_api(array $payload): string {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $endpoint = 'https://api.anthropic.com/v1/messages';
        $curl = new \curl();
        $curl->setHeader([
            'Content-Type: application/json',
            'x-api-key: ' . $this->apikey,
            'anthropic-version: 2023-06-01',
        ]);
        $response = $curl->post($endpoint, json_encode($payload));
        $info = $curl->get_info();
        $httpcode = (int) ($info['http_code'] ?? 0);
        if ($httpcode < 200 || $httpcode >= 300) {
            throw new \moodle_exception(
                'anthropichttp',
                'assignfeedback_aitutoria',
                '',
                $httpcode . ': ' . \core_text::substr((string) $response, 0, 240)
            );
        }

        try {
            $decoded = json_decode((string) $response, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \moodle_exception('anthropicbadjson', 'assignfeedback_aitutoria', '', $exception->getMessage());
        }

        $chunks = $decoded['content'] ?? [];
        $text = '';
        if (is_array($chunks)) {
            foreach ($chunks as $chunk) {
                if (is_array($chunk) && ($chunk['type'] ?? '') === 'text') {
                    $text .= (string) ($chunk['text'] ?? '');
                }
            }
        }
        $text = trim($text);
        if ($text === '') {
            throw new \moodle_exception('anthropicemptycontent', 'assignfeedback_aitutoria');
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
            throw new \moodle_exception('anthropicbadjson', 'assignfeedback_aitutoria', '', $exception->getMessage());
        }
        if (!is_array($decoded)) {
            throw new \moodle_exception('anthropicbadjson', 'assignfeedback_aitutoria', '', 'not an object');
        }
        return $decoded;
    }
}
