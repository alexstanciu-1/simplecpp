#!/usr/bin/env python3
"""Isolated accounting compile experiment; never changes application build state."""
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
parser = argparse.ArgumentParser()
parser.add_argument('--repeats', type=int, default=7)
parser.add_argument('--output', type=Path, default=HERE / 'results.json')
args = parser.parse_args()
assert args.repeats > 0

def run(cmd, **kwargs):
    start = time.perf_counter()
    proc = subprocess.run(list(map(str, cmd)), capture_output=True, text=True, **kwargs)
    if proc.returncode:
        raise RuntimeError(f'{cmd}\n{proc.stderr}')
    return time.perf_counter() - start, proc.stdout

report = {'platform': platform.platform(), 'clang': run(['clang', '--version'])[1],
          'cpu': next(x for x in Path('/proc/cpuinfo').read_text().splitlines() if x.startswith('model name')),
          'load_start': os.getloadavg(),
          'repeats': args.repeats, 'results': {}, 'pch_seconds': {}, 'trace_seconds': {},
          'source_sha256': {p.name: hashlib.sha256(p.read_bytes()).hexdigest() for p in HERE.iterdir() if p.suffix in ('.c', '.cpp', '.h', '.hpp')}}
common = ['-fPIC', '-g', '-pipe']
runtime = ['-I' + str(ROOT / 'runtime/include'), '-DSCPP_LANGUAGE_TARGET_PHP=1',
           '-DSCPP_ENABLE_CALL_DEPTH_GUARD=0']
# PCH contains only runtime, never application declarations.
with tempfile.TemporaryDirectory(prefix='accounting-c-cpp-') as tmp:
    work = Path(tmp)
    (work/'runtime.hpp').write_text('#include <scpp/lang/php.hpp>\n')
    for opt in ['-O0', '-O2']:
        flags = common + [opt]
        cpp = ['clang++', '-std=c++23'] + flags + runtime
        pch = work / (opt + '.pch')
        report['pch_seconds'][opt] = run(cpp + ['-x', 'c++-header', work/'runtime.hpp', '-o', pch])[0]
        variants = {
            'C': ['clang', '-std=c17'] + flags + ['-c', HERE/'money.c'],
            'lean_CXX': ['clang++', '-std=c++23'] + flags + ['-x', 'c++', '-c', HERE/'money.c'],
            'generated_CXX': cpp + ['-c', HERE/'generated_money.cpp'],
            'generated_CXX_PCH': cpp + ['-include-pch', pch, '-c', HERE/'generated_money.cpp'],
            'bridge_PCH': cpp + ['-include-pch', pch, '-c', HERE/'bridge.cpp'],
        }
        samples = {name: [] for name in variants}
        # Warm filesystem/tool paths; no object cache. Each compile overwrites its object.
        for name, cmd in variants.items():
            run(cmd + ['-o', work/(name+'.o')])
        rng = random.Random(18)
        for trial in range(args.repeats):
            names = list(variants)
            rng.shuffle(names)
            for name in names:
                elapsed, _ = run(variants[name] + ['-o', work/(name+'.o')])
                samples[name].append(elapsed)
        report['results'][opt] = {name: {'median_ms': statistics.median(values)*1000,
            'min_ms': min(values)*1000, 'max_ms': max(values)*1000, 'samples_s': values,
            'command': list(map(str, variants[name]))} for name, values in samples.items()}
        # Separate instrumented runs; aggregate categories overlap and must not be summed.
        report['trace_seconds'][opt] = {}
        for name, cmd in variants.items():
            trace = work/(name+'.json')
            run(cmd + ['-ftime-trace='+str(trace), '-o', work/(name+'.o')])
            events = json.loads(trace.read_text())['traceEvents']
            report['trace_seconds'][opt][name] = {e['name']: e['dur']/1e6 for e in events
                if e['name'] in ['Total ExecuteCompiler', 'Total Frontend', 'Total Backend',
                                 'Total InstantiateClass', 'Total InstantiateFunction']}
        # C caller links to both C and generated-C++ implementations through the same ABI.
        run(['clang', '-std=c17', opt, '-c', HERE/'driver.c', '-o', work/'driver.o'])
        library = ROOT/'examples/accounting_workload/.prism/runtime/project/php-strict/116def5680c631b9/libruntime.so'
        assert library.exists(), library
        executables = {}
        for name in ['C', 'lean_CXX', 'generated_CXX_PCH']:
            objects = [work/'driver.o', work/(name+'.o')]
            if name == 'generated_CXX_PCH':
                objects += [work/'bridge_PCH.o', library, '-Wl,-rpath,'+str(library.parent)]
            exe = work/(name+'.exe')
            run(['clang++'] + objects + ['-o', exe])
            executables[name] = exe
        cases, inputs, expected = verification_data()
        for name,exe in executables.items():
            actual = run([exe], input=inputs)[1]
            if actual != expected:
                raise AssertionError(f'behavior mismatch: {opt} {name}')
        report.setdefault('behavior', {})[opt] = {'cases_per_variant': len(cases), 'variants': list(executables), 'passed': True}
        print(opt, {k: round(v['median_ms'],2) for k,v in report['results'][opt].items()}, flush=True)
    report['load_end'] = os.getloadavg()
args.output.write_text(json.dumps(report, indent=2)+'\n')
print(args.output)
