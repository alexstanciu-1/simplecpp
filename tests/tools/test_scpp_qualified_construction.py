#!/usr/bin/env python3
"""#235: absolute construction, method/type collision and distinct namespace identities."""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile

repo = Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory(prefix="scpp-qualified-construction-") as directory:
    project = Path(directory)
    for source in (repo / "tests/tools/fixtures/qualified_construction").glob("*.phs"):
        shutil.copyfile(source, project / source.name)
    (project / "prism.json").write_text(json.dumps({
        "entrypoint": "main.phs", "build": {"mode": "debug"},
        "runtime": {"modules": [], "languages": {"php": {"profile": "strict"}}},
    }))
    result = subprocess.run(["php", str(repo / "bin/scpp.php"), "run", "--build-runtime"],
                            cwd=project, text=True, capture_output=True, timeout=180)
    assert result.returncode == 0, result.stdout + result.stderr
    assert result.stdout.endswith("7:7:11:13\n"), result.stdout + result.stderr
    analysis = subprocess.run(["php", str(repo / "bin/scpp.php"), "stan"],
                              cwd=project, text=True, capture_output=True, timeout=180)
    assert analysis.returncode == 0, analysis.stdout + analysis.stderr
    report = json.loads((project / ".prism/cache/stan_report.json").read_text())
    assert report["compile_error_count"] == 0, report
    # Existing STAN basename ambiguity and short-vs-qualified return advisories
    # are separate from rooted construction emission; keep native identity proof.
    for diagnostic in report["diagnostics"]:
        assert diagnostic["kind"] in {"ambiguous_dependency", "return_type_mismatch"}, diagnostic
        if diagnostic["kind"] == "return_type_mismatch":
            assert diagnostic["context"] == "example\\Queries::row", diagnostic
        else:
            assert diagnostic["target"] in {"row", "example\\row", "other\\row"}, diagnostic
    generated = (project / ".prism/generated/model.cpp").read_text()
    assert "create<::scpp::example::row>" in generated, generated
print("PASS: qualified constructors, method/type collision, ordinary/global/cross-namespace identities")
