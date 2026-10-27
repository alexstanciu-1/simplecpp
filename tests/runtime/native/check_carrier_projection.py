"""One-layer carrier plans, public-only nullable execution and publication reuse."""
import argparse
import copy
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import time

root = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(root / 'runtime/tools'))
import concrete_types
from public_package import seal_package
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--tool', type=Path, required=True)
parser.add_argument('--core', type=Path)
parser.add_argument('--sanitize', action='store_true')
args = parser.parse_args()
args.tool = args.tool.resolve()
metadata = concrete_types.metadata(root / 'runtime')

def resolve(left, right, operation='add', meta=metadata, names=None, error=None):
    result = subprocess.run([str(args.tool)], input=json.dumps({
        'action':'resolve_operator', 'metadata':meta, 'names':names or {},
        'request':{'operation':operation, 'left':left, 'right':right}}), text=True, capture_output=True)
    if error:
        assert result.returncode and error in result.stderr, result.stderr
        return
    assert result.returncode == 0, result.stderr
    return json.loads(result.stdout)

for op in ['add', 'subtract', 'multiply', 'divide', 'remainder']:
    for left in ['nullable<int64>', 'result<int64>', 'int64']:
        for right in ['nullable<int64>', 'result<int64>', 'int64']:
            plan = resolve(left, right, op)
            if left == right == 'int64': continue
            assert plan['kind'] == 'checked_payload_binary'
            assert plan['result_type'] == 'int64'
            assert plan['calculation']['api'] == op + '_int64'
            assert plan['steps'] == ['evaluate_left', 'project_left', 'convert_left',
                                     'evaluate_right', 'project_right', 'convert_right', 'calculate']
            assert plan['left_projection']['kind'] == ('identity' if left == 'int64' else 'checked_member')
assert resolve('nullable<int16>', 'int32')['result_type'] == 'int64'
assert resolve('nullable<int64>', 'float64')['result_type'] == 'float64'
for bad in ['nullable<boolean>', 'nullable<string>', 'result<nullable<int64>>', 'nullable<vector<string>>']:
    resolve(bad, 'int64', error='Unsupported carrier payload')
resolve('vector<int64>', 'int64', error='no carrier projection')
resolve('nullable<int64>', 'uint64', error='Unsupported operator operand domains')
for op in ['equal', 'numeric_equal', 'strict_identical', 'concat']:
    resolve('nullable<int64>', 'nullable<int64>', op, error='Unsupported operator operand type')
renamed = copy.deepcopy(metadata)
renamed['families']['optional_number'] = renamed['families'].pop('nullable')
renamed['families']['optional_number']['defaults'] = ['int64']
p = resolve('optional_number<>', 'int64', meta=renamed,
            names={'optional_number<int64>':'optional_integer'})
assert p['left_projection']['cpp'] == 'scpp::optional_integer'
assert p['left_projection']['target'] == 'int64'
for update, message in [({'payload':'missing'}, 'Unknown carrier payload'),
                        ({'require_value':'missing'}, 'one checked member'),
                        ({'unexpected':'x'}, 'unknown field')]:
    bad = copy.deepcopy(metadata)
    bad['families']['nullable']['carrier'].update(update)
    resolve('nullable<int64>', 'int64', meta=bad, error=message)
bad = copy.deepcopy(metadata)
for method in bad['families']['nullable']['classes'][0]['methods']:
    if method.get('name') == 'require_value': method['returns'] = 'self_ref'
resolve('nullable<int64>', 'int64', meta=bad, error='owned payload')
def convert(source, target, meta=metadata, error=None):
    result = subprocess.run([str(args.tool)], input=json.dumps({
        'action':'resolve_conversion', 'metadata':meta,
        'names':{'nullable<int64>':'optional_integer'},
        'request':{'source':source, 'target':target}}), text=True, capture_output=True)
    if error:
        assert result.returncode and error in result.stderr, result.stderr
        return
    assert result.returncode == 0, result.stderr
    return json.loads(result.stdout)

for family in ['nullable','result']:
    for target in metadata['conversions']['bindings']['int64']['targets']:
        plan = convert(f'{family}<int64>',target)
        assert plan['kind'] == 'carrier_conversion'
        assert plan['payload_conversion']['api'] == f'cast_{target}_from_int64'
        assert plan['states'][0]['conversion']['kind'] == 'checked_payload'
        assert plan['states'][-1]['conversion']['kind'] == ('expression' if family == 'nullable' and target == 'string' else 'checked_payload')
