"""Build with both native compilers and run one process at a time."""
import csv
import os
from pathlib import Path
import random
import statistics
import subprocess
import sys
import tempfile

source = Path(__file__).with_name("loop.cpp")
groups = {}
for row in subprocess.check_output(["lscpu", "-p=CPU,CORE,SOCKET"], text=True).splitlines():
    if row.startswith("#"):
        continue
    cpu, core, socket = map(int, row.split(","))
    if cpu in os.sched_getaffinity(0):
        groups.setdefault((socket, core), []).append(cpu)
cores = sorted(groups.values(), key=min)
physical = [c[0] for c in cores]
spread = physical + [cpu for c in cores for cpu in c[1:]]
print("core groups", cores, file=sys.stderr)
cases = [("spread", spread[:n]) for n in (1, 2, 4, 6, 8)]
if len(cores[0]) >= 2:
    cases.append(("siblings", cores[0][:2]))
cases.append(("same-cpu", [physical[0]] * 2))
iterations = 16_000_000 if "--long" in sys.argv else 4_000_000
rounds = 8
if "--placement" in sys.argv:
    if len(physical) < 16:
        raise SystemExit("placement sweep requires 16 physical cores")
    iterations = 16_000_000
    rounds = 6
    cases = [("spread", physical[:1]), ("spread", physical[:4]), ("spread", physical[:8]),
             ("upper", physical[8:16]), ("alternating", physical[::2][:8]),
             ("alternating", physical[::2][:4])]
small = "--batch" in sys.argv
traced = "--trace" in sys.argv
if small:
    iterations = 160_000 if "--fourfold" in sys.argv else 40_000
    rounds = 32
    cases = [("spread", spread[:n]) for n in (1, 2, 4, 8)]
matched = "--600ms" in sys.argv
if matched:
    cases = [(role, spread[:n]) for role in ("participate", "wait") for n in (1, 2, 4, 8)]
locality = "--locality" in sys.argv
writes = "--writes" in sys.argv
records = "--records" in sys.argv or locality or writes
if records:
    modes = ("field", "write") if writes else ("field", "index")
    cases = [(mode, spread[:n]) for mode in modes for n in (1, 2, 4, 8)]
if locality:
    cases = [("field", spread[:1]), ("tiles", spread[:1]), ("field", spread[:8])]
profile_pool = "--profile-pool" in sys.argv
compare_pool = "--compare-pool" in sys.argv
efficiency = "--efficiency" in sys.argv
pool = "--pool" in sys.argv or profile_pool or compare_pool or efficiency
eight = "--eight-passes" in sys.argv
peer_test = "--peer-barrier" in sys.argv
tail_trace = "--tail-trace" in sys.argv
dispatch = "--dispatch" in sys.argv or pool or eight or peer_test or tail_trace
if dispatch:
    source = source.with_name("dispatch.cpp")
    iterations = 5801
    schedules = ("batch", "per_pass", "pool") if pool else ("batch", "per_pass")
    if profile_pool or compare_pool:
        schedules = ("pool", "active", "per_pass") if profile_pool else ("pool", "active", "batch")
    if efficiency:
        schedules = ("pool", "active")
    if eight:
        iterations, schedules = 8, ("active",)
    if peer_test or tail_trace:
        schedules = ("active",) if tail_trace else ("active", "peers")
    cases = [(mode, spread[:n]) for mode in schedules for n in (1, 2, 4, 8)]
if efficiency:
    from efficiency_summary import report
    efficiency_rows = []
writer = csv.writer(sys.stdout)
columns = "compiler round placement workers cpus iterations wall_s cpu_s checksum".split()
if dispatch:
    columns[2], columns[5] = "dispatch", "passes"
if records:
    columns[2] = "kernel"
    columns[5] = "passes"
if efficiency:
    columns += "sample_wall_us longest_kernel_us summed_kernel_us".split()
if profile_pool:
    columns += "launch_us caller_us wait_us wait_cpu_us worker_delay_us worker_kernel_cpu_us".split()
if eight:
    columns.append("steps_per_record")
if tail_trace:
    columns += ["outstanding_us", "after_finish_us"]
