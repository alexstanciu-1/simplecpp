#!/usr/bin/env python3
"""Build a compiler-only Rust export and report wall times; never edit the working sources.

Run from compiler/ with the pinned toolchain and host cc/mold on PATH.
The temporary export contains no API tooling, C++ source, runtime, generated API or build script.
"""
import json
import os
from pathlib import Path
import platform
import shutil
import subprocess
import tempfile
import time

workspace = Path(__file__).resolve().parents[2]


def output(command, cwd):
    return subprocess.check_output(command, cwd=cwd, text=True).strip()


with tempfile.TemporaryDirectory(prefix="scpp-rust-foundation-") as temporary:
    root = Path(temporary)
    for name in ["rust-toolchain.toml", ".cargo/config.toml"]:
        destination = root / name
        destination.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(workspace / name, destination)
    (root / "Cargo.toml").write_text('[workspace]\nmembers = ["compiler"]\nresolver = "3"\n')
    for crate in ["compiler"]:
        (root / crate).mkdir(parents=True)
        shutil.copy2(workspace / crate / "Cargo.toml", root / crate / "Cargo.toml")
        for directory in ["src", "tests"]:
            (root / crate / directory).mkdir()
            for source in (workspace / crate / directory).rglob("*.rs"):
                destination = root / crate / directory / source.relative_to(workspace / crate / directory)
                destination.parent.mkdir(parents=True, exist_ok=True)
                shutil.copy2(source, destination)
    subprocess.run(["cargo", "generate-lockfile", "--offline"], cwd=root, check=True, capture_output=True)
    cpu = platform.processor()
    if Path("/proc/cpuinfo").exists():
        cpu = next((line.split(":", 1)[1].strip() for line in Path("/proc/cpuinfo").read_text().splitlines() if line.startswith("model name")), cpu)
    report = {
        "rustc": output(["rustc", "--version", "--verbose"], root),
        "cargo": output(["cargo", "--version"], root),
        "platform": platform.platform(),
        "cpu": cpu,
        "logical_cpus": os.cpu_count(),
        "build_jobs": 8,
        "dependencies": "standard library only; installed toolchain; no external packages or downloads",
        "cache_conditions": "fresh exported sources and Cargo targets; OS filesystem cache not flushed; one run per measurement",
        "seconds": {},
    }
    environment = os.environ.copy()
    environment.pop("CARGO_BUILD_TARGET", None)
    environment["CARGO_TARGET_DIR"] = str(root / "target")

    def measure(label, arguments, target=None):
        env = environment.copy()
        if target:
            env["CARGO_TARGET_DIR"] = str(root / target)
        start = time.perf_counter()
        result = subprocess.run(["cargo", *arguments, "-p", "scpp-compiler", "--locked", "--offline"], cwd=root, env=env, text=True, capture_output=True)
        if result.returncode:
            raise RuntimeError(result.stdout + result.stderr)
        report["seconds"][label] = round(time.perf_counter() - start, 3)

    measure("clean_debug_build", ["build"])
    measure("unchanged_debug_build", ["build"])
    main = root / "compiler/src/main.rs"
    original = main.read_text()
    edited = original.replace("(PHS scanning; no compilation yet)", "(timing probe; PHS scanning; no compilation yet)")
    assert edited != original
    main.write_text(edited)
    measure("incremental_cli_text_edit", ["build"])
    main.write_text(original)
    measure("cold_cargo_check", ["check"], "check-target")
    measure("warm_cargo_check", ["check"], "check-target")
    measure("test_build_and_run", ["test"])
    measure("warm_foundation_tests", ["test", "--test", "foundation"])
    measure("release_build", ["build", "--release"])
    # Warm compiler checking/building before separate source-edit measurements.
    measure("compiler_debug_warmup", ["build"])
    measure("compiler_check_warmup", ["check"])
    for label, relative, old, new in [
        ("compiler_model", "compiler/src/02_tokenize/token_sequence.rs", "token span is outside the retained source", "token span exceeds the retained source"),
        ("scanner", "compiler/src/02_tokenize/scanner.rs", "Unsupported token", "Unsupported source token"),
    ]:
        subprocess.run(["cargo", "check", "--workspace", "--locked", "--offline"], cwd=root, env=environment, check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        source = root / relative
        saved = source.read_text()
        assert old in saved
        source.write_text(saved.replace(old, new))
        measure(label + "_edit_check", ["check"])
        measure(label + "_edit_debug_build", ["build"])
        source.write_text(saved)
        # Restore/warm outside the edit measurement; no repository source is modified.
        subprocess.run(["cargo", "build", "--workspace", "--locked", "--offline"], cwd=root, env=environment, check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    subprocess.run(["cargo", "test", "--workspace", "--locked", "--offline"], cwd=root, env=environment, check=True, stdout=subprocess.DEVNULL)
    print(json.dumps(report, indent=2))
