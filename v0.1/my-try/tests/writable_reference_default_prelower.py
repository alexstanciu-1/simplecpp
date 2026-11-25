"""VD07-A01: writable reference defaults, binding and call-local lifetime."""
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
CASES = {}
for form in ['free', 'static', 'method']:
    for scalar, literal, explicit, body, expected in [
        ('int','7','3','++$n;return $n;','8:4:4:8'),
        ('float','1.5','2.5','$n+=1.0;return $n;','2.5:3.5:3.5:2.5'),
        ('bool','true','false','$n=!$n;return $n ? 1 : 0;','0:1:1:0'),
        ('string','"saved"','"live"','$n.="!";return $n;','saved!:live!:live!:saved!')]:
        result = 'int' if scalar == 'bool' else scalar
        function = 'function f('+scalar+' &$n='+literal+'):'+result+'{'+body+'}'
        if form == 'free': source = function; call = 'f'
        else:
            source = 'class C{'+('static ' if form == 'static' else '')+function+'}'
            if form == 'method': source += '$c=new C();'
            call = 'C::f' if form == 'static' else '$c->f'
        observed = '($x ? 1 : 0)' if scalar == 'bool' else '$x'
        source += '$x='+explicit+';echo '+call+'(),":",'+call+'($x),":",'+observed+',":",'+call+'();'
        CASES[form+'-'+scalar]=(source,expected,False)
        # Explicit reference-return controls exercise the same declaration/default.
        ref_function = 'function &f('+scalar+' &$n='+literal+'):'+scalar+'{return $n;}'
        if form == 'free': ref_source = ref_function
        else:
            ref_source = 'class C{'+('static ' if form == 'static' else '')+ref_function+'}'
            if form == 'method': ref_source += '$c=new C();'
        ref_source += '$x='+explicit+';$a =& '+call+'($x);$a='+literal+';echo '+observed+';'
        ref_expected = {'int':'7','float':'1.5','bool':'1','string':'saved'}[scalar]
        CASES[form+'-'+scalar+'-ref-return']=(ref_source,ref_expected,False)
