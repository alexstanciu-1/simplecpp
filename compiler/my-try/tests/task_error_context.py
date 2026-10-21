"""Observe task error contexts without treating existing behavior as the contract."""
import argparse
import json
import sys
from pathlib import Path
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP/'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser(description=__doc__)
p.add_argument('--results',type=Path,required=True)
a=p.parse_args()
out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
 cpp.flags.extend(['-std=c++23','-fsanitize=address,undefined','-fno-sanitize-recover=undefined','-DSCPP_HAS_TASKS=1'])
 cpp.link_flags.append(ROOT/'runtime/include/modules/tasks/tasks.cpp')
 def probe():
  cpp.compile('context-build',APP/'tests/task_error_context_runtime.cpp',out/'context')
  observed=json.loads(runner.run('context-run',[out/'context']))
  assert observed['vector_replacement']==42 and observed['hash_replacement']==43
  expected={'vector_error_on_coordinator':True,'hash_error_on_coordinator':True,'publish_void_handler_calls':1}
  mismatches={k:{'expected':v,'observed':observed[k]} for k,v in expected.items() if observed[k]!=v}
  (out/'observations.json').write_text(json.dumps({'observed':observed,'contract_mismatches':mismatches},indent=2)+'\n')
 run_tasks([Task('error-context-observation',probe)])
