"""Typed production foreach lists preserve values and iteration order across routes."""
import argparse
import json
import shutil
import sys
from pathlib import Path
ROOT = Path(__file__).resolve().parents[2]
APP = ROOT / 'compiler/my-try'
TOOLS = ROOT / 'tools/php_portability'
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
out = p.parse_args().results.resolve()
(out / 'source').mkdir(parents=True, exist_ok=False)
lists = {
 '05_backend/cpp/opaque_representation.php': ['special_operations'],
 '06_native/cpp_build.php': ['search_arguments', 'token_scan_arguments'],
 '06_native/ninja_build.php': ['dependency_arguments'],
 '05_backend/cpp/concrete_iteration.php': ['layout_roles', 'range_methods', 'entry_methods'],
 '06_native/cpp_publication.php': ['runtime_fields'],
 'compiler/initialization/catalog.php': ['boxed_names'],
 'compiler/indexing/contracts.php': ['boxed_names'],
 'compiler/iteration/contracts.php': ['boxed_names'],
 '05_backend/cpp/data_construction.php': ['boxed_names'],
}
source = "<?php\nnamespace scpp\\compiler;\nclass Proof {\n"
calls = ''
index = 0
for path, names in lists.items():
    text = (APP / path).read_text()
    for name in names:
        start = text.index('$' + name + ' /** vector<string> */')
        declaration = text[start:text.index(';', start) + 1]
        source += 'public static function list_' + str(index) + '(string $output): void {\n' + declaration + '\n'
        source += 'foreach ($' + name + ' as $item) { echo $item . "|"; } echo "\\n";\n}\n'
        calls += 'Proof::list_' + str(index) + '("target");\n'
        index += 1
source += '}\n' + calls
(out / 'source/main.php').write_text(source)
expected = ("default|copy|move|copy_assign|move_assign|\n-E|-v|-x|c++|/dev/null|\n-fsyntax-only|-Xclang|-dump-tokens|\n"
            "-MD|-MF|target.d|-MQ|target|-o|target|\nentry|sentinel|iterator|range|\n"
            "begin|end|\nkey|value_copy|\nsources|libraries|definitions|compile_options|link_options|diagnostics|\n"
            + "mixed|dynamic|\n" * 4).encode()
with CommandRunner(out / 'logs', ROOT) as runner:
    publisher = out / 'publish.php'
    publisher.write_text((APP / 'tests/async_tasks_publish.php').read_text()
                         .replace("dirname(__DIR__) . '/boot.php'", repr(str(APP / 'boot.php')))
                         .replace('[$argv[3]]', "[$argv[3], 'strings']"))
    assert runner.run('host', ['php8.5', '-r', 'foreach(array_slice($argv,1) as $path){require $path;}',
                             TOOLS / 'runtime/bootstrap.php', APP / 'helpers/storage_abstract.php',
                             APP / 'helpers/storage.php', APP / 'helpers/keyed_storage.php', out / 'source/main.php']) == expected
    for route in ['v0.1', 'v0.2']:
        runner.run(route + '-convert', ['php8.5', TOOLS / 'convert.php', out / 'source', out / route, '--target=' + route])
    for route in ['v0.1', 'v0.2']:
        runner.run(route + '-framework', ['php8.5', TOOLS / 'install_native_runtime.php', out / route])
    def check(mode):
        if mode == 'legacy':
            project = out / 'v0.1'
            runner.run('legacy-init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], project, cli_reason='CLI fixture setup')
            config = json.loads((project / 'prism.json').read_text())
            config['runtime']['modules'] = ['compiler']
            config['build']['cxx'] = 'clang++-18'
            (project / 'prism.json').write_text(json.dumps(config))
            runner.run('legacy-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'], project, timeout=300, cli_reason='CLI fixture build')
            executable = project / '.prism/build/main'
        else:
            folder = out / mode
            (folder / 'source').mkdir(parents=True)
            shutil.copyfile(out / 'v0.2/main.phs', folder / 'source/main.phs')
            # Include the full exception framework, including its identity helper.
            shutil.copyfile(out / 'v0.2/scpp_framework/exceptions.phs', folder / 'source/exceptions.phs')
            runner.run(mode + '-publish', ['php8.5', publisher, folder,
                                           ROOT / 'runtime/metadata/modules', 'compiler', mode])
            manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
            build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
            executable = build['executable']
        assert runner.run(mode + '-run', [executable]) == expected
    results = run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['legacy', 'ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps([{'name':r.name, 'seconds':r.seconds, 'passed':r.error is None} for r in results], indent=2) + '\n')
