"""Compiler-authored enum defaults reuse existing portable enum case constants."""
import argparse
import json
import shutil
import sys
from pathlib import Path
ROOT = Path(__file__).resolve().parents[2]
APP = ROOT / 'compiler/my-try'
TOOLS = ROOT / 'tools/php_portability'
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
out = p.parse_args().results.resolve()
(out / 'source').mkdir(parents=True, exist_ok=False)
FIELDS = [('compiler/my-try/03_parse/structures/structures_specialization.php',
  'type_use_modifier_node',
  'type_use_modifier_kind',
  'modifier',
  'by_value'),
 ('compiler/my-try/03_parse/structures/structures_object_type.php',
  'object_type_node',
  'object_type_operation',
  'operation',
  'checked_cast'),
 ('compiler/my-try/03_parse/structures/structures_probes.php',
  'value_probe_node',
  'value_probe_kind',
  'probe_kind',
  'isset'),
 ('compiler/my-try/03_parse/structures/structures_ownership_observation.php',
  'ownership_observation_node',
  'ownership_observation_kind',
  'operation',
  'derive_weak'),
 ('compiler/my-try/03_parse/types/catalog.php',
  'source_type_modifier_exposure',
  'type_use_modifier_kind',
  'modifier_kind',
  'by_value'),
 ('compiler/my-try/03_parse/types/model.php',
  'semantic_type_definition',
  'type_definition_origin',
  'definition_origin',
  'language'),
 ('compiler/my-try/03_parse/types/model.php',
  'floating_type_definition',
  'floating_format',
  'number_format',
  'binary'),
 ('compiler/my-try/03_parse/types/model.php',
  'abstract_nominal_type_definition',
  'nominal_type_kind',
  'nominal_kind_data',
  'structure'),
 ('compiler/my-try/03_parse/types/model.php',
  'abstract_template_type_definition',
  'nominal_type_kind',
  'result_kind_data',
  'structure'),
 ('compiler/my-try/03_parse/types/model.php',
  'result_type_definition',
  'result_kind',
  'family_kind',
  'structured_error'),
 ('compiler/my-try/03_parse/types/model.php',
  'ownership_type_definition',
  'ownership_kind',
  'family_kind',
  'shared'),
 ('compiler/my-try/04_analyze/conversions/model.php',
  'conversion_decision',
  'conversion_context',
  'context',
  'assignment'),
 ('compiler/my-try/04_analyze/conversions/model.php',
  'conversion_decision',
  'conversion_operation',
  'operation',
  'callable_wrap'),
 ('compiler/my-try/04_analyze/conversions/model.php',
  'truthiness_decision',
  'truthiness_operation',
  'operation',
  'boolean_identity'),
 ('compiler/my-try/04_analyze/operators/model.php',
  'operator_decision',
  'operator_kind',
  'source_operator',
  'index'),
 ('compiler/my-try/04_analyze/operators/model.php',
  'operator_decision',
  'operator_operation',
  'operation',
  'wrapper_equal'),
 ('compiler/my-try/04_analyze/prepare/data/async.php',
  'prepared_await',
  'await_operation',
  'operation',
  'suspend'),
 ('compiler/my-try/04_analyze/prepare/data/structures.php',
  'prepared_ownership_observation',
  'ownership_observation_kind',
  'operation',
  'derive_weak'),
 ('compiler/my-try/04_analyze/prepare/data/structures.php',
  'prepared_object_type',
  'object_type_operation',
  'operation',
  'checked_cast'),
 ('compiler/my-try/04_analyze/prepare/data/structures.php',
  'prepared_object_construction',
  'record_storage_kind',
  'storage',
  'inline_value'),
 ('compiler/my-try/04_analyze/prepare/data/structures.php',
  'prepared_record_construction',
  'record_storage_kind',
  'storage',
  'inline_value'),
 ('compiler/my-try/04_analyze/prepare/data/structures.php',
  'prepared_take',
  'take_operation',
  'operation',
  'nullable_value'),
 ('compiler/my-try/04_analyze/prepare/data/structures.php',
  'prepared_value_probe',
  'value_probe_kind',
  'operation',
  'isset'),
 ('compiler/my-try/04_analyze/prepare/data/structures.php',
  'prepared_coalesce_expression',
  'coalesce_presence',
  'presence',
  'always'),
 ('compiler/my-try/04_analyze/prepare/data/structures.php',
  'prepared_coalesce_expression',
  'value_boundary_operation',
  'left_boundary',
  'copy_value'),
 ('compiler/my-try/04_analyze/prepare/data/structures.php',
  'prepared_coalesce_expression',
  'value_boundary_operation',
  'fallback_boundary',
  'copy_value'),
 ('compiler/my-try/04_analyze/prepare/data/structures.php',
  'prepared_conditional_expression',
  'value_boundary_operation',
  'true_boundary',
  'copy_value'),
 ('compiler/my-try/04_analyze/prepare/data/structures.php',
  'prepared_conditional_expression',
  'value_boundary_operation',
  'fallback_boundary',
  'copy_value'),
 ('compiler/my-try/04_analyze/prepare/data/structures.php',
  'prepared_compound_assignment_expression',
  'compound_write_back',
  'write_back_mode',
  'converted_assignment'),
 ('compiler/my-try/04_analyze/prepare/data/structures.php',
  'prepared_binding',
  'binding_kind',
  'resolved_kind',
  'unresolved'),
 ('compiler/my-try/04_analyze/prepare/data/structures.php',
  'prepared_field_access',
  'record_storage_kind',
  'receiver_storage',
  'inline_value'),
 ('compiler/my-try/04_analyze/prepare/data/structures.php',
  'prepared_call_argument',
  'value_boundary_operation',
  'boundary',
  'copy_value'),
 ('compiler/my-try/04_analyze/prepare/data/structures.php',
  'prepared_control_transfer',
  'control_transfer_kind',
  'transfer_kind',
  'break_construct'),
 ('compiler/my-try/04_analyze/prepare/data/structures.php',
  'preparation_lookup',
  'preparation_lookup_kind',
  'kind',
  'property_name'),
 ('compiler/my-try/05_backend/cpp/type_bindings_catalog.php',
  'cpp_type_argument_binding',
  'cpp_type_argument_kind',
  'kind',
  'native_type'),
 ('compiler/my-try/05_backend/cpp/type_bindings_catalog.php',
  'cpp_type_modifier_binding',
  'type_use_modifier_kind',
  'modifier_kind',
  'by_value'),
 ('compiler/my-try/05_backend/cpp/structures.php',
  'cpp_type',
  'cpp_literal_kind',
  'literal',
  'signed_integer'),
 ('compiler/my-try/05_backend/cpp/structures.php',
  'cpp_artifact',
  'cpp_artifact_kind',
  'artifact_kind',
  'translation_unit'),
 ('compiler/my-try/compiler/runtime_catalog/structures.php',
  'runtime_type_definition_spec',
  'nominal_type_kind',
  'kind',
  'structure'),
 ('compiler/my-try/compiler/callables/structures.php', 'callable_parameter', 'passing_mode', 'mode', 'value')]