assert convert('nullable<int8>','uint16')['payload_conversion']['operand_type'] == 'int64'
for source,target in [('nullable<vector<string>>','string'), ('result<nullable<int64>>','boolean'),
                      ('nullable<int64>','result<int64>'), ('nullable<int64>','nullable<int8>')]:
    convert(source,target,error='Unsupported checked conversion')
for mutate,message in [
    (lambda c:c['states'].append({'name':'extra'}), 'final fallback'),
    (lambda c:c['states'][0].update(test='missing'), 'one observation'),
    (lambda c:c['states'][1].update(name='payload'), 'Duplicate carrier state'),
    (lambda c:c['states'][1]['casts']['string'].update(expression='${value}.value()'), 'independent result'),
]:
    bad = copy.deepcopy(metadata); mutate(bad['families']['nullable']['carrier'])
    convert('nullable<int64>','string',meta=bad,error=message)
def condition(source, meta=metadata, names=None, error=None):
    result = subprocess.run([str(args.tool)], input=json.dumps({
        'action':'resolve_condition', 'metadata':meta,
        'names':names or {'nullable<int64>':'optional_integer'},
        'request':{'source':source}}), text=True, capture_output=True)
    if error:
        assert result.returncode and error in result.stderr, result.stderr
        return
    assert result.returncode == 0, result.stderr
    return json.loads(result.stdout)

def negate(source):
    result = subprocess.run([str(args.tool)], input=json.dumps({
        'action':'resolve_operator', 'metadata':metadata,
        'names':{'nullable<int64>':'optional_integer'},
        'request':{'operation':'logical_not','operand':source}}), text=True, capture_output=True)
    assert result.returncode == 0, result.stderr
    return json.loads(result.stdout)

assert negate('nullable<int64>')['kind'] == 'condition_operator'
assert negate('result<nullable<int64>>')['condition']['kind'] == 'carrier_condition'
for scalar in metadata['function_sets']['condition_truthy']['variants']:
    assert condition(scalar)['api'] == 'condition_truthy_' + scalar
    if scalar == 'error_value': continue  # Result<error> is not an admitted source formation.
    for family in ['nullable','result']:
        p = condition(f'{family}<{scalar}>')
        assert p['kind'] == 'carrier_condition'
        assert p['payload_condition']['api'] == 'condition_truthy_' + scalar
        assert [s['condition'] for s in p['states']] == ['payload','false']
nested_condition = condition('result<nullable<int64>>')
assert nested_condition['payload_condition']['payload_condition']['api'] == 'condition_truthy_int64'
condition('optional_integer')
for source in ['vector<int64>','nullable<vector<int64>>','result<hash<int64>>','null']:
    condition(source,error='Unsupported condition operand')
bad = copy.deepcopy(metadata)
bad['families']['nullable']['carrier']['states'][1]['condition'] = 'guess'
condition('nullable<int64>',meta=bad,error='Unknown carrier condition policy')
bad = copy.deepcopy(metadata)
del bad['families']['nullable']['carrier']['states'][1]['condition']
condition('nullable<int64>',meta=bad,error='no condition policy')
bad = copy.deepcopy(metadata)
bad['function_sets']['condition_truthy']['function']['operation']['returns'] = 'int64'
condition('nullable<int64>',meta=bad,error='return boolean')
# A different family may have a true non-payload state without acquiring a bool cast.
custom = copy.deepcopy(metadata)
custom['families']['signal'] = copy.deepcopy(custom['families']['nullable'])
custom['families']['signal']['defaults'] = ['int64']
custom['families']['signal']['carrier']['states'][1]['condition'] = 'true'
assert condition('signal<>',meta=custom)['states'][1]['condition'] == 'true'
assert convert('signal<>','boolean',meta=custom)['states'][1]['conversion']['kind'] == 'checked_payload'
print('PASS: scalar/carrier conditions, nested delegation, aliases and rejected payload/state policies')
print('PASS: one-layer cast plans, nullable string exception, required bool payload and rejected state metadata')
print('PASS: carrier arithmetic matrix, defaults/names, one-layer limits and descriptor rejection')
if not args.core: sys.exit(0)

