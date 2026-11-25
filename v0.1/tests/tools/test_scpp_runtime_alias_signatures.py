#!/usr/bin/env python3
"""Issue #231: runtime alias signatures in real multi-file facade builds."""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile

repo = Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory(prefix="scpp-runtime-alias-signatures-") as directory:
    project = Path(directory)
    for source in (repo / "tests/tools/fixtures/runtime_alias_signatures").glob("*.phs"):
        shutil.copyfile(source, project / source.name)
    (project / "prism.json").write_text(json.dumps({
        "entrypoint": "main.phs", "build": {"mode": "debug"},
        "runtime": {"modules": ["filesystem", "process"], "languages": {"php": {"profile": "strict"}}},
    }))
    result = subprocess.run(["php", str(repo / "bin/scpp.php"), "run", "--build-runtime"],
                            cwd=project, text=True, capture_output=True, timeout=180)
    assert result.returncode == 0, result.stdout + result.stderr
    assert result.stdout.endswith("alias-signatures-ok\n"), result.stdout
    headers = "\n".join(p.read_text() for p in (project / ".prism/generated").rglob("*.hpp"))
    for alias in ("file_lock_handle", "process_handle", "process_output"):
        assert "class " + alias + ";" not in headers, "conflicting alias declaration: " + alias
    assert "class UserRecord;" in headers, "ordinary user-class forward declaration lost"
    assert (project / "alias.lock").exists(), "lock release changed stable file identity"
print("PASS: runtime alias signatures (multi-file parameters, returns, references, fields and methods)")
