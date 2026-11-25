"""Shared-object identity uses existing ownership and ancestry contracts."""
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
out = p.parse_args().results.resolve()
out.mkdir(parents=True, exist_ok=False)
cases = {
 'identity': ('''interface I { function number(): int; }
class Base { public int $value = 7; }
class Derived extends Base implements I { public function number(): int { return $this->value; } }
class Other extends Base {}
function clear(?Base &$value): ?Base { $value = null; return null; }
function once(Derived $value, int &$calls): ?Derived { $calls++; return $value; }
$d = new Derived(); $base Base = $d; $view I = $d;
$other = new Derived();
if (!($d === $base)) { return 1; }
if (!($view === $d)) { return 2; }
if ($d === $other) { return 3; }
$optional ?Derived = $d; $wide ?Base = $base;
if (!($optional === $wide)) { return 4; }
$none ?Derived = null; $empty ?Base = null;
if (!($none === $empty)) { return 5; }
if ($none === $base) { return 6; }
if (!($optional !== $none)) { return 7; }
if ($wide === clear($wide)) { return 8; }
$calls = 0;
if (!(once($d, $calls) === $base)) { return 9; }
if ($calls !== 1) { return 10; }
struct Record { public int $value = 3; }
$r shared<Record> = new Record(); $alias = $r; $absent shared<Record> = null;
if (!($r === $alias)) { return 11; }
if ($r === $absent) { return 12; }
echo "identity:ok";
''', b'identity:ok'),
}
negative = {
 'unrelated': ('class A {} class B {} $a = new A(); $b = new B(); echo $a === $b;', 'compatible base/derived'),
 'weak': ('class A {} $a = new A(); $w = weak($a); echo $a === $w;', 'two shared object handles'),
 'scalar': ('class A {} $a = new A(); echo $a === 7;', 'two shared object handles'),
}
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(name, mode, data):
        source, expected = data
        label = name + '-' + mode
        folder = out / label
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        runner.run(label + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                        ROOT / 'runtime/metadata/modules', 'core', mode])
        build = json.loads(runner.run(label + '-build', ['php8.5', APP / 'tools/build_cpp.php',
                                                        folder / 'published', folder / 'cache'], timeout=300))
        actual = runner.run(label + '-execute', [build['executable']])
        assert actual == expected, (actual, expected)
    tasks = [Task(name + '-' + mode, lambda name=name, mode=mode, data=data: check(name, mode, data))
             for name, data in cases.items() for mode in ['ordinary', 'concrete']]
    def reject(name, program, diagnostic, mode):
        label = name + '-' + mode
        folder = out / label
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(program)
        runner.run(label, ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                          ROOT / 'runtime/metadata/modules', 'core', mode], expected=255)
        error = (out / 'logs' / (label + '.stderr')).read_text()
        assert diagnostic.lower() in error.lower(), error
    tasks += [Task(name + '-' + mode, lambda name=name, pair=pair, mode=mode: reject(name, *pair, mode))
              for name, pair in negative.items() for mode in ['ordinary', 'concrete']]
    tasks += [Task('retained', lambda: runner.run('retained', ['php8.5', APP / 'tests/shared_identity.php', out / 'retained']))]
    results = run_tasks(tasks)
(out / 'summary.json').write_text(json.dumps([{'name': r.name, 'passed': r.error is None, 'seconds': r.seconds}
                                             for r in results], indent=2) + '\n')
