"""Strict regex callbacks, snapshots and production linkage."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True);p.add_argument('--baseline',action='store_true');a=p.parse_args()
out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
    run_tasks([Task('prepare',lambda:runner.run('prepare',['php',APP/'tests/regex_callbacks.php',out]+(['--baseline'] if a.baseline else [])))])
    if not a.baseline:
        cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
        cpp.flags.extend(['-I',APP/'tests','-fsanitize=address,undefined','-fno-sanitize-recover=undefined'])
        provider=out/'regex.o'
        runner.run('provider-build',[*cpp.flags,'-c',ROOT/'runtime/include/modules/regex/regex.cpp','-o',provider])
        cpp.link_flags.extend([provider,'-lpcre2-8'])
        def behavior():
            cpp.compile('behavior-build',out/'behavior.cpp',out/'behavior')
            actual=runner.run('behavior-execute',['env','ASAN_OPTIONS=detect_leaks=0',out/'behavior'])
            assert b'failures=0\n' in actual,actual
        source=out/'source';source.mkdir()
        (source/'main.phs').write_text('$f function<string(vector<string>)> = fn($m) => "X"; $h hash<function<string(vector<string>)>> = {"/a/": $f}; $n=1; $a string=regex_replace_callback("/a/",$f,"aba",$n); $b string=regex_replace_callback_array($h,"aba"); echo $a,":",$b,":",$n,"\\n";')
        published=out/'published';cache=out/'cache'
        def production():
            runner.run('publish',['php',APP/'tests/fixtures/runtime_build_publish.php',ROOT/'runtime/metadata/modules',source,published,'regex'],cli_reason='Production module publication')
            for name in ['cold','warm']:
                build=json.loads(runner.run(name+'-build',['php',APP/'tests/fixtures/native_cache_driver.php',published,cache,ROOT/'runtime/include','/usr/bin/clang++'],timeout=300,cli_reason='Production native provider build'))
                actual=runner.run(name+'-execute',[build['executable']]);assert actual==b'Xba:XbX:1\n',actual
                if name=='warm':assert build['compiled']==0 and build['linked']==0,build
                (out/(name+'.json')).write_text(json.dumps(build,indent=2)+'\n')
        run_tasks([Task('behavior',behavior),Task('production',production)])
(out/'summary.json').write_text(json.dumps({'passed':True,'baseline':a.baseline,'checks':json.loads((out/('baseline.json' if a.baseline else 'checks.json')).read_text()),'full_matrix_run':False},indent=2)+'\n')
