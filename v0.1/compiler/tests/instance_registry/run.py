"""Exact typed instance allocation identities and candidate isolation."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import time

ROOT = Path(__file__).resolve().parents[3]
FILES = ['src/04_analyze/instantiate/data/state.php', 'src/04_analyze/instantiate/data/view.php', 'src/04_analyze/instantiate/data/result.php', 'src/04_analyze/instantiate/data/store.php']
DEPENDENCIES = ['src/01_prepare_inputs/read_sources/data/buffer.php', 'src/02_tokenize/structures.php', 'src/02_tokenize/store.php', 'src/02_tokenize/tokenize.php', 'src/03_parse/data/nodes.php', 'src/03_parse/data/tree.php', 'src/03_parse/utilities/binary_syntax.php', 'src/03_parse/data/expression_state.php', 'src/03_parse/handlers/expressions.php', 'src/03_parse/data/result.php', 'src/03_parse/handlers/statements.php', 'src/03_parse/handlers/control_statements.php', 'src/03_parse/handlers/declarations.php', 'src/03_parse/handlers/metaprogramming.php', 'src/03_parse/parse_file.php', 'src/03_parse/data/role_views.php', 'src/03_parse/utilities/metaprogramming_syntax.php', 'src/03_parse/utilities/struct_member_cursor.php', 'src/03_parse/utilities/syntax_access.php', 'src/03_parse/utilities/syntax_comparer.php', 'src/03_parse/data/store.php', 'src/03_parse/select_tasks.php', 'src/03_parse/join.php', 'src/03_parse/main_parse.php', 'src/04_analyze/collect_symbols/data/structures.php', 'src/04_analyze/collect_symbols/data/store.php', 'src/04_analyze/collect_symbols/data/result.php', 'src/04_analyze/collect_symbols/collect.php', 'src/04_analyze/collect_symbols/main_collect_symbols.php', 'src/04_analyze/type_model/data/semantic_modes.php', 'src/04_analyze/type_model/data/representations.php', 'src/04_analyze/type_model/data/lifecycle_roles.php', 'src/04_analyze/type_model/data/lifecycle.php', 'src/04_analyze/type_model/data/lifetime_contract.php', 'src/04_analyze/type_model/data/native_record_layout.php', 'src/04_analyze/type_model/data/resources.php', 'src/04_analyze/type_model/data/definitions.php', 'src/04_analyze/type_model/data/context.php', 'src/04_analyze/type_model/data/lifetime_policy_codec.php', 'src/04_analyze/type_model/data/catalog.php', 'src/01_prepare_inputs/load_runtime/utilities/catalog_syntax.php', 'src/04_analyze/type_model/data/type_record.php', 'src/04_analyze/type_model/result_contracts.php', 'src/04_analyze/type_model/data/store.php', 'src/04_analyze/resolve_types/utilities/type_cache.php', 'src/04_analyze/resolve_types/data/lifecycle_bodies.php', 'src/04_analyze/resolve_types/lifecycle_composition.php', 'src/04_analyze/type_model/data/records.php', 'src/04_analyze/resolve_types/record_definitions.php', 'src/04_analyze/instantiate/data/context.php', 'src/04_analyze/type_model/data/type_references.php', 'src/04_analyze/type_model/data/semantic_calls.php', 'src/04_analyze/type_model/data/callable_modes.php', 'src/04_analyze/type_model/data/callables.php', 'src/04_analyze/type_model/data/storage.php', 'src/04_analyze/type_model/data/generic.php', 'src/04_analyze/type_model/data/families.php', 'src/04_analyze/type_model/data/source_families.php', 'src/04_analyze/collect_symbols/data/provider_declaration.php', 'src/04_analyze/instantiate/identities.php', 'src/04_analyze/resolve_types/data/entry_selection.php', 'src/04_analyze/resolve_types/main_prepare_entry.php', 'src/01_prepare_inputs/load_runtime/main_load_runtime.php', 'src/04_analyze/resolve_types/data/entry_contract.php', 'src/04_analyze/resolve_symbols/data/declarations.php', 'src/04_analyze/resolve_symbols/utilities/declaration_lookup.php', 'src/04_analyze/resolve_symbols/utilities/function_lookup.php', 'src/04_analyze/resolve_symbols/data/structures.php', 'src/04_analyze/resolve_symbols/data/traversal.php', 'src/04_analyze/resolve_symbols/data/result.php', 'src/04_analyze/resolve_symbols/handlers/names.php', 'src/04_analyze/resolve_symbols/handlers/declarations.php', 'src/04_analyze/resolve_symbols/handlers/statements.php', 'src/04_analyze/resolve_symbols/handlers/expressions.php', 'src/04_analyze/resolve_symbols/body.php', 'src/04_analyze/resolve_symbols/utilities/resolution_validity.php', 'src/04_analyze/resolve_symbols/utilities/binding_coverage.php', 'src/04_analyze/resolve_symbols/data/store.php', 'src/04_analyze/resolve_symbols/select_tasks.php', 'src/04_analyze/resolve_symbols/join.php', 'src/04_analyze/resolve_symbols/main_resolve_symbols.php', 'src/04_analyze/check_templates/data/terms.php', 'src/04_analyze/check_templates/data/structures.php', 'src/04_analyze/check_templates/data/result.php', 'src/04_analyze/check_templates/data/interpretation.php', 'src/04_analyze/check_templates/terms.php', 'src/04_analyze/resolve_types/source_lifecycle.php', 'src/04_analyze/check_templates/body.php', 'src/04_analyze/check_templates/select_tasks.php', 'src/04_analyze/check_templates/join.php', 'src/04_analyze/check_templates/main_check_templates.php']
LOAD_ORDER = DEPENDENCIES + FILES


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--target-checkout', type=Path)
    parser.add_argument('--candidate-revision')
    args = parser.parse_args()
    out = args.results.resolve(); out.mkdir(parents=True, exist_ok=False)
    source = out / 'source'; source.mkdir()
    inputs = out / 'inputs'; inputs.mkdir()
    report = {'passed': False, 'commands': [], 'native_build_attempts': 0}

    def run(label, command, cwd=ROOT):
        start = time.monotonic()
        result = subprocess.run(list(map(str, command)), cwd=cwd, capture_output=True, text=True)
        for kind, value in [('stdout', result.stdout), ('stderr', result.stderr)]:
            (out / f'{label}.{kind}.log').write_text(value)
        if label == 'native-build': report['native_build_attempts'] += 1
        report['commands'].append({'label': label, 'command': list(map(str, command)),
                                  'seconds': round(time.monotonic() - start, 3), 'exit': result.returncode})
        (out / 'summary.json').write_text(json.dumps(report, indent=2) + '\n')
        assert result.returncode == 0, (label, result.stdout, result.stderr)
        return result.stdout

    expected=[True]*29
    shutil.copy2(ROOT/'compiler/reference/pre-rewrite/language/named_types.json',inputs/'catalog.json')
    calls=['\\instance_registry_test\\Probe::run();']
    for relative in DEPENDENCIES + FILES:
        dest=source/relative;dest.parent.mkdir(parents=True,exist_ok=True)
        shutil.copy2(ROOT/'compiler'/relative,dest)
    shutil.copy2(Path(__file__).parent/'probe.php',source/'probe.php')
    (source/'main.php').write_text('<?php\n'+'\n'.join(calls)+'\n')
    run('imports',['php',ROOT/'tools/php_portability/sync_imports.php',source])
    run('check',['php',ROOT/'tools/php_portability/check.php',source])
    php=['php','-r','foreach(array_slice($argv,1) as $p) { require $p; }',
         ROOT/'tools/php_portability/runtime/bootstrap.php',*[source/f for f in LOAD_ORDER],
         source/'probe.php',source/'main.php']
    def prove(label, command, want):
        actual=[json.loads(line) for line in run(label,command,out).splitlines()]
        assert actual==want,(label,actual,want)
    prove('php',php,expected)
    report['php_ready_epoch'] = time.time()
    report['php_ready_sha256'] = {f:hashlib.sha256((source/f).read_bytes()).hexdigest() for f in DEPENDENCIES+FILES}
    (out/'summary.json').write_text(json.dumps(report,indent=2)+'\n')
    generated=out/'phpp';conversion=['php',ROOT/'tools/php_portability/convert.php',source,generated]
    assert json.loads(run('convert',conversion))['converted']==len(DEPENDENCIES+FILES)+2
    assert json.loads(run('reuse',conversion))=={'converted':0,'reused':len(DEPENDENCIES+FILES)+2,'removed':0}
    run('runtime',['php',ROOT/'tools/php_portability/install_native_runtime.php',generated,'--json','--filesystem'])
    binary=None
    if args.target_checkout:
        target=json.loads((ROOT/'compiler/tools/portability_target.json').read_text())
        revision=args.candidate_revision or target['verified_commit'];checkout=args.target_checkout.resolve()
        assert len(revision)==40 and all(c in '0123456789abcdef' for c in revision)
        assert run('revision',['git','-C',checkout,'rev-parse','HEAD']).strip()==revision
        assert run('clean',['git','-C',checkout,'status','--porcelain']).strip()==''
        cli=checkout/'bin/scpp.php';run('init',['php',cli,'init','--php-profile=strict'],generated)
        config=json.loads((generated/'prism.json').read_text());config['build']['cxx']='clang++-18'
        config['runtime']['modules']=['json','filesystem'];(generated/'prism.json').write_text(json.dumps(config,indent=2)+'\n')
        run('native-build',['php',cli,'build','--build-runtime'],generated)
        binary=[generated/'.prism/build/main'];prove('native',binary,expected)
        report['target_revision']=revision
        assert run('clean-after',['git','-C',checkout,'status','--porcelain']).strip()==''
    (out/'expected.json').write_text(json.dumps(expected,indent=2)+'\n')
    report.update(passed=True,native=bool(binary),cases=len(expected),identity_outcomes=len(expected),
                  production_files=FILES,source_sha256={f:hashlib.sha256((source/f).read_bytes()).hexdigest() for f in DEPENDENCIES+FILES})
    (out/'summary.json').write_text(json.dumps(report,indent=2)+'\n')
    print(f'Instance registry: {len(expected)} outcomes passed; native={bool(binary)}')

if __name__=='__main__':main()
