# Mixed/dynamic collection coverage

Doc Status: supporting

Checked 2026-11-18 against current compiler admission and native execution. This is an
inventory, not authorization to expand the language. `boxed_collection_coverage.py`
checks all three flavors, executes value/reference boundaries, and records rejected
capabilities. Existing `boxed_scalars.php`, `foreach.php` and `yield_foreach.php` provide
key normalization, mutation, incremental recovery and suspended traversal coverage.

| Capability | mixed / dynamic | Evidence or missing owner |
| --- | --- | --- |
| Explicitly typed empty local `[]` | Both supported | `boxed_type_definition::lower_cpp_empty()` |
| Keyed read/write, int/string normalized keys | Both supported | `prepare_key`, `prepare_index`, `lower_cpp_store`; boxed scalar tests |
| Assignment/copy | Both supported, different semantics | Native audit: mixed containers copy independently; dynamic container copies share identity |
| By-value function parameters and returns | Both supported | Native audit; same canonical conversion and native copy path |
| Writable function/method parameters | Both supported | Native audit: forwarding, slot replacement, exact-type checks; no new binder |
| Temporary/different-family reference argument | Both rejected | Existing stable-variable/exact-type diagnostics |
| Value/key foreach, including yield producers | Both supported | Generated iterable contract; existing foreach/yield regression suites |
| Instance fields | Both rejected | No generated instance-field admission/default contract |
| Append `[] = value` | Both rejected | Runtime append exists; boxed compiler type does not expose appendable capability |
| Indexed compounds/increment/decrement | Both rejected | Boxed compiler type does not implement indexed_mutation_type; boxed operator support is also narrower |
| count/empty | Both rejected | Runtime container_size exists; no counted_collection_type implementation |
| has/remove | Both rejected in source | Runtime APIs exist; missing generated source-method publication |
| clear | Both rejected in source | Needs a container-preserving contract; runtime clean must not be assumed equivalent |
| Nested indexed expressions | Deferred | Runtime can hold nested boxes; compiler currently handles one indexing level |
| Application-defined object payloads | Outside current boxed kinds | Requires a separate storage/copy/dispatch contract, not simply registration |

Static collection fields, property/element writable references, reference returns and
cross-thread mutation are separate boundaries, not promises implied by vector/hash support.
Likewise the public-method coverage of runtime-published vector/hash specializations must
not be assumed for every application-defined specialization.

## Owners and next slices

- `types/boxed.php` owns boxed indexing and scalar dispatch; it currently implements only
  indexed_collection_type and index_write_type. Generated `types/boxed.php` adds iterable_type.
- `04_analyze/prepare/prepare.php` consumes counted_collection_type and indexed_mutation_type;
  it should not gain a handwritten mixed/dynamic special case to bypass either contract.
- `tools/api_generator/templates/boxed/boxed_declaration.cpp.tpl` exposes native append,
  has, remove, container_size and clean. Native availability is not source admission.
- `04_analyze/prepare/functions.php` and the common argument binder already admit boxed
  references; no compiler implementation change was necessary for the audit.

Suggested catch-up order: count/empty and has/remove through generated capabilities, then
container-preserving clear and append with agreed scalar/null behavior, then instance fields
with an explicit default (null versus empty container). Indexed updates follow only after
settling which boxed operator families are admitted. Reuse scalar metadata for runtime
kind dispatch; do not duplicate a conversion or operator matrix for boxes. Nested mutation
and application object payloads should remain separate discussions.
