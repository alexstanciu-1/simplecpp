"""Focused collection/diagnostic goal probes; run on dev002."""
import argparse,json,sys,shutil
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True);args=p.parse_args()
out=args.results.resolve();out.mkdir(parents=True,exist_ok=False)
metadata=out/'metadata';shutil.copytree(ROOT/'runtime/metadata/modules',metadata)
module={'version':1,'module':'collections_probe','dependencies':['core'],'functions':[],'cpp':[]}
for family in ['mixed','dynamic']:
 ident='collections_probe.'+family
 module['functions'].append({'id':ident,'name':'probe_'+family,'parameters':[],'returns':family})
 module['cpp'].append({'id':ident,'symbol':'collections_probe::'+family+'_input','header':str(APP/'tests/fixtures/collections/provider.hpp')})
module['functions'].append({'id':'collections_probe.dynamic_result','name':'probe_dynamic_result','parameters':[{'name':'value','type':'dynamic','passing':'value'}],'returns':'int'})
module['cpp'].append({'id':'collections_probe.dynamic_result','symbol':'collections_probe::dynamic_result','header':str(APP/'tests/fixtures/collections/provider.hpp')})
(metadata/'collections_probe.json').write_text(json.dumps(module,indent=2)+'\n')
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 def runtime():
  exe=out/'collections';runner.run('compile',['/usr/bin/clang++','-std=c++23','-DSCPP_LANGUAGE_TARGET_PHP=1','-fsanitize=address,undefined','-fno-omit-frame-pointer','-I',ROOT/'runtime/include',APP/'tests/collections_runtime.cpp',ROOT/'runtime/include/core/runtime.cpp','-o',exe],timeout=300);runner.run('execute',[exe])
 def source(name,text,expected):
  folder=out/name;(folder/'source').mkdir(parents=True);(folder/'source/main.phs').write_text(text)
  runner.run(name+'-publish',['php',APP/'tests/collection_publish.php',folder,metadata])
  manifest=json.loads((folder/'published/.scpp-output.manifest').read_text());assert manifest['application_templates']=='forbidden'
  for temperature in ['cold','warm']:
   build=json.loads(runner.run(name+'-'+temperature,['php',APP/'tools/build_cpp.php',folder/'published',folder/'cache'],timeout=300))
   assert runner.run(name+'-'+temperature+'-run',[build['executable']])==expected.encode()
   if temperature=='warm':assert build['compiled']==build['linked']==0
 cases={
  'vector-map':('$v vector<int>=[1,2];$r=sequence_map($v,function(int $x):string{return "x";});echo count($r),":",$r[0],"\\n";','2:x\n'),
  'vector-filter':('$v vector<int>=[1,2,3];$r=collection_filter($v,function(int $x):bool{return $x>1;});echo count($r),":",$r[0],"\\n";','2:2\n'),
  'hash-map':('$v hash<int>={"b":2,"a":1};$r=keyed_map($v,function(int $x):int{return $x+10;});echo $r["b"],":",$r["a"],"\\n";','12:11\n'),
  'mixed-map':('$v=probe_mixed();$r=collection_map($v,function(mixed $x):mixed{return $x;});echo $r["a"],"\\n";','7\n'),
  'dynamic-map':('$v=probe_dynamic();$r=collection_map($v,function(dynamic $x):dynamic{return $x;});echo probe_dynamic_result($r),"\\n";','8\n'),
 }
 cases.update({
  'vector-collection-map':('$v vector<int> = [4,5];$r=collection_map($v,fn(int $x):int=>$x+1);echo $r[0],":",$r[1];','5:6'),
  'vector-sequence-filter':('$v vector<int> = [4,5];$r=sequence_filter($v,fn(int $x):bool=>$x==5);echo count($r),":",$r[0];','1:5'),
  'hash-collection-map':('$v hash<int> = {"b":4,"a":5};$r=collection_map($v,fn(int $x):string=>"mapped");foreach($r as $k=>$x){echo $k,":",$x,";";}','b:mapped;a:mapped;'),
  'hash-collection-filter':('$v hash<int> = {"b":4,"a":5};$r=collection_filter($v,fn(int $x):bool=>$x==5);echo count($r),":",$r["a"];','1:5'),
  'hash-keyed-filter':('$v hash<int> = {"b":4,"a":5};$r=keyed_filter($v,fn(int $x):bool=>true);foreach($r as $k=>$x){echo $k,":",$x,";";}','b:4;a:5;'),
  'mixed-filter':('$v=probe_mixed();$r=collection_filter($v,fn(mixed $x):bool=>true);echo $r["a"];','7'),
  'dynamic-filter':('$v=probe_dynamic();$r=collection_filter($v,fn(dynamic $x):bool=>true);echo probe_dynamic_result($r);','8'),
  'owned-capture':('$n=10;$f function<int(int)> = function(int $x) use ($n):int{return $x+$n;};$n=99;$g=$f;$v vector<int> = [1,2];$r=sequence_map($v,$g);echo $f(3),":",$r[0],":",$r[1];','13:11:12'),
  'callback-copies':('function make(int $n):function<int(int)>{return function(int $x) use ($n):int{return $n+$x;};}$f=make(10);$g=$f;$f=make(20);echo $f(1),":",$g(1);','21:11'),
  'object-capture':('class Box {public int $n=0;}$b=new Box();$f function<int(int)> = function(int $x) use ($b):int{$b->n=$b->n+1;return $x+$b->n;};$v vector<int> = [10,20];$r=collection_map($v,$f);echo $r[0],":",$r[1],":",$b->n;','11:22:2'),
  'source-snapshot':('function later(vector<int> &$v):function<int(int)>{$v[0]=9;return fn(int $x):int=>$x;}$v vector<int> = [1,2];$r=collection_map($v,later($v));echo $r[0],":",$v[0];','1:9'),
  'legal-alias-copy':('function &identity(int &$v):int{return $v;}$v=1;$a =& identity($v);$copy=$v;$a=3;echo $v,":",$a,":",$copy;','3:3:1'),
  'string-input':('$v vector<string> = ["a","b"];$r=sequence_map($v,fn(string $x):string=>$x."!");echo $r[0],$r[1];','a!b!'),
  'nested-input':('$a vector<int> = [3];$v vector<vector<int>> = [$a];$r=sequence_map($v,fn(vector<int> $x):int=>$x[0]);echo $r[0];','3'),
  'object-values':('class Box {public int $n=4;}$b=new Box();$v vector<Box> = [$b];$r=sequence_map($v,fn(Box $x):Box=>$x);$b->n=8;echo $r[0]->n;','8'),
  'empty-and-none':('$v vector<int> = [];$r=sequence_map($v,function(int $x):int{echo "BAD";return $x;});$w vector<int> = [1,2];$z=sequence_filter($w,fn(int $x):bool=>false);echo count($r),":",count($z);','0:0'),
  'callback-order':('$v vector<int> = [3,1,2];$r=sequence_map($v,function(int $x):int{echo $x;return $x+1;});echo ":",$r[0],$r[1],$r[2];','312:423'),
 })
 cases={n:(t.replace('>=','> ='),e) for n,(t,e) in cases.items()}
 run_tasks([Task('runtime',runtime),Task('contracts',lambda:runner.run('contracts',['php',APP/'tests/collection_contracts.php',metadata,out/'contract-source'])),Task('advisories',lambda:runner.run('advisories',['php',APP/'tests/reference_advisories.php',out/'advisory-source']))]+[Task(n,lambda n=n,t=t,e=e:source(n,t,e)) for n,(t,e) in cases.items()])
