"""Build one portable x86-64 Linux binary for both machines from the Run 1 compiler."""
import json
import os
import re
import subprocess
import sys
from pathlib import Path

HERE = Path(__file__).resolve().parent
BENCH = HERE.parent
ROOT = BENCH.parents[3]
BUILD = ROOT / 'build/clang'
OUT = Path(sys.argv[1]).resolve()
OUT.mkdir(parents=True, exist_ok=True)
os.environ['XDEBUG_MODE'] = 'off'
for stem in ('stock','work','price'):
    (OUT / (stem+'.php')).write_bytes((BENCH / (stem+'.php')).read_bytes())
launches = []
calls = []
for n in (1, 2, 4, 8):
    length = 65536 // n
    args = [f'reconcile(segment($stock,{i*length},{length}), segment($adj,{i*length},{length}))' for i in range(n)]
    launches.append(f'function batch{n}(vector<Stock> &$stock, vector<int> &$adj): vector<int> {{return mt_join(' + ','.join(args) + ');}')
    calls.append(f'$r=batch{n}($s,$a);')
(OUT/'launch.php').write_text('\n'.join(launches))
(OUT/'entry.php').write_text('$s vector<Stock>=[]; $a vector<int>=[]; '.replace('>=','> =') + ''.join(calls)+'return $r[0];')
bundle = OUT/'bundle'
cmd = ['php',ROOT/'compiler/main.php','program',OUT,'entry.php',BUILD/'native-layout/facts.json',bundle]
for name in ['entry','stock','work','price','launch']:
    cmd += ['php',name+'.php']
subprocess.run(cmd,check=True)
m = json.loads((bundle/'manifest.json').read_text())
headers = [bundle/p for p in m['headers']]
stock = next(p for p in headers if 'struct Stock {' in p.read_text())
vec = next(p for p in headers if 'mt_data()' in p.read_text())
vecname = re.search(r'class (scpp_vector_\w+) final',vec.read_text())[1]
(OUT/'driver.cpp').write_text(f'#include "{stock}"\n#include "{vec}"\nusing Rows = App::{vecname};\n#include "{HERE}/driver.hpp"\n')
fresh = subprocess.check_output(
    ['git', 'show', '462113d00:v0.2/runtime/src/mt/segments.cpp'], cwd=ROOT, text=True)
split = '    std::vector<std::exception_ptr> errors(count);'
runtime = (ROOT / 'runtime/src/mt/segments.cpp').read_text().split(split)[0] + split + fresh.split(split)[1].replace(
        "jobs[i].result = jobs[i].run(jobs[i].inputs[0], jobs[i].inputs[1])", "jobs[i].execute()")
assert runtime.count(split)==1
(OUT/'runtime.cpp').write_text('#include "reuse.hpp"\n'+runtime.replace(split,'    if (bench::selected) { bench::selected->run(jobs, count); return; }\n'+split))
objs=[]
flags=['clang++','-O3','-std=c++23','-stdlib=libc++','-pthread','-I'+str(ROOT/'runtime/generated/public'),'-I'+str(BENCH)]
for unit in [OUT/'driver.cpp', OUT/'runtime.cpp', *[bundle/p for p in m['translation_units'] if p!='entry.cpp']]:
    # Redirect only the compiled kernel definition through the optional timing wrapper.
    extra=['-Dreconcile_=measured_kernel'] if unit==bundle/m['sources']['work.php'] else []
    obj=OUT/(unit.stem+'.o')
    subprocess.run([*flags,*extra,'-c',unit,'-o',obj],check=True)
    objs.append(obj)
subprocess.run([*flags,'-static',*objs,BUILD/'libscpp_runtime.a','-o',OUT/'scaling'],check=True)
