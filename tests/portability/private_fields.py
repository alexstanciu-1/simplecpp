"""Independent private base/child fields and explicit portable base views."""
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
source = '''<?php
class Base_Record {
 private int $position = 0;
 public function __construct(int $value) { $this->position = $value; }
 public function base_position(): int { return $this->position; }
 public function set_base(int $value): void { $this->position = $value; }
 public function through_base(Child_Record $child): int {
  $base /** Base_Record */ = $child;
  return $base->position;
 }
}
class Middle_Record extends Base_Record {}
class Child_Record extends Middle_Record {
 private int $position = 0;
 public function __construct(int $base_value, int $child_value) {
  parent::__construct($base_value);
  $this->position = $child_value;
 }
 public function child_position(): int { return $this->position; }
 public function set_child(int $value): void { $this->position = $value; }
}
$child = new Child_Record(11, 22);
echo $child->base_position(), ':', $child->child_position(), ':';
$child->set_base(33);
$child->set_child(44);
echo $child->base_position(), ':', $child->child_position(), ':', $child->through_base($child);
'''
(out / 'source/main.php').write_text(source)
expected = b'11:22:33:44:33'
with CommandRunner(out / 'logs', ROOT) as runner:
    assert runner.run('host', ['php8.5', out / 'source/main.php']) == expected
    for route in ['v0.1', 'v0.2']:
        runner.run(route + '-convert', ['php8.5', TOOLS / 'convert.php', out / 'source', out / route, '--target=' + route])
    def check(mode):
        if mode == 'legacy':
            project = out / 'v0.1'
            runner.run('legacy-init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], project, cli_reason='CLI fixture setup')
            config = json.loads((project / 'prism.json').read_text())
            config['build']['cxx'] = 'clang++-18'
            (project / 'prism.json').write_text(json.dumps(config))
            runner.run('legacy-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'], project, timeout=300, cli_reason='CLI fixture build')
            executable = project / '.prism/build/main'
        else:
            folder = out / mode
            (folder / 'source').mkdir(parents=True)
            shutil.copyfile(out / 'v0.2/main.phs', folder / 'source/main.phs')
            runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                           ROOT / 'runtime/metadata/modules', 'core', mode])
            manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
            build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
            executable = build['executable']
        assert runner.run(mode + '-run', [executable]) == expected
    results = run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['legacy', 'ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps([{'name':r.name, 'seconds':r.seconds, 'passed':r.error is None} for r in results], indent=2) + '\n')
