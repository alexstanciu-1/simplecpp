#!/usr/bin/env python3
"""Build the strict workload with this checkout's legacy S2S and verify its output."""

import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import sys
import time


PROJECT = Path(__file__).resolve().parent
REPOSITORY = PROJECT.parents[1]
TARGET_BYTES = 512 * 1024


def source_inventory():
    files = [PROJECT / "main.phs", *sorted((PROJECT / "src").rglob("*.phs"))]
    manifest = []
    for path in files:
        content = path.read_bytes()
        manifest.append({
            "path": path.relative_to(PROJECT).as_posix(),
            "bytes": len(content),
            "lines": len(content.splitlines()),
            "sha256": hashlib.sha256(content).hexdigest(),
        })
    return {
        "files": manifest,
        "file_count": len(manifest),
        "source_bytes": sum(row["bytes"] for row in manifest),
        "source_lines": sum(row["lines"] for row in manifest),
        "target_bytes": TARGET_BYTES,
        "measurement": "Raw authored .phs bytes, including ordinary comments; no generated output, external fixtures, or docs",
    }


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--build-runtime", action="store_true", help="Build the project-local runtime when absent or stale")
    parser.add_argument("--size-only", action="store_true", help="Report source inventory without invoking the compiler")
    parser.add_argument("--require-target", action="store_true", help="Additionally require source size within 10 percent of 512 KiB")
    parser.add_argument("--php", default=os.environ.get("ACCOUNTING_PHP", shutil.which("php8.5") or shutil.which("php")), help="Host PHP executable")
    args = parser.parse_args()
    report = source_inventory()
    size_ok = TARGET_BYTES * 0.9 <= report["source_bytes"] <= TARGET_BYTES * 1.1
    report["size_target_met"] = size_ok
    print(f"Authored source: {report['source_bytes']:,} bytes in {report['file_count']} files ({report['source_lines']:,} lines)", flush=True)
    print(f"Source target: approximately {TARGET_BYTES:,} bytes; target met: {size_ok}", flush=True)
    if args.size_only:
        return 0 if size_ok or not args.require_target else 1
    if not args.php:
        parser.error("Host PHP not found; pass --php")
    command = [args.php, str(REPOSITORY / "bin" / "scpp.php"), "run"]
    if args.build_runtime:
        command.append("--build-runtime")
    started = time.monotonic()
    try:
        result = subprocess.run(command, cwd=PROJECT, text=True, stdout=subprocess.PIPE,
                                stderr=subprocess.STDOUT, timeout=600, check=False)
    except (OSError, subprocess.TimeoutExpired) as error:
        print(f"Verification could not complete: {error}", file=sys.stderr)
        return 1
    print(result.stdout, end="", flush=True)
    totals = re.findall(r"^CHECKS passed=(\d+) failed=(\d+)$", result.stdout, re.MULTILINE)
    markers = re.findall(r"^ACCOUNTING_WORKLOAD_(PASS|FAIL)$", result.stdout, re.MULTILINE)
    behavior_ok = (
        result.returncode == 0
        and len(totals) == 1
        and int(totals[0][0]) > 0
        and int(totals[0][1]) == 0
        and markers == ["PASS"]
        and not re.search(r"^FAIL ", result.stdout, re.MULTILINE)
    )
    report.update({
        "command": command,
        "compiler_route": "checkout bin/scpp.php -> generators/php, strict profile",
        "elapsed_seconds": round(time.monotonic() - started, 3),
        "process_exit_code": result.returncode,
        "behavior_passed": behavior_ok,
        "checks_passed": int(totals[0][0]) if len(totals) == 1 else None,
        "checks_failed": int(totals[0][1]) if len(totals) == 1 else None,
    })
    evidence = PROJECT / ".prism" / "workload-verification"
    evidence.mkdir(parents=True, exist_ok=True)
    (evidence / "output.log").write_text(result.stdout)
    (evidence / "report.json").write_text(json.dumps(report, indent=2) + "\n")
    if not behavior_ok:
        print("Workload verification FAILED: build/run or application checks failed.", file=sys.stderr)
        return 1
    if args.require_target and not size_ok:
        print("Behavior passed, but the approximately 512 KiB source target is not met.", file=sys.stderr)
        return 1
    print("Workload behavior verification passed.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
