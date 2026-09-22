#!/usr/bin/env python3
"""Strict collection surface: real build/run plus build-gate rejection."""
import json
from pathlib import Path
import subprocess
import tempfile

repo = Path(__file__).resolve().parents[2]
cli = ["php", str(repo / "bin/scpp.php")]
with tempfile.TemporaryDirectory(prefix="scpp-collections-") as directory:
    project = Path(directory)
    source = project / "main.phs"
    source.write_text((repo / "tests/tools/fixtures/collections/main.phs").read_text())
    (project / "prism.json").write_text(json.dumps({
        "entrypoint": "main.phs", "build": {"mode": "debug"},
        "runtime": {"languages": {"php": {"profile": "strict"}}},
    }))

    def run(*args):
        return subprocess.run(cli + list(args), cwd=project, text=True, capture_output=True, timeout=180)

    built = run("build", "--build-runtime")
    assert built.returncode == 0, built.stdout + built.stderr
    result = run("run", "--build-runtime")
    expected = "x!\n2:3\n3:7\n8:name\n3:name\n4:boxed\n04:boxed\n1:99\n4:99\n04:2\nhash\nhash-box\n"
    expected += "0:20\n1:30\n1:20\n2:30\nseq:key\n3:0:3:0\n4:table\n04:table\n2\nnested\n"
    assert result.returncode == 0 and result.stdout.endswith(expected), result.stdout + result.stderr
    for statement, message in [
        ('$wrong vector<int> = collection_map($input, function (int $x): string { return "x"; });', 'Collection assignment'),
        ('collection_map($input, function (mixed $x): int { return 1; });', 'value parameter'),
        ('collection_filter($input, function (int $x): int { return $x; });', 'must return'),
        ('collection_map($input, function (int &$x): int { return $x; });', 'value parameter'),
        ('$hash hash<int, int> = [0 => 10, 1 => 20]; sequence_map($hash, function (int $x): string { return "x"; });', 'requires carrier family'),
        ('$hash hash<int, int> = [0 => 10, 1 => 20]; sequence_filter($hash, function (int $x): bool { return true; });', 'requires carrier family'),
        ('keyed_map($input, function (int $x): string { return "x"; });', 'requires carrier family'),
        ('keyed_filter($input, function (int $x): bool { return true; });', 'requires carrier family'),
        ('sequence_map($input, function (mixed $x): string { return "x"; });', 'value parameter'),
        ('$wrong vector<int> = sequence_map($input, function (int $x): string { return "x"; });', 'Collection assignment'),
    ]:
        source.write_text('$input vector<int> = [1];\n' + statement + '\n')
        rejected = run("build", "--build-runtime")
        assert rejected.returncode != 0, "invalid collection call built: " + statement
        # Public diagnostics can be summarized; saved STAN state retains the reason.
        diagnostic_text = rejected.stdout + rejected.stderr
        for path in (project / ".prism").rglob("*.json"):
            if "stan" in str(path):
                diagnostic_text += path.read_text()
        assert message in diagnostic_text, diagnostic_text
print("PASS: strict collections (all source carriers, captures, identity, build-gate rejection)")
