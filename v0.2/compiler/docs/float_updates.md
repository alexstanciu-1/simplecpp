# Metadata-driven float64 update statements

Doc Status: supporting

The [scalar contract](../../../specs/compiler_scalars_v0_2.md#discarded-value-float64-updates--2026-11-05)
owns semantics. All flavors admit the four `++x`, `x++`, `--x`, `x--` spellings on
float/float64 locals and value/reference parameters, in statements and supported
`for` header positions. Update expression results, global/constant writes and
complex lvalues remain outside this slice.

## Ownership and efficiency

Metadata adds `compiler.updates.float64`, selecting increment/decrement, native
overflow and native lowering. The existing generator now admits that canonical type
and emits `supports_update()` / `lower_cpp_update()`. Compiler inventory version 15
regenerates the owned outputs. Removal/subset tests verify that admission follows
the metadata. No handwritten float dispatch is introduced in preparation/lowering.

There are no new AST classes, properties, prepared records, watches, queues or runtime
helpers. Existing `ast_update_statement`/`ast_storage_write` retain the destination,
direction, postfix spelling and prepared storage link. The same preparation method
resolves storage once and invalidates its cached value; no RHS, synthetic literal,
extra expression visit, constant folding or traversal is introduced.

Native C++ `++`/`--` uses the generated double representation, updating the storage
once. Rounding can leave large values unchanged. NaN/infinity, subnormals and zero
follow existing native floating semantics; no fast-math/environment controls are
added. The previous scalar update and donor-reviewed storage protocol remain in
place; no donor structures or general sequencing machinery are adopted.

## Verification

`tests/float_updates.php` covers all spellings, value/reference parameters, locals,
for initializer/update positions, lowering purity and absence of arithmetic helper
includes. An initializer plus update plus return counts only two expression visits.
A conversion after an update proves that an old literal fact cannot survive the
write: out-of-range conversion is checked at runtime rather than folded prematurely.
Unsupported update expression contexts still diagnose during parsing.

Incremental tests cover inferred locals following provider return contracts,
body-only changes, bool-signature rejection, retry without edits, literal overflow,
recovery, a caller operator edit and no-op reuse. Unchanged caller ASTs survive.
All successful outputs equal fresh C++/headers byte for byte. Body-only and caller
operator edits prepare one body, signature recovery includes its observer, and
no-op prepares none. Recovery and operator-edit executables are built and run.

The native observer checks all four forms with fractional values, both zero signs,
exact cancellation, subnormals, large finite values unchanged by rounding, infinities
and NaN. Observer-only `<cmath>`/limits includes do not enter ordinary generated
application code. No NaN payload or non-default rounding-mode guarantee is claimed.

```sh
XDEBUG_MODE=off php v0.2/compiler/tests/float_updates.php
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/float_updates_native.py --output v0.2/build/float-updates-proof-new
```

## Recorded native timings

Seven focused CTests passed, including existing integer update native regression,
float compounds, for loops, generator admission and runtime freshness. All fifteen
new native fixtures passed across the three flavors. Compiler PHP style and generated
compiler freshness checks passed.

O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime and fresh instrumented
application objects in a new directory. Single correctness samples, not a statistical
performance comparison. Trace totals accumulate across units and overlap; LLVM
optimizer/codegen categories are not additive wall-time components.

| Main fixture | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 136.67 | 10.60 | 13.70 | 7.09 | 4.73 | 64.00 |
| php | 143.69 | 9.94 | 13.37 | 6.48 | 4.90 | 69.00 |
| js | 125.84 | 8.95 | 8.83 | 3.99 | 3.29 | 63.00 |

PHP fixture generation and semantic/incremental checks: 144.84 ms.
Recovery: native wall 123.40–133.20 ms, frontend 11.83–13.43 ms, LLVM 16.88–18.52 ms, link 61.00–68.00 ms.
Operator edit: native wall 127.54–128.86 ms, frontend 10.84–13.94 ms, LLVM 16.10–16.85 ms, link 64.00–64.00 ms.
Test-only IEEE observers: native wall 250.22–274.86 ms, frontend 116.58–141.54 ms, LLVM 19.55–23.00 ms, link 62.00–63.00 ms.

Raw fixtures, C++, build logs, traces and results: `v0.2/build/float-updates-proof-2026-11-05/`.
