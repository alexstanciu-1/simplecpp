"""Replace a same-size, same-mtime manifest during an actual native inventory or publication."""
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile

from native_link_inputs import compare
from native_source_inventory import REFERENCE, ROOT


def verify(root, exe, compiler):
    app, bundle, output = root/'app', root/'bundle', root/'native'
    app.mkdir()
    manifest = app/'module.json'
    for name in ['main.phs', 'one.phs', 'two.phs']:
        (app/name).write_text('return 0;' if name == 'main.phs' else '')
    for name in ['left', 'right']:
        module = root/name
        module.mkdir()
        (module/'module.json').write_text(json.dumps({'name': name, 'artifacts': [name+'.a']}))
        (module/(name+'.a')).touch()

    def version(source, order):
        return json.dumps({'sources': ['main.phs', source+'.phs'],
                           'dependencies': {key: {'path': '../'+key} for key in order}})

    first = version('one', ['left', 'right'])
    second = version('two', ['right', 'left'])
    manifest.write_text(first)
    inventory = [exe, 'inventory', app]
    initial = json.loads(subprocess.check_output(inventory))
    initial_plan = compare(exe, app, bundle)
    config = root/'toolchain.json'
    config.write_text(json.dumps({'cxx': compiler, 'flags': ['-O2']}))
    publish = [exe, 'publish-ninja', app, bundle, output, config]
    subprocess.run(publish, check=True)
    graph = output/'build.ninja'
    saved = graph.read_bytes(), graph.stat().st_mtime_ns
    shim = root/'replace.so'
    subprocess.run([compiler, '-shared', '-fPIC', Path(__file__).with_name('manifest_replace_read.cpp'),
                    '-o', shim], check=True)

    def race(command, trigger, diagnostic):
        manifest.write_text(first)
        replacement = root/'next.json'
        replacement.write_text(second)
        before = manifest.stat()
        os.utime(replacement, ns=(before.st_atime_ns, before.st_mtime_ns))
        assert replacement.stat().st_size == before.st_size and replacement.stat().st_ino != before.st_ino
        selector = root/'trigger'
        selector.symlink_to(trigger)
        try:
            proc = subprocess.run(command, cwd=root, capture_output=True, timeout=5,
                                  env={**os.environ, 'LD_PRELOAD': str(shim)})
        finally:
            selector.unlink()
        assert manifest.read_text() == second and not replacement.exists()
        assert proc.returncode == 1 and not proc.stdout and diagnostic in proc.stderr, proc

    race(inventory, manifest, b'Cannot read stable module manifest')
    # The app was already read when the first dependency opens. Both consumers must reject its old contents.
    race(inventory, root/'left/module.json', b'Module manifest changed')
    config.write_text(json.dumps({'cxx': compiler, 'flags': ['-O0']}))
    race(publish, root/'left/module.json', b'Module manifest changed')
    assert (graph.read_bytes(), graph.stat().st_mtime_ns) == saved
    rows = json.loads(subprocess.check_output(inventory))
    fresh = json.loads(subprocess.check_output(['php', '-r', REFERENCE, ROOT, app]))
    assert list(rows.items()) == list(fresh.items()) and rows != initial
    plan = compare(exe, app, bundle)
    assert plan['link_inputs'] != initial_plan['link_inputs']
    subprocess.run(publish, check=True)
    repaired = graph.read_bytes()
    assert repaired != saved[0]
    graph.unlink()
    subprocess.run(publish, check=True)
    assert graph.read_bytes() == repaired


if __name__ == '__main__':
    with tempfile.TemporaryDirectory() as directory:
        verify(Path(directory), Path(sys.argv[1]).resolve(), sys.argv[2])