CASES.update({
 'alias-snapshot': ('function next(int &$n):int{++$n;return $n;}function f(int $snapshot,int &$live=7,int $effect=0):void{echo $snapshot,":",$live,":",$effect;++$live;}$n=1;f($n,$n,next($n));echo ":",$n;', '1:2:2:3', False),
 'live-callee-alias': ('function f(int &$a,int &$b=7):void{$a=9;++$b;echo $a,":",$b;}$n=1;f($n,$n);echo ":";f($n);echo ":",$n;', '10:10:9:8:9', False),
 'forward': ('function change(string &$s):void{$s.="!";}function f(string &$s="saved"):string{change($s);return $s;}echo f(),":",f();', 'saved!:saved!', False),
 'nested': ('function f(int $depth,string &$s="saved"):string{$s.="!";if($depth>0){return $s.f($depth-1);}return $s;}echo f(2),":",f(0);', 'saved!saved!saved!:saved!', False),
 'owned-return': ('function f(string &$s="saved"):string{$s.="!";return $s;}$s=f();$t=f();$t.="?";echo $s,":",$t;', 'saved!:saved!?', False),
 'callee-failure': ('function f(string &$s="saved"):void{$s.="!";echo $s;throw Exception::make("X");}try{f();}catch(Exception $e){echo $e->get_message();}finally{echo "F";}', 'saved!XF', False),
 'explicit-failure-writeback': ('function f(string &$s="saved"):void{$s.="!";throw Exception::make("X");}$s="live";try{f($s);}catch(Exception $e){echo $e->get_message();}finally{echo $s;}', 'Xlive!', False),
 'receiver-lifetime': ('class C{function __destruct(){echo "D";}function f(string &$s="saved"):string{$s.="!";return $s;}}function make():C{return new C();}echo make()->f();', 'Dsaved!', False),
 'receiver-failure': ('class C{function __destruct(){echo "D";}function f(string &$s="saved"):void{$s.="!";throw Exception::make($s);}}function make():C{return new C();}try{make()->f();}catch(Exception $e){echo $e->get_message();}finally{echo "F";}', 'Dsaved!F', False),
 'argument-failure': ('function fail():int{echo "A";throw Exception::make("X");}function f(int $n,string &$s="saved"):void{echo "bad";}try{f(fail());}catch(Exception $e){echo $e->get_message();}finally{echo "F";}', 'AXF', False),
 'local-reference-forward': ('function &identity(string &$s):string{return $s;}function f(string &$s="saved"):string{$a =& identity($s);$a.="!";return $s;}echo f();', 'saved!', False),
 'reference-return-other-default': ('function &f(int &$n=7,int $increment=2):int{$n+=$increment;return $n;}$n=3;$a =& f($n);++$a;echo $n;', '6', False),
 'signed-defaults': ('function f(int &$n=-7,float &$x=+2.0):void{++$n;$x+=0.5;echo $n,":",$x;}f();', '-6:2.5', False),
})
REJECTIONS = {
 'temporary': ('function f(int &$n=7):int{return $n;}echo f(3);', 'stable storage'),
 'conversion': ('function f(float &$n=1.0):float{return $n;}$n=1;echo f($n);', 'exact parameter type'),
 'interior': ('function f(int &$n=7):void{++$n;}$v vector<int> = [1];f($v[0]);', 'stable storage'),
 'readonly': ('function change(int &$n=7):void{++$n;}function read(const int &$n):void{change($n);}$n=1;read($n);', 'read-only'),
 'reference-return-copy': ('function &f(int &$n=7):int{return $n;}echo f();', 'Reference return cannot escape omitted default storage'),
 'reference-return-alias': ('function &f(string &$n="saved"):string{return $n;}$a =& f();', 'Reference return cannot escape omitted default storage'),
 'method-reference-return': ('class C{function &f(int &$n=7):int{return $n;}}$c=new C();echo $c->f();', 'Reference return cannot escape omitted default storage'),
 'static-reference-return': ('class C{static function &f(int &$n=7):int{return $n;}}echo C::f();', 'Reference return cannot escape omitted default storage'),
 'escaping-capture': ('function f(int &$n=7):function<int()>{return function() use(&$n):int{return $n;};}$f=f();echo $f();', 'Callable boundary requires an exact parameter and return signature'),
 'wider': ('enum E{case A;}function f(E &$n=E::A):E{return $n;}echo enum_name(f());', 'canonical scalar'),
 'computed': ('function other():int{return 7;}function f(int &$n=other()):int{return $n;}echo f();', 'scalar literals'),
 'anonymous': ('$f=function(int &$n=7):int{return $n;};echo $f();', 'Anonymous reference parameter defaults'),
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
        for form in ['free', 'static', 'method']:
            for scalar, literal, explicit, result, body, omitted, passed in [
                ('int','7','3','int','++$n;return $n;','8:8','4:4'),
                ('float','1.5','2.5','float','$n+=1.0;return $n;','2.5:2.5','3.5:3.5'),
                ('bool','true','false','int','$n=!$n;return $n ? 1 : 0;','0:0','1:1'),
                ('string','"saved"','"live"','string','$n.="!";return $n;','saved!:saved!','live!:live!')]:
                declaration = 'function f('+scalar+' &$n='+literal+'):'+result+'{'+body+'}'
                call = 'f'
                if form != 'free':
                    declaration = 'class C{'+('static ' if form == 'static' else '')+declaration+'}'
                    if form == 'method': declaration += '$c=new C();'
                    call = 'C::f' if form == 'static' else '$c->f'
                for mode in ['explicit', 'omitted']:
                    observed = '($x ? 1 : 0)' if scalar == 'bool' else '$x'
                    suffix = ('$x='+explicit+';echo '+call+'($x),":",'+observed+';') if mode == 'explicit' else ('echo '+call+'(),":",'+call+'();')
                    name = 'v01-'+form+'-'+scalar+'-'+mode
                    source = declaration + suffix
                    expected = passed if mode == 'explicit' else omitted
                    baseline_tasks.append(Task(name, lambda n=name, s=source, e=expected: baseline_probe(n,s,e)))
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
        runner.run('retained', ['php', APP / 'tests/writable_reference_default_retained.php', out / 'retained'])
        build_run('retained', out / 'retained-published', out / 'retained-cache', 'saved!', False)
    def reject(name, source, diagnostic):
        d = out / name
        (d / 'source').mkdir(parents=True)
        (d / 'source/main.phs').write_text(source)
        runner.run(name, ['php', APP / 'main.php', '--s2s', d / 'source', '--output', d / 'published'], expected=1)
        assert diagnostic in (out / 'logs' / (name + '.stderr')).read_text()
    run_tasks(baseline_tasks + [Task(n, lambda n=n, v=v: check(n, *v)) for n, v in CASES.items()]
              + [Task(n, lambda n=n, v=v: reject(n, *v)) for n, v in REJECTIONS.items()]
              + [Task('retained', retained)]
              + [Task(n, lambda n=n: runner.run(n, ['php', APP / 'tests' / (n + '.php')])) for n in ['references', 'closure_model', 'method_parameters']])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'cases': list(CASES), 'retained': True, 'rejections': list(REJECTIONS), 'sanitized': 5, 'cold_warm_programs': len(CASES) + 1}, indent=2) + '\n')
