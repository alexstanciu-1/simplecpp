# Named arithmetic runtime bindings

Doc Status: supporting

This implements the arithmetic-binding portion of finding 4 in the
[saved metadata audit](../../specs/planning/compiler_metadata_audit.md).

`compiler.runtime_bindings` in runtime metadata owns each helper's public C++ symbol,
operand/result identities and supported lowering strategy. Legacy bindings retain
a header field; output references a runtime-owned `declaration_group`, and type
bindings reference `type_export`. Their providers determine headers during generation. Arithmetic rows
refer to these records through `bindings`, keyed by operator. The three current
bindings are int64 division, int64 remainder and float64 division.

The generator resolves references and validates their declared signature and strategy
against the admitted operation. It rejects malformed symbols, non-public header paths,
missing references and incompatible contracts. This validates metadata consistency;
the native integration tests verify the actual runtime ABI. Metadata does not contain
executable C++ expressions. Runtime helper implementations/declarations remain unchanged.

`compilerArithmeticBinding()` performs generation-time resolution. `compilerBinary()`
emits the resolved symbol and groups required headers by operator. There is no new
per-expression lookup, AST property, prepared fact or runtime object. Default generated
PHP remains byte-for-byte identical, so the inventory version is unchanged.

Division checks, signed overflow behavior, compound orchestration and operand safety
remain unchanged. Native operators still use native syntax. Echo remains separate future work; copy and operation safety declarations are described below.

## Verification

Seven focused CTests passed: generator, integer/float arithmetic, integer/float compounds,
arithmetic runtime helpers and runtime freshness. Generator mutation tests verify that
changing symbol/header metadata changes generated output, and reject invalid references,
signatures, strategies and spellings. All eight generated files match the pre-change files.

The integer and floating native proofs passed for cpp/php/js. They cover zero-divisor
failures and normal execution; semantic checks cover signature changes, invalidation and
recovery. Floating incremental tests compare emitted artifacts with fresh compilation,
retry failed runs without edits, and verify unchanged caller AST reuse.

O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime; fresh instrumented application
objects. These are single correctness runs, with the two proof suites running concurrently,
not a comparative performance benchmark. Floating recovery builds:

| Flavor | Native wall ms | Clang frontend ms | LLVM backend ms | Link command ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 142.71 | 17.61 | 23.50 | 68.00 |
| php | 150.80 | 20.28 | 25.06 | 72.00 |
| js | 123.06 | 13.05 | 17.94 | 63.00 |

Frontend/backend sums are cumulative trace durations and are not additive wall-time
components. Full results, including integer builds and the heavier IEEE/exception
observer fixtures, are retained under `v0.2/build/arithmetic-bindings-proof/`.

## Checked conversions and string storage

The same `compiler.runtime_bindings` catalog now includes named `kind: type` records
for boxed int64, byte, bool, float64 and string storage. Each declares its semantic
identity and a `type_export` reference. The runtime export plan supplies its public
C++ name and provider header. Independent symbol/header overrides are rejected. Wrappers used to extract a native result also declare
an `extract` member. No arbitrary executable expressions are accepted.

Checked conversion rows reference `target_binding` and, for floating-to-integer,
`source_binding`. String's value-type row references `storage_binding`, replacing its
separate `cpp_type` spelling. `compilerTypeBinding()` validates and resolves these
records during generation. The normalized conversion plan retains resolved bindings;
there is no application-compilation metadata lookup or new AST/preparation structure.

Strategies still own wrapper construction, range checks, truncation, constant conversion
and diagnostics. Proven-safe branches remain native casts and require no wrapper
headers. String decoding, byte escaping and length computation remain handwritten.
Symbols and extraction calls in generated lowering are unchanged; only the generated
conversion-header methods change from target-specific ternaries to binding-derived
matches. String's generated class remains byte-identical. Safety and echo are unchanged.

