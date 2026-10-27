"""Resolve and execute ordered numeric compounds through published API calls."""
import argparse
import copy
import json
from pathlib import Path
import subprocess
import tempfile
import time

root = Path(__file__).resolve().parents[3]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--tool', type=Path, required=True)
parser.add_argument('--core', type=Path)
parser.add_argument('--sanitize', action='store_true')
args = parser.parse_args()
metadata = json.loads((root / 'runtime/metadata/public_api.json').read_text())
publication = json.loads((root / 'runtime/public/specializations.json').read_text())

def call(data, error=None):
    result = subprocess.run([str(args.tool)], input=json.dumps(data), text=True, capture_output=True)
    if error:
        assert result.returncode and error in result.stderr, result.stderr
        return
    assert result.returncode == 0, result.stderr
    return json.loads(result.stdout)

def resolve(operation, target, right, meta=metadata, error=None):
    return call({'action': 'resolve_compound', 'metadata': meta,
                 'request': {'operation': operation, 'target': target, 'right': right}}, error)

expected_steps = ['capture_destination_and_retain_owners', 'snapshot_left', 'convert_left',
                  'evaluate_right', 'convert_right', 'calculate', 'convert_writeback',
                  'commit_destination', 'return_value_snapshot']
operations = ['add', 'subtract', 'multiply', 'divide', 'remainder', 'bit_and', 'bit_or',
              'bit_xor', 'shift_left', 'shift_right', 'power']
plans = {}
for op in operations:
    for target in ['int64', 'float64', 'int32', 'uint64', 'boolean', 'mixed', 'dynamic', 'string']:
        for right in ['int64', 'float64']:
            valid = (target == right == 'int64' or
                     target == 'float64' and op in operations[:4])
            plan = resolve(op + '_assign', target, right, error=None if valid else 'Unsupported compound operands')
            if valid:
                assert plan['steps'] == expected_steps
                assert plan['failure'] == 'no_writeback_preserve_prior_effects'
                assert plan['result_type'] == target and plan['writeback_conversion']['kind'] == 'identity'
                assert plan['left_conversion']['kind'] == 'identity'
                assert plan['right_conversion']['kind'] == ('identity' if target == right else 'call')
                plans[op, target, right] = plan
assert len(plans) == 19
for mutation, error in [
    (lambda c: c['rules'].append(c['rules'][0]), 'Ambiguous compound rule'),
    (lambda c: c.update(operation='positive'), 'must be binary'),
    (lambda c: c.update(operation='numeric_equal'), 'returning the destination type'),
    (lambda c: c.update(unknown='x'), 'unknown field'),
]:
    changed = copy.deepcopy(metadata)
    mutation(changed['global_operators']['compounds']['add_assign'])
    resolve('add_assign', 'int64', 'int64', changed, error)
# Operation identity and its backend are metadata, not special cases in C++.
changed = copy.deepcopy(metadata)
changed['global_operators']['compounds']['custom_update'] = changed['global_operators']['compounds'].pop('add_assign')
assert resolve('custom_update', 'int64', 'int64', changed)['calculation']['function'] == plans['add', 'int64', 'int64']['calculation']['function']
headers, apis = set(), set()
for plan in plans.values():
    for key in ['calculation', 'left_conversion', 'right_conversion', 'writeback_conversion']:
        if plan[key]['kind'] == 'call':
            headers.add(plan[key]['header']); apis.add(plan[key]['api'])
base = {'metadata': metadata, 'request': {'roots': [], 'apis': sorted(apis)},
        'imports': {'float64': publication['types']['float64']}, 'leaves': {}, 'layouts': {},
        'names': {}, 'api_imports': publication['apis']}
assert call(base)['units'] == []
print('PASS: 19 compound plans, admission/rejection, metadata ownership, published API reuse')
if not args.core:
    raise SystemExit(0)

def conversion(plan, value):
    return value if plan['kind'] == 'identity' else plan['function'] + '(' + value + ')'

