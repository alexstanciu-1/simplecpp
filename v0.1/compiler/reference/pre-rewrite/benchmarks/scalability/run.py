"""Measure real native compiles in fresh PHP processes and disposable project copies."""

import argparse
from datetime import datetime, timezone
import hashlib
import json
import os
from pathlib import Path
import platform
import shutil
import statistics
import subprocess
import tempfile


ROOT = Path(__file__).resolve().parents[2]
HERE = Path(__file__).resolve().parent


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--trials", type=int, default=3)
    parser.add_argument("--sizes", nargs="+", default=["64kb", "128kb", "256kb", "512kb", "1mb"])
    parser.add_argument("--results", type=Path, default=ROOT / "build/scalability/results.json")
    parser.add_argument("--trace-tools", action="store_true", help="Separate diagnostic run with native tool time/CPU/peak RSS; includes wrapper overhead")
    args = parser.parse_args()
    if args.trials < 1:
        parser.error("--trials must be positive")
    inventory = json.loads((ROOT / "examples/scalability/inventory.json").read_text())
    if set(args.sizes) - {row["project"] for row in inventory}:
        parser.error("Unknown project size")
    php = shutil.which("php")
    if php is None:
        raise SystemExit("PHP CLI required")
    command = [php, "-d", "memory_limit=2048M", "-d", "opcache.enable_cli=0"]
    env = dict(os.environ, XDEBUG_MODE="off")
    version = subprocess.check_output(command + ["-r", 'echo json_encode(["version" => PHP_VERSION, "memory_limit" => ini_get("memory_limit"), "opcache_cli" => ini_get("opcache.enable_cli"), "jit" => ini_get("opcache.jit"), "xdebug_mode_env" => getenv("XDEBUG_MODE")]);'], env=env, text=True)
    backend = json.loads((ROOT / "tools/backend.json").read_text())
    clang = subprocess.check_output([backend["clang"], "--version"], text=True).strip()
    digest = hashlib.sha256()
    sources = [*sorted((ROOT / "src").rglob("*.php")),
               *sorted((ROOT / "tool_process").rglob("*.php")), ROOT / "bootstrap.php",
               *sorted((ROOT / "language").rglob("*.json")), ROOT / "tools/backend.json"]
    for path in sources:
        digest.update(str(path.relative_to(ROOT)).encode() + b"\0" + path.read_bytes() + b"\0")
    report = {"utc": datetime.now(timezone.utc).isoformat(), "platform": platform.platform(),
              "cpu": next((line.split(":", 1)[1].strip() for line in Path('/proc/cpuinfo').read_text().splitlines()
                           if line.startswith('model name')), 'unknown'),
              "php": json.loads(version), "clang": clang, "backend": backend,
              "compiler_sha256": digest.hexdigest(), "trials_per_size": args.trials, "trace_tools": args.trace_tools,
              "timing": "hrtime around Compiler_Session::compile; includes tool probes, native object compilation and linking; excludes bootstrap, fixture copies, counters, exports and executable verification",
              "memory": "PHP allocator peak per phase, reset before compile; includes live resident snapshots, excludes external Clang/linker memory",
              "timeout_seconds_per_trial": 180, "projects": []}
    if args.trace_tools:
        report['tool_measurement'] = {
            'wall_time': 'perf_counter around each real Clang invocation, excluding Python wrapper startup',
            'cpu_time': 'Linux RUSAGE_CHILDREN user/system CPU for the single invocation and waited descendants',
            'memory': 'Linux RUSAGE_CHILDREN ru_maxrss * 1024 bytes: largest process RSS high-water within an invocation, including waited descendants; not simultaneous process-tree total',
            'link_scope': 'Clang link-driver invocation including the real linker; not linker-only isolation',
            'parallel_timing': 'started_ns/ended_ns use the common monotonic clock; tool_seconds sums overlapping invocation times, not native-stage wall time',
            'wrapper': 'fresh Python process per invocation; wrapper/PHP RSS excluded',
        }
    args.results.parent.mkdir(parents=True, exist_ok=True)
    failed = False
    for project in inventory:
        if project["project"] not in args.sizes:
            continue
        source = ROOT / "examples/scalability" / project["project"]
        files = sorted(source.rglob("*.phs"))
        source_bytes = sum(path.stat().st_size for path in files)
        source_digest = hashlib.sha256()
        for path in files:
            source_digest.update(str(path.relative_to(source)).encode() + b"\0" + path.read_bytes() + b"\0")
        if source_bytes != project["source_bytes"] or source_digest.hexdigest() != project["source_sha256"]:
            raise SystemExit(f"Fixture differs from inventory: {source}")
        record = dict(project, trials=[])
        report["projects"].append(record)
        for trial in range(1, args.trials + 1):
            with tempfile.TemporaryDirectory(prefix="scpp_scale_") as temporary:
                work = Path(temporary)
                shutil.copytree(source, work / "project")
                invocation = command + [str(HERE / "measure.php"), str(work / "project/project.json"),
                                        str(work / "program"), str(work / "project" / project["edit_file"])]
                trial_env = env.copy()
                if args.trace_tools:
                    wrapper = work / 'clang-timed'
                    shutil.copyfile(HERE / 'time_clang.py', wrapper)
                    wrapper.chmod(0o755)
                    configuration = dict(backend, clang=str(wrapper))
                    (work / 'backend.json').write_text(json.dumps(configuration))
                    (work / 'tools.jsonl').touch()
                    trial_env['SCPP_BENCH_CLANG'] = shutil.which(backend['clang']) or backend['clang']
                    trial_env['SCPP_BENCH_TOOL_LOG'] = str(work / 'tools.jsonl')
                    invocation += [str(work / 'backend.json'), str(work / 'tools.jsonl')]
                try:
                    result = subprocess.run(invocation, cwd=ROOT, env=trial_env, capture_output=True, text=True, timeout=180)
                    rows = [json.loads(line) for line in result.stdout.splitlines() if line.strip()]
                    ok = result.returncode == 0 and [r["phase"] for r in rows] == ["cold", "unchanged", "body_edit"]
                    entry = {"trial": trial, "ok": ok, "measurements": rows, "stderr": result.stderr}
                except subprocess.TimeoutExpired as error:
                    entry = {"trial": trial, "ok": False, "measurements": [], "stderr": "Trial exceeded 180 seconds"}
                record["trials"].append(entry)
                if not entry["ok"]:
                    failed = True
                    print(f"{project['project']} trial {trial}: FAILED {entry['stderr']}", flush=True)
                else:
                    print(f"{project['project']} trial {trial}: " + ", ".join(
                        f"{r['phase']}={r['seconds']:.3f}s/{r['php_peak_allocated_bytes']/1048576:.0f}MiB" for r in rows), flush=True)
                args.results.write_text(json.dumps(report, indent=2) + "\n")
        record["summary"] = {}
        for phase in ("cold", "unchanged", "body_edit"):
            rows = [row for trial in record["trials"] if trial["ok"] for row in trial["measurements"] if row["phase"] == phase]
            if rows:
                record["summary"][phase] = {"median_seconds": statistics.median(row["seconds"] for row in rows),
                    "min_seconds": min(row["seconds"] for row in rows), "max_seconds": max(row["seconds"] for row in rows),
                    "max_php_peak_bytes": max(row["php_peak_bytes"] for row in rows),
                    "max_php_peak_allocated_bytes": max(row["php_peak_allocated_bytes"] for row in rows)}
        args.results.write_text(json.dumps(report, indent=2) + "\n")
    print(f"Results: {args.results}", flush=True)
    if failed:
        raise SystemExit(1)


if __name__ == "__main__":
    main()