Generator tests verify binding substitution and reject missing bindings, wrong identities,
wrong kinds, invalid symbols/headers and extraction members. Eight focused CTests pass.
The existing cast and string native suites pass, including checked exceptions and copy
behavior. `tests/type_bindings.php` additionally checks all three flavors for byte/int
checked conversion and string storage: edits, provider declaration removal, repeated
failure without edits, re-addition, unchanged caller AST reuse and no-op reuse. Successful
incremental output is compared with fresh compilation. All nine recovery executables
pass. Raw traces and results are in `v0.2/build/type-bindings-proof/`.

Single-run recovery timings (same O2/g0, Ninja8/mold configuration above):

| Flavor / destination | Native wall ms | Frontend ms | LLVM backend ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp_byte | 149.94 | 16.12 | 22.03 | 73.00 |
| cpp_int | 150.03 | 18.61 | 18.25 | 71.00 |
| cpp_string | 143.78 | 23.17 | 21.59 | 71.00 |
| php_byte | 147.90 | 17.02 | 21.45 | 76.00 |
| php_int | 164.84 | 26.23 | 26.31 | 74.00 |
| php_string | 140.59 | 25.17 | 20.42 | 64.00 |
| js_byte | 142.31 | 14.02 | 19.04 | 69.00 |
| js_int | 146.73 | 18.59 | 23.01 | 72.00 |
| js_string | 145.38 | 22.71 | 19.75 | 73.00 |

## Copy semantics and lowering safety

`compiler.value_types.*.copy_semantics` now declares `trivial` or `nontrivial` for
that compiler representation. Current native scalars are trivial; string is nontrivial.
The normalized type plan drives `cpp_trivial_value()`, replacing the fixed identity list.
These are reviewed representation facts, not source conversion permissions.

`compiler.lowering_strategies.*.reorderable` supplies the operation-local safety of
existing lowering strategies. Native comparisons, arithmetic and numeric unary operators
retain true; zero-checked division/remainder retain false. The generator resolves the
strategy declarations once and emits the existing direct queries. Missing declarations,
unknown strategy/copy vocabulary and non-boolean safety values are errors. Checked
strategies cannot be marked reorderable: their runtime failures remain observable.
Native operations may be conservatively marked false without altering admission.

The emitter remains responsible for operand effects, conversion boundaries, short-circuit
semantics, snapshot lifetimes and identical-spelling checks. No call/operand analysis,
conversion reordering, runtime structures or preparation facts changed. Nontrivial and
unsupported operations retain conservative base-class defaults. Echo remains deferred.

All eight generated files are byte-identical with the current metadata; inventory version
is unchanged. Generator mutation tests prove metadata consumption and rejection paths.
Focused tests cover arithmetic, unary operations, direct lowering, casts, strings and
incremental recovery. Native arithmetic tests cover effectful operands and checked
failures; string tests cover independent copies in all three flavors. Nine recovery
executables cover edits, declaration deletion, repeated failures and re-addition against
fresh output, retaining unchanged caller ASTs. Raw results are in
`v0.2/build/safety-metadata-proof/`.

Single-run recovery timings, O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime
and fresh instrumented application objects (not a comparative speed benchmark):

| Flavor / destination | Native wall ms | Frontend ms | LLVM backend ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp_byte | 185.56 | 18.18 | 23.69 | 87.00 |
| cpp_int | 180.55 | 26.10 | 26.07 | 88.00 |
| cpp_string | 166.33 | 29.57 | 22.24 | 82.00 |
| php_byte | 181.48 | 18.59 | 23.57 | 89.00 |
| php_int | 175.14 | 19.20 | 21.70 | 83.00 |
| php_string | 173.57 | 34.21 | 25.83 | 79.00 |
| js_byte | 135.82 | 16.18 | 19.23 | 68.00 |
| js_int | 148.80 | 15.78 | 16.89 | 82.00 |
| js_string | 152.22 | 23.32 | 22.51 | 77.00 |

## Entry ABI binding

