"""Compare the native plan with module artifacts and the PHP backend's published link inputs."""
import json
from pathlib import Path
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[2]
ORACLE = r'''
require $argv[1] . '/compiler/boot.php';
$session = (new scpp\compiler\Module_Loader($argv[4] ?? null))->load($argv[2], $argv[3]);
(new scpp\compiler\Project_Compiler())->run_loaded($session, $argv[1] . '/build/clang/native-layout/facts.json',
    $argv[3], true, []);
$bundle = scpp\compiler\Cpp_Bundle::read($argv[3]);
$artifacts = [];
foreach ($session->modules as $name => $module) $artifacts[$name] = $module->artifacts;
echo json_encode(['artifacts' => $artifacts, 'link_inputs' => $bundle['link_inputs'] ?? []], JSON_THROW_ON_ERROR);
'''


def compare(exe, app, bundle, runtime=None):
    native = [exe, 'inventory', app, '--link-inputs', '--output-directory', bundle]
    php = ['php', '-r', ORACLE, ROOT, app, bundle]
    if runtime:
        native += ['--runtime', runtime]
        php.append(runtime)
    result, expected = [json.loads(subprocess.check_output(cmd, timeout=30)) for cmd in (native, php)]
    assert list(result['artifacts'].items()) == list(expected['artifacts'].items()), (result, expected)
    assert result['link_inputs'] == expected['link_inputs'], (result, expected)
    return result


def verify(exe, root):
    def write(relative, document):
        path = root/relative
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(json.dumps(document))
        return path

    bundle = root/'bundle'
    runtime = write('sdk/module.json', {'name': 'runtime', 'artifacts': None})
    app = write('app/module.json', {'name': 'app', 'artifacts': ['unused.o']})
    (app.parent/'main.phs').write_text('return 0;')
    assert compare(exe, app, bundle, runtime)['link_inputs'] == []
    common = str(root/'shared absolute.a')
    left = {'name': 'left', 'artifacts': ['z.a', common, 'z.a'],
            'dependencies': {'base': {'path': '../base/module.json'}}}
    right = {'name': 'right', 'artifacts': [common, './r.o', 'r.o'],
             'dependencies': {'base': {'path': '../base'}}}
    write('base/module.json', {'name': 'base', 'type': 'c++', 'artifacts': ['../base.a']})
    write('left/module.json', left)
    write('right/module.json', right)
    write('plugin/module.json', {'name': 'plugin', 'artifacts': ['plug "in".a']})
    deps = {'left': {'path': '../left'}, 'right': {'path': '../right'}}
    manifest = {'name': 'app', 'artifacts': ['unused.o'], 'dependencies': deps,
                'modules': {'plugin': '../plugin'}, 'defaults': ['plugin']}
    write('app/module.json', manifest)
    result = compare(exe, app, bundle, runtime)
    prefix = str(root)
    assert result['link_inputs'] == [prefix + '/plugin/plug "in".a', common,
        prefix + '/right/./r.o', prefix + '/right/r.o', prefix + '/left/z.a', prefix + '/base/../base.a']
    assert result['artifacts']['left'] == [prefix + '/left/z.a', common, prefix + '/left/z.a']
    manifest['dependencies'] = dict(reversed(deps.items()))
    write('app/module.json', manifest)
    assert compare(exe, app, bundle, runtime)['link_inputs'] != result['link_inputs']
    write('app/module.json', {'dependencies': {'runtime': {'modules': ['json', 'filesystem']}}})
    selected = compare(exe, app, bundle)['link_inputs']
    assert any(path.endswith('/libscpp_json.a') for path in selected)
    assert any(path.endswith('/libscpp_filesystem.a') for path in selected)


if __name__ == '__main__':
    with tempfile.TemporaryDirectory() as tmp:
        verify(Path(sys.argv[1]).resolve(strict=True), Path(tmp))
