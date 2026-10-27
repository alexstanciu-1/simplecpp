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
for bad in ['nullable<boolean>', 'result<nullable<int64>>', 'nullable<vector<string>>']:
    resolve(bad, 'int64', error='Unsupported carrier payload')
resolve('nullable<string>', 'int64', error='Unsupported operator operand domains')
resolve('vector<int64>', 'int64', error='no carrier projection')
resolve('nullable<int64>', 'uint64', error='Unsupported operator operand domains')
assert resolve('nullable<int64>', 'nullable<int64>', 'concat')['kind'] == 'converted_carrier_binary'
resolve('nullable<int64>', 'nullable<int64>', 'equal', error='Unsupported operator operand domains')
for op in ['numeric_equal','numeric_not_equal','strict_identical','strict_not_identical']:
    for left,right in [('nullable<int64>','nullable<int64>'), ('nullable<int64>','int64'),
                       ('int64','nullable<int64>'), ('nullable<int64>','nullable<float64>'),
                       ('result<int64>','nullable<int64>')]:
        p = resolve(left,right,op)
        assert p['kind'] == 'state_comparison'
        assert p['steps'][:2] == ['evaluate_left_snapshot','evaluate_right_snapshot']
        assert p['different_states'] == str(op in ['numeric_not_equal','strict_not_identical']).lower()
    if op.startswith('strict_'):
        assert resolve('result<int64>','result<int64>',op)['same_state_calculations']['error']['api'] == op+'_error_value'
    else:
        assert resolve('result<int64>','result<int64>',op)['same_states']['error'] == str(op == 'numeric_equal').lower()
assert resolve('nullable<int64>','nullable<float64>','numeric_equal')['calculation']['operand_type'] == 'float64'
assert resolve('nullable<int64>','nullable<float64>','strict_identical')['calculation']['value'] == 'false'
for op in ['less','less_equal','greater','greater_equal','compare']:
    assert resolve('nullable<int64>','int64',op)['kind'] == 'checked_payload_binary'
resolve('nullable<int64>','nullable<boolean>','numeric_equal',error='Unsupported operator operand domains')
resolve('result<nullable<int64>>','nullable<int64>','numeric_equal',error='Unsupported carrier payload')
bad = copy.deepcopy(metadata)
bad['families']['nullable']['carrier']['states'][1]['comparison_tag'] = 'unknown_state'
resolve('nullable<int64>','nullable<int64>','numeric_equal',meta=bad,error='Unsupported carrier comparison state')
bad = copy.deepcopy(metadata)
bad['global_operators']['carrier_comparisons']['operations']['numeric_equal']['same_states']['payload'] = 'true'
resolve('nullable<int64>','nullable<int64>','numeric_equal',meta=bad,error='Payload states require payload comparison')
resolve('nullable<int64>','nullable<uint64>','numeric_equal',error='Unsupported operator operand domains')
bad = copy.deepcopy(metadata)
del bad['families']['result']['carrier']['states'][1]['comparison_value']
resolve('result<int64>','result<int64>','strict_identical',meta=bad,error='requires owned state projections')
bad = copy.deepcopy(metadata)
bad['families']['result']['carrier']['states'][1]['comparison_value']['member'] = 'require_value'
resolve('result<int64>','result<int64>','strict_identical',meta=bad,error='owned payload from a const checked member')
bad = copy.deepcopy(metadata)
bad['function_sets']['strict_identical']['function']['operation']['parameters'][0]['passing'] = 'value'
resolve('result<int64>','result<float64>','strict_identical',meta=bad,error='must borrow its projected values')
renamed_state = copy.deepcopy(metadata)
renamed_state['families']['outcome'] = renamed_state['families'].pop('result')
renamed_state['families']['outcome']['carrier']['states'][1]['comparison_tag'] = 'failure'
for op in ['strict_identical','strict_not_identical']:
    policies = renamed_state['global_operators']['carrier_comparisons']['operations'][op]['same_states']
    policies['failure'] = policies.pop('error')
    p = resolve('outcome<int64>','outcome<float64>',op,meta=renamed_state)
    assert p['same_state_calculations']['failure']['api'] == op+'_error_value'
