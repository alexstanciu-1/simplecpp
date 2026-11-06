# Generated float64 preparation and lowering

Doc Status: supporting

Current representation: [native scalar aliases](../../specs/native_scalar_aliases.md) supersedes earlier scalar wrapper spellings and extraction adapters.

The [source contract](../../../specs/compiler_scalars_v0_2.md#floating-literal-policy)
owns admission. `float` and `float64` share one generated canonical definition,
activated by default-runtime membership. Float32 and floating output formatting remain deferred. Explicit casts use the
shared conversion path; [float arithmetic](float_arithmetic.md) extends this definition.

## Literal preparation

`ast_float_expression` retains its token range, including an optional leading sign.
Generated `type_float64` reads the exact numeric token, applies its sign and converts
the decimal string to PHP binary64. Generated host checks validate precision and
normal exponent range; the target-fact validator already requires IEEE binary64.
The supported bootstrap environment uses ordinary nearest/ties-even rounding; it
does not expose floating-environment controls.

Preparation rejects nonfinite literal results. Subnormals and rounded zeros are
accepted with their sign intact. The existing AST-owned scalar facts now admit a
float payload; readiness/known-value semantics are unchanged. Constant conversions
produce destination facts without overwriting the initializer's original type/value.

This deliberately improves on my-try's deferred range validation: the donor preserved
decimal text and left overflow/underflow diagnostics to Clang. We retain original
source tokens for diagnostics, but settle the value during preparation so conversion
checks and native execution cannot disagree about its rounding.

## Exact emission

The generated formatter extracts binary64 bits with `pack('E', value)` and emits a
hexadecimal C++ floating literal. It does not use PHP's float-to-string formatting,
locale-dependent decimal output, or a second decimal conversion by Clang.

Examples:

```cpp
double normal = 0x1.0000000000000p3;       // 8.0
double tiny = 0x0.0000000000001p-1022;    // minimum positive subnormal
double underflow = -0x0.0000000000000p-1022; // negative zero
```

This also avoids Clang's too-small-decimal-literal warning after source-level
underflow has already been accepted. Inspection reports the same exact hexadecimal
spelling, preserving signed zero without depending on JSON consumer formatting.
The emitter remains read-only and uses native double storage without a runtime header.

## Conversions

Metadata emits direct handlers for int64/bool → float64, float64 → bool, and
float64 → int64. Written declaration types authorize these conversions. Runtime
`scalar_casts` must admit the corresponding family rule; compiler metadata cannot
request an unavailable runtime boundary. Byte/null conversions remain rejected.

Int64/bool → float64 uses native casts. Float64 → bool tests nonzero, including the
specified signed-zero/nonfinite behavior. Float64 → int64 truncates toward zero;
known values are checked during preparation against [-2^63, 2^63). The upper limit
is exclusive, avoiding rounding INT64_MAX to the wrong floating bound.

Unknown float64 → int64 uses:

```cpp
(scpp::int64{scpp::float64{value}}).native_value()
```

Only public `scpp/integers.hpp` and `scpp/values.hpp` are included. The existing
runtime check preserves failure behavior and evaluates the operand once. The
native carrier can be made lighter later if measurement justifies a dedicated
public helper; this slice does not add one.

## Structure changes

- Generated `type_float64`; registry adds float/float64 and literal-family lookup.
- `ast_float_expression`, using the existing type/constant/known facts.
- Scalar payload signatures expand to `int|float|bool|null`.
- `value_conversion` adds numeric-to-floating, floating-to-boolean and proven/checked
  floating-to-integer operations; no new conversion object is allocated.
- Normalized generator target facts now retain the already-validated double layout,
  precision and exponent record. Runtime publication artifacts do not change.
- Compiler inventory version 5 adds `types/float64.php`.
- Inferred-global fingerprints use the registry's literal-family lookup, so an
  int → float source edit invalidates the affected cached conversion without
  reparsing unchanged consumers.

## Validation — 2026-11-04

33 selected compiler/generator/freshness CTests passed. The separate runtime parity
capture verified 17 accepted and 37 rejected cases; only metadata-input hashes
changed, with identical artifact hashes, prepared models and target-fact snapshots.
Additional generator tests reject unsupported literal policies and missing runtime
conversion policies. Style checks and generated freshness passed.

The native proof checks exact expected bits for 17 cases in each flavor, including
two halfway ties, maximum finite rounding, minimum normal, subnormals, signed zero
and underflow to zero. Overflow and known int64 conversion failure diagnose before
lowering. Separate native boundary checks cover infinities/NaN, truncated values,
int64 bounds, one operand evaluation and unchanged destination after failure.

Raw artifacts: `v0.2/build/floating-proof-2026-11-04/`. All three programs printed
`-81` and exited 8. Configuration: O2/g0, libc++, mold, Ninja -j8, no PCH/LTO,
prebuilt runtime archive and fresh instrumented app objects. Each proof uses two
translation units, including test-only bit assertions; these are correctness timings,
not a compiler performance comparison.

| Flavor | Native wall ms | Frontend ms | LLVM backend ms | Link command ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 197.43 | 77.35 | 18.63 | 75 |
| php | 175.12 | 56.36 | 15.55 | 76 |
| js | 251.13 | 105.08 | 22.42 | 100 |

Frontend/LLVM are cumulative across the two units, not additive wall-time components.
LLVM includes optimization/code generation; raw JSON retains those subcategories.
PHP fixture generation plus semantic/incremental assertions took 82.44 ms in this run.

The native port must preserve correctly rounded decimal parsing, gradual underflow
and exact emission; ordinary underflow error codes must not accidentally become
source rejection. PHP-specific packing is a bootstrap implementation detail.
