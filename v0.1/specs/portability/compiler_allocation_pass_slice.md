# Resource statement and scope-exit transfer
Doc Status: supporting

Allocation_Pass owns statement/block resource transfer and scope-exit validation.
Named Local_Resource_Leaves groups descriptor leaves by root binding. Destination
indices execute before the RHS; allocation-backed destinations pin their descriptor
while the source evaluates. Local construction consumes temporary field facts;
assignment uses the selected complete source contract. Owned record results transfer
before cleanup, and remaining temporaries finish in reverse construction order.

Scope filtering uses the existing Local_Flow scope model. Each block starts from an
explicit copy of its entry facts. Exit destruction runs on a second private copy;
only preceding mutation observations propagate back, while states remain available
unchanged for sibling exits and normal-return summaries. Borrowed parameter leaves
retain their caller obligations. Aggregate result handling follows the prototype's
static resource-field list, distinct from a raw descriptor's root location.

Eight PHP/native cases establish balanced and leaking local owners, nested scope
exits, empty release, owned/empty indexed assignment, and supplied complete record
construction/destruction contracts. Assertions check entry immutability and repeated
exit checks without sibling state contamination. These are focused block/exit proofs,
not complete coverage of result/copy/assignment contracts or a whole ownership stage.
Those paths require continuing integration coverage with fixed-point analysis and
accepted lifecycle summaries. The first native build passes without corrections;
one PHP fixture changed an unsupported int-to-int32 assignment to Storage<int>.

The solver must assemble consistent contracts, bindings and expression traversal
for each observation context. Fixed-point merging, caller-summary inference and
final ownership/lifetime acceptance remain unfinished at this checkpoint.

Run `python3 compiler/tests/allocation_pass/run.py --results FRESH --target-checkout TARGET`.
Evidence: `specs/planning/compiler_migration/results/allocation-pass-01`.
