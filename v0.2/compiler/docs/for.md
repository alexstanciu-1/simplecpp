# Bounded for loops

Doc Status: supporting

The [loop contract](../../../specs/loop_statements_v0_2.md#bounded-for-slice--2026-11-04)
defines one required initializer (declaration/assignment), condition and assignment
update, with a braced body. Existing typed/inferred declarations and generated
conversions/truthiness apply in all three flavors. Omitted/comma clauses,
increments, compound updates, standalone call clauses and foreach remain deferred.

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
