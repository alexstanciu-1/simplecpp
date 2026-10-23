"""Capability-based nullable take with value copies and shared identity."""
import argparse
import json
import shutil
import sys
from pathlib import Path
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
p.add_argument('--case', action='append', default=[])
args = p.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
modules = out / 'modules'
shutil.copytree(ROOT / 'runtime/metadata/modules', modules)
provider = json.loads((APP / 'tests/runtime_protocols.json').read_text())
for section in ['cpp_types', 'cpp_initialization', 'cpp']:
    for entry in provider[section]:
        entry['header'] = str(APP / 'tests/runtime_protocol_support.hpp')
(modules / 'protocol_proof.json').write_text(json.dumps(provider))
cases = {
 'inline-class': ('''class Item { public int $n = 7; }
$value value<Item> = new Item(); $source ?value<Item> = $value;
$out value<Item>;
if (!take($out, $source)) { return 1; } $out->n = 8;
$saved value<Item> = $source; echo $saved->n, ":", $out->n;
''', b'7:8'),
 'provider': ('''$value tray<tray<int>> = [[11]]; $source ?tray<tray<int>> = $value;
$out tray<tray<int>> = [];
if (!take($out, $source)) { return 1; }
$inner tray<int> = $out[0]; echo $inner[0];
$empty ?tray<tray<int>> = null;
echo take($out, $empty) ? ":bad" : ":empty";
''', b'11:empty'),
 'containers': ('''$value vector<int> = [3, 7]; $source ?vector<int> = $value;
$out vector<int> = []; if (!take($out, $source)) { return 1; }
$out[0] = 9; $saved vector<int> = $source;
echo $saved[0], ":", $out[0];
$empty ?vector<int> = null; if (take($out, $empty)) { return 2; } echo ":", $out[0];
$inner hash<int> = ["x" => 4]; $nested vector<hash<int>> = [$inner];
$wrapped ?vector<hash<int>> = $nested; $copy vector<hash<int>> = [];
if (!take($copy, $wrapped)) { return 3; } echo ":", $copy[0]["x"];
''', b'3:9:9:4'),
 'records': ('''struct Item { public int $n; public function __construct(set:$n) {} }
$value = new Item(7); $source ?Item = $value; $out = new Item(1);
if (!take($out, $source)) { return 1; } $out->n = 8;
$saved Item = $source; echo $saved->n, ":", $out->n;
$empty ?Item = null; if (take($out, $empty)) { return 2; } echo ":", $out->n;
''', b'7:8:8'),
 'handles': ('''interface Named { public function number(): int; }
class Node implements Named { public int $n = 7; public function number(): int { return $this->n; } }
function once(?Node $value, int &$calls): ?Node { $calls++; return $value; }
$node = new Node(); $source ?Node = $node; $out Node; $calls = 0;
if (!take($out, once($source, $calls))) { return 1; }
echo $out === $node, ":", $calls;
$empty ?Node = null; if (take($out, $empty)) { return 2; } echo ":", $out === $node;
$view ?Named = $node; $named Named;
if (!take($named, $view)) { return 3; } echo ":", $named->number();
''', b'1:1:1:7'),
}
negative = {'noncopy': ('struct Owned { public unique<int> $value; } $source ?Owned; $out Owned; take($out, $source);', 'copyable_value'), 'inexact': ('$source ?vector<int> = null; $out vector<string> = []; take($out, $source);', 'exact branch type'), 'base-output': ('class Base {} class Child extends Base {} $source ?Child = null; $out Base = new Base(); take($out, $source);', 'exact branch type'), 'explicit-shared': ('struct Item {} $source shared<Item>; $out shared<Item>; take($out, $source);', 'supported wrapper source')}
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(name, mode, data):
        source, expected = data
        label = name + '-' + mode
        folder = out / label
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        runner.run(label + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                        modules, 'protocol_proof', mode])
        build = json.loads(runner.run(label + '-build', ['php8.5', APP / 'tools/build_cpp.php',
                                                        folder / 'published', folder / 'cache'], timeout=300))
        actual = runner.run(label + '-execute', [build['executable']])
        assert actual == expected, (actual, expected)
    tasks = [Task(name + '-' + mode, lambda name=name, mode=mode, data=data: check(name, mode, data))
             for name, data in cases.items() if not args.case or name in args.case for mode in ['ordinary', 'concrete']]
    def reject(name, program, diagnostic, mode):
        label = name + '-' + mode
        folder = out / label
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(program)
        runner.run(label, ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                          modules, 'protocol_proof', mode], expected=255)
        error = (out / 'logs' / (label + '.stderr')).read_text()
        assert diagnostic.lower() in error.lower(), error
    tasks += [Task(name + '-' + mode, lambda name=name, pair=pair, mode=mode: reject(name, *pair, mode))
              for name, pair in negative.items() if not args.case or name in args.case for mode in ['ordinary', 'concrete']]
    if not args.case or 'retained' in args.case:
        tasks.append(Task('retained', lambda: runner.run('retained', ['php8.5', APP / 'tests/nullable_take.php', out / 'retained'])))
    results = run_tasks(tasks)
(out / 'summary.json').write_text(json.dumps([{'name': r.name, 'passed': r.error is None, 'seconds': r.seconds}
                                             for r in results], indent=2) + '\n')
