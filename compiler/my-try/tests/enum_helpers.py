"""Concrete enum helper and presence proofs; default prelowering, no runtime changes."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
CASES={}
EXPECTED={}
for name,backing,a,b in [('unit','', '0','1'),('plain',':int','-2','7'),
 ('i8',':int8','-128','127'),('u8',':uint8','0','255'),
 ('i16',':int16','-32768','32767'),('u16',':uint16','0','65535'),
 ('i32',':int32','-2147483648','2147483647'),('u32',':uint32','0','4294967295'),
 ('i64',':int64','-9223372036854775808','9223372036854775807'),
 ('u64',':uint64','0','18446744073709551615')]:
 decl='enum E'+backing+'{case lower'+('='+a if backing else '')+';case Upper'+('='+b if backing else '')+';} '
 result=backing[1:] if backing else 'int'
 CASES[name]=decl+f'function exact({result} &$v):void{{}}$a=enum_value(E::lower);$b=enum_value(E::Upper);exact($a);exact($b);echo $a,",",$b,";";if(enum_name(E::lower)!=="lower" || enum_name(E::Upper)!=="Upper"){{return 3;}}if(enum_from_value(E::class,$a)!==E::lower || enum_from_value(E::class,$b)!==E::Upper){{return 4;}}$n ?E=null;$r result<E> =error::make("bad");if(isset($n)||isset($r)){{return 5;}}$n=E::lower;$r=E::lower;if(!isset($n)||!isset($r)){{return 6;}}$out E=E::Upper;$err error;if(!take($out,$err,$r)){{return 7;}}if(!isset($r)){{return 8;}}echo "AA";'
 EXPECTED[name]=f'{a},{b};AA'.encode()
BASE='enum E:int{case A=0;case B=7;} '
CASES.update({
 'once':BASE+'function value(int &$n):E{++$n;return E::A;}function number(int &$n):int{++$n;return 7;}function wrap(int &$n):?E{++$n;return E::A;}function failed(int &$n):result<E>{++$n;return error::make("bad");}$n=0;if(enum_name(value($n))!=="A"){return 2;}if(enum_value(value($n))!==0){return 3;}if(enum_from_value(E::class,number($n))!==E::B){return 4;}if(!isset(wrap($n))||isset(failed($n))){return 5;}if($n!==5){return 6;}echo "AA";',
 'snapshots':BASE+'function change(E &$e,?E &$n,result<E> &$r):int{$e=E::B;$n=null;$r=error::make("bad");return 1;}function read(string $name,int $value,E $decoded,bool $np,bool $rp,int $effect):void{if($name!=="A"||$value!==0||$decoded!==E::A||!$np||!$rp){throw Exception::make("snapshot");}}$e=E::A;$n ?E=E::A;$r result<E> =E::A;read(enum_name($e),enum_value($e),enum_from_value(E::class,enum_value($e)),isset($n),isset($r),change($e,$n,$r));if(isset($n)||isset($r)||$e!==E::B){return 2;}echo "AA";',
 'shadow':'function enum_name(int $v):string{return "AA";}echo enum_name(3);',
 'import-shadow':'namespace Lib;function label(int $v):string{return "AA";}namespace App;use function Lib\\label as enum_name;echo enum_name(3);',
 'cross-width':'enum E:uint8{case A=10;case B=255;}$signed int16=255;$unsigned uint64=10;if(enum_from_value(E::class,$signed)!==E::B||enum_from_value(E::class,$unsigned)!==E::A){return 2;}echo "AA";',
})
FAILURES={
 'overflow':'enum E:uint8{case A=10;}$e=enum_from_value(E::class,266);echo "wrong";',
 'negative':'enum E:uint8{case A=255;}$e=enum_from_value(E::class,-1);echo "wrong";',
 'unsigned':'enum E:int8{case A=-1;}enum U:uint64{case Max=18446744073709551615;}$v=enum_value(U::Max);$e=enum_from_value(E::class,$v);echo "wrong";',
 'unmatched':BASE+'$e=enum_from_value(E::class,1);echo "wrong";',
}
NEGATIVE={
 'name-type':'echo enum_name(1);',
 'name-arity':BASE+'echo enum_name(E::A,E::B);',
 'value-type':'echo enum_value("A");',
 'value-arity':'echo enum_value();',
 'from-marker':BASE+'$v=enum_from_value(E::A,0);',
 'from-arity':BASE+'$v=enum_from_value(E::class);',
 'from-float':BASE+'$v=enum_from_value(E::class,0.0);',
 'from-bool':BASE+'$v=enum_from_value(E::class,false);',
 'from-string':BASE+'$v=enum_from_value(E::class,"0");',
 'wrong-enum':BASE+'enum F{case A;}$v F=enum_from_value(E::class,0);',
 'plain-presence':BASE+'echo isset(E::A);',
 'empty-wrapper':BASE+'$v ?E=E::A;echo empty($v);',
 'enum-condition':BASE+'$v ?E=E::A;if($v){echo "wrong";}',
}
DEFERRED={'boxed-empty':'$v dynamic;echo empty($v);'}
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
    assert 'Invalid value for enum helper' in (out/'logs'/(name+'-'+mode+'-run.stderr')).read_text()
   else:assert actual==EXPECTED.get(name,b'AA'),actual
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
 runner.run('retained',['php',APP/'tests/enum_helper_retained.php',out/'retained'])
 b=json.loads(runner.run('retained-build',['php',APP/'tools/build_cpp.php',out/'retained-published',out/'retained-cache'],timeout=600))
 assert runner.run('retained-run',[b['executable']])==b'AA'
 suites=['enums','value_probes']
 def suite(name):
  command=['php',APP/'tests'/(name+'.php')]
  if name=='enums':
   destination=out/'ordinary-enums';destination.mkdir();command.append(destination)
  runner.run(name,command)
 run_tasks([Task(n,lambda n=n:suite(n)) for n in suites])
 (out/'summary.json').write_text(json.dumps(dict(positive=len(CASES),runtime_failures=len(FAILURES),rejections=len(NEGATIVE),deferred=len(DEFERRED),retained=True,passed=True),indent=2)+'\n')
