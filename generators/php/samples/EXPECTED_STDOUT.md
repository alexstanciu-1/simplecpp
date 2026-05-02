# Expected stdout for positive samples
Doc Status: derived
These values were captured by running the PHP fixtures directly.

## stage_01

- `01_literals_and_assignments.phs` â†’ `10|x|20`
- `02_functions_basic.phs` â†’ `3`
- `03_namespace_exec_ok.phs` â†’ `3`
- `04_namespace_nested_decl_only.phs` â†’ `3`
- `05_class_basic.phs` â†’ `user-created`
- `06_class_static_access.phs` â†’ `1|1|1`
- `07_typed_locals_phpdoc.phs` â†’ `test`
- `08_references.phs` â†’ `3|3|5`
- `09_constants_and_strings.phs` â†’ `demo`

## stage_02

- `01_flow_arithmetic_and_calls.phs` â†’ `50`
- `02_functions_defaults_nullable_and_strings.phs` â†’ `alpha|5`
- `03_namespace_exec_nested_decl_only.phs` â†’ `13|11`
- `04_cross_namespace_static_and_construction.phs` â†’ `42`
- `05_typed_locals_and_object_handles.phs` â†’ `done`
- `06_reference_pipeline.phs` â†’ `6`
- `07_class_methods_and_static_mix.phs` â†’ `11`
- `08_interfaces_abstracts_and_objects.phs` â†’ `6`
- `09_nested_namespaces_and_relative_paths.phs` â†’ `90`
- `10_constants_casts_and_numbers.phs` â†’ `limit|6`
- `11_instance_based_static_access_matrix.phs` â†’ `42`
- `12_constructor_defaults_and_roundtrip.phs` â†’ `5|7`
- `13_control_flow_suite.phs` â†’ `ok
3
`
- `14_if_else_and_switch_cases.phs` â†’ `low
two
`
- `15_value_local_point.phs` â†’ `5`
- `16_ref_to_value_local.phs` â†’ `6|6`
- `17_ref_to_handle_noop.phs` â†’ `8|8`

## stage_03

- `01_cross_namespace_use_and_service_flow.phs` â†’ `[run:36]|[sum:36]`
- `02_nullable_references_and_defaults_pipeline.phs` â†’ `pipe:8|state=8;pipe:13|state=13;final=15`
- `03_interfaces_abstracts_construction_and_exec.phs` â†’ `value=6/12|value=12/24;value=10/20|value=20/40`
- `04_constants_methods_and_state_roundtrip.phs` â†’ `main:2|cfg-main:17;main:2|cfg-main:19`
