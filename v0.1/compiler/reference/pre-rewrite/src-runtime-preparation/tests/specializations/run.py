#!/usr/bin/env python3
"""Bounded source/native-family proof. No compiler stages are imported or changed."""
import argparse
from concurrent.futures import ThreadPoolExecutor
import fcntl
import hashlib
import json
from pathlib import Path
import platform
import shutil
import statistics
import subprocess
import time

from consumer import Consumer

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[2]


def command(args, log=None):
    result = subprocess.run([str(x) for x in args], text=True, capture_output=True, timeout=180)
    if log:
        Path(log).write_text(json.dumps([str(x) for x in args]) + "\n" + result.stdout + result.stderr)
    if result.returncode:
        raise RuntimeError(f"Command failed ({result.returncode}): {args}\n{result.stderr[-5000:]}")
    return result.stdout


def prepare(work, request=HERE / "request.json"):
    print(f"Preparing {request.name} ...", flush=True)
    command(["php", HERE / "prepare.php", work, request], work / "prepare.log")
    accepted = json.loads((work / "accepted.json").read_text())
    print("Preparation: " + accepted["preparation"]["status"], flush=True)
    return accepted


def snapshot(package):
    return {p.name: (hashlib.sha256(p.read_bytes()).hexdigest(), p.stat().st_mtime_ns)
            for p in package.iterdir() if p.is_file()}


def write_consumers(work, accepted):
    definitions = json.loads((work / "inputs/definitions/request.json").read_text())
    operations = {x["id"]: x for x in definitions["operations"]}
    for demand in accepted["request"]["specializations"]:
        consumer = Consumer(accepted, demand)
        consumer.native_targets = {}
        for role, identity in accepted["bindings"][demand["id"]].items():
            op = operations[identity]
            if op["kind"] == "free_function":
                args = ", ".join(accepted["types"][t]["cpp_name"] for t in op.get("cpp_template_arguments", []))
                consumer.native_targets[role] = op["cpp_name"] + (f"<{args}>" if args else "")
        (work / (demand["id"] + ".ll")).write_text(consumer.generate())
        (work / (demand["id"] + ".cpp")).write_text(consumer.baseline())


def build(work, accepted, demand, mode, instrument=False, suffix=""):
    identity = demand["id"]
    opt, lto = {"O0": ("0", None), "O1": ("1", None),
                "O1-full": ("1", "full"), "O1-thin": ("1", "thin")}[mode]
    directory = work / (identity + "-" + mode + ("-counts" if instrument else "") + suffix)
    directory.mkdir(exist_ok=True)
    clang = accepted["manifest"]["link_driver"]["executable"]
    config = accepted["config"]
    target = accepted["manifest"]["target"]["triple"]
    common = [clang, "--driver-mode=g++", "--target=" + target, "-fPIC", "-O" + opt]
    includes = ["-I" + x for x in config["include_directories"]]
    ltoflags = ["-flto=" + lto] if lto else []
    linker = shutil.which("ld.lld-18")
    if not linker:
        raise RuntimeError("This isolated proof requires ld.lld-18")
    for side, language in [("bridge", "ir"), ("native", "c++")]:
        source = work / (identity + (".ll" if side == "bridge" else ".cpp"))
        shutil.copyfile(source, directory / source.name)
        shutil.copyfile(work / "inputs/source_types.hpp", directory / "source_types.hpp")
        shutil.copyfile(Path(accepted["package"]) / "runtime.cpp", directory / "provider.cpp")
        obj = directory / (side + ".bc")
        command([*common, *ltoflags, "-std=" + config["standard"], *includes,
                 "-x", language, "-c", "-emit-llvm", source, "-o", obj], directory / (side + "-compile.log"))
        modules = [obj]
        if side == "bridge":
            modules.append(work / (mode + "-provider.bc"))
        link_inputs = sum((["-Xlinker", str(module)] for module in modules), []) if lto else modules
        extra = ["-Wl,--lto-O" + opt, "-Wl,--save-temps"] if lto else []
        command([*common, *ltoflags, "-fuse-ld=" + linker, *extra,
                 *(["-DCOUNT_ALLOCATIONS"] if instrument else []),
                 HERE / "harness.cpp", *link_inputs, "-o", directory / side], directory / (side + "-link.log"))
        command([*common, "-S", "-x", "ir", obj, "-o", directory / (side + ".s")])
    print(f"Built {directory.name}", flush=True)
    return directory


