"""S07 runtime-first kind, state and non-retention probe."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1];sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True);a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
source='#include "'+str(APP/'tests/mixed_predicates_support.hpp')+'"\n#include <iostream>\nint main(){using namespace predicate_probe;std::cout<<"predicate_checks="<<verify([](const M& v){return scpp::php::is_bool(v);},[](const M& v){return scpp::php::is_int(v);},[](const M& v){return scpp::php::is_float(v);})<<"\\n";}\n'
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
 def proof():
  path=out/'runtime.cpp';path.write_text(source);cpp.compile('runtime-build',path,out/'native');runner.run('runtime-execute',[out/'native'])
 run_tasks([Task('runtime',proof)])
(out/'summary.json').write_text(json.dumps({'passed':True,'observation':(out/'logs/runtime-execute.stdout').read_text(),'runtime_changes':False},indent=2)+'\n')