with tempfile.TemporaryDirectory(prefix='scpp-carrier-') as directory:
    work = Path(directory)
    sdk = work / 'sdk'
    for folder in ['metadata', 'include', 'public']:
        shutil.copytree(root / 'runtime' / folder, sdk / folder)
    (sdk / 'lib').mkdir(); (sdk / 'tools').mkdir()
    shutil.copy2(args.core, sdk / 'lib/libcore.a')
    shutil.copy2(args.tool, sdk / 'tools/scpp_api_generator')
    seal_package(sdk, ['core'])
    os.environ['SCPP_API_GENERATOR'] = str(sdk / 'tools/scpp_api_generator')
    request = work / 'request.json'
    request.write_text(json.dumps({'package':'app.carrier', 'roots':['nullable<int64>'],
                                  'names':{'nullable<int64>':'optional_integer'}}))
    app = work / 'app'
    # Storage ABI flags match the SDK. Instrument the generated implementation below.
    manifest = concrete_types.generate(request, app, sdk, [sdk], 'clang++', [])
    assert len(manifest['sources']) == 1
    names = {'nullable<int64>':'optional_integer'}
    functions = []
    headers = {'scpp/optional_integer.hpp'}
    for op in ['add','subtract','multiply','divide','remainder']:
        plan = resolve('nullable<int64>', 'nullable<int64>', op, names=names)
        calculation = plan['calculation']; headers.add(calculation['header'])
        assert calculation['api'] in manifest['apis']
        left = plan['left_projection']['expression'].replace('${value}', 'left')
        right = plan['right_projection']['expression'].replace('${value}', 'right')
        functions.append(f'''static scpp::int64 run_{op}(const scpp::optional_integer &left,
            const scpp::optional_integer &right, int &effects) {{
            const auto left_payload = {left};
            ++effects;
            const auto right_payload = {right};
            return {calculation['function']}(left_payload, right_payload);
        }}''')
    for target in ['boolean','int8','string']:
        plan = convert('nullable<int64>',target)
        cast = plan['payload_conversion']; headers.add(cast['header'])
        assert cast['api'] in manifest['apis']
        projection = plan['projection']['expression'].replace('${value}','source')
        body = ''
        for state in plan['states']:
            conversion = state['conversion']
            if conversion['kind'] == 'expression':
                headers.add(conversion['header'])
                branch = 'return ' + conversion['expression'] + ';'
            else:
                branch = f'const auto payload = {projection}; return {cast["function"]}(payload);'
            if 'test_expression' in state:
                body += 'if (' + state['test_expression'].replace('${value}','source') + ') { ' + branch + ' }\n'
            else: body += branch
        functions.append('static ' + metadata['types'][target]['cpp'] + ' convert_' + target
                         + '(const scpp::optional_integer &source) { ' + body + ' }')
    plan = condition('nullable<int64>')
    payload = plan['payload_condition']; headers.add(payload['header'])
    assert payload['api'] in manifest['apis']
    body = ''
    for state in plan['states']:
        if state['condition'] == 'payload':
            projected = plan['projection']['expression'].replace('${value}','source')
            branch = f'const auto value = {projected}; return {payload["function"]}(value);'
        else: branch = 'return scpp::boolean(' + state['condition'] + ');'
        if 'test_expression' in state:
            body += 'if (' + state['test_expression'].replace('${value}','source') + ') { ' + branch + ' }\n'
        else: body += branch
    functions.append('static scpp::boolean observe_condition(const scpp::optional_integer &source) { ' + body + ' }')
    code = '\n'.join('#include "'+h+'"' for h in sorted(headers)) + '\n' + '\n'.join(functions)
    code += r'''
#include "public_api_guard.hpp"
#include <cstdlib>
#include <new>
#include <cstdio>
static int allocations = 0;
void *operator new(std::size_t n) { ++allocations; if (auto p = std::malloc(n ? n : 1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p, std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if (!(x)) { std::fprintf(stderr,"line %d\n",__LINE__); std::abort(); } } while(false)
int main() {
    using scpp::int64; using optional = scpp::optional_integer;
    optional left(int64(12)), right(int64(3)), absent;
    int effects = 0;
    auto before = allocations;
    CHECK(run_add(left,right,effects).native_value() == 15);
    CHECK(run_subtract(left,right,effects).native_value() == 9);
    CHECK(run_multiply(left,right,effects).native_value() == 36);
    CHECK(run_divide(left,right,effects).native_value() == 4);
    CHECK(run_remainder(left,right,effects).native_value() == 0);
    CHECK(effects == 5);
    optional copy(left); left.reset();
    CHECK(!left.has_value() && copy.has_value());
    int64 output(9);
    CHECK(!scpp::take(output,absent) && output.native_value() == 9);
    CHECK(scpp::take(output,copy) && output.native_value() == 12);
    optional zero(int64(0));
    CHECK(zero.has_value() && zero.require_value("zero").native_value() == 0);
    CHECK(CONDITION_NOT_ABSENT);
    CHECK(!observe_condition(absent));
    CHECK(!observe_condition(zero));
    CHECK(observe_condition(copy));
    optional negative(int64(-1));
    CHECK(observe_condition(negative));
    int selected = 0;
    if (observe_condition(absent)) ++selected;
    if (observe_condition(copy)) ++selected;
    CHECK(selected == 1);
    CHECK(!convert_boolean(zero));
    CHECK(convert_boolean(copy));
    CHECK(convert_int8(copy).native_value() == 12);
    CHECK(convert_string(copy).equals("12"));
    CHECK(convert_string(absent).byte_size().native_value() == 0);
    CHECK(allocations == before);
    effects = 0; bool caught = false;
    try { (void)run_add(absent,right,effects); } catch (...) { caught = true; }
    CHECK(caught && effects == 0);
    caught = false;
    try { (void)run_add(copy,absent,effects); } catch (...) { caught = true; }
    CHECK(caught && effects == 1 && copy.has_value());
    caught = false;
    try { (void)convert_boolean(absent); } catch (...) { caught = true; }
    CHECK(caught && !absent.has_value());
    optional too_large(int64(128)); caught = false;
    try { (void)convert_int8(too_large); } catch (...) { caught = true; }
    CHECK(caught && too_large.require_value("unchanged").native_value() == 128);
    // Extraction returns an owned value that survives mutation/destruction of its carrier.
    int64 retained(0);
    { optional temporary(int64(42)); retained = temporary.require_value("retain"); temporary.reset(); }
    CHECK(retained.native_value() == 42);
    std::puts("PASS: public nullable arithmetic, ordered failures, take/reset, casts, conditions, owned lifetime; success allocations=0");
}
'''
    code = code.replace('CONDITION_NOT_ABSENT', negate('nullable<int64>')['result_expression'].replace('${result}', 'observe_condition(absent)'))
    caller = work / 'main.cpp'; caller.write_text(code)
    flags = ['-fsanitize=address,undefined','-fno-omit-frame-pointer'] if args.sanitize else []
    common = ['clang++','-std=c++23','-O1',*flags,'-I'+str(app/'public'),'-I'+str(sdk/'public')]
    start = time.perf_counter()
    subprocess.run([*common,'-I'+str(sdk/'include'),'-I'+str(app/'include'),'-c',str(app/manifest['sources'][0]),'-o',str(work/'storage.o')],check=True)
    storage_time = time.perf_counter()-start
    start = time.perf_counter()
    subprocess.run([*common,'-I'+str(root/'tests/runtime/native'),'-c',str(caller),'-o',str(work/'caller.o')],check=True)
    caller_time = time.perf_counter()-start
    subprocess.run(['clang++',*flags,str(work/'caller.o'),str(work/'storage.o'),str(args.core.resolve()),'-pthread','-o',str(work/'proof')],check=True)
    subprocess.run([str(work/'proof')],check=True)
    (app/'lib').mkdir()
    subprocess.run(['ar','rcs',str(app/'lib/libcarrier.a'),str(work/'storage.o')],check=True)
    seal_package(app,['carrier'])
    reused = concrete_types.generate(request,work/'reuse',sdk,[sdk,app],'clang++',[])
    assert reused['sources'] == []
    print(f'PASS: sealed application specialization reuse; storage compile={storage_time:.4f}s public caller compile={caller_time:.4f}s')
