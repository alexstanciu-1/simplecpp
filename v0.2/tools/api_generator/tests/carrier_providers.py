"""Carrier provider exports, metadata state policies and generated compiler registration."""
import argparse
import copy
import json
import os
from pathlib import Path
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[3]


def run(args, expected=0):
    result = subprocess.run(list(map(str, args)), capture_output=True, text=True,
                            env={**os.environ, 'XDEBUG_MODE': 'off'}, timeout=120)
    assert result.returncode == expected, (args, result.stdout, result.stderr)
    return result.stdout


def proof(work):
    work.mkdir(parents=True, exist_ok=True)
    metadata = json.loads((ROOT / 'runtime/metadata/runtime.json').read_text())
    publication = json.loads((ROOT / 'runtime/metadata/publication.json').read_text())
    publication['carriers'].pop('header', None)
    generator = ROOT / 'tools/api_generator/generate.php'
    facts = ROOT / 'build/clang/native-layout/facts.json'
    context = facts.with_name('context.json')
    mp, pp = work / 'metadata.json', work / 'publication.json'
    runtime, compiler = work / 'runtime', work / 'compiler'
    runner = work / 'resolve.php'
    runner.write_text('<?php\n' + ''.join('require ' + repr(str(ROOT / 'compiler/types' / (name + '.php'))) + ';\n'
        for name in ['conversion', 'definition', 'vector', 'carrier', 'hash', 'boxed'])
        + "foreach (['int64','null','bool','uint8','float64','string','registry'] as $name) require $argv[1].'/types/'.$name.'.php';\n"
        + "$t=\\scpp\\compiler\\type_registry::specialize('nullable',\\scpp\\compiler\\type_registry::integer());\n"
        + "echo json_encode($t === null ? null : [$t->name,$t->cpp_type(),$t->cpp_headers()]);\n")

    def emit(m, p, expected=0):
        mp.write_text(json.dumps(m)); pp.write_text(json.dumps(p))
        run(['php', generator, 'emit-runtime', mp, pp, facts, context, runtime], expected)
        run(['php', generator, 'emit-compiler', mp, pp, facts, context, compiler], expected)
        if expected == 0:
            return json.loads((runtime / 'prepared.json').read_text())

    default = emit(metadata, publication)
    assert len(default['carriers']) == 16
    assert default['carrier_families']['result']['default_state'] == 'value'
    assert default['carrier_families']['result_or_bool']['states'] == {'value': 'payload', 'false': None, 'true': None}
    for carrier in default['carriers']:
        assert default['submodules']['type_exports'][carrier['name']]['provider'] == 'core.carriers'
    assert default['submodules']['type_exports']['error_t']['symbol'] == 'scpp::error_t'
    before = {p.name: p.read_bytes() for p in (compiler / 'types').glob('*.php')}
    for variant in ('relocated', 'shared_vector'):
        m = copy.deepcopy(metadata)
        if variant == 'relocated': m['submodules']['core.carriers']['header'] = 'scpp/carriers/value_or.hpp'
        else: m['carriers']['provider'] = 'core.vectors'
        plan = emit(m, publication)
        provider = plan['submodules']['type_exports']['nullable_int64']['provider']
        header = plan['submodules']['nodes'][provider]['header']
        assert json.loads(run(['php', runner, compiler])) == ['nullable<int64>', 'scpp::nullable_int64', [header]]
        after = {p.name: p.read_bytes() for p in (compiler / 'types').glob('*.php')}
        assert {k:v for k,v in before.items() if k != 'carriers.php'} == {k:v for k,v in after.items() if k != 'carriers.php'}
        assert not (runtime / 'public/scpp/carriers.hpp').exists()
        consumer = work / 'consumer.cpp'
        consumer.write_text(f'#include <{header}>\nvoid f(scpp::nullable_int64, scpp::result_string, scpp::result_or_false_bool_t, scpp::result_or_bool_vector_string) {{}}\n')
        run(['clang++', '-std=c++23', '-stdlib=libc++', '-Werror', '-fsyntax-only', '-I'+str(runtime / 'public'), consumer])
        assert 'template<' not in (runtime / 'public' / header).read_text()

    explicit = copy.deepcopy(metadata)
    explicit['carriers']['extraction'] = 'explicit_checked_one_layer_value_copy'
    emit(explicit, publication)
    explicit_code = (compiler / 'types/carriers.php').read_text()
    assert 'function implicit_extraction(): bool\n\t{\n\t\treturn false;' in explicit_code
    assert 'function implicit_extraction(): bool\n\t{\n\t\treturn true;' not in explicit_code

    emit(metadata, publication)
    for invalid in ('states', 'default', 'provider', 'legacy_header', 'access', 'presence', 'sentinel'):
        m, p = copy.deepcopy(metadata), copy.deepcopy(publication)
        if invalid == 'access': m['carriers']['payload_access'] = 'unchecked_value'
        if invalid == 'presence': m['carriers']['presence']['value'] = 'is_false'
        if invalid == 'sentinel': m['carriers']['sentinels']['false']['factory'] = 'from_true'
        if invalid == 'states': m['carriers']['families']['nullable']['states']['error'] = 'error_t'
        if invalid == 'default': m['carriers']['families']['result']['default_state'] = 'error'
        if invalid == 'provider': m['carriers']['provider'] = 'missing'
        if invalid == 'legacy_header': p['carriers']['header'] = 'scpp/wrong.hpp'
        stable = (compiler / 'types/carriers.php').read_bytes()
        emit(m, p, 1)
        assert (compiler / 'types/carriers.php').read_bytes() == stable

    absent = copy.deepcopy(publication); absent.pop('carriers'); absent.pop('hashes', None)
    removed = emit(metadata, absent)
    assert 'nullable_int64' not in removed['submodules']['type_exports']
    assert not (runtime / 'public/scpp/carriers.hpp').exists()
    assert json.loads(run(['php', runner, compiler])) is None
    emit(metadata, publication)
    assert json.loads(run(['php', runner, compiler]))[1] == 'scpp::nullable_int64'
    print('Carrier state policies, export placement, shared providers and retirement/re-addition passed')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        proof(args.output.resolve())
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-carrier-providers-') as directory:
            proof(Path(directory))
