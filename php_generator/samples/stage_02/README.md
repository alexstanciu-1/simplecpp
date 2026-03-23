# Stage 02 sample set

These fixtures are intentionally larger than the stage 01 samples.
They are designed to combine multiple catalog rules in the same file,
while still staying small enough to debug quickly.

## Goals

- exercise interactions between declarations and executable flow
- force synthetic entry generation through real executable statements
- combine namespace, class, function, constant, reference, and typed-local rules
- make success depend on exact stdout parity, not only on avoiding errors
- keep each file in the ~30–40 line range

## Files

- `01_flow_arithmetic_and_calls.php`
- `02_functions_defaults_nullable_and_strings.php`
- `03_namespace_exec_nested_decl_only.php`
- `04_cross_namespace_static_and_construction.php`
- `05_typed_locals_and_object_handles.php`
- `06_reference_pipeline.php`
- `07_class_methods_and_static_mix.php`
- `08_interfaces_abstracts_and_objects.php`
- `09_nested_namespaces_and_relative_paths.php`
- `10_constants_casts_and_numbers.php`
- `11_instance_based_static_access_matrix.php`
- `12_constructor_defaults_and_roundtrip.php`

## Expected testing model

For each positive sample:

1. run the PHP file and capture stdout
2. generate C++
3. compile the generated C++
4. run the produced executable and capture stdout
5. require exact stdout equality

If any step fails, the sample fails.
