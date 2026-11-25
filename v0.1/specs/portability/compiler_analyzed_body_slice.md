# Complete analyzed-body acceptance
Doc Status: supporting

Analyzed_Body combines an existing Lifetime_Plan with allocation facts and an optional
accepted Ownership_Result. The plan remains the owner of consumption, local lifetime
and cleanup validation. The result boundary verifies exact checked-body identity,
resource-input identity, required allocation presence, exact reachable-block membership
and valid resource-owner/state membership. It does not rerun the solvers or equate
structurally similar body snapshots. Ordered facts and local lookup delegate to the
plan rather than copying or maintaining a second set of lifetime rows.

Lifetime_Worker owns the plan worker and, when no accepted ownership result exists,
a direct allocation-flow worker. It is one-shot. Accepted allocation facts are reused
by exact identity. Diagnostic lookup reads the retained delegates, preserving source
attribution after failure without translating or swallowing the exception.

The first native attempt stopped before C++ compilation because STAN did not recognize
complete returns inside try/catch forwarding helpers. Retaining delegate owners and
reading their diagnostics directly eliminated those helpers. This is one native
STAN correction cycle, not a C++ compiler failure. An earlier checker correction
replaced unsupported named-nullable local annotations with typed method boundaries.
First PHP readiness remains `/tmp/analyzed-body-php-02`; expanded eight-scenario
coverage is `/tmp/analyzed-body-php-03`. Final native verification passed at the second attempt (first C++ build).
Saved evidence: `specs/planning/compiler_migration/results/analyzed-body-01`.

The PHP/native proof covers scalar bodies, raw resource flow, balanced allocation/release,
leak diagnostics, borrowed aggregates, local construction, owned record returns and
branched resource flow. Boundary assertions reject foreign plans/allocations, stale
ownership in workers/results, missing/unexpected allocation analysis, unknown owners,
zero entry relations and incomplete block coverage. Rejection leaves accepted facts
usable. It also checks one-shot execution and accepted resource-analysis reuse.

Whole-stage selection/publication and debug exports are separate migration work.
Run `python3 compiler/tests/analyzed_body/run.py --results FRESH --target-checkout TARGET`.