`compiler.entry.checked_binding` selects a named runtime binding with operand `int64`
and ABI result `native_int`. This does not register another source type. The generated
`bindings/entry.php` defines `Cpp_Entry_Binding::checked()` and `header()`; the generator
validates the bounded signature and public symbol/header syntax before publication.

A proven integer literal at the root of an entry return uses its decoded decimal spelling.
For other expressions, `Cpp_Exit` emits the native cast for proven values or the generated
helper for checked values. The emitter gets the
entry header from that helper even for proven returns, because it provides the existing
`SCPP_REQUIRE_ENTRY_TARGET` assertion. Target assertion emission, `Exit_Preparation`,
`entry_target`, diagnostics and the runtime range-checking implementation are unchanged.
Default emitted helper calls and include spelling are identical.

Structure changes are limited to the compiler entry metadata record, its named binding,
and one generated static helper artifact (registered in boot/CMake). Inventory version
17 includes this ninth artifact. No AST, prepared-fact, session or runtime structures
changed, and no per-expression metadata lookup was introduced.

Six focused CTests pass. Generator tests cover symbol/header substitution, invalid
references/signatures/spellings and publication cleanup/freshness including the new file.
`entry_binding.php` checks proven/checked spelling and all-flavor incremental failures,
retry without edits, provider deletion/re-addition, fresh-output equivalence and unchanged
caller AST reuse. Fifteen native proof programs cover proven success, checked success,
both runtime out-of-range directions and incremental recovery. Existing `entry_native.py`
also passes boundary-value checks, diagnostic locations, publication preservation and
mismatched-target rejection. Negative unary values retain the current fact-analysis
behavior; this slice does not expand constant propagation.

Raw proof artifacts: `v0.2/build/entry-binding-proof/`. Recovery timings below are single
correctness samples: O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime, fresh
instrumented application objects. Stage trace sums are not additive wall-time components.

| Flavor | Native ms | Frontend ms | LLVM ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 116.75 | 9.74 | 14.25 | 60.00 |
| php | 151.33 | 11.11 | 18.40 | 80.00 |
| js | 116.47 | 8.61 | 15.18 | 60.00 |

## String/output submodule providers

String storage and output header queries now consume the shared submodule plan.
Runtime string/output records own group placement; type bindings reference semantic
exports and output bindings reference their declaration group without duplicating paths. Application compilation still uses the existing
per-file header collection and does not interpret metadata. Global declaration
requirements are now collected before include emission, including standalone
emission without a cross-unit header planner.

The [partition plan](../../specs/planning/runtime_submodule_partitioning.md) records
scope and verification. Scalar representations, output signatures and other
runtime families are unchanged in this first slice.

Group export contracts now validate that migrated bindings select their actual
owner; those bindings cannot opt back into an independent header field. After
regenerating compiler PHP, restart the compiler process and any retained
`Incremental_Project` session. Loaded PHP classes are not hot-reloaded. A new
`Project_Compiler` process uses the existing compiler-input scan to invalidate
cached application output when its generated handlers change. The provider proof
covers this path against an existing application/native build for all flavors.


Type exports do not replace native scalar storage spellings with wrapper names.
`cpp_type`, checked construction and `extract` remain owned by their existing
representation/lowering contracts. Export spelling can select an already published
name, such as `scpp::byte` for `uint8`, without changing semantic identity or strategy.
The shared plan validates declaration placement before generating name/header queries.

## Native alias integration (2026-11-06)

Scalar wrappers and their boxed numeric conversion bindings are retired. Checked
conversions use named functions. `scalar_bool` references the bool_t type export
with null extraction; string_storage references the string export. Compiler scalar
representations now use the public alias names and headers from the same export
plan. Underlying native identity remains owned by target facts, not export spelling.
The earlier wrapper descriptions above record the pre-alias stages.

See the [integration handoff](../../specs/planning/alias_provider_integration.md)
for the deliberately pending combined verification.
