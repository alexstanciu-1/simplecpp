<?php
declare(strict_types=1);

/*
 * Role: Load compiler declarations without running a compilation.
 * Used by: main.php and test/tool entry points
 * Call map:
 *   [action] require bootstrap.php
 *     -> [action] load records, traits and processing classes
 */

require_once __DIR__ . '/../tools/php_portability/runtime/bootstrap.php';

if (PHP_INT_SIZE < 8) {
    throw new RuntimeException('The prototype requires 64-bit PHP.');
}

// Project composition only. Loading declarations must not run the command.
require_once __DIR__ . '/src/compile/step.php';
require_once __DIR__ . '/src/compile/join.php';

// Shared contracts precede the processes that produce and consume them.
require_once __DIR__ . '/src/04_analyze/type_model/data/representations.php';
require_once __DIR__ . '/src/04_analyze/type_model/data/records.php';
require_once __DIR__ . '/src/04_analyze/type_model/data/resources.php';
require_once __DIR__ . '/src/04_analyze/type_model/data/storage.php';
require_once __DIR__ . '/src/04_analyze/type_model/data/type_references.php';
require_once __DIR__ . '/src/04_analyze/type_model/data/semantic_calls.php';
require_once __DIR__ . '/src/04_analyze/type_model/result_contracts.php';
require_once __DIR__ . '/src/04_analyze/type_model/data/families.php';
require_once __DIR__ . '/src/04_analyze/type_model/data/source_families.php';
require_once __DIR__ . '/src/04_analyze/type_model/family_contracts.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/family_adapter.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/family_operations.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/family_preparation_join.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/prepare_families.php';
require_once __DIR__ . '/src/04_analyze/type_model/data/callables.php';
require_once __DIR__ . '/src/04_analyze/type_model/data/lifecycle.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/data/bindings.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/data/abi.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/data/lifecycle.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/data/configuration.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/data/tools.php';

require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/handlers/statements.php';
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/handlers/values.php';
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/handlers/locals.php';
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/body.php';
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/flow.php';
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/resource_effects.php';
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/resource_aliasing.php';
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/allocation_flow.php';
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/data/ownership.php';
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/resource_states.php';
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/resource_locations.php';
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/ownership_worker.php';
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/ownership_join.php';
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/ownership_preparation.php';
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/data/exports.php';
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/export_worker.php';
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/export_join.php';

