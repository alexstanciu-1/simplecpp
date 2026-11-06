# Integer compounds with checked write-back

Doc Status: supporting
Date: 2026-11-06

The [scalar contract](../../../specs/compiler_scalars_v0_2.md#integer-compound-write-back--2026-11-06)
admits `+= -= *= /= %=` for the active int64/int and uint8/byte representations,
in every operand pairing. Ordinary binary promotion selects the calculation type;
its result is converted back to the destination using the checked integer conversion
contract. Ordinary assignment remains same-type-only.

Same-type byte arithmetic retains uint8 semantics. Starting from byte 250,
`value += 10` calculates int64 260 and fails write-back; `value += (byte)10`
calculates uint8 4 and stores it. The check applies to the calculated result, not
the RHS: byte 255 divided by int64 256 stores zero successfully. Signed arithmetic
overflow keeps its existing native limitations; checked narrowing does not add
checked signed arithmetic.

## Ownership and metadata

`compiler.integer_operators.compounds` owns enabled destination identities and
`write_back: checked`. The generator derives compound operators from the admitted
arithmetic group and shared integer promotion policy. The int64→uint8 conversion
row explicitly grants the new `compound_write_back` context; removing it rejects
incomplete generator metadata rather than emitting unchecked stores.

Existing conversion algorithms, named runtime bindings and `value_conversion`
values are reused. Mixed handlers attach decisions directly; preparation does not
interpret a table, construct a synthetic binary expression, fold arithmetic or
visit the RHS twice. Same-type handlers attach identity conversions.

Structure changes:

- `ast_assignment_statement` adds a non-owning execution-type reference, saved-left
  conversion and result write-back conversion as protected prepared facts. The
  existing conversion property continues to describe RHS conversion. Accessors
  require compound readiness; reset clears the new facts with the existing binding.
- `prepare_compound(statement, local): void` now settles those facts on the AST,
  instead of returning only an RHS conversion. Ordinary assignment still follows
  its existing conversion path.
- `conversion_context::compound_write_back` is appended with backing value 5.
  No new conversion operation, retained preparation record, watcher or queue exists.
- Stage inspection exposes compound execution type, left conversion and write-back
  alongside the existing RHS conversion. Both body and for-header views use this path.

## Lowering and failures

The shared compound emitter preserves the old destination before evaluating the RHS,
converts both operands to the prepared execution type, lowers the operation, applies
the destination's generated result conversion and only then stores. Statement bodies
and for headers use the same mechanism. No new lambda or sequencing mechanism is added.

An RHS failure, zero divisor or out-of-range result prevents the final store. Already
performed RHS effects remain; there is no rollback. Locals and value/reference
parameters are supported. Global/field/indexed writes, expression-valued compounds,
float→integer compounds and new operator families remain deferred.

## Verification

All three flavors cover all five operators and all four int64/uint8 pairings,
same-byte reduction, mixed negative RHS values, reference parameters, self reads,
for initializers/updates, and single-visit preparation. Native observers verify
upper/lower range failures, zero divisors, no premature RHS narrowing, no final store
on failure, and exactly-once RHS side effects that survive failure.

Incremental checks alternate byte/int/uint8 function signatures, reject bool and
out-of-range declarations, retry unchanged failures, delete/re-add the provider,
change the caller and verify no-op reuse. Unchanged caller ASTs stay shared;
successful incremental artifacts match fresh compilation and execute natively.

The proof uses `tests/integer_compounds.php`, its native runner and a test-only C++
exception observer. Timings and raw artifacts are retained under
`v0.2/build/integer-compounds-proof-2026-11-06-final/`.

The final native proof ran 30 executables. Configuration: O2/g0, libc++, Ninja8,
mold, no PCH/LTO, prebuilt runtime, fresh instrumented application objects. Single
samples, not comparative averages; generator contract tests were also running.

| Flavor | Native wall ms | Frontend ms | LLVM backend ms | Link command ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 197.36 | 22.11 | 33.40 | 75 |
| php | 209.07 | 25.39 | 30.79 | 85 |
| js | 158.89 | 16.49 | 26.71 | 70 |

Full PHP fixture generation: 227.67 ms. LLVM includes optimization
and code generation; cumulative traces are not additive parallel wall time.

Generator proofs passed 19 accepted/537 rejected scalar cases and 17 accepted/37
rejected publication cases. Metadata-key snapshot hashes were refreshed; all prior
runtime artifact fingerprints and native layout facts remain unchanged.
