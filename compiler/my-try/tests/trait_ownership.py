"""Bounded trait lifecycle and ownership selection acceptance on dev002."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks

def main():
 p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True);p.add_argument('--baseline',action='store_true');p.add_argument('--traits-only',action='store_true')
 args=p.parse_args();out=args.results.resolve();out.mkdir(parents=True,exist_ok=False)
 cases={
 'unique-control':('class Item {} $a unique<Item> = new Item();$b=move($a);echo ($a ? 1 : 0),":",($b ? 1 : 0),"\\n";','0:1\n',False),
 'trait-control':('trait T {function number():int{return 7;}}class Item {use T;} $a=new Item();echo $a->number(),"\\n";','7\n',False),
 'trait-lifecycle':('trait T {function __construct(){echo "C";}function __destruct(){echo "D";}}class Item {use T;}function test():void {$a=new Item();}test();echo "\\n";','CD\n',True),
 'unique-ternary':('class Item {} $a unique<Item> = new Item();$b unique<Item> = new Item();$c=true?move($a):move($b);echo ($a ? 1 : 0),":",($b ? 1 : 0),":",($c ? 1 : 0),"\\n";','0:1:1\n',True),
 'weak-ternary':('class Item {} $a=new Item();$b=new Item();$wa=weak($a);$wb=weak($b);$c=true?$wa:$wb;$a=null;echo (lock($c) ? 1 : 0),":",(lock($wb) ? 1 : 0),"\\n";','0:1\n',True),
 }
 catalog=json.loads((APP/'tests/trait_lifecycle_cases.json').read_text())
 if not args.traits_only: catalog.update(json.loads((APP/'tests/ownership_selection_cases.json').read_text()))
 if not args.baseline:
  for name,row in catalog.items():
   if row.get('pending'): continue
   cases[name]=(row['source'],row.get('stdout',''),False)
  if args.traits_only: cases={n:r for n,r in cases.items() if not n.startswith(('unique-','weak-'))}
 with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
  def case(name,row):
   source,expected,new=row;negative=catalog.get(name,{}).get('error');folder=out/name;(folder/'source').mkdir(parents=True);(folder/'source/main.phs').write_text(source)
   runner.run(name+'-publish',['php',APP/'tests/verification_host_publish.php',folder],expected=255 if (args.baseline and new) or negative else 0)
   if negative:
    error=(out/'logs'/(name+'-publish.stderr')).read_text();assert negative.lower() in error.lower(),error
    return
   if args.baseline and new:
    error=(out/'logs'/(name+'-publish.stderr')).read_text();assert ('Trait methods require ordinary concrete bodies' if name=='trait-lifecycle' else ('approved consuming' if name=='unique-ternary' else 'Shared selection requires two shared handles')) in error,error
    return
   build=json.loads(runner.run(name+'-build',['php',APP/'tools/build_cpp.php',folder/'published',folder/'cache'],timeout=300))
   assert runner.run(name+'-run',[build['executable']])==expected.encode()
   warm=json.loads(runner.run(name+'-warm',['php',APP/'tools/build_cpp.php',folder/'published',folder/'cache'],timeout=300))
   assert warm['compiled']==warm['linked']==0,warm
  def runtime():
   exe=out/'runtime';runner.run('runtime-compile',['/usr/bin/clang++','-std=c++23','-DSCPP_LANGUAGE_TARGET_PHP=1','-fsanitize=address,undefined','-fno-omit-frame-pointer','-I',ROOT/'runtime/include',APP/'tests/trait_ownership_runtime.cpp',ROOT/'runtime/include/core/runtime.cpp','-o',exe],timeout=300);runner.run('runtime-execute',[exe])
  run_tasks([Task('runtime',runtime),Task('recovery',lambda:runner.run('recovery',['php',APP/'tests/trait_ownership_recovery.php',out/'recovery']))]+[Task(n,lambda n=n:runner.run(n,['php',APP/'tests'/n])) for n in ['traits.php','trait_instances.php','shared_joins.php','shared_upcasts.php']]+[Task(n,lambda n=n,r=r:case(n,r)) for n,r in cases.items()])
 (out/'summary.json').write_text(json.dumps({'passed':True,'baseline':args.baseline,'cases':list(cases)},indent=2)+'\n')
if __name__=='__main__':main()
