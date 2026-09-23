"""Run registered rewrite-stage outcome proofs; readiness must match the registered sources."""
import argparse
import importlib.util
import json
from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[2]
STAGES = [('read_manifest', Path(__file__).parent / 'read_manifest/run.py'),
          ('discovery', Path(__file__).parent / 'discovery/run.py'),
          ('snapshot', Path(__file__).parent / 'snapshot/run.py'),
          ('tokenizer', Path(__file__).parent / 'tokenizer/run.py'),
          ('parser_foundation', Path(__file__).parent / 'parser_foundation/run.py'),
          ('expressions', Path(__file__).parent / 'expressions/run.py'),
          ('statements', Path(__file__).parent / 'statements/run.py'),
          ('syntax_access', Path(__file__).parent / 'syntax_access/run.py'),
          ('parser_project', Path(__file__).parent / 'parser_project/run.py'),
          ('collect_symbols', Path(__file__).parent / 'collect_symbols/run.py'),
          ('entry_preparation', Path(__file__).parent / 'entry_preparation/run.py'),
          ('type_representations', Path(__file__).parent / 'type_representations/run.py'),
          ('type_lifetimes', Path(__file__).parent / 'type_lifetimes/run.py'),
          ('scalar_catalog', Path(__file__).parent / 'scalar_catalog/run.py'),
          ('name_lookup', Path(__file__).parent / 'name_lookup/run.py'),
          ('lexical_resolution', Path(__file__).parent / 'lexical_resolution/run.py'),
          ('resolution_project', Path(__file__).parent / 'resolution_project/run.py'),
          ('type_store', Path(__file__).parent / 'type_store/run.py'),
          ('aggregate_lifecycles', Path(__file__).parent / 'aggregate_lifecycles/run.py'),
          ('native_record_layout', Path(__file__).parent / 'native_record_layout/run.py'),
          ('resource_obligations', Path(__file__).parent / 'resource_obligations/run.py'),
          ('record_materialization', Path(__file__).parent / 'record_materialization/run.py'),
          ('definition_view', Path(__file__).parent / 'definition_view/run.py'),
          ('instance_contexts', Path(__file__).parent / 'instance_contexts/run.py'),
          ('instance_identities', Path(__file__).parent / 'instance_identities/run.py'),
          ('symbolic_terms', Path(__file__).parent / 'symbolic_terms/run.py'),
          ('provider_semantics', Path(__file__).parent / 'provider_semantics/run.py'),
          ('provider_families', Path(__file__).parent / 'provider_families/run.py'),
          ('provider_catalog', Path(__file__).parent / 'provider_catalog/run.py'),
          ('callable_abi', Path(__file__).parent / 'callable_abi/run.py'),
          ('storage_contracts', Path(__file__).parent / 'storage_contracts/run.py'),
          ('package_syntax', Path(__file__).parent / 'package_syntax/run.py'),
          ('resource_import', Path(__file__).parent / 'resource_import/run.py'),
          ('lifecycle_import', Path(__file__).parent / 'lifecycle_import/run.py'),
          ('record_import', Path(__file__).parent / 'record_import/run.py'),
          ('binding_import', Path(__file__).parent / 'binding_import/run.py'),
          ('callable_positions', Path(__file__).parent / 'callable_positions/run.py'),
          ('callable_import', Path(__file__).parent / 'callable_import/run.py'),
          ('storage_import', Path(__file__).parent / 'storage_import/run.py'),
          ('package_measurements', Path(__file__).parent / 'package_measurements/run.py'),
          ('type_exposure', Path(__file__).parent / 'type_exposure/run.py'),
          ('native_type_import', Path(__file__).parent / 'native_type_import/run.py'),
          ('export_provenance', Path(__file__).parent / 'export_provenance/run.py'),
          ('export_identity', Path(__file__).parent / 'export_identity/run.py'),
          ('layout_contracts', Path(__file__).parent / 'layout_contracts/run.py'),
          ('source_export_contracts', Path(__file__).parent / 'source_export_contracts/run.py'),
          ('package_type_map', Path(__file__).parent / 'package_type_map/run.py'),
          ('runtime_package', Path(__file__).parent / 'runtime_package/run.py'),
          ('callable_contracts', Path(__file__).parent / 'callable_contracts/run.py'),
          ('lifecycle_contracts', Path(__file__).parent / 'lifecycle_contracts/run.py'),
          ('definition_contracts', Path(__file__).parent / 'definition_contracts/run.py'),
          ('type_retention', Path(__file__).parent / 'type_retention/run.py'),
          ('package_context', Path(__file__).parent / 'package_context/run.py'),
          ('package_manifest', Path(__file__).parent / 'package_manifest/run.py'),
          ('source_export_validation', Path(__file__).parent / 'source_export_validation/run.py'),
          ('project_receipt', Path(__file__).parent / 'project_receipt/run.py'),
          ('runtime_lease', Path(__file__).parent / 'runtime_lease/run.py'),
          ('package_composition', Path(__file__).parent / 'package_composition/run.py'),
          ('source_export_work', Path(__file__).parent / 'source_export_work/run.py'),
          ('layout_capture', Path(__file__).parent / 'layout_capture/run.py'),
          ('layout_selection', Path(__file__).parent / 'layout_selection/run.py'),
          ('layout_witness', Path(__file__).parent / 'layout_witness/run.py'),
          ('layout_measurement', Path(__file__).parent / 'layout_measurement/run.py'),
          ('layout_join', Path(__file__).parent / 'layout_join/run.py'),
          ('tool_run', Path(__file__).parent / 'tool_run/run.py'),
          ('layout_worker', Path(__file__).parent / 'layout_worker/run.py'),
          ('provider_declaration', Path(__file__).parent / 'provider_declaration/run.py'),
          ('symbol_origins', Path(__file__).parent / 'symbol_origins/run.py'),
          ('provider_record_bindings', Path(__file__).parent / 'provider_record_bindings/run.py'),
          ('symbolic_interpretation', Path(__file__).parent / 'symbolic_interpretation/run.py'),
          ('template_body', Path(__file__).parent / 'template_body/run.py'),
          ('template_project', Path(__file__).parent / 'template_project/run.py'),
          ('instance_registry', Path(__file__).parent / 'instance_registry/run.py'),
          ('concrete_bindings', Path(__file__).parent / 'concrete_bindings/run.py'),
          ('constant_batch', Path(__file__).parent / 'constant_batch/run.py'),
          ('application_arguments', Path(__file__).parent / 'application_arguments/run.py'),
          ('instance_join', Path(__file__).parent / 'instance_join/run.py'),
          ('member_instances', Path(__file__).parent / 'member_instances/run.py'),
          ('record_preparation', Path(__file__).parent / 'record_preparation/run.py'),
          ('signature_requests', Path(__file__).parent / 'signature_requests/run.py'),
          ('type_result_records', Path(__file__).parent / 'type_result_records/run.py'),
          ('signature_publication', Path(__file__).parent / 'signature_publication/run.py')]


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--target-checkout', type=Path)
    parser.add_argument('--candidate-revision')
    args = parser.parse_args()
    files = []
    for name, path in STAGES:
        spec = importlib.util.spec_from_file_location(name, path)
        module = importlib.util.module_from_spec(spec); spec.loader.exec_module(module)
        files.extend(module.FILES)
    ready = json.loads((ROOT / 'compiler/portability.json').read_text())['files']
    if not ready or set(ready) != set(files) or len(files) != len(set(files)):
        raise SystemExit('Active ready set must match registered stage proof sources')
    results = args.results.resolve(); results.mkdir(parents=True, exist_ok=False)
    for name, path in STAGES:
        cmd = [sys.executable, str(path), '--results', str(results / name)]
        if args.target_checkout: cmd += ['--target-checkout', str(args.target_checkout)]
        if args.candidate_revision: cmd += ['--candidate-revision', args.candidate_revision]
        subprocess.run(cmd, cwd=ROOT, check=True)
    (results / 'summary.json').write_text(json.dumps({'passed': True, 'stages': [n for n, _ in STAGES],
        'production_files': len(files), 'native': bool(args.target_checkout)}, indent=2) + '\n')


if __name__ == '__main__': main()
