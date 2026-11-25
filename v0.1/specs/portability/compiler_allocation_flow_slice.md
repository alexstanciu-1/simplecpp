# Fixed-point allocation analysis and summary inference
Doc Status: supporting

Allocation_Flow now owns the prototype's two-pass algorithm. Initial parameter
membership is fixed independently of inferred requirements. A LIFO worklist merges
possible relation edges and preceding parameter mutations until stable. Each block
and sibling exit retains independent snapshots. Solver contexts receive no validation
observations; a second traversal of converged entries collects requirements, access,
mutation, exclusions and return facts. Scope filtering retains the existing Local_Flow
rules. Worklist depth replaces array_pop; named state/entry records replace nested
PHP arrays and implicit clone behavior.

Convergence compares relation and mutation membership by meaning, not PHP map insertion
order. Publication uses ascending block IDs. Canonical alias exclusions remain a set;
byte-identical incidental PHP key order is not a requirement. Summary inference keeps
parameter positions and static field paths explicit, intersects deterministic return
requirements, preserves const identity and demands fixed owned result states. Raw
allocation-owner parameters retain the prototype's contract rejection.

Allocation_Entry and Allocation_Analysis capture immutable membership and the exact
Checked_Body. They are allocation evidence, not a completed lifetime authorization.
The worker is one-shot; summary() now requires successful analysis rather than yielding
an uncomputed empty contract. Accepted ownership task/result provenance and complete
lifetime publication remain separate obligations.

15 PHP/native cases prove balanced/leaking owners, consistent/inconsistent branch
merges, loop fixed points, loop-local scope filtering, unreachable code, empty analysis,
borrowed record-field requirements and raw-owner parameter rejection. Tests assert
complete reachable-block membership, detached exported maps, exact converged relations
for branch/loop/parameter cases, a 32-branch stress case and one-shot behavior. These
are independent expected-behavior integration proofs, not a full retained-worker
differential. The first native build passed; one checker-only fixture correction
preceded first PHP readiness. Final ownership acceptance and remaining owned-result/
mutable-lifecycle integrations still need their selected proofs.

Run `python3 compiler/tests/allocation_flow/run.py --results FRESH --target-checkout TARGET`.
Evidence: `specs/planning/compiler_migration/results/allocation-flow-01`.
