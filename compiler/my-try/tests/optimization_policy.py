"""Prove policy switches without source edits, outlining eligibility and executable behavior."""
import argparse, json, sys
from pathlib import Path
ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler

def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    out = parser.parse_args().results.resolve(); out.mkdir(parents=True, exist_ok=False)
    src = out / 'source'; src.mkdir()
    (src / 'types.phs').write_text('''class Item { public int $number = 7; }
class Bag { public $items hash<Item> = []; public int $marker = 3; }
class Authored { public $items hash<Item> = []; public function __construct() {} }
class Derived extends Bag { public int $extra = 5; }
struct ValueBag { public $items hash<Item> = []; }
class Incomplete { public $items hash<Item> = []; public int $unset; }
''')
    (src / 'main.phs').write_text('''$a = new Bag(); $b = new Bag(); $alias = $a;
$item = new Item(); $alias->items["x"] = $item;
$owned unique<Bag> = new Bag();
if (count($owned->items) !== 0 || $owned->marker !== 3) { return 6; }
if (count($b->items) !== 0) { return 1; }
if ($a->items["x"]->number !== 7) { return 2; }
if ($a->marker !== 3) { return 3; }
$derived = new Derived(); if ($derived->marker !== 3) { return 4; }
$authored = new Authored(); if (count($authored->items) !== 0) { return 5; }
return 0;''')
    driver = out / 'proof.php'
    driver.write_text('''<?php
namespace scpp\\compiler;
require ''' + repr(str(ROOT/'boot.php')) + ''';
$compiler = new Compiler(); $compiler->init([''' + repr(str(src)) + ''']);
$compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
$baseline = CPP_Output::encode(Model::$cpp_files);
foreach ([2, 0, 1, 3, 0, 2] as $index => $level) {
 $publication = new Output_Publication(''' + repr(str(out)) + ''' . '/published');
 try { $compiler->cpp($publication, new cpp_lowering_policy($level)); }
 finally { $publication->close(); }
 $text = CPP_Output::encode(Model::$cpp_files);
 if ($level >= 2 && $text !== $baseline) { throw new \\Exception('Release output changed'); }
 file_put_contents(''' + repr(str(out)) + ''' . '/' . $index . '.json', $text);
}
$compiler->cpp(null, new cpp_lowering_policy(0));
$before = [];
foreach (Model::$cpp_files as $artifact) { $before[$artifact->file_name] = $artifact->text; }
$source_path = ''' + repr(str(src/'types.phs')) + ''';
file_put_contents($source_path, str_replace('$marker = 3', '$marker = 4', file_get_contents($source_path)));
$compiler->update_cpp([$source_path], null, new cpp_lowering_policy(0));
$changed = [];
foreach (Model::$cpp_files as $artifact) {
 if ($before[$artifact->file_name] !== $artifact->text) { $changed[] = $artifact->file_name; }
}
if ($changed !== ['units/n6d61696e/n74797065732e706873.cpp']) { throw new \\Exception('Default edit escaped owning unit'); }
// Eligibility changes must refresh callers even when their source is untouched.
file_put_contents($source_path, str_replace('public int $marker = 4;', 'public int $marker = 4; public function __construct() {}', file_get_contents($source_path)));
$compiler->update_cpp([$source_path], null, new cpp_lowering_policy(0));
$ineligible = CPP_Output::encode(Model::$cpp_files);
if (str_contains($ineligible, 'allocate_shared_Bag')) { throw new \\Exception('Stale outlined allocation'); }
file_put_contents($source_path, str_replace('public int $marker = 4; public function __construct() {}', 'public int $marker = 4;', file_get_contents($source_path)));
$compiler->update_cpp([$source_path], null, new cpp_lowering_policy(0));
$restored = CPP_Output::encode(Model::$cpp_files);
if (!str_contains($restored, 'allocate_shared_Bag')) { throw new \\Exception('Missing restored allocation'); }
Compiler_Lifecycle::reset_cpp();
$compiler->cpp(null, new cpp_lowering_policy(0));
if (CPP_Output::encode(Model::$cpp_files) !== $restored) { throw new \\Exception('Retained and fresh allocation output differ'); }
$view = json_read('{"cpp_policy":"cpp-lowering-v1:latency"}');
Native_CPP_Publication::require_policy($view, 0);
Native_CPP_Publication::require_policy($view, 1);
$rejected = false;
try { Native_CPP_Publication::require_policy($view, 2); }
catch (\\RuntimeException $error) { $rejected = true; }
if (!$rejected) { throw new \\Exception('Release accepted latency output'); }
''')
    with CommandRunner(out/'logs', ROOT.parents[1]) as runner:
        def proof():
            runner.run('policy-switches', ['php', driver], cli_reason='Retained backend policy switching')
            from cpp_artifacts import decode_artifacts
            cpp = CppFixtureCompiler(runner, ROOT.parents[1], out, runtime=True, pch=False, cxx='/usr/bin/clang++-18')
            for i,level in enumerate([2,0,1,3,0,2]):
                payload=(out/f'{i}.json').read_bytes()
                artifacts=decode_artifacts(payload)
                text=''.join(a['text'] for a in artifacts)
                assert ('record_Bag::record_Bag()' in text)==(level<2)
                assert ('allocate_shared_Bag()' in text)==(level<2)
                assert '::scpp::unique<record_Bag>()' in text
                for excluded in ['Authored','Derived','ValueBag','Incomplete','Item']:
                    assert f'allocate_shared_{excluded}()' not in text
                for name in ['Derived','ValueBag','Incomplete']:
                    assert f'record_{name}::record_{name}()' not in text
                if i < 4:
                    cpp.flags=[f for f in cpp.flags if not str(f).startswith('-O')]+[f'-O{level}']
                    cpp.compile_artifacts(f'mode-{level}',payload,out/f'app-{level}')
                    runner.run(f'execute-{level}',[out/f'app-{level}'])
            assert (out/'1.json').read_bytes()==(out/'2.json').read_bytes()==(out/'4.json').read_bytes()
            (out/'summary.json').write_text(json.dumps(dict(passed=True,levels=[0,1,2,3],retained_switches=6,default_edit_changed_units=1,allocation_eligibility_switches=2),indent=2))
        run_tasks([Task('optimization-policy',proof)])

if __name__=='__main__': main()
