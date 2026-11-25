"""Warm-cache, single-source compile and body-edit benchmark for the 10 KB pilots."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import platform
import random
import shutil
import subprocess
import time

from workloads import write

HERE = Path(__file__).resolve().parent
EDITS = {"access-log": ('echo "error-budget ', 'echo "remaining-budget '),
         "stock-ledger": ("warehouse cents ", "total inventory cents "),
         "route-table": ('echo "matched ', 'echo "resolved cases ')}
ENV = {**os.environ, "XDEBUG_MODE": "off"}


def run(args, **kwargs):
    return subprocess.run(args, check=True, env=ENV, capture_output=True, **kwargs)


def timed(action):
    start = time.perf_counter_ns()
    result = action()
    return result, (time.perf_counter_ns() - start) / 1e6


def emit(process):
    process.stdin.write('"run"\n')
    process.stdin.flush()
    data = json.loads(process.stdout.readline())
    if "error" in data:
        raise RuntimeError(data["error"])
    return data


def verify(binary, directory, expected=None):
    output = run([binary], cwd=directory).stdout
    expected = (directory / "expected.txt").read_bytes() if expected is None else expected
    if output != expected:
        raise ValueError(f"{binary}: report mismatch in {directory}")


def sample(root, work, name):
    case = HERE / name
    app, out = work / "app", work / "out"
    app.mkdir(parents=True)
    for filename in ["module.json", "main.php"]:
        shutil.copyfile(case / filename, app / filename)
    source = app / "main.php"
    flags = ["clang++", "-std=c++23", "-stdlib=libc++", "-O2", "-g0",
             "-I" + str(root / "runtime/generated/public")]
    args = ["php", root / "compiler/tests/incremental_executable.php", app, out]
    started = time.perf_counter_ns()
    with subprocess.Popen(args, env=ENV, stdin=subprocess.PIPE, stdout=subprocess.PIPE, text=True) as proc:
        initial, frontend = timed(lambda: emit(proc))
        manifest = json.loads((out / "manifest.json").read_text())
        units = [out / path for path in manifest["translation_units"]]
        main = out / next(iter(manifest["sources"].values()))
        objects = [unit.with_suffix(".o") for unit in units]
        def compile_unit(unit):
            run([*flags, "-c", unit, "-o", unit.with_suffix(".o")])
        def link():
            run([*flags, "--ld-path=" + shutil.which("ld.mold"), *objects, *manifest["link_inputs"], "-o", work / "run"])
        _, compile_ms = timed(lambda: [compile_unit(unit) for unit in units])
        _, link_ms = timed(link)
        total = (time.perf_counter_ns() - started) / 1e6
        verify(work / "run", case)
        records = write(name, work / "large")
        verify(work / "run", work / "large")
        source_bytes = source.read_bytes()
        result = dict(total_ms=total, frontend_wall_ms=frontend, prepare_emit_ms=initial["php_ms"],
                      clang_ms=compile_ms, link_ms=link_ms, cpp_bytes=sum(u.stat().st_size for u in units),
                      source_bytes=len(source_bytes), source_sha256=hashlib.sha256(source_bytes).hexdigest(),
                      source_lines=len(source_bytes.splitlines()), records=records)
        old, new = EDITS[name]
        text = source.read_text()
        assert text.count(old) == 1
        source.write_text(text.replace(old, new))
        edit_start = time.perf_counter_ns()
        updated = emit(proc)
        compile_unit(main)
        link()
        result["edit_total_ms"] = (time.perf_counter_ns() - edit_start) / 1e6
        result["edit_prepare_emit_ms"] = updated["php_ms"]
        old_output = old.removeprefix('echo "').encode()
        new_output = new.removeprefix('echo "').encode()
        expected = (case / "expected.txt").read_bytes()
        # Change only the report label at the beginning of its line.
        changed = b"\n".join(new_output + line[len(old_output):] if line.startswith(old_output)
                             else line for line in expected.split(b"\n"))
        assert changed != expected
        verify(work / "run", case, changed)
        proc.stdin.close()
    return result


def package_runtime(root, output, name):
    # Package the unedited source and both verified fixture reports for the remote host.
    case = HERE / name
    shutil.copyfile(case / "main.php", output / "app/main.php")
    args = ["php", root / "compiler/tests/incremental_executable.php", output / "app", output / "out"]
    response = json.loads(run(args, input=b'"run"\n').stdout)
    if "error" in response:
        raise RuntimeError(response["error"])
    manifest = json.loads((output / "out/manifest.json").read_text())
    unit = output / "out" / next(iter(manifest["sources"].values()))
    flags = ["clang++", "-std=c++23", "-stdlib=libc++", "-O2", "-g0", "-static-libstdc++",
             "--ld-path=" + shutil.which("ld.mold"), "-I" + str(root / "runtime/generated/public")]
    destination = output.parents[1] / "remote" / name
    destination.mkdir(parents=True)
    for path in (output / "large").iterdir():
        shutil.copyfile(path, destination / path.name)
    for mode, extra in [("timing", []), ("memory", ["-DCOUNT_ALLOCATIONS", "-Wl,--wrap=_Znwm"]),
                        ("report", [])]:
        driver = output / "out/entry.cpp" if mode == "report" else HERE / "runtime.cpp"
        run([*flags, *extra, driver, unit, *manifest["link_inputs"], "-o", destination / mode])
    verify(destination / "report", destination)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("output", type=Path)
    parser.add_argument("--v02", type=Path, default=HERE.parents[1])
    parser.add_argument("--rounds", type=int, default=5)
    args = parser.parse_args()
    if args.rounds < 1:
        parser.error("--rounds must be positive")
    root, output = args.v02.resolve(), args.output.resolve()
    output.mkdir(parents=True, exist_ok=False)
    rnd = random.Random(42)
    rows = []
    for i in range(-1, args.rounds):
        names = list(EDITS)
        rnd.shuffle(names)
        for name in names:
            work = output / str(i) / name
            data = sample(root, work, name)
            if i >= 0:
                rows.append({"name": name, "round": i, **data})
    for name in EDITS:
        package_runtime(root, output / "0" / name, name)
    report = dict(host=platform.node(), clang=run(["clang++", "--version"]).stdout.decode().splitlines()[0],
                  php=run(["php", "-r", "echo PHP_VERSION;"]).stdout.decode(), samples=rows)
    (output / "compile.json").write_text(json.dumps(report, indent=2) + "\n")


if __name__ == "__main__":
    main()
