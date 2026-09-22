#!/usr/bin/env python3
"""#240: schema-relevant JSON distinctions through normal strict/STAN build/run."""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile

repo = Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory(prefix="scpp-json-document-") as directory:
    project = Path(directory)
    for source in (repo / "tests/tools/fixtures/json_document").glob("*.phs"):
        shutil.copyfile(source, project / source.name)
    (project / "prism.json").write_text(json.dumps({
        "entrypoint": "main.phs", "build": {"mode": "debug"},
        "runtime": {"modules": ["json"], "languages": {"php": {"profile": "strict"}}},
    }))
    def run(*args):
        return subprocess.run(["php", str(repo / "bin/scpp.php"), *args], cwd=project,
                              text=True, capture_output=True, timeout=240)
    result = run("run", "--build-runtime")
    expected = "accept\nlist\nlist\nnonempty\nstring\nobject\nmissing\n3\n0\nmissing\nlast\nfalse\n9223372036854775808\nrange\nsyntax:1\nreset\nnull\ndepth:1\n"
    assert result.returncode == 0 and result.stdout.endswith(expected), result.stdout + result.stderr
    analysis = run("stan")
    assert analysis.returncode == 0, analysis.stdout + analysis.stderr
    report = json.loads((project / ".prism/cache/stan_report.json").read_text())
    assert report["compile_error_count"] == 0 and report["stan_error_count"] == 0, report
    (project / "main.phs").write_text('$diag json_parse_error = null;\njson_document_parse("null", $diag, "bad depth");\n')
    rejected = run("build")
    assert rejected.returncode != 0, rejected.stdout + rejected.stderr
    analysis = run("stan")
    diagnostic = analysis.stdout + analysis.stderr + (project / ".prism/cache/stan_report.json").read_text()
    assert "json_document_parse" in diagnostic and "int" in diagnostic and "string" in diagnostic, diagnostic
print("PASS: JSON document manifest shapes, wrappers, lifetimes, signatures and STAN diagnostics")
