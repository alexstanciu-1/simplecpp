"""Exercise the generated filesystem module from application selection through native execution."""
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile

root = Path(__file__).resolve().parents[2]
php, cxx, linker, filesystem_archive, runtime_archive = sys.argv[1:6]
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

    published = publish()
    assert relocated in published
    lock_header = relocated.removesuffix('.hpp') + '/lock_try.hpp'
    assert lock_header in published and (runtime/'public'/lock_header).is_file()
    header = runtime / 'public' / relocated
    bridge = runtime / 'private/filesystem_provider.hpp'
    registry = compiler / 'types/registry.php'
    assert 'scpp::result_string get(const scpp::string&)' in header.read_text()
    assert relocated in bridge.read_text()
    bindings = registry.read_text()
    for function in profile['filesystem']['functions']:
        operation_header = relocated.removesuffix('.hpp') + '/' + function + '.hpp'
        assert any(f'general:func:{function}' in line and operation_header in line for line in bindings.splitlines())
        assert (runtime / 'public' / operation_header).is_file()

    profile['filesystem'] = {'functions': ['fs_is_dir']}
    assert 'scpp/php/file_get_contents.hpp' not in publish()
    assert not (runtime / 'public/scpp/php/file_get_contents.hpp').exists()
    assert 'file_get_contents' not in registry.read_text()
    assert 'fs_lock_try' not in registry.read_text() and not (runtime/'public'/lock_header).exists()
    assert ' scan(' not in header.read_text()
    # Compiling the full implementation against the reduced headers took the isolated
    # publication test over three seconds. The default checks its generated inventory.
    if '--compile-reduced' in sys.argv[6:]:
        run(cxx, '-std=c++23', '-stdlib=libc++', '-fsyntax-only', '-I', runtime / 'private',
            '-I', runtime / 'public', root / 'runtime/src/filesystem/filesystem.cpp')
    del profile['filesystem']
    assert relocated not in publish()
    assert not header.exists() and not bridge.exists()
    bindings = registry.read_text()
    assert not any(f'func:{name}' in bindings for name in metadata['filesystem']['functions'])


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
$enabled = '{"general_api":true,"dependencies":{"runtime":{"modules":["filesystem"]}}}';
file_put_contents($manifest, $enabled);
$project->run();
$before = file_get_contents("$out/manifest.json");

