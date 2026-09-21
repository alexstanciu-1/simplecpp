#!/usr/bin/env python3
"""Focused debug field-access cost; not an application performance forecast."""
import argparse,json,re,shlex,subprocess
from pathlib import Path
from experiment import require_scratch
p=argparse.ArgumentParser();p.add_argument('--app',type=Path,required=True);a=p.parse_args();app=a.app.resolve();require_scratch(app)
build=app/'.prism/build';out=app.parent/'over20-solutions/runtime-cost';out.mkdir(parents=True,exist_ok=True)
cpp=out/'counter_cost.cpp';cpp.write_text('''#include "structure_kernel/frontend_model_kernel_counters.hpp"
#include "__counter/create.hpp"
#include "__counter/update_call_count.hpp"
#include <chrono>
#include <iostream>
#include <string>
using namespace scpp;
int main(int argc, char** argv) {
 const bool boundary = argc > 1 && std::string(argv[1]) == "boundary";
 auto value = __latency_counter_create();
 auto alias = value;
 const auto start = std::chrono::steady_clock::now();
 for (int i=0; i<5000000; ++i) {
  if (boundary) __latency_counter_update_call_count(alias) = __latency_counter_update_call_count(value) + static_cast<int_t<>>(1);
  else alias->update_call_count = value->update_call_count + static_cast<int_t<>>(1);
 }
 const double elapsed=std::chrono::duration<double>(std::chrono::steady_clock::now()-start).count();
 if (value->update_call_count.native_value()!=5000000) return 2;
 std::cout << elapsed << "\\n";
}
''')
graph=(build/'latency-expanded.ninja').read_text();flags=shlex.split(re.search(r'^cxxflags = (.+)$',graph,re.M)[1]);ldflags=shlex.split(re.search(r'^ldflags = (.+)$',graph,re.M)[1]);runtime=next(x for x in next(l for l in graph.splitlines() if l.startswith('build main:')).split() if x.endswith('/libruntime.so'))
exe=out/'counter_cost'
subprocess.run(['clang++',*flags,str(cpp),'expanded/__counter/boundary.o',runtime,*ldflags,'-o',str(exe)],cwd=build,check=True)
rows=[]
for trial in range(1,6):
 for mode in (['direct','boundary'] if trial%2 else ['boundary','direct']):
  r=subprocess.run([str(exe),mode],cwd=build,text=True,capture_output=True,check=True);rows.append({'trial':trial,'mode':mode,'seconds':float(r.stdout),'updates':5000000})
(out/'measurements.json').write_text(json.dumps({'scope':'O0 debug artificial tight loop, same shared object and alias; not whole-application overhead','runs':rows},indent=2)+'\n');print(json.dumps(rows))