print('PASS: state-first comparison plans, numeric promotion versus strict identity, structured error identity and checked ordering')
for op in ['strict_identical','strict_not_identical']:
    for carrier in ['nullable<int64>','nullable<string>','nullable<vector<string>>']:
        for left,right,side in [(carrier,'null','left'),('null',carrier,'right')]:
            p = resolve(left,right,op)
            assert p['kind'] == 'carrier_literal_observation'
            assert p['operand_side'] == side and p['payload_access'] == 'none'
            assert p['operand_passing'] == 'const_ref'
            assert p['states'][-1]['value'] == str(op == 'strict_identical').lower()
    resolve('result<int64>','null',op,error='Unsupported carrier literal comparison')
    resolve('result<nullable<int64>>','null',op,error='Unsupported carrier literal comparison')
for op in ['numeric_equal','numeric_not_equal']:
    resolve('nullable<int64>','null',op,error='Unsupported carrier payload')
for op in ['equal','not_equal']:
    resolve('nullable<int64>','null',op,error='Unsupported carrier payload')
bad = copy.deepcopy(metadata)
bad['families']['nullable']['carrier']['states'][1]['literals'] = ['null','null']
resolve('nullable<int64>','null','strict_identical',meta=bad,error='Duplicate carrier state literal')
print('PASS: strict literal-null presence, both operand orders, no extraction/copy and rejected Result/ordinary routes')
renamed = copy.deepcopy(metadata)
renamed['families']['optional_number'] = renamed['families'].pop('nullable')
renamed['families']['optional_number']['defaults'] = ['int64']
p = resolve('optional_number<>', 'int64', meta=renamed,
            names={'optional_number<int64>':'optional_integer'})
