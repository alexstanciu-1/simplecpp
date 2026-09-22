#!/usr/bin/env python3
"""Probe runtime templates calling complete compiler-owned source operations."""
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
ROLES = ("construct", "copy", "move", "assign", "move_assign", "destroy")


def command(args, log, success=True):
    process = subprocess.run([str(x) for x in args], capture_output=True, text=True, timeout=120)
    Path(log).write_text(json.dumps([str(x) for x in args]) + "\n" + process.stdout + process.stderr)
    if (process.returncode == 0) != success:
        raise RuntimeError(f"Unexpected exit {process.returncode}: {args}\n{process.stderr[-5000:]}")
    return process.stdout + (process.stderr if not success else "")


def definitions():
    mutable = dict(type="field", passing="mutable_address", borrow_scope="call")
    const = dict(type="field", passing="const_address", borrow_scope="call")
    types = [dict(id="integer", cpp_name="std::int32_t", header="cstdint", kind="integer"),
             dict(id="nothing", cpp_name="void", header="cstddef", kind="void"),
             dict(id="field", cpp_name="source_operations_probe::managed_field", header="native.hpp",
                  kind="runtime_value", storage="inline", lifecycle=dict(construct="field.construct",
                  destroy="field.destroy", copy_construct="field.copy"))]
    operations = []
    for role, kind in (("construct", "construct"), ("copy", "copy_construct"), ("destroy", "destroy")):
        op = dict(id="field." + role, kind=kind, type="field", error_policy="terminate")
        if kind == "construct":
            op["parameters"] = ["integer"]
        operations.append(op)
    for role, name, params, result in (("move", "move_value", [mutable], "field"),
                                       ("assign", "assign", [mutable, const], "nothing"),
                                       ("move_assign", "move_assign", [mutable, mutable], "nothing"),
                                       ("exercise", "exercise", ["integer"], "nothing")):
        operations.append(dict(id="field." + role, kind="free_function", error_policy="terminate",
                               cpp_name="source_operations_probe::" + name, header="native.hpp",
                               parameters=params, result_type=result))
    return dict(schema_version=1, types=types, operations=operations)


def compiler_module(measured, revision):
    """Test LLVM composition. Every source lifecycle definition lives in this module only."""
    target = measured["target"]
    lines = [f'target triple = "{target["triple"]}"', f'target datalayout = "{target["data_layout"]}"',
             "declare void @observe_source(i32, i32, ptr) nounwind",
             "declare void @check_equal(i32, i32) nounwind"]
    boundary = measured["payload_boundary"]
    assert boundary["return_type"] == "void"
    assert [p["type"] for p in boundary["parameters"]] == ["ptr", "ptr", "i32"]
    params = ", ".join(p["type"] + " " + p["attributes"] for p in boundary["parameters"])
    lines.append(f"declare void @native_payload_roundtrip({params}) nounwind")
    for operation in measured["operations"].values():
        abi = operation["abi"]
        params = ", ".join(p["type"] + " " + p["attributes"] for p in abi["parameters"])
        lines.append(f"declare {abi['return_attributes']} {abi['return_type']} @{operation['symbol']}({params}) nounwind")

    def call(role, args):
        operation = measured["operations"]["field." + role]
        abi = operation["abi"]
        assert abi["return_type"] == "void" and len(args) == len(abi["parameters"])
        params = ", ".join(f"{p['type']} {p['attributes']} {a}" for p, a in zip(abi["parameters"], args))
        lines.append(f"  call void @{operation['symbol']}({params})")

    for number, role in enumerate(ROLES):
        binary = role not in ("construct", "destroy")
        params = "ptr %self, ptr %source" if binary else "ptr %self"
        lines += [f"define void @source_{role}({params}) nounwind {{",
                  f"  call void @observe_source(i32 {number}, i32 0, ptr %self)"]
        # Field order belongs to this LLVM implementation, not the C++ adapter.
        fields = [("first", 10), ("tag", revision), ("second", 20)]
        if role == "destroy":
            fields.reverse()
        for index, (name, initial) in enumerate(fields):
            offset = measured["layout"][name]
            lines.append(f"  %dst{index} = getelementptr i8, ptr %self, i64 {offset}")
            args = [f"%dst{index}"]
            if binary:
                lines.append(f"  %src{index} = getelementptr i8, ptr %source, i64 {offset}")
                args.append(f"%src{index}")
            elif role == "construct":
                args.append(str(initial))
            if name == "tag":
                if role != "destroy":
                    if binary:
                        lines.append(f"  %tag_value = load i32, ptr %src{index}, align 1")
                    lines.append(f"  store i32 {'%tag_value' if binary else initial}, ptr %dst{index}, align 1")
            else:
                call(role, args)
        lines += [f"  call void @observe_source(i32 {number}, i32 1, ptr %self)", "  ret void", "}"]
    lines += ["define void @compiler_check_payload(ptr %self, i32 %expected) nounwind {",
              f"  %tag = getelementptr i8, ptr %self, i64 {measured['layout']['tag']}",
              "  %value = load i32, ptr %tag, align 1",
              "  call void @check_equal(i32 %value, i32 %expected)", "  ret void", "}"]
    lines.append("define void @compiler_entry(i32 %revision) nounwind {")
    call("exercise", ["%revision"])
    for name in ("local", "result"):
        lines.append(f"  %{name} = alloca [{measured['layout']['size']} x i8], align {measured['layout']['alignment']}")
    lines += ["  call void @source_construct(ptr %local)"]
    args = ", ".join(f"{p['type']} {p['attributes']} {a}"
                     for p, a in zip(boundary["parameters"], ("%local", "%result", "%revision")))
    lines += [f"  call void @native_payload_roundtrip({args})",
              "  call void @source_destroy(ptr %local)",
              "  call void @compiler_check_payload(ptr %result, i32 %revision)",
              "  call void @source_destroy(ptr %result)"]
    lines += ["  ret void", "}"]
    return "\n".join(lines) + "\n"