def providers(work, accepted):
    clang = accepted["manifest"]["link_driver"]["executable"]
    config = accepted["config"]
    common = [clang, "--target=" + accepted["manifest"]["target"]["triple"], "-std=" + config["standard"], "-fPIC",
              *["-I" + x for x in config["include_directories"]]]
    for mode in ("O0", "O1", "O1-full", "O1-thin"):
        flags = ["-O0"] if mode == "O0" else ["-O1"]
        if "-" in mode:
            flags += ["-flto=" + mode.split("-")[1]]
        command([*common, *flags, "-c", "-emit-llvm", Path(accepted["package"]) / "runtime.cpp", "-o", work / (mode + "-provider.bc")],
                work / (mode + "-provider.log"))


def expected(accepted, demand, n, iterations, rounds, seed):
    """Independent arithmetic oracle, including unsigned narrow-field truncation."""
    fields = accepted["types"][demand["arguments"][0]]["fields"]
    total = 0
    for r in range(rounds):
        elements = []
        for i in range(n):
            value = 0
            for f, field in enumerate(fields):
                scalar = accepted["types"][field["type"]]
                v = (i + seed + r + f) % (1 << scalar["value_bits"])
                if scalar["signed"] and v >= (1 << (scalar["value_bits"] - 1)):
                    v -= 1 << scalar["value_bits"]
                value += v
            elements.append(value)
        count, tail = divmod(iterations, n)
        total += count * sum(elements) + sum(elements[:tail])
    return total % (1 << 64)


def execute(directory, side, workload):
    return json.loads(command([directory / side, *workload]))


def measurements(work, accepted, builds, repetitions):
    results = []
    workloads = {"access": [257, 5000000, 2, 17], "lifecycle": [8, 8, 500000, 17]}
    checksums = {}
    for demand, mode, directory in builds:
        for name, workload in workloads.items():
            key = (demand["id"], name)
            if key not in checksums:
                checksums[key] = expected(accepted, demand, *workload)
            wanted = checksums[key]
            times = {side: [] for side in ("bridge", "native")}
            # Warm each executable, then alternate order to reduce systematic ordering bias.
            for side in times:
                assert execute(directory, side, workload)["checksum"] == wanted
            for repeat in range(repetitions):
                for side in (["bridge", "native"] if repeat % 2 == 0 else ["native", "bridge"]):
                    result = execute(directory, side, workload)
                    assert result["checksum"] == wanted, (directory, side, result, wanted)
                    times[side].append(result["nanoseconds"])
            medians = {side: statistics.median(values) for side, values in times.items()}
            row = dict(type=demand["id"], mode=mode, workload=name, arguments=workload,
                       samples_ns=times, medians_ns=medians, ratio=medians["bridge"] / medians["native"])
            results.append(row)
            print(f"{demand['id']} {mode} {name}: bridge/native {row['ratio']:.3f}x", flush=True)
        bad = subprocess.run([str(directory / "bridge"), "bad"], capture_output=True, text=True, timeout=10)
        assert bad.returncode != 0 and "runtime operation" in bad.stderr
        (directory / "bounds.stderr").write_text(bad.stderr)
    return results


