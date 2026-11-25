# Bound resource calls and alias checks
Doc Status: supporting

Resource_Calls owns application of already bound ownership contracts. The two
prototype private traits depended implicitly on Allocation_Flow fields and passed
nullable validation observations through every helper. The migrated local owner
captures fixed locations/parameter membership and an optional observations handle
at construction. A solver instance has no observations; validation uses a separate
instance. No checked-expression traversal, symbol resolution or global lookup is
introduced here.

Parameter-relative fields bind once to exact endpoint effects. Every source-call
requirement reads the unchanged pre-call state; aliases retain all requirements but
select only one writer poststate. Read-only aliases cannot overwrite that writer.
Explicit alias exclusions and active element borrows are checked before effects are
committed. Independently applied object fields retain their explicit iteration order.
State compatibility intersects inferred parameter requirements; concrete local
states never acquire caller preconditions. Preceding mutations produce parameter
alias exclusions; mutation and access remain independent observations. Solving
updates flow facts without collecting observations, matching the retained traits.

Ownership_Failure records the failing source node and reason before throwing. The
future checked-body worker must attach the frontend path/span via the existing
diagnostic owner; this helper does not claim complete source-diagnostic integration.
All facts/observations remain private work products and are discarded on rejection.

449 PHP/native cases agree with a bridge invoking the preserved effect/alias traits.
Seven cases also have independently specified complete outcomes. Coverage includes
all 16 input relations across reader/writer, release/acquire and unused contracts;
alias order, multiple-writer rejection, local versus inferred parameter requirements,
active ancestor/sibling borrows, preceding mutations, explicit exclusions, field
application, solver-only operation, and unchanged flow on failed call validation.
First PHP/native attempts pass without corrections. Runtime allocation metadata
binding/transfer ordering, expression traversal, fixed-point solving and complete
ownership acceptance remain dependencies, not established by this proof.

Run `python3 compiler/tests/resource_calls/run.py --results FRESH --target-checkout TARGET`.
Evidence: `specs/planning/compiler_migration/results/resource-calls-01`.