require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/data/structures.php';
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/data/result.php';
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/data/store.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/utilities/body_validity.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/handlers/statements.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/handlers/writes.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/handlers/control_statements.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/handlers/expressions.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/handlers/places.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/body.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/flow.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/utilities/evaluation.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/utilities/flow_graph.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/utilities/operations.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/utilities/conversions.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/utilities/decimal_range.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/utilities/literals.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/utilities/byte_literals.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/data/structures.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/data/result.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/data/store.php';
require_once __DIR__ . '/src/04_analyze/collect_symbols/handlers/declarations.php';
require_once __DIR__ . '/src/04_analyze/collect_symbols/utilities/frontend_validation.php';
require_once __DIR__ . '/src/04_analyze/collect_symbols/collect.php';
require_once __DIR__ . '/src/04_analyze/collect_symbols/compare.php';
require_once __DIR__ . '/src/04_analyze/collect_symbols/utilities/declaration_syntax.php';
require_once __DIR__ . '/src/04_analyze/collect_symbols/data/result.php';
require_once __DIR__ . '/src/04_analyze/collect_symbols/data/store.php';
require_once __DIR__ . '/src/04_analyze/collect_symbols/data/structures.php';
require_once __DIR__ . '/src/compile/compile.php';
require_once __DIR__ . '/src/compile/inputs.php';
require_once __DIR__ . '/src/compile/lock.php';
require_once __DIR__ . '/src/compile/phases.php';
require_once __DIR__ . '/src/compile/result.php';
require_once __DIR__ . '/src/compile/state.php';
require_once __DIR__ . '/src/diagnostics/diagnostics.php';
require_once __DIR__ . '/src/05_generate_code/emit_llvm/handlers/instructions.php';
require_once __DIR__ . '/src/05_generate_code/emit_llvm/handlers/storage.php';
require_once __DIR__ . '/src/05_generate_code/emit_llvm/handlers/calls.php';
require_once __DIR__ . '/src/05_generate_code/emit_llvm/handlers/terminators.php';
require_once __DIR__ . '/src/05_generate_code/emit_llvm/handlers/locations.php';
require_once __DIR__ . '/src/05_generate_code/emit_llvm/body.php';
require_once __DIR__ . '/src/05_generate_code/emit_llvm/utilities/module_validity.php';
require_once __DIR__ . '/src/05_generate_code/emit_llvm/modules.php';
require_once __DIR__ . '/src/05_generate_code/emit_llvm/source_exports.php';
require_once __DIR__ . '/src/05_generate_code/emit_llvm/lifecycle.php';
require_once __DIR__ . '/src/05_generate_code/emit_llvm/lifecycle_join.php';
require_once __DIR__ . '/src/05_generate_code/emit_llvm/data/structures.php';
require_once __DIR__ . '/src/04_analyze/type_model/data/definitions.php';
require_once __DIR__ . '/src/04_analyze/type_model/data/generic.php';
require_once __DIR__ . '/src/04_analyze/type_model/generic_contracts.php';
require_once __DIR__ . '/src/04_analyze/check_templates/data/structures.php';
require_once __DIR__ . '/src/04_analyze/check_templates/data/result.php';
require_once __DIR__ . '/src/04_analyze/check_templates/terms.php';
require_once __DIR__ . '/src/04_analyze/check_templates/body.php';
require_once __DIR__ . '/src/04_analyze/check_templates/join.php';
require_once __DIR__ . '/src/04_analyze/check_templates/main_check_templates.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/handlers/type_definitions.php';
require_once __DIR__ . '/src/04_analyze/type_model/data/catalog.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/data/runtime.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/data/package.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/data/family_preparation.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/data/project.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/project_import.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/data/inputs.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/utilities/callable_bindings.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/input_join.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/runtime_import.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/handlers/records.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/handlers/package_types.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/handlers/lifecycle.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/handlers/callables.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/handlers/resources.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/handlers/storage.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/handlers/bindings.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/handlers/package_syntax.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/package_adapter.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/utilities/callable_contract.php';
require_once __DIR__ . '/src/05_generate_code/lower/handlers/statements.php';
require_once __DIR__ . '/src/05_generate_code/lower/handlers/expressions.php';
require_once __DIR__ . '/src/05_generate_code/lower/handlers/locals.php';
require_once __DIR__ . '/src/05_generate_code/lower/handlers/storage.php';
require_once __DIR__ . '/src/05_generate_code/lower/handlers/control_flow.php';
require_once __DIR__ . '/src/05_generate_code/lower/binary_operations.php';
require_once __DIR__ . '/src/05_generate_code/lower/body.php';
require_once __DIR__ . '/src/05_generate_code/lower/data/result.php';
require_once __DIR__ . '/src/05_generate_code/lower/data/store.php';
require_once __DIR__ . '/src/05_generate_code/lower/data/structures.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/data/context.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/utilities/llvm_types.php';
require_once __DIR__ . '/tool_process/process.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/tools/toolchain.php';
require_once __DIR__ . '/src/03_parse/utilities/metaprogramming_syntax.php';
require_once __DIR__ . '/src/03_parse/utilities/syntax_access.php';
require_once __DIR__ . '/src/03_parse/utilities/struct_member_cursor.php';
require_once __DIR__ . '/src/03_parse/utilities/syntax_comparer.php';
require_once __DIR__ . '/src/03_parse/handlers/statements.php';
require_once __DIR__ . '/src/03_parse/handlers/control_statements.php';
require_once __DIR__ . '/src/03_parse/handlers/declarations.php';
require_once __DIR__ . '/src/03_parse/handlers/metaprogramming.php';
require_once __DIR__ . '/src/03_parse/utilities/binary_syntax.php';
require_once __DIR__ . '/src/03_parse/handlers/expressions.php';
require_once __DIR__ . '/src/03_parse/parse_file.php';
require_once __DIR__ . '/src/03_parse/data/result.php';
require_once __DIR__ . '/src/03_parse/data/tree.php';
require_once __DIR__ . '/src/03_parse/data/store.php';
require_once __DIR__ . '/src/03_parse/data/nodes.php';
require_once __DIR__ . '/src/03_parse/data/role_views.php';
require_once __DIR__ . '/src/03_parse/data/structures.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_manifest/data/result.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/utilities/path_syntax.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/utilities/paths.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/read.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/data/result.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/scan.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/data/buffer.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/data/source_json.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/data/store.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/data/read_tasks.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/data/structures.php';
require_once __DIR__ . '/src/04_analyze/resolve_symbols/data/declarations.php';
require_once __DIR__ . '/src/04_analyze/resolve_symbols/utilities/declaration_lookup.php';
require_once __DIR__ . '/src/04_analyze/resolve_symbols/utilities/binding_coverage.php';
require_once __DIR__ . '/src/04_analyze/resolve_symbols/handlers/names.php';
require_once __DIR__ . '/src/04_analyze/resolve_symbols/utilities/resolution_validity.php';
require_once __DIR__ . '/src/04_analyze/resolve_symbols/utilities/function_lookup.php';
require_once __DIR__ . '/src/04_analyze/resolve_symbols/handlers/declarations.php';
require_once __DIR__ . '/src/04_analyze/resolve_symbols/handlers/statements.php';
require_once __DIR__ . '/src/04_analyze/resolve_symbols/handlers/expressions.php';
require_once __DIR__ . '/src/04_analyze/resolve_symbols/body.php';
require_once __DIR__ . '/src/04_analyze/resolve_symbols/data/result.php';
require_once __DIR__ . '/src/04_analyze/resolve_symbols/data/store.php';
require_once __DIR__ . '/src/04_analyze/resolve_symbols/data/structures.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/utilities/local_type_validity.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/utilities/signature_validity.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/utilities/annotation_types.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/utilities/parameter_contracts.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/data/structures.php';
require_once __DIR__ . '/src/04_analyze/type_model/data/store.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/data/result.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/storage.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/signatures.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/locals.php';
require_once __DIR__ . '/src/simulate_increment/run.php';
require_once __DIR__ . '/src/simulate_increment/store.php';
require_once __DIR__ . '/src/02_tokenize/store.php';
require_once __DIR__ . '/src/02_tokenize/structures.php';
require_once __DIR__ . '/src/02_tokenize/tokenize.php';

