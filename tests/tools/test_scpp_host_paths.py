#!/usr/bin/env python3
"""Target fact + pure two-policy examples; native evidence is only the host OS."""
import json
import os
from pathlib import Path
import subprocess
import tempfile

repo = Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory(prefix="scpp-host-paths-") as directory:
    project = Path(directory)
    (project / "main.phs").write_text((repo / "tests/tools/fixtures/host_paths/main.phs").read_text())
    (project / ("native.txt" if os.name == "nt" else "literal\\name")).write_text("data")
    (project / "prism.json").write_text(json.dumps({
        "entrypoint": "main.phs", "build": {"mode": "debug"},
        "runtime": {"modules": ["filesystem"], "languages": {"php": {"profile": "strict"}}},
    }))
    result = subprocess.run(["php", str(repo / "bin/scpp.php"), "run", "--build-runtime"], cwd=project, text=True, capture_output=True, timeout=180)
    expected = ("windows" if os.name == "nt" else "posix") + "\npolicy-ok\nrealpath-ok\n"
    assert result.returncode == 0 and result.stdout.endswith(expected), result.stdout + result.stderr
print("PASS: native host fact on " + os.name + "; pure Windows/POSIX policy examples; native realpath spelling")
