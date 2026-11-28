"""Measure native driver rebuilds from the production PHP host's saved graph."""
import hashlib
import json
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile

from app_namespace_native import invoke, service
from translated_native_driver import build_driver
from translated_vector_recipe import ROOT, source_edit


def verify(work, cxx, linker):
    driver = build_driver(work, cxx, linker)
    app, bundle, native = work/'app', work/'generated', work/'native objects'
    app.mkdir()
    (app/'module.json').write_text('{}')
    (app/'main.phs').write_text('echo number(); return 8;')
    helper = app/'number.phs'
    helper.write_text('function number(): int { return 1; }')
    compiler = service(app, bundle)
    command = [driver, '--ninja', bundle, native, shutil.which('ninja')]
    config = {'cxx': cxx, 'flags': ['-std=c++23', '-stdlib=libc++', '-I', str(ROOT/'runtime/generated/public')],
              'link_flags': [f'--ld-path={linker}']}
    request = dict(action='publish_graph', cwd=str(work), bundle=str(bundle), native=str(native), config=config)

    def publish():
        response = subprocess.check_output(['php', ROOT/'compiler/tests/native_build_host.php'],
                                           input=json.dumps(request)+'\n', text=True)
        assert json.loads(response).get('result') == {'graph': str(native/'build.ninja')}, response

    def build(expected, outputs):
        log = native/'.ninja_log'
        before = log.read_bytes() if log.exists() else b''
        result = subprocess.run(command, capture_output=True, timeout=30)
        assert result.returncode == 8 and result.stdout.endswith(expected), result
        after = log.read_bytes()
        assert after.startswith(before)
        edges = [line.split('\t')[3] for line in after[len(before):].decode().splitlines()
                 if not line.startswith('#')]
        application = [name for name in edges if name != '.scpp-native-context']
        assert sorted(application) == sorted(outputs), application

    def object_name(unit):
        return 'unit-' + hashlib.sha256(str(unit).encode()).hexdigest() + '.o'

    try:
        assert 'error' not in invoke(compiler)
        publish()
        units = [bundle/name for name in json.loads((bundle/'manifest.json').read_text())['translation_units']]
        before = {unit: unit.read_bytes() for unit in units}
        build(b'1', [*(object_name(unit) for unit in units), 'program'])
        build(b'1', [])
        source_edit(helper, 'function number(): int { return 2; }')
        assert 'error' not in invoke(compiler)
        changed, = [unit for unit in units if unit.read_bytes() != before[unit]]
        build(b'2', [object_name(changed), 'program'])
        saved = changed.read_bytes()
        changed.write_bytes(saved + b'\n#error ninja_build_failed\n')
        failed = subprocess.run(command, capture_output=True, timeout=10)
        assert failed.returncode not in (0, 8) and b'ninja_build_failed' in failed.stdout, failed
        changed.write_bytes(saved)
        build(b'2', [object_name(changed), 'program'])
        (app/'extra.phs').write_text('function extra(): int { return 3; }')
        assert 'error' not in invoke(compiler)
        stale = subprocess.run(command, capture_output=True, timeout=10)
        assert stale.returncode not in (0, 8) and b'Republish the Ninja graph' in stale.stderr, stale
        publish()
        updated = json.loads((bundle/'manifest.json').read_text())['translation_units']
        extra, = [bundle/name for name in updated if bundle/name not in before]
        build(b'2', [object_name(extra), 'program'])
        print(f'Cold: {len(units)} compiles + 1 link; unchanged: 0 + 0; body edit: 1 + 1')
    finally:
        compiler.stdin.close()
        compiler.wait(timeout=10)


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-ninja-driver-') as tmp:
        verify(Path(tmp), *sys.argv[1:])
