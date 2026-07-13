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
    "classes/local/decision_policy.php",
    "classes/local/feedback_repository.php",
    "classes/privacy/provider.php",
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

IGNORED_DIRS = {".git", "vendor", "node_modules", "dist", ".venv"}


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

    prohibited_grade_writes = (
        "insert_record('assign_grades'",
        'insert_record("assign_grades"',
        "update_record('assign_grades'",
        'update_record("assign_grades"',
        "delete_records('assign_grades'",
        'delete_records("assign_grades"',
    )
    for marker in prohibited_grade_writes:
        if marker in "\n".join((locallib, repository, upgrade)):
            fail("direct numeric-grade mutation detected")


def validate_schema_contract() -> None:
    tree = ET.parse(ROOT / "db/install.xml")
    table = tree.find(".//TABLE[@NAME='assignfeedback_aitut_fb']")
    if table is None:
        fail("canonical feedback table is missing")

    fields = {node.attrib["NAME"] for node in table.findall("./FIELDS/FIELD")}
    required_fields = {
        "id",
        "assignment",
        "grade",
        "feedbacktext",
        "feedbackformat",
        "aisuggestion",
        "aistatus",
        "decision",
        "model",
        "promptversion",
        "rubricversion",
        "timecreated",
        "timemodified",
    }
    missing = sorted(required_fields - fields)
    if missing:
        fail("schema is missing fields: " + ", ".join(missing))


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
    validate_schema_contract()
    validate_forbidden_patterns()
    run_behavioral_tests()
    print("Standalone plugin validation passed.")


if __name__ == "__main__":
    main()
