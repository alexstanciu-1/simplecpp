"""VD07-A03: read-only owned value packs, snapshots and cleanup with default prelowering."""
import argparse
import json
import hashlib
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler

p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
p.add_argument('--baseline', type=Path)
a = p.parse_args()
out = a.results.resolve()
out.mkdir(parents=True, exist_ok=False)
CASES = {
    'int-pack': ('function f(int $n,int &...$xs):int{foreach($xs as $x){$n+=$x;}return $n;}echo f(1,2,3),":",f(4);', '6:4', False),
    'float-pack': ('function f(float &...$xs):float{$n=0.0;foreach($xs as $x){$n+=$x;}return $n;}echo f(1.5,2.5);', '4', False),
    'bool-pack': ('function f(bool &...$xs):int{$n=0;foreach($xs as $x){if($x){++$n;}}return $n;}echo f(true,false,true);', '2', False),
    'string-pack': ('function f(string &...$xs):string{$s="";foreach($xs as $x){$s.=$x;}return $s;}echo f("a","b");', 'ab', False),
    'method-pack': ('class C{function f(int &...$xs):int{$n=0;foreach($xs as $x){$n+=$x;}return $n;}}$c=new C();echo $c->f(2,3);', '5', False),
    'static-pack': ('class C{static function f(int &...$xs):int{$n=0;foreach($xs as $x){$n+=$x;}return $n;}}echo C::f(2,3);', '5', False),
    'default-prefix': ('function f(int $base=9,int &...$xs):int{foreach($xs as $x){$base+=$x;}return $base;}echo f(),":",f(2,3);', '9:5', False),
    'int-snapshot': ('function next(int &$n):int{++$n;return $n;}function f(int &...$xs):void{foreach($xs as $x){echo $x;}}$n=1;f($n,next($n));echo ":",$n;', '12:2', False),
    'string-snapshot': ('function change(string &$s):string{$s="new";return $s;}function f(string &...$xs):void{foreach($xs as $x){echo $x;}}$s="old";f($s,change($s));echo ":",$s;', 'oldnew:new', False),
    'reference-prefix': ('function next(int &$n):int{++$n;return $n;}function f(int &$n,int &...$xs):void{echo $n,":";foreach($xs as $x){echo $x;}$n=9;}$n=1;f($n,$n,next($n));echo ":",$n;', '2:12:9', False),
    'owned-return': ('function f(string &...$xs):vector<string>{return $xs;}$s="old";$v=f($s,"tail");$s="new";echo $v[0],$v[1];', 'oldtail', False),
    'local-copy': ('function f(int &...$xs):int{$copy=$xs;$copy[0]=9;return $copy[0];}$n=3;echo f($n),":",$n;', '9:3', False),
    'argument-failure': ('function first():string{echo "A";return "saved";}function fail():string{echo "B";throw Exception::make("X");}function last():string{echo "wrong";return "bad";}function f(string &...$xs):void{echo "wrong";}try{f(first(),fail(),last());}catch(Exception $e){echo $e->get_message();}finally{echo "F";}', 'ABXF', False),
    'conversion-failure': ('function later():int{echo "wrong";return 3;}function f(int &...$xs):void{echo "wrong";}$v ?int=null;try{f(1,$v,later());}finally{echo "F";}', 'F', True),
    'callee-failure': ('function f(string &...$xs):void{echo $xs[0];throw Exception::make("X");}try{f("saved","tail");}catch(Exception $e){echo $e->get_message();}finally{echo "F";}', 'savedXF', False),
    'receiver-lifetime': ('class C{function __destruct(){echo "D";}function f(string &...$xs):void{echo "C",$xs[0],$xs[1];}}function clear(C &$c):string{$c=null;echo "A";return "b";}$c=new C();$c->f("a",clear($c));echo "E";', 'ACabDE', False),
    'receiver-failure': ('class C{function __destruct(){echo "D";}function f(string &...$xs):void{echo "wrong";}}function fail(C &$c):string{$c=null;echo "A";throw Exception::make("X");}$c=new C();try{$c->f("saved",fail($c));}catch(Exception $e){echo $e->get_message();}finally{echo "F";}', 'ADXF', False),
    'receiver-order': ('class C{function f(int &...$xs):void{foreach($xs as $x){echo $x;}}}function make():C{echo "R";return new C();}function arg(int $n):int{echo $n;return $n;}make()->f(arg(1),arg(2));', 'R1212', False),
}
# Exercise every approved scalar family through both method call forms.
for kind in ['method', 'static']:
    for scalar, body, args, expected in [
        ('float', '$n=0.0;foreach($xs as $x){$n+=$x;}return $n;', '1.5,2.5', '4'),
        ('bool', '$n=0;foreach($xs as $x){if($x){++$n;}}return $n;', 'true,false,true', '2'),
        ('string', '$s="";foreach($xs as $x){$s.=$x;}return $s;', '"a","b"', 'ab'),
    ]:
        result = 'int' if scalar == 'bool' else scalar
        modifier = 'static ' if kind == 'static' else ''
        call = 'echo C::f(' if kind == 'static' else '$c=new C();echo $c->f('
        source = 'class C{' + modifier + 'function f(' + scalar + ' &...$xs):' + result + '{' + body + '}}' + call + args + ');'
        CASES[kind + '-' + scalar] = (source, expected, False)
