"""Production constant initializer accessors and traversal across PHP and native routes."""
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

declarations = APP / '03_parse/structures/structures_declarations.php'
constant = declaration(declarations, 'abstract class constant_declaration_node')
def method(text, name):
    start = text.index(name)
    opening = text.index('{', start)
    depth, end = 1, opening + 1
    while depth:
        depth += (text[end] == '{') - (text[end] == '}')
        end += 1
    return text[start:end]
field = next(line.strip() for line in constant.splitlines() if 'private ?expression_node $initializer_value' in line)
source = r"""<?php
namespace scpp\compiler;
class ast_node {}
class expression_node extends ast_node { public int $value = 0; }
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
abstract class constant_declaration_node extends ast_node {
""" + field + '\n'
for name in ['public function require_initializer', 'public function set_initializer', 'public function children', 'public function maintain']:
    source += method(constant, name) + '\n'
source += '}\nfinal class namespace_constant_node extends constant_declaration_node {}\n'
source += declaration(APP / '03_parse/structures/iterators.php', 'final class constant_children_iterator')
source += r"""
class Failure_Source { public static function expression(): expression_node { throw new \LogicException('parse failed'); } }
$constant = new namespace_constant_node();
$original = $constant;
try { $constant->require_initializer(); echo 'bad'; }
catch (\LogicException $error) { echo 'read'; }
try { $constant->children()->current(); echo ':bad'; }
catch (\LogicException $error) { echo ':child'; }
$worker = new node_maintenance_worker_i();
try { $constant->maintain($worker); echo ':bad'; }
catch (\LogicException $error) { echo ':maintain'; }
$first = new expression_node();
$first->value = 7;
$constant->set_initializer($first);
echo ':', $constant->require_initializer() === $first ? 'same' : 'bad';
$constant->maintain($worker);
echo ':', $worker->edges;
$children = $constant->children();
echo ':', $children->current() === $first ? 'child' : 'bad';
echo ':', $children->current() === null ? 'end' : 'bad';
try { $constant->set_initializer(Failure_Source::expression()); echo ':bad'; }
catch (\LogicException $error) { echo ':failed'; }
echo ':', $constant->require_initializer() === $first ? 'kept' : 'bad';
$second = new expression_node(); $second->value = 9;
$constant->set_initializer($second);
echo ':', $constant->require_initializer() === $second ? 'replaced' : 'bad';
echo ':', $constant === $original ? 'identity' : 'bad';
echo ':', $first->value;
"""
(out / 'source/main.php').write_text(source)
expected = b'read:child:maintain:same:1:child:end:failed:kept:replaced:identity:7'
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
