"""Shared 12-slot regression pool for touched declaration and operator owners."""
import argparse
import json
import sys
from pathlib import Path

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / "tools"))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler
from enums import native_checks

parser = argparse.ArgumentParser()
parser.add_argument("--results", type=Path, required=True)
args = parser.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
records = ["records", "property_defaults", "data_records", "property_mutations"]
php_suites = ["declaration_model", "member_owners", "relationships", "ast",
    "ast_invariants", "parse_collection", "collected_roles", "preparation_work",
    "preparation_recovery", "incremental_cpp", "type_catalog", "enum_model",
    "reference_model", "token_cleanup", "project_cpp", "specialization_dispatch",
    "mixed", "dynamic_boundaries", "nullable", "results", "operators", "mutations",
    "compound_assignments"]
with CommandRunner(out / "logs", ROOT, jobs=12) as runner:
    def export(name, script=None):
        directory = out / name
        directory.mkdir()
        runner.run(name + "-export", ["php", APP / "tests" / ((script or name) + ".php"), directory])

    run_tasks([Task(name, lambda name=name: runner.run(name, ["php", APP / "tests" / (name + ".php")]))
               for name in php_suites] +
              [Task(name, lambda name=name: export(name)) for name in records + ["enums"]] +
              [Task("boxed", lambda: export("boxed", "boxed_selection_regressions"))])
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    # Preserve the original enum harness's standalone compilation setup.
    enum_cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=False)
    tasks = []
    count = 0

    def execute(suite, row):
        source = Path(row["path"])
        name = suite + "-" + source.stem
        executable = out / name
        cpp.compile(name + "-build", source, executable)
        stdout = runner.run(name + "-run", [executable], expected=row["exit_code"])
        if "stdout" in row:
            assert stdout == row["stdout"].encode(), (name, stdout)
        if "stderr_contains" in row:
            assert row["stderr_contains"] in (out / "logs" / (name + "-run.stderr")).read_text()

    for suite in records + ["boxed"]:
        rows = json.loads((out / suite / "executions.json").read_text())
        for row in rows:
            tasks.append(Task(suite + "-" + Path(row["path"]).stem,
                              lambda suite=suite, row=row: execute(suite, row)))
            count += 1

    def enumeration(source):
        name = "enum-" + source.stem
        bundle = native_checks(source.stem, json.loads(source.read_text()))
        enum_cpp.compile_artifacts(name + "-build", json.dumps(bundle).encode(), out / name)
        # The generated entry catches this exception and exits 1, also on the
        # pinned pre-goal compiler. enums.py's older SIGABRT expectation is
        # recorded separately in the closeout rather than changing the compiler.
        runner.run(name + "-run", [out / name], expected=1 if source.stem == "enum_unhandled" else 7)
        if source.stem == "enum_unhandled":
            assert "Unhandled match expression" in (out / "logs" / (name + "-run.stderr")).read_text()

    for source in sorted((out / "enums").glob("*.json")):
        tasks.append(Task("enum-" + source.stem, lambda source=source: enumeration(source)))
        count += 1
    run_tasks(tasks)
(out / "summary.json").write_text(json.dumps({"passed": True,
    "php_checks": len(php_suites) + len(records) + 2, "native_programs": count,
    "full_matrix_run": False}, indent=2) + "\n")
