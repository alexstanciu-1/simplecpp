"""Compare ordered activation, actual dependency edges, and first errors against Module_Loader."""
import json
from pathlib import Path
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[2]
ORACLE = r'''
require $argv[1] . '/compiler/boot.php';
try {
    $loader = new scpp\compiler\Module_Loader($argv[3] ?? null);
    $session = $loader->load($argv[2]);
    $graph = [];
    foreach ($session->modules as $name => $module) $graph[$name] = array_keys($module->dependencies);
    echo json_encode($graph, JSON_THROW_ON_ERROR);
} catch (Throwable $e) { fwrite(STDERR, $e->getMessage() . "\n"); exit(1); }
'''


def compare(exe, app, runtime=None, *, rejects=False):
    native = [exe, 'inventory', app, '--graph']
    oracle = ['php', '-r', ORACLE, ROOT, app]
    if runtime:
        native += ['--runtime', runtime]
        oracle.append(runtime)
    actual, expected = [subprocess.run(cmd, capture_output=True, timeout=5) for cmd in (native, oracle)]
    assert actual.returncode == expected.returncode == int(rejects), (actual, expected)
    assert actual.stderr == expected.stderr, (actual.stderr, expected.stderr)
    if rejects:
        assert not actual.stdout and not expected.stdout and actual.stderr
        return
    graph = json.loads(actual.stdout)
    assert list(graph.items()) == list(json.loads(expected.stdout).items()), (actual.stdout, expected.stdout)
    return graph


def verify(root, exe):
    def write(name, data):
        path = root/name
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(json.dumps(data))
        return path

    runtime = write('sdk/module.json', {'name': 'runtime'})
    app = write('app/module.json', {'name': 'app', 'dependencies': {'runtime': {'modules': ['json']}}})
    (app.parent/'main.phs').write_text('return 0;')
    real = compare(exe, app)
    assert 'runtime-boxed' in real['runtime-json'] and real['app'] == ['runtime']
    base = {'name': 'app', 'sources': ['main.phs'], 'dependencies': {
        'left': {'path': '../left.json', 'modules': ['extension', 'extension']},
        'right': {'path': '../right.json'}}}
    left = {'name': 'left', 'defaults': ['extension'], 'modules': {'extension': 'ext.json', 'unused': 'missing.json'},
            'dependencies': {'shared': {'path': 'shared.json'}}}
    write('app/module.json', base)
    write('left.json', left)
    write('right.json', {'name': 'right', 'dependencies': {'shared': {'path': './shared.json'}}})
    write('shared.json', {'name': 'shared'})
    write('ext.json', {'name': 'extension', 'dependencies': {'left': {'path': 'left.json'}}})
    graph = compare(exe, app, runtime)
    assert graph['extension'] == ['left'] and graph['app'] == ['left', 'right', 'runtime']
    assert list(graph).count('shared') == 1 and 'unused' not in graph

    # Activation may return to a previously selected module without a dependency cycle.
    write('ext.json', {'name': 'extension', 'modules': {'back': 'left.json'}, 'defaults': ['back']})
    assert compare(exe, app, runtime)['extension'] == []
    # A cycle wholly inside an activation-only branch must still reject.
    write('ext.json', {'name': 'extension', 'dependencies': {'extension': {'path': 'ext.json'}}})
    compare(exe, app, runtime, rejects=True)
    write('ext.json', {'name': 'extension'})
    failures = [
        {'name': 'bad name'}, {'type': 'other'}, {'unexpected': False}, {'dependencies': []},
        {'modules': []}, {'modules': {'x': 42}}, {'defaults': 'x'}, {'defaults': ['']},
        {'artifacts': [False]}, {'defaults': ['absent']}, {'dependencies': {'left': []}},
        {'dependencies': {'left': {}}}, {'dependencies': {'left': {'modules': [], 'extra': True}}},
        {'dependencies': {'left': {'path': 1}}}, {'dependencies': {'left': {'path': ''}}},
        {'dependencies': {'left': {'path': '../absent.json'}}},
        {'dependencies': {'other': {'path': '../left.json'}}},
        {'dependencies': {'left': {'path': '../left.json', 'modules': {}}}},
        {'dependencies': {'left': {'path': '../left.json', 'modules': [2]}}},
        {'dependencies': {'left': {'path': '../left.json', 'modules': ['missing']}}},
    ]
    for bad in failures:
        write('app/module.json', {'name': 'app', 'sources': ['main.phs'], **bad})
        compare(exe, app, runtime, rejects=True)
    write('app/module.json', base)
    write('right.json', {'name': 'right', 'dependencies': {'app': {'path': 'app'}}})
    compare(exe, app, runtime, rejects=True)
    write('right.json', {'name': 'left'})
    compare(exe, app, runtime, rejects=True)
    write('sdk/module.json', {'name': 'other'})
    compare(exe, app, runtime, rejects=True)
    print(f'3 ordered graphs and {len(failures) + 4} exact rejection diagnostics matched PHP.')


if __name__ == '__main__':
    with tempfile.TemporaryDirectory() as tmp:
        verify(Path(tmp), Path(sys.argv[1]).resolve(strict=True))
