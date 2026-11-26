"""Run equivalent clean and incremental builds from independently published Ninja graphs."""
import hashlib
import json
from pathlib import Path
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[2]


def verify(work, publisher, cxx, linker):
    app, bundle = work/'app', work/'generated $: bundle'
    app.mkdir()
    manifest = app/'module.json'
    manifest.write_text('{"dependencies":{"runtime":{"modules":["filesystem"]}}}')
    (app/'main.php').write_text('echo number(); return 8;')
    body = app/'number.php'
    body.write_text('function number(): int { return 1; }')
    config = {'cxx': cxx, 'flags': ['-std=c++23', '-stdlib=libc++', '-I', str(ROOT/'runtime/generated/public'),
                                    "-DUNUSED_TEXT=\"a $b's\""],
              'link_flags': [f'--ld-path={linker}'], 'cache': str(work/'qualification')}
    cfg = work/'toolchain.json'
    cfg.write_text(json.dumps(config))
    phpdir, nativedir = work/'php', work/'native'
    command = [publisher, 'publish-ninja', app, bundle, nativedir, cfg]

    def lower():
        subprocess.run(['php', ROOT/'compiler/main.php', 'project', app, ROOT/'build/clang/native-layout/facts.json',
                        bundle], check=True, stdout=subprocess.DEVNULL)
        return json.loads((bundle/'manifest.json').read_text())

    def publish():
        request = dict(action='publish_graph', cwd=str(work), bundle=str(bundle), native=str(phpdir), config=config)
        result = subprocess.check_output(['php', ROOT/'compiler/tests/native_build_host.php'],
                                         input=json.dumps(request)+'\n', text=True)
        assert 'result' in json.loads(result), result
        subprocess.run(command, check=True)
        graph = (nativedir/'build.ninja').read_text()
        native_gate = f"'{publisher}' 'qualify'"
        python_gate = f"'python3' '-B' '{ROOT}/tools/layout_probe/qualification.py'"
        assert graph.count(native_gate) == 1 and python_gate not in graph
        assert graph.replace(native_gate, python_gate) == (phpdir/'build.ninja').read_text()

    def build(directory, outputs, value):
        log = directory/'.ninja_log'
        start = log.stat().st_size if log.exists() else 0
        subprocess.run(['ninja', '-C', directory, 'program'], check=True, stdout=subprocess.PIPE)
        lines = log.read_bytes()[start:].decode().splitlines()
        edges = [line.split('\t')[3] for line in lines if not line.startswith('#')]
        assert sorted(e for e in edges if e != '.scpp-native-context') == sorted(outputs), edges
        result = subprocess.run([directory/'program'], capture_output=True)
        assert (result.returncode, result.stdout, result.stderr) == (8, value, b''), result

    def obj(unit):
        return 'unit-' + hashlib.sha256(str(bundle/unit).encode()).hexdigest() + '.o'

    data = lower()
    units = data['translation_units']
    assert len(units) == 3 and data['link_inputs'], data
    changed, = [unit for key, unit in data['sources'].items() if key.endswith('/number.php')]
    publish()
    graph = nativedir/'build.ninja'
    timestamp = graph.stat().st_mtime_ns
    for directory in [phpdir, nativedir]:
        build(directory, [*(obj(u) for u in units), 'program'], b'1')
    lower()
    publish()
    assert graph.stat().st_mtime_ns == timestamp
    for directory in [phpdir, nativedir]:
        build(directory, [], b'1')
    body.write_text('function number(): int { return 2; }')
    lower()
    publish()
    assert graph.stat().st_mtime_ns == timestamp
    for directory in [phpdir, nativedir]:
        build(directory, [obj(changed), 'program'], b'2')

    manifest.write_text('{}')
    saved = graph.read_bytes()
    stale = subprocess.run(command, capture_output=True)
    assert stale.returncode and b'Republish the bundle' in stale.stderr, stale
    assert graph.read_bytes() == saved and graph.stat().st_mtime_ns == timestamp
    print('Both publishers: 3 compiles + link cold; no application edges unchanged; body edit 1 compile + link.')


if __name__ == '__main__':
    with tempfile.TemporaryDirectory() as tmp:
        verify(Path(tmp), *sys.argv[1:])
