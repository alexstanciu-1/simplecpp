"""Measure fresh compiler-to-executable wall time and separate Clang traces on one host."""
import argparse
import json
import os
from pathlib import Path
import platform
import random
import shutil
import subprocess
import tempfile
import time

from workloads import write

HERE = Path(__file__).resolve().parent


def measured(args, **kwargs):
    start = time.perf_counter_ns()
    result = subprocess.run(args, capture_output=True, **kwargs)
    elapsed = (time.perf_counter_ns() - start) / 1e6
    if result.returncode:
        raise RuntimeError((args, result.stdout, result.stderr))
    return result, elapsed


def build(root, app, out, php, mold, level):
    flags = ["clang++", "-std=c++23", "-stdlib=libc++", "-g0", level,
             "-I" + str(root / "runtime/generated/public")]
    # All command and input setup precedes the clock. Report checks and traced
    # recompilations happen only after the uninstrumented executable is ready.
    command = [php, root / "compiler/tests/incremental_executable.php", app, out]
    start = time.perf_counter_ns()
    result, php_wall = measured(command, input=b'"run"\n')
    response = json.loads(result.stdout)
    if "error" in response:
        raise RuntimeError(response["error"])
    manifest = json.loads((out / "manifest.json").read_text())
    units = [out / path for path in manifest["translation_units"]]
    objects = [unit.with_suffix(".o") for unit in units]
    compile_ms = 0
    unit_times = {}
    for unit, obj in zip(units, objects):
        _, elapsed = measured([*flags, "-c", unit, "-o", obj])
        compile_ms += elapsed
        unit_times[str(unit.relative_to(out))] = elapsed
    _, link_ms = measured([*flags, "--ld-path=" + mold, *objects, *manifest["link_inputs"],
                           "-o", out / "program"])
    total = (time.perf_counter_ns() - start) / 1e6
    phases = {"Frontend": 0, "Backend": 0}
    traced_ms = 0
    execute_compiler_ms = 0
    for unit in units:
        path = out / "trace.json"
        _, elapsed = measured([*flags, "-ftime-trace=" + str(path), "-c", unit, "-o", out / "traced.o"])
        events = json.loads(path.read_text())["traceEvents"]
        traced_ms += elapsed
        execute_compiler_ms += next(e["dur"] for e in events if e["name"] == "Total ExecuteCompiler") / 1000
        for key in phases:
            phases[key] += next(e["dur"] for e in events if e["name"] == "Total " + key) / 1000
    return dict(compiler_ms=response["php_ms"], php_wall_ms=php_wall, compile_ms=compile_ms,
                link_ms=link_ms, total_ms=total, cpp_bytes=sum(u.stat().st_size for u in units),
                harness_ms=total-php_wall-compile_ms-link_ms, php_other_ms=php_wall-response["php_ms"],
                unit_wall_ms=unit_times, traced_wall_ms=traced_ms, execute_compiler_ms=execute_compiler_ms,
                **phases)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("v02", type=Path)
    parser.add_argument("output", type=Path)
    parser.add_argument("--php", default="php")
    args = parser.parse_args()
    root = args.v02.resolve()
    mold = shutil.which("ld.mold")
    if not mold:
        parser.error("ld.mold is required")
    os.environ["XDEBUG_MODE"] = "off"
    rng = random.Random(1301)
    rows = []
    names = ["access-log", "stock-ledger", "route-table"]
    with tempfile.TemporaryDirectory() as tmp:
        base = Path(tmp)
        for name in names:
            write(name, base / name / "large")
        for round_id in range(-1, 5):
            jobs = [(n, opt) for n in names for opt in ["-O0", "-O2"]]
            rng.shuffle(jobs)
            for name, opt in jobs:
                work = base / name / str(round_id) / opt
                app, out = work / "app", work / "out"
                app.mkdir(parents=True)
                for file in ["main.php", "module.json"]:
                    shutil.copyfile(HERE / name / file, app / file)
                row = build(root, app, out, args.php, mold, opt)
                for case in [HERE / name, base / name / "large"]:
                    result, _ = measured([out / "program"], cwd=case)
                    if result.stdout != (case / "expected.txt").read_bytes():
                        raise ValueError(f"{name} {opt}: report mismatch")
                if round_id >= 0:
                    rows.append(dict(name=name, level=opt, round=round_id, **row))
    def version(cmd):
        return subprocess.check_output(cmd, text=True).splitlines()[0]
    args.output.write_text(json.dumps(dict(host=platform.node(), php=version([args.php, "-v"]),
                                          clang=version(["clang++", "--version"]), mold=version([mold,"--version"]),
                                          samples=rows), indent=2) + "\n")


if __name__ == "__main__":
    main()
