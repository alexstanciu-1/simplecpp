# Definite initialization
Doc Status: supporting

Definite initialization now has a typed per-block owner. Compact Initialization_Fact
value records carry local/initializing-statement IDs; statement zero means an entry
parameter. Initialization_State preserves declaration order in a vector and uses a
hash only for membership. Publication/read boundaries copy value records explicitly
so PHP sharing cannot mutate native-style value snapshots. Initialization_Entries
retains the exact checked body and omits unreachable blocks.

Local_Flow keeps the preserved LIFO fixed-point algorithm: seed parameters, transfer
local declarations, remove out-of-scope facts and intersect predecessor keys. The
intersection retains old values/order, so unchanged size proves unchanged state.
The reusable worklist uses logical depth instead of PHP array_pop; dense records
replace nested ad-hoc maps without depending on hash iteration order. Transfer
updates a private vector/index in place, avoiding a full copy for every declaration.

Sixteen PHP/native outputs agree with the unchanged preserved solver and graph-query
implementation, invoked through a host-only shape bridge. Fourteen source-produced
bodies cover parameters, branches/returns, loops, nesting, unreachable blocks, 256
locals, 128 nested scopes and 64 branches. Two isolated checked-graph fixtures force
predecessor intersection/requeue independently of source CFG generation. Repeat
solutions and mutation of returned fact copies preserve the published facts.
This is initialization readiness only: value/local lifetime records, cleanup planning,
allocation/ownership flow, joins and the complete lifetime stage remain to migrate.

Run `python3 compiler/tests/local_flow/run.py --results FRESH --target-checkout TARGET`.
Evidence: `specs/planning/compiler_migration/results/local-flow-01`.
