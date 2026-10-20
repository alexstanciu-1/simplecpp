#!/usr/bin/env python3
"""Separate untimed frontend traces and concrete ownership checks."""
import hashlib
import json
from pathlib import Path
import subprocess
import tempfile
HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[3]
def run(cmd):
    subprocess.run(list(map(str,cmd)),check=True,capture_output=True,text=True)
report = {}
with tempfile.TemporaryDirectory(prefix='selective-profile-') as tmp:
    work = Path(tmp)
    cpp = ['clang++','-std=c++23','-fPIC','-g','-pipe','-O0',
           '-I'+str(ROOT/'runtime/include'),'-DSCPP_LANGUAGE_TARGET_PHP=1',
           '-DSCPP_ENABLE_CALL_DEPTH_GUARD=0']
    (work/'runtime.hpp').write_text('#include <scpp/lang/php.hpp>\n')
    pch = work/'runtime.pch'
    run(cpp+['-x','c++-header',work/'runtime.hpp','-o',pch])
    variants = {
        'CXX_RAII_statements': cpp+['-c',HERE/'concrete_money.cpp'],
        'CXX_RAII_lambdas': cpp+['-DUSE_LAMBDAS=1','-c',HERE/'concrete_money.cpp'],
        'CXX_RAII_exposed_PCH': cpp+['-include-pch',pch,'-DEXPOSE_RUNTIME=1','-c',HERE/'concrete_money.cpp'],
        'generated_CXX_PCH': cpp+['-include-pch',pch,'-c',HERE/'generated_money.cpp'],
    }
    for name,cmd in variants.items():
        run(cmd+['-ftime-trace','-o',work/(name+'.o')])
        events=json.loads((work/(name+'.json')).read_text())['traceEvents']
        report[name] = {'totals_ms': {e['name']:e['dur']/1000 for e in events if e['name'].startswith('Total ')},
                        'largest_events':sorted([{'name':e['name'],'ms':e['dur']/1000,'detail':e.get('args',{}).get('detail','')} for e in events if e['name'] in ('InstantiateFunction','InstantiateClass','PerformPendingInstantiations','Frontend','Backend')],key=lambda e:e['ms'],reverse=True)[:20]}
    library=ROOT/'examples/accounting_workload/.prism/runtime/project/php-strict/116def5680c631b9/libruntime.so'
    run(cpp+['-include-pch',pch,'-c',HERE/'handle_runtime.cpp','-o',work/'runtime.o'])
    run(cpp+[HERE/'concrete_checks.cpp',work/'runtime.o',library,'-Wl,-rpath,'+str(library.parent),'-o',work/'checks'])
    run([work/'checks'])
report['concrete_copy_move_unwind_checks_passed']=True
(HERE/'selective_profile.json').write_text(json.dumps(report,indent=2)+'\n')
print('Traces and concrete ownership checks complete')
