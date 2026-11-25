"""Production type-declaration child traversal across portable compiler routes."""
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
production = (APP / '03_parse/structures/iterators.php').read_text()
def extract(label):
    start = production.index(label)
    opening = production.index('{', start)
    end, depth = opening + 1, 1
    while depth:
        depth += (production[end] == '{') - (production[end] == '}')
        end += 1
    return production[start:end]
source = '<?php\nnamespace scpp\\compiler;\nclass ast_node { public int $value = 0; }\n'
fields = ['extended_interfaces', 'implemented_interfaces', 'properties', 'methods', 'constants', 'use_traits']
source += 'class type_declaration_node { public ?ast_node $base_class = null;\n'
for field in fields:
    source += 'public Storage $' + field + ' /** Storage<ast_node> */;\n'
source += 'public function __construct() {\n'
for field in fields:
    source += '$this->' + field + ' = new Storage /** Storage<ast_node> */();\n'
source += '} }\n'
for label in ['interface child_iterator_i', 'abstract class children_iterator', 'final class type_declaration_children_iterator', 'final class storage_children_iterator']:
    source += extract(label) + '\n'
source += '$owner = new type_declaration_node(); $base = new ast_node(); $base->value = 1; $owner->base_class = $base;\n'
for index, field in enumerate(fields, 2):
    source += '$rows_' + field + ' /** Storage<ast_node> */ = $owner->' + field + ';\n'
    source += '$item_' + field + ' = new ast_node(); $item_' + field + '->value = ' + str(index) + ';\n'
    source += '$rows_' + field + '->append($item_' + field + ');\n'
source += '$cursor = new type_declaration_children_iterator($owner); while ($cursor->valid()) { $node = $cursor->current(); echo $node->value, ":"; $cursor->next(); } echo "end";\n'
source += '$empty = new type_declaration_children_iterator(new type_declaration_node()); if ($empty->valid()) { echo "unexpected"; }\n'
(out / 'source/main.php').write_text(source)
expected = b'1:2:3:4:5:6:7:end'
with CommandRunner(out / 'logs', ROOT) as runner:
    assert runner.run('host', ['php8.5', '-r', 'foreach(array_slice($argv,1) as $path){require $path;}',
                             TOOLS / 'runtime/bootstrap.php', APP / 'helpers/storage_abstract.php', APP / 'helpers/storage.php', APP / 'helpers/storage_cursor.php', out / 'source/main.php']) == expected
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
            runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                           ROOT / 'runtime/metadata/modules', 'compiler', mode])
            manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
            build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
            executable = build['executable']
        assert runner.run(mode + '-run', [executable]) == expected
    results = run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['legacy', 'ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps([{'name':r.name, 'seconds':r.seconds, 'passed':r.error is None} for r in results], indent=2) + '\n')
