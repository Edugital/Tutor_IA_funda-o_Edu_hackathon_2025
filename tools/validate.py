#!/usr/bin/env python3
"""Static and behavioral validation for assignfeedback_aitutoria."""

from __future__ import annotations

import re
import subprocess
import sys
import xml.etree.ElementTree as ET
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

REQUIRED = {
    "version.php",
    "settings.php",
    "locallib.php",
    "db/install.xml",
    "db/upgrade.php",
    "db/tasks.php",
    "db/access.php",
    "report.php",
    "classes/local/decision_policy.php",
    "classes/local/feedback_repository.php",
    "classes/local/assessment_service.php",
    "classes/local/repository/human_criterion_repository.php",
    "classes/local/reporting/health_report.php",
    "classes/privacy/provider.php",
    "classes/task/process_assessment.php",
    "classes/task/cleanup_assessment_data.php",
    "backup/moodle2/backup_assignfeedback_aitutoria_subplugin.class.php",
    "backup/moodle2/restore_assignfeedback_aitutoria_subplugin.class.php",
    "lang/en/assignfeedback_aitutoria.php",
    "lang/pt_br/assignfeedback_aitutoria.php",
    "tests/decision_policy_test.php",
    "README.md",
    "CHANGELOG.md",
    "LICENSE",
}

FORBIDDEN_PATTERNS = {
    r"/home/[A-Za-z0-9._-]+/public_html": "production-specific absolute path",
    r"whm\s+root:[A-Za-z0-9]{16,}": "embedded WHM token",
    r"sk-[A-Za-z0-9_-]{20,}": "embedded API key",
    r"(?:api[_-]?key|token|secret)\s*[=:]\s*['\"][A-Za-z0-9_\-]{20,}": "embedded secret",
    r"autograde\s*[=:>]\s*(?:1|true)": "automatic grading enabled",
}

GRADE_WRITE_PATTERNS = {
    r"(?:insert_record|update_record|delete_records|delete_records_select)\s*\(\s*['\"]assign_grades['\"]":
        "direct assign_grades record mutation",
    r"(?:set_field|set_field_select)\s*\(\s*['\"]assign_grades['\"]":
        "direct assign_grades field mutation",
    r"(?:UPDATE|INSERT\s+INTO|DELETE\s+FROM)\s+\{assign_grades\}":
        "direct assign_grades SQL mutation",
}

IGNORED_DIRS = {".git", "vendor", "node_modules", "dist", ".venv"}
NON_PRODUCTION_DIRS = {"tests", "tools"}


def fail(message: str) -> None:
    print(f"ERROR: {message}", file=sys.stderr)
    raise SystemExit(1)


def iter_text_files():
    for path in ROOT.rglob("*"):
        if not path.is_file() or any(part in IGNORED_DIRS for part in path.parts):
            continue
        try:
            yield path, path.read_text(encoding="utf-8")
        except (UnicodeDecodeError, OSError):
            continue


def iter_production_php_files():
    for path in sorted(ROOT.rglob("*.php")):
        relative = path.relative_to(ROOT)
        if any(part in IGNORED_DIRS | NON_PRODUCTION_DIRS for part in relative.parts):
            continue
        yield path, path.read_text(encoding="utf-8")


def validate_required_files() -> None:
    missing = sorted(path for path in REQUIRED if not (ROOT / path).is_file())
    if missing:
        fail("missing required files: " + ", ".join(missing))


def validate_php() -> None:
    php_files = sorted(ROOT.rglob("*.php"))
    if not php_files:
        fail("no PHP files found")
    for path in php_files:
        result = subprocess.run(
            ["php", "-l", str(path)],
            check=False,
            capture_output=True,
            text=True,
        )
        if result.returncode != 0:
            fail(f"PHP syntax error in {path.relative_to(ROOT)}:\n{result.stdout}{result.stderr}")


def validate_xml() -> None:
    for relative in ("db/install.xml", "phpcs.xml.dist"):
        try:
            ET.parse(ROOT / relative)
        except ET.ParseError as exc:
            fail(f"invalid XML in {relative}: {exc}")


