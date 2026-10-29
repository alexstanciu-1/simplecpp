#!/usr/bin/env python3
"""Build a Rust-only export and report wall times; never edit the working sources.

Run from compiler/ with the pinned toolchain and host cc/mold on PATH.
The temporary export contains no C++ source, runtime, generated API or build script.
"""
import json
import os
from pathlib import Path
import platform
import shutil
import subprocess
import tempfile
import time

package = Path(__file__).resolve().parents[1]


def output(command, cwd):
    return subprocess.check_output(command, cwd=cwd, text=True).strip()


with tempfile.TemporaryDirectory(prefix="scpp-rust-foundation-") as temporary:
    root = Path(temporary)
    for name in ["Cargo.toml", "Cargo.lock", "rust-toolchain.toml", ".cargo/config.toml"]:
        destination = root / name
        destination.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(package / name, destination)
    for directory in ["src", "tests"]:
        (root / directory).mkdir()
        for source in (package / directory).glob("*.rs"):
            shutil.copy2(source, root / directory / source.name)
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
        "dependencies": "standard library only; installed toolchain; no downloads",
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
        result = subprocess.run(["cargo", *arguments, "--locked", "--offline"], cwd=root, env=env, text=True, capture_output=True)
        if result.returncode:
            raise RuntimeError(result.stdout + result.stderr)
        report["seconds"][label] = round(time.perf_counter() - start, 3)

    measure("clean_debug_build", ["build"])
    measure("unchanged_debug_build", ["build"])
    main = root / "src/main.rs"
    original = main.read_text()
    edited = original.replace("(no scanning or compilation yet)", "(timing probe; no scanning or compilation yet)")
    assert edited != original
    main.write_text(edited)
    measure("incremental_cli_text_edit", ["build"])
    main.write_text(original)
    measure("cold_cargo_check", ["check"], "check-target")
    measure("warm_cargo_check", ["check"], "check-target")
    measure("test_build_and_run", ["test"])
    measure("warm_foundation_tests", ["test", "--test", "foundation"])
    measure("release_build", ["build", "--release"])
    print(json.dumps(report, indent=2))
