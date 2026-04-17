# Operator Matrix Data

Status: Machine-readable generator input

---

## Purpose

The files in this folder provide the structured JSON input consumed by
`tools/operator_matrix/`.

These files are subordinate to the operator-matrix coordination specs.
They exist so the generator does not need to parse Markdown directly.

---

## v1 Scope

The current generator scope is intentionally limited to:
- `condition_truthiness`
- `casts_explicit`

Within that scope, the current supported items are:
- `if_condition`
- `cast_bool`

The current type subset is:
- `int_t`
- `bool_t`
- `nullable<int_t>`

---

## Files

- `families.json` — family/item registry and source references
- `types.json` — type/profile registry for the current generator scope
- `semantics.json` — profile-explicit row definitions for the current generator scope

---

## Rules

- Keep source rows fully profile-explicit.
- Do not use aggregated source rows.
- Do not add support here that is not justified by the upstream docs.
- If the JSON and the docs diverge, fix the project.
