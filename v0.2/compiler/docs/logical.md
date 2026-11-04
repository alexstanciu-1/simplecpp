# Logical expressions through generated truthiness

Doc Status: supporting

The [logical contract](../../../specs/expression_evaluation.md#php-first-logical-slice--2026-11-04)
defines `!`, `&&` and `||` uniformly across cpp/php/js. Each operand independently
admits the current condition types: bool, int64, float and string. Results are bool.
Null/byte conditions, keyword logical operators and xor remain deferred.

## Mining and agreed boundary

Reviewed `compiler/my-try/04_analyze/operators/preparation.php` and
`compiler/my-try/04_analyze/conversions/truthiness.php`: reuse condition interpretation,
prepare both operands, keep short-circuit execution, and register dependencies even
on skipped syntax. The donor's bool/bool or numeric/numeric restriction is deliberately
not adopted: the user approved independent current-condition admission, including
strings and bool/numeric mixing. Its separate prepared operator records and branch
path maps are not imported.

## Structure and cost

- `binary_operator` adds logical_and/logical_or IDs without changing existing IDs.
  Its precedence places OR below AND, then equality/relational/additive/multiplicative.
  Existing `ast_binary_expression` owns lazy operands as well as eager operands.
- `ast_not_expression` owns one operand and inherited result-type readiness. It
  retains no separate conversion record, branch state or copied operand facts.
- The parser consumes prefix negation through its existing primary recursion.
  Collection, reset and the two inspection serializers visit the owned child.
- Preparation visits each expression once. Generated `prepare_condition()` validates
  each operand; the AST receives the canonical bool result. There is no new type
  matrix, metadata interpretation, callee-body scan or preparation pass.

Current expressions cannot assign locals directly. Calls can only invalidate cached
local values through reference arguments. Preparing a potentially skipped RHS applies
that same conservative invalidation; it cannot publish a new constant. Consequently
this slice needs no branch map copies or merge. Revisit this boundary when assignment
expressions or other fact-producing conditional effects are introduced.

Lowering calls generated `lower_cpp_condition()` and `condition_headers()` and emits
built-in C++ `!`, `&&` and `||`. RHS text stays inside its lazy operand, including any
existing call/conversion wrappers. No sequencing lambda is added by these operators.
The existing generated string-truthiness helper still binds a returned string once;
this is not the deferred general lambda-removal experiment. Conservative parent
reordering classification is unchanged for these new expressions.

## Verification

`tests/logical.php` checks precedence, truthiness/mixed operands, AST ownership,
single-pass visit counts, skipped calls/failures, reference writes, nested loop
conditions, bool values/returns, unsupported types and errors on skipped RHS syntax.
It also checks reset/reprepare and emission purity plus inspection of the new node.
All three flavors exercise retained-session signature changes (bool to rejected
byte to string), caller AST/token reuse, body-only edits and no-op reuse. Native
emission of recovered source uses the separate full-build path; incremental native
emission is not introduced.

`tests/logical_native.py` runs the generated fixtures through the shared instrumented
Clang proof, retaining frontend, LLVM and linker timings. Existing generated
truthiness methods and their metadata remain unchanged; no regeneration is needed.

## Native proof — 2026-11-04

45 compiler/generator/freshness regression checks passed, plus nine logical native
executables and nine existing direct-lowering native regression executables.
Compiler style checks passed. PHP logical fixture generation, including incremental
checks, took 96.424 ms. These are individual instrumented correctness builds, not
repeated-run performance estimates; the first C++ run was slower.

Configuration: O2/g0, libc++, mold, Ninja8, no PCH/LTO, prebuilt runtime, fresh
application objects. Frontend/backend trace totals are nested/cumulative and must
not be added to wall time. LLVM optimizer/codegen columns are parts of backend work.

| Fixture | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 314.796 | 36.624 | 81.999 | 39.123 | 38.951 | 81.000 |
| cpp_reference | 168.142 | 20.055 | 9.735 | 3.881 | 4.252 | 88.000 |
| cpp_recovery | 144.340 | 23.914 | 21.740 | 8.361 | 7.412 | 67.000 |
| php | 189.469 | 24.178 | 51.000 | 23.151 | 26.235 | 62.000 |
| php_reference | 131.244 | 14.336 | 8.184 | 3.640 | 2.987 | 59.000 |
| php_recovery | 144.777 | 18.738 | 18.261 | 6.937 | 6.165 | 73.000 |
| js | 185.231 | 22.607 | 53.119 | 23.596 | 27.854 | 61.000 |
| js_reference | 129.272 | 13.314 | 8.343 | 3.207 | 3.635 | 62.000 |
| js_recovery | 135.174 | 20.919 | 20.496 | 6.925 | 8.088 | 64.000 |

Local generated sources, logs and raw results: `v0.2/build/logical-proof-2026-11-04/`
(ignored build artifacts). No preparation benchmark speedup is claimed.
