"""Whole-project invalidation: fresh processes, mtime/size, unchanged output and failure retry."""
import json
import os
from pathlib import Path
import subprocess
import tempfile

root = Path(__file__).resolve().parents[2]

with tempfile.TemporaryDirectory(prefix='scpp-incremental-') as temporary:
    work = Path(temporary)
    app = work/'app'
    native = work/'native'
    output = app/'output'
    app.mkdir()
    native.mkdir()
    (app/'module.json').write_text(json.dumps({'dependencies': {'native': {'path': '../native'}}}))
    (native/'module.json').write_text('{"type":"c++"}')
    (native/'value.hpp').write_text('// v1\n')
    (native/'value.cpp').write_text('#include "value.hpp"\n')
    source = app/'main.cpp'
    source.write_text('return 8;')
    facts = work/'facts.json'
    facts.write_bytes((root/'build/clang/native-layout/facts.json').read_bytes())
    command = [os.environ.get('PHP_EXECUTABLE', 'php'), str(root/'compiler/main.php'),
               'project', str(app), str(facts), str(output)]

    def run(rebuilt=None, failure=False, force=False):
        result = subprocess.run(command + (['--force'] if force else []), capture_output=True, text=True,
                                env={**os.environ, 'XDEBUG_MODE': 'off'})
        if failure:
            assert result.returncode != 0, result.stdout
            return
        assert result.returncode == 0, result.stderr
        report = json.loads(result.stdout)
        run.report = report
        assert report['rebuilt'] is rebuilt, report
        return json.loads((output/'manifest.json').read_text())

    def snapshot():
        return {p.relative_to(output).as_posix(): (p.read_bytes(), p.stat().st_mtime_ns)
                for p in output.rglob('*') if p.is_file()}

    first = run(True)
    before = snapshot()
    run(False)
    assert snapshot() == before
    # Equal size and mtime are deliberately not detected; force is the explicit escape hatch.
    stamp = source.stat()
    source.write_text('return 9;')
    os.utime(source, ns=(stamp.st_atime_ns, stamp.st_mtime_ns))
    run(False)
    run(True, force=True)
    os.utime(source, (stamp.st_atime, stamp.st_mtime + 2))
    changed = run(True)
    assert run.report['changes'][str(source)] == 'changed'
    assert changed['build']['inputs'] != first['build']['inputs']
    run(False)
    # Full semantic compilation still runs, but identical generated text keeps its timestamp.
    extra = app/'other.php'
    extra.write_text('function helper(): void {}')
    added = run(True)
    assert run.report['changes'][str(extra)] == 'added'
    assert len(added['sources']) == 2
    unit = output/added['sources']['app/main.cpp']
    os.utime(unit, ns=(1, 1))
    extra.write_text('function helper(): void { echo "changed"; }')
    run(True)
    assert unit.stat().st_mtime_ns == 1
    # Header artifacts participate in cache validation and last-use cleanup too.
    source.write_text('helper(); return 9;')
    public = run(True)
    assert len(public['headers']) == 1
    header = output/public['headers'][0]
    run(False)
    header.unlink()
    run(True)
    assert header.is_file()
    run(False)
    source.write_text('return 9;')
    private = run(True)
    assert private['headers'] == [] and not header.exists()
    extra.unlink()
    removed = run(True)
    assert run.report['changes'][str(extra)] == 'removed'
    assert len(removed['sources']) == 1
    assert not (output/added['sources']['app/other.php']).exists()
    # Only app inputs and facts invalidate. Dependency sources, headers and artifacts are not tracked.
    for path, text in [(app/'module.json', (app/'module.json').read_text()+'\n'),
                       (native/'value.cpp', '#include "value.hpp"\n// changed\n'),
                       (native/'value.hpp', '// v2\n'), (native/'extra.hpp', '// new\n'),
                       (facts, facts.read_text()+'\n')]:
        old_time = path.stat().st_mtime if path.exists() else 0
        path.write_text(text)
        os.utime(path, (old_time + 2, old_time + 2))
        run(path.parent != native)
        run(False)
    (native/'extra.hpp').unlink()
    run(False)
    # Source failure never records a successful baseline and remains a failure on retry.
    before = snapshot()
    successful_stamp = source.stat()
    source.write_text('return;')
    run(failure=True)
    run(failure=True)
    assert snapshot() == before
    source.write_text('return 7;')
    os.utime(source, (successful_stamp.st_atime, successful_stamp.st_mtime + 2))
    run(True)
    # A graph failure also leaves successful output intact.
    before = snapshot()
    module_stamp = (native/'module.json').stat()
    (native/'module.json').write_text('{broken')
    run(failure=True)
    assert snapshot() == before
    (native/'module.json').write_text('{"type":"c++"}')
    os.utime(native/'module.json', ns=(module_stamp.st_atime_ns, module_stamp.st_mtime_ns))
    run(False)
    # Damaged or missing generated artifacts cannot hit the cache.
    unit.write_text('broken')
    run(True)
    unit.unlink()
    run(True)
    manifest = output/'manifest.json'
    data = json.loads(manifest.read_text())
    data['native_sources'] = []
    manifest.write_text(json.dumps(data))
    run(True)
    data = json.loads(manifest.read_text())
    data.pop('build')
    manifest.write_text(json.dumps(data))
    run(True)
    run(False)
print('Whole-project mtime/size checks, full rebuilds, unchanged reuse and failure retry passed')
