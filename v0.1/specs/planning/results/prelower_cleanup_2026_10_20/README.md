# Prelowering cleanup evidence — 2026-10-20

Doc Status: planning

Baseline: `1810bc2d`, with the cleanup worktree overlay. This is a bounded
retirement of superseded paths, not a runtime coverage expansion.

## Ownership and scope

The public `prelower_templates` setting now selects the sole general concrete
path. Removed the temporary runtime-ABI/concrete-substitution selectors,
handwritten concrete recipes and operations, and the old source implementation
partition. Existing registry bindings, prepared facts, concrete interfaces,
publication and native-build owners remain responsible for their existing work.

The entry support needed by the general path has its own small owner. Shared
physical-storage and observation APIs remain available for layout proofs; they
are not alternate application lowering modes. Strict boundary validation and
explicit rejection remain in place.

## Fresh verification

All native validation in this slice used O0. No O2 benchmark or native compiler
rebuild was performed. Run the scripts below from the repository root, prefixed
with `python3 compiler/my-try/tests/`.

| Script | Result |
| --- | --- |
| `prelower_accounting_acceptance.py` | Ordinary and public-flag prelowered modes each passed 998 checks, zero failures, identical stdout and exit status. All 16 application units have zero template declarations/instantiations. Warm replay compiled and linked nothing. |
| `prelower_header_boundaries.py` | Output, indexing and compound-operation proofs passed, including direct/concrete behavior, counters, evaluation order, nested references and warm reuse. |
| `prelower_null_output.py` | Public CLI/API, selector absence, lifecycle replay and null-output acceptance passed. |
| `prelower_contracts.py` | 21 strict-boundary rejection/recovery cases and 10 closure-coverage cases passed. |
| `prelower_layout.py --o0-only` | Cold/warm/body edits, layout corruption repair, provider dependency changes, failed-layout rejection, recovery and counters passed. |

The shared runner also passed `runtime_inventory.php`,
`runtime_type_bindings.php` (including 21 invalid schemas), and
`runtime_protocols.php`.

Local recordings, which are temporary evidence rather than repository fixtures:

- `/tmp/prelower-cleanup-accounting-final-20261020`
- `/tmp/prelower-cleanup-boundaries-final-20261020`
- `/tmp/prelower-cleanup-public-final-20261020`
- `/tmp/prelower-cleanup-contracts-final-20261020`
- `/tmp/prelower-cleanup-layout-full-20261020`
- `/tmp/prelower-cleanup-layout-20261020`

Comparing the published accounting manifest with the prior public acceptance
recording found no added or removed artifacts: 357 artifacts were byte-identical.
Only `specializations/base.hpp` and `specializations/base.cpp` changed, removing
obsolete recipe arithmetic helpers. These runs establish behavior and closure;
their suite durations are not a compiler performance comparison.

## Preserved historical cases

Superseded comparison harnesses and their fixtures are preserved verbatim from
`1810bc2d` in the
[historical archive](../../../../compiler/my-try/tests/historical/prelowering/README.md).
They are non-executable references, not passing current tests. Detailed historical
source-method and constructor failure-injection cases still need migration into
general-path fixtures; that obligation remains on the solidification TODO.
