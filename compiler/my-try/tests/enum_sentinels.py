"""Sentinel Result enum states, presence, snapshots and exact payload boundaries."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
CASES={}
for name,backing,a,b in [('unit','', '0','1'),('plain',':int','0','7'),
 ('i8',':int8','-128','127'),('u8',':uint8','0','255'),
 ('i16',':int16','-32768','32767'),('u16',':uint16','0','65535'),
 ('i32',':int32','-2147483648','2147483647'),('u32',':uint32','0','4294967295'),
 ('i64',':int64','-9223372036854775808','9223372036854775807'),
 ('u64',':uint64','0','18446744073709551615')]:
 decl='enum E'+backing+'{case A'+('='+a if backing else '')+';case B'+('='+b if backing else '')+';} '
 CASES[name+'-false']=decl+'$v result_or_false<E> =E::A;$copy=$v;$out E=E::B;if(!isset($v)||!take($out,$v)||$out!==E::A){return 2;}$v=false_sentinel;$out=E::B;if(isset($v)||take($out,$v)||$out!==E::B){return 3;}if(!isset($copy)){return 4;}take($out)=$copy;if($out!==E::A){return 5;}$v=E::B;$out=E::A;take($out)=$v;if($out!==E::B||!isset($v)){return 6;}$v=null;if(isset($v)){return 7;}$v=false_sentinel;if(isset($v)){return 8;}echo "AA";'
 CASES[name+'-bool']=decl+'$v result_or_bool<E> =E::A;$copy=$v;$out E=E::B;$flag=false;if(!isset($v)||!take($out,$flag,$v)||$out!==E::A||$flag){return 2;}$v=true_sentinel;$saved_true=$v;$out=E::B;if(!isset($v)||!take($out,$flag,$v)||$out!==E::B||!$flag){return 3;}$v=false_sentinel;$saved_false=$v;if(isset($v)||take($out,$flag,$v)||$out!==E::B||$flag){return 4;}$v=E::B;$flag=true;take($out,$flag)=$copy;if($out!==E::A||!$flag){return 5;}take($out,$flag)=$saved_true;if($out!==E::A||!$flag||!isset($saved_true)){return 6;}take($out,$flag)=$saved_false;if($out!==E::A||$flag||isset($saved_false)){return 7;}$v=true_sentinel;if(!isset($v)){return 8;}$v=false_sentinel;if(isset($v)){return 9;}$v=null;if(isset($v)){return 10;}echo "AA";'
BASE='enum E:int{case A=0;case B=7;} '
CASES.update({
 'lazy':BASE+'function fallback(int &$n):E{++$n;return E::B;}$n=0;$v result_or_false<E> =E::A;$a=$v??fallback($n);if($a!==E::A||$n!==0){return 2;}$v=false_sentinel;$a=$v??fallback($n);if($a!==E::B||$n!==1){return 3;}echo "AA";',
 'once':BASE+'function source(int &$n,E &$out):result_or_bool<E>{++$n;$out=E::B;return E::A;}function sentinel(int &$n):result_or_bool<E>{++$n;return true_sentinel;}$n=0;$out E=E::A;$flag=false;if(!take($out,$flag,source($n,$out))||$out!==E::A||$flag||$n!==1){return 2;}if(!isset(sentinel($n))||$n!==2){return 3;}if(!take($out,$flag,sentinel($n))||!$flag||$out!==E::A||$n!==3){return 4;}echo "AA";',
 'snapshots':BASE+'function change(result_or_false<E> &$f,result_or_bool<E> &$b):int{$f=false_sentinel;$b=false_sentinel;return 1;}function read(result_or_false<E> $f,result_or_bool<E> $b,int $effect):void{$out E=E::B;$flag=false;if(!take($out,$f)||$out!==E::A){throw Exception::make("false copy");}$out=E::B;if(!isset($b)||!take($out,$flag,$b)||!$flag||$out!==E::B){throw Exception::make("true copy");}}$f result_or_false<E> =E::A;$b result_or_bool<E> =true_sentinel;read($f,$b,change($f,$b));if(isset($f)||isset($b)){return 2;}echo "AA";',
 'required':BASE+'function unwrap(result_or_false<E> $v):E{return $v;}function consume(E $v):E{return $v;}$f result_or_false<E> =E::A;$b result_or_bool<E> =E::B;$out E=$b;if(unwrap($f)!==E::A||consume($b)!==E::B||$out!==E::B){return 2;}echo "AA";',
})
FAILURES={
 'false-extract':BASE+'$v result_or_false<E> =false_sentinel;$out E=$v;echo "wrong";',
 'bool-false-extract':BASE+'$v result_or_bool<E> =false_sentinel;$out E=$v;echo "wrong";',
 'bool-true-extract':BASE+'$v result_or_bool<E> =true_sentinel;if(!isset($v)){return 2;}$out E=$v;echo "wrong";',
}
NEGATIVE={
 'false-bool-literal':BASE+'$v result_or_false<E> =false;',
 'bool-bool-literal':BASE+'$v result_or_bool<E> =true;',
 'false-int':BASE+'$v result_or_false<E> =0;',
 'bool-int':BASE+'$v result_or_bool<E> =0;',
 'other-enum':BASE+'enum F{case A;}$v result_or_bool<E> =F::A;',
 'false-true':BASE+'$v result_or_false<E> =true_sentinel;',
 'bool-variable':BASE+'$b=true;$v result_or_bool<E> =$b;',
 'cross-family':BASE+'$f result_or_false<E> =E::A;$b result_or_bool<E> =$f;',
 'false-error':BASE+'$v result_or_false<E> =error::make("bad");',
 'bool-error':BASE+'$v result_or_bool<E> =error::make("bad");',
 'wrong-out':BASE+'$v result_or_false<E> =E::A;$out=0;take($out)=$v;',
 'missing-bool-out':BASE+'$v result_or_bool<E> =true_sentinel;$out E=E::A;take($out)=$v;',
 'wrong-bool-out':BASE+'$v result_or_bool<E> =true_sentinel;$out E=E::A;$flag=0;take($out,$flag)=$v;',
 'false-default':BASE+'$v result_or_false<E>;',
 'bool-default':BASE+'$v result_or_bool<E>;',
 'false-condition':BASE+'$v result_or_false<E> =E::A;if($v){return 1;}',
 'bool-condition':BASE+'$v result_or_bool<E> =true_sentinel;if($v){return 1;}',
 'false-equality':BASE+'$v result_or_false<E> =E::A;echo $v===E::A;',
 'bool-equality':BASE+'$v result_or_bool<E> =E::A;echo $v===E::A;',
 'bool-coalesce':BASE+'$v result_or_bool<E> =true_sentinel;$out=$v??E::A;',
 'false-wrapper-fallback':BASE+'$v result_or_false<E> =false_sentinel;$out=$v??$v;',
 'false-integer-fallback':BASE+'$v result_or_false<E> =false_sentinel;$out=$v??0;',
 'empty':BASE+'$v result_or_bool<E> =true_sentinel;echo empty($v);',
}
DEFERRED={}
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True)
a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
with CommandRunner(out/'logs',ROOT,jobs=12,journals=[out/'commands.json']) as runner:
 def check(name,source,failure=False):
  d=out/name;(d/'source').mkdir(parents=True);(d/'source/main.phs').write_text(source)
  runner.run(name+'-publish',['php',APP/'main.php','--s2s',d/'source','--output',d/'published'])
  assert json.loads((d/'published/.scpp-output.manifest').read_text())['application_templates']=='forbidden'
  for mode in ['cold','warm']:
   b=json.loads(runner.run(name+'-'+mode,['php',APP/'tools/build_cpp.php',d/'published',d/'cache'],timeout=600))
   if mode=='warm':assert b['compiled']==0 and not b['linked'],b
   actual=runner.run(name+'-'+mode+'-run',[b['executable']],expected=1 if failure else 0)
   if failure:
    assert b'wrong' not in actual
    assert 'requires a present' in (out/'logs'/(name+'-'+mode+'-run.stderr')).read_text()
   else:assert actual==b'AA',actual
 run_tasks([Task(n,lambda n=n,s=s:check(n,s)) for n,s in CASES.items()]+[
  Task(n,lambda n=n,s=s:check(n,s,True)) for n,s in FAILURES.items()])
 def reject(name,source):
  d=out/name;(d/'source').mkdir(parents=True);(d/'source/main.phs').write_text(source)
  runner.run(name+'-reject',['php',APP/'main.php','--s2s',d/'source','--output',d/'published'],expected=1)
  diagnostic=(out/'logs'/(name+'-reject.stderr')).read_text()
  assert 'S2S generation failed:' in diagnostic and 'prelower_templates:' not in diagnostic,diagnostic
 run_tasks([Task(n,lambda n=n,s=s:reject(n,s)) for n,s in NEGATIVE.items()])
 def deferred(name,source):
  d=out/name;(d/'source').mkdir(parents=True);(d/'source/main.phs').write_text(source)
  runner.run(name+'-deferred',['php',APP/'main.php','--s2s',d/'source','--output',d/'published'],expected=1)
  assert 'requires concrete transport' in (out/'logs'/(name+'-deferred.stderr')).read_text()
 run_tasks([Task(n,lambda n=n,s=s:deferred(n,s)) for n,s in DEFERRED.items()])
 runner.run('retained',['php',APP/'tests/enum_sentinel_retained.php',out/'retained'])
 b=json.loads(runner.run('retained-build',['php',APP/'tools/build_cpp.php',out/'retained-published',out/'retained-cache'],timeout=600))
 assert runner.run('retained-run',[b['executable']])==b'AA'
 suites=['results','take','nullable','value_probes']
 def suite(name):
  command=['php',APP/'tests'/(name+'.php')]
  if name=='enums':
   destination=out/'ordinary-enums';destination.mkdir();command.append(destination)
  runner.run(name,command)
 run_tasks([Task(n,lambda n=n:suite(n)) for n in suites])
 (out/'summary.json').write_text(json.dumps(dict(positive=len(CASES),runtime_failures=len(FAILURES),rejections=len(NEGATIVE),deferred=len(DEFERRED),retained=True,passed=True),indent=2)+'\n')
