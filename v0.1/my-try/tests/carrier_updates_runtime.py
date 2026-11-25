"""V-R10 original native test and bounded carrier mutation participation."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1];sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--results',required=True,type=Path);out=parser.parse_args().results.resolve();out.mkdir(parents=True,exist_ok=False)
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
 def original():
  cpp.compile('original-nullable-build',ROOT/'tests/runtime/native/test_nullable.cpp',out/'nullable')
  runner.run('original-nullable-execute',[out/'nullable'])
 def rejected(carrier,kind,form):
  payload={'bool':'scpp::bool_t','string':'scpp::string_t','fixed':'scpp::int_t<std::int16_t>','nested':'scpp::nullable<scpp::int_t<>>','const':'scpp::int_t<>'}[kind]
  type=('const ' if kind=='const' else '')+'scpp::'+carrier+'<'+payload+'>'
  expr={'preinc':'++x','postinc':'x++','predec':'--x','postdec':'x--'}[form]
  name=carrier+'-'+kind+'-'+form;src=out/(name+'.cpp');src.write_text('#include "scpp/generated/operators.hpp"\nvoid rejected('+type+'& x){(void)('+expr+');}')
  runner.run(name,['clang++','-std=c++20','-I',ROOT/'runtime/include','-fsyntax-only',src],expected=1)
  assert 'cannot increment' in (out/'logs'/(name+'.stderr')).read_text() or 'cannot decrement' in (out/'logs'/(name+'.stderr')).read_text() or 'overload' in (out/'logs'/(name+'.stderr')).read_text()
 run_tasks([Task('original-nullable',original)]+[Task(c+'-'+k+'-'+f,lambda c=c,k=k,f=f:rejected(c,k,f)) for c in ['nullable','result','result_or_false','result_or_bool'] for k in ['bool','string','fixed','nested','const'] for f in ['preinc','postinc','predec','postdec']])
(out/'summary.json').write_text(json.dumps({'passed':True,'original_nullable_test':True,'negative_native_cases':80},indent=2)+'\n')
