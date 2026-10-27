"""Measure equivalent public/native value callers and compiled adapters, serially."""
import argparse
import json
from pathlib import Path
import statistics
import subprocess
import tempfile
import time

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--facility', choices=('string', 'errors', 'composition'), default='string')
parser.add_argument('--compiler', default='clang++')
parser.add_argument('--output', type=Path, required=True)
parser.add_argument('--core-build', type=Path, help='Also force core object rebuild in this existing CMake build')
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
    if args.facility == 'errors':
        inputs = {
            'public': ('public', '#include "scpp/error_value.hpp"\n#include "scpp/exception_info.hpp"\nscpp::boolean present(const scpp::error_value &e) { return e.has_error(); }\nscpp::int64 count(const scpp::exception_info &e) { return e.detail_count(); }\n'),
            'native': ('include', '#include "scpp/error_t.hpp"\n#include "scpp/runtime_error.hpp"\nscpp::bool_t present(const scpp::error_t &e) { return e.has_error(); }\nstd::size_t count(const scpp::runtime_error &e) { return e.details().size(); }\n'),
        }
    if args.facility == 'composition':
        inputs = {
            'public': ('public', '#include "modules/filesystem/scan.hpp"\nscpp::result_vector_string example(const scpp::string &path) { return scpp::fs::scan(path); }\n'),
            'native': ('include', '#include "modules/filesystem/filesystem.hpp"\nscpp::result<scpp::vector_t<scpp::string_t>> example(const scpp::string_t &path) { return scpp::fs::scan(path); }\n'),
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
    adapters = ['string'] if args.facility == 'string' else ['error_value', 'exception_info']
    results['adapter_seconds'] = {}
    if args.facility == 'composition':
        adapters = ['generated/vector_string', 'generated/result_vector_string', '../modules/filesystem/public_scan']
    for adapter in adapters:
        start = time.perf_counter()
        subprocess.run([args.compiler, '-std=c++23', '-O2', '-DNDEBUG', '-I'+str(runtime/'include'),
                        '-c', str(runtime/('include/core/'+adapter+'.cpp')), '-o', str(directory/(Path(adapter).name+'.o'))], check=True)
        results['adapter_seconds'][adapter] = time.perf_counter()-start
for name in inputs:
    results[name]['median_seconds'] = statistics.median(results[name]['seconds'])
if args.core_build:
    (args.core_build/'CMakeFiles/scpp_runtime_core.dir/include/core/runtime_core.cpp.o').unlink()
    command = ['cmake', '--build', str(args.core_build), '--target', 'scpp_runtime_core', '-j8']
    start = time.perf_counter()
    subprocess.run(command, check=True)
    results['core_rebuild_seconds'] = time.perf_counter()-start
    start = time.perf_counter()
    subprocess.run(command, check=True)
    results['core_noop_seconds'] = time.perf_counter()-start
args.output.parent.mkdir(parents=True, exist_ok=True)
args.output.write_text(json.dumps(results, indent=2)+'\n')
print(json.dumps(results, indent=2))
