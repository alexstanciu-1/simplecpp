"""Production access-base constructors and traversal across PHP and native routes."""
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
def declaration(path, label):
    text = path.read_text()
    start = text.index(label)
    opening = text.index('{', start)
    depth = 1
    end = opening + 1
    while depth:
        depth += (text[end] == '{') - (text[end] == '}')
        end += 1
    return text[start:end]

structures = APP / '03_parse/structures/structures_specialization.php'
def method(text, name):
    start = text.index(name)
    opening = text.index('{', start)
    depth, end = 1, opening + 1
    while depth:
        depth += (text[end] == '{') - (text[end] == '}')
        end += 1
    return text[start:end]
source = r"""<?php
namespace scpp\compiler;
class ast_node {}
class expression_node extends ast_node { public int $value = 7; }
class assignable_expression_node extends expression_node {}
class node_maintenance_worker_i {
 public int $edges = 0;
 public function enter(ast_node $node): void {}
 public function edge(ast_node $owner, ast_node $child): void { $this->edges++; }
}
interface child_iterator_i { public function current(): ?ast_node; }
abstract class children_iterator implements child_iterator_i {
 abstract protected function read_next(): ?ast_node;
 public function current(): ?ast_node { return $this->read_next(); }
}
"""
for cls in ['index_node', 'field_access_node']:
    original = declaration(structures, 'final class ' + cls)
    source += 'final class ' + cls + ' extends assignable_expression_node {\n'
    if cls == 'index_node':
        source += 'public ?expression_node $index = null;\n'
    for name in ['public function __construct', 'public function children', 'public function maintain']:
        source += method(original, name) + '\n'
    source += '}\n'
for cls in ['index_children_iterator', 'field_access_children_iterator']:
    source += declaration(APP / '03_parse/structures/iterators.php', 'final class ' + cls) + '\n'
source += r"""
$root = new expression_node();
$field = new field_access_node($root);
$index = new index_node($field);
$leaf = new field_access_node($index);
echo $field->base === $root ? 'root' : 'bad';
echo ':', $index->base === $field ? 'field' : 'bad';
echo ':', $leaf->base === $index ? 'index' : 'bad';
$root->value = 11;
echo ':', $field->base->value;
$key = new expression_node();
$index->index = $key;
$children = $index->children();
echo ':', $children->current() === $field ? 'base' : 'bad';
echo ':', $children->current() === $key ? 'key' : 'bad';
echo ':', $children->current() === null ? 'end' : 'bad';
$field_children = $field->children();
echo ':', $field_children->current() === $root ? 'root' : 'bad';
echo ':', $field_children->current() === null ? 'end' : 'bad';
$empty_index = new index_node($root);
$empty_children = $empty_index->children();
echo ':', $empty_children->current() === $root ? 'root' : 'bad';
echo ':', $empty_children->current() === null ? 'end' : 'bad';
$worker = new node_maintenance_worker_i();
$field->maintain($worker); $index->maintain($worker); $empty_index->maintain($worker);
echo ':', $worker->edges;
"""
(out / 'source/main.php').write_text(source)
expected = b'root:field:index:11:base:key:end:root:end:root:end:4'
with CommandRunner(out / 'logs', ROOT) as runner:
    publisher = out / 'publish.php'
    publisher.write_text((APP / 'tests/async_tasks_publish.php').read_text()
                         .replace("dirname(__DIR__) . '/boot.php'", repr(str(APP / 'boot.php')))
                         .replace('[$argv[3]]', "[$argv[3], 'strings']"))
    assert runner.run('host', ['php8.5', '-r', 'foreach(array_slice($argv,1) as $path){require $path;}',
                             TOOLS / 'runtime/bootstrap.php', APP / 'helpers/storage_abstract.php',
                             APP / 'helpers/storage.php', out / 'source/main.php']) == expected
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
