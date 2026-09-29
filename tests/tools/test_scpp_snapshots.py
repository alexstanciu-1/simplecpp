#!/usr/bin/env python3
"""Linux snapshot strict/PHS build, wrapped returns, failures and STAN diagnostics."""
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile

repo = Path(__file__).resolve().parents[2]
if sys.platform != "linux":
    print("SKIP: snapshot backend is Linux-only")
    sys.exit(0)
with tempfile.TemporaryDirectory(prefix="scpp-snapshots-") as directory:
    project = Path(directory)
    source = project / "main.phs"
    source.write_text((repo / "tests/tools/fixtures/snapshots/main.phs").read_text())
    for name, content in [("unchanged", "abcdef"), ("empty", "")]:
        (project / name).write_text(content)
        os.utime(project / name, (1700000000, 1700000000))
    (project / "link").symlink_to("unchanged")
    (project / "directory").mkdir()
    (project / "prism.json").write_text(json.dumps({
        "entrypoint": "main.phs", "build": {"mode": "debug"},
        "runtime": {"modules": ["filesystem"], "languages": {"php": {"profile": "strict"}}},
    }))
    def run(*args):
        return subprocess.run(["php", str(repo / "bin/scpp.php"), *args], cwd=project, text=True, capture_output=True, timeout=180)
    result = run("run", "--build-runtime")
    assert result.returncode == 0 and result.stdout.endswith("snapshot-ok\n"), result.stdout + result.stderr
    source.write_text('fs_read_snapshot("unchanged", "wrong timestamp", 6);\n')
    rejected = run("build")
    assert rejected.returncode != 0, rejected.stdout + rejected.stderr
    analysis = run("stan")
    diagnostic = analysis.stdout + analysis.stderr
    assert "fs_read_snapshot" in diagnostic and "int" in diagnostic and "string" in diagnostic, diagnostic
print("PASS: strict snapshots (real build/run, result extraction and argument rejection)")
