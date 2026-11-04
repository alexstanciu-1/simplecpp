# Canonical type definitions and conversions

Doc Status: supporting

The [scalar contract](../../../specs/compiler_scalars_v0_2.md) owns semantics.
The API generator emits canonical `type_int64`, `type_null`, `type_bool`,
`type_byte`, `type_float64` and `type_string` definitions plus `type_registry`. `int` and `int64` share one identity;
bool/null use the existing metadata identities bool_t/null_t internally. Instances
are shared definitions, never occurrence-specific records. `type_void` remains a
small handwritten non-value definition.

## Metadata and generated code

`runtime/metadata/runtime.json` owns:

- `compiler.modules.default-runtime.types`: always-active membership.
- `types.int64`: signedness and width; exact decimal limits are computed during generation.
- `byte`: unsigned width and storage identity, shared by runtime and compiler generation.
- `values`: runtime bool/null representation contracts.
- `source_types` and `compiler.value_types`: source names, generated type classes,
  compiler representations and boolean/null literal spellings and values.
- `compiler.arithmetic`: same-type int64 operators, result, native overflow and lowering policies; see [arithmetic](arithmetic.md).
- `compiler.comparisons`: admitted same-type pairs, operators, result identity and native lowering strategy.
  See [comparisons](comparisons.md) for ownership and sequencing.
- `compiler.integer_literals`: decimal, checked overflow and signed literal suffix policy.
- `compiler.floating_literals`: decimal radix, reject overflow, gradual signed-zero
  underflow and nearest/ties-even rounding.
- `compiler.conversions`: source/destination, explicit-intent requirement and implementation strategy.
  Scalar cross-family rules must also be admitted by runtime `scalar_casts`.

Generation validates the bounded policies, native facts and supported strategies.
Byte's width/storage facts are no longer hard-coded in the runtime scalar registry.
Generated methods contain direct branches; compilation does not interpret metadata
or build a conversion matrix. Unsupported policies fail before publication.

The output is `compiler/generated/types/{int64,null,bool,byte,float64,string,registry}.php` and
inventory version 10. Generated PHP is not tracked. CMake lists the byproducts and
checks freshness; regeneration cleans only the dedicated PHP/JSON extension set.

## Shared type contract

- `accepts_name()` and `type_registry::resolve()` provide canonical registration.
- `type_registry::literal()` supplies generated literal spelling lookup to the parser.
- `prepare_binary()` validates operands and attaches the result type; `lower_cpp_binary()` emits the approved operator.
- `prepare_literal()` validates a literal and attaches its value/type to the AST.
- `prepare_conversion(value, explicit)` selects a `value_conversion` enum at a destination boundary.
- `convert_constant(conversion, value)` derives a converted constant payload without changing the operand.
- `cpp_type()` and `lower_cpp_literal()` consume settled source facts.
- `lower_cpp_conversion(conversion, expression)` emits the approved operation, without resolving types again.
- `cpp_trivial_value()` and `cpp_binary_reorderable()` are generated backend-only safety queries; see [direct lowering](direct_lowering.md).
- `cpp_headers()` declares public storage headers; `conversion_headers()` adds conversion-specific headers.

Common same-type identity behavior lives in the base definition. Generated overrides
implement the metadata-listed conversions. Removing a conversion row removes its
handler branches. There is no prepared-value hierarchy or allocated conversion object.

A written declaration type supplies explicit intent, so `$v byte = 8;` is legal.
This is a declaration-bound conversion, not a synthetic cast node. Global inference
preserves the literal's source type. Local [inference](inference.md) preserves the
initializer's type except null; it does not authorize explicit conversion. No explicit source cast grammar is implemented.

## AST-owned facts

`ast_keyword_expression` retains the exact literal word and token range. The existing
expression facts now hold `int|float|bool|null` plus `constant_known`, preserving the
separation between a known null, unknown payload and unprepared type. `ast_float_expression` retains source tokens and a rounded binary64 payload through
these same facts; no second prepared-literal object is introduced. String literals retain byte-specific facts, described in [the string slice](strings.md).
Their scalar constant payload stays unknown; references do not copy bytes.

Local declarations retain the converted payload, known flag and approved initialization
operation. Global declarations own a general scalar expression child and approved
initialization operation; their checked literal remains on the child. Neither
conversion mutates the operand's original type/value. Global read occurrences stay
unknown and use their stable symbol links. Reset clears all new prepared facts.

Publication's lightweight global contract fingerprint includes the inferred literal
type. Changing an inferred global from integer to bool/null/float invalidates its observers;
a value-only edit retains the existing selective-reuse behavior. No new dependency
owner or additional watcher graph is introduced.

## Native lowering

Storage uses `__INT64_TYPE__`, `bool`, `__UINT8_TYPE__`, `double` and `decltype(nullptr)`.
Null's native carrier does not introduce pointer semantics or claim the ABI of the
runtime's distinct `scpp::null_t`; future runtime-call adapters must handle that boundary.

Known valid byte conversions emit a native static cast. Unknown int64-to-byte values
currently reuse `(scpp::byte{value}).native_value()` and include only the public
`scpp/integers.hpp` header. This preserves the established checked-conversion behavior
and evaluates the operand once. A lighter native public helper can replace it later
if measurements justify that work. No private runtime headers are emitted.

Bool-to-int and byte-to-int use native static casts; int-to-bool uses `value != 0`.
Float conversions and exact hexadecimal literal emission are described in
[the floating implementation](floating.md). Unknown float-to-int conversion reuses
the public runtime wrappers, while proven-safe conversions emit native casts.
Prepared conversion facts select these paths. The emitter does not reinterpret
metadata, mutate facts, resolve source symbols or permit C++ to decide source validity.

Echo and entry returns are explicitly restricted to their existing supported types.
Operators, overloads, source cast syntax, nullable values, optional modules and
additional types remain deferred.

## Verification

`tests/scalars.php` covers all three flavors, constant/unknown conversions, null
readiness, mismatches and byte overflow, public includes, lowering purity, inspection
and inferred-global incremental invalidation. `tests/scalars_native.py` compiles and
runs each flavor and checks runtime boundaries, single operand evaluation and unchanged
destination on failure. Generator tests validate fresh definitions, conversion removal,
metadata rejection and extension-scoped cleanup. All 33 selected compiler/generator/freshness
CTests passed, with the scalar test rerun after repairing a stale generated int64 file.

Native proof artifacts: `v0.2/build/core-scalars-proof-2026-11-04/`. O2/g0, libc++,
mold, Ninja -j8, no PCH/LTO, prebuilt runtime archive. Single instrumented runs, not
comparative performance claims:

| Flavor | Native wall ms | Clang frontend ms | LLVM backend ms | Link command ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 144.57 | 9.61 | 8.26 | 76 |
| php | 114.02 | 8.16 | 6.23 | 58 |
| js | 125.99 | 10.45 | 7.97 | 62 |

All generated programs printed `0125581` and exited 8. LLVM includes optimization and
code generation; trace totals exclude driver/startup work and are not an additive
breakdown of wall time. Raw JSON retains optimizer/codegen detail. PHP fixture generation
and its semantic/incremental assertions took 61.36 ms in that run.
