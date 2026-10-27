"""Compose bounded text conversion and ordered string compound replacement."""
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
metadata = json.loads((root/'runtime/metadata/public_api.json').read_text())
publication = json.loads((root/'runtime/public/specializations.json').read_text())
types = ['int8','int16','int32','int64','uint8','uint16','uint32','float64','boolean','string','null']
def call(data, error=None):
    result = subprocess.run([str(args.tool)], input=json.dumps(data), text=True, capture_output=True)
    if error:
        assert result.returncode and error in result.stderr, result.stderr
        return
    assert result.returncode == 0, result.stderr
    return json.loads(result.stdout)
def resolve(left, right, compound=False, meta=metadata, error=None):
    request = {'operation':'concat_assign' if compound else 'concat', 'right':right,
               'target' if compound else 'left':left}
    return call({'action':'resolve_compound' if compound else 'resolve_operator',
                 'metadata':meta, 'request':request}, error)
for left in types:
    for right in types:
        plan = resolve(left,right)
        assert plan['api'] == 'concat_string' and plan['result_type'] == 'string'
        for side, source in [('left',left),('right',right)]:
            conversion = plan[side+'_conversion']
            assert conversion['kind'] == ('identity' if source == 'string' else 'call')
            if source != 'string': assert conversion['api'] == 'concat_text_'+source
plans = {t:resolve('string',t,True) for t in types}
for excluded in ['uint64','mixed','dynamic','error_value']:
    resolve('string',excluded,error='Unsupported operator')
    resolve('string',excluded,True,error='Unsupported compound operands')
for excluded in ['int64','float64','boolean','mixed','dynamic']:
    resolve(excluded,'string',True,error='Unsupported compound operands')
changed = copy.deepcopy(metadata)
changed['function_sets']['concat_text']['function']['operation']['returns'] = 'int64'
resolve('float64','string',meta=changed,error='conversion signature mismatch')
changed = copy.deepcopy(metadata)
changed['function_sets']['concat_text']['function']['operation']['parameters'][0]['passing'] = 'ref'
resolve('int64','string',meta=changed,error='conversion signature mismatch')
# The operation's context owner is metadata; replacing it with an incompatible set rejects.
changed = copy.deepcopy(metadata)
changed['global_operators']['operations']['concat']['operand_conversion_function_set'] = 'condition_truthy'
resolve('int64','string',meta=changed,error='conversion signature mismatch')
headers, apis = set(), set()
for plan in plans.values():
    for part in [plan['calculation'],plan['left_conversion'],plan['right_conversion']]:
        if 'header' in part: headers.add(part['header'])
        if 'api' in part: apis.add(part['api'])
assert call({'metadata':metadata,'request':{'roots':[],'apis':sorted(apis)},'imports':{},
             'leaves':{},'layouts':{},'names':{},'api_imports':publication['apis']})['units'] == []
print('PASS: 121 scalar text pairs, 11 string compounds, contextual conversion signatures and publication reuse')
if not args.core: raise SystemExit(0)
def convert(plan, operand):
    return operand if plan['kind'] == 'identity' else plan['function']+'('+operand+')'
def lower(plan):
    assert plan['left_conversion']['kind'] == 'identity'
    steps = {
        'capture_destination_and_retain_owners':'auto &destination=capture();',
        'snapshot_left':'const auto old_left=destination;',
        'convert_left':'const auto &left=old_left;',
        'evaluate_right':'const auto right_value=rhs();',
        'convert_right':'const auto right='+convert(plan['right_conversion'],'right_value')+';',
        'calculate':'auto calculated='+plan['calculation']['function']+'(left,right);',
        'convert_writeback':'const auto &replacement=calculated;',
        'commit_destination':'destination=replacement;',
        'return_value_snapshot':'return calculated;',
    }
    assert plan['writeback_conversion']['kind'] == 'identity'
    return '\n'.join(steps[s] for s in plan['steps'])
source = '#include "public_api_guard.hpp"\n'+''.join('#include "'+h+'"\n' for h in sorted(headers))
source += '''#include <cstdio>
#include <cstdlib>
#include <new>
using namespace scpp;
static unsigned allocations=0;
static bool counting=false, fail_next=false;
void *operator new(std::size_t n) { if(fail_next) { fail_next=false; throw std::bad_alloc(); } if(counting) ++allocations; if(auto p=std::malloc(n?n:1)) return p; throw std::bad_alloc(); }
void operator delete(void *p) noexcept { std::free(p); }
void operator delete(void *p,std::size_t) noexcept { std::free(p); }
#define CHECK(x) do { if(!(x)) { std::fprintf(stderr,"text plan failure %d\\n",__LINE__); std::abort(); } } while(false)
int main() {
'''
samples = {t:(t+'(-2)','-2') for t in types[:4]}
samples.update({t:(t+'(2)','2') for t in types[4:7]})
samples.update(float64=('float64(1.23456789)','1.23457'),boolean=('boolean(true)','1'),string=('string("R")','R'),null=('null_t{}',''))
for t,(value,text) in samples.items():
    source += '{ string target("L"); int captures=0,evaluations=0;\n'
    source += 'auto capture=[&]() -> auto & { ++captures; return target; };\n'
    source += 'auto rhs=[&]() { ++evaluations; target=string("changed"); return '+value+'; };\n'
    source += 'allocations=0; counting=true; const auto result=[&]() {\n'+lower(plans[t])+'\n}(); counting=false;\n'
    source += 'CHECK(allocations==0 && captures==1 && evaluations==1); CHECK(target.equals("L'+text+'"));\n'
    source += 'target=string("later"); CHECK(result.equals("L'+text+'")); }\n'
    # Also exercise the same conversion on the left, before RHS effects.
    plan = resolve(t,'string')
    source += '{ const auto input='+value+'; const auto left='+convert(plan['left_conversion'],'input')+';\n'
    source += 'const auto result='+plan['function']+'(left,string("R")); CHECK(result.equals("'+text+'R")); }\n'
