#!/usr/bin/env python3
"""Managed process public strict surface and module composition."""
import json
from pathlib import Path
import subprocess
import tempfile

repo = Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory(prefix="scpp-process-") as directory:
    project = Path(directory)
    config = {"entrypoint": "main.phs", "build": {"mode": "debug"},
              "runtime": {"languages": {"php": {"profile": "strict"}}, "modules": ["process"]}}
    (project / "prism.json").write_text(json.dumps(config))
    source = project / "main.phs"
    source.write_text((repo / "tests/tools/fixtures/process/main.phs").read_text())
    cli = ["php", str(repo / "bin/scpp.php")]
    def run(*args):
        return subprocess.run(cli + list(args), cwd=project, capture_output=True, text=True, timeout=180)
    result = run("run", "--build-runtime")
    assert result.returncode == 0 and result.stdout.endswith("input:error:7\nprocess-ok\n0\n"), result.stdout + result.stderr
    source.write_text('process_poll("wrong");\n')
    result = run("build", "--build-runtime")
    assert result.returncode != 0, "string accepted as a process handle"
    source.write_text((repo / "tests/tools/fixtures/process/main.phs").read_text())
    config["runtime"]["modules"] = []
    (project / "prism.json").write_text(json.dumps(config))
    result = run("build", "--build-runtime")
    assert result.returncode != 0, "process implementation linked without module selection"
    assert "undefined" in result.stdout + result.stderr, result.stdout + result.stderr
    print("PASS: strict managed process build/run and handle boundary and module exclusion")
