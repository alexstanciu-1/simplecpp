"""Temporary field-only empty object storage; required values and struct defaults remain distinct."""
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
cases['temporary-storage'] = ('''class Node { public int $value = 9; }
interface Named { public function number(): int; }
class NamedNode implements Named { public function number(): int { return 4; } }
class Holder {
 public Node $node = null;
 public static Node $shared;
 public static Node $explicit = null;
 private Node $hidden;
 public Named $contract;
 public function __construct() { return; }
 public function install(Node $n): void { $this->node = $n; $this->hidden = $n; }
 public function read(): Node { return $this->hidden; }
}
class Plain { public Node $node; }
class Base { public function __construct() {} }
class Child extends Base { public Node $node = null; }
class Body { public Node $node; public function __construct(Node $n) { $this->node = $n; } }
class Optional { public ?Node $node = null; }
class Slot { public Node $node = null; }
$node = new Node();
$holder = new Holder(); $holder->install($node); Holder::$shared = $node; Holder::$explicit = $node;
$holder->contract = new NamedNode();
$plain = new Plain(); $plain->node = $node;
$child = new Child(); $child->node = $node;
$body = new Body($node);
$optional = new Optional(); $optional->node = $node; $optional->node = null;
$slot = new Slot(); $slot->node = $node;
echo $holder->node === $node ? 'same' : 'bad';
echo ':', $holder->read()->value, ':', Holder::$shared->value, ':', $holder->contract->number();
echo ':', $plain->node->value, ':', $child->node->value, ':', $body->node->value, ':', $slot->node->value;
echo ':', $optional->node === null ? 'optional' : 'bad';
''', b'same:9:9:4:9:9:9:9:optional')
negative = {
 'later-write': ('class Node {} class Holder { public Node $node = null; } $h = new Holder(); $h->node = null;', 'rejects null'),
 'later-static-write': ('class Node {} class Holder { public static Node $node = null; } Holder::$node = null;', 'rejects null'),
 'argument': ('class Node {} function consume(Node $node): void {} consume(null);', 'rejects null'),
 'return': ('class Node {} function make(): Node { return null; }', 'rejects null'),
 'set-missing': ('class Node {} class Holder { public Node $node = null; public function __construct(set:$node) {} } $h = new Holder();', 'required'),
 'set-null': ('class Node {} class Holder { public Node $node = null; public function __construct(set:$node) {} } $h = new Holder(null);', 'rejects null'),
 'data-null-default-missing': ('class Node {} data class Holder { public Node $node = null; } $h Holder = {};', 'required'),
 'inline-null': ('struct Value { public int $n; } class Holder { public Value $value = null; }', 'same payload type'),
 'data-missing': ('class Node {} data class Holder { public Node $node; } $h Holder = {};', 'required'),
 'struct-default': ('class Node {} struct Value { public Node $node; public function __construct(set:$node) {} } $v Value;', 'explicit initializer'),
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
