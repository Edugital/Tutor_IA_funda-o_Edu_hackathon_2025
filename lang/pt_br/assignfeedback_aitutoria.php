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
 * Strings em português do Brasil para o feedback de Tutoria IA.
 *
 * @package    assignfeedback_aitutoria
 * @copyright  2026 Edugital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['acceptsuggestion'] = 'Aceitar a sugestão de IA como feedback publicado';
$string['acceptsuggestion_help'] = 'Esta seleção registra uma decisão humana explícita. Revise a sugestão integralmente antes de salvar.';
$string['aisuggestion'] = 'Sugestão de IA ainda não publicada';
$string['allowaisuggestions'] = 'Permitir sugestões de IA';
$string['allowaisuggestions_help'] = 'Permite que uma camada interna e autorizada armazene sugestões não publicadas para revisão humana. Não ativa correção automática nem publicação automática.';
$string['default'] = 'Ativar por padrão';
$string['default_help'] = 'Ativa o feedback com Tutoria IA por padrão em novas tarefas. A versão de recuperação mantém esta opção desativada.';
$string['feedback'] = 'Feedback para o estudante';
$string['feedback_help'] = 'Escreva ou revise o feedback que será publicado para o estudante.';
$string['humancontrol'] = 'Controle humano';
$string['humancontrol_help'] = 'A IA é apenas consultiva. Uma pessoa responsável deve revisar e salvar explicitamente o feedback. O plugin nunca altera a nota numérica automaticamente.';
$string['pluginname'] = 'Feedback com Tutoria IA';
$string['privacy:assessmentjob'] = 'Processamento de avaliação {$a}';
$string['privacy:metadata:actorid'] = 'A pessoa que iniciou uma ação humana ou sistêmica registrada.';
$string['privacy:metadata:aistatus'] = 'O estado de processamento de uma sugestão de IA.';
$string['privacy:metadata:aisuggestion'] = 'Uma sugestão gerada por IA, ainda não publicada e aguardando revisão humana.';
$string['privacy:metadata:assignment'] = 'A tarefa associada ao feedback.';
$string['privacy:metadata:auditaction'] = 'O identificador estável de uma ação auditada.';
$string['privacy:metadata:auditpayload'] = 'Metadados redigidos associados a uma ação auditada.';
$string['privacy:metadata:auditsummary'] = 'Armazena uma trilha mínima e redigida de processamento e decisões humanas.';
$string['privacy:metadata:criterionkey'] = 'O identificador estável do critério avaliado.';
$string['privacy:metadata:criterionsummary'] = 'Armazena recomendações por critério, justificativas, evidências e incerteza.';
$string['privacy:metadata:decision'] = 'Indica se a pessoa avaliadora escreveu, aceitou ou substituiu uma sugestão de IA.';
$string['privacy:metadata:evidence'] = 'Trechos e localizações das evidências que sustentam uma recomendação.';
$string['privacy:metadata:feedbacktext'] = 'O feedback publicado após revisão humana.';
$string['privacy:metadata:grade'] = 'O registro de avaliação do Moodle associado ao estudante.';
$string['privacy:metadata:jobid'] = 'O processamento consultivo associado ao registro.';
$string['privacy:metadata:jobstatus'] = 'O estado de processamento de uma avaliação consultiva.';
$string['privacy:metadata:jobsummary'] = 'Armazena processamentos idempotentes e seus resultados privados.';
$string['privacy:metadata:lasterror'] = 'O erro de processamento mais recente, previamente sanitizado.';
$string['privacy:metadata:model'] = 'O identificador do modelo usado para gerar a sugestão.';
$string['privacy:metadata:policyjson'] = 'O snapshot da política Human in Control aplicada.';
$string['privacy:metadata:promptversion'] = 'A versão do prompt ou template usado para gerar a sugestão.';
$string['privacy:metadata:proposedlevel'] = 'O nível da rubrica proposto para um critério.';
$string['privacy:metadata:provider'] = 'O identificador do provedor de avaliação.';
$string['privacy:metadata:rationale'] = 'A justificativa que sustenta uma recomendação por critério.';
$string['privacy:metadata:requireshuman'] = 'Indica se a recomendação exige revisão humana.';
$string['privacy:metadata:rubricjson'] = 'O snapshot da rubrica estruturada usada na avaliação.';
$string['privacy:metadata:rubricversion'] = 'A versão da rubrica associada à sugestão.';
$string['privacy:metadata:scoring'] = 'O cálculo consultivo determinístico derivado dos níveis da rubrica.';
$string['privacy:metadata:snapshotsummary'] = 'Armazena snapshots imutáveis da submissão, rubrica e política.';
$string['privacy:metadata:submissionhash'] = 'Um hash criptográfico usado para identificar o snapshot da submissão.';
$string['privacy:metadata:submissiontext'] = 'O texto da submissão processado pela avaliação consultiva.';
$string['privacy:metadata:tablesummary'] = 'Armazena feedback revisado por uma pessoa e sugestões opcionais de IA ainda não publicadas.';
$string['privacy:metadata:timecreated'] = 'Quando o registro de feedback foi criado.';
$string['privacy:metadata:timemodified'] = 'Quando o registro de feedback foi alterado pela última vez.';
$string['privacy:metadata:uncertainty'] = 'A classificação de incerteza de uma recomendação por critério.';
$string['privacy:path'] = 'Feedback com Tutoria IA';
$string['publicationcontrol'] = 'Controle de publicação';
$string['publicationcontrol_help'] = 'Somente o texto salvo pela pessoa avaliadora é exibido ao estudante. Sugestões de IA permanecem privadas até serem aceitas ou reescritas.';
$string['reviewaction'] = 'Decisão sobre a sugestão de IA';
$string['reviewaction_accept'] = 'Aceitar a sugestão como feedback';
$string['reviewaction_escalate'] = 'Escalar para uma revisão humana adicional';
$string['reviewaction_help'] = 'Escolha uma ação explícita. Rejeitar ou escalar nunca publica a sugestão da IA.';
$string['reviewaction_manual'] = 'Usar meu próprio feedback ou uma versão editada';
$string['reviewaction_reject'] = 'Rejeitar a sugestão';
$string['rubric'] = 'Rubrica e orientações da avaliação';
$string['rubric_help'] = 'Descreva critérios, níveis e evidências esperadas. Esta versão armazena a rubrica na tarefa e exige revisão humana.';
$string['rubricversion'] = 'Versão da rubrica';
$string['rubricversion_help'] = 'Identificador estável da rubrica usada nesta tarefa, como 2026.1 ou v3.';
