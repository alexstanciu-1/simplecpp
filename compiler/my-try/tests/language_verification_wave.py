"""Run the bounded first verification wave through existing shared-runner suites.

Two subprocess slots reserve twelve workers each. A separate eight-worker strict
replay may run concurrently. This does not rebuild the compiler or run LLVM/STAN.
Suite success is evidence input, not a claim that a checklist obligation is proved.
"""
import argparse
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime, timezone
import json
from pathlib import Path
import subprocess
import sys


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', required=True, type=Path)
    parser.add_argument('--wave', choices=['first', 'remaining'], default='first')
    args = parser.parse_args()
    output = args.results.resolve()
    output.mkdir(parents=True, exist_ok=False)
    tests = Path(__file__).resolve().parent
    records = '''finally exceptions error_values echo callable_results
        callable_reference_returns callable_inputs void_returns callable_storage
        empty_callables contextual_closures reference_captures closures records
        property_defaults data_records derived_data constructors methods
        static_members static_redeclarations late_static method_parameters
        method_returns qualified_static_calls qualified_static_members destructors
        nullable_handles nullable_records collection_probes property_mutations
        inheritance abstract_dispatch callable_variance interfaces shared_upcasts
        shared_joins object_types interface_handles traits'''.split()
    commands = [(name, [sys.executable, str(tests / (name + '.py')),
                       '--results', str(output / name), '--no-pch'])
                for name in ['functions', 'iteration']]
    commands += [('records-' + suite, [sys.executable, str(tests / 'records.py'),
                 '--suite', suite, '--no-pch', '--results',
                 str(output / ('records-' + suite))]) for suite in records]
    commands += [(name, [sys.executable, str(tests / (name + '.py')),
                        '--results', str(output / name)])
                 for name in ['runtime_strings', 'runtime_assets', 'native_invalidation']]
    if args.wave == 'remaining':
        no_pch = set('cleanup container_calls container_reads container_regression '
                     'container_writes control_flow data_initialization dynamic_families '
                     'json_api keyed_paths mixed nullable results'.split())
        names = """cleanup compound_effects container_calls container_reads
            container_regression container_writes control_flow data_initialization
            declaration_model dynamic_families eager_binary enums field_container_probes
            field_container_writes index_compound indexed_properties integer_widths
            json_api keyed_paths mixed mixed_layout mysql_rows namespace_bodies
            namespace_identity namespaces native_cache nullable output_publication
            ownership project_cpp references results runtime_builders runtime_catalog
            runtime_cli runtime_datetime runtime_environment runtime_exports
            runtime_filesystem runtime_generics runtime_handles runtime_memory
            runtime_module_build runtime_observation runtime_regex runtime_source
            runtime_types source_constants string_identity""".split()
        commands = [(name, [sys.executable, str(tests / (name + '.py')),
                           '--results', str(output / name)] +
                     (['--no-pch'] if name in no_pch else [])) for name in names]
    (output / 'commands.json').write_text(json.dumps(commands, indent=2) + '\n')

    def execute(item):
        name, command = item
        record = dict(name=name, command=command,
                      started=datetime.now(timezone.utc).isoformat())
        with (output / (name + '.log')).open('wb') as log:
            try:
                result = subprocess.run(command, stdout=log, stderr=subprocess.STDOUT,
                                        cwd=tests.parents[2], timeout=3600)
                record['exit_code'] = result.returncode
            except (OSError, subprocess.TimeoutExpired) as error:
                record['harness_error'] = str(error)
        record['finished'] = datetime.now(timezone.utc).isoformat()
        (output / (name + '.outcome.json')).write_text(json.dumps(record, indent=2) + '\n')
        print(name, record.get('exit_code', record.get('harness_error')), flush=True)
        return record

    with ThreadPoolExecutor(max_workers=2) as pool:
        outcomes = list(pool.map(execute, commands))
    (output / 'outcomes.json').write_text(json.dumps(outcomes, indent=2) + '\n')
    return int(any(row.get('exit_code') != 0 for row in outcomes))


if __name__ == '__main__':
    sys.exit(main())
