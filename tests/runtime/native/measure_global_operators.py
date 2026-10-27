"""One compile per caller/adapter; report cost without building the runtime."""
import argparse
import json
from pathlib import Path
import subprocess
import tempfile
import time

root = Path(__file__).resolve().parents[3]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--compiler', default='clang++')
parser.add_argument('--numeric', action='store_true', help='Measure ordinary numeric division/comparison instead of boxed equality')
args = parser.parse_args()
public = '''#include "scpp/operators/add_int64.hpp"
#include "scpp/operators/equal_mixed.hpp"
scpp::int64 add(scpp::int64 a, scpp::int64 b) { return scpp::numeric_add(a,b); }
scpp::boolean equal(const scpp::mixed &a, const scpp::mixed &b) { return scpp::boxed_equal(a,b); }
'''
native = '''#include "operators/identity/boxed_scalar_equality.hpp"
scpp::int_t<> add(scpp::int_t<> a, scpp::int_t<> b) { return a+b; }
scpp::bool_t equal(const scpp::mixed_t &a, const scpp::mixed_t &b) { return a==b; }
'''
if args.numeric:
    public = '#include "scpp/operators/divide_int64.hpp"\n#include "scpp/operators/less_float64.hpp"\nscpp::int64 div(scpp::int64 a, scpp::int64 b) { return scpp::numeric_divide(a,b); }\nscpp::boolean less(const scpp::float64 &a, const scpp::float64 &b) { return scpp::numeric_less(a,b); }\n'
    native = '#include "scpp/generated/operator_detail.hpp"\nscpp::int_t<> div(scpp::int_t<> a, scpp::int_t<> b) { return scpp::detail::generated_operator_detail::div(a,b); }\nscpp::bool_t less(const scpp::float_t &a, const scpp::float_t &b) { return scpp::detail::generated_operator_detail::lt(a,b); }\n'
rows = []
with tempfile.TemporaryDirectory(prefix='scpp-operator-cost-') as directory:
    stage = Path(directory)
    for name, source in [('public', public), ('native', native)]:
        (stage/(name+'.cpp')).write_text(source)
    cases = [('public', stage/'public.cpp', ['public']), ('native', stage/'native.cpp', ['include'])]
    adapters = ['divide_int64','divide_float64'] if args.numeric else ['equal_mixed','equal_dynamic']
    cases += [(f'adapter_{identity}', root/f'runtime/include/scpp/generated/public_{identity}.cpp', ['public','include']) for identity in adapters]
    for name, path, includes in cases:
        command = [args.compiler, '-std=c++23', '-O2', *['-I'+str(root/'runtime'/i) for i in includes]]
        start = time.perf_counter()
        subprocess.run([*command, '-c', str(path), '-o', str(stage/(name+'.o'))], check=True)
        elapsed = time.perf_counter()-start
        preprocessed = subprocess.check_output([*command, '-E', str(path)])
        rows.append({'case':name, 'seconds':elapsed, 'preprocessed_bytes':len(preprocessed)})
print(json.dumps(rows, indent=2))
