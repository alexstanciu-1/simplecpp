"""Error state observations through the compiled boundary; runtime-first baseline/acceptance."""
import argparse
import json
from pathlib import Path
import sys
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler

CASES = {
 'states': '''$none error;if($none){return 1;}if(!(!$none)){return 2;}
 $none=error::$no_error;if($none){return 3;}
 $e=error::make("");if(!$e){return 4;}$copy=$e;$e=error::$no_error;
 if($e){return 5;}if(!$copy){return 6;}
 $flag=$copy?11:12;if($flag!==11){return 7;}
 $flag=$none?11:12;if($flag!==12){return 8;}echo "ok";''',
 'sequencing': '''function clear(error &$e,int &$n):error{$saved=$e;$e=error::$no_error;$n++;return $saved;}
 function once(int &$n):error{$n++;return error::make("");}
 $n=0;$e=error::make("saved");if(!clear($e,$n)){return 1;}
 if($e){return 2;}if($n!==1){return 3;}if(!once($n)){return 4;}
 if($n!==2){return 5;}$saved=!once($n);if($saved||$n!==3){return 6;}
 $e=error::make("");$turns=0;while($e){$turns++;$e=error::$no_error;}
 if($turns!==1){return 7;}do{$turns++;}while($e);if($turns!==2){return 8;}
 $e=error::make("");for(;$e;){$turns++;$e=error::$no_error;}
 if($turns!==3){return 9;}echo "ok";''',
 'take': '''$r result<int> = error::make("");$e error;$v=9;
 if(take($v,$e)=$r){return 1;}if(!$e){return 2;}if($v!==9){return 3;}
 $saved=$e;$r=0;if(!take($v,$e,$r)){return 4;}
 if($e){return 5;}if(!$saved){return 6;}if($v!==0){return 7;}echo "ok";''',
}
parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results',required=True,type=Path)
parser.add_argument('--baseline',action='store_true')
args=parser.parse_args();out=args.results.resolve();out.mkdir(parents=True,exist_ok=False)
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
    compiler=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
    exe=out/'runtime'
    compiler.compile('runtime-build',APP/'tests/error_condition_runtime.cpp',exe)
    assert runner.run('runtime-run',[exe])==b'runtime-ok'
    def check(name,program):
        folder=out/name;source=folder/'source';source.mkdir(parents=True)
        (source/'main.phs').write_text(program)
        (source/'prism.json').write_text(json.dumps(dict(entrypoint='main.phs',runtime=dict(languages=dict(php=dict(profile='strict')),modules=[]))))
        published=folder/'published'
        try:
            runner.run(name+'-publish',['php',APP/'main.php','--project',source,'--output',published])
        except Exception:
            if args.baseline and 'runtime condition requires concrete transport' in (out/'logs'/f'{name}-publish.stderr').read_text(): return
            raise
        assert not args.baseline,'Baseline unexpectedly published'
        assert json.loads((published/'.scpp-output.manifest').read_text())['application_templates']=='forbidden'
        for phase in ['cold','warm']:
            build=json.loads(runner.run(name+'-'+phase,['php',APP/'tools/build_cpp.php',published,folder/'cache'],timeout=600))
            assert runner.run(name+'-'+phase+'-run',[build['executable']])==b'ok'
            if phase=='warm': assert build['compiled']==0 and not build['linked']
    run_tasks([Task(name,lambda n=name,p=program:check(n,p)) for name,program in CASES.items()])
(out/'summary.json').write_text(json.dumps(dict(passed=True,runtime=True,baseline=args.baseline,cases=list(CASES),default_prelowering=True))+'\n')
