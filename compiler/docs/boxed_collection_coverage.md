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
| Instance fields | Both supported, null default | Generated field contract; normal struct copy/lifetime |
| Append `[] = value` | Both supported | Generated appendable capability; mixed null bootstraps, dynamic null rejects |
| Indexed compounds/increment/decrement | Both supported for approved scalar payloads | Generated scalar dispatch; existing-key mutation capability |
| count/empty | Count requires a container. Empty accepts strings and containers | Generated bindings dispatch to container_size and empty |
| has/remove | Both supported with normalized int64/string keys | Generated method overloads and source key adapters |
| clear | Both supported, container required | Preserves dynamic table identity and mixed copy independence |
| Nested indexed expressions | Deferred | Runtime can hold nested boxes; compiler currently handles one indexing level |
| Application-defined object payloads | Outside current boxed kinds | Requires a separate storage/copy/dispatch contract, not simply registration |

Static collection fields, property/element writable references, reference returns and
cross-thread mutation are separate boundaries, not promises implied by vector/hash support.
Application-defined vector/hash specializations now share generated has/remove/clear signatures with published types.

## Owners and next slices

- `types/boxed.php` owns boxed indexing and scalar dispatch; it implements indexed_mutation_type. Generated `types/boxed.php` adds iterable_type, counted_collection_type and appendable_collection_type.
- `04_analyze/prepare/prepare.php` consumes counted_collection_type and indexed_mutation_type;
  it should not gain a handwritten mixed/dynamic special case to bypass either contract.
- `tools/api_generator/templates/boxed/boxed_declaration.cpp.tpl` exposes native append,
  has, remove, container_size and clean. Native availability is not source admission.
- `04_analyze/prepare/functions.php` and the common argument binder already admit boxed
  references; no compiler implementation change was necessary for the audit.

Generated scalar plans now also supply boxed arithmetic, bitwise/shift and concatenation
execution for compounds and updates. The actual stored kind selects the scalar operation;
results remain in the same boxed family. Ordering remains bool. Flavor masks preserve
PHP concatenation versus C++/JS string addition. No per-expression dispatch table is retained.

Ordinary reads still return null on missing keys. Indexed mutation uses `source_require`,
which returns a copied value or throws `boxed_missing_key`; write-back rechecks the key
after RHS effects. It never retains a slot pointer across user code. Existing typed
collections reuse their already-checked read through `checked_indexed_mutation`.
Nested mutation and application object payloads remain separate discussions.

## Implementation and verification

`runtime_overloads` now records an argument_index (default zero) and receiver_method
(default false). This is canonical generated signature data, not an occurrence fact.
Boxed keyed methods select on operand one; free overloads keep operand zero. The common
binder prepares the receiver and selector once, validates exact key type, and invalidates
mutable named storage through the existing method path. No new AST nodes or body scans.

`boxed_collection_methods_native.py` runs 13 incremental revisions per flavor, compares
fresh artifacts through errors/retries/recovery, and executes native results. It covers
normalized numeric/NUL/large-text keys, present null, removal booleans, mixed/dynamic copy
policies, expression receivers, arity/type errors, provider type changes and deletion.
Native guard fixtures cover null/scalar cardinality and invalid integer keys. Existing
PHP number_format's six arity/type overloads prove default selector compatibility.
`tests/boxed/publication.py` checks invalid metadata and independently generated providers.

Initial fixture timings (single observations, milliseconds):

| Flavor | PHP initial | PHP no-op | Native wall | Frontend | LLVM backend | Link |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| php | 6.93 | 0.96 | 247.46 | 43.00 | 61.21 | 72.00 |
| cpp | 7.43 | 0.91 | 231.66 | 39.79 | 58.88 | 63.00 |
| js | 6.87 | 0.92 | 211.50 | 39.50 | 58.92 | 55.00 |

O2/g0, Ninja8, mold, no PCH/LTO, prebuilt libc++ runtime, direct Ninja qualification.
Frontend/backend are summed work across units and overlap in wall time. These are
correctness-fixture observations, not a controlled before/after performance comparison.
Raw local proof artifacts: `/tmp/scpp-boxed-methods-final/`.
