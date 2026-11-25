# Ownership flow and summary records
Doc Status: supporting

Resource_Flow_State owns the solver's private typed relation/mutation maps. Its
explicit copy operation replaces clone and preserves independent block/sibling-exit
membership. Ownership_Observations remains a distinct validation-only scratch owner:
requirements, mutations, normal returns, owned results, accesses and exclusions never
belong to the solver state. Actual traversal must retain that separation; these records
alone do not prove the two-pass algorithm.

Resource_Transition retains independent required-mask, result-relation, mutation and
access fields. Bound_Resource_Effect shares the exact immutable location/transition
objects. Parameter_Effects replaces a nested parameter/path PHP array with a named
zero-based parameter owner and copied field membership. Ownership_Summary copies
parameter, exclusion and result membership and returns container values; immutable
transition and parameter objects retain shared identity. Numeric PHP hash keys are
normalized by concatenating an empty string at canonical path boundaries.

Local malformed-contract checks now reject negative/duplicate parameter positions,
noncanonical paths, missing exclusion endpoints, self exclusions and nonfixed owned
result states. Reversed/duplicate exclusion pairs normalize to canonical membership.
These checks consolidate internal producer requirements already enforced by the
prototype acceptance layer. Full expected body/type path coverage, constness,
unaccessed-transition consistency, producer provenance and reuse remain the future
ownership join's responsibility. No task/result or completed allocation claim is made.

340 transition-constructor outcomes agree with the retained prototype in PHP and
native (180 accepted, 160 rejected). Additional assertions prove copy independence,
empty summaries, immutable row identity, numeric/root paths, canonical exclusions,
unchanged exported snapshots and ten malformed/missing-contract rejections.
The first PHP behavior execution and first native build pass; two earlier checker
corrections concern fixture keyed literals and unsupported source string casts.
Lifecycle tasks, allocation facts, effect/alias consumers, flow solving and ownership
acceptance remain to migrate.

Run `python3 compiler/tests/ownership_records/run.py --results FRESH --target-checkout TARGET`.
Evidence: `specs/planning/compiler_migration/results/ownership-records-01`.
