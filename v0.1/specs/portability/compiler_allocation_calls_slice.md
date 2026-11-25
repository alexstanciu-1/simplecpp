# Runtime allocation call effects
Doc Status: supporting

Allocation_Calls applies runtime allocation metadata to already bound semantic
operands. The owner uses metadata positions rather than argument order conventions;
proofs deliberately select owner position 2 and transfer destination position 0.
Acquire/release/transfer/inspect/mutate/observe preserve their original required
states, result relations and independent mutation/access behavior.

Runtime transfer deliberately retains the prototype's ordered destination-then-source
application. Source-call summary checks instead share one pre-call state. The common
Resource_Calls owner supplies requirements, alias exclusions, active-borrow checks,
access observations and transition application for both protocols. Transfer rejects
overlapping descriptors even during solving. Validation rejects an occupied
destination or a mutation invalidating a live allocation borrow. A later source
rejection may leave the private destination scratch updated, as in the prototype;
the worker must discard the failed work product rather than publish partial facts.

The optional destination position is unwrapped explicitly with take_nullable.
Failure exposes the precise node/reason from either runtime ordering or common
contract checks; frontend diagnostic attribution remains a body-worker obligation.
Missing mapped operands are internal producer errors. Checked-value binding and
dynamic-subobject rejection are separate dependencies, not bypassed here.

896 PHP/native cases compare directly with the preserved Resource_Effects trait
through a bound-operand bridge. The matrix covers all 16 source relations, every
transfer source/destination relation pair, solving versus validation, inferred
parameter requirements, active ancestor/sibling borrows, overlapping owners and
preceding mutations. Independent outcomes anchor acquisition, empty release,
invalid inspection and successful transfer. First PHP/native attempts pass without
corrections. No runtime allocation is actually performed: this proof concerns the
compiler's ownership-effect analysis, not allocator implementation or full CFG
acceptance.

Run `python3 compiler/tests/allocation_calls/run.py --results FRESH --target-checkout TARGET`.
Evidence: `specs/planning/compiler_migration/results/allocation-calls-01`.
