"""Record runtime prerequisites for V-L09/V-L10 without adapting failed contracts."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1];sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser();p.add_argument('--results',type=Path,required=True);p.add_argument('--require-supported',action='store_true');a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
header='#include "scpp/lang/php.hpp"\n#include "operators/arithmetic/boxed_numeric.hpp"\n#include "operators/coalesce/coalesce.hpp"\n#include <type_traits>\n'
rows=[]
for family,typ in [('mixed','scpp::mixed_t'),('dynamic','scpp::dynamic_t<>')]:
 for name,op in [('and','&'),('or','|'),('xor','^'),('left','<<'),('right','>>'),('not','~')]:
  expr='~a' if name=='not' else 'a '+op+' b'
  expected={'and':2,'or':7,'xor':5,'left':28,'right':1,'not':-8}[name]
  source=header+f'int main(){{using B={typ};B a{{scpp::int_t<>{{7}}}},b{{scpp::int_t<>{{2}}}};static_assert(std::is_same_v<decltype({expr}),B>);auto r={expr};return r.get_int().native_value()=={expected}?0:1;}}\n'
  rows.append((family+'-'+name,source))
for family in ['nullable','result','result_or_false']:
 for payload,value in [('int','scpp::int_t<>{7}'),('float','scpp::float_t{7.0}'),('bool','scpp::bool_t{false}'),('string','scpp::string_t{""}')]:
  typ={'int':'scpp::int_t<>','float':'scpp::float_t','bool':'scpp::bool_t','string':'scpp::string_t'}[payload]
  source=header+f'int main(){{using T={typ};using W=scpp::{family}<T>;W a{{{value}}},b{{{value}}};auto r=scpp::php::coalesce_eval([&](){{return a;}},[&](){{return b;}});static_assert(std::is_same_v<decltype(r),T>);return 0;}}\n'
  rows.append((family+'-'+payload,source))
results={}
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
 def probe(name,source):
  path=out/(name+'.cpp');path.write_text(source)
  try:cpp.compile(name+'-build',path,out/name)
  except RuntimeError:
   results[name]={'stage':'compile-failed','diagnostic':(out/'logs'/(name+'-build.stderr')).read_text()};return
  try:runner.run(name+'-execute',[out/name])
  except RuntimeError:
   results[name]={'stage':'execution-failed'};return
  results[name]={'stage':'passed'}
 run_tasks([Task(n,lambda n=n,s=s:probe(n,s)) for n,s in rows])
(out/'summary.json').write_text(json.dumps({'probe_completed':True,'results':dict(sorted(results.items())),'runtime_changes':False},indent=2)+'\n')
print(json.dumps({stage:sum(r['stage']==stage for r in results.values()) for stage in ['passed','compile-failed','execution-failed']}))

if a.require_supported and any(r["stage"]!="passed" for r in results.values()):raise SystemExit(1)
