#!/usr/bin/env python3
"""Issue #242: compile/run real strict PHS, with the normal STAN build gate."""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile

repo = Path(__file__).resolve().parents[2]
cli = ["php", str(repo / "bin/scpp.php")]
fixture = repo / "tests/tools/fixtures/compiler_storage"
with tempfile.TemporaryDirectory(prefix="scpp-storage-source-") as directory:
    project = Path(directory)
    for path in fixture.iterdir():
        if path.is_file():
            shutil.copy2(path, project / path.name)

    def run(*args):
        result = subprocess.run(cli + list(args), cwd=project, text=True, capture_output=True, timeout=240)
        return result, result.stdout + result.stderr

    result, output = run("run", "--build-runtime")
    assert result.returncode == 0 and result.stdout.endswith("compiler storage source bindings: ok\n"), output
    print("PASS strict multi-file consumer: static/nested collections, aliases, lifetime, holes, exact order", flush=True)

    source = project / "main.phs"
    prefix = "namespace CompilerFixture;\nclass Row {}\n$rows Storage<Row> = new Storage<Row>();\n$map Keyed_Storage<Row> = new Keyed_Storage<Row>();\n$row Row = new Row();\n"
    # Each runtime rejection must first pass STAN and native compilation.
    for statement, message in [
        ('$old Row = $rows[0];', 'Storage position is absent'),
        ('$old Row = $map["missing"];', 'Keyed_Storage key is absent'),
        ('$rows[0] = $row;', 'Storage position is absent'),
        ('$rows->remove(0);', 'Storage position is absent'),
        ('$map->replace("missing", $row);', 'Keyed_Storage key is absent'),
        ('$map->add("x", $row); $map->add("x", $row);', 'key already exists'),
        ('$rows[] = null;', 'requires a non-null record'),
        ('$map["x"] = null;', 'requires a non-null record'),
        ('$bad Storage<Row> = new Storage<Row>(-1);', 'capacity must be non-negative'),
        ('$map->reserve(-1);', 'capacity must be non-negative'),
    ]:
        source.write_text(prefix + statement + "\n")
        result, output = run("run")
        report = json.loads((project / '.prism/last_error.json').read_text())
        assert result.returncode != 0 and report['category'] == 'runtime', output
        assert message in json.dumps(report), output + json.dumps(report)
    print("PASS source runtime rejection: missing reads/writes, duplicates, nulls, negative capacity", flush=True)

    for statement in [
        '$rows->append(12);',
        '$map->add(0, $row);',
        '$map->append($row);',
        '$rows["0"] = $row;',
        '$map[0] = $row;',
        '$bad Storage<shared<Row>>;',
        '$bad Storage<Row> = new Storage<Row>(true);',
        'foreach ($rows as &$record) {}',
    ]:
        source.write_text(prefix + statement + "\n")
        result, output = run("build")
        assert result.returncode != 0, statement + " was accepted\n" + output
    print("PASS source compile rejection: wrong record/key/capacity, double wrapping, keyed append, reference iteration", flush=True)
print("PASS compiler Storage strict source bindings")
