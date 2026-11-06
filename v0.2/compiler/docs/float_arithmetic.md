# Metadata-driven float64 arithmetic

Doc Status: supporting

The [scalar contract](../../../specs/compiler_scalars_v0_2.md#float64-arithmetic--2026-11-05)
owns the semantics. All flavors support same-type `float`/`float64` binary
`+ - * /` and unary signs. Mixed arithmetic uses the [shared promotion path](mixed_arithmetic.md); float remainder remains deferred; float echo is not added. [Discarded-value updates](float_updates.md) now reuse the existing update path.

## Ownership and preparation

Runtime metadata adds `compiler.arithmetic.float64` and `compiler.unary.float64`.
The existing generator produces admission, result identity, C++ expressions, required
headers and reorderability methods on the canonical `type_float64` singleton.
Generation validates same-type results, native overflow and checked division;
removing an operator or capability removes its generated support. Inventory version
14 causes a clean regeneration of generator-owned compiler artifacts.

The existing `ast_binary_expression` and `ast_unary_expression` retain their children
and inherited prepared result-type fact. There are no new AST classes, copied operands,
prepared wrappers, dependency indexes or per-occurrence handlers. Preparation visits
each operand once and dispatches to its generated type. It does not fold arithmetic,
walk foreign bodies or add listeners. Calls and symbol reads use existing body watches.
Incremental reset, grouped queues, selective emission and cleanup remain unchanged.

One admission boundary is now explicit: metadata's `compound` boolean generates
`supports_compound($operator)`. Assignment preparation requires this capability as well as the
existing binary result/write-back identity check. Int64 remains enabled; float64 was initially disabled and is now enabled by
the [float compound slice](float_compounds.md). This prevents adding ordinary arithmetic from accidentally widening writes.

## Native boundary

Addition, subtraction, multiplication and unary signs emit parenthesized native
`double` operations. Only division adds `<scpp/arithmetic.hpp>` and calls
`scpp::float64_divide(double, double)`. Its compiled implementation delegates to the
existing floating scalar semantics. The public header acquires no heavy includes.
Division remains classified as potentially throwing; current sequencing behavior is
preserved without extending the separately deferred execution-order work.

Both signs of zero as divisor throw `division_by_zero`, even with zero/NaN/infinity
numerators. Other arithmetic permits IEEE infinity/NaN and gradual underflow/signed
zero. Literal overflow remains a preparation error; arithmetic results do not pass
through the finite-literal check. No fast-math flags or floating environment controls
are introduced.

## Verification — 2026-11-05

`tests/float_arithmetic.php` checks all three flavors, unsupported mixed/remainder/
compound cases, one preparation visit per operand, absence of constant folding,
lowering purity and runtime include selection. Incremental edits cover provider body
changes, signature changes, literal overflow, retries without edits, recovery and a
no-op. Unchanged caller AST identity survives; every successful changed generation's
complete C++/header output equals a fresh compilation byte for byte.

`tests/float_arithmetic_native.py` uses the existing instrumented build runner.
Test-only native observers verify finite results, overflow infinity, NaN propagation,
subnormal results, signed zero and twelve numerator/zero-divisor combinations per
flavor. Recovery executables print `Y` and exit 8. Eight focused CTests passed,
including generator removal/policy tests and existing arithmetic, unary, compounds,
floating, runtime helper and runtime freshness checks; four further CTests passed for native integer/unary/compound regression and the
numeric contract. Compiler PHP style checks pass.

Configuration: O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime archive,
fresh instrumented application objects. Single correctness samples, not a comparative
performance benchmark. PHP fixture generation including all semantic/incremental
checks took 125.50 ms.

| Recovery executable | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 123.47 | 12.52 | 17.32 | 6.35 | 5.71 | 60 |
| php | 126.09 | 13.02 | 18.77 | 6.54 | 6.39 | 61 |
| js | 131.55 | 11.98 | 18.48 | 6.52 | 6.12 | 62 |

The IEEE observer fixtures intentionally include `<cmath>`, limits and exception
inspection: native wall 586.57–753.00 ms, frontend 448.28–574.76 ms, LLVM backend
25.77–45.17 ms and link 58–64 ms. These test-only include costs are absent from the
ordinary recovery executables. Frontend/backend are cumulative across translation
units; LLVM subcategories overlap, so they are not additive wall-time components.

Raw fixtures, generated C++, logs, traces and timings are retained under
`v0.2/build/float-arithmetic-proof-2026-11-05/`.

```sh
XDEBUG_MODE=off php v0.2/compiler/tests/float_arithmetic.php
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/float_arithmetic_native.py --output v0.2/build/float-arithmetic-proof-new
```
