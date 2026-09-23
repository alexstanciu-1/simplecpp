# Lifetime consumption and cleanup plan
Doc Status: supporting

The lifetime owner now separates consumption/cleanup planning from resource
ownership acceptance. Lifetime_Plan_Worker and the three original handler concepts
consume a fixed Checked_Body and produce a Lifetime_Plan. This intermediate is
explicitly not Analyzed_Body and cannot authorize lowering; the later coordinator
must compose it with accepted allocation/ownership facts. The original combined
worker/result remain preserved references for that integration.

The worker retains source-attributed failures, one-shot execution, exact scope
ranges, initialized live locals, one-consumer value facts and ordered full-expression
cleanup. It reads the shared Expression_Order iterator. evaluate returns its call
boundary instead of changing a scalar by reference. Indexed assignment consumes
target indices before the RHS. Logical-depth vectors replace array_pop scratch
stacks; ordered consumption rows plus a membership index replace array_values on
an insertion-ordered hash. Scope exits preserve reverse initialization order.

Lifetime_Plan owns copied membership and shared immutable lifetime rows. Its
validation preserves local-entry/index checks and exact cleanup completeness,
boundaries and order. A named lexicographic Cleanup_Rank replaces tuple comparison;
temporaries precede locals at a shared boundary, with each group in reverse
construction order. Type definitions remain shared in the checked body. Full
allocation coverage/provenance validation and the combined Analyzed_Body result
are still required, rather than fabricated as empty successful allocation facts.

Twenty-nine PHP/native scenarios cover scalar/void flows, calls/nested calls,
conversions/operations, local initialization/assignment, managed default/copy/assign,
move/copy/direct returns, owned provider results, temporary borrowing, scope/branch/
loop exits, unreachable statements, indexed assignment, 128 nested calls and 64
managed locals. Flat cases assert exact consumption/local/cleanup rows; branch,
loop and stress cases check counts, validation and repeat agreement. The retained
lifetime_analysis.php reachable/void/unreachable example supplies one explicit
baseline; this suite does not claim a full retained-worker differential test.
Missing, duplicate and reversed cleanup lists are rejected. Repeated planning does
not change type-store sizes and a second call on the same worker is rejected.

The result debug/export projection, project selection/join and complete lifetime
stage remain unfinished. Native provider bodies are not executed by this compiler
plan proof. Resource location/state discovery and allocation-flow checking are next.

Run `python3 compiler/tests/lifetime_plan/run.py --results FRESH --target-checkout TARGET`.
Evidence: `specs/planning/compiler_migration/results/lifetime-plan-01`.
