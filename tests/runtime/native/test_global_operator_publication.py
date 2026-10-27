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
    request.write_text(json.dumps({'package':'app.operators', 'roots':[], 'apis':['add_int64','equal_mixed','add_int64','divide_int64','less_float64','cast_uint64_from_string','cast_string_from_mixed','cast_boolean_from_dynamic','cast_uint64_from_mixed','condition_truthy_string','condition_truthy_dynamic','concat_text_float64','strict_identical_mixed','strict_identical_float64','logical_xor_boolean','isset_dynamic','empty_mixed','clean_float64','strict_identical_boxed_mixed_string','strict_not_identical_boxed_dynamic_int64']}))
    os.environ['SCPP_API_GENERATOR'] = str(sdk/'tools/scpp_api_generator')
    manifest = concrete_types.generate(request, stage/'app', sdk, [sdk], 'clang++', [])
    assert manifest['sources'] == []
    assert all(x in manifest['apis'] for x in ['add_int64','equal_mixed','divide_int64','less_float64','cast_uint64_from_string','cast_string_from_mixed','cast_boolean_from_dynamic','cast_uint64_from_mixed','condition_truthy_string','condition_truthy_dynamic','concat_text_float64','strict_identical_mixed','strict_identical_float64','logical_xor_boolean','isset_dynamic','empty_mixed','clean_float64','strict_identical_boxed_mixed_string','strict_not_identical_boxed_dynamic_int64'])
    (stage/'main.cpp').write_text('''#include "scpp/operators/add_int64.hpp"
#include "scpp/operators/equal_mixed.hpp"
#include "scpp/operators/divide_int64.hpp"
#include "scpp/operators/less_float64.hpp"
#include "scpp/conversions/cast_uint64_from_string.hpp"
#include "scpp/conversions/cast_string_from_mixed.hpp"
#include "scpp/conversions/cast_boolean_from_dynamic.hpp"
#include "scpp/conversions/cast_uint64_from_mixed.hpp"
#include "scpp/conditions/condition_truthy_string.hpp"
#include "scpp/conditions/condition_truthy_dynamic.hpp"
#include "scpp/text/concat_text_float64.hpp"
#include "scpp/identity/strict_identical_mixed.hpp"
#include "scpp/identity/strict_identical_float64.hpp"
#include "scpp/operators/logical_xor_boolean.hpp"
#include "scpp/observations/isset_dynamic.hpp"
#include "scpp/observations/empty_mixed.hpp"
#include "scpp/observations/clean_float64.hpp"
#include "scpp/identity/strict_identical_boxed_mixed_string.hpp"
#include "scpp/identity/strict_not_identical_boxed_dynamic_int64.hpp"
int main() {
    scpp::mixed value(scpp::int64(7));
    scpp::float64 reset_value(7.0); scpp::clean(reset_value);
    return scpp::numeric_add(scpp::int64(3),scpp::int64(4)).native_value()==7
        && static_cast<bool>(scpp::boxed_equal(value,value))
        && scpp::numeric_divide(scpp::int64(8),scpp::int64(2)).native_value()==4
        && scpp::cast_uint64(scpp::mixed(scpp::float64(9223372036854775808.0))).native_value()==9223372036854775808ULL
        && static_cast<bool>(scpp::condition_truthy(scpp::string("false")))
        && static_cast<bool>(scpp::condition_truthy(scpp::make_dynamic_container()))
        && scpp::concat_text(scpp::float64(1.23456789)).equals("1.23457")
        && static_cast<bool>(scpp::strict_identical(value,value))
        && static_cast<bool>(scpp::strict_identical(scpp::float64(0.0),scpp::float64(-0.0)))
        && static_cast<bool>(scpp::strict_identical(scpp::mixed(scpp::string("a")),scpp::string("a")))
        && static_cast<bool>(scpp::strict_not_identical(scpp::dynamic(scpp::float64(7.0)),scpp::int64(7)))
        && reset_value.native_value()==0.0
        && !static_cast<bool>(scpp::isset(scpp::dynamic{}))
        && static_cast<bool>(scpp::empty(scpp::mixed(scpp::string(""))))
        && static_cast<bool>(scpp::logical_xor(scpp::boolean(false),scpp::boolean(true)))
        && scpp::cast_string(value).equals("7")
        && static_cast<bool>(scpp::cast_boolean(scpp::dynamic(scpp::int64(1))))
        && scpp::cast_uint64(scpp::string("18446744073709551615")).native_value()==18446744073709551615ULL
        && static_cast<bool>(scpp::numeric_less(scpp::float64(1.0),scpp::float64(2.0))) ? 0 : 1;
}
''')
    flags = ['-fsanitize=address,undefined', '-fno-sanitize-recover=all'] if args.sanitize else []
    subprocess.run(['clang++','-std=c++23','-O2',*flags,'-I'+str(sdk/'public'),str(stage/'main.cpp'),
        str(sdk/'lib/libcore.a'),'-fuse-ld=mold','-o',str(stage/'proof')], check=True)
    subprocess.run([stage/'proof'], check=True)
    print('PASS: sealed relocated operator import, zero generated units, public-only linked caller')
