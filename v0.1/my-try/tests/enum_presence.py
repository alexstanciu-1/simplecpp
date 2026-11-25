"""Enum presence preserves lazy lookup and operand effects in both C++ output modes."""
import argparse, json, sys
from pathlib import Path
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
out = p.parse_args().results.resolve()
out.mkdir(parents=True, exist_ok=False)
SOURCE = '''
enum E : int { case Zero = 0; case Seven = 7; }
enum Small : int8 { case Low = -128; }
enum Wide : uint64 { case High = 18446744073709551615; }
enum Unit { case First; }
function produce(int &$calls): E { $calls += 1; return E::Zero; }
function key(int &$calls): string { $calls += 1; return "leaf"; }
$zero = E::Zero; $small = Small::Low; $wide = Wide::High; $unit = Unit::First;
if (!isset($zero) || !isset($small) || !isset($wide) || !isset($unit)) { return 1; }
$calls = 0;
if (!isset(produce($calls)) || $calls !== 1) { return 2; }
$values hash<E> = [];
if (isset($values["missing"]) || count($values) !== 0) { return 3; }
$values["leaf"] = E::Zero;
if (!isset($values["leaf"]) || $values["leaf"] !== E::Zero) { return 4; }
$nested hash<hash<E>> = [];
$nested["present"] = $values;
$keys = 0;
if (isset($nested["missing"][key($keys)]) || $keys !== 0) { return 5; }
if (!isset($nested["present"][key($keys)]) || $keys !== 1) { return 6; }
if (count($nested) !== 1 || count($values) !== 1) { return 7; }
$list vector<E> = [E::Zero];
if (!isset($list[0]) || isset($list[1]) || count($list) !== 1) { return 8; }
$maybe hash<nullable<E>> = [];
$maybe["absent"] = null;
$maybe["present"] = E::Zero;
if (isset($maybe["absent"]) || !isset($maybe["present"]) || isset($maybe["missing"])) { return 9; }
$results hash<result<E>> = [];
$results["present"] = E::Zero;
$results["failed"] = error::make("failure");
if (!isset($results["present"]) || isset($results["failed"])) { return 10; }
$falseable hash<result_or_false<E>> = [];
$falseable["present"] = E::Zero;
$falseable["absent"] = false_sentinel;
if (!isset($falseable["present"]) || isset($falseable["absent"])) { return 11; }
$flags hash<result_or_bool<E>> = [];
$flags["yes"] = true_sentinel;
$flags["no"] = false_sentinel;
if (!isset($flags["yes"]) || isset($flags["no"])) { return 12; }
echo "enum-presence-ok";
'''
NEGATIVE = {
    'plain-empty': 'enum E { case A; } $v = E::A; empty($v);',
    'keyed-empty': 'enum E { case A; } $v hash<E> = []; empty($v["missing"]);',
    'condition': 'enum E { case A; } $v = E::A; if ($v) { return 1; }',
}
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(mode):
        folder = out / mode
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(SOURCE)
        runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                    ROOT / 'runtime/metadata/modules', 'compiler', mode])
        manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
        assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
        build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
        assert runner.run(mode + '-run', [build['executable']]) == b'enum-presence-ok'
    def reject(name, source):
        folder = out / name
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        runner.run(name, ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                    ROOT / 'runtime/metadata/modules', 'compiler', 'concrete'], expected=255)
        error = (out / 'logs' / (name + '.stderr')).read_text()
        assert ('empty requires a collection or string' in error if 'empty' in name else 'condition' in error.lower()), error
    results = run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['ordinary', 'concrete']]
        + [Task(name, lambda name=name, source=source: reject(name, source)) for name, source in NEGATIVE.items()])
(out / 'summary.json').write_text(json.dumps([{'name':r.name, 'seconds':r.seconds, 'passed':r.error is None} for r in results], indent=2) + '\n')
