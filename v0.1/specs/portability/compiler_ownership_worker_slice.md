# Ownership tasks and lifecycle workers
Doc Status: supporting

Ownership_Task captures exactly one checked body or lifecycle subject plus fixed
dependency-summary membership. Two checked nullable handles replace the prototype's
PHP union; ambiguous or absent subjects reject. Ownership_Lifecycle retains exact
type/definition/role/body identity and ordered named Ownership_Child rows. Copied
membership and immutable summary handles make reuse inputs explicit. Lifecycle_Order
is computed as a fresh compact value, avoiding PHP object sharing of mutable rows.
Ownership_Result retains the exact producing task and rejects mismatched allocation
body provenance. Final batch acceptance is still a separate owner.

Ownership_Worker now has an explicit one-shot task instance so attributed body-flow
failures remain accessible after an exception. Body work delegates to Allocation_Flow;
lifecycle work composes child and body contracts in the preserved order. Constructor
and destructor requirements, independent source/destination lanes, alias endpoint
prefixing, and discharge restrictions retain their meaning. Named private mutable
parameter builders replace nested arrays and by-reference scalar/container mutation;
published Parameter_Effects copy their field membership. Child order remains a
preparation responsibility, including reverse destruction and custom member roles.

53 PHP/native lifecycle outcomes agree with the preserved Ownership_Worker. Its host
bridge supplies only the definition name/resource paths actually consumed, using a
minimal reflected definition; it does not prove old type construction. Additional
PHP/native assertions cover real body tasks, exact allocation/task identity, captured
dependency maps, detached children/order values, malformed subject alternatives,
provenance rejection and one-shot operation.

Review also corrected allocation parameter classification to use the existing
Semantic_Modes::is_borrow owner, matching the prototype and excluding byte-span
passing. The focused allocation PHP suite passes. First worker native build passed;
a second final build verifies the current dependency revision after that review
correction. This is a verification build, not a response to a native failure.
Ownership batch acceptance, request selection/scheduling and complete lifetime
publication remain uncompleted at this checkpoint.

Run `python3 compiler/tests/ownership_worker/run.py --results FRESH --target-checkout TARGET`.
Evidence: `specs/planning/compiler_migration/results/ownership-worker-01`.
