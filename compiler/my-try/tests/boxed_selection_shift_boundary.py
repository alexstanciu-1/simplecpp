"""Record the inherited primitive invalid-shift boundary; do not repair it here."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1];sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser();p.add_argument('--results',type=Path,required=True);a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=False);cpp.flags.extend(['-DSCPP_LANGUAGE_TARGET_PHP=1','-pthread','-fsanitize=undefined','-fno-sanitize-recover=undefined'])
 runner.run('instrumented-runtime-build',[*cpp.flags,'-c',ROOT/'runtime/include/core/runtime.cpp','-o',out/'runtime.o'])
 cpp.link_flags.append(out/'runtime.o')
 def probe(family,typ):
  src=out/(family+'.cpp');src.write_text('#include "scpp/lang/php.hpp"\n#include "operators/arithmetic/boxed_numeric.hpp"\n#include <cstdlib>\nint main(int argc,char**argv){using B='+typ+';B a{scpp::int_t<>{1}},b{scpp::int_t<>{std::strtoll(argv[1],nullptr,10)}};auto c=a<<b;(void)c;return 0;}\n')
  cpp.compile(family+'-build',src,out/family)
  for label,count in [('negative','-1'),('width','64')]:
   runner.run(family+'-'+label,[out/family,count],expected=1)
   text=(out/'logs'/(family+'-'+label+'.stderr')).read_text();assert 'runtime error: shift exponent' in text,text
 run_tasks([Task(f,lambda f=f,t=t:probe(f,t)) for f,t in [('scalar','scpp::int_t<>'),('mixed','scpp::mixed_t'),('dynamic','scpp::dynamic_t<>')]])
(out/'summary.json').write_text(json.dumps({'probe_completed':True,'finding':'Negative and width-or-greater shift counts reach unchecked native integer shifts; UBSan rejects scalar, mixed and dynamic controls.','cases':6,'primitive_policy_changed':False},indent=2)+'\n')
