"""V-D01 focused source mutation checks with default prelowering and cold/warm builds."""
import argparse
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

CASES = {
    'dynamic-shallow': ('$child dynamic = {n:2};$xs dynamic = [0,$child];$first=true;foreach($xs as $v){if($first){$first=false;echo "A";$xs[]=3;$child["n"]=20;}else{echo $v["n"];}}', 'A20'),
    'dynamic-two-bindings': ('$xs dynamic = [1,2];$alias=$xs;foreach($xs as $v){$n int=$v;echo $n;if($n===1){foreach($alias as $inner){$m int=$inner;echo "[",$m,"]";if($m===1){$xs=null;$alias[1]=20;}}}}', '1[1][20]2'),
    'dynamic-remove-nested': ('function key(string $s):string{echo $s;return $s;}$d dynamic = {a:{b:1,c:2}};unset($d[key("a")][key("b")]);echo $d["a"]["c"];', 'ab2'),
    'dynamic-remove-missing': ('function key(string $s):string{echo $s;return $s;}$d dynamic = {};unset($d[key("a")][key("b")]);echo "E";', 'aE'),
    'dynamic-remove-null': ('function key():string{echo "wrong";return "a";}$d dynamic = null;unset($d[key()]);echo "E";', 'E'),
    'dynamic-remove-replaced-root': ('function key(dynamic &$d):string{echo "K";$d={b:9};return "a";}$d dynamic = {a:1};unset($d[key($d)]);echo $d["b"];', 'K9'),
    'dynamic-remove-replaced-prefix': ('function key(dynamic &$d):string{$d={a:{b:9,c:8}};return "b";}$d dynamic = {a:{b:1,c:2}};unset($d["a"][key($d)]);echo $d["a"]["c"];', '8'),
    'mixed-remove-cow': ('$d mixed = {a:{b:1,c:2}};$copy=$d;unset($d["a"]["b"]);echo $copy["a"]["b"],$d["a"]["c"];', '12'),
    'dynamic-required-string': ('$d dynamic = "saved";$s string=$d;echo $s;', 'saved'),
    'dynamic-typed-value': ('$xs dynamic = [1,2,3];$v int=9;foreach($xs as $v){echo $v;if($v===1){$xs[]=4;$xs[1]=20;}}echo ":",$v;', '123:3'),
    'dynamic-conversion-failure': ('$xs dynamic = [1,"bad",3];$v int=9;try{foreach($xs as $v){echo $v;}}finally{echo ":",$v;}', '1:1'),
    'dynamic-alias': ('$xs dynamic = [1,2,3];$alias=$xs;foreach($xs as $v){$n int=$v;echo $n,",";if($n===1){$alias[1]=20;$alias[]=4;$alias[2]=30;}}', '1,20,3,'),
    'dynamic-replace': ('$xs dynamic = [1,2,3];$alias=$xs;foreach($xs as $v){$n int=$v;echo $n;if($n===1){$xs=9;$alias[1]=20;}}$last int=$xs;echo ":",$last;', '123:9'),
    'dynamic-rebind-alias': ('$xs dynamic = [1,2,3];$alias=$xs;foreach($xs as $v){$n int=$v;echo $n,",";if($n===1){$alias=null;$xs[1]=20;}}', '1,20,3,'),
    'dynamic-call': ('function change(dynamic &$xs):void{$xs[]=4;$xs[1]=20;}$xs dynamic = [1,2,3];foreach($xs as $v){$n int=$v;echo $n;if($n===1){change($xs);}}', '123'),
    'dynamic-remove': ('$xs dynamic = {a:1,b:2,c:3};foreach($xs as $k=>$v){$n int=$v;echo (string)$k,":",$n,",";if($n===1){unset($xs["b"]);$xs["c"]=30;}}', 'a:1,b:2,c:3,'),
    'dynamic-nested': ('$xs dynamic = [1,2,3];foreach($xs as $v){$n int=$v;echo $n;if($n===1){$xs[]=4;foreach($xs as $inner){$m int=$inner;echo "[",$m,"]";}$xs[1]=20;}}', '1[1][2][3][4]23'),
    'hash-live': ('$xs hash<int> = {a:1,b:2,c:3};foreach($xs as $k=>$v){echo $k,":",$v,",";if($v===1){$xs["b"]=20;}}', 'a:1,b:20,c:3,'),
    'hash-insert': ('$xs hash<int> = {a:1,b:2,c:3};foreach($xs as $k=>$v){echo $k,":",$v,",";if($v===1){$xs["b"]=20;$xs["d"]=4;$xs["c"]=30;}}', 'a:1,b:20,c:3,'),
    'hash-remove': ('$xs hash<int> = {a:1,b:2,c:3};foreach($xs as $k=>$v){echo $k,":",$v,",";if($v===1){unset($xs["b"]);$xs["c"]=30;}}', 'a:1,b:2,c:3,'),
    'hash-replace': ('$xs hash<int> = {a:1,b:2,c:3};foreach($xs as $v){echo $v;if($v===1){$xs={x:9};}}echo ":",$xs["x"];', '123:9'),
    'hash-call': ('function change(hash<int> &$xs):void{$xs["d"]=4;$xs["b"]=20;}$xs hash<int> = {a:1,b:2,c:3};foreach($xs as $v){echo $v;if($v===1){change($xs);}}echo ":",$xs["b"];', '123:20'),
    'hash-copy': ('$xs hash<int> = {a:1,b:2,c:3};$copy=$xs;foreach($xs as $v){echo $v,",";if($v===1){$copy["d"]=4;$xs["b"]=20;}}', '1,20,3,'),
    'hash-integer-keys': ('$xs hash<int,int> = [10=>1,20=>2,30=>3];foreach($xs as $k=>$v){echo $k,":",$v,",";if($v===1){$xs[40]=4;$xs[20]=200;}}', '10:1,20:2,30:3,'),
    'vector-live': ('$xs vector<int> = [1,2,3];foreach($xs as $v){echo $v,",";if($v===1){$xs[1]=20;}}', '1,20,3,'),
    'vector-append': ('$xs vector<int> = [1,2,3];foreach($xs as $k=>$v){echo $k,":",$v,",";if($v===1){$xs[1]=20;$xs[]=4;$xs[2]=30;}}echo count($xs);', '0:1,1:20,2:3,4'),
    'vector-replace': ('$xs vector<int> = [1,2,3];foreach($xs as $v){echo $v;if($v===1){$xs=[9];}}echo ":",$xs[0];', '123:9'),
    'vector-call': ('function change(vector<int> &$xs):void{$xs[]=4;$xs[1]=20;}$xs vector<int> = [1,2,3];foreach($xs as $v){echo $v;if($v===1){change($xs);}}echo ":",$xs[1];', '123:20'),
    'vector-copy': ('$xs vector<int> = [1,2,3];$copy=$xs;foreach($xs as $v){echo $v,",";if($v===1){$copy[]=4;$xs[1]=20;}}', '1,20,3,'),
    'vector-nested': ('$xs vector<int> = [1,2,3];foreach($xs as $v){echo $v;if($v===1){$xs[]=4;foreach($xs as $inner){echo "[",$inner,"]";}$xs[1]=20;}}', '1[1][2][3][4]23'),
    'vector-break': ('$xs vector<int> = [1,2,3];foreach($xs as $v){$xs[]=4;break;}$xs[1]=20;foreach($xs as $v){echo $v,",";}', '1,20,3,4,'),
}
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', required=True, type=Path)
parser.add_argument('--case', action='append', choices=list(CASES))
args = parser.parse_args()
selected = {name: CASES[name] for name in args.case} if args.case else CASES
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
with CommandRunner(out / 'logs', ROOT, jobs=12, journals=[out / 'commands.json']) as runner:
    def check(name, source, expected):
        directory = out / name
        (directory / 'source').mkdir(parents=True)
        (directory / 'source/main.phs').write_text(source)
        published = directory / 'published'
        runner.run(name + '-generate', ['php', APP / 'main.php', '--s2s', directory / 'source', '--output', published])
        assert json.loads((published / '.scpp-output.manifest').read_text())['application_templates'] == 'forbidden'
        for mode in ['cold', 'warm']:
            build = json.loads(runner.run(name + '-' + mode,
                ['php', APP / 'tools/build_cpp.php', published, directory / 'cache'], timeout=600))
            label = name + '-' + mode + '-run'
            failure = name == 'dynamic-conversion-failure'
            assert runner.run(label, [build['executable']], expected=1 if failure else 0).decode() == expected
            if failure:
                assert 'exact int access requires int payload' in (out / 'logs' / (label + '.stderr')).read_text()
            if mode == 'warm':
                assert build['compiled'] == 0 and not build['linked'], build
    run_tasks([Task(name, lambda n=name, v=value: check(n, *v)) for name, value in selected.items()]
              + [Task(name + '-contracts', lambda n=name: runner.run(n + '-contracts',
                      ['php', APP / 'tests' / (n + '.php')])) for name in ['iteration', 'indexing']])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'cases': list(selected), 'default_prelowering': True}) + '\n')
