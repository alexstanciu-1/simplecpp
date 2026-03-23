# Expected stdout for positive samples

These values were captured by running the PHP fixtures directly.

## stage_01

- `01_literals_and_assignments.php` → `10|x|20`
- `02_functions_basic.php` → `3`
- `03_namespace_exec_ok.php` → `3`
- `04_namespace_nested_decl_only.php` → `3`
- `05_class_basic.php` → `user-created`
- `06_class_static_access.php` → `1|1|1`
- `07_typed_locals_phpdoc.php` → `test`
- `08_references.php` → `3|3|5`
- `09_constants_and_strings.php` → `demo`

## stage_02

- `01_flow_arithmetic_and_calls.php` → `50`
- `02_functions_defaults_nullable_and_strings.php` → `alpha|5`
- `03_namespace_exec_nested_decl_only.php` → `13|11`
- `04_cross_namespace_static_and_construction.php` → `42`
- `05_typed_locals_and_object_handles.php` → `done`
- `06_reference_pipeline.php` → `6`
- `07_class_methods_and_static_mix.php` → `11`
- `08_interfaces_abstracts_and_objects.php` → `6`
- `09_nested_namespaces_and_relative_paths.php` → `90`
- `10_constants_casts_and_numbers.php` → `limit|6`
- `11_instance_based_static_access_matrix.php` → `42`
- `12_constructor_defaults_and_roundtrip.php` → `5|7`
- `13_control_flow_suite.php` → `ok
3
`
- `14_if_else_and_switch_cases.php` → `low
two
`
- `15_value_local_point.php` → `5`
- `16_ref_to_value_local.php` → `6|6`
- `17_ref_to_handle_noop.php` → `8|8`

## stage_03

- `01_cross_namespace_use_and_service_flow.php` → `[run:36]|[sum:36]`
- `02_nullable_references_and_defaults_pipeline.php` → `pipe:8|state=8;pipe:13|state=13;final=15`
- `03_interfaces_abstracts_construction_and_exec.php` → `value=6/12|value=12/24;value=10/20|value=20/40`
- `04_constants_methods_and_state_roundtrip.php` → `main:2|cfg-main:17;main:2|cfg-main:19`
