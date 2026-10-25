"""Portable cursor widening, sparse keys, aliased state and retained source across routes."""
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
namespace scpp\compiler;
class Cursor_Node { public int $value = 0; }
class Cursor_Child extends Cursor_Node {}
final class Cursor_Field {
 public function __construct(private Storage_Cursor $cursor /** Storage_Cursor<Cursor_Node> */) {}
 public function read_next(): ?Cursor_Node {
  $cursor /** Storage_Cursor<Cursor_Node> */ = $this->cursor;
  if (!$cursor->valid()) { return null; }
  $node = $cursor->current();
  $cursor->next();
  return $node;
 }
}
final class Cursor_Proof {
 public static function retained(): Storage_Cursor /** Storage_Cursor<Cursor_Node> */ {
  $rows = new Storage /** Storage<Cursor_Child> */();
  $first = new Cursor_Child(); $first->value = 3;
  $hole = new Cursor_Child();
  $last = new Cursor_Child(); $last->value = 7;
  $rows->append($first); $rows->append($hole); $rows->append($last);
  $rows->remove(1);
  $cursor = new Storage_Cursor /** Storage_Cursor<Cursor_Node> */($rows);
  $current /** Cursor_Node */ = $cursor->current();
  $current->value = 5;
  echo $first->value, ':';
  return $cursor;
 }
}
$cursor = Cursor_Proof::retained();
$alias = $cursor;
$current /** Cursor_Node */ = $cursor->current();
echo $cursor->key(), ':', $current->value, ':';
$alias->next();
$current = $cursor->current();
echo $cursor->key(), ':', $current->value, ':';
$cursor->next();
echo $alias->valid() ? 'live' : 'end';
echo ':';
$field = new Cursor_Field(Cursor_Proof::retained());
$first /** Cursor_Node */ = $field->read_next();
$last /** Cursor_Node */ = $field->read_next();
echo $first->value, ':', $last->value, ':';
echo $field->read_next() === null ? 'end' : 'live';
'''
(out / 'source/main.php').write_text(source)
expected = b'5:0:5:2:7:end:5:5:7:end'
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
