"""Required class locals use structured definite assignment in both emission modes."""
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
p.add_argument('--case', action='append', default=[], help='Run only selected fixture names')
args = p.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
cases = {
    'take-output': ('''function discard(result<json_node> $source): void {
 $out json_node; $failure error; take($out, $failure, $source);
}
echo "write-only";
''', b'write-only'),
    'values': ('''class Node { public int $value = 7; }
struct DefaultValue { public int $value = 3; }
function early(bool $flag): Node {
 $n Node; if ($flag) { return new Node(); } else { $n = new Node(); } return $n;
}
function throwing(): Node { throw Exception::make("fail"); }
$a Node; if (true) { $a = new Node(); } else { $a = new Node(); }
$b Node; do { $b = new Node(); continue; } while ($b->value < 0);
$c Node; for (;;) { $c = new Node(); break; }
$d Node; for (;;) { $d = new Node(); break; }
$e Node; switch (1) { case 1: $e = new Node(); break; default: $e = new Node(); }
$f Node; try { $f = throwing(); } catch (Exception $error) { $f = new Node(); }
$g Node; try { $unused = 1; } finally { $g = new Node(); }
$h Node; $other = ($h = new Node());
$i Node; $selected = true ? ($i = new Node()) : ($i = new Node());
$j Node; do { $j = new Node(); } while (false);
$k Node; for ($index = 0; $index < 1; $index = $k->value) { $k = new Node(); }
$values vector<Node> = [new Node()]; $element Node;
foreach ($values as $element) { echo $element->value; }
$value DefaultValue;
$optional ?Node;
echo ":", $a->value, $b->value, $c->value, $d->value, $e->value, $f->value,
 $g->value, $h->value, $i->value, $j->value, early(false)->value, ":", $value->value;
''', b'7:77777777777:3'),
    'transfers': ('''class Node { public int $value = 7; }
function leave(): Node { $n Node; return new Node(); echo $n->value; }
$a Node; for (;;) { try { break; } finally { $a = new Node(); } }
$b Node; do { try { continue; } finally { $b = new Node(); } } while ($b->value < 0);
$c Node; try { throw Exception::make("fail"); } catch (Exception $error) { $c = new Node(); }
$d Node; switch (1) { case 1: $d = new Node(); default: $d = new Node(); }
$e Node; $m = match (1) { 1 => ($e = new Node())->value, default => ($e = new Node())->value };
$f Node; try { $f = new Node(); } finally { $unused = 1; }
$g Node; do { for (;;) { break; } $g = new Node(); } while (false);
echo $a->value, $b->value, $c->value, $d->value, $e->value, $f->value, $g->value, leave()->value;
''', b'77777777'),
}
negative = {
    'read': ('class Node { public int $value = 7; } $n Node; echo $n->value;', 'Required local may be read before assignment'),
    'branch': ('class Node { public int $value = 7; } $n Node; if (true) { $n = new Node(); } echo $n->value;', 'Required local may be read before assignment'),
    'self-rhs': ('class Node { public int $value = 7; } $n Node; $n = $n;', 'Required local may be read before assignment'),
    'sibling': ('class Node { public int $value = 7; } $n Node; if (true) { $n = new Node(); } else { echo $n->value; }', 'Required local may be read before assignment'),
    'zero-while': ('class Node { public int $value = 7; } $n Node; while (false) { $n = new Node(); } echo $n->value;', 'Required local may be read before assignment'),
    'zero-for': ('class Node { public int $value = 7; } $n Node; for (;false;) { $n = new Node(); } echo $n->value;', 'Required local may be read before assignment'),
    'continue': ('class Node { public int $value = 7; } $n Node; do { if (true) { continue; } $n = new Node(); } while ($n->value > 0);', 'Required local may be read before assignment'),
    'break': ('class Node { public int $value = 7; } $n Node; do { if (true) { break; } $n = new Node(); } while (false); echo $n->value;', 'Required local may be read before assignment'),
    'for-update': ('class Node { public int $value = 7; } $n Node; for ($i=0;$i<1;$i=$n->value) { if (true) { continue; } $n = new Node(); }', 'Required local may be read before assignment'),
    'switch-entry': ('class Node { public int $value = 7; } $n Node; switch (1) { case 1: $n = new Node(); case 2: echo $n->value; }', 'Required local may be read before assignment'),
    'switch-missing': ('class Node { public int $value = 7; } $n Node; switch (1) { case 1: $n = new Node(); break; } echo $n->value;', 'Required local may be read before assignment'),
    'catch': ('class Node { public int $value = 7; } $n Node; try { $n = new Node(); } catch (Exception $e) { echo $n->value; }', 'Required local may be read before assignment'),
    'finally-read': ('class Node { public int $value = 7; } $n Node; try { $n = new Node(); } finally { echo $n->value; }', 'Required local may be read before assignment'),
    'lazy-and': ('class Node { public int $value = 7; } $n Node; $b = false && (($n = new Node()) === new Node()); echo $n->value;', 'Required local may be read before assignment'),
    'lazy-or': ('class Node { public int $value = 7; } $n Node; $b = true || (($n = new Node()) === new Node()); echo $n->value;', 'Required local may be read before assignment'),
    'ternary': ('class Node { public int $value = 7; } $n Node; $b = true ? ($n = new Node()) : new Node(); echo $n->value;', 'Required local may be read before assignment'),
    'ternary-sibling': ('class Node { public int $value = 7; } $n Node; $b = true ? ($n = new Node()) : $n;', 'Required local may be read before assignment'),
    'coalesce': ('class Node { public int $value = 7; } $n Node; $p ?Node = new Node(); $b = $p ?? ($n = new Node()); echo $n->value;', 'Required local may be read before assignment'),
    'capture': ('class Node { public int $value = 7; } $n Node; $f = function() use ($n): int { return $n->value; };', 'Required local may be read before assignment'),
    'arrow': ('class Node { public int $value = 7; } $n Node; $f = fn(): int => $n->value;', 'Required local may be read before assignment'),
    'reference': ('class Node { public int $value = 7; } $n Node; $alias Node =& $n;', 'Required local may be read before assignment'),
    'ref-arg': ('class Node { public int $value = 7; } function accept(Node &$n): void {} $n Node; accept($n);', 'Required local may be read before assignment'),
    'foreach-zero': ('class Node { public int $value = 7; } $n Node; $items vector<Node> = []; foreach ($items as $n) { echo $n->value; } echo $n->value;', 'Required local may be read before assignment'),
    'shadow': ('class Node { public int $value = 7; } $n Node; if (true) { $n Node = new Node(); } else { $n Node = new Node(); } echo $n->value;', 'Required local may be read before assignment'),
    'catch-empty': ('class Node { public int $value = 7; } $n Node; try { $n = new Node(); } catch (Exception $e) {} echo $n->value;', 'Required local may be read before assignment'),
    'finally-branch': ('class Node { public int $value = 7; } $n Node; try {} finally { if (true) { $n = new Node(); } } echo $n->value;', 'Required local may be read before assignment'),
    'finally-break': ('class Node { public int $value = 7; } $n Node; for (;;) { try { break; } finally { if (true) { $n = new Node(); } } } echo $n->value;', 'Required local may be read before assignment'),
    'match-sibling': ('class Node { public int $value = 7; } $n Node; $v = match(1) { 1 => ($n = new Node())->value, default => $n->value };', 'Required local may be read before assignment'),
    'match-branch': ('class Node { public int $value = 7; } $n Node; $v = match(1) { 1 => ($n = new Node())->value, default => 0 }; echo $n->value;', 'Required local may be read before assignment'),
    'do-shadow': ('class Node { public int $value = 7; } $n Node; do { $n Node = new Node(); } while ($n->value > 0);', 'Required local may be read before assignment'),
}
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(name, mode, data):
        source, expected = data
        label = name + '-' + mode
        folder = out / label
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        runner.run(label + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                        ROOT / 'runtime/metadata/modules', 'json' if name == 'take-output' else 'core', mode])
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
                          ROOT / 'runtime/metadata/modules', 'core', mode], expected=255)
        error = (out / 'logs' / (label + '.stderr')).read_text()
        assert diagnostic.lower() in error.lower(), error
    tasks += [Task(name + '-' + mode, lambda name=name, pair=pair, mode=mode: reject(name, *pair, mode))
              for name, pair in negative.items() if not args.case or name in args.case for mode in ['ordinary', 'concrete']]
    if not args.case or 'retained' in args.case:
        tasks.append(Task('retained', lambda: runner.run('retained',
            ['php8.5', APP / 'tests/local_assignment.php', out / 'retained'])))
    results = run_tasks(tasks)
(out / 'summary.json').write_text(json.dumps([{'name': r.name, 'passed': r.error is None, 'seconds': r.seconds}
                                             for r in results], indent=2) + '\n')
