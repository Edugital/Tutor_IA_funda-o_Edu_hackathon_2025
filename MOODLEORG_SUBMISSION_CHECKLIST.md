# Moodle.org submission checklist — assignfeedback_aitutoria

**Plugin:** `assignfeedback_aitutoria` (AI tutoring feedback)  
**Release target:** `0.6.0-rfp` / version `2026071501`  
**Maintainer:** Edugital  
**Status:** Draft checklist for Moodle.org directory submission (RFP track)

Fill placeholders as evidence is collected. Do not publish API keys or student content in screenshots.

---

## 1. Plugin identity

| Item | Status | Notes |
|------|--------|-------|
| Component name matches path `mod/assign/feedback/aitutoria` | [ ] | |
| `version.php` release + version coherent | [ ] | `0.6.0-rfp` / `2026071501` |
| `maturity` appropriate (ALPHA until UAT) | [ ] | Currently `MATURITY_ALPHA` |
| Supported Moodle branches declared | [ ] | Requires `2024042200` (4.4+); CI matrix 4.4 / 4.5 |
| License GPL v3+ present | [ ] | `LICENSE` |
| README / INSTALL for schools | [ ] | `README.md`, `INSTALL.md` |

---

## 2. Privacy API

| Item | Status | Notes / evidence |
|------|--------|------------------|
| `\assignfeedback_aitutoria\privacy\provider` implemented | [ ] | `classes/privacy/provider.php` |
| Metadata collectors cover all plugin tables | [ ] | feedback, job, snp, crt, hcr, aud |
| Export / delete user data paths tested | [ ] | PHPUnit `tests/privacy/provider_test.php` |
| No unexpected third-party export of PII | [ ] | Providers receive submission text only when authorized; keys in config |
| Privacy strings complete (en + pt_br) | [ ] | `privacy:metadata:*` |
| Data retention task documented | [ ] | `retentiondays` + cleanup scheduled task |

---

## 3. Language strings

| Item | Status | Notes |
|------|--------|-------|
| English (`lang/en`) complete for UI | [ ] | |
| Portuguese Brazil (`lang/pt_br`) complete | [ ] | Pilot locale |
| No hardcoded UI strings in PHP/JS | [ ] | Spot-check grader + reports |
| Help strings for HIC actions | [ ] | `reviewaction_help`, policy settings |
| Capability string `aitutoria:viewgovernance` | [ ] | |

---

## 4. Human-in-Control (HIC) policy

| Item | Status | Notes |
|------|--------|-------|
| Site defaults `default=0`, `allowaisuggestions=0` | [ ] | Must remain off until acceptance |
| Numeric grade never written by plugin | [ ] | Covered by decision_policy / assessment_service |
| Accept copies suggestion only via explicit action | [ ] | `decision_policy::ACTION_ACCEPT` |
| Reject / escalate never publish AI text | [ ] | |
| Diff UI does not auto-publish | [ ] | Grader panel only |

---

## 5. Screenshots (Moodle.org)

Prepare **non-production** screenshots with synthetic data (qa_* accounts). Store paths below when ready.

| Shot | Description | Path / URL | Status |
|------|-------------|------------|--------|
| Settings | Admin settings: policy + providers (keys masked) | _TBD_ | [ ] |
| Grader diff | Side-by-side AI vs current feedback + Aceitar/Editar/Descartar | _TBD_ | [ ] |
| Generate confirm | `generate.php` human confirmation | _TBD_ | [ ] |
| Governance report | Health + metrics | _TBD_ | [ ] |
| Calibration | 7d / 30d decision rates | _TBD_ | [ ] |
| Student view | Published human feedback only (no raw AI) | _TBD_ | [ ] |

---

## 6. CI / quality

| Item | Status | Notes |
|------|--------|-------|
| GitHub Actions `moodle-plugin-ci` present | [ ] | `.github/workflows/moodle-ci.yml` |
| Matrix Moodle 4.4 + 4.5 | [ ] | `MOODLE_404_STABLE`, `MOODLE_405_STABLE` |
| PHP lint | [ ] | CI step / last green run: _TBD_ |
| PHPUnit | [ ] | Last green run: _TBD_ |
| Moodle Code Checker / PHPCS | [ ] | `phpcs.xml.dist` |
| Behat (if applicable) | [ ] | Optional for alpha |
| CI badge / Actions URL | [ ] | _TBD_ |

---

## 7. Security & secrets

| Item | Status | Notes |
|------|--------|-------|
| No API keys in git | [ ] | |
| Provider stubs fail closed without key | [ ] | GLM / Anthropic / OpenAI stub |
| Error sanitizer strips secrets from failures | [ ] | `error_sanitizer` |
| Capability checks on report/calibration pages | [ ] | `assignfeedback/aitutoria:viewgovernance` |

---

## 8. Submission package

| Item | Status | Notes |
|------|--------|-------|
| ZIP root folder named `aitutoria/` | [ ] | Moodle.org convention for assign feedback |
| Exclude `.git`, secrets, local snapshots | [ ] | |
| Upgrade path from previous release tested | [ ] | Pilot: `0.5.0-hic` → `0.6.0-rfp` |
| Tracker / docs link | [ ] | GitHub + `docs/` |
| Funding / origin disclosure | [ ] | `docs/FUNDING_AND_ORIGIN.md` |

---

## 9. Sign-off

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Product / SoW owner | | | |
| Engineering | | | |
| Privacy / LGPD review | | | |
| UAT lead | | | |

*Checklist created for EBAC LMS RFP plan v1.5.0 — update statuses before Moodle.org upload.*
