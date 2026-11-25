"""Closure lexical class context in ordinary and prelowered output."""
import argparse
import json
import sys
from pathlib import Path
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
a = p.parse_args()
out = a.results.resolve()
out.mkdir(parents=True, exist_ok=False)
source = """class Base {
 protected static function base(): int { return 3; }
}
class Owner extends Base {
 private int $n = 4;
 private static function secret(): int { return 7; }
 public static function run(): int {
  $outer = function(): int {
   $inner = function(): int { return self::secret() + parent::base(); };
   return $inner();
  };
  return $outer();
 }
 public function captured(): int {
  $callback = function() use ($this): int { return $this->n; };
  return $callback();
 }
 public function read(): int {
  $callback = function(): int { return Owner::secret(); };
  return $callback();
 }
}
echo Owner::run(), ":";
$o = new Owner(); echo $o->read(), ":", $o->captured();
"""
expected = '10:7:4'
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(mode):
        folder = out / mode
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder, ROOT / 'runtime/metadata/modules', 'compiler', mode])
        manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
        assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
        build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
        actual = runner.run(mode + '-execute', [build['executable']]).decode()
        assert actual == expected, (actual, expected)
    run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['ordinary', 'concrete']] +
              [Task('contracts', lambda: runner.run('contracts',
                  ['php8.5', APP / 'tests/closure_class_context.php', out / 'contracts']))])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'expected': expected}, indent=2) + '\n')
