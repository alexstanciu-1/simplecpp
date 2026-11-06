# Integer family arithmetic and comparison

Doc Status: supporting
Date: 2026-11-06

The [integer contract](../../../specs/compact_layout_types.md#7-v02-integer-arithmetic-and-numeric-comparisons)
owns semantics. Active int64/int and uint8/byte support `+ - * / %` and all six
ordinary comparisons in both operand orders. Same canonical types preserve their
arithmetic identity; mixed int64/uint8 selects int64. Comparisons return bool and
preserve exact integer values. Literals remain int64 and there is no literal-value
promotion exception.

## Generation ownership

`promotion_policies.integer_domain_maximum` remains the common runtime/compiler
policy. `integerPromotionPolicy` validates it; `integerExecution` selects the domain
from representation signedness and width. The runtime resolver reuses this helper.
`compiler.integer_operators` owns the admitted arithmetic/comparison groups, named
helper bindings and compound-enabled identities. It replaces the separate int64
arithmetic and int64/uint8 comparison metadata rows.

`compilerIntegerOperations` expands that group for active integer definitions at
generation time, then reuses the existing binary/promotion generators. It derives
promotion permission for existing lossless integer conversions. It neither grants
ordinary assignment conversion nor searches for multi-step conversions. Existing
floating promotion rows and their permissions stay independent.

Generated handlers consume already-prepared operands and attach execution/result
types and operand conversions to the existing binary AST node. No extra traversal,
metadata interpretation, folding, watcher or per-expression registry is added.
Generation uses temporary normalized arrays/objects; no retained compiler model
changes. Lowering consumes the settled facts and preserves its existing sequencing.

Native C++ promotes small integers during expressions. Generated arithmetic explicitly
restores the selected integer result representation, including before an enclosing
expression consumes a uint8 result. Thus `(byte)255 + (byte)1` produces uint8 zero,
while `(byte)255 + 1` produces int64 256. Signed overflow remains under the existing
native contract; no new overflow checks are introduced. Named uint8 divide/remainder
bindings use the runtime's existing generic integer helpers and zero-divisor errors.

The currently active mixed comparison pair has a lossless int64 execution domain.
Optional uint64/signed comparisons will need exact mixed-domain lowering before
activation; the generator rejects unsupported activation rather than silently
rounding or converting negative values to unsigned. Runtime generic comparisons
already use their exact signedness-aware helpers.

## Boundaries and proofs

The subsequent [integer compound slice](integer_compounds.md) enables checked write-back.
Byte updates/unary signs, byte/float operations, byte truthiness,
byte echo, and optional integer source types are unchanged/deferred. Existing int64
compounds retain their current admission. Ordinary assignment stays same-type-only.

`tests/integer_promotion.php` checks result/execution identities, one visit per operand,
pure lowering, wrapping unsigned nested results, mixed ordering/equality at signed
boundaries, zero divisors, rejected families, and unsupported float compounds. Incremental
checks cover byte/int/uint8 signature edits, invalid bool signatures, overflow,
retry without edits, deletion/re-addition and caller edits; unchanged caller ASTs
remain reused and successful artifacts match fresh compilation.

`tests/integer_promotion_native.py` builds/runs 33 executables across all three flavors,
including error observers and incremental recovery artifacts. Raw proof and Clang
traces: `v0.2/build/integer-promotion-proof-2026-11-06-final/`. O2/g0, libc++, Ninja8, mold,
no PCH/LTO; rebuilt runtime archive reused, fresh instrumented application objects.
These are single samples alongside generator regression tests, not a comparative speed benchmark:

| Flavor | Native wall ms | Frontend ms | LLVM backend ms | Link command ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 212.48 | 35.51 | 19.10 | 97 |
| php | 173.31 | 27.14 | 15.79 | 73 |
| js | 220.96 | 33.09 | 19.19 | 87 |

PHP fixture generation took 291.32 ms for the full proof. LLVM includes optimization
and code generation; cumulative trace work is not additive parallel wall time.
