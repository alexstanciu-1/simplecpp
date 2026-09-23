# Checked body flow construction and queries
Doc Status: supporting

`check_bodies/Flow_Builder` retains the prototype's reserve/begin/terminate/complete
protocol. `Typed_Block` holds a contiguous statement range, lexical scope and
callable-local successors. Four checked integer tags represent jump, branch,
return and fallthrough. A private reservation record represents an unfinished
block explicitly; completion rejects missing blocks and returns fixed membership.
Calling terminate on an already terminated path remains a no-op. The caller now
passes both successor IDs explicitly, using zero for absent successors, because
the converter does not admit ordinary method defaults in this form.

`Flow_Graph` starts at block 1. It rejects missing reachable targets, ignores
unreachable blocks, and returns reachable IDs ordered by statement start then ID.
Terminal exits have no successors; branch preserves both edges even if equal.
These are structural queries, not body validity, lifetime analysis or lowering.

The traversal uses dense visited flags and an indexed work vector. Nodes are
marked when enqueued, so each reachable block is visited once, including loops
and shared joins. A bottom-up merge sort replaces PHP callback sorting without
an O(n²) fallback. Explicit guards protect index access on the current native
short-circuit contract. Auxiliary storage is O(total blocks + reachable blocks),
with O(reachable blocks log reachable blocks) sorting. Per-comparison container
arguments are avoided.

The prototype placed the flow vocabulary within a larger structures file. It now
has a dedicated `data/flow.php` owner, shared by body checking and future lifetime/
lowering consumers. Those consumers and remaining typed value records are not
claimed migrated by this slice. Private reservation objects are a future native
storage optimization opportunity; the published block contract stays independent.

`compiler/tests/body_flow` checks 48 graph cases against independent expected
results and the retained prototype, including ties, cycles, invalid and unreachable
edges, seeded generated graphs and a 3,000-block chain. Its builder trace checks
reservation, ignored duplicate termination, range/scope preservation, completion,
rejection and previously returned membership. PHP/native proofs and per-command
timings are retained with the migration evidence.
