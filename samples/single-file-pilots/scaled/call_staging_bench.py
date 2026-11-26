"""Benchmark the two audited statement-call rewrites independently on the 100 KB pilots."""
import argparse
from collections import Counter
import hashlib
import json
import os
from pathlib import Path
import platform
import random
import re
import shutil
import tempfile
import time

from generate import BASE, HERE
from ninja_split import run
from verify_workload import fixture

SITES = {"access-log": (639, 31), "stock-ledger": (574, 33), "route-table": (560, 31)}
MODES = ("current", "blocks", "direct")
CALL = re.compile(r"^(\s*)\(\[&\]\(\) -> void \{ (.+); return ([^;]+); \}\(\)\);$", re.M)
OWNER = re.compile(r"owner_\w+ argument_\d+_\d+ = (var_\w+)")


def variant(cpp, name, mode):
    counts = Counter()

    def rewrite(m):
        indent, steps, call = m.groups()
        counts["blocks"] += 1
        target = call.split("(", 1)[0]
        if "_by_" in target:
            args = [OWNER.fullmatch(step) for step in steps.split("; ")]
            assert len(args) == 2 and all(args)
            counts["direct"] += 1
            if mode == "direct":
                return f"{indent}{target}({args[0][1]}, {args[1][1]});"
        if mode == "blocks":
            return f"{indent}{{ {steps}; {call}; }}"
        return m[0]

    output = CALL.sub(rewrite, cpp)
    assert (counts["blocks"], counts["direct"]) == SITES[name]
    return output


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("v02", type=Path)
    parser.add_argument("output", type=Path)
    parser.add_argument("--php", default="php")
    parser.add_argument("--new-compiler", type=Path, help="Compare two real compiler directories without rewriting C++")
    args = parser.parse_args()
    root = args.v02.resolve()
    os.environ["XDEBUG_MODE"] = "off"
    flags = ["clang++", "-std=c++23", "-stdlib=libc++", "-O2", "-g0",
             "-I" + str(root / "runtime/generated/public")]
    link = ["clang++", "-stdlib=libc++", "--ld-path=" + shutil.which("ld.mold")]
    rng = random.Random(4127)
    jobs = [(name, mode) for name in SITES for mode in (("current", "compiler-blocks") if args.new_compiler else MODES)]
    builds, traces, cpu = [], [], []
    clock = time.perf_counter
    with tempfile.TemporaryDirectory() as directory:
        temp = Path(directory)
        for name in SITES:
            for case in ("shared", "full"):
                fixture(name, 100, temp / name / case, case == "full")
        for iteration in range(-1, 5):
            rng.shuffle(jobs)
            for name, mode in jobs:
                w = temp / name / mode / str(iteration)
                app, out = w / "app", w / "out"
                app.mkdir(parents=True)
                for file in ("main.php", "module.json"):
                    shutil.copyfile(HERE / "100k" / name / file, app / file)
                compiler = args.new_compiler.resolve() if mode == "compiler-blocks" else root / "compiler"
                start = clock()
                info = json.loads(run([args.php, compiler / "tests/incremental_executable.php",
                                       app, out], input=b'"run"\n').stdout)
                php_done = clock()
                manifest = json.loads((out / "manifest.json").read_text())
                unit = out / next(iter(manifest["sources"].values()))
                if not args.new_compiler:
                    unit.write_text(variant(unit.read_text(), name, mode))
                prepared = clock()
                run([*flags, "-c", unit, "-o", w / "app.o"])
                compiled = clock()
                run([*flags, "-c", out / "entry.cpp", "-o", w / "entry.o"])
                entry_done = clock()
                run([*link, w / "app.o", w / "entry.o", *manifest["link_inputs"], "-o", w / "exe"])
                finish = clock()
                for case_dir in (BASE / name, temp / name / "shared", temp / name / "full"):
                    assert run([w / "exe"], cwd=case_dir).stdout == (case_dir / "expected.txt").read_bytes()
                if iteration >= 0:
                    builds.append(dict(sample=name, mode=mode, round=iteration, compiler_ms=info["php_ms"],
                        php_other_ms=(php_done-start)*1000-info["php_ms"], probe_ms=(prepared-php_done)*1000,
                        app_ms=(compiled-prepared)*1000, entry_ms=(entry_done-compiled)*1000,
                        link_ms=(finish-entry_done)*1000, total_ms=(finish-start)*1000,
                        cpp_bytes=unit.stat().st_size+(out/"entry.cpp").stat().st_size,
                        app_sha256=hashlib.sha256(unit.read_bytes()).hexdigest()))
                if iteration in (0, 2, 4):
                    trace = w / "trace.json"
                    run([*flags, "-ftime-trace="+str(trace), "-c", unit, "-o", w / "traced.o"])
                    totals = {e["name"]: e["dur"]/1000 for e in json.loads(trace.read_text())["traceEvents"]
                              if e["name"] in ("Total Frontend", "Total Optimizer", "Total CodeGenPasses")}
                    traces.append(dict(sample=name, mode=mode, round=iteration, **totals))
                if iteration == 0:
                    run([*flags, link[-1], BASE / "runtime.cpp", w / "app.o", *manifest["link_inputs"], "-o", w / "timing"])
            print(f"Build round {iteration} complete", flush=True)
        cases = [(n, m, c) for n, m in jobs for c in ("shared", "full")]
        for iteration in range(7):
            rng.shuffle(cases)
            for name, mode, case in cases:
                home = temp / name
                timing = run(["taskset", "-c", "8", home / mode / "0/timing", home / case / "input.txt", "24"])
                ns = int(timing.stderr.split()[0])
                cpu.append(dict(round=iteration, sample=name, mode=mode, case=case, cpu_ms=ns/(24*1e6)))
    data = dict(host=platform.node(), clang=run(["clang++", "--version"]).stdout.decode().splitlines()[0],
                php=run([args.php, "-v"]).stdout.decode().splitlines()[0], flags=flags, link=link,
                builds=builds, traces=traces, cpu=cpu)
    args.output.write_text(json.dumps(data, indent=2)+"\n")


if __name__ == "__main__":
    main()