def validate_component_contract() -> None:
    version = (ROOT / "version.php").read_text(encoding="utf-8")
    locallib = (ROOT / "locallib.php").read_text(encoding="utf-8")
    repository = (ROOT / "classes/local/feedback_repository.php").read_text(encoding="utf-8")
    upgrade = (ROOT / "db/upgrade.php").read_text(encoding="utf-8")
    providerregistry = (ROOT / "classes/local/provider/provider_registry.php").read_text(encoding="utf-8")

    if "$plugin->component = 'assignfeedback_aitutoria';" not in version:
        fail("version.php does not declare assignfeedback_aitutoria")
    if "class assign_feedback_aitutoria extends assign_feedback_plugin" not in locallib:
        fail("locallib.php does not expose the expected feedback plugin class")
    if "set_config('mode', 'human_review')" not in locallib:
        fail("human-review mode is not enforced")
    if "store_ai_suggestion" not in repository:
        fail("private AI suggestion storage boundary is missing")
    if "legacy_autograde_was_enabled" not in upgrade:
        fail("legacy autograde evidence is not preserved safely")
    if "return [];" not in providerregistry:
        fail("production provider registry must remain closed by default")


def validate_no_grade_writes() -> None:
    for path, text in iter_production_php_files():
        for pattern, description in GRADE_WRITE_PATTERNS.items():
            if re.search(pattern, text, flags=re.IGNORECASE | re.MULTILINE):
                fail(f"{description} detected in {path.relative_to(ROOT)}")


def validate_schema_contract() -> None:
    tree = ET.parse(ROOT / "db/install.xml")
    tables = {node.attrib["NAME"]: node for node in tree.findall(".//TABLE")}
    requiredtables = {
        "assignfeedback_aitutoria",
        "assignfeedback_aitutoria_job",
        "assignfeedback_aitutoria_snp",
        "assignfeedback_aitutoria_crt",
        "assignfeedback_aitutoria_hcr",
        "assignfeedback_aitutoria_aud",
    }
    missingtables = sorted(requiredtables - set(tables))
    if missingtables:
        fail("schema is missing tables: " + ", ".join(missingtables))

    feedbackfields = {node.attrib["NAME"] for node in tables["assignfeedback_aitutoria"].findall("./FIELDS/FIELD")}
    requiredfeedbackfields = {
        "id", "assignment", "grade", "feedbacktext", "feedbackformat", "aisuggestion",
        "aistatus", "decision", "model", "promptversion", "rubricversion", "timecreated", "timemodified",
    }
    missingfields = sorted(requiredfeedbackfields - feedbackfields)
    if missingfields:
        fail("feedback schema is missing fields: " + ", ".join(missingfields))

    jobfields = {node.attrib["NAME"] for node in tables["assignfeedback_aitutoria_job"].findall("./FIELDS/FIELD")}
    for required in ("idempotencykey", "status", "provider", "suggestiontext", "scoringjson", "attempts"):
        if required not in jobfields:
            fail(f"job schema is missing field: {required}")

    humanfields = {node.attrib["NAME"] for node in tables["assignfeedback_aitutoria_hcr"].findall("./FIELDS/FIELD")}
    for required in (
        "assignment", "grade", "jobid", "criterionkey", "selectedlevel",
        "aiproposedlevel", "matchesai", "reviewerid", "timecreated", "timemodified",
    ):
        if required not in humanfields:
            fail(f"human criterion schema is missing field: {required}")


def validate_forbidden_patterns() -> None:
    for path, text in iter_text_files():
        for pattern, description in FORBIDDEN_PATTERNS.items():
            if re.search(pattern, text, flags=re.IGNORECASE):
                fail(f"{description} found in {path.relative_to(ROOT)}")


def run_behavioral_tests() -> None:
    result = subprocess.run(
        ["php", str(ROOT / "tools/test_decision_policy.php")],
        check=False,
        capture_output=True,
        text=True,
    )
    if result.returncode != 0:
        fail(f"behavioral tests failed:\n{result.stdout}{result.stderr}")
    print(result.stdout.strip())


def main() -> None:
    validate_required_files()
    validate_php()
    validate_xml()
    validate_component_contract()
    validate_no_grade_writes()
    validate_schema_contract()
    validate_forbidden_patterns()
    run_behavioral_tests()
    print("Standalone plugin validation passed.")


if __name__ == "__main__":
    main()
