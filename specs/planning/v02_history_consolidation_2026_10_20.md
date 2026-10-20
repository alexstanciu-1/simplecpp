# v0.2 history consolidation — 2026-10-20
Doc Status: planning

Target: `v0.2/develop`. No rebasing, cherry-picking or squashing. The `_02` checkout
is read-only; fetched history comes exclusively from its `v0.2/develop` branch.

## History order

1. Fast-forward local develop from `c09a6003` to prelowering tip `ee87a080`.
2. Merge complete `origin/v0.2/develop` at `2b932aab` (25 incoming commits).
3. Merge complete `_02` develop at `d1d1750e` (21 commits beyond the prior import).

The prior `_02` imports `d675aca8` and `3afc0573` retain their original merge
ancestry. Main, older development/release branches and remote deletion are outside
this consolidation step. No force-push is involved.

## Remote integration resolutions

- Retain concrete lowering options and incoming optimization policy, independently
  invalidating backend fragments. The generator accepts options then policy;
  Compiler's existing entrypoints pass both to their existing owners.
- Reuse extracted implementation grouping with the concrete dispatch context.
  Source storage/constructor definitions use the incoming `record_definition_text`.
- Retain template policy alongside optimization policy in authenticated publication.
- Retain shared native command recipes, linker selection and link-only identity;
  initialize linker identity before layout-probe linking. Preserve debug selection,
  warning diagnostics, dependency-validated template checks and ordered layout inputs.
- Native builds still default to no PCH. Ordinary Ninja tests explicitly request
  PCH where they verify PCH behavior, including failed-build/retry parity.
- The optional Ninja consumer explicitly rejects concrete publications until it owns
  layout probes and strict closure validation. It must not silently bypass policy.
- Incoming shared-allocation outlining applies to ordinary representation only;
  concrete construction keeps its existing prepared transport.
- Preserve both documentation histories; current concrete defaults take precedence
  over older ordinary-mode recipes. Historical evidence files remain unchanged.

## Verification

Remote integration: public-default prelowering behavior/closure/recovery,
optimization-policy switching/generated executions, lowering reports and Ninja
strict rejection passed. Raw results are under `/tmp/v02-merge-*20261020`.
The first native attempt exposed linker identity initialization after layout probes;
resolved before the passing public-default run. The first Ninja test mixed explicit
PCH builds with a now-default no-PCH failure attempt; the fixture now keeps the same
PCH setting during failure/retry. Final combined results and ancestry checks follow
below after both merges. No native compiler rebuild is claimed by these checks.