CASES['literal-controls'] = ('$v vector<string> =["a","b"];$h hash<int> ={x:7};$m mixed=[9];$d dynamic={x:8};echo $v[0],$v[1],$h["x"],$m[0],$d["x"];', 'ab798', False)
# Every scalar and source call form additionally proves empty and one-element packs.
for form in ['free', 'static', 'method']:
    for scalar, literal in [('int','3'),('float','1.5'),('bool','true'),('string','"saved"')]:
        decl='function size('+scalar+' &...$xs):int{return count($xs);}'
        call='size'
        if form != 'free':
            decl='class C{'+('static ' if form=='static' else '')+decl+'}'
            if form=='method':decl+='$c=new C();'
            call='C::size' if form=='static' else '$c->size'
        CASES[form+'-'+scalar+'-arity']=(decl+'echo '+call+'(),":",'+call+'('+literal+');','0:1',False)
CASES.update({
 'repeated-caller': ('function f(int &...$xs):int{$n=0;foreach($xs as $x){$n+=$x;}return $n;}$n=3;echo f($n,$n),":",$n;', '6:3', False),
 'callee-no-alias': ('function f(int &$n,int &...$xs):void{$n=9;echo $xs[0],":",$xs[1];}$n=3;f($n,$n,$n);echo ":",$n;', '3:3:9', False),
 'readonly-forward': ('function read(const vector<string> &$xs):string{return $xs[0];}function f(string &...$xs):string{return read($xs);}echo f("saved");', 'saved', False),
 'local-alias-read': ('function f(string &...$xs):string{$a =& $xs;return $a[0];}echo f("saved");', 'saved', False),
 'writable-default-prefix': ('function f(int &$n=7,int &...$xs):int{++$n;foreach($xs as $x){$n+=$x;}return $n;}echo f(),":";$n=1;echo f($n,2,3),":",$n;', '8:7:7', False),
 'ordinary-pack-write-control': ('function f(int ...$xs):int{$xs[0]=9;return $xs[0];}$n=3;echo f($n),":",$n;', '9:3', False),
})
REJECTIONS = {
 'write': ('function f(int &...$xs):void{$xs[0]=9;}f(1);','read-only'),
 'append': ('function f(int &...$xs):void{$xs[]=9;}f();','read-only'),
 'replace': ('function f(int &...$xs):void{$xs=[9];}f(1);','read-only'),
 'alias-write': ('function f(int &...$xs):void{$a =& $xs;$a[0]=9;}f(1);','read-only'),
 'clean': ('function f(int &...$xs):void{clean($xs);}f(1);','read-only'),
 'unset-index': ('function f(int &...$xs):void{unset($xs[0]);}f(1);','keyed removal for this receiver is deferred'),
 'mutable-forward': ('function change(vector<int> &$xs):void{$xs[]=9;}function f(int &...$xs):void{change($xs);}f(1);','read-only'),
 'reference-return': ('function &f(int &...$xs):vector<int>{return $xs;}f(1);','reference return requires exactly one reference parameter'),
 'escaping-capture': ('function f(int &...$xs):function<int()>{return function() use(&$xs):int{return count($xs);};}$f=f(1);echo $f();','Callable boundary requires an exact parameter and return signature'),
 'wider': ('function f(vector<int> &...$xs):void{}','canonical scalar elements'),
 'const-value-pack': ('function f(const int ...$xs):void{}','named source compatibility spelling'),
 'explicit-const': ('function f(const int &...$xs):void{}','const reference-pack spelling'),
 'anonymous': ('$f=function(int &...$xs):void{};','Anonymous read-only compatibility packs'),
 'default': ('function f(int &...$xs=1):void{}','cannot have an explicit default'),
 'not-last': ('function f(int &...$xs,int $n):void{}','variadic parameter must be last'),
 'constructor': ('class C{function __construct(int &...$xs){}}','Constructor parameters require fixed by-value inputs'),
}
sanitizer = out / 'sanitizer-clang'
sanitizer.write_text('#!/bin/sh\nexec /usr/bin/clang++ -fsanitize=address,undefined -fno-sanitize-recover=undefined -fno-omit-frame-pointer "$@"\n')
sanitizer.chmod(0o755)
with CommandRunner(out / 'logs', ROOT, jobs=12, journals=[out / 'commands.json']) as runner:
    def baseline_probe(name, source, expected):
        d = out / name
        d.mkdir()
        source_file = d / 'main.phs'
        source_file.write_text(source)
        row = {'name': name, 'source_sha256': hashlib.sha256(source.encode()).hexdigest(), 'stage': 'generation', 'supported': False}
        try:
            generated = runner.run(name + '-generate', ['php', APP / 'tests/compatibility_export.php', a.baseline, source_file, 'strict'])
            target = d / 'main.cpp'
            target.write_bytes(generated)
            row['stage'] = 'build'
            cpp.compile(name + '-build', target, d / 'program')
            row['stage'] = 'execution'
            row['stdout'] = runner.run(name + '-run', [d / 'program']).decode()
            assert row['stdout'] == expected, (row['stdout'], expected)
            row.update(stage='executed', supported=True)
        except Exception as error:
            row['failure'] = str(error)
        (d / 'outcome.json').write_text(json.dumps(row, indent=2) + '\n')
    baseline_tasks = []
    if a.baseline:
        cpp = CppFixtureCompiler(runner, a.baseline, out, pch=False, runtime=True)
        cpp.flags.append('-std=c++23')
        names=['int-pack','float-pack','bool-pack','string-pack','method-pack','static-pack','method-float','method-bool','method-string','static-float','static-bool','static-string','repeated-caller','local-copy']
        names += [n for n in CASES if n.endswith('-arity')]
        for n in names:
            source,expected,_=CASES[n]
            baseline_tasks.append(Task('v01-'+n,lambda n=n,s=source,e=expected:baseline_probe('v01-'+n,s,e)))
    def build_run(name, published, cache, expected, failure):
        assert json.loads((published / '.scpp-output.manifest').read_text())['application_templates'] == 'forbidden'
        for mode in ['cold', 'warm']:
            b = json.loads(runner.run(name + '-' + mode, ['php', APP / 'tools/build_cpp.php', published, cache], timeout=600))
            label = name + '-' + mode + '-run'
            assert runner.run(label, [b['executable']], expected=1 if failure else 0).decode() == expected
            if failure:
                assert 'null' in (out / 'logs' / (label + '.stderr')).read_text()
            if mode == 'warm':
                assert b['compiled'] == 0 and not b['linked'], b
    def check(name, source, expected, failure):
        d = out / name
        (d / 'source').mkdir(parents=True)
        (d / 'source/main.phs').write_text(source)
        runner.run(name + '-generate', ['php', APP / 'main.php', '--s2s', d / 'source', '--output', d / 'published'])
        build_run(name, d / 'published', d / 'cache', expected, failure)
        if name in ['argument-failure', 'callee-failure', 'receiver-lifetime', 'receiver-failure', 'owned-return']:
            b = json.loads(runner.run(name + '-sanitized', ['php', APP / 'tests/async_sanitized_build.php', d / 'published', d / 'sanitized-cache', sanitizer], timeout=600))
            assert runner.run(name + '-sanitized-run', [b['executable']]).decode() == expected

    def retained():
        runner.run('retained', ['php', APP / 'tests/readonly_pack_retained.php', out / 'retained'])
        build_run('retained', out / 'retained-published', out / 'retained-cache', '9', False)
    def reject(name, source, diagnostic):
        d = out / name
        (d / 'source').mkdir(parents=True)
        (d / 'source/main.phs').write_text(source)
        runner.run(name, ['php', APP / 'main.php', '--s2s', d / 'source', '--output', d / 'published'], expected=1)
        assert diagnostic in (out / 'logs' / (name + '.stderr')).read_text()
    run_tasks(baseline_tasks + [Task(n, lambda n=n, v=v: check(n, *v)) for n, v in CASES.items()]
              + [Task(n, lambda n=n, v=v: reject(n, *v)) for n, v in REJECTIONS.items()]
              + [Task('retained', retained)]
              + [Task(n, lambda n=n: runner.run(n, ['php', APP / 'tests' / (n + '.php')])) for n in ['references', 'method_parameters', 'callables']])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'cases': list(CASES), 'retained': True, 'rejections': list(REJECTIONS), 'sanitized': 5, 'cold_warm_programs': len(CASES) + 1}, indent=2) + '\n')
