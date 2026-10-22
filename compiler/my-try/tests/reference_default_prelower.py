"""VD07-A01: read-only reference defaults, binding and call-local lifetime."""
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
CASES = {}
for form in ['free', 'static', 'method']:
    for scalar, literal, explicit, expected in [('int','7','3','7:3'),('float','1.5','2.5','1.5:2.5'),('bool','true','false','1:0'),('string','"saved"','"live"','saved:live')]:
        result = 'int' if scalar == 'bool' else scalar
        body = 'return $n ? 1 : 0;' if scalar == 'bool' else 'return $n;'
        function = 'function f(const '+scalar+' &$n='+literal+'):'+result+'{'+body+'}'
        if form == 'free': source = function; call = 'f'
        else:
            source = 'class C{'+('static ' if form == 'static' else '')+function+'}'
            if form == 'method': source += '$c=new C();'
            call = 'C::f' if form == 'static' else '$c->f'
        source += '$x='+explicit+';echo '+call+'(),":",'+call+'($x);'
        CASES[form+'-'+scalar]=(source,expected,False)
CASES.update({
 'alias-snapshot': ('function next(int &$n):int{++$n;return $n;}function f(int $snapshot,const int &$live=7,int $effect=0):void{echo $snapshot,":",$live,":",$effect;}$n=1;f($n,$n,next($n));', '1:2:2', False),
 'live-callee-alias': ('function f(int &$a,const int &$b=7):void{$a=9;echo $b;}$n=1;f($n,$n);echo ":";f($n);', '9:7', False),
 'forward': ('function read(const string &$s):string{return $s;}function f(const string &$s="saved"):string{return read($s);}echo f();', 'saved', False),
 'nested': ('function f(int $depth,const string &$s="saved"):string{if($depth>0){return f($depth-1);}return $s;}echo f(3),":",f(0);', 'saved:saved', False),
 'owned-return': ('function f(const string &$s="saved"):string{return $s;}$s=f();$t=f();$t.="!";echo $s,":",$t;', 'saved:saved!', False),
 'callee-failure': ('function f(const string &$s="saved"):void{echo $s;throw Exception::make("X");}try{f();}catch(Exception $e){echo $e->get_message();}finally{echo "F";}', 'savedXF', False),
 'receiver-lifetime': ('class C{function __destruct(){echo "D";}function f(const string &$s="saved"):string{return $s;}}function make():C{return new C();}echo make()->f();', 'Dsaved', False),
 'receiver-failure': ('class C{function __destruct(){echo "D";}function f(const string &$s="saved"):void{throw Exception::make($s);}}function make():C{return new C();}try{make()->f();}catch(Exception $e){echo $e->get_message();}finally{echo "F";}', 'DsavedF', False),
 'argument-failure': ('function fail():int{echo "A";throw Exception::make("X");}function f(int $n,const string &$s="saved"):void{echo "bad";}try{f(fail());}catch(Exception $e){echo $e->get_message();}finally{echo "F";}', 'AXF', False),
})
CASES['borrowed-bool-control'] = ('class C{function f(const bool &$n):int{return $n ? 1 : 0;}}$c=new C();$b=true;echo $c->f($b);', '1', False)
REJECTIONS = {
 'temporary': ('function f(const int &$n=7):int{return $n;}echo f(3);', 'stable storage'),
 'conversion': ('function f(const float &$n=1.0):float{return $n;}$n=1;echo f($n);', 'exact parameter type'),
 'write': ('function f(const int &$n=7):void{$n=9;}f();', 'read-only'),
 'mutable-forward': ('function change(int &$n):void{$n=9;}function f(const int &$n=7):void{change($n);}f();', 'read-only'),
 'reference-return': ('function &f(const int &$n=7):int{return $n;}echo f();', 'const-reference returns'),
 'escaping-capture': ('function f(const int &$n=7):function<int()>{return function() use(&$n):int{return $n;};}$f=f();echo $f();', 'Callable boundary requires an exact parameter and return signature'),
 'wider': ('enum E{case A;}function f(const E &$n=E::A):E{return $n;}echo enum_name(f());', 'canonical scalar'),
 'computed': ('function other():int{return 7;}function f(const int &$n=other()):int{return $n;}echo f();', 'scalar literals'),
 'anonymous': ('$f=function(const int &$n=7):int{return $n;};echo $f();', 'Anonymous reference parameter defaults'),
}
sanitizer = out / 'sanitizer-clang'
sanitizer.write_text('#!/bin/sh\nexec /usr/bin/clang++ -fsanitize=address,undefined -fno-sanitize-recover=undefined -fno-omit-frame-pointer "$@"\n')
sanitizer.chmod(0o755)
with CommandRunner(out / 'logs', ROOT, jobs=12, journals=[out / 'commands.json']) as runner:
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
        runner.run('retained', ['php', APP / 'tests/reference_default_retained.php', out / 'retained'])
        build_run('retained', out / 'retained-published', out / 'retained-cache', 'saved', False)
    def reject(name, source, diagnostic):
        d = out / name
        (d / 'source').mkdir(parents=True)
        (d / 'source/main.phs').write_text(source)
        runner.run(name, ['php', APP / 'main.php', '--s2s', d / 'source', '--output', d / 'published'], expected=1)
        assert diagnostic in (out / 'logs' / (name + '.stderr')).read_text()
    run_tasks([Task(n, lambda n=n, v=v: check(n, *v)) for n, v in CASES.items()]
              + [Task(n, lambda n=n, v=v: reject(n, *v)) for n, v in REJECTIONS.items()]
              + [Task('retained', retained)]
              + [Task(n, lambda n=n: runner.run(n, ['php', APP / 'tests' / (n + '.php')])) for n in ['references', 'closure_model', 'method_parameters']])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'cases': list(CASES), 'retained': True, 'rejections': list(REJECTIONS), 'sanitized': 5, 'cold_warm_programs': len(CASES) + 1}, indent=2) + '\n')
