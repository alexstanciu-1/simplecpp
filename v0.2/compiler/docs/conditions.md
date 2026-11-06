# Generated truthiness and braced conditionals

Doc Status: supporting

The [conditional contract](../../../specs/conditional_statements_v0_2.md) owns semantics.
All flavors admit braced if/elseif/else-if/else, nested arms, and branch-local declarations.
Unbraced arms, standalone blocks, ternary, null/byte conditions,
constant-condition reachability and precise value merging remain deferred.
Logical `!`, `&&` and `||` now reuse this generated truthiness; see [logical expressions](logical.md).

## Type boundary

Runtime metadata `compiler.conditions` admits bool identity, integer/float nonzero,
and string nonempty-except-exactly-zero. The API generator emits `prepare_condition`,
`lower_cpp_condition` and `condition_headers` on each admitted canonical type class.
Missing admission retains the rejecting base implementation. No metadata is interpreted
while preparing a source. Conditions do not call generated value-conversion handlers.
Generated string lowering binds the operand once to a const whole-string reference
inside an immediate noncapturing lambda, preserving returned-string lifetime. Only
`scpp/string.hpp` is needed; no broad runtime helper header or wrapper is introduced.
Condition readiness follows the prepared function body; there is no condition-fact object.

## Owners and preparation cost

`ast_statement_block` owns statements/scope/local indexes. Function bodies add
completion, statistics and watcher identity; ordinary `ast_block` adds no duplicated
function state. An if statement owns a valid list of arms, each owning its condition
and block. No syntax is copied into a prepared tree. Branch collectors point to their
lexical parent and share one function-local storage-ID allocator, preserving flat-body
IDs while giving every nested local unique storage. Duplicate chains stay scope-owned.

Preparation uses transient name frames, not copies of visible outer maps. Lookup walks
lexical depth. Each arm is prepared once, including unreachable arms. Alternatives and
joins clear the latest-write index and disable fallback to old initializer constants.
New assignments/declarations can establish fresh values. Clearing touches only retained
writes, not the whole symbol table; entries discarded were created by prior preparation
work. There is no fixed point, branch-result comparison or per-branch watcher. Types,
bindings and earlier value snapshots survive. Reference-parameter reads remain unknown.

The statement traversal returns transient completion flags for fallthrough, return, break and continue. A chain returns
only with a final else and returns from every arm. The enclosing function supplies the
return contract directly, so nested blocks have no extra function backlink or copied
signature. Reset, duplicate diagnostics, inspection and lowering traverse owned blocks.
C++ lowering emits direct braced branches and consumes prepared condition types.

## Incremental and validation

Conditions and calls inside nested blocks register dependencies on the enclosing function
body. A callee return-type change invalidates that body without reparsing it. A body-only
callee edit retains the caller; unsupported condition types diagnose and recover through
the existing watcher/reset mechanism. This adds no incremental emission path.

`tests/conditions.php` proves truthiness boundaries, single evaluation/lazy arms, nested
scope shadowing, parent ownership, duplicate errors, nonescaping names, structural return
completion, checking unreachable syntax, reset/purity/inspection and incremental signature
edit/rejection/recovery. Native fixtures verify all three flavors, mixed-flavor cross-file
calls and runtime range checks after sibling/join value invalidation. The generator proof
checks metadata-driven admission/removal and rejects an unspecified truthiness policy.

## Native proof

2026-11-04: ten executables passed. `-O2 -g0`, libc++, mold, Ninja `-j8`, no PCH/LTO,
reused runtime archive and fresh instrumented app objects. PHP fixture generation,
including incremental checks, took 103.566 ms. These are individual correctness builds,
not repeated-run benchmarks. Frontend/backend traces overlap and are not additive wall time.

| Program | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 186.024 | 21.334 | 36.939 | 15.013 | 19.721 | 67.000 |
| cpp_sibling | 133.024 | 18.733 | 7.716 | 2.878 | 3.350 | 62.000 |
| cpp_join | 126.546 | 13.089 | 7.463 | 3.152 | 2.784 | 63.000 |
| php | 156.091 | 15.646 | 24.876 | 10.420 | 13.041 | 71.000 |
| php_sibling | 144.068 | 16.517 | 6.905 | 2.773 | 2.693 | 66.000 |
| php_join | 137.428 | 13.615 | 6.659 | 2.693 | 2.440 | 65.000 |
| js | 171.716 | 19.373 | 27.945 | 13.280 | 13.077 | 79.000 |
| js_sibling | 131.605 | 13.830 | 7.344 | 2.767 | 3.192 | 63.000 |
| js_join | 126.908 | 13.481 | 7.539 | 2.776 | 3.011 | 62.000 |
| multifile | 131.509 | 23.630 | 27.037 | 9.517 | 10.284 | 63.000 |

Braced while/do-while now reuse these block and condition owners; see [loops](loops.md).
The backend condition helper and shared braced-block parser serve both branches and loops.

## Condition strategy and binding ownership

`compiler.conditions` remains the only admission list. The generator's
`compilerConditionPlan()` validates each row once against the resolved type strategy:
identity requires boolean; integer nonzero requires integer; floating nonzero requires
floating; the string rule requires string storage. This replaces the second fixed
identity-to-rule mapping without allowing null/byte conditions or extra conversions.

String truthiness uses the resolved storage binding's symbol and public header. The
handwritten algorithm still evaluates its operand once and rejects empty strings and
exactly `"0"`; other strings, including NUL bytes and `"00"`, are true. Other strategy
algorithms and diagnostics are unchanged. Missing rows retain base condition rejection.

With current metadata, all nine generated artifacts are byte-identical, including the
inventory version. No metadata schema, AST, preparation facts, runtime or emitter
structure changed; the only new model is a generation-time normalized condition plan.
Generator mutation tests check storage binding use, the full current type/strategy
admission matrix and compatibility independent of identity spelling.

Six focused CTests pass (truthiness bindings, conditions, logical operators, loops,
generator and runtime freshness). Existing native condition proofs also pass. The new
`tests/truthiness_bindings.php` checks cpp/php/js signature/value edits, null/byte
rejection, provider declaration deletion, repeated failures without edits, re-addition
and recovery against fresh emitted output. Unchanged caller ASTs are reused and no-op
runs prepare no bodies. All 33 native programs confirm expected truth values and one
provider evaluation per condition. Raw traces/results:
`v0.2/build/truthiness-binding-proof/` and `v0.2/build/truthiness-condition-regression/`.

Single-run ranges across native recovery states: O2/g0, libc++, Ninja8, mold, no
PCH/LTO, prebuilt runtime and fresh instrumented application objects. Concurrent proof
suites make these correctness timings unsuitable for speedup claims; trace stage sums
are not additive wall-time components.

| Flavor | Native ms | Frontend ms | LLVM ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 138.07–282.79 | 16.93–41.00 | 17.45–36.62 | 61.00–154.00 |
| php | 128.81–162.70 | 14.82–35.17 | 16.35–30.82 | 66.00–85.00 |
| js | 131.81–154.63 | 14.82–33.35 | 16.96–28.12 | 65.00–75.00 |
