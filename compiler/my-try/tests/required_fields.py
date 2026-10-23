"""Required object fields use direct initialization; struct defaults remain distinct."""
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
    'values': ('''class Node { public int $value = 7; }
data class Holder { public Node $node; public function __construct(set:$node) {} }
class Child extends Holder { public ?Node $other = null; }
data class Data { public Node $node; }
struct Value { public Node $node; public function __construct(set:$node) {} }
struct DefaultValue { public int $number = 3; }
data class PrivateHolder {
 public Node $node;
 private function __construct(set:$node) {}
 public static function make(Node $node): PrivateHolder { $result PrivateHolder = {node:$node}; return $result; }
}
$node = new Node();
$holder Holder = {node:$node};
$child = new Child($node);
$data Data = {node:$node};
$value = new Value($node);
$private = PrivateHolder::make($node);
$default DefaultValue;
$local Node;
if (true) { $local = $node; } else { $local = new Node(); }
echo $holder->node->value, ":", $child->node->value, ":", $data->node->value,
 ":", $value->node->value, ":", $default->number, ":", $local->value, ":", $private->node->value;
''', b'7:7:7:7:3:7:7'),
}
negative = {
 'plain': ('class Node {} class Holder { public Node $node; }', 'Required object property needs direct initialization'),
 'static': ('class Node {} class Holder { public static Node $node; }', 'Required object property needs direct initialization'),
 'body': ('class Node {} class Holder { public Node $node; public function __construct(Node $n) { $this->node = $n; } }', 'Property requires set: initialization'),
 'early': ('class Node {} class Holder { public Node $node; public function __construct() { return; } }', 'Property requires set: initialization'),
 'data-missing': ('class Node {} data class Holder { public Node $node; } $h Holder = {};', 'required'),
 'struct-default': ('class Node {} struct Value { public Node $node; public function __construct(set:$node) {} } $v Value;', 'explicit initializer'),
 'inherited': ('class Node {} class Base { public function __construct() {} } class Child extends Base { public Node $node; }', 'Required object property needs direct initialization'),
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
    results = run_tasks(tasks)
(out / 'summary.json').write_text(json.dumps([{'name': r.name, 'passed': r.error is None, 'seconds': r.seconds}
                                             for r in results], indent=2) + '\n')
