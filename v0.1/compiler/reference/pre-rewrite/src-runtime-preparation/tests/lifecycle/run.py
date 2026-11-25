#!/usr/bin/env python3
"""Isolated Clang lifecycle investigation. Does not implement source compilation."""
import argparse
from concurrent.futures import ThreadPoolExecutor
import fcntl
import hashlib
import json
from pathlib import Path
import re
import shutil
import subprocess
import time

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[2]


def command(args, log, success=True):
    result = subprocess.run([str(x) for x in args], text=True, capture_output=True, timeout=120)
    Path(log).write_text(json.dumps([str(x) for x in args]) + "\n" + result.stdout + result.stderr)
    if (result.returncode == 0) != success:
        raise RuntimeError(f"Unexpected exit {result.returncode}: {args}\n{result.stderr[-6000:]}")
    return result.stdout + (result.stderr if not success else "")


def definitions():
    """Fixture exposures use existing operation roles, including generic assignment helpers."""
    types = [dict(id="integer", cpp_name="std::int32_t", header="cstdint", kind="integer"),
             dict(id="nothing", cpp_name="void", header="cstddef", kind="void")]
    operations = []
    for identity in ("automatic", "custom"):
        types.append(dict(id=identity, cpp_name=f"lifecycle_probe::{identity}_record", header="native.hpp",
                          kind="runtime_value", storage="inline",
                          lifecycle=dict(construct=identity + ".construct", destroy=identity + ".destroy",
                                         copy_construct=identity + ".copy")))
        for role, kind in (("construct", "construct"), ("destroy", "destroy"), ("copy", "copy_construct")):
            row = dict(id=identity + "." + role, kind=kind, type=identity, error_policy="terminate")
            if kind == "construct":
                row["parameters"] = ["integer"] if identity == "custom" else []
            operations.append(row)
        mutable = dict(type=identity, passing="mutable_address", borrow_scope="call")
        const = dict(type=identity, passing="const_address", borrow_scope="call")
        for role, parameters, result in (("append", [mutable, "integer"], "nothing"),
                                         ("length", [const], "integer"),
                                         ("first_value", [const], "integer"),
                                         ("assign", [mutable, const], "nothing")):
            operations.append(dict(id=identity + "." + role, kind="free_function", error_policy="terminate",
                                   cpp_name="lifecycle_probe::" + role, header="native.hpp",
                                   cpp_template_arguments=[identity], parameters=parameters, result_type=result))
    for role, parameters in (("shared_lifetime", ["integer"]), ("rvalue_copy", [])):
        operations.append(dict(id=role, kind="free_function", error_policy="terminate",
                               cpp_name="lifecycle_probe::" + role, header="native.hpp",
                               parameters=parameters, result_type="nothing"))
    return dict(schema_version=1, types=types, operations=operations)


