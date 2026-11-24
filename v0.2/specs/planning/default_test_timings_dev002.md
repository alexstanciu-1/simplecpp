# Default test timings on dev002, 2026-11-24

Doc Status: planning

All 361 names in the final default CTest manifest have an end-to-end measurement. Unchanged units ran with eight CTest jobs. The eight replacements ran sequentially. Each unit includes fixture generation, native compilation and execution where applicable. The runtime was already built.

69 units took over three seconds on the first measurement. All 18 units between 2.4 and 5 seconds were repeated with one CTest job. Using the repeat where available, 64 units are still over budget. Units above five seconds have only the parallel-run timing. Failed units report time to failure.

## Follow-up on the five longest units

Each replacement passed three consecutive serial runs on dev002 with the runtime built. The five full matrices are registered under the on-demand label. This remeasurement removes five entries from the original 64-unit backlog, leaving 59 based on the earlier measurements.

| Default unit | New range (s) |
| --- | ---: |
| `compiler_shared_upcasts_native` | 0.58–0.60 |
| `compiler_last_use_transfer` | 1.02–1.06 |
| `compiler_arithmetic_argument_order` | 0.76–0.77 |
| `compiler_expression_static_native` | 0.76–0.78 |
| `compiler_interface_access_native` | 0.74–0.77 |

## Default units over three seconds on the first measurement

| CTest name | First run (s) | Serial repeat (s) | Result |
| --- | ---: | ---: | --- |
| `compiler_shared_upcasts_native` | 51.04 | — | Passed |
| `compiler_last_use_transfer` | 47.22 | — | Passed |
| `compiler_arithmetic_argument_order` | 45.15 | — | Passed |
| `compiler_expression_static_native` | 43.70 | — | Passed |
| `compiler_interface_access_native` | 41.04 | — | Passed |
| `compiler_hash_contains_native` | 40.45 | — | Passed |
| `compiler_called_methods_native` | 38.54 | — | Passed |
| `compiler_object_access_native` | 34.26 | — | Passed |
| `compiler_native_build` | 33.50 | — | Passed |
| `compiler_ast_parent_lifetime_native` | 33.02 | — | Passed |
| `compiler_keyed_initialization_native` | 31.58 | — | Passed |
| `compiler_inline_adapters_native` | 30.84 | — | Passed |
| `compiler_app_namespace_native` | 29.07 | — | Passed |
| `boxed_publication` | 26.63 | — | Passed |
| `compiler_named_arguments_native` | 23.40 | — | Passed |
| `compiler_hash_find_native` | 22.91 | — | Passed |
| `compiler_keyed_initialization_incremental` | 22.36 | — | Passed |
| `compiler_match_native` | 22.10 | — | Passed |
| `compiler_destination_append` | 21.64 | — | Passed |
| `compiler_match_conditions_native` | 20.15 | — | Passed |
| `compiler_coalesce_assignment_native` | 16.99 | — | Passed |
| `compiler_coalesce_native` | 16.78 | — | Passed |
| `json_publication` | 16.42 | — | Passed |
| `compiler_incremental_equivalence` | 15.34 | — | Passed |
| `compiler_list_initialization_native` | 15.12 | — | Passed |
| `compiler_collection_references_native` | 14.86 | — | Passed |
| `compiler_collection_append_native` | 14.73 | — | Passed |
| `compiler_named_construction_native` | 14.46 | — | Passed |
| `compiler_struct_constants_native` | 13.88 | — | Passed |
| `compiler_shared_lookup_native` | 13.65 | — | Passed |
| `compiler_finally_native` | 13.57 | — | Passed |
| `compiler_exceptions_native` | 13.55 | — | Passed |
| `compiler_bootstrap_runtime_lookup_native` | 13.38 | — | Passed |
| `compiler_collection_transfer` | 13.34 | — | Passed |
| `datetime_publication` | 11.73 | — | Passed |
| `native_probe_contract` | 11.43 | — | Passed |
| `compiler_switch_native` | 10.79 | — | Passed |
| `compiler_required_fields_native` | 10.23 | — | Passed |
| `compiler_integer_radix_native` | 9.67 | — | Passed |
| `compiler_scoped_byte_view_native` | 9.36 | — | Passed |
| `compiler_fixed_array_native` | 9.34 | — | Passed |
| `generator_parity` | 8.82 | — | Failed |
| `native_qualification_cache` | 7.94 | — | Passed |
| `compiler_ternary_native` | 7.37 | — | Passed |
| `compiler_boxed_collection_coverage` | 7.35 | — | Passed |
| `compiler_bootstrap_donor_verification_native` | 7.19 | — | Failed |
| `concrete_templates` | 6.98 | — | Passed |
| `compiler_named_callbacks_native` | 6.45 | — | Passed |
| `compiler_failure_expressions_native` | 6.42 | — | Passed |
| `compiler_readonly_native` | 6.31 | — | Passed |
| `compiler_input_records_native` | 6.21 | — | Passed |
| `compiler_incremental_executable_native` | 5.70 | — | Passed |
| `compiler_weak_native` | 5.50 | — | Passed |
| `compiler_shared_references_native` | 5.24 | — | Passed |
| `compiler_reference_parameters_native` | 5.02 | — | Failed |
| `compiler_collection_bootstrap_native` | 5.01 | — | Passed |
| `hash_provider_exports` | 4.86 | 3.94 | Passed |
| `compiler_undefined_key_native` | 4.41 | 3.60 | Passed |
| `compiler_runtime_operand_projection_native` | 4.21 | 3.43 | Passed |
| `compiler_scalar_fact_edits` | 4.11 | 3.53 | Passed |
| `compiler_staged_ownership_native` | 4.07 | 3.48 | Passed |
| `compiler_keyed_initialization_ownership` | 3.91 | 3.32 | Passed |
| `compiler_frontend_service` | 3.84 | 3.52 | Passed |
| `compiler_source_loader_callbacks_native` | 3.69 | 3.10 | Passed |
| `compiler_unset_native` | 3.41 | 2.75 | Passed |
| `compiler_list_initialization_ownership` | 3.40 | 2.86 | Passed |
| `compiler_indexed_descriptor_native` | 3.24 | 2.60 | Passed |
| `compiler_unit_headers_native` | 3.08 | 2.53 | Passed |
| `compiler_inspection_scalars` | 3.08 | 2.58 | Passed |

## Replacement units

| CTest name | Serial (s) |
| --- | ---: |
| `compiler_boxed_containers_native` | 1.04 |
| `compiler_callback_native` | 0.53 |
| `compiler_method_dispatch_native` | 0.66 |
| `compiler_python_bridge_native` | 0.57 |
| `compiler_record_ownership_native` | 0.58 |
| `compiler_static_access_native` | 0.53 |
| `keyed_initialization_providers` | 2.02 |
| `list_initialization_providers` | 2.05 |

## Default suite failures (18)

These units are still part of the default suite. All eight replacement units passed.

- `carrier_provider_exports`
- `checked_conversion_generation`
- `compiler_ast_type_ownership_native`
- `compiler_bootstrap_donor_verification_native`
- `compiler_boxed_scalars`
- `compiler_boxed_scalars_native`
- `compiler_dependency_watches`
- `compiler_launcher_native`
- `compiler_publication_gates`
- `compiler_reference_parameters_native`
- `compiler_string_scanner_native`
- `compiler_style`
- `generator_parity`
- `numeric_contract`
- `runtime_family_contract`
- `runtime_submodule_providers`
- `scalar_contract`
- `vector_provider_exports`
