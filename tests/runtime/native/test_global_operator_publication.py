"""Reuse normalized operators from a relocated sealed runtime; no adapter rebuild."""
import argparse
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile

root = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(root/'runtime/tools'))
import concrete_types
from public_package import seal_package
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--core', type=Path, required=True)
parser.add_argument('--tool', type=Path, required=True)
parser.add_argument('--sanitize', action='store_true')
args = parser.parse_args()
with tempfile.TemporaryDirectory(prefix='scpp-operator-publication-') as directory:
    stage = Path(directory)
    sdk = stage/'runtime'
    for name in ['public','include','metadata']:
        shutil.copytree(root/'runtime'/name, sdk/name)
    (sdk/'lib').mkdir(); (sdk/'tools').mkdir()
    shutil.copy2(args.core, sdk/'lib/libcore.a')
    shutil.copy2(args.tool, sdk/'tools/scpp_api_generator')
    seal_package(sdk, ['core'])
    request = stage/'request.json'
    request.write_text(json.dumps({'package':'app.operators', 'roots':[], 'apis':['index_path_store_mixed','index_path_store_dynamic','index_path_append_mixed','index_path_append_dynamic','keyed_path_erase_mixed','keyed_path_erase_dynamic','keyed_prefix_ready_mixed','keyed_prefix_ready_dynamic','keyed_path_probe_mixed','keyed_path_probe_dynamic','keyed_ready_mixed','keyed_ready_dynamic','keyed_probe_mixed','keyed_probe_dynamic','reject_empty_operand','require_write_key_mixed','mixed_predicate_bool','mixed_predicate_int','mixed_predicate_float','dbg_value_int64', 'dbg_value_float64', 'dbg_value_boolean', 'dbg_value_string', 'dbg_labelled_int64', 'dbg_labelled_float64', 'dbg_labelled_boolean', 'dbg_labelled_string','debug_flags','memory_get_usage','memory_get_usage_real','memory_get_peak_usage','memory_get_peak_usage_real','getenv','shell_exec','cli_argc','cli_argv','cli_args','microtime_string','microtime_float','add_int64','equal_mixed','equal_boolean','equal_string','add_int64','divide_int64','less_float64','cast_uint64_from_string','cast_string_from_mixed','cast_boolean_from_dynamic','cast_uint64_from_mixed','condition_truthy_string','condition_truthy_dynamic','concat_text_float64','strict_identical_mixed','strict_identical_float64','logical_xor_boolean','isset_dynamic','empty_mixed','clean_float64','strict_identical_boxed_mixed_string','strict_not_identical_boxed_dynamic_int64','concat_text_mixed','concat_text_dynamic','power_int64','shift_left_mixed','compare_uint64','compare_float64','negative_dynamic','complement_int64','post_increment_dynamic','add_mixed','divide_dynamic','numeric_compound_replacement_dynamic','concat_string','concat_text_int64']}))
    os.environ['SCPP_API_GENERATOR'] = str(sdk/'tools/scpp_api_generator')
    manifest = concrete_types.generate(request, stage/'app', sdk, [sdk], 'clang++', [])
    assert manifest['sources'] == []
    assert manifest['types']['vector<mixed>']['owner'] == 'runtime.core'
    assert 'debug_flags' in manifest['apis']
    assert all(api in manifest['apis'] for api in ['dbg_value_int64', 'dbg_value_float64', 'dbg_value_boolean', 'dbg_value_string', 'dbg_labelled_int64', 'dbg_labelled_float64', 'dbg_labelled_boolean', 'dbg_labelled_string'])
    assert all(api in manifest['apis'] for api in ['memory_get_usage','memory_get_usage_real','memory_get_peak_usage','memory_get_peak_usage_real'])
    assert manifest['types']['result_or_false<string>']['owner'] == 'runtime.core'
    assert all(api in manifest['apis'] for api in ['microtime_string', 'microtime_float', 'cli_argc', 'cli_argv', 'cli_args', 'getenv', 'shell_exec'])
    assert all(x in manifest['apis'] for x in ['add_int64','equal_mixed','equal_boolean','equal_string','divide_int64','less_float64','cast_uint64_from_string','cast_string_from_mixed','cast_boolean_from_dynamic','cast_uint64_from_mixed','condition_truthy_string','condition_truthy_dynamic','concat_text_float64','strict_identical_mixed','strict_identical_float64','logical_xor_boolean','isset_dynamic','empty_mixed','clean_float64','strict_identical_boxed_mixed_string','strict_not_identical_boxed_dynamic_int64','concat_text_mixed','concat_text_dynamic','power_int64','shift_left_mixed','compare_uint64','compare_float64','negative_dynamic','complement_int64','post_increment_dynamic','add_mixed','divide_dynamic','numeric_compound_replacement_dynamic','concat_string','concat_text_int64'])
    logical_request={'action':'resolve_operator','metadata':concrete_types.load_api_metadata(sdk),
                     'request':{'operation':'logical_not','operand':'dynamic'}}
    logical_plan=json.loads(subprocess.check_output([str(sdk/'tools/scpp_api_generator')],input=json.dumps(logical_request),text=True))
    assert logical_plan['api']=='condition_truthy_dynamic' and logical_plan['api'] in manifest['apis']
    logical_expression=logical_plan['result_expression'].replace('${result}',logical_plan['function']+'(scpp::dynamic(scpp::boolean(false)))')
    compound_request={'action':'resolve_compound','metadata':logical_request['metadata'],
                      'request':{'operation':'add_assign','target':'int64','right':'int64'}}
    compound_plan=json.loads(subprocess.check_output([str(sdk/'tools/scpp_api_generator')],input=json.dumps(compound_request),text=True))
    assert compound_plan['calculation']['api'] in manifest['apis']
    assert compound_plan['left_conversion']['kind']==compound_plan['right_conversion']['kind']=='identity'
    boxed_request={'action':'resolve_compound','metadata':logical_request['metadata'],
                   'request':{'operation':'add_assign','target':'dynamic','right':'dynamic'}}
    boxed_plan=json.loads(subprocess.check_output([str(sdk/'tools/scpp_api_generator')],input=json.dumps(boxed_request),text=True))
    assert boxed_plan['writeback_conversion']['kind']=='checked_saved_value'
    assert boxed_plan['writeback_conversion']['api'] in manifest['apis']
    peer_request={'action':'resolve_operator','metadata':logical_request['metadata'],
                  'request':{'operation':'add','left':'int64','right':'mixed'}}
    peer_plan=json.loads(subprocess.check_output([str(sdk/'tools/scpp_api_generator')],input=json.dumps(peer_request),text=True))
    assert peer_plan['api'] in manifest['apis']
    peer_left=peer_plan['left_conversion']['expression'].replace('${value}','scpp::int64(3)')
    peer_expression=peer_plan['function']+'('+peer_left+',value)'
    text_request={'action':'resolve_compound','metadata':logical_request['metadata'],
                  'request':{'operation':'concat_assign','target':'string','right':'float64'}}
    text_plan=json.loads(subprocess.check_output([str(sdk/'tools/scpp_api_generator')],input=json.dumps(text_request),text=True))
    assert text_plan['calculation']['api'] in manifest['apis']
    assert text_plan['right_conversion']['api'] in manifest['apis']
    text_expression=text_plan['calculation']['function']+'(scpp::string("x"),'+text_plan['right_conversion']['function']+'(scpp::float64(1.23456789)))'
    boxed_text_request={'action':'resolve_compound','metadata':logical_request['metadata'],
                        'request':{'operation':'concat_assign','target':'dynamic','right':'int64'}}
    boxed_text=json.loads(subprocess.check_output([str(sdk/'tools/scpp_api_generator')],input=json.dumps(boxed_text_request),text=True))
    assert 'snapshot_converted_left' in boxed_text['steps']
    for key in ['calculation','left_conversion','right_conversion']:
        assert boxed_text[key]['api'] in manifest['apis']
    boxed_text_calculation=boxed_text['calculation']['function']+'('+boxed_text['left_conversion']['function']+'(scpp::dynamic(scpp::int64(10))),'+boxed_text['right_conversion']['function']+'(scpp::int64(3)))'
    boxed_text_expression=boxed_text['writeback_conversion']['expression'].replace('${value}',boxed_text_calculation)
    (stage/'main.cpp').write_text('''#include "scpp/access/index_path_store_mixed.hpp"
#include "scpp/access/index_path_store_dynamic.hpp"
#include "scpp/access/index_path_append_mixed.hpp"
#include "scpp/access/index_path_append_dynamic.hpp"
#include "scpp/access/keyed_path_erase_mixed.hpp"
#include "scpp/access/keyed_path_erase_dynamic.hpp"
#include "scpp/access/keyed_prefix_ready_mixed.hpp"
#include "scpp/access/keyed_prefix_ready_dynamic.hpp"
#include "scpp/access/keyed_path_probe_mixed.hpp"
#include "scpp/access/keyed_path_probe_dynamic.hpp"
#include "scpp/access/keyed_ready_mixed.hpp"
#include "scpp/access/keyed_ready_dynamic.hpp"
#include "scpp/access/keyed_probe_mixed.hpp"
#include "scpp/access/keyed_probe_dynamic.hpp"
#include "scpp/access/reject_empty_operand.hpp"
#include "scpp/access/require_write_key_mixed.hpp"
#include "scpp/observations/is_bool_mixed.hpp"
#include "scpp/observations/is_int_mixed.hpp"
#include "scpp/observations/is_float_mixed.hpp"
#include "scpp/operators/add_int64.hpp"
#include "scpp/operators/equal_mixed.hpp"
#include "scpp/operators/equal_boolean.hpp"
#include "scpp/operators/equal_string.hpp"
#include "scpp/operators/add_mixed.hpp"
#include "scpp/operators/divide_dynamic.hpp"
#include "scpp/operators/numeric_compound_replacement_dynamic.hpp"
#include "scpp/operators/divide_int64.hpp"
#include "scpp/operators/less_float64.hpp"
#include "scpp/conversions/cast_uint64_from_string.hpp"
#include "scpp/conversions/cast_string_from_mixed.hpp"
#include "scpp/conversions/cast_boolean_from_dynamic.hpp"
#include "scpp/conversions/cast_uint64_from_mixed.hpp"
#include "scpp/conditions/condition_truthy_string.hpp"
#include "scpp/conditions/condition_truthy_dynamic.hpp"
#include "scpp/text/concat_text_float64.hpp"
#include "scpp/text/concat_text_int64.hpp"
#include "scpp/text/concat_strings.hpp"
#include "scpp/text/concat_text_mixed.hpp"
#include "scpp/text/concat_text_dynamic.hpp"
#include "scpp/operators/power_int64.hpp"
#include "scpp/operators/shift_left_mixed.hpp"
#include "scpp/operators/compare_uint64.hpp"
#include "scpp/operators/compare_float64.hpp"
#include "scpp/operators/negative_dynamic.hpp"
#include "scpp/operators/complement_int64.hpp"
#include "scpp/operators/post_increment_dynamic.hpp"
#include "scpp/identity/strict_identical_mixed.hpp"
#include "scpp/identity/strict_identical_float64.hpp"
#include "scpp/operators/logical_xor_boolean.hpp"
#include "scpp/observations/isset_dynamic.hpp"
#include "scpp/observations/empty_mixed.hpp"
#include "scpp/observations/clean_float64.hpp"
#include "scpp/time/microtime_string.hpp"
#include "scpp/time/microtime_float.hpp"
#include "scpp/cli/cli_argc.hpp"
#include "scpp/cli/cli_argv.hpp"
#include "scpp/cli/cli_args.hpp"
#include "scpp/process/getenv.hpp"
#include "scpp/process/shell_exec.hpp"
#include "scpp/debug/flags.hpp"
#include "scpp/debug/dbg_value_int64.hpp"
#include "scpp/memory/memory_get_usage.hpp"
#include "scpp/memory/memory_get_usage_real.hpp"
#include "scpp/memory/memory_get_peak_usage.hpp"
#include "scpp/memory/memory_get_peak_usage_real.hpp"
#include "scpp/identity/strict_identical_boxed_mixed_string.hpp"
#include "scpp/identity/strict_not_identical_boxed_dynamic_int64.hpp"
static_assert(scpp::debug::DBG_DEFAULT.native_value()==262343);
int main() {
    const scpp::mixed write_key(scpp::int64(1));
    scpp::require_write_key(write_key);
    scpp::mixed target;
    auto dynamic_target=scpp::make_dynamic_container();
    target.set(write_key,scpp::mixed(scpp::int64(3)));
    dynamic_target.set(write_key,scpp::dynamic(scpp::int64(3)));
    if (target.get(write_key).get_int()!=scpp::int64(3) || dynamic_target.get(write_key).get_int()!=scpp::int64(3)) return 92;
    if (!scpp::keyed_ready(target,scpp::boolean(false)) || !scpp::keyed_probe(target,write_key,scpp::boolean(false))) return 93;
    if (!scpp::keyed_ready(dynamic_target,scpp::boolean(false)) || !scpp::keyed_probe(dynamic_target,write_key,scpp::boolean(false))) return 94;
    scpp::vector_mixed saved_keys; saved_keys.append(write_key);
    scpp::vector_mixed no_keys;
    if (!scpp::keyed_prefix_ready(target,no_keys,scpp::boolean(false)) || !scpp::keyed_prefix_ready(dynamic_target,no_keys,scpp::boolean(false))) return 96;
    if (!scpp::keyed_path_probe(target,saved_keys,scpp::boolean(false)) || !scpp::keyed_path_probe(dynamic_target,saved_keys,scpp::boolean(false))) return 97;
    scpp::index_path_store(target,saved_keys,scpp::mixed(scpp::int64(12)));
    scpp::index_path_store(dynamic_target,saved_keys,scpp::dynamic(scpp::int64(12)));
    scpp::index_path_append(target,no_keys,scpp::mixed(scpp::int64(13)));
    scpp::index_path_append(dynamic_target,no_keys,scpp::dynamic(scpp::int64(13)));
    if (target.get(write_key).get_int()!=scpp::int64(12) || dynamic_target.get(write_key).get_int()!=scpp::int64(12)) return 98;
    scpp::keyed_path_erase(target,saved_keys); scpp::keyed_path_erase(dynamic_target,saved_keys);
    if (target.remove(write_key) || dynamic_target.remove(write_key)) return 95;
    const scpp::mixed missing_key(scpp::string("missing"));
    if (!scpp::mixed{}.get(missing_key).is_null() || !scpp::dynamic{}.get(missing_key).is_null()) return 91;
    if (!scpp::predicates::is_int(scpp::mixed(scpp::int64(0)))) return 90;

    scpp::debug::dbg_at("publication.phs",scpp::int32(1),scpp::int64(7));
    scpp::int64 destination(10);
    const auto saved_left=destination;
    destination=scpp::int64(100); const scpp::int64 saved_right(3);
    const auto replacement=COMPOUND_CALCULATION(saved_left,saved_right);
    destination=replacement;
    scpp::mixed value(scpp::int64(7));
    scpp::dynamic updated(scpp::int64(1)); const auto old=scpp::update_post_increment(updated);
    scpp::float64 reset_value(7.0); scpp::clean(reset_value);
    return scpp::numeric_add(scpp::int64(3),scpp::int64(4)).native_value()==7
        && static_cast<bool>(scpp::value_equal(value,value))
        && static_cast<bool>(scpp::value_equal(scpp::boolean(false),scpp::boolean(false)))
        && static_cast<bool>(scpp::value_equal(scpp::string("same"),scpp::string("same")))
        && scpp::numeric_divide(scpp::int64(8),scpp::int64(2)).native_value()==4
        && scpp::cast_uint64(scpp::mixed(scpp::float64(9223372036854775808.0))).native_value()==9223372036854775808ULL
        && static_cast<bool>(scpp::condition_truthy(scpp::string("false")))
        && static_cast<bool>(scpp::condition_truthy(scpp::make_dynamic_container()))
        && scpp::concat_text(scpp::float64(1.23456789)).equals("1.23457")
        && scpp::concat_text(scpp::mixed(scpp::int64(7))).equals("7")
        && scpp::concat_text(scpp::dynamic(scpp::boolean(false))).equals("")
        && static_cast<bool>(scpp::strict_identical(value,value))
        && static_cast<bool>(scpp::strict_identical(scpp::float64(0.0),scpp::float64(-0.0)))
        && static_cast<bool>(scpp::strict_identical(scpp::mixed(scpp::string("a")),scpp::string("a")))
        && static_cast<bool>(scpp::strict_not_identical(scpp::dynamic(scpp::float64(7.0)),scpp::int64(7)))
        && scpp::integer_power(scpp::int64(2),scpp::int64(10)).native_value()==1024
        && scpp::integer_shift_left(scpp::mixed(scpp::int64(1)),scpp::mixed(scpp::int64(3))).get_int().native_value()==8
        && scpp::numeric_compare(scpp::uint64(0),scpp::uint64(18446744073709551615ULL)).native_value()==-1
        && scpp::numeric_compare(scpp::float64(-0.0),scpp::float64(0.0)).native_value()==0
        && scpp::numeric_negative(scpp::dynamic(scpp::int64(7))).get_int().native_value()==-7
        && scpp::numeric_complement(scpp::int64(0)).native_value()==-1
        && static_cast<bool>(LOGICAL_NOT_EXPRESSION)
        && scpp::numeric_add(value,value).get_int().native_value()==14
        && scpp::numeric_divide(scpp::dynamic(scpp::float64(12)),scpp::dynamic(scpp::int64(3))).get_float().native_value()==4
        && BOXED_WRITEBACK(scpp::dynamic(scpp::int64(10)),scpp::dynamic(scpp::int64(13))).get_int().native_value()==13
        && PEER_EXPRESSION.get_int().native_value()==10
        && TEXT_EXPRESSION.equals("x1.23457")
        && BOXED_TEXT_EXPRESSION.get_string().equals("103")
        && scpp::microtime(scpp::boolean(true)).native_value()>0.0
        && scpp::microtime().byte_size().native_value()>11
        && scpp::cli_argc().native_value()==0
        && scpp::cli_argv().is_hash() && scpp::cli_args().is_hash()
        && scpp::getenv(scpp::string("")).is_false()
        && scpp::process_memory_usage().native_value()>=0
        && scpp::process_peak_memory_usage(scpp::boolean(true)).native_value()>=0
        && destination.native_value()==13
        && old.get_int().native_value()==1 && updated.get_int().native_value()==2
        && reset_value.native_value()==0.0
        && !static_cast<bool>(scpp::isset(scpp::dynamic{}))
        && static_cast<bool>(scpp::empty(scpp::mixed(scpp::string(""))))
        && static_cast<bool>(scpp::logical_xor(scpp::boolean(false),scpp::boolean(true)))
        && scpp::cast_string(value).equals("7")
        && static_cast<bool>(scpp::cast_boolean(scpp::dynamic(scpp::int64(1))))
        && scpp::cast_uint64(scpp::string("18446744073709551615")).native_value()==18446744073709551615ULL
        && static_cast<bool>(scpp::numeric_less(scpp::float64(1.0),scpp::float64(2.0))) ? 0 : 1;
}
'''.replace('LOGICAL_NOT_EXPRESSION',logical_expression).replace('COMPOUND_CALCULATION',compound_plan['calculation']['function']).replace('BOXED_WRITEBACK',boxed_plan['writeback_conversion']['function']).replace('PEER_EXPRESSION',peer_expression).replace('BOXED_TEXT_EXPRESSION',boxed_text_expression).replace('TEXT_EXPRESSION',text_expression))
    flags = ['-fsanitize=address,undefined', '-fno-sanitize-recover=all'] if args.sanitize else []
    subprocess.run(['clang++','-std=c++23','-O2',*flags,'-I'+str(sdk/'public'),str(stage/'main.cpp'),
        str(sdk/'lib/libcore.a'),'-fuse-ld=mold','-o',str(stage/'proof')], check=True)
    subprocess.run([stage/'proof'], check=True)
    print('PASS: sealed relocated operator import, zero generated units, public-only linked caller')
