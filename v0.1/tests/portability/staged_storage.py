"""INIT-02 backing/accessor layout proof; no automatic marker rewriting yet."""
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
source = r'''<?php
class Node { public int $value = 7; }
/** @scpp-struct */
final class Point { public int $value /** uint32 */ = 0; }
class Copies {
 public static function point(Point $source): Point {
  $copy = new Point(); $copy->value = $source->value; return $copy;
 }
}
class Staged {
 private ?Node $node_storage = null;
 private ?Point $point_storage = null;
 public ?Node $nullable_control = null;
 public function node(): Node {
  if ($this->node_storage === null) { throw new \RuntimeException('uninitialized node'); }
  return $this->node_storage;
 }
 public function set_node(Node $value): void {
  if ($value === null) { throw new \RuntimeException('required node'); }
  $this->node_storage = $value;
 }
 public function point(): Point {
  if ($this->point_storage === null) { throw new \RuntimeException('uninitialized point'); }
  $value /** Point */ = $this->point_storage;
  return Copies::point($value);
 }
 public function set_point(Point $value): void {
  $this->point_storage = Copies::point($value);
 }
}
class Inputs {
 public static function fail_node(): Node { throw new \RuntimeException('input failed'); }
 public static function read(Node $value): int { return $value->value; }
}
$owner = new Staged();
try { $early = $owner->node(); echo 'bad'; }
catch (\RuntimeException $error) { echo 'node-empty:'; }
try { $early_point = $owner->point(); echo 'bad'; }
catch (\RuntimeException $error) { echo 'point-empty:'; }
try { echo Inputs::read($owner->node()); }
catch (\RuntimeException $error) { echo 'argument-empty:'; }
echo $owner->nullable_control === null ? 'nullable:' : 'bad:';
$first = new Node();
$owner->set_node($first);
echo $owner->node() === $first ? 'same:' : 'bad:';
$first->value = 9;
echo $owner->node()->value, ':';
$second = new Node(); $second->value = 12;
$owner->set_node($second);
echo $owner->node() === $second ? 'replaced:' : 'bad:';
try { $owner->set_node(Inputs::fail_node()); }
catch (\RuntimeException $error) { echo 'write-failed:'; }
echo $owner->node() === $second ? 'retained:' : 'bad:';
$point = new Point(); $point->value = 3;
$owner->set_point($point);
$point->value = 8;
$read = $owner->point();
echo $read->value, ':';
$read->value = 11;
$unchanged = $owner->point();
echo $unchanged->value, ':';
$owner->set_point($read);
$replaced = $owner->point();
echo $replaced->value;
'''
parser_source = (APP / '03_parse/parser.php').read_text()
def parser_method(name):
    start = parser_source.index('private function ' + name + '(')
    opening = parser_source.index('{', start)
    end, depth = opening + 1, 1
    while depth:
        depth += (parser_source[end] == '{') - (parser_source[end] == '}')
        end += 1
    return parser_source[start:end]

backing = 'private ?scope $current_scope_value = null;'
assert backing in parser_source
source += '\nclass scope {}\nclass Parser_Scope_Proof {\n' + backing + '\n'
source += parser_method('current_scope') + '\n' + parser_method('select_scope')
source += r'''
 public function read(): scope { return $this->current_scope(); }
 public function select(scope $value): void { $this->select_scope($value); }
}
$parser_scope = new Parser_Scope_Proof();
try { $early_scope = $parser_scope->read(); echo ':bad'; }
catch (\LogicException $error) { echo ':scope-empty'; }
$outer = new scope(); $inner = new scope();
$parser_scope->select($outer);
$saved = $parser_scope->read();
$parser_scope->select($inner);
echo $parser_scope->read() === $inner ? ':inner' : ':bad';
$parser_scope->select($saved);
echo $parser_scope->read() === $outer ? ':restored' : ':bad';
'''
(out / 'source/main.php').write_text(source)
expected = b'node-empty:point-empty:argument-empty:nullable:same:9:replaced:write-failed:retained:3:3:11'
expected += b':scope-empty:inner:restored'
with CommandRunner(out / 'logs', ROOT) as runner:
    assert runner.run('host', ['php8.5', '-r', 'foreach(array_slice($argv,1) as $path){require $path;}',
                             TOOLS / 'runtime/bootstrap.php', out / 'source/main.php']) == expected
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
