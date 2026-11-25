#!/usr/bin/env python3
"""Prove self-contained initializer headers, composition and typed-only adapter isolation."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler

def main():
 p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',required=True,type=Path)
 out=p.parse_args().results.resolve();out.mkdir(parents=True,exist_ok=False)
 headers=['data_initializer_input.hpp','data_initializer_typed.hpp','data_initializer_mixed.hpp','data_initializer_dynamic.hpp','data_initializer.hpp']
 with CommandRunner(out/'logs',APP.parents[1]) as runner:
  cpp=CppFixtureCompiler(runner,APP.parents[1],out,runtime=True,pch=False,cxx='/usr/bin/clang++-18')
  def header_case(tag,items):
   source=out/(tag+'.cpp');source.write_text(''.join('#include "scpp/'+h+'"\n' for h in items)+'int main() {}\n')
   runner.run(tag,[*cpp.flags,'-fsyntax-only',source])
  def lifetime(tag,narrow):
   source=out/(tag+'.cpp');text=(APP/'tests/fixtures/data_initializer_runtime.cpp').read_text()
   if narrow:
    text=text.replace('scpp/data_initializer.hpp','scpp/data_initializer_typed.hpp')
    text+='\ntemplate<class T> concept has_adapter = requires { scpp::data_initializer<T>::from_list({}); };\nstatic_assert(!has_adapter<scpp::mixed_t>);\n'
   source.write_text(text);exe=out/tag;cpp.compile(tag+'-compile',source,exe);runner.run(tag+'-execute',[exe])
  tasks=[Task(h,lambda h=h:header_case(h,[h])) for h in headers]
  tasks += [Task('all-forward',lambda:header_case('all-forward',headers)),Task('all-reverse',lambda:header_case('all-reverse',headers[::-1]))]
  tasks += [Task('umbrella-lifetime',lambda:lifetime('umbrella-lifetime',False)),Task('typed-lifetime',lambda:lifetime('typed-lifetime',True))]
  run_tasks(tasks)
 (out/'summary.json').write_text(json.dumps(dict(passed=True,standalone_headers=5,include_orders=2,lifetime_programs=2,pch=False),indent=2)+'\n')
if __name__=='__main__':main()
