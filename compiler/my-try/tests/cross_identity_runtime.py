import sys,json,itertools,argparse
from pathlib import Path
root=Path(__file__).resolve().parents[3];sys.path.insert(0,str(root/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
parser=argparse.ArgumentParser(description='Cross-family strict identity runtime prerequisite probe; no compiler admission changes.')
parser.add_argument('--results',type=Path,required=True)
parser.add_argument('--controls',action='store_true')
args=parser.parse_args();out=args.results.resolve();out.mkdir(parents=True,exist_ok=False)
families=['nullable','result','result_or_false','result_or_bool']; payloads={'int':'scpp::int_t<>','float':'scpp::float_t','bool':'scpp::bool_t','string':'scpp::string_t'}
results={}
with CommandRunner(out/'logs',root,jobs=12) as runner:
 def probe(a,b,p,op):
  name=f'{a}-{b}-{p}-{op}';src=out/(name+'.cpp');src.write_text('#include "lang/php/operators/identity/strict_identity.hpp"\nscpp::bool_t probe(const scpp::'+a+'<'+payloads[p]+'>& a,const scpp::'+b+'<'+payloads[p]+'>& b) { return scpp::php::'+op+'(a,b); }\n')
  try:
   runner.run(name,['clang++','-std=c++20','-DSCPP_LANGUAGE_TARGET_PHP=1','-I',root/'runtime/include','-fsyntax-only',src]);results[name]='pass'
  except Exception as e:results[name]='fail'
 run_tasks([Task(f'{a}-{b}-{p}-{op}',lambda a=a,b=b,p=p,op=op:probe(a,b,p,op)) for a,b in ([(f,f) for f in families] if args.controls else itertools.permutations(families,2)) for p in payloads for op in ['identical','not_identical']])
(out/'summary.json').write_text(json.dumps(results,indent=2)+'\n');print('PASS',list(results.values()).count('pass'),'FAIL',list(results.values()).count('fail'))
