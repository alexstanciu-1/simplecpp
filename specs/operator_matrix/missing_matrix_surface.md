# Missing Operator Matrix Surface
Doc Status: planning

This document previously tracked the operator-matrix surface that was still
missing, partially activated, or not yet fully canonical.

That matrix-completion program is now finished.

As of 2026-04-27:
- full generated `php-matrix` test count: `22743`
- enabled generated tests: `22743`
- disabled generated tests: `0`
- negative-generate emitted rows: `0`
- validation errors: `0`
- validation warnings: `0`
- full active dev-server run: `22743 / 22743` passed with `php8.5` and `--jobs=24`

This file remains as a checkpoint marker so future work does not accidentally
restart from stale assumptions.

## Closed Matrix Gaps

The following previously tracked gaps were closed during the completion pass:
- `language_probes_and_reset` family activation
- `operators_compound_assignment` member/property write targets
- broader writable-target modeling beyond variable/keyed/member
- wrapper-lifted bitwise/shift enablement and audit
- broader wrapper lifting consistency across implemented families
- `mixed_t` participation consistency across implemented families
- full enablement audit for implemented-green slices
- `operators_conditional_selection` closeout, including `coalesce`, `ternary`, and `elvis`

## What This File No Longer Means

This file should no longer be read as evidence that the semantic matrix is
unfinished.

If new gaps appear in the future, they should be recorded as a fresh follow-up
program rather than by silently assuming the older matrix-completion work is
still open.

## Remaining Non-Matrix Follow-up

The main remaining follow-up areas are outside the matrix completion program
itself, for example:
- expanding matrix scope to new type families such as pointer/vector/table/sentinel
- adding future runtime-matrix emission if that lane becomes active
- continuing broader language/runtime feature work that happens to need matrix coverage
