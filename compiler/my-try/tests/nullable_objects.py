"""Distinct object nullability and composed checked ancestry in both output modes."""
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
source = '''class Base { public int $value = 7; public function get(): int { return $this->value; } }
class Derived extends Base {}
class Holder { public ?Base $item = null; }
function absent(): ?Base { return null; }
function identity(?Base $value = null): ?Base { return $value; }
function read(Base $value): int { return $value->value; }
function promote(?Derived $value): Base { return $value; }
function widen(?Derived $value): ?Base { return $value; }
function choose(int &$calls): ?Derived { $calls++; return new Derived(); }
$derived = new Derived();
$maybe ?Derived = $derived;
$base ?Base = $maybe;
$required Base = promote($maybe);
$required->value = 9;
echo $base->get(), ":", read($maybe), ":";
$holder = new Holder();
$holder->item = $derived;
echo $holder->item->get(), ":";
$none ?Derived = null;
$wide ?Base = widen($none);
if ($wide !== null) { return 1; }
if (absent() !== null) { return 2; }
if (identity(null) !== null) { return 3; }
if (identity() !== null) { return 4; }
$observer = weak($maybe);
$held = lock($observer);
$held->value = 9;
$empty_observer = weak($none);
$not_held = lock($empty_observer);
if ($not_held !== null) { return 8; }
$calls = 0;
$once Base = choose($calls);
echo $calls, ":", $once->get();
$fallback Base = $wide ?? $derived;
if ($fallback->value !== 9) { return 6; }
$selected ?Base = true ? $base : $derived;
if ($selected->value !== 9) { return 7; }


interface Consumer { function accept(Derived $value): ?Base; }
class Broad implements Consumer {
 public function accept(?Base $value): Derived { return new Derived(); }
}
$consumer Consumer = new Broad();
$returned ?Base = $consumer->accept($derived);
if ($returned === null) { return 5; }
'''
expected = "9:9:9:1:7"
negative = {'required-local': ('class Node {} $n Node = null;', 'Required object boundary rejects null'),
 'required-return': ('class Node {} function f(): Node { return null; } f();',
                     'Required object boundary rejects null'),
 'required-argument': ('class Node {} function f(Node $n): void {} f(null);',
                       'Required object boundary rejects null'),
 'required-default': ('class Node {} function f(Node $n = null): void {} f();',
                      'Required object boundary rejects null'),
 'required-reference': ('class Node {} function f(Node &$n): void {} $n ?Node = null; f($n);',
                        'exact parameter type'),
 'optional-reference': ('class Node {} function f(?Node &$n): void {} $n = new Node(); f($n);',
                        'exact parameter type'),
 'parameter-narrowing': ('class Node {} interface API { function read(?Node $n): void; } class Impl '
                         'implements API { public function read(Node $n): void {} } $x = new Impl();',
                         'signature'),
 'return-weakening': ('class Node {} interface API { function read(): Node; } class Impl implements API { '
                      'public function read(): ?Node { return null; } } $x = new Impl();',
                      'signature'),
 'field-null': ('class Node {} class Holder { public Node $n = null; } $h = new Holder();',
                'Required object boundary rejects null')}
failure_source = 'class Node {} function read(Node $n): void { echo "entered"; } $n ?Node = null; echo "before:"; read($n); echo "after";'
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(mode, failure=False):
        label = ('failure-' if failure else '') + mode
        folder = out / label
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(failure_source if failure else source)
        runner.run(label + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder, ROOT / 'runtime/metadata/modules', 'core', mode])
        build = json.loads(runner.run(label + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
        actual = runner.run(label + '-execute', [build['executable']], expected=1 if failure else 0).decode()
        wanted = 'before:' if failure else expected
        assert actual == wanted, (actual, wanted)
        if failure:
            assert 'nullable required-value extraction' in (out / 'logs' / (label + '-execute.stderr')).read_text()

    def reject(name, program, diagnostic):
        folder = out / name
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(program)
        runner.run(name, ['php8.5', APP / 'tests/async_tasks_publish.php', folder, ROOT / 'runtime/metadata/modules', 'core', 'concrete'], expected=255)
        error = (out / 'logs' / (name + '.stderr')).read_text()
        assert diagnostic in error, error

    run_tasks([Task('retained', lambda: runner.run('retained', ['php8.5', APP / 'tests/nullable_objects.php', out / 'retained']))] +
              [Task(('failure-' if failure else '') + mode,
                    lambda mode=mode, failure=failure: check(mode, failure))
               for failure in [False, True] for mode in ['ordinary', 'concrete']] +
              [Task(name, lambda name=name, pair=pair: reject(name, *pair)) for name, pair in negative.items()])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'expected': expected}, indent=2) + '\n')