if (!is_file("$app/main.cpp")) {
    file_put_contents($manifest, '{"dependencies":{"runtime":{"modules":["filesystem"]}}}');
    try {
        $project->run();
        throw new RuntimeException('Hidden general function remained visible');
    } catch (diagnostic|diagnostic_group $error) {
        verify(str_contains($error->getMessage(), 'fs_get'), $error->getMessage());
    }
    verify(file_get_contents("$out/manifest.json") === $before, 'Visibility failure changed the manifest');
    file_put_contents($manifest, $enabled);
    $project->run();
}
file_put_contents($manifest, '{"general_api":true}');
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
    if '--publication' in sys.argv[6:]:
        check_publication(work)
        sys.exit(0)
    contents = bytes(range(256)) * 40
    (work / 'bytes').write_bytes(contents)
    (work / 'empty').touch()
    blocked = work / 'blocked'
    blocked.mkdir()
    (blocked / 'member').touch()

    for flavor, declaration, variable, missing_call in [
        ('cpp', '{type} {name} = {value};', '', 'fs_get'),
        ('php', '${name} {type} = {value};', '$', 'read_file'),
        ('js', 'let {name}: {type} = {value};', '', 'fs_get'),
    ]:
        app = work / flavor
        app.mkdir()
        def local(name, type_name, value):
            return declaration.format(name=name, type=type_name, value=value)

        statements = [
            local(name, type_name, value)
            for name, type_name, value in [
                ('read', 'result<string>', 'fs_get("bytes")'),
                ('bytes', 'string', variable + 'read'),
                ('empty', 'result<string>', 'fs_get("empty")'),
                ('missing', 'result<string>', f'{missing_call}("missing")'),
                ('invalid', 'result<string>', 'fs_get("bytes\\0ignored")'),
            ]
        ]
        binding = {'cpp': 'Exception failure', 'php': 'Exception $failure', 'js': 'failure: Exception'}[flavor]
        factory = 'Exception.make' if flavor == 'js' else 'Exception::make'
        stage = local('written', 'result<int>', f'fs_put("stage", {variable}bytes)')
        confirmed = local('confirmed', 'int', variable + 'written')
        before = local('before', 'string', 'fs_get("published")')
        cleanup = local('cleaned', 'bool', 'false')
        caught = local('caught', 'bool', 'false')
        failed = local('failed', 'result<int>', 'fs_put("missing-dir/file", "bytes")')
        invalid = local('badPath', 'result<int>', 'fs_put("published\\0ignored", "different")')
        empty_write = local('emptyWrite', 'int', 'fs_put("empty", "")')
        source = ('use function fs_get as read_file;' if flavor == 'php' else '') + '\n'.join(statements)
        source += (
            f'\nif (!isset({variable}empty) || isset({variable}missing) || isset({variable}invalid)) {{return 3;}}'
            f'''
            {stage}
            {confirmed}
            {failed}
            {invalid}
            {empty_write}
            if ({variable}confirmed !== {len(contents)} || isset({variable}failed) ||
                isset({variable}badPath) || {variable}emptyWrite !== 0) {{return 4;}}
            {local('route', 'int', '0')}
            switch ({variable}failed) {{
                case 0: return 7;
                default: {variable}route = 1;
                case -1: {variable}route += 2; break;
            }}
            if ({variable}route !== 3) {{return 8;}}
            if (match ({variable}missing) {{ "" => 1, default => 2 }} !== 2) {{return 9;}}
            {cleanup}
            {caught}
            try {{
                try {{
                    if (!fs_rename("stage", "blocked")) {{
                        throw {factory}("failed promotion");
                    }}
                }} finally {{
                    {variable}cleaned = fs_remove("stage");
                }}
            }} catch ({binding}) {{
                {variable}caught = true;
            }}
            {before}
            if (!{variable}caught || !{variable}cleaned || fs_remove("stage") ||
                {variable}before !== "last good") {{return 5;}}
            {variable}written = fs_put("stage", {variable}bytes);
            if (!isset({variable}written) || !fs_rename("stage", "published")) {{return 6;}}
            echo {variable}bytes; return 0;
            '''
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
        assert any('#include <scpp/filesystem/fs_get.hpp>' in unit.read_text() for unit in units)
        binary = app / 'program'
        run(cxx, '-std=c++23', '-stdlib=libc++', '-O1', f'--ld-path={linker}',
            '-I', include, *units, filesystem_archive, runtime_archive, '-o', binary)
        published = work / 'published'
        published.write_bytes(b'last good')
        assert run(binary, cwd=work) == contents
        assert published.read_bytes() == contents
        assert not (work / 'stage').exists()
        assert (blocked / 'member').is_file()

    # Compile with the public header first and inspect the structured error payload directly.
    native = work / 'error.cpp'
    native.write_text('''
#include "scpp/filesystem.hpp"
int main(int argc, char** argv) {
    if (argc == 2) {
        const auto failed_write = scpp::fs::put(scpp::string{argv[1]}, scpp::string{"short buffered write"});
        return failed_write.has_error() && !failed_write.has_value() ? 0 : 1;
    }
    const auto failure = scpp::fs::get(scpp::string{"missing"});
    return failure.has_error() && !failure.has_value() && !failure.error().get_message().empty() ? 0 : 1;
}
''')
    binary = work / 'native-error'
    run(cxx, '-std=c++23', '-stdlib=libc++', f'--ld-path={linker}',
        '-I', include, native, filesystem_archive, runtime_archive, '-o', binary)
    run(binary, cwd=work)
    if Path('/dev/full').is_char_device():
        run(binary, '/dev/full', cwd=work)
