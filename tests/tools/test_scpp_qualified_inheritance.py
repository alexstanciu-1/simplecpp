#!/usr/bin/env python3
"""#233: real multi-file strict inheritance, namespace collisions and exception identity."""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile

repo = Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory(prefix="scpp-qualified-inheritance-") as directory:
    project = Path(directory)
    for source in (repo / "tests/tools/fixtures/qualified_inheritance").glob("*.phs"):
        shutil.copyfile(source, project / source.name)
    (project / "prism.json").write_text(json.dumps({
        "entrypoint": "main.phs", "build": {"mode": "debug"},
        "runtime": {"modules": [], "languages": {"php": {"profile": "strict"}}},
    }))
    result = subprocess.run(["php", str(repo / "bin/scpp.php"), "run", "--build-runtime"],
                            cwd=project, text=True, capture_output=True, timeout=180)
    assert result.returncode == 0, result.stdout + result.stderr
    assert result.stdout.endswith("7:7:7\n11:11:13:13:17\ndiagnostic:changed\nchanged:same\n"), result.stdout
    header = (project / ".prism/generated/child.hpp").read_text()
    assert "class Child : public ::scpp::Root_Base" in header, header
    assert "class Cross : public ::scpp::models::Base" in header, header
    assert "class Contract : public ::scpp::Root_Contract" in header, header
print("PASS: qualified inheritance, local/cross-namespace bases, parent calls and typed exception identity")