def snapshot(path):
    return (hashlib.sha256(path.read_bytes()).hexdigest(), path.stat().st_mtime_ns)


def mode_run(work, measured, mode, linker):
    directory = work / mode
    directory.mkdir(exist_ok=True)
    options = measured["config"]
    common = [measured["clang"], "--target=" + measured["target"]["triple"], "-std=" + options["standard"],
              "-fPIC", "-O0" if mode == "O0" else "-O1", *["-I" + p for p in options["include_directories"]]]
    lto = mode.split("-", 1)[1] if "-" in mode else None
    flags = ["-flto=" + lto] if lto else []
    native = directory / "native.bc"
    command([*common, *flags, "-c", "-emit-llvm", work / "native.cpp", "-o", native], directory / "native.log")
    before = snapshot(native)
    results = []
    for revision in (1, 2):
        body = directory / f"compiler-{revision}.bc"
        command([*common, *flags, "-x", "ir", "-c", "-emit-llvm", work / f"compiler-{revision}.ll", "-o", body],
                directory / f"compiler-{revision}.log")
        modules = ["-Xlinker", native, "-Xlinker", body] if lto else [native, body]
        executable = directory / f"proof-{revision}"
        command([*common, *flags, "-fuse-ld=" + linker, *(["-Wl,--lto-O1"] if lto else []),
                 HERE / "harness.cpp", *modules, "-o", executable], directory / f"link-{revision}.log")
        result = json.loads(command([executable, revision], directory / f"run-{revision}.log"))
        assert all(result[role] > 0 for role in ROLES)
        assert snapshot(native) == before
        results.append(result)
    assert results[0] == results[1]
    print(f"{mode}: all six source operations, field order/liveness and native module reuse passed", flush=True)
    return dict(mode=mode, revisions=2, counts=results[0], native_module_reused=True)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--workspace", type=Path, default=ROOT / "generated-runtime/proofs/source-operations")
    options = parser.parse_args()
    work = options.workspace.resolve()
    work.mkdir(parents=True, exist_ok=True)
    with (work / ".proof.lock").open("w") as lock:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        (work / "report.json").unlink(missing_ok=True)
        started = time.monotonic()
        (work / "definitions").mkdir(exist_ok=True)
        (work / "definitions/operations.json").write_text(json.dumps(definitions(), indent=2) + "\n")
        command(["php", HERE / "prepare.php", work], work / "prepare.log")
        measured = json.loads((work / "measured.json").read_text())
        native_ir = (work / "native.ll").read_text()
        for role in ROLES:
            assert not re.search(rf"^define .* @source_{role}\(", native_ir, re.M)
            assert len(re.findall(rf"^declare .* @source_{role}\(", native_ir, re.M)) == 1
        for revision in (1, 2):
            (work / f"compiler-{revision}.ll").write_text(compiler_module(measured, revision))
        linker = shutil.which("ld.lld-18")
        if not linker:
            raise RuntimeError("This fixture requires ld.lld-18")
        with ThreadPoolExecutor(max_workers=4) as pool:
            futures = [pool.submit(mode_run, work, measured, mode, linker)
                       for mode in ("O0", "O1", "O1-full", "O1-thin")]
            results = [future.result() for future in futures]

        includes = ["-I" + p for p in measured["config"]["include_directories"]]
        # Deleted capabilities must prevent a native template from silently copying bytes.
        negative = work / "copy-forbidden.cpp"
        negative.write_text('#include "native.hpp"\nvoid check() {\n'
                            '  using T = source_operations_probe::element_adapter<false>;\n'
                            '  T value; scpp::vector_t<T> values; values.append(value);\n}\n')
        failure = command([measured["clang"], "-std=c++23", *includes, "-fsyntax-only", negative],
                          work / "copy-forbidden.log", success=False)
        assert "deleted" in failure

        # Missing source operations and duplicate definitions must both fail linking.
        main_file = work / "empty.cpp"
        main_file.write_text('extern "C" void compiler_entry(int) {}\n'
                             'extern "C" void compiler_check_payload(const void*, int) {}\n')
        base = [measured["clang"], "-std=c++23", *includes, HERE / "harness.cpp", work / "O0/native.bc"]
        failure = command([*base, main_file, "-o", work / "missing"], work / "missing.log", success=False)
        assert all("source_" + role in failure for role in ROLES)
        failure = command([*base, work / "O0/compiler-1.bc", work / "O0/compiler-1.bc", "-o", work / "duplicate"],
                          work / "duplicate.log", success=False)
        assert "multiple definition" in failure or "duplicate symbol" in failure
        report = dict(status="passed", clang=measured["clang_version"], target=measured["target"],
                      layout=measured["layout"], modes=results, copy_forbidden_rejected=True,
                      missing_operations_rejected=True, duplicate_definitions_rejected=True,
                      wall_seconds=round(time.monotonic() - started, 3),
                      scope="Native adapter experiment + hand-authored LLVM; no compiler integration")
        (work / "report.json").write_text(json.dumps(report, indent=2) + "\n")
        print(json.dumps(report, indent=2), flush=True)


if __name__ == "__main__":
    main()