class Fixture_IR:
    """LLVM test instructions consume measured ABI/storage; they are not a new backend."""
    def __init__(self, measured):
        self.measured = measured
        self.lines = []
        self.number = 0

    def emit(self, instruction):
        self.lines.append("  " + instruction)

    def value(self, instruction):
        name = f"%v{self.number}"
        self.number += 1
        self.emit(name + " = " + instruction)
        return name

    def begin(self, signature):
        self.lines.append("define " + signature + " nounwind {")

    def end(self):
        self.emit("ret void")
        self.lines.append("}")

    def call(self, operation, *arguments):
        op = self.measured["operations"][operation]
        abi = op["abi"]
        assert len(arguments) == len(abi["parameters"])
        params = ", ".join(f"{p['type']} {p['attributes']} {a}" for p, a in zip(abi["parameters"], arguments))
        instruction = f"call {abi['return_attributes']} {abi['return_type']} @{op['symbol']}({params})"
        if abi["return_type"] == "void":
            self.emit(instruction)
        else:
            return self.value(instruction)

    def equal(self, actual, expected):
        self.emit(f"call void @proof_equal(i32 {actual}, i32 {expected})")

    def event(self, kind, value):
        self.emit(f"call void @proof_event(i32 {kind}, i32 {value})")

    def field(self, name):
        return self.value(f"getelementptr i8, ptr %self, i64 {self.measured['offsets'][name]}")

    def storage(self, identity):
        info = self.measured["types"][identity]
        return self.value(f"alloca [{info['size_bytes']} x i8], align {info['alignment_bytes']}")

    def generate(self, delta):
        target = self.measured["target"]
        self.lines = [f'target triple = "{target["triple"]}"',
                      f'target datalayout = "{target["data_layout"]}"',
                      "declare void @proof_event(i32, i32) nounwind",
                      "declare void @proof_equal(i32, i32) nounwind"]
        for op in self.measured["operations"].values():
            abi = op["abi"]
            params = ", ".join(p["type"] + " " + p["attributes"] for p in abi["parameters"])
            self.lines.append(f"declare {abi['return_attributes']} {abi['return_type']} @{op['symbol']}({params}) nounwind")

        self.begin("i32 @proof_initializer(i32 %seed)")
        self.event(5, "%seed")
        result = self.value("add i32 %seed, 100")
        self.emit("ret i32 " + result)
        self.lines.append("}")

        self.begin("void @proof_constructor_body(ptr %self, i32 %seed)")
        first = self.value(f"load i32, ptr {self.field('first')}, align 1")
        expected = self.value("add i32 %seed, 100")
        self.equal(first, expected)
        marker = self.field("marker")
        self.equal(self.value(f"load i32, ptr {marker}, align 1"), 0)
        new_value = self.value(f"add i32 %seed, {delta}")
        self.emit(f"store i32 {new_value}, ptr {marker}, align 1")
        self.call("custom.append", "%self", new_value)
        self.event(6, new_value)
        self.end()

        self.begin("void @proof_destructor_body(ptr %self)")
        self.event(7, self.value(f"load i32, ptr {self.field('marker')}, align 1"))
        self.event(10, self.call("custom.length", "%self"))
        self.event(11, self.value(f"load i32, ptr {self.field('first')}, align 1"))
        self.end()

        self.begin("void @proof_run()")
        original, copy = self.storage("automatic"), self.storage("automatic")
        self.call("automatic.construct", original)
        self.call("automatic.append", original, 42)
        self.call("automatic.copy", copy, original)
        self.call("automatic.append", original, 99)
        self.equal(self.call("automatic.length", copy), 1)
        self.equal(self.call("automatic.first_value", copy), 42)
        self.call("automatic.assign", copy, original)
        self.call("automatic.append", original, 100)
        self.equal(self.call("automatic.length", copy), 2)
        self.call("automatic.destroy", copy)
        self.call("automatic.destroy", original)

        original, copy, target = [self.storage("custom") for _ in range(3)]
        self.call("custom.construct", original, 10)
        self.call("custom.copy", copy, original)
        self.call("custom.append", original, 99)
        self.equal(self.call("custom.length", original), 2)
        self.equal(self.call("custom.length", copy), 1)
        self.equal(self.call("custom.first_value", copy), 10 + delta)
        self.call("custom.construct", target, 20)
        self.call("custom.assign", target, original)
        self.call("custom.append", original, 100)
        self.equal(self.call("custom.length", target), 2)
        self.equal(self.call("custom.first_value", target), 10 + delta)
        for value in (target, copy, original):
            self.call("custom.destroy", value)
        self.call("shared_lifetime", 30)
        self.call("rvalue_copy")
        self.end()
        return "\n".join(self.lines) + "\n"


def imports_match(shell, bodies):
    """Check the fixture's three narrow C ABI imports before linking; not a general LLVM parser."""
    names = ("proof_initializer", "proof_constructor_body", "proof_destructor_body")
    for name in names:
        shapes = []
        for text, keyword in ((shell, "declare"), (bodies, "define")):
            matches = re.findall(rf"^{keyword} ([^\n]+) @{name}\(([^)]*)\)", text, re.M)
            if len(matches) != 1:
                raise ValueError("Missing or duplicate body import: " + name)
            prefix, parameters = matches[0]
            # This proof accepts only default ccc, ptr/i32, and no extension attributes.
            if re.search(r"\b(?:\w+cc|cc|signext|zeroext)\b", prefix + " " + parameters):
                raise ValueError("Unsupported fixture ABI: " + name)
            result = re.search(r"\b(void|i32)$", prefix)
            args = []
            for parameter in parameters.split(",") if parameters else []:
                match = re.match(r"\s*(ptr|i32)\b", parameter)
                if not match:
                    raise ValueError("Unsupported fixture parameter: " + name)
                args.append(match[1])
            if not result:
                raise ValueError("Unsupported fixture result: " + name)
            shapes.append((result[1], args))
        if shapes[0] != shapes[1]:
            raise ValueError("Mismatched body import: " + name)


def expected(delta):
    """Independent event oracle catches agreement between two incorrectly ordered paths."""
    events = [(1, 1), (1, 2), (2, 1), (2, 2), (3, 1), (3, 2), (4, 2), (4, 1), (4, 2), (4, 1)]
    def construct(seed):
        return [(5, seed), (1, seed + 100), (1, seed + 1), (1, seed + 2), (6, seed + delta)]
    def destroy(seed, count):
        return [(7, seed + delta), (10, count), (11, seed + 100),
                (4, seed + 2), (4, seed + 1), (4, seed + 100)]
    events += construct(10) + [(2, 110), (2, 11), (2, 12)] + construct(20)
    events += [(3, 110), (3, 11), (3, 12)]
    events += destroy(10, 2) + destroy(10, 1) + destroy(10, 3)
    events += construct(30) + [(8, 30)] + destroy(30, 1) + [(9, 30)]
    events += [(1, 7), (2, 7), (4, 7), (4, 7)]
    return "".join(f"{kind}:{value}\n" for kind, value in events)


