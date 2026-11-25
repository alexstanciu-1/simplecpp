"""VD08-A01: default-less match execution and retained publication on dev002."""
import argparse
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
a = p.parse_args()
out = a.results.resolve()
out.mkdir(parents=True, exist_ok=False)
E = 'enum E:int {case A=0;case B=7;case C=9;}'
MARK = 'function mark(string $s):string{echo $s;return $s;}'
CASES = {
    'enum-first': (E + 'echo match(E::A){E::A=>"A",E::B=>"B"};', 'A', False),
    'enum-last': (E + 'echo match(E::C){E::A=>"A",E::B=>"B",E::C=>"C"};', 'C', False),
    'integer-labels': ('echo match(7){0=>"bad",6,7=>"yes",7=>"bad"};', 'yes', False),
    'lazy-duplicate': (MARK + 'echo match(7){0=>mark("bad"),7=>mark("Y"),7=>mark("bad")};', 'YY', False),
    'default-first': (MARK + 'echo match(7){default=>mark("bad"),7=>mark("Y")};', 'YY', False),
    'default-last': ('echo match(9){7=>"bad",default=>"D"};', 'D', False),
    'default-only': ('echo match(9){default=>"D"};', 'D', False),
    'selector-once': ('function next(int &$n):int{++$n;return $n;}$n=0;echo match(next($n)){1=>"Y",2=>"bad"},$n;', 'Y1', False),
    'selector-snapshot': ('function change(int &$n):string{$n=9;return "Y";}$n=7;echo match($n){7=>change($n),9=>"bad"},$n;', 'Y9', False),
    'argument-snapshot': ('function change(int &$n):int{$n=9;return 3;}function pair(int $a,int $b):void{echo $a,$b;}$n=7;pair(match($n){7=>$n},change($n));echo $n;', '739', False),
    'enum-return': (E + 'function f(E $v):E{return match($v){E::A=>E::B,E::B=>E::C};}echo enum_name(f(E::A));', 'B', False),
    'nested': ('echo match(7){7=>match(9){9=>"Y"}};', 'Y', False),
    'enum-unmatched': (E + 'echo match(E::C){E::A=>"bad",E::B=>"bad"};echo "bad";', '', True),
    'integer-unmatched': ('echo match(9){0=>"bad",7=>"bad"};echo "bad";', '', True),
    'catch-finally': ('try{echo match(9){7=>"bad"};}catch(Exception $e){echo "caught";}finally{echo "F";}echo "bad";', 'F', True),
    'assignment-failure': ('$n=42;try{$n=match(9){7=>1};}finally{echo $n;}echo "bad";', '42', True),
    'cleanup': ('data struct P{int $id;function __destruct(){echo $this->id;}}function f():int{$outer P={id:9};try{$a P={id:1};$b P={id:2};return match(9){7=>1};}finally{echo "F";}}echo f();', '21F9', True),
    'nested-finally': ('try{try{echo match(9){7=>"bad"};}finally{echo "I";}}finally{echo "O";}', 'IO', True),
    'replace-failure': ('try{try{echo match(9){7=>"bad"};}finally{throw Exception::make("new");}}catch(Exception $e){echo $e->get_message();}', 'new', False),
    'matched-finally': ('function f():int{try{return match(7){7=>8};}finally{echo "F";}}echo f();', 'F8', False),
}
with CommandRunner(out / 'logs', ROOT, jobs=12, journals=[out / 'commands.json']) as runner:
    def build_run(name, published, cache, expected, failure):
        assert json.loads((published / '.scpp-output.manifest').read_text())['application_templates'] == 'forbidden'
        for mode in ['cold', 'warm']:
            b = json.loads(runner.run(name + '-' + mode, ['php', APP / 'tools/build_cpp.php', published, cache], timeout=600))
            label = name + '-' + mode + '-run'
            assert runner.run(label, [b['executable']], expected=1 if failure else 0).decode() == expected
            if failure:
                assert 'Unhandled match expression' in (out / 'logs' / (label + '.stderr')).read_text()
            if mode == 'warm':
                assert b['compiled'] == 0 and not b['linked'], b
    def check(name, source, expected, failure):
        d = out / name
        (d / 'source').mkdir(parents=True)
        (d / 'source/main.phs').write_text(source)
        runner.run(name + '-generate', ['php', APP / 'main.php', '--s2s', d / 'source', '--output', d / 'published'])
        build_run(name, d / 'published', d / 'cache', expected, failure)
    def retained():
        runner.run('retained', ['php', APP / 'tests/match_retained.php', out / 'retained'])
        build_run('retained', out / 'retained-published', out / 'retained-cache', 'B', False)
    run_tasks([Task(n, lambda n=n, v=v: check(n, *v)) for n, v in CASES.items()] + [Task('retained', retained)])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'cases': list(CASES), 'retained': True, 'cold_warm_programs': len(CASES) + 1}, indent=2) + '\n')
