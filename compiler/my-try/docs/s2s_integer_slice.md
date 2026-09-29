# Scalar S2S slices
Doc Status: supporting

Current scalar decisions live in the [literal catalog](catalog/01_literals_locals.md).
This guide describes the common path; the [initial implementation record](archive/s2s_integer_slice.md)
is historical and its token-based names/payload terminology are superseded.

## Model and ownership

Literal nodes own exact prepared scalar facts. Explicit declarations and inferred
first writes share storage preparation. References retain the same collected identity
and canonical type. Algorithms live under `04_analyze/prepare/semantics`; the C++
backend reads facts rather than resolving names or inferring types.

Language `int` is signed 64-bit under the Simple C++ contract, independent of the
parked LLVM experiment's i32 policy. Decimal integer/float spellings are normalized
without conversion through host PHP floating-point values. Native representation,
conversion and rounding belong to the target runtime/toolchain.

## Current generation surface

A first write such as `$a = 10;` introduces inferred integer storage. Reassignment
reuses it. Generated names now use saved source names with role prefixes, independent
of token positions; the representative spelling is `local_a`, not `local_0`.
Explicit scalar types, copies and supported same-type assignments use the same path.

## Boolean literal extension

`true` and `false` share boolean literal preparation with canonical language bool
identity. The backend emits the bool runtime representation and needed include.
This did not add boolean operators or unrestricted cross-type conversion.

## Proof

[tests/s2s.php](../tests/s2s.php) checks scalar facts, scope lookup, syntax purity and
output cases. It requires an existing output directory. It runs in PHP and writes
C++ cases; compilation/execution and native-compiler parity are separate checks.
Incremental retention/recovery is covered by the focused preparation/generation tests.
See [portability status](portability/conversion_review.md) before making native claims.
