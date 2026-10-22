"""Focused source exception execution with production default prelowering."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1]; ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True)
a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
cases={
 'catch':('try{throw Exception::make("caught");}catch(Exception $e){echo $e->get_message();}',b'caught',None),
 'callee':('function fail():int{throw Exception::make("callee");}try{echo fail();}catch(Exception $e){echo $e->get_message();}',b'callee',None),
 'rethrow':('try{try{throw Exception::make("same");}catch(Exception $e){throw $e;}}catch(Exception $e){echo $e->get_message();}',b'same',None),
 'once':('function make(int &$n):Exception{++$n;return Exception::make("once");}$n=0;try{throw make($n);}catch(Exception $e){echo $n,$e->get_message();}',b'1once',None),
 'absent':('try{$e Exception;throw $e;}catch(Exception $e){echo "wrong";}',b'','Cannot throw an absent Exception'),
 'guard':('try{$z=0;echo 1/$z;}catch(Exception $e){echo "wrong";}',b'',"operator '/' rejected a zero divisor"),
 'unhandled':('throw Exception::make("unhandled");',b'','unhandled'),
}
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 def check(name,source,stdout,error):
  d=out/name;(d/'source').mkdir(parents=True);(d/'source/main.phs').write_text(source)
  runner.run(name+'-publish',['php',APP/'tests/async_tasks_publish.php',d,ROOT/'runtime/metadata/modules','core','concrete'])
  assert json.loads((d/'published/.scpp-output.manifest').read_text())['application_templates']=='forbidden'
  for temp in ['cold','warm']:
   b=json.loads(runner.run(name+'-'+temp,['php',APP/'tools/build_cpp.php',d/'published',d/'cache'],timeout=300))
   label=name+'-'+temp+'-run'
   assert runner.run(label,[b['executable']],expected=1 if error else 0)==stdout
   if error: assert error in (out/'logs'/(label+'.stderr')).read_text()
   if temp=='warm': assert b['compiled']==b['linked']==0
 run_tasks([Task('contracts',lambda:runner.run('contracts',['php',APP/'tests/exception_prelower_contracts.php',out/'contracts']))]+[Task(n,lambda n=n,s=s,o=o,e=e:check(n,s,o,e)) for n,(s,o,e) in cases.items()])
(out/'summary.json').write_text(json.dumps({'passed':True,'cases':list(cases)},indent=2)+'\n')