FIELDS.append(('compiler/my-try/05_backend/llvm/structures.php', 'llvm_parameter', 'passing_mode', 'mode', 'value'))
import re
enums = {}
for stage in ['03_parse', '04_analyze', '05_backend/cpp', 'compiler']:
    for path in (APP / stage).rglob('*.php'):
        for match in re.finditer(r'\benum\s+(\w+)[^{]*\{[^{}]*\}', path.read_text()):
            enums[match[1]] = match[0]
used = sorted({item[2] for item in FIELDS})
source = '<?php\nnamespace scpp\\compiler;\n' + '\n'.join(enums[name] for name in used)
source += '\nclass DefaultFacts {\n'
for index, (path, owner, kind, field, case) in enumerate(FIELDS):
    declaration = kind + ' $' + field + ' = ' + kind + '::' + case + ';'
    assert declaration in (ROOT / path).read_text(), declaration
    source += ' public ' + kind + ' $field_' + str(index) + ' = ' + kind + '::' + case + ';\n'
source += '}\n$facts = new DefaultFacts();\n'
for index, (_, _, kind, _, case) in enumerate(FIELDS):
    source += 'echo $facts->field_' + str(index) + ' === ' + kind + '::' + case + " ? '1' : '0';\n"
(out / 'source/main.php').write_text(source)
expected = b'1' * len(FIELDS)
with CommandRunner(out / 'logs', ROOT) as runner:
    publisher = out / 'publish.php'
    publisher.write_text((APP / 'tests/async_tasks_publish.php').read_text()
                         .replace("dirname(__DIR__) . '/boot.php'", repr(str(APP / 'boot.php')))
                         .replace('[$argv[3]]', "[$argv[3], 'strings']"))
    assert runner.run('host', ['php8.5', '-r', 'foreach(array_slice($argv,1) as $path){require $path;}',
                             TOOLS / 'runtime/bootstrap.php', APP / 'helpers/storage_abstract.php',
                             APP / 'helpers/storage.php', out / 'source/main.php']) == expected
    for route in ['v0.1', 'v0.2']:
        runner.run(route + '-convert', ['php8.5', TOOLS / 'convert.php', out / 'source', out / route, '--target=' + route])
    for route in ['v0.1', 'v0.2']:
        runner.run(route + '-framework', ['php8.5', TOOLS / 'install_native_runtime.php', out / route])
    def check(mode):
        if mode == 'legacy':
            project = out / 'v0.1'
            runner.run('legacy-init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], project, cli_reason='CLI fixture setup')
            config = json.loads((project / 'prism.json').read_text())
            config['runtime']['modules'] = ['compiler']
            config['build']['cxx'] = 'clang++-18'
            (project / 'prism.json').write_text(json.dumps(config))
            runner.run('legacy-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'], project, timeout=300, cli_reason='CLI fixture build')
            executable = project / '.prism/build/main'
        else:
            folder = out / mode
            (folder / 'source').mkdir(parents=True)
            shutil.copyfile(out / 'v0.2/main.phs', folder / 'source/main.phs')
            # Include the full exception framework, including its identity helper.
            shutil.copyfile(out / 'v0.2/scpp_framework/exceptions.phs', folder / 'source/exceptions.phs')
            runner.run(mode + '-publish', ['php8.5', publisher, folder,
                                           ROOT / 'runtime/metadata/modules', 'compiler', mode])
            manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
            build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
            executable = build['executable']
        assert runner.run(mode + '-run', [executable]) == expected
    results = run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['legacy', 'ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps([{'name':r.name, 'seconds':r.seconds, 'passed':r.error is None} for r in results], indent=2) + '\n')
