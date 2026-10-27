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
parser.add_argument('--conversions', action='store_true', help='Measure checked numeric/string conversion callers')
parser.add_argument('--boxed-conversions', action='store_true', help='Measure owned string and boxed boolean casts')
parser.add_argument('--boxed-integers', action='store_true', help='Measure fixed-width casts preserving boxed payload origins')
parser.add_argument('--conditions', action='store_true', help='Measure condition observations independently of explicit casts')
parser.add_argument('--text', action='store_true', help='Measure bounded text conversion plus primitive string concatenation')
parser.add_argument('--identity', action='store_true', help='Measure canonical and boxed strict identity')
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
if args.conversions:
    public = '#include "scpp/conversions/cast_uint64_from_string.hpp"\n#include "scpp/conversions/cast_int8_from_float64.hpp"\nscpp::uint64 parse(const scpp::string &s) { return scpp::cast_uint64(s); }\nscpp::int8 narrow(const scpp::float64 &f) { return scpp::cast_int8(f); }\n'
    native = '#include "scpp/cast.hpp"\nscpp::int_t<__UINT64_TYPE__> parse(const scpp::string_t &s) { return scpp::cast<scpp::int_t<__UINT64_TYPE__>>(s); }\nscpp::int_t<__INT8_TYPE__> narrow(const scpp::float_t &f) { return scpp::cast<scpp::int_t<__INT8_TYPE__>>(f); }\n'
if args.boxed_conversions:
    public = '#include "scpp/conversions/cast_string_from_mixed.hpp"\n#include "scpp/conversions/cast_boolean_from_dynamic.hpp"\nscpp::string text(const scpp::mixed &m) { return scpp::cast_string(m); }\nscpp::boolean flag(const scpp::dynamic &d) { return scpp::cast_boolean(d); }\n'
    native = '#include "scpp/cast.hpp"\nscpp::string_t text(const scpp::mixed_t &m) { return scpp::cast<scpp::string_t>(m); }\nscpp::bool_t flag(const scpp::detail::dynamic_value &d) { return scpp::cast<scpp::bool_t>(d); }\n'
if args.boxed_integers:
    public = '#include "scpp/conversions/cast_uint64_from_mixed.hpp"\n#include "scpp/conversions/cast_int8_from_dynamic.hpp"\nscpp::uint64 wide(const scpp::mixed &m) { return scpp::cast_uint64(m); }\nscpp::int8 narrow(const scpp::dynamic &d) { return scpp::cast_int8(d); }\n'
    native = '#include "scpp/cast.hpp"\nscpp::int_t<__UINT64_TYPE__> wide(const scpp::mixed_t &m) { return scpp::cast<scpp::int_t<__UINT64_TYPE__>>(m); }\nscpp::int_t<__INT8_TYPE__> narrow(const scpp::detail::dynamic_value &d) { return scpp::cast<scpp::int_t<__INT8_TYPE__>>(d); }\n'
if args.conditions:
    public = '#include "scpp/conditions/condition_truthy_string.hpp"\n#include "scpp/conditions/condition_truthy_dynamic.hpp"\nscpp::boolean text(const scpp::string &s) { return scpp::condition_truthy(s); }\nscpp::boolean boxed(const scpp::dynamic &d) { return scpp::condition_truthy(d); }\n'
    native = '#include "operators/conditional/condition_truthiness.hpp"\nscpp::bool_t text(const scpp::string_t &s) { return scpp::condition_truthy(s); }\nscpp::bool_t boxed(const scpp::detail::dynamic_value &d) { return scpp::condition_truthy(d); }\n'
if args.text:
    public = '#include "scpp/text/concat_text_float64.hpp"\nscpp::string text(const scpp::string &s, const scpp::float64 &f) { return s+scpp::concat_text(f); }\n'
    native = '#include "lang/php/support/php_string.hpp"\n#include "scpp/generated/operators.hpp"\nscpp::string_t text(const scpp::string_t &s, const scpp::float_t &f) { return s+scpp::php::to_string(f); }\n'
if args.identity:
    public = '#include "scpp/identity/strict_identical_float64.hpp"\n#include "scpp/identity/strict_identical_mixed.hpp"\nscpp::boolean scalar(const scpp::float64 &a,const scpp::float64 &b) { return scpp::strict_identical(a,b); }\nscpp::boolean boxed(const scpp::mixed &a,const scpp::mixed &b) { return scpp::strict_identical(a,b); }\n'
    native = '#include "operators/identity/strict_identity.hpp"\nscpp::bool_t scalar(const scpp::float_t &a,const scpp::float_t &b) { return scpp::identical(a,b); }\nscpp::bool_t boxed(const scpp::mixed_t &a,const scpp::mixed_t &b) { return scpp::identical(a,b); }\n'
rows = []
with tempfile.TemporaryDirectory(prefix='scpp-operator-cost-') as directory:
    stage = Path(directory)
    for name, source in [('public', public), ('native', native)]:
        (stage/(name+'.cpp')).write_text(source)
    cases = [('public', stage/'public.cpp', ['public']), ('native', stage/'native.cpp', ['include'])]
    adapters = ['divide_int64','divide_float64'] if args.numeric else ['equal_mixed','equal_dynamic']
    if args.conversions: adapters = ['cast_uint64_from_string']
    if args.boxed_conversions: adapters = ['cast_string_from_mixed']
    if args.boxed_integers: adapters = ['cast_uint64_from_mixed']
    if args.conditions: adapters = ['condition_truthy_dynamic']
    if args.text: adapters = ['concat_text_float64']
    if args.identity: adapters = ['strict_identical_mixed']
    cases += [(f'adapter_{identity}', root/f'runtime/include/scpp/generated/public_{identity}.cpp', ['public','include']) for identity in adapters]
    for name, path, includes in cases:
        command = [args.compiler, '-std=c++23', '-O2', *['-I'+str(root/'runtime'/i) for i in includes]]
        start = time.perf_counter()
        subprocess.run([*command, '-c', str(path), '-o', str(stage/(name+'.o'))], check=True)
        elapsed = time.perf_counter()-start
        preprocessed = subprocess.check_output([*command, '-E', str(path)])
        rows.append({'case':name, 'seconds':elapsed, 'preprocessed_bytes':len(preprocessed)})
print(json.dumps(rows, indent=2))
