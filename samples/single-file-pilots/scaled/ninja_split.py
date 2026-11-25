"""Ninja split experiment for the 100 KB access-log fixture on Linux."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import platform
import random
import re
import resource
import shlex
import shutil
import subprocess
import tempfile
import time

from generate import BASE, HERE
from verify_workload import fixture

SOURCE = HERE / "100k/access-log"
CONFIGS = {"one-j4": (1, 4), "four-j1": (4, 1), "four-j4": (4, 4)}
OLD = "elapsed/status=success/method=get/cache=hit"
NEW = "elapsed/status=success/method=get/cache=HIT"


def write_changed(path, data):
    if not path.exists() or path.read_bytes() != data:
        path.write_bytes(data)


def split_application(text):
    # This recognizer is limited to this fixture's emitted format. Preserve all
    # bytes of each report body. Leave the remaining definitions together.
    first = text.index("::scpp::int64 decimal_field_(", text.index("void update_dashboard_("))
    preamble = text[:first] + "}\n"
    body = text[first:].removesuffix("}\n")
    matches = list(re.finditer(r"^void (\w+_by_\w+)\([^\n]+\)\n\{.*?^\}\n", body, re.M | re.S))
    assert len(matches) == 31
    core = body
    for m in reversed(matches):
        core = core[:m.start()] + core[m.end():]
    groups = [[("core", core)], [], [], []]
    for m in sorted(matches, key=lambda m: -len(m[0])):
        group = min(groups, key=lambda g: sum(len(code) for _, code in g))
        group.append((m[1], m[0]))
    return preamble, groups


def report_header(header):
    # Copy complete class definitions verbatim to preserve the C++ ODR. Only the
    # owner operations actually used in the report partitions need inline bodies.
    parts = ['#pragma once', '#include <scpp/owner_access.hpp>',
             '#include <scpp/string/type.hpp>', 'namespace App {']
    for name in ("Dashboard", "ReportEvent"):
        owner = "owner_" + name
        start = header.index("struct " + owner + " {")
        end = header.index("\n};", start) + 3
        parts += ["namespace scpp_records { struct " + name + "; }", header[start:end]]
        start = header.index(f"inline {owner}::{owner}(const")
        end = header.index("inline void weak_", start)
        parts.append(header[start:end].strip())
    start = header.index("namespace scpp_records {\nstruct ReportEvent {")
    end = header.index("\n}", header.index("\n};", start) + 3) + 2
    parts.append(header[start:end])
    start = header.index("inline scpp_records::ReportEvent* owner_ReportEvent::operator->()")
    parts.append(header[start:header.index("\n}", start)+2])
    parts += [re.search(r"^void observe_\([^\n]+;",header,re.M)[0], "}"]
    return "\n".join(parts)+"\n"


def child_cpu():
    r = resource.getrusage(resource.RUSAGE_CHILDREN)
    return r.ru_utime + r.ru_stime


def run(cmd, **kw):
    result = subprocess.run(cmd, capture_output=True, **kw)
    if result.returncode:
        raise RuntimeError((cmd, result.stdout, result.stderr))
    return result


def group_pss_kib(sid):
    # Process-group PSS prorates shared pages. Sample only in separate runs.
    total = 0
    for stat in Path("/proc").glob("[0-9]*/stat"):
        try:
            fields = stat.read_text().rsplit(")", 1)[1].split()
            if int(fields[3]) == sid:
                rollup = (stat.parent / "smaps_rollup").read_text()
                total += int(re.search(r"^Pss:\s+(\d+)", rollup, re.M)[1])
        except (FileNotFoundError, ProcessLookupError, PermissionError):
            pass
    return total


def build(root, php, mold, app, work, config, memory=False):
    count, jobs = CONFIGS[config]
    emitted, native = work / "emit", work / "native"
    native.mkdir(exist_ok=True)
    cpu_start = child_cpu()
    start = time.perf_counter()
    res = run([php, root / "compiler/tests/incremental_executable.php", app, emitted], input=b'"run"\n')
    info = json.loads(res.stdout)
    if "error" in info:
        raise ValueError(info["error"])
    php_done = time.perf_counter()
    manifest = json.loads((emitted / "manifest.json").read_text())
    unit = emitted / next(iter(manifest["sources"].values()))
    for header in unit.parent.glob("*.hpp"):
        write_changed(native / header.name, header.read_bytes())
    write_changed(native / "entry.cpp", (emitted / "entry.cpp").read_bytes())
    groups = []
    if count == 1:
        write_changed(native / "app0.cpp", unit.read_bytes())
    else:
        header, groups = split_application(unit.read_text())
        write_changed(native / "common.hpp", header.encode())
        if config == "narrow-j4":
            write_changed(native / "report.hpp", report_header(header).encode())
        for i, group in enumerate(groups):
            include = "report.hpp" if i and config == "narrow-j4" else "common.hpp"
            source = f'#include "{include}"\n' + 'namespace App {\n' + "\n".join(body for _, body in group) + "}\n"
            write_changed(native / f"app{i}.cpp", source.encode())
    objects = [f"app{i}.o" for i in range(count)] + ["entry.o"]
    flags = ["-std=c++23", "-stdlib=libc++", "-O2", "-g0", "-I" + str(root / "runtime/generated/public")]
    graph = ["rule compile", "  command = clang++ " + shlex.join(flags) + " -MMD -MF $out.d -c $in -o $out",
             "  depfile = $out.d", "  deps = gcc", "rule link",
             "  command = clang++ -stdlib=libc++ --ld-path=" + shlex.quote(mold) + " $in -o $out"]
    graph.extend(f"build {obj}: compile {obj[:-2]}.cpp" for obj in objects)
    graph.append("build program: link " + " ".join(objects + manifest["link_inputs"]))
    write_changed(native / "build.ninja", ("\n".join(graph)+"\n").encode())
    before = native / ".ninja_log"
    offset = before.stat().st_size if before.exists() else 0
    ninja_start = time.perf_counter()
    native_cpu_start = child_cpu()
    cmd = ["ninja", "-j" + str(jobs), "program"]
    peak = 0
    if memory:
        with subprocess.Popen(cmd, cwd=native, start_new_session=True, stdout=subprocess.PIPE,
                              stderr=subprocess.STDOUT) as proc:
            while proc.poll() is None:
                peak = max(peak, group_pss_kib(proc.pid))
                time.sleep(.02)
            if proc.returncode:
                raise RuntimeError(proc.stdout.read())
    else:
        run(cmd, cwd=native)
    finish = time.perf_counter()
    cpu_finish = child_cpu()
    edges = {}
    for line in before.read_bytes()[offset:].decode().splitlines():
        if line and not line.startswith("#"):
            a,b,_,name,_ = line.split("\t")
            edges[name] = [int(a),int(b)]
    return dict(total_ms=(finish-start)*1000, php_wall_ms=(php_done-start)*1000,
                compiler_ms=info["php_ms"], publish_ms=(ninja_start-php_done)*1000,
                ninja_ms=(finish-ninja_start)*1000, native_cpu_ms=(cpu_finish-native_cpu_start)*1000,
                build_cpu_ms=(cpu_finish-cpu_start)*1000, edges_ms=edges, peak_pss_kib=peak,
                partition_bytes=[(native / f"app{i}.cpp").stat().st_size for i in range(count)],
                shared_header_bytes=(native / "common.hpp").stat().st_size if groups else 0,
                report_header_bytes=(native / "report.hpp").stat().st_size if config == "narrow-j4" else 0,
                object_list=objects, archives=manifest["link_inputs"])


def verify(binary, cases, edited):
    for path in cases:
        expected = (path / "expected.txt").read_bytes()
        if edited and OLD.encode() in expected:
            expected = expected.replace(OLD.encode(), NEW.encode())
        actual = run([binary], cwd=path).stdout
        if actual != expected:
            raise ValueError(f"Report mismatch for {path}, edited={edited}")


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("v02", type=Path)
    parser.add_argument("output", type=Path)
    parser.add_argument("--php", default="php")
    parser.add_argument("--cpu", type=int, default=8)
    parser.add_argument("--rounds", type=int, default=5)
    parser.add_argument("--compare-headers", action="store_true")
    args = parser.parse_args()
    if args.compare_headers:
        CONFIGS.clear()
        CONFIGS.update({"four-j4": (4, 4), "narrow-j4": (4, 4)})
    root = args.v02.resolve()
    os.environ["XDEBUG_MODE"] = "off"
    mold = shutil.which("ld.mold")
    if not mold or not shutil.which("ninja"):
        parser.error("mold and Ninja are required")
    rng = random.Random(146)
    rows, runtime, memory_rows = [], [], []
    source = (SOURCE / "main.php").read_text()
    assert source.count(OLD) == 1
    with tempfile.TemporaryDirectory() as tmp:
        base = Path(tmp)
        cases = [BASE / "access-log", base / "shared", base / "full"]
        for path in cases[1:]:
            fixture("access-log", 100, path, path.name == "full")
        assert OLD.encode() in (cases[2] / "expected.txt").read_bytes()
        drivers = {}
        for i in range(-1,args.rounds):
            configs = list(CONFIGS)
            rng.shuffle(configs)
            for config in configs:
                work = base / str(i) / config
                app = work / "app"
                app.mkdir(parents=True)
                shutil.copyfile(SOURCE / "module.json", app / "module.json")
                src = app / "main.php"
                src.write_text(source)
                fresh = build(root,args.php,mold,app,work,config,memory=i==-1)
                native = work / "native"
                verify(native / "program", cases, False)
                if i == -1:
                    memory_rows.append(dict(config=config,peak_pss_kib=fresh["peak_pss_kib"]))
                if i == 0:
                    drivers[config] = work / "timing"
                    run(["clang++","-std=c++23","-stdlib=libc++","-O2","-g0","--ld-path="+mold,
                         "-I"+str(root/"runtime/generated/public"), BASE/"runtime.cpp",
                         *(native/o for o in fresh["object_list"] if o!="entry.o"), *fresh["archives"],
                         "-o",drivers[config]])
                src.write_text(source.replace(OLD,NEW))
                edit = build(root,args.php,mold,app,work,config)
                verify(native / "program", cases, True)
                assert sum(x.endswith(".o") for x in edit["edges_ms"]) == 1
                if i >= 0:
                    for kind,row in [("fresh",fresh),("edit",edit)]:
                        rows.append(dict(round=i,config=config,kind=kind,**row))
        for i in range(7):
            jobs = [(config,path) for config in CONFIGS for path in cases[1:]]
            rng.shuffle(jobs)
            for config,path in jobs:
                result = run(["taskset","-c",str(args.cpu),drivers[config],path/"input.txt","24"])
                ns = int(result.stderr.split()[0])
                runtime.append(dict(round=i,config=config,case=path.name,cpu_ms_per_pass=ns/24e6))
    args.output.write_text(json.dumps(dict(host=platform.node(),ninja=run(["ninja","--version"]).stdout.decode().strip(),
                                           cpu=args.cpu, source_sha256=hashlib.sha256(source.encode()).hexdigest(),
                                           builds=rows,runtime=runtime,memory=memory_rows),indent=2)+"\n")


if __name__ == "__main__":
    main()
