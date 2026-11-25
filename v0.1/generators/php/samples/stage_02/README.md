# Stage 02 sample set
Doc Status: supporting
These fixtures are intentionally larger than the stage 01 samples.
They are designed to combine multiple catalog rules in the same file,
while still staying small enough to debug quickly.

## Goals

- exercise interactions between declarations and executable flow
- force synthetic entry generation through real executable statements
- combine namespace, class, function, constant, reference, and typed-local rules
- make success depend on exact stdout parity, not only on avoiding errors
- keep each file in the ~30â€“40 line range

## Files

- `01_flow_arithmetic_and_calls.phs`
- `02_functions_defaults_nullable_and_strings.phs`
- `03_namespace_exec_nested_decl_only.phs`
- `04_cross_namespace_static_and_construction.phs`
- `05_typed_locals_and_object_handles.phs`
- `06_reference_pipeline.phs`
- `07_class_methods_and_static_mix.phs`
- `08_interfaces_abstracts_and_objects.phs`
- `09_nested_namespaces_and_relative_paths.phs`
- `10_constants_casts_and_numbers.phs`
- `11_instance_based_static_access_matrix.phs`
- `12_constructor_defaults_and_roundtrip.phs`
- `13_control_flow_suite.phs`
- `14_if_else_and_switch_cases.phs`
- `15_value_local_point.phs`
- `16_ref_to_value_local.phs`
- `17_ref_to_handle_noop.phs`

## Expected testing model

For each positive sample:

1. run the PHP file and capture stdout
2. generate C++
3. compile the generated C++
4. run the produced executable and capture stdout
5. require exact stdout equality

If any step fails, the sample fails.