def code_evidence(work, accepted, builds):
    rows = []
    clang = accepted["manifest"]["link_driver"]["executable"]
    for demand, mode, directory in builds:
        modules = sorted(directory.glob("bridge*.opt.bc")) if "-" in mode else [directory / "bridge.bc"]
        calls = 0
        import re
        for module in modules:
            llvm = command([clang, "-x", "ir", "-S", "-emit-llvm", module, "-o", "-"])
            module.with_suffix(".ll").write_text(llvm)
            # Includes check/run/bad fixture functions, not just the measured loop.
            calls += len(re.findall(r"\b(?:call|invoke)\b[^\n]*@rp_op_X_", llvm))
        rows.append(dict(type=demand["id"], mode=mode, inspected_modules=[x.name for x in modules], remaining_bridge_calls=calls))
    return rows


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--workspace", type=Path, default=ROOT / "generated-runtime/proofs/source-specializations")
    parser.add_argument("--repetitions", type=int, default=5)
    args = parser.parse_args()
    if args.repetitions < 3:
        parser.error("Use at least three timing repetitions")
    work = args.workspace.resolve()
    work.mkdir(parents=True, exist_ok=True)
    # This fixture owns one workspace. Concurrent drivers cannot rewrite each other's fixed inputs.
    with (work / ".proof.lock").open("w") as lock:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        start = time.monotonic()
        accepted = prepare(work)
        (work / "initial-accepted.json").write_text(json.dumps(accepted, indent=2) + "\n")
        package = Path(accepted["package"])
        original = snapshot(package)
        warm = prepare(work)
        assert warm["preparation"]["status"] == "reused" and snapshot(package) == original
        with (work / "output/.prepare.lock").open("r") as package_lock:
            fcntl.flock(package_lock, fcntl.LOCK_SH)
            write_consumers(work, accepted)
            # Match optimization on provider and caller, rather than using the production O2 LTO variants.
            providers(work, accepted)
            tasks = [(demand, mode) for demand in accepted["request"]["specializations"]
                     for mode in ("O0", "O1", "O1-full", "O1-thin")]
            with ThreadPoolExecutor(max_workers=4) as pool:
                futures = [pool.submit(build, work, accepted, demand, mode) for demand, mode in tasks]
                builds = [(demand, mode, future.result()) for (demand, mode), future in zip(tasks, futures)]
            # Execution timings are deliberately serial, never concurrent with compilation.
            results = measurements(work, accepted, builds, args.repetitions)
            counts = []
            for demand in accepted["request"]["specializations"]:
                directory = build(work, accepted, demand, "O1", True)
                values = {side: execute(directory, side, [257, 10000, 2, 17]) for side in ("bridge", "native")}
                for key in ("checksum", "allocations", "releases", "allocated_bytes"):
                    assert values["bridge"][key] == values["native"][key], (key, values)
                assert values["bridge"]["allocations"] == values["bridge"]["releases"]
                counts.append(dict(type=demand["id"], results=values))
            evidence = code_evidence(work, accepted, builds)

            # A real caller-body change must not change preparation inputs/artifacts.
            demand = accepted["request"]["specializations"][0]
            caller = work / (demand["id"] + ".ll")
            caller.write_text(caller.read_text().replace("add i64 %a, %seed", "add i64 %a, 7"))
            edited = build(work, accepted, demand, "O1", suffix="-caller-edit")
            assert execute(edited, "bridge", [9, 30, 1, 17])["checksum"] == expected(accepted, demand, 9, 30, 1, 7)
        warm = prepare(work)
        assert warm["preparation"]["status"] == "reused" and snapshot(package) == original

        # A changed source definition rebuilds the dependent package; unchanged identities are retained.
        request = json.loads((HERE / "request.json").read_text())
        request["source_types"][0]["fields"].append(dict(name="added", type="signed64", writable=True))
        changed_path = work / "changed-request.json"
        changed_path.write_text(json.dumps(request, indent=2) + "\n")
        changed = prepare(work, changed_path)
        assert changed["preparation"]["status"] == "built"
        record_id = demand["arguments"][0]
        assert changed["types"][record_id]["size_bytes"] != accepted["types"][record_id]["size_bytes"]
        assert changed["bindings"] == accepted["bindings"]
        with (work / "output/.prepare.lock").open("r") as package_lock:
            fcntl.flock(package_lock, fcntl.LOCK_SH)
            write_consumers(work, changed)
            providers(work, changed)
            updated = build(work, changed, demand, "O1", suffix="-record-edit")
            for side in ("bridge", "native"):
                assert execute(updated, side, [9, 30, 1, 17])["checksum"] == expected(changed, demand, 9, 30, 1, 17)

        rejection_output = command(["php", HERE / "rejections.php", work], work / "rejections.log")
        print(rejection_output.strip(), flush=True)
        report = dict(status="passed", rejection_checks=7, machine=platform.platform(), toolchain=accepted["manifest"]["inputs"]["clang_version"],
                      target=accepted["manifest"]["target"], repetitions=args.repetitions, measurements=results,
                      allocation_checks=counts, code_evidence=evidence,
                      reuse=["unchanged package", "changed caller reuses package", "changed record rebuilds package with stable identities"],
                      elapsed_seconds=time.monotonic() - start)
        (work / "report.json").write_text(json.dumps(report, indent=2) + "\n")
        print(f"PASS: report and artifacts in {work}", flush=True)


if __name__ == "__main__":
    main()
