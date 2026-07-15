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

$string['acceptancerate'] = 'Taxa de aceitação das sugestões de IA';
$string['acceptsuggestion'] = 'Aceitar a sugestão de IA como feedback publicado';
$string['acceptsuggestion_help'] = 'Esta seleção registra uma decisão humana explícita. Revise a sugestão integralmente antes de salvar.';
$string['agreementrate'] = 'Taxa de concordância IA-pessoa avaliadora';
$string['aisuggestion'] = 'Sugestão de IA ainda não publicada';
$string['aitutoria:viewgovernance'] = 'Visualizar relatórios de governança e saúde da Tutoria IA';
$string['allowaisuggestions'] = 'Permitir sugestões de IA';
$string['allowaisuggestions_help'] = 'Permite que uma camada interna e autorizada armazene sugestões não publicadas para revisão humana. Não ativa correção automática nem publicação automática.';
$string['assessmentmode'] = 'Modo de avaliação consultiva';
$string['assessmentmode_assistive'] = 'Assistido: disponibilizar sugestões privadas aos professores';
$string['assessmentmode_disabled'] = 'Desativado';
$string['assessmentmode_help'] = 'O modo sombra armazena resultados privados para calibração. O modo assistido também disponibiliza a sugestão ao professor. Nenhum modo publica feedback nem altera nota numérica automaticamente.';
$string['assessmentmode_shadow'] = 'Sombra: processar privadamente para calibração';
$string['assignmentid'] = 'ID da tarefa';
$string['attempts'] = 'Tentativas';
$string['averageadvisorypercentage'] = 'Percentual consultivo médio';
$string['calibrationcompared'] = 'Comparações IA-pessoa por critério';
$string['calibrationmatched'] = 'Seleções coincidentes';
$string['calibrationmismatched'] = 'Seleções divergentes';
$string['calibrationperiod7d'] = 'Últimos 7 dias';
$string['calibrationperiod30d'] = 'Últimos 30 dias';
$string['calibrationreport'] = 'Calibração da Tutoria IA';
$string['calibrationreport_help'] = 'Taxas entre revisões que envolveram sugestão de IA (aceita, editada/sobrescrita, rejeitada ou escalada). A política Human in Control permanece: nada é publicado sem ação explícita do avaliador.';
$string['calibrationreviews'] = 'Seleções humanas finais por critério';
$string['calibrationwindow'] = 'Janela: {$a->from} → {$a->to}';
$string['count'] = 'Quantidade';
$string['criterion_notassessed'] = 'Não avaliado';
$string['criterionagreement'] = 'Concordância por critério';
$string['criterionreview'] = 'Seleção humana final da rubrica';
$string['criterionreview_help'] = 'Selecione o nível final de cada critério. Essas escolhas servem apenas para calibração e governança; nunca alteram a nota numérica do Moodle.';
$string['decision_accepted_ai'] = 'IA aceita';
$string['decision_escalated'] = 'Escalado';
$string['decision_overridden_ai'] = 'IA editada / sobrescrita';
$string['decision_rejected_ai'] = 'IA rejeitada';
$string['default'] = 'Ativar por padrão';
$string['default_help'] = 'Ativa o feedback com Tutoria IA por padrão em novas tarefas. A versão de recuperação mantém esta opção desativada.';
$string['diffaisuggestion'] = 'Sugestão de IA';
$string['diffcompare'] = 'Comparar sugestão e feedback atual';
$string['diffcurrentempty'] = '(Ainda sem feedback publicado — a área de texto abaixo está vazia.)';
$string['diffcurrentfeedback'] = 'Feedback atual (padrão do formulário)';
$string['downloadreportjson'] = 'Baixar relatório JSON';
$string['feedback'] = 'Feedback para o estudante';
$string['feedback_help'] = 'Escreva ou revise o feedback que será publicado para o estudante.';
$string['frameworkcompetencies'] = 'Competências institucionais';
$string['governancemetrics'] = 'Métricas de governança';
$string['governancereport'] = 'Governança e saúde da Tutoria IA';
$string['healthstatus'] = 'Saúde operacional';
$string['healthstatus_critical'] = 'Foram detectados problemas críticos de configuração ou banco de dados.';
$string['healthstatus_ok'] = 'As verificações de saúde não detectaram condições críticas ou alertas.';
$string['healthstatus_warning'] = 'O plugin está operacional, mas existem alertas que exigem atenção.';
$string['humancontrol'] = 'Controle humano';
$string['humancontrol_help'] = 'A IA é apenas consultiva. Uma pessoa responsável deve revisar e salvar explicitamente o feedback. O plugin nunca altera a nota numérica automaticamente.';
$string['humanreviews'] = 'Registros de revisão humana';
$string['institutionalframework'] = 'Matriz institucional de competências (JSON)';
$string['institutionalframework_help'] = 'Defina a matriz institucional versionada que servirá de contexto para avaliação e tutoria em todo o site. Cada competência exige ID, título e pelo menos um indicador. Também podem ser informados níveis, métodos e evidências esperadas.';
$string['institutionalframeworkvalidationerror'] = 'Matriz institucional inválida: {$a}';
$string['invalidstructuredrubric'] = 'A rubrica estruturada é inválida: {$a}';
$string['jobid'] = 'ID do processamento';
$string['jobstatus'] = 'Estado dos processamentos de avaliação';
$string['metric'] = 'Métrica';
$string['none'] = 'Nenhum';
$string['notconfigured'] = 'Não configurado';
$string['opencalibrationreport'] = 'Abrir painel de calibração';
$string['opengovernancereport'] = 'Abrir painel de governança e saúde';
$string['overduequeuedjobs'] = 'Processamentos em fila atrasados';
$string['percentofreviewed'] = '% entre revisados com IA';
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
$string['privacy:metadata:humancriterionsummary'] = 'Armazena o nível final selecionado por uma pessoa e sua concordância com a recomendação consultiva da IA.';
$string['privacy:metadata:jobid'] = 'O processamento consultivo associado ao registro.';
$string['privacy:metadata:jobstatus'] = 'O estado de processamento de uma avaliação consultiva.';
$string['privacy:metadata:jobsummary'] = 'Armazena processamentos idempotentes e seus resultados privados.';
$string['privacy:metadata:lasterror'] = 'O erro de processamento mais recente, previamente sanitizado.';
$string['privacy:metadata:matchesai'] = 'Indica se o nível humano final coincide com o nível consultivo da IA.';
$string['privacy:metadata:model'] = 'O identificador do modelo usado para gerar a sugestão.';
$string['privacy:metadata:policyjson'] = 'O snapshot da política Human in Control aplicada.';
$string['privacy:metadata:promptversion'] = 'A versão do prompt ou template usado para gerar a sugestão.';
$string['privacy:metadata:proposedlevel'] = 'O nível da rubrica proposto para um critério.';
$string['privacy:metadata:provider'] = 'O identificador do provedor de avaliação.';
$string['privacy:metadata:rationale'] = 'A justificativa que sustenta uma recomendação por critério.';
$string['privacy:metadata:requireshuman'] = 'Indica se a recomendação exige revisão humana.';
$string['privacy:metadata:reviewerid'] = 'A pessoa que selecionou o nível final da rubrica.';
$string['privacy:metadata:rubricjson'] = 'O snapshot da rubrica estruturada usada na avaliação.';
$string['privacy:metadata:rubricversion'] = 'A versão da rubrica associada à sugestão.';
$string['privacy:metadata:scoring'] = 'O cálculo consultivo determinístico derivado dos níveis da rubrica.';
$string['privacy:metadata:selectedlevel'] = 'O nível final da rubrica selecionado por uma pessoa.';
$string['privacy:metadata:snapshotsummary'] = 'Armazena snapshots imutáveis da submissão, rubrica e política.';
$string['privacy:metadata:submissionhash'] = 'Um hash criptográfico usado para identificar o snapshot da submissão.';
$string['privacy:metadata:submissiontext'] = 'O texto da submissão processado pela avaliação consultiva.';
$string['privacy:metadata:tablesummary'] = 'Armazena feedback revisado por uma pessoa e sugestões opcionais de IA ainda não publicadas.';
$string['privacy:metadata:timecreated'] = 'Quando o registro de feedback foi criado.';
$string['privacy:metadata:timemodified'] = 'Quando o registro de feedback foi alterado pela última vez.';
$string['privacy:metadata:uncertainty'] = 'A classificação de incerteza de uma recomendação por critério.';
$string['privacy:path'] = 'Feedback com Tutoria IA';
$string['processingtimeoutminutes'] = 'Tempo limite de processamento (minutos)';
$string['processingtimeoutminutes_help'] = 'Processamentos que permanecerem ativos além deste período serão sinalizados como travados no diagnóstico de saúde. Esta configuração não publica feedback nem atribui nota automaticamente.';
$string['productionproviders'] = 'Provedores de produção';
$string['provider'] = 'Provedor';
$string['publicationcontrol'] = 'Controle de publicação';
$string['publicationcontrol_help'] = 'Somente o texto salvo pela pessoa avaliadora é exibido ao estudante. Sugestões de IA permanecem privadas até serem aceitas ou reescritas.';
$string['recentfailures'] = 'Falhas definitivas recentes';
$string['requiringhumanreview'] = 'Resultados por critério que exigem revisão humana';
$string['retentiondays'] = 'Retenção das avaliações consultivas (dias)';
$string['retentiondays_help'] = 'Exclui processamentos concluídos e definitivamente falhos, snapshots de submissões, evidências e eventos de auditoria do processamento após este número de dias. O feedback oficial e as decisões humanas são preservados. Use 0 para desativar a exclusão automática.';
$string['reviewedwithai'] = 'Revisados com IA';
$string['reviewaction'] = 'Decisão sobre a sugestão de IA';
$string['reviewaction_accept'] = 'Aceitar';
$string['reviewaction_escalate'] = 'Escalar';
$string['reviewaction_help'] = 'Escolha uma ação explícita. Aceitar publica a sugestão da IA como feedback. Editar (manual) mantém o texto da área. Descartar rejeita a sugestão. Escalar nunca publica o texto da IA.';
$string['reviewaction_manual'] = 'Editar (manual)';
$string['reviewaction_reject'] = 'Descartar';
$string['rubric'] = 'Rubrica e orientações da avaliação';
$string['rubric_help'] = 'Descreva critérios, níveis e evidências esperadas. Esta versão armazena a rubrica na tarefa e exige revisão humana.';
$string['rubricversion'] = 'Versão da rubrica';
$string['rubricversion_help'] = 'Identificador estável da rubrica usada nesta tarefa, como 2026.1 ou v3.';
$string['settings:framework'] = 'Competências institucionais';
$string['settings:framework_help'] = 'A matriz institucional é um contexto geral do site. Ela não contém dados de estudantes nem ativa correção automática.';
$string['settings:general'] = 'Controles gerais e operacionais';
$string['settings:policy'] = 'Política Human in Control';
$string['settings:policy_help'] = 'A Tutoria IA nunca publica feedback nem altera nota numérica automaticamente. Mantenha “Ativar por padrão” e “Permitir sugestões de IA” desligados até haver provedor autorizado e aceite institucional. Veja INSTALL.md.';
$string['settings:providers'] = 'Provedores de avaliação';
$string['settings:providers_help'] = 'Configure um provedor autorizado para sugestões consultivas não publicadas. Chaves de API nunca devem ir para o git.';
$string['defaultprovider'] = 'Provedor padrão';
$string['defaultprovider_help'] = 'Provedor usado ao enfileirar avaliações consultivas.';
$string['anthropicapikey'] = 'Chave de API Anthropic';
$string['anthropicapikey_help'] = 'Chave Claude do piloto EBAC. Armazenada apenas na config do Moodle.';
$string['anthropicmodel'] = 'Modelo Anthropic';
$string['anthropicmodel_help'] = 'Identificador do modelo, ex.: claude-sonnet-4-20250514.';
$string['glmapikey'] = 'Chave de API GLM';
$string['glmapikey_help'] = 'Chave Zhipu / Z.ai do piloto EBAC. Armazenada apenas na config do Moodle.';
$string['glmmodel'] = 'Modelo GLM';
$string['glmmodel_help'] = 'Identificador do modelo, ex.: glm-4-flash.';
$string['glmendpoint'] = 'Endpoint GLM';
$string['glmendpoint_help'] = 'URL OpenAI-compatible de chat completions.';
$string['openaiapikey'] = 'Chave de API OpenAI';
$string['openaiapikey_help'] = 'Chave OpenAI (ou compatível). Deixe vazia até o uso em produção ser autorizado. Nunca versione chaves no git.';
$string['openaimodel'] = 'Modelo OpenAI';
$string['openaimodel_help'] = 'Identificador do modelo, ex.: gpt-4o-mini.';
$string['openaiendpoint'] = 'Endpoint OpenAI';
$string['openaiendpoint_help'] = 'URL de chat completions (OpenAI ou Azure-compatible).';
$string['missingglmkey'] = 'A chave de API GLM não está configurada.';
$string['glmemptysuggestion'] = 'A GLM retornou sugestão vazia.';
$string['glmbadcriteria'] = 'A GLM retornou critérios inválidos.';
$string['glmmissingcriterion'] = 'Resposta GLM sem o critério: {$a}';
$string['glmhttp'] = 'Erro HTTP GLM: {$a}';
$string['glmbadjson'] = 'Erro ao interpretar JSON GLM: {$a}';
$string['glmemptycontent'] = 'A GLM retornou conteúdo vazio.';
$string['missinganthropickey'] = 'A chave de API Anthropic não está configurada.';
$string['anthropicemptysuggestion'] = 'A Anthropic retornou sugestão vazia.';
$string['anthropicbadcriteria'] = 'A Anthropic retornou critérios inválidos.';
$string['anthropicmissingcriterion'] = 'Resposta Anthropic sem o critério: {$a}';
$string['anthropichttp'] = 'Erro HTTP Anthropic: {$a}';
$string['anthropicbadjson'] = 'Erro ao interpretar JSON Anthropic: {$a}';
$string['anthropicemptycontent'] = 'A Anthropic retornou conteúdo vazio.';
$string['missingopenaikey'] = 'A chave de API OpenAI não está configurada. Defina assignfeedback_aitutoria/openaiapikey antes de usar este provedor.';
$string['openaistub'] = 'O provedor OpenAI é um stub neste release (modelo {$a}). Configure a chave de API e substitua a implementação stub de assess() antes do uso em produção. A política Human in Control permanece intacta.';
$string['pluginisdisabled'] = 'A Tutoria IA está desativada nesta tarefa.';
$string['submissionnotready'] = 'O envio do aluno ainda não está pronto para avaliação.';
$string['submissionempty'] = 'O texto do envio do aluno está vazio.';
$string['generatesuggestion'] = 'Gerar sugestão de IA';
$string['generatesuggestionbutton'] = 'Gerar sugestão de IA agora';
$string['generatesuggestion_help'] = 'Executa o provedor configurado e guarda uma sugestão não publicada para revisão humana. Não altera a nota numérica.';
$string['generatesuggestionconfirm'] = 'Gerar uma sugestão de IA não publicada para este envio? A nota numérica não será alterada.';
$string['generatesuggestionsuccess'] = 'Sugestão de IA gerada e armazenada para revisão humana.';
$string['generatefailed'] = 'Não foi possível gerar a sugestão de IA: {$a}';
$string['requestaisuggestion'] = 'Também gerar sugestão de IA ao salvar';
$string['requestaisuggestion_help'] = 'Se marcado, ao salvar este formulário a avaliação consultiva é executada antes de gravar o feedback humano.';
$string['staleprocessingjobs'] = 'Processamentos ativos travados';
$string['structuredrubric'] = 'Rubrica estruturada da atividade (JSON)';
$string['structuredrubric_help'] = 'Informe uma rubrica JSON versionada com critérios, pesos positivos e pelo menos dois níveis por critério. Os critérios podem referenciar competências institucionais pelo campo competencyid.';
$string['task:cleanupassessmentdata'] = 'Excluir artefatos expirados da Tutoria IA';
$string['totalcriteria'] = 'Resultados por critério';
$string['totaljobs'] = 'Processamentos de avaliação';
$string['value'] = 'Valor';
