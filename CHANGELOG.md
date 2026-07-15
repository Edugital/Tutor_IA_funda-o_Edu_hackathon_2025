# Changelog

Todas as alterações relevantes deste projeto serão documentadas aqui.

O formato segue os princípios de Keep a Changelog e versionamento semântico, adaptados ao versionamento obrigatório de plugins Moodle.

## [Unreleased]

### Planejado

- implementação completa do provedor OpenAI (hoje stub);
- rubricas estruturadas e versionadas na UI;
- tutor fundamentado no curso;
- submissão Moodle.org após UAT + checklist.

## [0.6.0-rfp] - 2026-07-15

### Adicionado

- painel de diff no grader (sugestão IA × feedback atual) com classes `.aitutoria-diff`;
- labels HIC mais claras no select: Aceitar / Editar (manual) / Descartar / Escalar;
- `calibration.php` — taxas 7d/30d (`accepted_ai`, `overridden_ai`, `rejected_ai`, `escalated`) via `governance_report::build`;
- link Calibração a partir de `report.php`;
- CLI `cli/seed_calibration_demo.php` (dry-run; `--execute` aplica decisões em jobs completos existentes);
- stub `openai_provider` + settings + decision YAML `blueprint/plugins/decision/assignfeedback_aitutoria_openai.yml`;
- `MOODLEORG_SUBMISSION_CHECKLIST.md`;
- CI mínimo `.github/workflows/moodle-ci.yml` (Moodle 4.4 / 4.5).

### Alterado

- release `0.6.0-rfp` / version `2026071501`;
- strings en + pt_br para diff, calibração e OpenAI stub.

### Política

- HIC intacta: `decision_policy::ACTION_ACCEPT` continua sendo o único caminho que copia a sugestão para o feedback publicado; defaults de site permanecem off no piloto.

## [0.5.0-hic] - 2026-07-14

### Adicionado

- providers **GLM** e **Anthropic** plugáveis;
- `generate.php` com confirmação humana (`confirm` + sesskey);
- `request_factory` e registro de provedores;
- `docs/FUNDING_AND_ORIGIN.md` e `docs/ABOUT.md` (cadeia Fundação Itaú → Instituto Saber → EduHackathon 2025);
- CLI `queue_assessment.php`.

### Alterado

- release `0.5.0-hic` / version `2026071400`;
- README alinhado ao estado real HIC + piloto EBAC (substitui narrativa `0.2.1-recovery` sem provider).

### Validado

- piloto EBAC LMS Moodle 4.5.12+ com GLM operacional;
- política HIC intacta (`default=0`, `allowaisuggestions=0` no site).

## [0.4.0-alpha.0] - 2026-07-13

### Adicionado

- `INSTALL.md` para escolas (ZIP + SSH + checklist de aceite);
- docs STATUS alinhados ao release atual;
- habilitação demonstrável por atividade no piloto EBAC (site default permanece off).

### Alterado

- bump de versão Moodle plugin para `2026071310` / release `0.4.0-alpha.0`;
- registro explícito do contrato de provedores (ainda sem provedor de produção).

## [0.3.0-alpha.2] - 2026-07-13

### Validado no piloto EBAC LMS (Moodle 4.5)

- instalação SHA-pinned;
- `diagnose.php --json` sem critical;
- `default=0` e `allowaisuggestions=0`.

## [0.2.1-recovery] - 2026-07-13

### Corrigido

- conformidade com o Moodle Code Checker;
- prefixo canônico da tabela `assignfeedback_aitutoria`;
- validação oficial de metadados do plugin;
- migração automática da tabela curta usada pela primeira baseline candidata;
- rastreabilidade dos relatórios de CI.

### Validado

- instalação limpa no Moodle 4.4 com MariaDB;
- instalação limpa no Moodle 4.5 com PostgreSQL;
- PHP lint;
- PHPUnit;
- Behat;
- savepoints de atualização;
- pacote ZIP instalável com raiz `aitutoria/`.

## [0.2.0-recovery] - 2026-07-13

### Adicionado

- fonte canônica do plugin `assignfeedback_aitutoria`;
- feedback manual por grade;
- rubrica e versão por atividade;
- armazenamento separado de sugestão de IA;
- decisão humana `manual`, `accepted_ai` ou `overridden_ai`;
- feedback visível ao estudante;
- Privacy API;
- backup e restauração;
- migração não destrutiva da configuração legada conhecida;
- idiomas inglês e português do Brasil;
- teste unitário da política Human in Control;
- validação estática e empacotamento ZIP.

### Segurança

- plugin desativado por padrão;
- sugestões de IA desativadas por padrão;
- nenhuma alteração automática de nota numérica;
- nenhuma credencial ou endpoint de produção incorporado ao código.

### Limitações

- sem provedor externo de IA;
- sem processamento de anexos;
- sem rubrica estruturada;
- sem painel administrativo avançado;
- atualização sobre o banco legado real ainda depende de teste externo.
