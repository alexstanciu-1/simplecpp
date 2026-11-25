"""Replay original strict curl fixtures against an explicit pinned v0.1 checkout."""
import argparse
import hashlib
import json
from pathlib import Path
import sys

from curl_runtime import fixture_server

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / "tools"))
from test_runner import CommandRunner, Task, run_tasks


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--baseline", required=True, type=Path)
    parser.add_argument("--results", required=True, type=Path)
    args = parser.parse_args()
    baseline = args.baseline.resolve()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    records = {}
    with CommandRunner(out / "logs", ROOT, jobs=12) as runner:
        composition = out / "runtime.cpp"
        composition.write_text("".join('#include "' + name + '"\n' for name in [
            "core/runtime.cpp", "modules/json/json.cpp", "modules/datetime/datetime.cpp",
            "modules/mysql/mysql_module.cpp", "lang/php/php_filesystem.cpp",
            "lang/php/php_json.cpp", "lang/php/php_mysqli.cpp", "lang/php/php_regex.cpp",
            "modules/curl/curl.cpp",
        ]))
        flags = ["clang++", "-std=c++23", "-DSCPP_LANGUAGE_TARGET_PHP=1",
                 "-DSCPP_HAS_CURL=1", "-I", baseline / "runtime/include", "-pthread"]
        runtime = out / "runtime.o"
        run_tasks([Task("runtime", lambda: runner.run("runtime-build", [
            *flags, "-c", composition, "-o", runtime], timeout=300))])

        def verify(path):
            name = path.stem
            folder = out / name
            folder.mkdir()
            info = json.loads(path.with_suffix(".test-info.json").read_text())
            generated = runner.run(name + "-generate", [
                "php", APP / "tests/compatibility_export.php", baseline, path, "strict"],
                timeout=120)
            source = folder / "program.cpp"
            source.write_bytes(generated)
            executable = folder / "program"
            runner.run(name + "-build", [
                *flags, source, runtime, "-lcurl", "-lpcre2-8", "-lmariadb",
                "-o", executable], timeout=300)
            row = dict(source=str(path.relative_to(baseline)),
                       sha256=hashlib.sha256(path.read_bytes()).hexdigest(),
                       generated=True, compiled=True, execution="skipped-external-opt-in")
            if "github_raw" not in name:
                work = folder / "cwd"
                work.mkdir()
                if "http_server" in info.get("build", {}):
                    with fixture_server(folder) as base:
                        actual = runner.run(name + "-execute", [executable, base],
                                            cwd=work, timeout=60)
                else:
                    actual = runner.run(name + "-execute", [executable], cwd=work, timeout=60)
                assert actual == info["expect"]["run"]["stdout"].encode(), (name, actual)
                assert not list(work.iterdir()), (name, "filesystem effects remain")
                row["execution"] = "passed"
                row["stdout"] = actual.decode()
            records[name] = row
            (folder / "result.json").write_text(json.dumps(row, indent=2) + "\n")
        run_tasks([Task(path.stem, lambda path=path: verify(path))
                   for path in sorted((baseline / "tests/php/curl/strict_level_01").glob("*.phs"))])
    (out / "summary.json").write_text(json.dumps({
        "passed": True, "baseline_directory": str(baseline),
        "cases": records, "external_execution": "not requested; compile-only",
    }, indent=2) + "\n")


if __name__ == "__main__":
    main()
