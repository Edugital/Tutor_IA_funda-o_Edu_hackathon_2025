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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

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
$string['privacy:metadata:aisuggestion'] = 'Uma sugestão gerada por IA, ainda não publicada e aguardando revisão humana.';
$string['privacy:metadata:aistatus'] = 'O estado de processamento de uma sugestão de IA.';
$string['privacy:metadata:assignment'] = 'A tarefa associada ao feedback.';
$string['privacy:metadata:decision'] = 'Indica se a pessoa avaliadora escreveu, aceitou ou substituiu uma sugestão de IA.';
$string['privacy:metadata:feedbacktext'] = 'O feedback publicado após revisão humana.';
$string['privacy:metadata:grade'] = 'O registro de avaliação do Moodle associado ao estudante.';
$string['privacy:metadata:model'] = 'O identificador do modelo usado para gerar a sugestão.';
$string['privacy:metadata:promptversion'] = 'A versão do prompt ou template usado para gerar a sugestão.';
$string['privacy:metadata:rubricversion'] = 'A versão da rubrica associada à sugestão.';
$string['privacy:metadata:tablesummary'] = 'Armazena feedback revisado por uma pessoa e sugestões opcionais de IA ainda não publicadas.';
$string['privacy:metadata:timecreated'] = 'Quando o registro de feedback foi criado.';
$string['privacy:metadata:timemodified'] = 'Quando o registro de feedback foi alterado pela última vez.';
$string['privacy:path'] = 'Feedback com Tutoria IA';
$string['publicationcontrol'] = 'Controle de publicação';
$string['publicationcontrol_help'] = 'Somente o texto salvo pela pessoa avaliadora é exibido ao estudante. Sugestões de IA permanecem privadas até serem aceitas ou reescritas.';
$string['rubric'] = 'Rubrica e orientações da avaliação';
$string['rubric_help'] = 'Descreva critérios, níveis e evidências esperadas. Esta versão armazena a rubrica na tarefa e exige revisão humana.';
$string['rubricversion'] = 'Versão da rubrica';
$string['rubricversion_help'] = 'Identificador estável da rubrica usada nesta tarefa, como 2026.1 ou v3.';
