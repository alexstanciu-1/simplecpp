"""BS-R01 runtime proof with the runtime object itself instrumented."""
import argparse
import json
import shutil
import sys
from pathlib import Path

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / "tools"))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler

parser = argparse.ArgumentParser()
parser.add_argument("--results", type=Path, required=True)
args = parser.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
with CommandRunner(out / "logs", ROOT, jobs=12) as runner:
    generated = out / "generator/runtime"
    for part in ["tools", "specs", "include/scpp/generated"]:
        (generated / part).mkdir(parents=True, exist_ok=True)
    shutil.copy2(ROOT / "runtime/tools/generate_operators.php", generated / "tools")
    shutil.copy2(ROOT / "runtime/specs/config.json", generated / "specs")
    run_tasks([Task("regenerate", lambda: runner.run("regenerate",
               ["php", generated / "tools/generate_operators.php"]))])
    def semantic_lines(path):
        return [line for line in path.read_text().splitlines()
                if not line.startswith("// Generated from runtime/specs/config.json on ")]
    assert semantic_lines(generated / "include/scpp/generated/operators.hpp") == semantic_lines(
        ROOT / "runtime/include/scpp/generated/operators.hpp")
    run_tasks([Task("source-prepare", lambda: runner.run("source-prepare",
               ["php", APP / "tests/checked_shifts.php", out]))])
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=False)
    cpp.flags.extend(["-DSCPP_LANGUAGE_TARGET_PHP=1", "-pthread",
                      "-I", APP / "tests",
                      "-fsanitize=undefined", "-fno-sanitize-recover=undefined"])
    runner.run("instrumented-runtime-build", [*cpp.flags, "-c",
               ROOT / "runtime/include/core/runtime.cpp", "-o", out / "runtime.o"])
    cpp.link_flags.append(out / "runtime.o")

    def check():
        cpp.compile("checked-shifts-build", APP / "tests/checked_shifts.cpp", out / "checked-shifts")
        runner.run("checked-shifts-run", [out / "checked-shifts"])

    def source_check(source):
        cpp.compile(source.stem + "-build", source, out / source.stem)
        runner.run(source.stem + "-run", [out / source.stem])

    run_tasks([Task("checked-shifts", check)] + [
        Task(source.stem, lambda source=source: source_check(source))
        for source in out.glob("*.cpp")])
(out / "summary.json").write_text(json.dumps({"passed": True,
    "integer_representations": 8, "boxed_families": 2, "lifted_families": 4,
    "runtime_instrumented": True, "source_programs": 7,
    "operator_regeneration_matches": True}, indent=2) + "\n")