assert p['left_projection']['cpp'] == 'scpp::optional_integer'
assert p['left_projection']['target'] == 'int64'
assert resolve('optional_number<>','int64','numeric_equal',meta=renamed, names={'optional_number<int64>':'optional_integer'})['kind'] == 'state_comparison'
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
def convert(source, target, meta=metadata, error=None, names=None):
    result = subprocess.run([str(args.tool)], input=json.dumps({
        'action':'resolve_conversion', 'metadata':meta,
        'names':names or {'nullable<int64>':'optional_integer'},
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
                      ('result<nullable<int64>>','result<nullable<float64>>')]:
    convert(source,target,error='Unsupported checked conversion')
for family in ['nullable','result']:
    for target in ['int8','int64','float64','boolean','string']:
        plan = convert(f'{family}<int64>', f'{family}<{target}>')
        assert plan['kind'] == 'carrier_reconstruction'
        assert plan['states'][0]['kind'] == 'payload'
        assert plan['states'][1]['kind'] == 'state'
        assert plan['commit'] == 'construct_destination_after_payload_conversion'
convert('nullable<int64>','result<int64>',error='Cross-family carrier conversion')
convert('result<int64>','nullable<int64>',error='Cross-family carrier conversion')
convert('int64','nullable<int64>',error='Unsupported checked conversion')
for recipe,message in [
    ({'kind':'guess','expression':'${target}()'}, 'Unknown carrier reconstruction kind'),
    ({'kind':'state','expression':'${target}(${value})'}, 'declared value source'),
    ({'kind':'payload','expression':'${target}(${source})'}, 'declared value source'),
    ({'kind':'state','expression':'${target}(${unknown})'}, 'Unknown reconstruction marker'),
]:
    bad = copy.deepcopy(metadata); bad['families']['nullable']['carrier']['states'][1]['reconstruct'] = recipe
    convert('nullable<int64>','nullable<float64>',meta=bad,error=message)
bad = copy.deepcopy(metadata)
del bad['families']['nullable']['carrier']['states'][1]['reconstruct']
convert('nullable<int64>','nullable<float64>',meta=bad,error='Missing carrier state reconstruction')
print('PASS: same-family reconstruction, explicit state transport and no recursive/cross-family casts')
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
    request.write_text(json.dumps({'package':'app.carrier', 'roots':['nullable<int64>','nullable<float64>','nullable<string>','result<int64>','result<float64>'],
                                  'names':{'nullable<int64>':'optional_integer','nullable<float64>':'optional_float','nullable<string>':'optional_text','result<int64>':'result_integer','result<float64>':'result_float'}}))
    app = work / 'app'
    # Storage ABI flags match the SDK. Instrument the generated implementation below.
    manifest = concrete_types.generate(request, app, sdk, [sdk], 'clang++', [])
    assert len(manifest['sources']) == 5
    print('Carrier storage bytes:', {key:manifest['types'][key]['layout'][0] for key in ['nullable<int64>','nullable<float64>','nullable<string>','result<int64>','result<float64>']})
    names = {'nullable<int64>':'optional_integer','nullable<float64>':'optional_float','nullable<string>':'optional_text','result<int64>':'result_integer','result<float64>':'result_float'}
    functions = []
    headers = {'scpp/optional_integer.hpp', 'scpp/result_vector_string.hpp'}
    for op in ['add','subtract','multiply','divide','remainder','less','compare']:
        plan = resolve('nullable<int64>', 'nullable<int64>', op, names=names)
        calculation = plan['calculation']; headers.add(calculation['header'])
        assert calculation['api'] in manifest['apis']
        left = plan['left_projection']['expression'].replace('${value}', 'left')
        right = plan['right_projection']['expression'].replace('${value}', 'right')
        functions.append(f'''static auto run_{op}(const scpp::optional_integer &left,
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
    for family in ['nullable','result']:
        for op in ['numeric_equal','numeric_not_equal','strict_identical','strict_not_identical']:
            for right_type, suffix in [('int64','integer'), ('float64','float')]:
                plan = resolve(f'{family}<int64>',f'{family}<{right_type}>',op,names=names)
                left_p, right_p = plan['left_projection'], plan['right_projection']
                headers.update([left_p['header'],right_p['header']])
                tags = {tag:i for i,tag in enumerate(sorted({state['tag'] for side in ['left_states','right_states'] for state in plan[side]}))}
                mutation = 'original_right.reset()' if family == 'nullable' else f'original_right = scpp::{right_type}(7)'
                body = 'const auto left = original_left; ++effects; if (mutate_right) '+mutation+'; const auto right = original_right;\n'
                for side in ['left','right']:
                    observe = ''
                    for state in plan[side+'_states']:
                        result = 'return ' + str(tags[state['tag']]) + ';'
                        if 'test_expression' in state:
                            observe += 'if (' + state['test_expression'].replace('${value}',side) + ') { ' + result + ' }'
                        else: observe += result
                    body += 'const int '+side+'_state = ([&]() { '+observe+' })();\n'
                body += 'if (left_state != right_state) return scpp::boolean('+plan['different_states']+');\n'
                for tag,result in plan['same_states'].items():
                    if tag in tags: body += f'if (left_state == {tags[tag]}) return scpp::boolean({result});\n'
                for tag,calc_state in plan['same_state_calculations'].items():
                    headers.add(calc_state['header']); assert calc_state['api'] in manifest['apis']
                    body += f'if (left_state == {tags[tag]}) {{ '
                    for side in ['left','right']:
                        expr = calc_state[side+'_projection']['expression'].replace('${value}',side)
                        body += 'const auto '+side+'_state_value = '+expr+'; '
                    body += 'return '+calc_state['function']+'(left_state_value,right_state_value); }\n'
                calc = plan['calculation']
                if calc['kind'] == 'constant_after_evaluation': body += 'return scpp::boolean('+calc['value']+');'
                else:
                    headers.add(calc['header']); assert calc['api'] in manifest['apis']
                    for side, projection in [('left',left_p),('right',right_p)]:
                        expression = projection['expression'].replace('${value}',side)
                        body += f'const auto {side}_payload = {expression};\n'
                        conversion = calc[side+'_conversion']
                        expression = side+'_payload'
                        if conversion['kind'] != 'identity':
                            headers.add(conversion['header']); assert conversion['api'] in manifest['apis']
                            expression = conversion['function']+'('+expression+')'
                        body += f'const auto {side}_converted = {expression};\n'
                    body += 'return '+calc['function']+'(left_converted,right_converted);'
                functions.append('static scpp::boolean '+('compare_' if family == 'nullable' else 'result_compare_')+op+'_'+suffix+'(const '+left_p['cpp']+'& original_left, '+right_p['cpp']+'& original_right, int &effects, bool mutate_right=false) { '+body+' }')
    for family in ['nullable','result']:
        for source_type,target_type in [('int64','float64'), ('float64','int64')]:
            plan = convert(f'{family}<{source_type}>',f'{family}<{target_type}>',names=names)
            payload = plan['payload_conversion']
            headers.update([plan['projection']['header'],plan['target_header'],payload['header']])
            assert payload['api'] in manifest['apis']
            body = ''
            for state in plan['states']:
                expression = state['expression'].replace('${source}','source')
                if state['kind'] == 'payload':
                    projection = plan['projection']['expression'].replace('${value}','source')
                    branch = f'const auto payload = {projection}; const auto converted = {payload["function"]}(payload); '
                    branch += 'return ' + expression.replace('${value}','converted') + ';'
                else: branch = 'return ' + expression + ';'
                if 'test_expression' in state:
                    body += 'if (' + state['test_expression'].replace('${value}','source') + ') { ' + branch + ' }\n'
                else: body += branch
            functions.append('static '+plan['target_cpp']+' reconstruct_'+family+'_'+target_type
                             +'(const '+plan['projection']['cpp']+'& source) { '+body+' }')
    for op in ['strict_identical','strict_not_identical']:
        for source_type, suffix in [('int64','integer'),('string','text')]:
            for side in ['left','right']:
                source = f'nullable<{source_type}>'
                plan = resolve(source if side == 'left' else 'null', 'null' if side == 'left' else source, op, names=names)
                headers.add(plan['header'])
                body = '++observations; const auto &value = source;\n'
                for state in plan['states']:
                    branch = 'return scpp::boolean('+state['value']+');'
                    if 'test_expression' in state:
                        body += 'if ('+state['test_expression'].replace('${value}','value')+') { '+branch+' }\n'
                    else: body += branch
                functions.append('static scpp::boolean null_'+op+'_'+side+'_'+suffix
                                 +'(const '+plan['cpp']+'& source, int &observations) { '+body+' }')
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
    const scpp::string long_text("This payload is long enough that copying it requires a heap allocation.");
    const scpp::optional_text present_text(long_text), absent_text;
    auto before = allocations;
    CHECK(run_add(left,right,effects).native_value() == 15);
    CHECK(run_subtract(left,right,effects).native_value() == 9);
    CHECK(run_multiply(left,right,effects).native_value() == 36);
    CHECK(run_divide(left,right,effects).native_value() == 4);
    CHECK(run_remainder(left,right,effects).native_value() == 0);
    CHECK(effects == 5);
    CHECK(run_less(right,left,effects));
    CHECK(run_compare(left,right,effects).native_value() == 1);
    optional same(int64(12)), another_absent;
    CHECK(compare_numeric_equal_integer(left,same,effects));
    CHECK(!compare_numeric_equal_integer(left,right,effects));
    CHECK(compare_strict_identical_integer(left,same,effects));
    CHECK(compare_numeric_equal_integer(absent,another_absent,effects));
    CHECK(compare_strict_identical_integer(absent,another_absent,effects));
    CHECK(compare_numeric_not_equal_integer(absent,right,effects));
    CHECK(compare_strict_not_identical_integer(absent,right,effects));
    scpp::optional_float floating(scpp::float64(12)), absent_float;
    CHECK(compare_numeric_equal_float(left,floating,effects));
    CHECK(!compare_numeric_not_equal_float(left,floating,effects));
    CHECK(!compare_strict_identical_float(left,floating,effects));
    CHECK(compare_strict_not_identical_float(left,floating,effects));
    CHECK(compare_numeric_equal_float(absent,absent_float,effects));
    CHECK(compare_strict_identical_float(absent,absent_float,effects));
    optional alias(int64(12));
    CHECK(!compare_numeric_equal_integer(alias,alias,effects,true));
    CHECK(!alias.has_value());
    effects = 0;
    CHECK(!compare_numeric_equal_integer(absent,right,effects));
    CHECK(effects == 1); // RHS runs before states are compared, even with absent left.

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
    auto widened = reconstruct_nullable_float64(copy);
    CHECK(widened.require_value("widened").native_value() == 12.0);
    auto absent_widened = reconstruct_nullable_float64(absent);
    CHECK(!absent_widened.has_value());
    auto roundtrip = reconstruct_nullable_int64(widened);
    widened.reset();
    CHECK(roundtrip.require_value("roundtrip").native_value() == 12);
    scpp::result_integer successful(int64(12));
    auto result_widened = reconstruct_result_float64(successful);
    CHECK(result_widened.require_value("result").native_value() == 12.0);
    CHECK(reconstruct_result_int64(result_widened).require_value("roundtrip").native_value() == 12);
    int observations = 0, evaluations = 0;
    CHECK(null_strict_identical_left_integer(absent,observations));
    CHECK(null_strict_identical_right_integer(absent,observations));
    CHECK(!null_strict_identical_left_integer(zero,observations));
    CHECK(!null_strict_identical_right_integer(zero,observations));
    CHECK(null_strict_not_identical_left_integer(zero,observations));
    CHECK(null_strict_not_identical_right_integer(zero,observations));
    CHECK(!null_strict_not_identical_left_integer(absent,observations));
    CHECK(!null_strict_not_identical_right_integer(absent,observations));
    CHECK(null_strict_identical_left_text(absent_text,observations));
    CHECK(null_strict_identical_right_text(absent_text,observations));
    CHECK(!null_strict_identical_left_text(present_text,observations));
    CHECK(!null_strict_identical_right_text(present_text,observations));
    CHECK(null_strict_not_identical_left_text(([&]() -> const scpp::optional_text & {
        ++evaluations; return present_text;
    })(),observations));
    CHECK(null_strict_not_identical_right_text(present_text,observations));
    CHECK(!null_strict_not_identical_left_text(absent_text,observations));
    CHECK(!null_strict_not_identical_right_text(absent_text,observations));
    CHECK(observations == 16 && evaluations == 1);
    CHECK(allocations == before);
    effects = 0; bool caught = false;
    try { (void)run_add(absent,right,effects); } catch (...) { caught = true; }
    CHECK(caught && effects == 0);
    caught = false;
    try { (void)run_less(absent,right,effects); } catch (...) { caught = true; }
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
    scpp::optional_float huge(scpp::float64(1e300));
    optional destination(int64(42)); caught = false;
    try { destination = reconstruct_nullable_int64(huge); } catch (...) { caught = true; }
    CHECK(caught && destination.require_value("retained").native_value() == 42);
    CHECK(huge.require_value("source").native_value() == 1e300);
    scpp::result_float huge_result(scpp::float64(1e300));
    scpp::result_integer result_destination(int64(42)); caught = false;
    try { result_destination = reconstruct_result_int64(huge_result); } catch (...) { caught = true; }
    CHECK(caught && result_destination.require_value("retained").native_value() == 42);
    CHECK(huge_result.has_value());
    const auto failure = scpp::error_value::make(scpp::string("conversion failure"),int64(17),scpp::string("sample.phs"));
    scpp::result_integer failed(failure);
    scpp::result_float failed_float(failure);
    const auto direct_identity_before = allocations;
    CHECK(scpp::strict_identical(failure,failure));
    CHECK(!scpp::strict_not_identical(failure,failure));
    CHECK(allocations == direct_identity_before);
    effects = 0;
    const auto identity_before = allocations;
    CHECK(result_compare_strict_identical_float(failed,failed_float,effects));
    const auto identity_allocations = allocations - identity_before;
    CHECK(identity_allocations == 4); // Whole operand snapshots + owned state projections.
    CHECK(effects == 1);
    CHECK(!result_compare_strict_not_identical_float(failed,failed_float,effects));
    CHECK(result_compare_numeric_equal_float(failed,failed_float,effects));
    CHECK(!result_compare_numeric_not_equal_float(failed,failed_float,effects));
    for (int difference = 0; difference < 3; ++difference) {
        const auto different = scpp::error_value::make(
            scpp::string(difference == 0 ? "different failure" : "conversion failure"),
            int64(difference == 1 ? 18 : 17),
            scpp::string(difference == 2 ? "other.phs" : "sample.phs"));
        scpp::result_float different_result(different);
        CHECK(!result_compare_strict_identical_float(failed,different_result,effects));
        CHECK(result_compare_strict_not_identical_float(failed,different_result,effects));
        CHECK(result_compare_numeric_equal_float(failed,different_result,effects));
    }
    CHECK(!scpp::strict_identical(failure,scpp::error_value::no_error));
    CHECK(scpp::strict_identical(scpp::error_value::no_error,scpp::error_value::no_error));
    scpp::result_integer alias_error(failure);
    // RHS changes the source after the left snapshot. The saved left must stay an error.
    CHECK(!result_compare_strict_identical_integer(alias_error,alias_error,effects,true));
    CHECK(alias_error.has_value());
    CHECK(!result_compare_strict_identical_float(failed,result_widened,effects));
    std::printf("Result strict error identity allocations=%d; direct error comparison allocations=0\n",identity_allocations);
    const auto error_before = allocations;
    auto converted_failure = reconstruct_result_float64(failed);
    const auto error_allocations = allocations - error_before;
    CHECK(converted_failure.has_error() && failed.has_error());
    CHECK(error_allocations == 1); // One owned snapshot; destination consumes it.
    CHECK(failed.require_error("original").get_message().equals("conversion failure"));
    failed = int64(7);
    const auto saved_error = converted_failure.require_error("retained error");
    CHECK(saved_error.get_message().equals("conversion failure"));
    CHECK(saved_error.get_line().native_value() == 17);
    CHECK(saved_error.get_file().equals("sample.phs"));
    std::printf("Result error preservation allocations=%d (separate from success cases)\n",error_allocations);
    // Explicit transfer consumes an owned error, never an error borrowed from a Result.
    auto constructor_error = failure;
    auto assignment_error = failure;
    auto published_error = failure;
    auto published_assignment_error = failure;
    scpp::result_integer assigned_error(int64(42));
    const auto transfer_before = allocations;
    scpp::result_integer moved_error(static_cast<scpp::error_value&&>(constructor_error));
    assigned_error = static_cast<scpp::error_value&&>(assignment_error);
    scpp::result_vector_string published_failure(static_cast<scpp::error_value&&>(published_error));
    published_failure = static_cast<scpp::error_value&&>(published_assignment_error);
    CHECK(allocations == transfer_before);
    CHECK(moved_error.has_error() && assigned_error.has_error() && published_failure.has_error());
    constructor_error = scpp::error_value();
    assignment_error = scpp::error_value();
    published_error = scpp::error_value();
    published_assignment_error = scpp::error_value();
    CHECK(moved_error.require_error("moved constructor").get_message().equals("conversion failure"));
    CHECK(assigned_error.require_error("moved assignment").get_line().native_value() == 17);
    CHECK(published_failure.require_error("published move").get_file().equals("sample.phs"));
    caught = false;
    try { scpp::result_integer invalid(scpp::error_value{}); } catch (...) { caught = true; }
    CHECK(caught);
    caught = false;
    try { assigned_error = scpp::error_value{}; } catch (...) { caught = true; }
    CHECK(caught && assigned_error.has_error());
    CHECK(assigned_error.require_error("failed transfer").get_message().equals("conversion failure"));
    // Extraction returns an owned value that survives mutation/destruction of its carrier.
    int64 retained(0);
    { optional temporary(int64(42)); retained = temporary.require_value("retain"); temporary.reset(); }
    CHECK(retained.native_value() == 42);
    std::puts("PASS: public nullable arithmetic, ordered failures, take/reset, casts, conditions, comparisons, reconstruction, literal presence, owned lifetime; success allocations=0");
}
'''
    code = code.replace('CONDITION_NOT_ABSENT', negate('nullable<int64>')['result_expression'].replace('${result}', 'observe_condition(absent)'))
    caller = work / 'main.cpp'; caller.write_text(code)
    flags = ['-fsanitize=address,undefined','-fno-omit-frame-pointer'] if args.sanitize else []
    common = ['clang++','-std=c++23','-O1',*flags,'-I'+str(app/'public'),'-I'+str(sdk/'public')]
    start = time.perf_counter()
    objects = []
    for i,source in enumerate(manifest['sources']):
        obj = work / f'storage{i}.o'; objects.append(str(obj))
        subprocess.run([*common,'-I'+str(sdk/'include'),'-I'+str(app/'include'),'-c',str(app/source),'-o',str(obj)],check=True)
    storage_time = time.perf_counter()-start
    start = time.perf_counter()
    subprocess.run([*common,'-I'+str(root/'tests/runtime/native'),'-c',str(caller),'-o',str(work/'caller.o')],check=True)
    caller_time = time.perf_counter()-start
    subprocess.run(['clang++',*flags,str(work/'caller.o'),*objects,str(args.core.resolve()),'-pthread','-o',str(work/'proof')],check=True)
    subprocess.run([str(work/'proof')],check=True)
    (app/'lib').mkdir()
    subprocess.run(['ar','rcs',str(app/'lib/libcarrier.a'),*objects],check=True)
    seal_package(app,['carrier'])
    reused = concrete_types.generate(request,work/'reuse',sdk,[sdk,app],'clang++',[])
    assert reused['sources'] == []
    print(f'PASS: sealed application specialization reuse; storage compile={storage_time:.4f}s public caller compile={caller_time:.4f}s')
