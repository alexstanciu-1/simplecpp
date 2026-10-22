"""Bounded nullable/Result enum source proofs with default prelowering."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
BASE='enum E:int{case A=0;case B=7;} '
CASES={
 'nullable':BASE+'$v nullable<E> =E::A;$out E=E::B;if(!take($out,$v)){return 2;}echo $out===E::A?"A":"bad";$v=null;if(take($out,$v)){return 3;}echo $out===E::A?"A":"bad";',
 'shorthand':BASE+'function f():?E{return E::A;}$v nullable<E> =f();$out E=E::B;take($out)=$v;echo $out===E::A?"A":"bad";$v=null;take($out)=$v;echo $out===E::A?"A":"bad";',
 'result':BASE+'$v result<E> =E::A;$out E=E::B;$err error;if(!take($out,$err,$v)){return 2;}echo $out===E::A?"A":"bad";$v=error::make("failed");if(take($out,$err,$v)){return 3;}echo $out===E::A?"A":"bad";',
 'coalesce':BASE+'$v ?E=null;$r result<E> =error::make("failed");echo ($v??E::A)===E::A?"A":"bad";echo ($r??E::A)===E::A?"A":"bad";',
}
# Backing representation must not alter state or nominal identity.
for name,decl in {
 'unit':'enum E{case A;case B;} ',
 'signed8':'enum E:int8{case A=-128;case B=127;} ',
 'unsigned64':'enum E:uint64{case A=18446744073709551615;case B=0;} ',
}.items():
 for form in ('nullable','result'):
  CASES[name+'-'+form]=CASES[form].replace(BASE,decl)
CASES.update({
 'snapshot-result':BASE+'function change(result<E> &$v):int{$v=E::B;return 1;}function read(result<E> $v,int $effect):E{$out E=E::B;$err error;take($out,$err)=$v;return $out;}$v result<E> =E::A;echo read($v,change($v))===E::A?"A":"bad";echo ($v??E::A)===E::B?"A":"bad";',
 'snapshot-nullable':BASE+'function change(?E &$v):int{$v=E::B;return 1;}function read(nullable<E> $v,int $effect):E{$out E=E::B;take($out)=$v;return $out;}$v ?E=E::A;echo read($v,change($v))===E::A?"A":"bad";echo ($v??E::A)===E::B?"A":"bad";',
 'lazy':BASE+'function fallback(int &$n):E{++$n;return E::B;}$n=0;$v ?E=E::A;$r result<E> =E::A;$a=$v??fallback($n);$b=$r??fallback($n);if($n!==0){return 2;}$v=null;$r=error::make("failed");$a=$v??fallback($n);$b=$r??fallback($n);if($n!==2){return 3;}echo $a===E::B?"A":"bad";echo $b===E::B?"A":"bad";',
 'once-error-state':BASE+'function source(int &$n):result<E>{++$n;return E::A;}$n=0;$out E=E::B;$err=error::make("old");if(!take($out,$err,source($n))){return 2;}if($n!==1){return 3;}if($err->get_message()!==""){return 4;}$r result<E> =error::make("failed");if(take($out,$err,$r)){return 5;}if($err->get_message()!=="failed"){return 6;}echo $out===E::A?"A":"bad";$r=E::B;take($out,$err)=$r;if($err->get_message()!==""){return 7;}echo $out===E::B?"A":"bad";',
 'presence':BASE+'$v ?E;if(isset($v)){return 2;}$v=E::A;if(!isset($v)){return 3;}$r result<E> =E::A;if(!isset($r)){return 4;}$r=error::make("failed");if(isset($r)){return 5;}echo "AA";',
 'extract':BASE+'$v ?E=E::A;$r result<E> =E::B;$a E=$v;$b E=$r;echo $a===E::A?"A":"bad";echo $b===E::B?"A":"bad";',
})
DEFERRED={
 'presence':CASES.pop('presence'),
 'enum-helper':BASE+'echo enum_name(E::A);',
}
CASES['default-empty']=BASE+'$v ?E;$out E=E::B;if(take($out,$v)){return 2;}echo $out===E::B?"A":"bad";$v=E::A;if(!take($out,$v)){return 3;}echo $out===E::A?"A":"bad";'
FAILURES={
 'empty-extract':BASE+'$v ?E=null;$out E=$v;echo "wrong";',
 'error-extract':BASE+'$v result<E> =error::make("failed");$out E=$v;echo "wrong";',
}
NEGATIVE={
 'nullable-int':BASE+'$v ?E=0;',
 'result-int':BASE+'$v result<E> =0;',
 'wrong-enum':BASE+'enum F{case A;}$v result<E> =F::A;',
 'wrong-take':BASE+'$v ?E=E::A;$out int=0;take($out)=$v;',
 'wrong-fallback':BASE+'$v ?E=null;echo $v??0;',
 'result-default':BASE+'$v result<E>;',
 'result-field-default':'enum E:int{case A=7;}class H{result<E> $v;}$h=new H();',
 'nullable-condition':BASE+'$v ?E=E::A;if($v){echo "wrong";}',
 'result-condition':BASE+'$v result<E> =E::A;if($v){echo "wrong";}',
 'false-carrier':BASE+'$v result_or_false<E> =E::A;',
 'bool-carrier':BASE+'$v result_or_bool<E> =E::A;',
 'lifted-equality':BASE+'$v ?E=E::A;echo $v===E::A;',
}
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True)
a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
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
 runner.run('retained',['php',APP/'tests/enum_wrapper_retained.php',out/'retained'])
 b=json.loads(runner.run('retained-build',['php',APP/'tools/build_cpp.php',out/'retained-published',out/'retained-cache'],timeout=600))
 assert runner.run('retained-run',[b['executable']])==b'AA'
 suites=['results','take','nullable','enum_model','type_capabilities','result_containers']
 def suite(name):
  command=['php',APP/'tests'/(name+'.php')]
  if name=='result_containers':
   destination=out/'result-containers';destination.mkdir();command.append(destination)
  runner.run(name,command)
 run_tasks([Task(n,lambda n=n:suite(n)) for n in suites])
 (out/'summary.json').write_text(json.dumps(dict(positive=len(CASES),runtime_failures=len(FAILURES),rejections=len(NEGATIVE),deferred=len(DEFERRED),retained=True,passed=True),indent=2)+'\n')
