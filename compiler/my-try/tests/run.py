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
    run(["python3", str(root / "tools/style_check.py")])
    php_files = sorted(p for p in root.rglob("*.php") if "build" not in p.relative_to(root).parts)
    for source in php_files:
        run(["php", "-l", str(source)])

    storage = run(["php", str(root / "tests/storage.php")])
    (output / "storage.log").write_text(storage.stdout + storage.stderr)
    tokenizer = run(["php", str(root / "tests/tokenizer.php")])
    (output / "tokenizer.log").write_text(tokenizer.stdout + tokenizer.stderr)
    ast = run(["php", str(root / "tests/ast.php")])
    (output / "ast.log").write_text(ast.stdout + ast.stderr)
    access = run(["php", str(root / "tests/structure_access.php")])
    (output / "structure_access.log").write_text(access.stdout + access.stderr)
    invariants = run(["php", str(root / "tests/ast_invariants.php")])
    (output / "ast_invariants.log").write_text(invariants.stdout + invariants.stderr)
    incremental = run(["php", str(root / "tests/incremental.php")])
    (output / "incremental.log").write_text(incremental.stdout + incremental.stderr)
    pipeline = run(["php", str(root / "tests/pipeline.php")])
    (output / "pipeline.log").write_text(pipeline.stdout + pipeline.stderr)
    publication = run(["php", str(root / "tests/publication.php")])
    (output / "publication.log").write_text(publication.stdout + publication.stderr)
    model = run(["php", str(root / "tests/model.php")])
    (output / "model.log").write_text(model.stdout + model.stderr)

    text = run(["php", str(root / "tests/llvm_text.php")])
    (output / "llvm_text.log").write_text(text.stdout + text.stderr)
    native = run(["php", str(root / "tests/native.php")])
    (output / "native.log").write_text(native.stdout + native.stderr)

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

    s2s = output / "s2s"
    s2s.mkdir()
    proof = run(["php", str(root / "tests/s2s.php"), str(s2s)])
    (output / "s2s.log").write_text(proof.stdout + proof.stderr)
    s2s_cases = json.loads((s2s / "executions.json").read_text())
    for fixture in s2s_cases:
        source = Path(fixture["path"])
        executable = source.with_suffix(".program")
        run(["clang++", "-std=c++20", "-I", str(root.parents[1] / "runtime/include"), str(source), "-o", str(executable)])
        run([str(executable)], fixture["exit_code"])

    # The single host entry exposes S2S without the report banner or diagnostics on stdout.
    cli_source = output / "cli-source"
    cli_source.mkdir()
    (cli_source / "main.phs").write_text("$a = 10; return $a;")
    cli = run(["php", str(root / "main.php"), "--s2s", str(cli_source)])
    if cli.stdout != (s2s / "value.cpp").read_text() or cli.stderr:
        raise RuntimeError("Host S2S mode differs from direct generation")
    (output / "s2s-cli.log").write_text(cli.stdout)
    for arguments in (["--s2s"], ["--unknown"], ["--s2s", str(cli_source), "extra"]):
        bad_usage = run(["php", str(root / "main.php"), *arguments], expected=1)
        if bad_usage.stdout or not bad_usage.stderr.startswith("Usage:"):
            raise RuntimeError("Host usage failure did not stay on stderr")
    missing = run(["php", str(root / "main.php"), "--s2s", str(output / "missing-source")], expected=1)
    if missing.stdout or not missing.stderr.startswith("S2S generation failed:"):
        raise RuntimeError("Host S2S failure did not stay on stderr")

    sample = run(["php", str(root / "main.php")])
    (output / "sample.log").write_text(sample.stdout + sample.stderr)
    if "Native build: exit 0\n" not in sample.stdout or "Executable exit code: 9\n" not in sample.stdout:
        raise RuntimeError("Sample did not compile and execute with its expected exit code 9")

    calls = (output / "calls.log").read_text().count("dependencies verified, native exit")
    summary = {"php_lint_files": len(php_files), "llvm_executions": len(executions),
               "call_executions": calls, "sample_exit": 9, "s2s_executions": len(s2s_cases)}
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