source += '''{ string target("a\\0b",uint64(3)); auto capture=[&]() -> auto & { return target; };
auto rhs=[&]() { target=string("changed"); return string("é"); };
const auto result=[&]() {\n'''+lower(plans['string'])+'''
}(); CHECK(result.equals(string("a\\0bé",uint64(5)))); CHECK(target.equals(result)); }
'''
# Failure before RHS and failure during calculation must preserve the right state.
for phase in ['snapshot','calculation','rhs']:
    source += '{ string target("abcdefghijklmnopqrstuvwxyz0123456789"); int evaluations=0; bool failed=false;\n'
    source += 'auto capture=[&]() -> auto & { return target; };\n'
    source += 'auto rhs=[&]() -> string { ++evaluations; target=string("changed"); '
    source += 'throw 7;' if phase == 'rhs' else ('fail_next=true; return string("R");' if phase == 'calculation' else 'return string("R");')
    source += ' }; '+('fail_next=true;' if phase == 'snapshot' else '')+' try { (void)[&]() {\n'+lower(plans['string'])+'\n}(); } catch (...) { failed=true; }\n'
    source += 'fail_next=false; CHECK(failed && evaluations=='+('0' if phase == 'snapshot' else '1')+');\n'
    source += 'CHECK(target.equals("'+('abcdefghijklmnopqrstuvwxyz0123456789' if phase == 'snapshot' else 'changed')+'")); }\n'
# A left text conversion allocation failure must suppress RHS evaluation.
left_plan=resolve('int64','string')
source += '{ int effects=0; bool failed=false; const int64 value(-9223372036854775807LL-1); fail_next=true; try {\n'
source += 'const auto left='+convert(left_plan['left_conversion'],'value')+'; ++effects; (void)left;\n'
source += '} catch (...) { failed=true; } fail_next=false; CHECK(failed && effects==0); }\n'
# A right conversion failure retains its earlier mutation of the destination.
source += '{ string target("L"); bool failed=false; auto capture=[&]() -> auto & { return target; };\n'
source += 'auto rhs=[&]() { target=string("changed"); fail_next=true; return int64(-9223372036854775807LL-1); };\n'
source += 'try { (void)[&]() {\n'+lower(plans['int64'])+'\n}(); } catch (...) { failed=true; }\n'
source += 'fail_next=false; CHECK(failed && target.equals("changed")); }\n'
source += '{ string target("self"); auto capture=[&]() -> auto & { return target; }; auto rhs=[&]() { return target; };\n'
source += 'const auto result=[&]() {\n'+lower(plans['string'])+'\n}(); CHECK(target.equals("selfself") && result.equals(target)); }\n'
# Calculation boundary allocation parity with the existing primitive string API.
source += '''{ const string a("abcdefghijklmnopqrstuvwxyz0123456789"), b("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789");
allocations=0; counting=true; const auto primitive=a+b; counting=false; const auto baseline=allocations;
allocations=0; counting=true; const auto composed=concat_strings(a,b); counting=false;
CHECK(composed.equals(primitive) && allocations==baseline);
std::printf("long calculation allocations: primitive=%u, published=%u\\n",baseline,allocations); }
std::puts("PASS: text conversion ordering, snapshots, embedded bytes, failure effects; short compounds allocate zero"); }
'''
with tempfile.TemporaryDirectory(prefix='scpp-text-plan-') as directory:
    stage=Path(directory); (stage/'main.cpp').write_text(source)
    flags=['-fsanitize=address,undefined','-fno-sanitize-recover=all'] if args.sanitize else []
    start=time.perf_counter()
    subprocess.run(['clang++','-std=c++23','-O2',*flags,'-I'+str(root/'runtime/public'),'-I'+str(root/'tests/runtime/native'),str(stage/'main.cpp'),str(args.core.resolve()),'-fuse-ld=mold','-o',str(stage/'main')],check=True)
    print(json.dumps({'public_proof_compile_and_link_seconds':time.perf_counter()-start}))
    subprocess.run([stage/'main'],check=True)