writer.writerow(columns + (["first_s", "start_spread_s", "tail_s"] if traced else []))
with tempfile.TemporaryDirectory() as tmp:
    binaries = {}
    for compiler in ("clang++", "g++"):
        print(subprocess.check_output([compiler, "--version"], text=True).splitlines()[0], file=sys.stderr)
        binaries[compiler] = str(Path(tmp) / compiler)
        subprocess.run([compiler, "-O3", "-std=c++20", "-pthread", *(["-DEFFICIENCY_TRACE"] if efficiency else []), *(["-DTAIL_TRACE"] if tail_trace else []), *(["-DEIGHT_PASSES"] if eight else []), *(["-DPROFILE_DISPATCH"] if profile_pool else []), *(["-DTRACE_LOOP"] if traced else []), *(["-DRECORD_SWEEP"] if records else []), source, "-o", binaries[compiler]], check=True)
    env = os.environ.copy()
    env.pop("LOOP_PEER_BARRIER", None)
    env.pop("LOOP_ACTIVE", None)
    env.pop("LOOP_POOL", None)
    env.pop("LOOP_PER_PASS", None)
    env.pop("LOOP_WAIT_ONLY", None)
    if eight:
        steps = 800
        for _ in range(2):
            probe_env = dict(env, LOOP_ACTIVE="1", LOOP_RECORD_STEPS=str(steps))
            probes = [float(subprocess.check_output(
                [binary, str(iterations), str(physical[0])], env=probe_env, text=True
            ).split(",")[0]) for _ in range(2) for binary in binaries.values()]
            steps = max(1, round(steps * 0.600 / statistics.median(probes)))
        env["LOOP_RECORD_STEPS"] = str(steps)
    if matched:
        probe_iterations = 16_000_000
        samples = [float(subprocess.check_output(
            [binary, str(probe_iterations), str(physical[0])], text=True, env=env
        ).split(",")[0]) for _ in range(3) for binary in binaries.values()]
        iterations = round(probe_iterations * 0.600 / statistics.median(samples))
    env.pop("LOOP_WRITE_BACK", None)
    env.pop("LOOP_INDEX_CONTROL", None)
    env.pop("LOOP_SERIAL_TILES", None)
    passes = {}
    if locality:
        with open(sys.argv[sys.argv.index("--locality") + 1]) as previous:
            for sample in csv.DictReader(previous):
                passes[sample["compiler"]] = int(sample["passes"])
    elif writes and "--passes" in sys.argv:
        count = int(sys.argv[sys.argv.index("--passes") + 1])
        if count < 1:
            raise ValueError("expected a positive pass count")
        passes = dict.fromkeys(modes, count)
    elif writes:
        for mode in modes:
            probe = 2000
            probe_env = dict(env, LOOP_WRITE_BACK="1") if mode == "write" else env
            for _ in range(2):
                samples = [float(subprocess.check_output(
                    [binary, str(probe), str(physical[0])], text=True, env=probe_env
                ).split(",")[0]) for _ in range(2) for binary in binaries.values()]
                probe = max(1, round(probe * 0.600 / statistics.median(samples)))
            passes[mode] = probe
    elif records:
        for cc, binary in binaries.items():
            probe = 2000
            for _ in range(2):
                samples = [float(subprocess.check_output(
                    [binary, str(probe), str(physical[0])], text=True, env=env
                ).split(",")[0]) for _ in range(3)]
                probe = max(1, round(probe * 0.600 / statistics.median(samples)))
            passes[cc] = probe
        # Common work across compilers whenever their calibrated counts agree closely.
        if max(passes.values()) < 1.06 * min(passes.values()):
            passes = dict.fromkeys(binaries, round(statistics.mean(passes.values())))
    rng = random.Random(20261124)
    expected = {}
    for round_id in range(rounds):
        jobs = [(cc, placement, cpus) for cc in binaries for placement, cpus in cases]
        rng.shuffle(jobs)
        for cc, placement, cpus in jobs:
            worker_env = dict(env, LOOP_WAIT_ONLY="1") if placement == "wait" else env
            if dispatch and placement == "per_pass":
                worker_env = dict(env, LOOP_PER_PASS="1")
            if dispatch and placement == "active":
                worker_env = dict(env, LOOP_ACTIVE="1")
            if dispatch and placement == "peers":
                worker_env = dict(env, LOOP_ACTIVE="1", LOOP_PEER_BARRIER="1")
            if dispatch and placement == "pool":
                worker_env = dict(env, LOOP_POOL="1")
            if records:
                iterations = passes[placement] if writes else passes[cc]
                if placement == "write":
                    worker_env = dict(env, LOOP_WRITE_BACK="1")
                if placement == "tiles":
                    worker_env = dict(env, LOOP_SERIAL_TILES="1")
                if placement == "index":
                    worker_env = dict(env, LOOP_INDEX_CONTROL="1")
            result = subprocess.check_output([binaries[cc], str(iterations), *map(str, cpus)], text=True, env=worker_env)
            wall, cpu, checksum, *trace = result.strip().split(",")
            if checksum != expected.setdefault((iterations, placement == "write"), checksum):
                raise ValueError("checksum mismatch")
            row = [cc, round_id, placement, len(cpus), "/".join(map(str, cpus)), iterations, wall, cpu, checksum, *trace, *([steps] if eight else [])]
            writer.writerow(row)
            if efficiency:
                efficiency_rows.append(dict(zip(columns, row, strict=True)))

if efficiency:
    report(efficiency_rows, sys.stderr)