def snapshot(path):
    return (hashlib.sha256(path.read_bytes()).hexdigest(), path.stat().st_mtime_ns)


def build_mode(work, measured, mode, linker):
    directory = work / mode
    directory.mkdir(exist_ok=True)
    config = measured["config"]
    common = [measured["clang"], "--driver-mode=g++", "--target=" + measured["target"]["triple"],
              "-std=" + config["standard"], "-fPIC", "-O0" if mode == "O0" else "-O1",
              *["-I" + p for p in config["include_directories"]]]
    lto = mode.split("-", 1)[1] if "-" in mode else None
    flags = ["-flto=" + lto] if lto else []
    shell = directory / "shell.bc"
    command([*common, *flags, "-c", "-emit-llvm", work / "shell.cpp", "-o", shell], directory / "shell.log")
    before = snapshot(shell)
    base_link = [*common, *flags, "-fuse-ld=" + linker]
    if lto:
        base_link += ["-Wl,--lto-O1"]

    for delta in (1, 2):
        body = directory / f"body-{delta}.bc"
        command([*common, *flags, "-x", "ir", "-c", "-emit-llvm", work / f"body-{delta}.ll", "-o", body],
                directory / f"body-{delta}.log")
        # Give bitcode directly to LLD for LTO, preserving its module inputs.
        modules = ["-Xlinker", shell, "-Xlinker", body] if lto else [shell, body]
        command([*base_link, HERE / "harness.cpp", *modules, "-o", directory / f"bridge-{delta}"],
                directory / f"link-{delta}.log")
        command([*base_link, f"-DBODY_DELTA={delta}", HERE / "harness.cpp", HERE / "reference.cpp",
                 "-o", directory / f"native-{delta}"], directory / f"native-{delta}.log")
        for side in ("bridge", "native"):
            output = command([directory / f"{side}-{delta}"], directory / f"{side}-{delta}.trace")
            if output != expected(delta):
                raise AssertionError(f"{mode} {side} body {delta}: wrong lifecycle trace")
        assert snapshot(shell) == before, "A body edit changed the prepared shell"
    print(f"{mode}: ordering, deep copies, custom bodies, native shared cleanup and body replacement passed", flush=True)
    return dict(mode=mode, body_revisions=2, events_per_run=len(expected(1).splitlines()), shell_reused=True)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--workspace", type=Path, default=ROOT / "generated-runtime/proofs/lifecycle")
    options = parser.parse_args()
    work = options.workspace.resolve()
    work.mkdir(parents=True, exist_ok=True)
    with (work / ".proof.lock").open("w") as lock:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        started = time.monotonic()
        (work / "definitions").mkdir(exist_ok=True)
        (work / "definitions/lifecycle.json").write_text(json.dumps(definitions(), indent=2) + "\n")
        print("Measuring shell ABI and checking runtime-package rejection ...", flush=True)
        command(["php", HERE / "prepare.php", work], work / "prepare.log")
        measured = json.loads((work / "measured.json").read_text())
        shell = (work / "shell.ll").read_text()
        for delta in (1, 2):
            body = Fixture_IR(measured).generate(delta)
            imports_match(shell, body)
            (work / f"body-{delta}.ll").write_text(body)

        # A C linker matches names, not full function types. Catch this before linking.
        invalid = body.replace("@proof_constructor_body(ptr %self, i32 %seed)",
                               "@proof_constructor_body(ptr %self, i64 %seed)")
        try:
            imports_match(shell, invalid)
        except ValueError:
            pass
        else:
            raise AssertionError("Mismatched body import was accepted")

        linker = shutil.which("ld.lld-18")
        if not linker:
            raise RuntimeError("This isolated proof requires ld.lld-18")
        with ThreadPoolExecutor(max_workers=4) as pool:
            futures = [pool.submit(build_mode, work, measured, mode, linker)
                       for mode in ("O0", "O1", "O1-full", "O1-thin")]
            results = [future.result() for future in futures]

        # Prove that no fallback bodies can hide an incomplete project link.
        missing_main = work / "missing-main.cpp"
        missing_main.write_text('extern "C" void proof_run() {}\n')
        failure = command([measured["clang"], "--target=" + measured["target"]["triple"],
                           HERE / "harness.cpp", missing_main, work / "O0/shell.bc", "-o", work / "missing-body"],
                          work / "missing-body.log", success=False)
        assert all(name in failure for name in ("proof_constructor_body", "proof_destructor_body", "proof_initializer"))
        report = dict(status="passed", clang=measured["clang_version"], target=measured["target"], modes=results,
                      package_rejected=True, missing_bodies_rejected=True, mismatched_body_abi_rejected=True,
                      wall_seconds=round(time.monotonic() - started, 3),
                      scope="Native fixture + hand-authored LLVM; no source compiler or published-package integration")
        (work / "report.json").write_text(json.dumps(report, indent=2) + "\n")
        print(json.dumps(report, indent=2), flush=True)


if __name__ == "__main__":
    main()
