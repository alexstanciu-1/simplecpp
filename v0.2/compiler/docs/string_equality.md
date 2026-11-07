# Metadata-driven string equality

Doc Status: supporting

The [scalar contract](../../../specs/compiler_scalars_v0_2.md#string-equality--2026-11-06)
admits exact string/string `== !=` in all flavors. Equality compares complete bytes
and length, including embedded NULs, independently of storage identity. There is no
numeric coercion, case folding, Unicode normalization or locale collation. Ordering,
this original slice deferred strict identity, cross-type equality, carriers and text conversions.
Current carrier payload equality is covered by [carrier values](carrier_values.md).

## Ownership and structure

`compiler.comparisons.string` selects `runtime_compare` with bool result and the
named `boxed_bool` extraction binding. Comparison validation cross-checks the
runtime string operations and limits this slice to equality/inequality. The shared
binary generation plan retains the existing string storage binding and result
binding. Generated lowering calls the public runtime operators and extracts their
bool_t result as native bool. Headers come from bindings; private std::string is
never exposed or included by emitted application code.

The runtime already compares its private std::string values through const-reference
public operators. No runtime helper or implementation change was needed. Generation
adds the comparison strategy and bool binding; no AST classes/fields, enums, retained
preparation records, queues or watcher changes. Handwritten preparation and emission
are unchanged. Preparation visits each operand once, with no folding, metadata
lookup or synthesized nodes. The strategy is conservatively non-reorderable so the
existing emitter snapshots values before RHS effects. Copy optimization is separate.

## Verification — 2026-11-06

All flavors cover empty strings, different lengths, numeric-looking strings, case,
embedded NULs, differing Unicode encodings, locals, value parameters, returns and
RHS mutation through a reference. Native observers compare an independent length/
byte-loop oracle, including 0xff runtime input without adding source literal syntax.
Prepared result/type, visit counts, reset/reprepare and emission purity are checked.
Ordering and string/numeric/bool/null/byte pairs remain rejected. Generator negatives
cover missing runtime operations, invalid result bindings, unsupported ordering and
unsafe policy; removed admission and renamed extraction binding affect generated code.

Incremental proofs cover body-only edits, invalid return signatures, retries without
edits, recovery, declaration deletion/re-addition, caller operator edits and no-op
reuse. Caller AST identity survives provider edits. Successful C++/headers equal fresh
compilation byte for byte; fresh invalid inputs diagnose too. Both successful provider
checkpoints and caller edits execute natively for every flavor. All 15 new native
fixtures and 10 selected CTests passed. Style, diff whitespace and runtime freshness
checks passed.

```sh
XDEBUG_MODE=off php v0.2/compiler/tests/string_equality.php
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/string_equality_native.py --output v0.2/build/string-equality-proof-new
```

## Clang timings

O2/g0, libc++, Ninja8, mold, no PCH/LTO; prebuilt runtime; fresh instrumented application
objects. Single correctness samples, not averages. Frontend/LLVM trace work is not
additive parallel wall time. LLVM includes optimizer and code generation.

| Main fixture | Native wall ms | Frontend ms | LLVM backend ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 224.07 | 39.67 | 51.40 | 76 |
| php | 156.02 | 15.70 | 22.01 | 70 |
| js | 230.51 | 30.46 | 27.42 | 70 |

Recovered artifacts built fresh natively: wall 121.75–282.97 ms, frontend 18.63–49.87 ms,
LLVM 17.37–47.81 ms, link 56–116 ms. Native byte observers: wall 162.66–188.63 ms,
frontend 20.46–27.81 ms, LLVM 30.70–53.72 ms, link 62–66 ms.
PHP fixture generation and semantic/incremental checks: 125.87 ms; not an isolated
preparation timing. Variation does not establish a flavor performance difference.
Raw builds/traces: `v0.2/build/string-equality-proof-2026-11-06/`.
