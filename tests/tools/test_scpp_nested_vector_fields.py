#!/usr/bin/env python3
"""Issue #232: actual strict build/run for chained fields and independent copies."""
import json
from pathlib import Path
import subprocess
import tempfile

repo = Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory(prefix="scpp-nested-vector-fields-") as directory:
    project = Path(directory)
    (project / "main.phs").write_text((repo / "tests/tools/fixtures/nested_vector_fields/main.phs").read_text())
    (project / "prism.json").write_text(json.dumps({
        "entrypoint": "main.phs", "build": {"mode": "debug"},
        "runtime": {"modules": [], "languages": {"php": {"profile": "strict"}}},
    }))
    result = subprocess.run(["php", str(repo / "bin/scpp.php"), "run", "--build-runtime"],
                            cwd=project, text=True, capture_output=True, timeout=180)
    assert result.returncode == 0, result.stdout + result.stderr
    assert result.stdout.endswith("1:12:3:12\n37:37:12\n55:12\n3:21:12\n"), result.stdout
print("PASS: nested vector fields (visibility, deeper reads, local copies, direct writes/appends)")
