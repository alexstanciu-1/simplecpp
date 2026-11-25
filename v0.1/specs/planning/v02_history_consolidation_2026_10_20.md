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

## Recorded merges and retained evidence

- `ce17ff73`: remote integration, parents `ee87a080` and `2b932aab`.
- `59023685`: `_02` integration, parents `ce17ff73` and `d1d1750e`.
- Ancestry verification reports zero missing commits for both incoming tips, the
  prelowering tip, original local develop and both earlier `_02` imports. See
  [retained results](results/v02_consolidation_2026_10_20/ancestry.json).

Incoming conditional syntax (46 executions), vector helper/copy-contract proofs,
ordinary shared-handle completeness, optimization policy, lowering reports and
ordinary Ninja cold/warm/edit/failure-retry checks pass. The concrete O0 latency
policy also publishes `application_templates: forbidden`, compiles with the normal
layout/native owner and prints the expected `3` for a class with vector and scalar
defaults. No runtime/compiler semantics were reimplemented for these checks.

One incoming shared-handle fixture still assumed ordinary CLI defaults; it now
explicitly selects `--no-prelower-templates`, preserving the original proof scope.
Imported CSV line endings and historical patch whitespace are preserved, not
normalized during a history merge. Working-source whitespace checks are separate
from those archived evidence files.

The local branch tracks `origin/v0.2/develop`. Remote publication and removal of
redundant branches remain separate steps; this task does not alter the `_02` checkout.

## Final combined O0 acceptance

All eight phases passed in `/tmp/v02-consolidated-acceptance-20261020`: metadata,
contracts, public defaults, provider boundary, counters/references, source lifecycle,
layout cache and accounting. Ordinary and prelowered accounting have identical
output: 998 checks pass, zero fail. All 16 application units have zero template
declarations and zero class/function instantiations. Both warm builds reuse their
existing artifacts without compilation/linking. See [acceptance](results/v02_consolidation_2026_10_20/acceptance.json)
and [accounting](results/v02_consolidation_2026_10_20/accounting.json).

The combined run started with the resolved `_02` merge still uncommitted, so its
recorded revision is `ce17ff73` with the pending merge worktree. The merge was
subsequently recorded as `59023685` without changing the tested production sources.
Checks use the PHP-hosted compiler and execute generated O0 programs; this is not a
fresh native compiler rebuild, a benchmark, or universal runtime coverage.
