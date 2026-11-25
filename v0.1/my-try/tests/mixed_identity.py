"""Heterogeneous canonical scalar identity: runtime gate, scalar and carrier proofs."""
import argparse,itertools,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--results',required=True,type=Path);parser.add_argument('--runtime-only',action='store_true');parser.add_argument('--group',choices=['mixed-numeric','bool-numeric','string-other'],default='mixed-numeric');args=parser.parse_args();out=args.results.resolve();out.mkdir(parents=True,exist_ok=False)
families=['nullable','result','result_or_false','result_or_bool','plain']
groups={'mixed-numeric':[('int','float'),('float','int')], 'bool-numeric':[('bool','int'),('int','bool'),('bool','float'),('float','bool')], 'string-other':[(a,b) for p in ['int','float','bool'] for a,b in [('string',p),(p,'string')]]}
pairs=groups[args.group]
payloads={'bool':('scpp::bool_t','bool'),'int':('scpp::int_t<>','std::int64_t'),'float':('scpp::float_t','double'),'string':('scpp::string_t','std::string')}
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
 def runtime(p,q):
  v,n=payloads[p];w,m=payloads[q];name=p+'-'+q;source=out/(p+'-'+q+'.cpp');source.write_text('#include "'+str(APP/'tests/cross_identity_support.hpp')+'"\nint main() { long n=0;\n'+''.join(f'n+=identity_probe::runtime_pair<{a},{b},{v},{n},{w},{m}>();\n' for a,b in itertools.product(range(5),repeat=2))+'std::cout<<"runtime_cases="<<n<<"\\n";}\n');cpp.compile(name+'-runtime-build',source,out/name);runner.run(name+'-runtime-execute',[out/name])
 run_tasks([Task(p+'-'+q+'-runtime',lambda p=p,q=q:runtime(p,q)) for p,q in pairs])
 if not args.runtime_only:
  def proof(a,b,p,q):
   name=f'{a}-{b}-{p}-{q}';target=out/name;target.mkdir();runner.run(name+'-prepare',['php',APP/'tests/cross_identity.php',target,a,b,p,q]);s=json.loads((target/'signatures.json').read_text());typ=lambda f,p:p if f=='plain' else '?'+p if f=='nullable' else f+'<'+p+'>';assert len(s)==2 and set(map(tuple,s))=={(typ(a,p),op,typ(b,q),'bool') for op in ['===','!==']};cpp.compile(name+'-build',target/'cross_identity.cpp',target/'native');runner.run(name+'-execute',[target/'native'])
  run_tasks([Task(p+'-'+q+'-scalar',lambda p=p,q=q:proof('plain','plain',p,q)) for p,q in pairs])
  run_tasks([Task(f'{a}-{b}-{p}-{q}',lambda a=a,b=b,p=p,q=q:proof(a,b,p,q)) for a,b in itertools.product(families,repeat=2) if (a,b)!=('plain','plain') for p,q in pairs])
(out/'summary.json').write_text(json.dumps({'passed':True,'group':args.group,'runtime_signatures':50*len(pairs),'scalar_prerequisites':0 if args.runtime_only else 2*len(pairs),'carrier_signatures':0 if args.runtime_only else 48*len(pairs),'full_matrix_run':False},indent=2)+'\n')
