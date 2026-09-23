# Ownership dependency queue
Doc Status: supporting

Ownership_Request captures one checked-body or lifecycle subject and deduplicated
required keys. It is deliberately separate from Ownership_Task, which receives actual
accepted dependency summaries. Named queue nodes own remaining counts and reverse
edges; missing keys and duplicate requests reject before execution.

Ownership_Queue executes dependency-ready batches. It captures the complete batch's
inputs before running workers, accepts results through Ownership_Join before waking
dependents, and publishes only a complete current result map. Unsupported dependency
cycles retain an explicit diagnostic. Worker exceptions retain attributed diagnostics;
no partially accumulated results are returned. This is sequential execution, not a
threading proof. The queue is one-shot.

Incremental reuse checks exact body identity or explicit lifecycle subject facts,
plus exact dependency-summary handles. Rebuilt lifecycle descriptors can reuse work;
removed requests disappear; forced rebuilds recompute tasks but retain equal summary
handles through the join. Typed comparisons replace PHP union tests and ad-hoc nested
array equality without relying on insertion order for keyed dependency membership.

Eight PHP/native cases prove out-of-order readiness, duplicate prerequisite removal,
unchanged reuse, full rebuild, removed work, body replacement, changed dependency
propagation, and missing/duplicate/cyclic request rejection. Additional assertions
verify composed resource transitions, captured accepted handles and one-shot execution.
The changed-dependency fixture changes a synthetic leaf role at a fixed arbitrary
queue key: it proves queue invalidation, not canonical lifecycle-key selection.

Full Ownership_Preparation still needs to select requests from Body_Set and Type_Store,
map source lifecycle bodies, preserve member-role overrides and destruction order,
and collect actual call/construction/return dependencies. Complete analyzed-body
publication and whole-stage coordination remain unfinished. src-runtime-preparation
is unchanged.

Run `python3 compiler/tests/ownership_queue/run.py --results FRESH --target-checkout TARGET`.
Evidence: `specs/planning/compiler_migration/results/ownership-queue-01`.
