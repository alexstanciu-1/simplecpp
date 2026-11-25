#!/usr/bin/env python3
"""Measure native application rebuilds with retained C++ runtime/bridge objects."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import platform
import random
import statistics
import subprocess
import tempfile
import time
from verification import verification_data

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[3]
p = argparse.ArgumentParser()
p.add_argument('--repeats', type=int, default=7)
p.add_argument('--output', type=Path, default=HERE/'selective_results.json')
args = p.parse_args()
assert args.repeats > 0

def run(command, **kwargs):
    start = time.perf_counter()
    result = subprocess.run(list(map(str, command)), capture_output=True, text=True, **kwargs)
    if result.returncode:
        raise RuntimeError(f'{command}\n{result.stdout}\n{result.stderr}')
    return time.perf_counter()-start, result.stdout

def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()

def summary(values):
    return {'median_ms': statistics.median(values)*1000,
            'min_ms': min(values)*1000, 'max_ms': max(values)*1000, 'samples_s': values}

library = ROOT/'examples/accounting_workload/.prism/runtime/project/php-strict/116def5680c631b9/libruntime.so'
assert library.exists(), library
report = {'platform': platform.platform(), 'clang': run(['clang', '--version'])[1],
          'cpu': next(x for x in Path('/proc/cpuinfo').read_text().splitlines() if x.startswith('model name')),
          'load_start': os.getloadavg(), 'repeats': args.repeats,
          'runtime_library': str(library), 'runtime_sha256': digest(library),
          'source_sha256': {f.name: digest(f) for f in HERE.iterdir() if f.suffix in ('.c','.cpp','.h','.hpp','.py')},
          'results': {}}
cases, inputs, expected = verification_data()
with tempfile.TemporaryDirectory(prefix='accounting-handle-') as tmp:
    work = Path(tmp)
    (work/'runtime.hpp').write_text('#include <scpp/lang/php.hpp>\n')
    for opt in ['-O0', '-O2']:
        cpp = ['clang++','-std=c++23','-fPIC','-g','-pipe',opt,
               '-I'+str(ROOT/'runtime/include'), '-DSCPP_LANGUAGE_TARGET_PHP=1',
               '-DSCPP_ENABLE_CALL_DEPTH_GUARD=0']
        c = ['clang','-std=c17','-fPIC','-g','-pipe',opt]
        pch = work/(opt+'.pch')
        setup = {}
        setup['pch_s'] = run(cpp+['-x','c++-header',work/'runtime.hpp','-o',pch])[0]
        with_pch = cpp+['-include-pch',pch]
        # Retained runtime adapter objects; never recompiled inside measured rounds.
        for name, source in [('handle_runtime','handle_runtime.cpp'),('original_bridge','bridge.cpp')]:
            setup[name+'_s'] = run(with_pch+['-c',HERE/source,'-o',work/(name+'.o')])[0]
        run(c+['-c',HERE/'driver.c','-o',work/'driver.o'])
        run(c+['-c',HERE/'handle_checks.c','-o',work/'checks.o'])
        run(cpp+['-c',HERE/'concrete_checks.cpp','-o',work/'concrete_checks.o'])
        run(['clang++',work/'concrete_checks.o',work/'handle_runtime.o',library,
             '-Wl,-rpath,'+str(library.parent),'-o',work/'concrete_checks'])
        run([work/'concrete_checks'])
        retained = [pch, work/'handle_runtime.o', work/'original_bridge.o',work/'driver.o',library]
        before = {str(f): (digest(f),f.stat().st_mtime_ns) for f in retained}
        variants = {
            'C_handles': c+['-c',HERE/'handle_money.c'],
            'CXX_handles_control': cpp+['-x','c++','-c',HERE/'handle_money.c'],
            'CXX_RAII_statements': cpp+['-c',HERE/'concrete_money.cpp'],
            'CXX_RAII_lambdas': cpp+['-DUSE_LAMBDAS=1','-c',HERE/'concrete_money.cpp'],
            'CXX_RAII_exposed_PCH': with_pch+['-DEXPOSE_RUNTIME=1','-c',HERE/'concrete_money.cpp'],
            'generated_CXX_PCH': with_pch+['-c',HERE/'generated_money.cpp'],
        }
        def link_command(name):
            bridge = 'original_bridge' if name == 'generated_CXX_PCH' else 'handle_runtime'
            return ['clang++',work/'driver.o',work/(name+'.o'),work/(bridge+'.o'),
                    library,'-Wl,-rpath,'+str(library.parent),'-o',work/(name+'.exe')]
        samples = {name: {'compile': [], 'link': [], 'compile_and_link': []} for name in variants}
        for name, cmd in variants.items():
            run(cmd+['-o',work/(name+'.o')])
            run(link_command(name))
        rng = random.Random(1802)
        for trial in range(args.repeats):
            names=list(variants); rng.shuffle(names)
            for name in names:
                compile_s=run(variants[name]+['-o',work/(name+'.o')])[0]
                link_s=run(link_command(name))[0]
                samples[name]['compile'].append(compile_s)
                samples[name]['link'].append(link_s)
                samples[name]['compile_and_link'].append(compile_s+link_s)
            print(opt, 'round',trial+1,'complete',flush=True)
        for name in variants:
            assert run([work/(name+'.exe')],input=inputs)[1] == expected, name
        for name in variants:
            if name == 'generated_CXX_PCH': continue
            run(['clang++',work/'checks.o',work/(name+'.o'),work/'handle_runtime.o',library,
                 '-Wl,-rpath,'+str(library.parent),'-o',work/'checks'])
            run([work/'checks'])
        assert all((digest(f),f.stat().st_mtime_ns)==before[str(f)] for f in retained)
        report['results'][opt] = {'setup':setup, 'retained_artifacts_unchanged':True,
            'behavior_cases_per_variant':len(cases),'alias_checks_passed':True,
            'variants':{name:{'timings':{kind:summary(values) for kind,values in groups.items()},
                'compile_command':list(map(str,variants[name])),
                'link_command':list(map(str,link_command(name)))} for name,groups in samples.items()}}
        print(opt,{name:round(summary(groups['compile_and_link'])['median_ms'],2)
                   for name,groups in samples.items()},flush=True)
    report['load_end']=os.getloadavg()
args.output.write_text(json.dumps(report,indent=2)+'\n')
print(args.output)
