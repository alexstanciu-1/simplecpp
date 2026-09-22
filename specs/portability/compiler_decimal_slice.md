# Exact decimal-range algorithm portability slice
Doc Status: supporting

The checking owner's existing repeated-division algorithm is now
`check_bodies\Decimal_Range::fits_positive` in `utilities/decimal_range.php`.
`Integer_Literals::resolve` retains definition/input validation, zero normalization,
signed-width selection, returned text and the original exception/message. It calls
the extracted predicate for the nonzero range decision. This is a local algorithm
extraction, not a new literal policy or accepted source-language feature.

The predicate requires normalized, nonzero ASCII decimal input. It never parses
the full value into a host integer. Its loose digit-length rejection and repeated
division are preserved. Each intermediate digit is 0..19: positive division by two
followed by an explicit integer cast replaces PHP-only `intdiv`, and a byte slice
of `0123456789` replaces `chr` for the resulting digit. These bounded operations
preserve the algorithm; arbitrary division/cast parity is not asserted.

## Local syntax and byte library

The converter now accepts for loops, increment, arithmetic/remainder, ordered
comparisons, concatenating assignment and explicit integer casts. PHP token
parsing checks source syntax; no variable or callee types are resolved by the
converter. Tests cover the bounded integer domain used by the real algorithm.

`scpp\string_byte_at` is a central runtime operation mapped to native
`string_byte_at`. The PHP implementation returns an unsigned byte value, or -1
for an out-of-range offset. All portable files receive the same updated managed
imports. This avoids confusing UTF-8 codepoints with source bytes.

## Evidence

[decimal-01](../planning/compiler_migration/results/decimal-01/summary.json)
passes PHP/native strict v0.1.76 parity, boundaries through 256 bits, a large early
rejection, byte values for UTF-8 input and out-of-range byte access. Sixteen compiler
fixtures pass, including the existing integer-conversion end-to-end integration
fixture in addition to the prior frontend/component tests.

`python3 tests/portability/decimal_range.py` checks 24,728 cases against Python's
independent arbitrary-precision integer oracle: exhaustive small ranges, seeded
larger values, power-of-two boundaries through 512 bits and the loose-bound path.
This larger oracle runs in PHP; the native witness uses the selected boundary set.
Converter and prologue regressions also pass. The owner patch and provenance record
show that validation/normalization/zero/width handling are unchanged.

Ten production files are now ready. The complete literal checker still depends on
the unmigrated type model and exception path; tokenizer/parser execution, indexed
sets and diagnostic serialization also remain pending.
