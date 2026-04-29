# Missing Operator Matrix Surface
Doc Status: planning

This document tracks the operator-matrix surface that is still missing,
partially activated, or not yet fully canonical.

It is a current working gap report, not a normative semantic source.

| Missing area | Tag | Notes |
|---|---|---|
| `operators_compound_assignment` member/property write targets | `implemented` | Canonical `member_property` target rows are now modeled in the matrix and emitted through dedicated property-write tests. |
| broader assign-target coverage beyond keyed/member | `implemented` | Compound assignment now models a canonical `chained_writable_path` target to cover representative deeper member/keyed lvalue chains. |
| wrapper-lifted bitwise/shift compound-assignment full enablement audit | `testing` | The validated wrapper `int_t` bitwise slices need a clean enabled-vs-disabled audit across emitted rows. |
| broader wrapper lifting consistency across existing families | `testing` | Needs a systematic pass for present, absent, and disabled wrapper rows across families. Some rows may still reveal real implementation gaps. |
| mixed participation consistency audit across existing families | `testing` | Needs a family-by-family check that each intended `mixed_t` slice is present and aligned with runtime/config authority. |
| pointer/vector/table/sentinel participation in operator matrix | `needs implementation (code)` | These types are still largely outside the active operator-matrix families. |
| `language_probes_and_reset` family activation (`isset_*`, `empty_*`, `count_*`, `unset_*`) | `needs implementation (code)` | Strong spec basis exists in the matrix taxonomy, but the family is not yet activated in the tooling/data surface. |
| full enablement audit for implemented-green slices | `testing` | Needs a compact inventory of implemented+green+enabled versus implemented+green+disabled matrix slices. |
