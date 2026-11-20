"""Exercise the generated filesystem module from application selection through native execution."""
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile

root = Path(__file__).resolve().parents[2]
php, cxx, linker, filesystem_archive, runtime_archive = sys.argv[1:]
include = root / 'runtime/generated/public'


def run(*args, cwd=None):
    result = subprocess.run(list(map(str, args)), cwd=cwd, capture_output=True,
                            env={**os.environ, 'XDEBUG_MODE': 'off'})
    assert result.returncode == 0, (str(args[0]), result.stdout, result.stderr)
    return result.stdout


def check_publication(work):
    generator = root / 'tools/api_generator/generate.php'
    build = Path(runtime_archive).parent
    facts = build / 'native-layout/facts.json'
    context = facts.with_name('context.json')
    metadata = json.loads((root / 'runtime/metadata/runtime.json').read_text())
    profile = json.loads((build / 'runtime-publication.json').read_text())
    catalog, request = work / 'metadata.json', work / 'publication.json'
    runtime, compiler = work / 'published-runtime', work / 'published-compiler'

    provider = metadata['filesystem']['provider']
    relocated = 'scpp/proof/relocated_filesystem.hpp'
    metadata['submodules'][provider]['header'] = relocated
    catalog.write_text(json.dumps(metadata))

    def publish():
        request.write_text(json.dumps(profile))
        run(php, generator, 'emit-runtime', catalog, request, facts, context, runtime)
        run(php, generator, 'emit-compiler', catalog, request, facts, context, compiler)
        return json.loads(run(php, generator, 'provider-headers', catalog, request))

    assert relocated in publish()
    header = runtime / 'public' / relocated
    bridge = runtime / 'private/filesystem_provider.hpp'
    registry = compiler / 'types/registry.php'
    assert 'scpp::result_string get(const scpp::string&)' in header.read_text()
    assert relocated in bridge.read_text()
    assert any('func:fs_get' in line and relocated in line for line in registry.read_text().splitlines())

    del profile['filesystem']
    assert relocated not in publish()
    assert not header.exists() and not bridge.exists()
    assert 'func:fs_get' not in registry.read_text()


# Toggle only the manifest in one compiler instance, and compare recovered output with a fresh build.
incremental = r'''
require $argv[1];

use scpp\compiler\{Incremental_Project, entry_target, diagnostic, diagnostic_group};

function verify(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

[$app, $runtime, $out, $fresh] = array_slice($argv, 2);
$manifest = "$app/module.json";
$target = new entry_target(31);
$project = new Incremental_Project($app, $out, $target, $runtime);
$enabled = '{"dependencies":{"runtime":{"modules":["filesystem"]}}}';
file_put_contents($manifest, $enabled);
$project->run();
$before = file_get_contents("$out/manifest.json");

file_put_contents($manifest, '{}');
for ($attempt = 0; $attempt < 2; $attempt++) {
    try {
        $project->run();
        throw new RuntimeException('Filesystem call admitted without its module');
    }
    catch (diagnostic|diagnostic_group $error) {
        verify(str_contains($error->getMessage(), 'Function fs_get requires module runtime-filesystem'),
            $error->getMessage());
    }
    verify(file_get_contents("$out/manifest.json") === $before, 'Failed preparation changed the manifest');
}

file_put_contents($manifest, $enabled);
$project->run();
(new Incremental_Project($app, $fresh, $target, $runtime))->run();
verify(file_get_contents("$out/manifest.json") === $before, 'Recovered manifest changed');
verify(file_get_contents("$fresh/manifest.json") === $before, 'Fresh manifest differs');
$bundle = json_decode($before, true, flags: JSON_THROW_ON_ERROR);
foreach (array_merge($bundle['translation_units'], $bundle['headers']) as $path) {
    verify(file_get_contents("$out/$path") === file_get_contents("$fresh/$path"),
        "Recovered artifact differs: $path");
}
verify($project->run()['preparation']['prepared'] === 0, 'Unchanged input lost preparation reuse');
'''

with tempfile.TemporaryDirectory(prefix='scpp-filesystem-native-') as directory:
    work = Path(directory)
    check_publication(work)
    contents = bytes(range(256)) * 40
    (work / 'bytes').write_bytes(contents)
    (work / 'empty').touch()

    for flavor, declaration, variable, missing_call in [
        ('cpp', '{type} {name} = {value};', '', 'fs_get'),
        ('php', '${name} {type} = {value};', '$', 'read_file'),
        ('js', 'let {name}: {type} = {value};', '', 'fs_get'),
    ]:
        app = work / flavor
        app.mkdir()
        statements = [
            declaration.format(type=type_name, name=name, value=value)
            for type_name, name, value in [
                ('result<string>', 'read', 'fs_get("bytes")'),
                ('string', 'bytes', variable + 'read'),
                ('result<string>', 'empty', 'fs_get("empty")'),
                ('result<string>', 'missing', f'{missing_call}("missing")'),
                ('result<string>', 'invalid', 'fs_get("bytes\\0ignored")'),
            ]
        ]
        source = ('use function fs_get as read_file;' if flavor == 'php' else '') + '\n'.join(statements)
        source += (
            f'\nif (!isset({variable}empty) || isset({variable}missing) || isset({variable}invalid)) {{return 3;}}'
            f'\necho {variable}bytes; return 0;'
        )
        (app / f'main.{flavor}').write_text(source)
        output = work / f'{flavor}-out'
        run(php, '-r', incremental, root / 'compiler/boot.php', app, root / 'runtime',
            output, work / f'{flavor}-fresh')
        bundle = json.loads((output / 'manifest.json').read_text())
        assert [Path(path).name for path in bundle['link_inputs']] == [
            'libscpp_filesystem.a', 'libscpp_runtime.a'
        ]
        units = [output / path for path in bundle['translation_units']]
        assert any('#include <scpp/filesystem.hpp>' in unit.read_text() for unit in units)
        binary = app / 'program'
        run(cxx, '-std=c++23', '-stdlib=libc++', '-O1', f'--ld-path={linker}',
            '-I', include, *units, filesystem_archive, runtime_archive, '-o', binary)
        assert run(binary, cwd=work) == contents

    # Compile with the public header first and inspect the structured error payload directly.
    native = work / 'error.cpp'
    native.write_text('''
#include "scpp/filesystem.hpp"
int main() {
    const auto failure = scpp::fs::get(scpp::string{"missing"});
    return failure.has_error() && !failure.has_value() && !failure.error().get_message().empty() ? 0 : 1;
}
''')
    binary = work / 'native-error'
    run(cxx, '-std=c++23', '-stdlib=libc++', f'--ld-path={linker}',
        '-I', include, native, filesystem_archive, runtime_archive, '-o', binary)
    run(binary, cwd=work)
