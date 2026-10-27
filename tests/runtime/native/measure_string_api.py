"""Measure equivalent public/native string callers and the compiled adapter, serially."""
import argparse
import json
from pathlib import Path
import statistics
import subprocess
import tempfile
import time

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--compiler', default='clang++')
parser.add_argument('--output', type=Path, required=True)
args = parser.parse_args()
root = Path(__file__).resolve().parents[3]
runtime = root / 'runtime'
results = {}
with tempfile.TemporaryDirectory(prefix='scpp-string-cost-') as directory:
    directory = Path(directory)
    inputs = {
        'public': ('public', '#include "scpp/string.hpp"\nscpp::uint64 example(const scpp::string &s) { return s.byte_size(); }\n'),
        'native': ('include', '#include "scpp/string_t.hpp"\nstd::size_t example(const scpp::string_t &s) { return s.byte_size(); }\n'),
    }
    for name, (include, source) in inputs.items():
        path = directory / (name + '.cpp')
        path.write_text(source)
        command = [args.compiler, '-std=c++23', '-O2', '-DNDEBUG', '-I'+str(runtime/include)]
        preprocessed = subprocess.check_output([*command, '-E', '-P', str(path)])
        results[name] = {'preprocessed_bytes': len(preprocessed), 'seconds': []}
    for trial in range(3):
        for name in ('public', 'native') if trial % 2 == 0 else ('native', 'public'):
            include = inputs[name][0]
            start = time.perf_counter()
            subprocess.run([args.compiler, '-std=c++23', '-O2', '-DNDEBUG', '-I'+str(runtime/include),
                            '-c', str(directory/(name+'.cpp')), '-o', str(directory/(name+'.o'))], check=True)
            results[name]['seconds'].append(time.perf_counter()-start)
    start = time.perf_counter()
    subprocess.run([args.compiler, '-std=c++23', '-O2', '-DNDEBUG', '-I'+str(runtime/'include'),
                    '-c', str(runtime/'include/core/string.cpp'), '-o', str(directory/'adapter.o')], check=True)
    results['adapter_seconds'] = time.perf_counter()-start
for name in inputs:
    results[name]['median_seconds'] = statistics.median(results[name]['seconds'])
args.output.parent.mkdir(parents=True, exist_ok=True)
args.output.write_text(json.dumps(results, indent=2)+'\n')
print(json.dumps(results, indent=2))
