"""Record pinned v0.1 enum-wrapper behavior without treating rejection as support."""
import argparse, json, sys
from pathlib import Path
APP = Path(__file__).resolve().parents[1]; ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser(description=__doc__)
p.add_argument('--baseline',type=Path,required=True);p.add_argument('--results',type=Path,required=True)
a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
cases={
 'plain': '$out E = E::B; echo enum_name($out);',
 'nullable': '$v nullable<E> = E::A; $out E = E::B; echo take($out,$v)?"yes":"no"; echo enum_name($out); $v=null; echo take($out,$v)?"bad":"empty"; echo enum_name($out);',
 'shorthand': '$v ?E = E::A; $out E = E::B; echo take($out,$v)?"yes":"no"; $v=null; echo take($out,$v)?"bad":"empty";',
 'result': '$v result<E> = E::A; $out E = E::B; $err error; echo take($out,$err,$v)?"yes":"no"; echo enum_name($out); $v=error; echo take($out,$err,$v)?"bad":"error"; echo enum_name($out);',
}
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 cpp=CppFixtureCompiler(runner,a.baseline,out,pch=False,runtime=True)
 cpp.flags.append('-std=c++23')
 def check(name,body):
  source=out/(name+'.phs');source.write_text('enum E:int {case A=0;case B=7;}\n'+body)
  row={'case':name,'stage':'generation','passed':False}
  try:
   generated=runner.run(name+'-generate',['php',APP/'tests/compatibility_export.php',a.baseline,source,'strict'])
   target=out/(name+'.cpp');target.write_bytes(generated);row['stage']='cpp-build'
   cpp.compile(name+'-build',target,out/name);row['stage']='execution'
   result=runner.run(name+'-run',[out/name]);row.update(passed=True,stdout=result.decode())
  except Exception as error:row['failure']=str(error)
  (out/(name+'.json')).write_text(json.dumps(row,indent=2)+'\n')
  return row
 results=run_tasks([Task(n,lambda n=n,b=b:check(n,b)) for n,b in cases.items()])
 rows=[r.value for r in results]
 (out/'summary.json').write_text(json.dumps(rows,indent=2)+'\n')
 expected={'plain':'B','nullable':'yesAemptyA','result':'yesAerrorA'}
 for row in rows:
  if row['case'] in expected:assert row['passed'] and row['stdout']==expected[row['case']],row
  else:assert not row['passed'] and row['stage']=='generation',row
 print(json.dumps(rows,indent=2))
