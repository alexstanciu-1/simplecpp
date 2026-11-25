"""Capability-based optional values: built-in and unfamiliar nested payloads."""
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
a = p.parse_args()
out = a.results.resolve()
out.mkdir(parents=True, exist_ok=False)
modules = out / 'modules'
shutil.copytree(ROOT / 'runtime/metadata/modules', modules)
provider = json.loads((APP / 'tests/runtime_protocols.json').read_text())
for entries in ['cpp_types', 'cpp_initialization', 'cpp']:
    for entry in provider[entries]:
        entry['header'] = str(APP / 'tests/runtime_protocol_support.hpp')
(modules / 'protocol_proof.json').write_text(json.dumps(provider))

source = '''enum Contract { case Copyable; case Storable; }
function contracts(?vector<Contract> $values = null): vector<Contract> {
 $effective vector<Contract> = [];
 if ($values === null) { $effective[] = Contract::Copyable; }
 else { $effective = $values; }
 return $effective;
}
if (count(contracts()) !== 1) { return 20; }
if (count(contracts(vector<Contract>::[])) !== 0) { return 21; }
struct Noncopyable { unique<int> $owner; function __construct(int $required) {} }
$empty ?vector<Noncopyable> = null;
if ($empty !== null) { return 22; }
$empty = null;
function wrap(vector<int> $v): ?vector<int> { return $v; }
function extract(?vector<int> $v): vector<int> { return $v; }
function fallback(int &$calls): vector<int> { $calls++; return vector<int>::[9]; }
function count_or_absent(?vector<int> $v = null): int {
 if ($v === null) { return -1; }
 $required vector<int> = $v;
 return count($required);
}
$v vector<int> = [3];
$a ?vector<int> = wrap($v);
$v[] = 4;
$b ?vector<int> = $a;
$a = null;
$out vector<int> = extract($b);
$out[] = 5;
$again vector<int> = $b;
echo count($again), ":", count($out), ":", count_or_absent(), ":", count_or_absent(vector<int>::[]), ":";
$calls = 0;
$present = $b ?? fallback($calls);
$missing = $a ?? fallback($calls);
echo $calls, ":", $present[0], ":", $missing[0], ":";
$joined = true ? $b : $a;
$selected vector<int> = $joined;
echo $selected[0], ":";
$map hash<int> = ["key" => 7];
$optional_map ?hash<int> = $map;
$map_copy hash<int> = $optional_map;
echo $map_copy["key"], ":";
$nested vector<vector<int>> = [[8]];
$optional_nested ?vector<vector<int>> = $nested;
$nested_copy vector<vector<int>> = $optional_nested;
$inner vector<int> = $nested_copy[0];
echo $inner[0], ":";
$tray tray<int> = [6];
$optional_tray ?tray<int> = $tray;
$tray_copy tray<int> = $optional_tray;
echo $tray_copy[0], ":";
$deep tray<tray<int>> = [[11]];
$optional_deep ?tray<tray<int>> = $deep;
$deep_copy tray<tray<int>> = $optional_deep;
$deep_inner tray<int> = $deep_copy[0];
echo $deep_inner[0];
'''
expected = '1:2:-1:0:1:3:9:3:7:8:6:11'
negative = {
    'condition': ('$v ?vector<int> = null; if ($v) { return 1; }', 'admitted truthiness contract'),
    'payload-mismatch': ('$v ?vector<int> = null; $out vector<string> = $v;', 'same payload type'),
    'reference': ('function use_value(vector<int> &$v): void {} $v ?vector<int> = null; use_value($v);', 'exact parameter type'),
    'copy': ('struct Item { unique<int> $owner; } $v ?vector<Item> = null; $copy = $v;', 'copyable_value'),
}
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(mode):
        folder = out / mode
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder, modules, 'protocol_proof', mode])
        build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
        actual = runner.run(mode + '-execute', [build['executable']]).decode()
        assert actual == expected, (actual, expected)

    def reject(name, program, diagnostic):
        folder = out / name
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(program)
        runner.run(name, ['php8.5', APP / 'tests/async_tasks_publish.php', folder, modules, 'protocol_proof', 'concrete'], expected=255)
        error = (out / 'logs' / (name + '.stderr')).read_text()
        assert diagnostic in error, error

    run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['ordinary', 'concrete']] +
              [Task(name, lambda name=name, pair=pair: reject(name, *pair)) for name, pair in negative.items()])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'expected': expected}, indent=2) + '\n')