require_once __DIR__ . '/src/06_build_output/build_native/result.php';
require_once __DIR__ . '/src/05_generate_code/emit_llvm/data/result.php';

require_once __DIR__ . '/src/01_prepare_inputs/read_manifest/utilities/manifest_syntax.php';
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/utilities/catalog_syntax.php';

require_once __DIR__ . '/src/04_analyze/resolve_types/utilities/type_cache.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/callable.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/data/storage.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/storage.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/storage_join.php';
require_once __DIR__ . '/src/06_build_output/build_native/utilities/native_paths.php';
require_once __DIR__ . '/src/06_build_output/build_native/compile_objects.php';
require_once __DIR__ . '/src/06_build_output/build_native/workspace.php';

require_once __DIR__ . '/src/04_analyze/resolve_types/data/records.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/definition_view.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/records.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/record_definitions.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/lifecycle_composition.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/source_lifecycle.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/record_join.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/data/layout.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/layout.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/native_layout.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/layout_join.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/layout_coordinator.php';

// Joins implement concrete return contracts after data declarations.
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/join.php';
require_once __DIR__ . '/src/06_build_output/build_native/join.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/join.php';
require_once __DIR__ . '/src/04_analyze/collect_symbols/comparison_join.php';
require_once __DIR__ . '/src/04_analyze/collect_symbols/join.php';
require_once __DIR__ . '/src/05_generate_code/emit_llvm/join.php';
require_once __DIR__ . '/src/05_generate_code/emit_llvm/module_join.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/backend_join.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/lifecycle_join.php';
require_once __DIR__ . '/src/05_generate_code/lower/join.php';
require_once __DIR__ . '/src/03_parse/join.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/scan_join.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/snapshot_join.php';
require_once __DIR__ . '/src/04_analyze/resolve_symbols/join.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/local_join.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/signature_join.php';
require_once __DIR__ . '/src/02_tokenize/join.php';

