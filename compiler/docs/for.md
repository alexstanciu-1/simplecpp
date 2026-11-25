# Bounded for loops

Doc Status: supporting

The [loop contract](../../specs/loop_statements_v0_2.md#for-headers) defines
three ordered, independently optional header clauses. For example, `for (;;)`
needs only a braced body. `for (; trace(), ready(); step())` discards the trace
result, tests readiness and calls step after normal completion or continue.

## Structure and ownership

`ast_for_statement` owns the three lists, body and header scope. Declarations
become visible in written order, including to later initializers. The body owns
a child scope. Header items reuse ordinary declarations, storage writes, call
statements and expressions. The lists also drive collection, reset and inspection.

Preparation visits each item once. It uses ordinary statement preparation for
initializers and updates and applies generated truthiness only to the final test.
Existing repeating-region value barriers and dependency watches apply. An empty
test needs no synthetic true expression or constant folding.

Ordinary lowering emits native `for`. A scoped prelude accommodates multiple
declarations and compound snapshots. Generator lowering keeps live header locals
across suspension and routes continue through any pending updates. Header locals
are destroyed in reverse declaration order.

`tests/for.php` and its Clang harness cover clause order, skipped work, header scope,
compound writes, and deletion/edit recovery against fresh output across cpp/php/js.
`tests/yield.php` exercises multiple header locals and updates across suspension.

## Proof results — 2026-11-04

47 compiler/generator/freshness checks passed. 15 native executables passed:
six for fixtures, 6 transfer regressions and 3 string regressions.
Style checks passed. Final fixture output matches the native-tested sources exactly.
PHP fixture generation (including incremental checks) took 81.364 ms.

O2/g0, libc++, mold, Ninja8, no PCH/LTO, prebuilt runtime, fresh instrumented
application objects. These are single correctness builds, not benchmark medians.
Trace totals overlap; optimizer/codegen are parts of backend work, not additive
wall-time components.

| Fixture | Build ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 142.194 | 13.850 | 14.709 | 6.594 | 6.623 | 67.000 |
| cpp_update_range | 141.423 | 18.570 | 8.780 | 3.580 | 3.760 | 65.000 |
| php | 134.773 | 15.556 | 13.907 | 5.792 | 6.281 | 60.000 |
| php_update_range | 141.219 | 16.902 | 9.527 | 4.072 | 3.512 | 65.000 |
| js | 130.826 | 13.988 | 14.340 | 5.940 | 6.798 | 57.000 |
| js_update_range | 122.396 | 13.238 | 7.635 | 3.007 | 3.298 | 57.000 |

Raw results, generated sources and logs are retained locally in
`build/for-proof-2026-11-04/` (ignored build artifacts).


## Compound header extension — 2026-11-05

No AST or preparation structure changes are needed. Both header positions already
own ordinary assignment statements, whose optional operator identifies compounds.
The parser no longer rejects them. All ordinary admission, binding, latest-write
invalidation and per-clause preparation paths remain unchanged.

Native integer [compound writes](compounds.md) on plain locals use the same eligibility rule in both header clauses as in a standalone statement. When a snapshot is required, a compound initializer executes once in a block immediately before the native
loop. It uses the ordinary compound statement's local `const auto` snapshot.
A compound update requiring a snapshot has its own temporary of the update destination's C++ type,
scoped to the loop. The built-in comma operator sequences the snapshot and
write in the update clause, including when the body executes `continue`.

```cpp
{ int64_t compound_previous;
  for (int64_t i = 0; i < 10;
       (compound_previous = i, i = compound_previous + step(i))) {
      // Native continue reaches the update; break/return do not.
  }
}
```

The snapshot is assigned before every use; false conditions and skipped updates do
not read uninitialized storage. Native for emission preserves continue-to-update
without copying updates into bodies or rewriting transfer statements. There is no
new lambda or GNU statement expression. Compiler-generated comma sequencing does
not enable comma-separated clauses in source syntax.

Tests extend the existing for suite: all five operators in both positions, first
condition false, RHS reference mutation, continue/break/return, nested loops,
header/body shadowing, unknown/out-of-scope storage, unsupported RHS types, one-pass
statistics, reset/purity, incremental signature recovery and multifile native output.
Test-only C++ catches verify initializer/update failures preserve RHS mutations and
prevent the compound store. Existing standalone compound native proofs are rerun
against the shared emission refactor.


### Recorded header proof

All 46 selected compiler CTest checks passed (`^compiler_`, excluding native suites
and the persistent-service harness). Expanded for checks and writing checks passed.
All 18 for native programs and 21 standalone compound native regressions passed.
Proof artifacts are retained locally under
`build/for-compounds-proof-2026-11-05/` (ignored build output).

Compound-header fixture timings below are single instrumented fresh builds, not
medians: O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime. Trace times are
not additive wall time; optimizer/codegen are parts of LLVM work. Link measures
command duration.

| Flavor | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 165.99 | 17.24 | 17.17 | 9.35 | 5.73 | 67.00 |
| php | 132.48 | 11.32 | 10.49 | 5.21 | 3.80 | 64.00 |
| js | 158.34 | 14.15 | 13.98 | 7.39 | 4.91 | 71.00 |

PHP fixture generation and assertions took 176.94 ms across the complete for suite,
including incremental cases; this is not an isolated preparation-stage timing.


## Discarded-value updates

Header write slots now accept the `ast_storage_write` base shared by assignments
and `ast_update_statement`. Prefix/postfix increment/decrement therefore reuse the
existing header storage scope and preparation order. They have no RHS expression
to collect or reset. In the final update clause, a writable instance field or static
property uses the statement-level property update node. Its target expression is
collected and reset along with the condition. Normal iteration and `continue`
execute this clause once; `break` skips it. Native header emission calls the generated update handler
directly; it creates no temporary or lambda. Compound snapshot storage is allocated
only for clauses requiring a prior destination read. Mixed
assignment/compound/update headers and shadowing are covered by update native tests.
