# Stage 03 sample set

These samples are intended as the next step after Stage 02.
They are deliberately larger, around ~100 lines each, and combine more moving parts
without trying to expand the supported subset too aggressively.

## Goals

- keep using only the currently intended subset
- exercise interactions across multiple namespaces
- increase pressure on declaration ordering, generated entry flow, and cross-unit lowering
- cover `use function` / `use const` with the newly adopted C++ `using` semantics
- cover nullable values, references, defaults, classes, interfaces, abstract classes, and string concat
- make the fixtures output-driven, so success depends on exact stdout parity
- stay small enough to debug by hand

## Files

- `01_cross_namespace_use_and_service_flow.php`
- `02_nullable_references_and_defaults_pipeline.php`
- `03_interfaces_abstracts_construction_and_exec.php`
- `04_constants_methods_and_state_roundtrip.php`

## Notes

These files are provided as PHP source only.
Export the matching parser output beside each PHP file using the same basename,
for example:

- `01_cross_namespace_use_and_service_flow.php.json`
- `02_nullable_references_and_defaults_pipeline.php.json`

That keeps them aligned with the current fixture workflow.
