# Bounded for loops

Doc Status: supporting

The [loop contract](../../../specs/loop_statements_v0_2.md#bounded-for-slice--2026-11-04)
defines one required initializer (declaration/assignment), condition and assignment
update, with a braced body. Existing typed/inferred declarations and generated
conversions/truthiness apply in all three flavors. Omitted/comma clauses,
increments, standalone call clauses and foreach remain deferred. Int64 compound
assignments are supported in both initializer and update positions.

## Structure and ownership

`ast_for_statement` extends the existing loop node. It directly owns the initializer,
assignment update and header scope, alongside inherited condition/body ownership.
The body scope is a child of the header scope, which is a child of the enclosing
block scope. Header declarations have function-unique storage IDs and stay out of
the enclosing block's local index. One initializer cannot contain duplicate header
declarations; body duplicates retain the existing scoped duplicate diagnostics.

The parser reuses flavor statement grammar for initialization and the shared
assignment parser with an explicit closing-parenthesis delimiter for updates.
`Body_Collection::publish_scope()` publishes the header through `Scope_Collection`.
The enclosing occurrence index references header expressions without owning them.
No synthetic block, duplicate syntax list or separate prepared loop is introduced.
Reset and inspection cover initializer and update as well as test/body.

Preparation pushes one header frame, prepares initialization once, then reuses the
loop worker for test and body. It exits the body frame before preparing the update,
so body locals cannot resolve there. Value barriers precede repeated regions and
follow update/exit; there is no fixed point, path map copy or extra traversal.
Completion and loop-depth validation reuse the existing transfer machinery.
Each initializer/update counts as one statement visit. Calls in every clause use
the existing enclosing-body dependency watcher.

C++ lowering emits native `for`, preserving continue-to-update and break/return
suppression. Declaration/write rendering is shared between ordinary statements
and headers through one backend helper, including generated conversions, required
headers and identity-write handling. No new metadata or runtime API is required.

## Mining and tests

Reviewed the donor control-flow catalog and
`compiler/my-try/04_analyze/prepare/semantics/loops.php`: header scope, exclusion of
body locals from update and native for emission are useful concepts. Its arrays
of clauses, prepared loop records and path-specific local maps are not adopted.

`tests/for.php` covers all flavors, exact preparation counts, ownership/publication,
header/body shadowing, nonescaping header locals, typed/inferred initialization,
assignment to outer storage, string/bool conditions, zero iterations, nested loops,
continue/update order, break suppression, reset, inspection and emission purity.
It rejects unsupported clauses, body-local update targets and false guaranteed-return
claims. Incremental tests change update-callee signatures, recover without replacing
caller AST/tokens, reuse body-only edits, edit the loop source and verify no-op reuse.
Native proofs check output/exit and update-time conversion failure. Incremental native
emission remains outside this slice.

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
`v0.2/build/for-proof-2026-11-04/` (ignored build artifacts).


## Compound header extension — 2026-11-05

No AST or preparation structure changes are needed. Both header positions already
own ordinary assignment statements, whose optional operator identifies compounds.
The parser no longer rejects them. All ordinary admission, binding, latest-write
invalidation and per-clause preparation paths remain unchanged.

The shared `Cpp_Emitter::compound()` produces a snapshot assignment and final write
expression. Standalone statements wrap these in a block with a const temporary.
Header rendering sequences them using the built-in comma operator. The enclosing
loop supplies one scoped int64 temporary, shared by its initializer/update because
both currently have the same int64 storage contract and never overlap. Nested loops
own separate scopes; no source binding or prepared fact is added for this temporary.

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
`v0.2/build/for-compounds-proof-2026-11-05/` (ignored build output).

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
