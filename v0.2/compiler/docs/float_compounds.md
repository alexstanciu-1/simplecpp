# Metadata-driven float compound statements

Doc Status: supporting

The [scalar contract](../../../specs/compiler_scalars_v0_2.md#float64-compound-statements--2026-11-05)
owns this slice. All flavors now support `+= -= *= /=` on float/float64 locals,
value parameters and mutable-reference parameters, including supported `for`
initializer/update clauses. [Mixed int64 RHS](mixed_compounds.md) now uses the same path.
Remainder, global writes, complex
lvalues and assignment expression results remain deferred.

## Implementation and structures

Only `compiler.arithmetic.float64.compound` changes to true in runtime metadata.
Regeneration enables `type_float64::supports_compound($operator)`. The existing binary
admission enforces same-type operands; generated lowering selects native operations
or the existing checked division helper. Disabling the metadata flag again disables
compound admission, as covered by the generator proof.

No new AST classes, fields, prepared records, queues, watchers or runtime helpers
are introduced. The parser, assignment preparation and C++ lowering are unchanged.
Preparation resolves the destination once and visits the RHS once, without synthetic
binary syntax or constant folding. Existing latest-write facts invalidate the old
constant. The existing compound snapshot preserves the old destination before RHS
effects. Both statement and for-header emission use the canonical type's `cpp_type()`.

The existing runtime division helper throws on either sign of zero. A failed
operation does not perform the final store; earlier RHS mutations remain visible.
Other arithmetic retains IEEE infinity/NaN, underflow and signed-zero behavior.
The specification and donor compound mechanism were reviewed; no donor structures
were adopted and no general expression-order machinery was expanded.

## Verification

`tests/float_compounds.php` covers all operators, aliases, ordinary/value/reference
storage, self reads, an RHS that mutates the destination, and compound for headers.
It checks one RHS preparation visit, unknown post-write constants, binding identity,
lowering purity, minimal helper includes and rejected mixed/remainder operands.

Incremental tests reuse an unchanged caller AST through provider edits, signature
failure, retry without edits, literal overflow and recovery. Successful output is
compared byte for byte with fresh C++/headers. A provider body-only edit prepares
one body; signature recovery prepares its dependent caller too. A caller operator
edit prepares only that body; no-op prepares none. Recovered and operator-edited
programs are built and run natively for every flavor.

Test-only C++ observers inspect reference storage after zero division, RHS mutation
followed by failure, and failure within the RHS. They also check overflow infinity,
NaN and signed underflow to zero. These observer headers are not compiler-generated
application dependencies. Raw artifacts and measured Clang traces are retained at
`v0.2/build/float-compounds-proof-2026-11-05-final/`.

```sh
XDEBUG_MODE=off php v0.2/compiler/tests/float_compounds.php
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/float_compounds_native.py --output v0.2/build/float-compounds-proof-new
```

## Recorded checks and timings

Eight focused CTests passed: float compound/arithmetic semantics, existing integer
compounds and for loops, generator admission, runtime freshness, and native compound/
for regression. All twelve new native fixtures passed across the three flavors.
PHP style checks and compiler generation freshness passed.

O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime, fresh instrumented
application objects in a new output directory. Single correctness samples, not
statistical performance comparisons. Frontend/LLVM are cumulative across units;
optimizer/codegen overlap LLVM totals and are not additive wall-time components.

| Main fixture | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 152.30 | 14.00 | 20.65 | 9.46 | 9.15 | 70.00 |
| php | 123.73 | 9.12 | 12.45 | 4.82 | 6.16 | 56.00 |
| js | 141.09 | 10.53 | 14.61 | 6.21 | 6.60 | 65.00 |

PHP fixture generation plus semantic/incremental checks: 138.27 ms.
Recovery: native wall 132.58–145.66 ms, frontend 13.78–16.78 ms, LLVM 17.76–20.14 ms, link 64.00–73.00 ms.
Operator edit: native wall 126.15–145.11 ms, frontend 13.10–13.45 ms, LLVM 16.96–18.34 ms, link 61.00–77.00 ms.
Test-only IEEE/exception observers: native wall 658.37–690.15 ms, frontend 476.43–519.26 ms, LLVM 39.86–42.24 ms, link 70.00–79.00 ms.

[Discarded-value float updates](float_updates.md) now extend the same storage-write ownership.
