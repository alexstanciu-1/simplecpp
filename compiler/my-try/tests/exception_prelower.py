"""Focused source exception execution with production default prelowering."""
import argparse,json,sys,shutil
from pathlib import Path
APP=Path(__file__).resolve().parents[1]; ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True);p.add_argument('--case',action='append',default=[])
a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
metadata=out/'metadata';shutil.copytree(ROOT/'runtime/metadata/modules',metadata)
(metadata/'exception_probe.json').write_text(json.dumps({'version':1,'module':'exception_probe','dependencies':['core'],'functions':[{'id':'exception_probe.same','name':'probe_exception_same','parameters':[{'name':'left','type':'Exception','passing':'value'},{'name':'right','type':'Exception','passing':'value'}],'returns':'bool'}],'cpp':[{'id':'exception_probe.same','symbol':'exception_probe::same','header':str(APP/'tests/fixtures/exceptions/identity.hpp'),'native_payloads':True}]}))
sanitizer=out/'sanitizer-clang';sanitizer.write_text('#!/bin/sh\nexec /usr/bin/clang++ -fsanitize=address,undefined -fno-sanitize-recover=undefined -fno-omit-frame-pointer "$@"\n');sanitizer.chmod(0o755)
sanitized={'finally-owned-lifetime','finally-discard-owned','finally-cleanup-order','finally-weak','async-nested-transfer','async-handler-finally','exception-identity'}
cases={
 'finally-null-state':('function f():?int{try{return null;}finally{echo "f";}}echo f()??9;',b'f9',None),
 'finally-error-state':('function f():result<int>{try{return error::make("saved");}finally{echo "f";}}$v int=0;$e error;if(take($v,$e,f())){return 2;}echo $e->get_message();',b'fsaved',None),
 'finally-enum':('enum E{case A;case B;}function f():E{try{return E::B;}finally{echo "f";}}if(f()!==E::B){return 2;}echo "enum";',b'fenum',None),
 'finally-callable':('function f():function<int()>{try{return fn():int=>7;}finally{echo "f";}}$f=f();echo $f();',b'f7',None),
 'finally-false-wrapper':('function f(bool $payload):result_or_false<int>{try{if($payload){return 7;}return false_sentinel;}finally{echo "f";}}echo f(true)??0;if(f(false)!==false){return 2;}echo "false";',b'f7ffalse',None),
 'async-value-snapshot':('async function f():vector<int>{$v vector<int> = [7];try{await async_sleep_ms(0);return $v;}finally{await async_sleep_ms(0);$v[0]=9;}}$v=await f();echo $v[0];',b'7',None),
 'exception-identity':('function f(Exception $original):Exception{try{try{throw $original;}finally{echo "f";}}catch(Exception $e){return $e;}}$e=Exception::make("same");$r=f($e);if(!probe_exception_same($e,$r)){return 2;}if(probe_exception_same($e,Exception::make("same"))){return 3;}echo "same";',b'fsame',None),
 'async-nested-transfer':('async function f():int{$n=0;while($n<2){try{try{++$n;await async_sleep_ms(0);continue;}finally{await async_sleep_ms(0);echo "i";}}finally{echo "o";}}return $n;}echo await f();',b'ioio2',None),
 'async-handler-finally':('async function f():int{try{throw Exception::make("x");}catch(Exception $e){await async_sleep_ms(0);echo $e->get_message();return 7;}finally{await async_sleep_ms(0);echo "f";}}echo await f();',b'xf7',None),
 'async-finally':('async function f():int{try{await async_sleep_ms(0);return 7;}finally{echo "f";}}echo await f();',b'f7',None),
 'async-finalizer-await':('async function f():int{try{return 7;}finally{await async_sleep_ms(0);echo "f";}}echo await f();',b'f7',None),
 'async-catch-await':('async function f():int{try{throw Exception::make("x");}catch(Exception $e){await async_sleep_ms(0);echo $e->get_message();return 7;}}echo await f();',b'x7',None),
 'finally-mixed':('function f():mixed{$v mixed=[7];try{return $v;}finally{$v[0]=9;}}$v=f();echo $v[0];',b'7',None),
 'finally-dynamic':('function f():dynamic{$v dynamic=[7];try{return $v;}finally{$v[0]=9;}}$v=f();echo $v[0];',b'9',None),
 'finally-owned-lifetime':('class P{int $x=7;function __destruct(){echo "D";}}function f():unique<P>{$p unique<P> =new P();try{return move($p);}finally{echo "F";if($p){echo "wrong";}}}$p=f();echo $p->x;$p=null;echo "end";',b'F7Dend',None),
 'finally-discard-owned':('class P{function __destruct(){echo "D";}}function f():unique<P>{$p unique<P> =new P();try{return move($p);}finally{echo "F";throw Exception::make("C");}}try{$p=f();}catch(Exception $e){echo $e->get_message();}',b'FDC',None),
 'finally-cleanup-order':('class Log{static int $n=0;}data struct P{int $id;function __destruct(){Log::$n=Log::$n*10+$this->id;}}function f():int{$outer P={id:9};try{$a P={id:1};$b P={id:2};return 7;}finally{echo Log::$n;$c P={id:3};}}echo f(),Log::$n;',b'2172139',None),
 'finally-catch-continue':('$n=0;while($n<2){++$n;try{throw Exception::make("x");}catch(Exception $e){continue;}finally{echo $n;}}',b'12',None),
 'finally-do-continue':('$n=0;do{try{++$n;continue;}finally{echo $n;}}while($n<2);',b'12',None),
 'finally-inner-local-finalizer':('try{echo "a";}finally{while(true){try{break;}finally{echo "i";}}echo "f";}',b'aif',None),
 'finally-handled-in-finalizer':('function f():int{try{return 7;}finally{try{throw Exception::make("x");}catch(Exception $e){echo $e->get_message();}}}echo f();',b'x7',None),
 'finally-evaluation-fails':('function fail():int{throw Exception::make("x");}function f():int{try{return fail();}catch(Exception $e){return 9;}finally{echo "f";}}echo f();',b'f9',None),
 'finally-vector':('function f():vector<int>{$v vector<int> =[7];try{return $v;}finally{$v[0]=9;}}$r=f();echo $r[0];',b'7',None),
 'finally-hash':('function f():hash<int>{$v hash<int> ={a:7};try{return $v;}finally{$v["a"]=9;}}$r=f();echo $r["a"];',b'7',None),
 'finally-nullable':('function f():?int{try{return 7;}finally{echo "f";}}echo f()??0;',b'f7',None),
 'finally-result':('function f():result<int>{try{return 7;}finally{echo "f";}}echo f()??0;',b'f7',None),
 'finally-record':('data struct P{int $x;}function f():P{$p P={x:7};try{return $p;}finally{$p->x=9;}}$p=f();echo $p->x;',b'7',None),
 'finally-shared':('function f():Exception{$e=Exception::make("saved");try{return $e;}finally{$e=null;}}$e=f();echo $e->get_message();',b'saved',None),
 'finally-unique':('class P{int $x=7;}function f():unique<P>{$p unique<P> =new P();try{return move($p);}finally{if($p){echo "wrong";}}}$p=f();echo $p->x;',b'7',None),
 'finally-weak':('class P{int $x=7;}function f(P $p):weak<P>{try{return weak($p);}finally{echo "f";}}$p=new P();$w=f($p);$q=lock($w);echo $q->x;',b'f7',None),
 'finally-break':('$n=0;while(true){try{++$n;break;}finally{echo "f";}echo "wrong";}echo $n;',b'f1',None),
 'finally-continue':('$n=0;for($n=0;$n<3;++$n){try{continue;}finally{echo $n;}echo "wrong";}echo $n;',b'0123',None),
 'finally-nested-break':('while(true){try{try{break;}finally{echo "i";}}finally{echo "o";}}echo "done";',b'iodone',None),
 'finally-local-break':('try{while(true){try{break;}finally{echo "i";}}echo "b";}finally{echo "o";}',b'ibo',None),
 'finally-switch':('for($n=0;$n<2;++$n){switch($n){case 0:try{continue;}finally{echo "c";}break;default:try{break;}finally{echo "b";}}echo "x";}',b'cbx',None),
 'finally-loop-replace':('try{while(true){try{break;}finally{throw Exception::make("new");}}echo "wrong";}catch(Exception $e){echo $e->get_message();}',b'new',None),
 'finally-normal':('try{echo "a";}finally{echo "b";}echo "c";',b'abc',None),
 'finally-return':('function f():int{$n=7;try{return $n;}finally{$n=9;echo "f";}}echo f();',b'f7',None),
 'finally-nested':('function f():string{try{try{return "saved";}finally{echo "i";}}finally{echo "o";}}echo f();',b'iosaved',None),
 'finally-void':('function f():void{try{return;}finally{echo "f";}}f();',b'f',None),
 'finally-entry':('try{return 0;}finally{echo "entry";}',b'entry',None),
 'finally-rethrow':('try{try{throw Exception::make("old");}finally{echo "f";}}catch(Exception $e){echo $e->get_message();}',b'fold',None),
 'finally-replace':('try{try{throw Exception::make("old");}finally{throw Exception::make("new");}}catch(Exception $e){echo $e->get_message();}',b'new',None),
 'finally-replace-return':('function f():int{try{return 7;}finally{throw Exception::make("new");}}try{echo f();}catch(Exception $e){echo $e->get_message();}',b'new',None),
 'finally-guard':('try{try{$z=0;echo 1/$z;}finally{echo "cleanup";}}catch(Exception $e){echo "wrong";}',b'cleanup',"operator '/' rejected a zero divisor"),
 'catch':('try{throw Exception::make("caught");}catch(Exception $e){echo $e->get_message();}',b'caught',None),
 'callee':('function fail():int{throw Exception::make("callee");}try{echo fail();}catch(Exception $e){echo $e->get_message();}',b'callee',None),
 'rethrow':('try{try{throw Exception::make("same");}catch(Exception $e){throw $e;}}catch(Exception $e){echo $e->get_message();}',b'same',None),
 'once':('function make(int &$n):Exception{++$n;return Exception::make("once");}$n=0;try{throw make($n);}catch(Exception $e){echo $n,$e->get_message();}',b'1once',None),
 'absent':('try{$e Exception;throw $e;}catch(Exception $e){echo "wrong";}',b'','Cannot throw an absent Exception'),
 'guard':('try{$z=0;echo 1/$z;}catch(Exception $e){echo "wrong";}',b'',"operator '/' rejected a zero divisor"),
 'unhandled':('throw Exception::make("unhandled");',b'','unhandled'),
}
if a.case:
 assert set(a.case)<=set(cases)
 cases={n:v for n,v in cases.items() if n in a.case}
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 def check(name,source,stdout,error):
  d=out/name;(d/'source').mkdir(parents=True);(d/'source/main.phs').write_text(source)
  runner.run(name+'-publish',['php',APP/'tests/async_tasks_publish.php',d,metadata,'exception_probe','concrete'])
  assert json.loads((d/'published/.scpp-output.manifest').read_text())['application_templates']=='forbidden'
  for temp in ['cold','warm']:
   b=json.loads(runner.run(name+'-'+temp,['php',APP/'tools/build_cpp.php',d/'published',d/'cache'],timeout=300))
   label=name+'-'+temp+'-run'
   assert runner.run(label,[b['executable']],expected=1 if error else 0)==stdout
   if error: assert error in (out/'logs'/(label+'.stderr')).read_text()
   if temp=='warm': assert b['compiled']==b['linked']==0
  if name in sanitized:
   b=json.loads(runner.run(name+'-sanitized',['php',APP/'tests/async_sanitized_build.php',d/'published',d/'sanitized-cache',sanitizer],timeout=300))
   assert runner.run(name+'-sanitized-run',[b['executable']],expected=1 if error else 0)==stdout
 run_tasks([Task('contracts',lambda:runner.run('contracts',['php',APP/'tests/exception_prelower_contracts.php',out/'contracts']))]+[Task(n,lambda n=n,s=s,o=o,e=e:check(n,s,o,e)) for n,(s,o,e) in cases.items()])
(out/'summary.json').write_text(json.dumps({'passed':True,'cases':list(cases),'sanitized':sorted(set(cases)&sanitized)},indent=2)+'\n')
