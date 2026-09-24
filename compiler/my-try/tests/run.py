#!/usr/bin/env python3
"""Run the PHP suites and execute every LLVM fixture they emit."""

import argparse
import json
from pathlib import Path
import subprocess
import tempfile


def run(command, expected=0):
    result = subprocess.run(command, capture_output=True, text=True, timeout=120)
    if result.returncode != expected:
        raise RuntimeError(
            f"{command}: expected exit {expected}, got {result.returncode}\n"
            f"{result.stdout}\n{result.stderr}"
        )
    return result


def verify(root, output):
    clang = json.loads((root / "06_native/toolchain.json").read_text())["clang"]
    php_files = sorted(root.rglob("*.php"))
    for source in php_files:
        run(["php", "-l", str(source)])

    storage = run(["php", str(root / "tests/storage.php")])
    (output / "storage.log").write_text(storage.stdout + storage.stderr)
    ast = run(["php", str(root / "tests/ast.php")])
    (output / "ast.log").write_text(ast.stdout + ast.stderr)
    model = run(["php", str(root / "tests/model.php")])
    (output / "model.log").write_text(model.stdout + model.stderr)

    for suite in ("llvm", "calls"):
        directory = output / suite
        directory.mkdir()
        result = run(["php", str(root / "tests" / f"{suite}.php"), str(directory)])
        (output / f"{suite}.log").write_text(result.stdout + result.stderr)

    executions = json.loads((output / "llvm/executions.json").read_text())
    for fixture in executions:
        source = Path(fixture["path"])
        executable = source.with_suffix(".program")
        run([clang, "-Wno-override-module", "-x", "ir", str(source), "-o", str(executable)])
        run([str(executable)], fixture["exit_code"])

    sample = run(["php", str(root / "main.php")])
    (output / "sample.log").write_text(sample.stdout + sample.stderr)
    if "Native build: exit 0\n" not in sample.stdout or "Executable exit code: 9\n" not in sample.stdout:
        raise RuntimeError("Sample did not compile and execute with its expected exit code 9")

    calls = (output / "calls.log").read_text().count("dependencies verified, native exit")
    summary = {"php_lint_files": len(php_files), "llvm_executions": len(executions),
               "call_executions": calls, "sample_exit": 9}
    (output / "summary.json").write_text(json.dumps(summary, indent=2) + "\n")
    print(json.dumps(summary))


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--results", type=Path, help="New directory in which to retain evidence")
    arguments = parser.parse_args()
    root = Path(__file__).resolve().parents[1]
    if arguments.results:
        output = arguments.results.resolve()
        output.mkdir(parents=True, exist_ok=False)
        verify(root, output)
    else:
        with tempfile.TemporaryDirectory(prefix="scpp-my-try-proof-") as directory:
            verify(root, Path(directory))
