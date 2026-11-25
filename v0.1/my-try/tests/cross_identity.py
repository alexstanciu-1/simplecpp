"""Cross-family strict identity: runtime prerequisite and 96 compiler signatures."""
import argparse,itertools,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--results',required=True,type=Path);parser.add_argument('--runtime-only',action='store_true');args=parser.parse_args();out=args.results.resolve();out.mkdir(parents=True,exist_ok=False)
families=['nullable','result','result_or_false','result_or_bool'];payloads={'int':('scpp::int_t<>','std::int64_t'),'float':('scpp::float_t','double'),'bool':('scpp::bool_t','bool'),'string':('scpp::string_t','std::string')}
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
 def runtime(p):
  v,n=payloads[p];source=out/(p+'.cpp');source.write_text('#include "'+str(APP/'tests/cross_identity_support.hpp')+'"\nint main() { long n=0;\n'+''.join(f'n+=identity_probe::runtime_pair<{a},{b},{v},{n}>();\n' for a,b in itertools.product(range(4),repeat=2))+'std::cout<<"runtime_cases="<<n<<"\\n";}\n');cpp.compile(p+'-runtime-build',source,out/p);runner.run(p+'-runtime-execute',[out/p])
 run_tasks([Task(p+'-runtime',lambda p=p:runtime(p)) for p in payloads])
 if not args.runtime_only:
  def proof(a,b,p):
   name=f'{a}-{b}-{p}';target=out/name;target.mkdir();runner.run(name+'-prepare',['php',APP/'tests/cross_identity.php',target,a,b,p]);s=json.loads((target/'signatures.json').read_text());typ=lambda f:'?'+p if f=='nullable' else f+'<'+p+'>';assert set(map(tuple,s))=={(typ(a),op,typ(b),'bool') for op in ['===','!==']};cpp.compile(name+'-build',target/'cross_identity.cpp',target/'native');runner.run(name+'-execute',[target/'native'])
  run_tasks([Task(f'{a}-{b}-{p}',lambda a=a,b=b,p=p:proof(a,b,p)) for a,b in itertools.permutations(families,2) for p in payloads])
(out/'summary.json').write_text(json.dumps({'passed':True,'runtime_cross_signatures':96,'runtime_same_family_controls':32,'compiler_signatures':0 if args.runtime_only else 96,'full_matrix_run':False},indent=2)+'\n')