def lower(plan, rhs):
    """A small consumer of the ordered protocol, not a runtime compound helper."""
    fragments = {
        'capture_destination_and_retain_owners': 'auto &destination = capture();',
        'snapshot_left': 'const auto old_left = destination;',
        'convert_left': 'const auto left = ' + conversion(plan['left_conversion'], 'old_left') + ';',
        'evaluate_right': 'const auto right_value = ' + rhs + ';',
        'convert_right': 'const auto right = ' + conversion(plan['right_conversion'], 'right_value') + ';',
        'calculate': 'const auto calculated = ' + plan['calculation']['function'] + '(left, right);',
        'convert_writeback': 'const auto replacement = ' + conversion(plan['writeback_conversion'], 'calculated') + ';',
        'commit_destination': 'destination = replacement;',
        'return_value_snapshot': 'return replacement;',
    }
    return '\n'.join(fragments[step] for step in plan['steps'])

source = '#include "public_api_guard.hpp"\n' + ''.join('#include "' + h + '"\n' for h in sorted(headers))
source += '''#include <cstdio>
#include <cstdlib>
#include <new>
using namespace scpp;
static unsigned allocations = 0;
static bool counting = false;
void *operator new(std::size_t n) { if(counting) ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p, std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"compound failure %d\\n",__LINE__); std::abort(); } } while(false)
int main() {
'''
expected = dict(zip(operations, [13, 7, 30, 3, 1, 2, 11, 9, 80, 1, 1000]))
for (op, target, right), plan in plans.items():
    answer = 10 / 3 if target == 'float64' and op == 'divide' else expected[op]
    source += '{ ' + target + ' target(10); int captures=0, evaluations=0;\n'
    source += 'auto capture = [&]() -> auto & { ++captures; return target; };\n'
    source += 'auto rhs = [&]() { CHECK(captures==1); ++evaluations; target=' + target + '(100); return ' + right + '(3); };\n'
    source += 'allocations=0; counting=true; const auto result = [&]() {\n' + lower(plan, 'rhs()') + '\n}(); counting=false;\n'
    source += 'CHECK(allocations==0 && captures==1 && evaluations==1); CHECK(target.native_value()=='+repr(answer)+');\n'
    source += 'target='+target+'(200); CHECK(result.native_value()=='+repr(answer)+'); }\n'
# RHS failures and calculation failures retain RHS mutations and never commit.
for op, rhs, throw_rhs in [('add', 3, True), ('divide', 0, False), ('power', -1, False),
                          ('power', 63, False), ('shift_left', 64, False), ('shift_right', -1, False)]:
    source += '{ int64 target(10); int captures=0, evaluations=0; bool failed=false;\n'
    source += 'auto capture = [&]() -> auto & { ++captures; return target; };\n'
    source += 'auto rhs = [&]() -> int64 { ++evaluations; target=int64(100); '
    source += 'throw 7;' if throw_rhs else 'return int64('+str(rhs)+');'
    source += ' }; try { (void)[&]() {\n'+lower(plans[op, 'int64', 'int64'], 'rhs()')+'\n}(); } catch (...) { failed=true; }\n'
    source += 'CHECK(failed && captures==1 && evaluations==1 && target.native_value()==100); }\n'
# Destination failure suppresses the RHS altogether.
source += '''{ int evaluations=0; bool failed=false;
auto capture = []() -> int64 & { throw 7; };
auto rhs = [&]() { ++evaluations; return int64(3); };
try { (void)[&]() {\n'''+lower(plans['add', 'int64', 'int64'], 'rhs()')+'''
}(); } catch(int) { failed=true; } CHECK(failed && evaluations==0); }
std::puts("PASS: planned compounds preserve old left, destination once, owned result, failure effects; successful allocations=0");
}
'''
with tempfile.TemporaryDirectory(prefix='scpp-compound-') as directory:
    stage = Path(directory); (stage / 'main.cpp').write_text(source)
    flags = ['-fsanitize=address,undefined', '-fno-sanitize-recover=all'] if args.sanitize else []
    start = time.perf_counter()
    subprocess.run(['clang++', '-std=c++23', '-O2', *flags, '-I'+str(root/'runtime/public'),
                    '-I'+str(root/'tests/runtime/native'), str(stage/'main.cpp'), str(args.core.resolve()),
                    '-fuse-ld=mold', '-o', str(stage/'main')], check=True)
    print(json.dumps({'public_proof_compile_and_link_seconds': time.perf_counter()-start}))
    subprocess.run([stage/'main'], check=True)
