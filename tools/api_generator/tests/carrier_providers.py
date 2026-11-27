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
    compiler_root = ROOT / 'compiler'
    boot = (compiler_root / 'boot.php').read_text().split("require_once __DIR__ . '/generated/", 1)[0]
    runner.write_text(boot.replace('__DIR__', repr(str(compiler_root)))
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
    requested = publication['carriers']['specializations']
    assert sorted(c['name'] for c in default['carriers']) == sorted(
        f"{c['family']}_{c['payload']}" for c in requested)
    assert [c['name'] for c in default['carriers'] if c['instance_field']] == ['nullable_string']
    assert default['carrier_families']['result']['default_state'] == 'value'
    assert default['carrier_families']['result_or_bool']['states'] == {'value': 'payload', 'false': None, 'true': None}
    for carrier in default['carriers']:
        expected = metadata['carriers']['specialization_providers'].get(carrier['name'], 'core.carriers')
        assert default['submodules']['type_exports'][carrier['name']]['provider'] == expected
    assert default['submodules']['type_exports']['exception']['symbol'] == 'scpp::exception'
    capability = work / 'capability.php'
    capability.write_text(runner.read_text().split('echo json_encode')[0]
        + "$r=\\scpp\\compiler\\type_registry::specialize('result',\\scpp\\compiler\\type_registry::integer());\n"
        + "$s=\\scpp\\compiler\\type_registry::specialize('nullable',\\scpp\\compiler\\type_registry::string());\n"
        + "if (!$s->supports_instance_field() || $s->supports_static_property() || $t->supports_instance_field()) throw new RuntimeException('Carrier field capability mismatch');\n"
        + "if (!$t->supports_coalescing() || $r->supports_coalescing()) throw new RuntimeException('Carrier capability mismatch');\n")
    run(['php', capability, compiler])
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
        # Record decoding also consumes the relocated result provider through the registry.
        old_header = default['carriers_header']
        old_constant = f"public const ?string RECORD_RESULT_HEADER = '{old_header}';".encode()
        new_constant = f"public const ?string RECORD_RESULT_HEADER = '{header}';".encode()
        assert before['registry.php'].count(old_constant) == 1
        expected = before.copy()
        expected['registry.php'] = before['registry.php'].replace(old_constant, new_constant)
        assert {k:v for k,v in expected.items() if k != 'carriers.php'} == {k:v for k,v in after.items() if k != 'carriers.php'}
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

    # Run the same four source contexts with the explicit-only compiler publication.
    alternate_boot = work / 'explicit_boot.php'
    alternate_boot.write_text((compiler_root / 'boot.php').read_text()
        .replace("__DIR__ . '/generated/", repr(str(compiler)) + " . '/")
        .replace('__DIR__', repr(str(compiler_root))))
    run(['php', compiler_root / 'tests/php_carrier_implicit_matrix.php', alternate_boot])

    emit(metadata, publication)
    for invalid in ('states', 'default', 'provider', 'legacy_header', 'access', 'presence', 'sentinel'):
        m, p = copy.deepcopy(metadata), copy.deepcopy(publication)
        if invalid == 'access': m['carriers']['payload_access'] = 'unchecked_value'
        if invalid == 'presence': m['carriers']['presence']['value'] = 'is_false'
        if invalid == 'sentinel': m['carriers']['sentinels']['false']['factory'] = 'from_true'
        if invalid == 'states': m['carriers']['families']['nullable']['states']['error'] = 'exception'
        if invalid == 'default': m['carriers']['families']['result']['default_state'] = 'error'
        if invalid == 'provider': m['carriers']['provider'] = 'missing'
        if invalid == 'legacy_header': p['carriers']['header'] = 'scpp/wrong.hpp'
        stable = (compiler / 'types/carriers.php').read_bytes()
        emit(m, p, 1)
        assert (compiler / 'types/carriers.php').read_bytes() == stable

    absent = copy.deepcopy(publication); absent.pop('carriers'); absent.pop('hashes', None)
    # Retire functions whose core or PHP adapter result needs a carrier as well.
    absent['string']['functions'] = [name for name in absent['string']['functions']
        if name not in ('hex2bin', 'strpos', 'stripos', 'strrpos', 'strripos')]
    del absent['cli_helpers']['environment']
    absent['text_helpers'].remove('string_byte_find')
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
