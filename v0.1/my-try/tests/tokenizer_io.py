"""Strict tokenizer and IO bindings, originals, snapshots and production builds."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True);p.add_argument('--baseline',action='store_true');p.add_argument('--tokenizer-only',action='store_true');a=p.parse_args()
out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
    mode=['--baseline'] if a.baseline else ['--tokenizer-only'] if a.tokenizer_only else []
    run_tasks([Task('prepare',lambda:runner.run('prepare',['php',APP/'tests/tokenizer_io.php',out]+mode))])
    if not a.baseline:
        checks=json.loads((out/'checks.json').read_text())
        cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
        cpp.flags.extend(['-fsanitize=address,undefined','-fno-sanitize-recover=undefined'])
        def execute(item):
            name=item['name'];work=out/(name+'-cwd');work.mkdir()
            cpp.compile(name+'-build',out/(name+'.cpp'),out/name)
            actual=runner.run(name+'-execute',['env','ASAN_OPTIONS=detect_leaks=0',out/name],cwd=work)
            assert actual==item['stdout'].encode(),(name,actual,item['stdout'])
            assert not list(work.iterdir()),(name,'fixture left filesystem effects')
        def production():
            source=out/'source';source.mkdir()
            program = '$b=phs_tokenize("foo 12");echo token_buffer_count($b),"\\n";' if a.tokenizer_only else '$h resource_handle=io_open("production.bin","wb+");$n int=io_write($h,"alpha");io_close($h);$b=phs_tokenize("foo 12");echo $n,":",token_buffer_count($b),"\\n";fs_remove("production.bin");'
            expected = b'3\n' if a.tokenizer_only else b'5:3\n'
            (source/'main.phs').write_text(program)
            runner.run('publish',['php',APP/'tests/tokenizer_io.php',out,'--publish'],cli_reason='Production module publication')
            work=out/'production-cwd';work.mkdir()
            for name in ['cold','warm']:
                build=json.loads(runner.run(name+'-build',['php',APP/'tests/fixtures/native_cache_driver.php',out/'published',out/'cache',ROOT/'runtime/include','/usr/bin/clang++','--no-pch'],cli_reason='Production native build',timeout=300))
                actual=runner.run(name+'-execute',[build['executable']],cwd=work)
                assert actual==expected,actual
                if name=='warm':assert build['compiled']==0 and build['linked']==0,build
                (out/(name+'.json')).write_text(json.dumps(build,indent=2)+'\n')
        run_tasks([Task(x['name'],lambda x=x:execute(x)) for x in checks['executions']]+[Task('production',production)])
(out/'summary.json').write_text(json.dumps({'passed':True,'baseline':a.baseline,'tokenizer_only':a.tokenizer_only,'checks':json.loads((out/('baseline.json' if a.baseline else 'checks.json')).read_text())},indent=2)+'\n')