// Concrete results must be loaded before covariant step implementations.
require_once __DIR__ . '/src/01_prepare_inputs/load_runtime/main_load_runtime.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_manifest/main_read_manifest.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/main_discover_sources.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/select_reads.php';
require_once __DIR__ . '/src/01_prepare_inputs/read_sources/main_read_sources.php';
require_once __DIR__ . '/src/02_tokenize/select_tasks.php';
require_once __DIR__ . '/src/02_tokenize/main_tokenize.php';
require_once __DIR__ . '/src/03_parse/select_tasks.php';
require_once __DIR__ . '/src/03_parse/main_parse.php';
require_once __DIR__ . '/src/04_analyze/analyze_lifetimes/main_analyze_lifetimes.php';
require_once __DIR__ . '/src/04_analyze/check_bodies/main_check_bodies.php';
require_once __DIR__ . '/src/04_analyze/collect_symbols/main_collect_symbols.php';
require_once __DIR__ . '/src/04_analyze/collect_symbols/main_compare_symbols.php';
require_once __DIR__ . '/src/04_analyze/resolve_symbols/main_resolve_symbols.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/main_prepare_entry.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/main_resolve_types.php';
require_once __DIR__ . '/src/05_generate_code/emit_llvm/main_assemble_modules.php';
require_once __DIR__ . '/src/05_generate_code/emit_llvm/main_emit_llvm.php';
require_once __DIR__ . '/src/05_generate_code/lower/main_lower.php';
require_once __DIR__ . '/src/05_generate_code/lower/main_native_entry.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/main_prepare_backend.php';
require_once __DIR__ . '/src/06_build_output/build_native/main_build_native.php';

// Explicit instance preparation shares the existing type/body pipeline.
require_once __DIR__ . '/src/04_analyze/instantiate/data/structures.php';
require_once __DIR__ . '/src/04_analyze/instantiate/data/view.php';
require_once __DIR__ . '/src/04_analyze/instantiate/data/result.php';
require_once __DIR__ . '/src/04_analyze/instantiate/data/store.php';
require_once __DIR__ . '/src/04_analyze/instantiate/bindings.php';
require_once __DIR__ . '/src/04_analyze/instantiate/constants.php';
require_once __DIR__ . '/src/04_analyze/instantiate/applications.php';
require_once __DIR__ . '/src/04_analyze/instantiate/identities.php';
require_once __DIR__ . '/src/04_analyze/instantiate/members.php';
require_once __DIR__ . '/src/04_analyze/instantiate/member_join.php';
require_once __DIR__ . '/src/04_analyze/instantiate/join.php';
require_once __DIR__ . '/src/04_analyze/instantiate/policy.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/data/preparation.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/preparation_queue.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/main_prepare_concrete.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/utilities/callable_inputs.php';

// Compiler-owned source/native export boundary; selected explicitly by its coordinator.
require_once __DIR__ . '/src/compile/data/native_project.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/data/export_identity.php';
require_once __DIR__ . '/src/04_analyze/resolve_types/source_identities.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/data/source_exports.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/source_export_preparation.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/source_export_join.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/source_export_coordinator.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/project_exports.php';
require_once __DIR__ . '/src/05_generate_code/prepare_backend/export_verification.php';
