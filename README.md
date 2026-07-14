# Tutor IA para Moodle (`assignfeedback_aitutoria`)

Plugin de feedback e tutoria assistida por IA para tarefas Moodle, com política **Human in Control (HIC)**: a IA sugere; o professor decide.

| Campo | Valor |
|-------|-------|
| Componente | `assignfeedback_aitutoria` |
| Release | **`0.5.0-hic`** |
| Maturidade | alpha |
| Moodle mínimo | 4.4 (validado 4.5 LTS) |
| Path | `mod/assign/feedback/aitutoria` |
| Piloto | [EBAC LMS](https://alandantas.net/moodle_ebac) |

## Origem e financiamento

Cadeia documentada em [`docs/FUNDING_AND_ORIGIN.md`](docs/FUNDING_AND_ORIGIN.md) e [`docs/ABOUT.md`](docs/ABOUT.md):

**Fundação Itaú** (edital *Inteligência Artificial para a Educação*) → **Instituto Saber de Desenvolvimento Social e Educacional** (projeto selecionado) → **EduHackathon 2025** (Instituto Saber Social + EBAC + Edugital) → este plugin.

## Human in Control (garantias)

1. A IA **não** escreve nota numérica.
2. Sugestões ficam privadas até decisão humana explícita (`confirm=1` + sesskey).
3. O professor pode aceitar, editar ou substituir a sugestão.
4. Falha/ausência de IA não bloqueia avaliação manual.
5. Automação **desligada por padrão** (`default=0`, `allowaisuggestions=0`).
6. Provedores plugáveis — sem lock-in de fornecedor.

## O que esta versão entrega

- Endpoints de geração com confirmação humana (`generate.php`).
- Providers plugáveis: **GLM** (operacional no piloto) e **Anthropic** (quota limitada no lab).
- Registro de provedores (`provider_registry`) + `request_factory`.
- CLI `diagnose.php --json` e fila `cli/queue_assessment.php`.
- Privacy API, backup/restore, pt_br + en.
- Instalação SHA/tag pinned (nunca `main`/HEAD em produção).

## Instalação (resumo)

1. Baixe o release ZIP `aitutoria/` na raiz do pacote (ver GitHub Releases).
2. Copie para `mod/assign/feedback/aitutoria` **ou** instale via UI de plugins.
3. `php admin/cli/upgrade.php --non-interactive`
4. Configure provedor e chaves **somente** na UI Moodle (nunca em git).
5. Mantenha site default off; habilite por atividade no piloto.

Runbook completo: [`INSTALL.md`](INSTALL.md) · checklist: [`docs/RELEASE_CHECKLIST.md`](docs/RELEASE_CHECKLIST.md).

## Diagnóstico

```bash
php mod/assign/feedback/aitutoria/cli/diagnose.php --json
```

Esperado: `status=ok`, sem `critical`.

## Licença

GNU GPL v3 or later — ver [`LICENSE`](LICENSE).

## Relacionados

- Pacote white-label: [ebac-lms-blueprint](https://github.com/Edugital/ebac-lms-blueprint) (`v1.2.0-revenda`)
- Issues: epic HIC [#3](https://github.com/Edugital/Tutor_IA_funda-o_Edu_hackathon_2025/issues/3) · install P0 [#2](https://github.com/Edugital/Tutor_IA_funda-o_Edu_hackathon_2025/issues/2)
