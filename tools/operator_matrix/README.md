# Operator Matrix Tooling
Doc Status: supporting
Status: active generator and test-emission surface

---

## Purpose

This tool generates structured operator-matrix rows from the normalized data in
`specs/operator_matrix/data/` and emits matrix-driven test assets.

The Markdown specs remain the human-facing coordination layer.
The structured data files provide the machine-readable input used by the
generator.

---

## Current Scope

Active semantic families:
- `condition_truthiness`
- `casts_explicit`
- `operators_conditional_selection`
- `operators_unary`
- `operators_binary_arithmetic`
- `operators_binary_logical`
- `operators_comparison_equality`
- `operators_comparison_ordering`
- `operators_strict_identity`
- `operators_binary_bitwise`
- `operators_compound_assignment`

Current emitted PHP-matrix surface includes:
- scalar core rows
- selected `mixed_t` rows where the runtime/config surface is explicit
- wrapper-lifted rows for validated slices
- compound-assignment rows for:
  - `assignable_variable`
  - `keyed_element`

Current emitted test outputs:
- concrete PHP matrix tests under `tests/php-matrix/`
- matrix metadata and summaries under `build/operator_matrix/`

Current runtime-matrix state:
- the runtime-matrix root is created
- the runtime-matrix emitter is still intentionally unimplemented
- `tests/runtime-matrix/` is therefore still effectively empty

---

## Structured Data

Primary structured inputs:
- `specs/operator_matrix/data/families.json`
- `specs/operator_matrix/data/semantics.index.json`
- `specs/operator_matrix/data/semantics/`

The generator does not treat Markdown docs as direct source input.

---

## CLI

Run from the project root:

```bash
php tools/operator_matrix/generator.php
php tools/operator_matrix/generator.php --family=operators_comparison_equality
php tools/operator_matrix/generator.php --family=operators_compound_assignment
php tools/operator_matrix/generator.php --stdout
```

Generated artifacts:
- `build/operator_matrix/matrix.json`
- `build/operator_matrix/validation_report.json`
- `build/operator_matrix/test_seeds.json`
- `build/operator_matrix/test_seed_validation_report.json`
- `build/operator_matrix/test_emission_report.json`

Exit codes:
- `0` - generation and validation succeeded
- `1` - usage or IO failure
- `2` - validation errors were found

---

## Test Emission

The generator emits row-faithful test seeds and concrete test files.

Current emitted suite:
- `php-matrix`

Current non-emitted suite:
- `runtime-matrix`

Enablement is intentionally selective:
- validated positive slices are enabled
- broader wrapper or gap-exposing slices may remain emitted-but-disabled
- negative-generate tests are emitted under explicit policy controls

---

## Negative-Generate Controls

Negative-generate emission controls:
- `--emit-negative-generate=none|all`
- `--enable-negative-generate=none|all`
- `--enable-negative-generate-diagnostic=a,b`
- `--disable-negative-generate-diagnostic=a,b`
- `--negative-generate-disabled-status=experimental|known_fail`
- `--strict-negative-generate-enable`

Enablement priority:
1. explicit diagnostic denylist
2. explicit diagnostic allowlist
3. global `--enable-negative-generate`
4. default disabled state

---

## Notes

- Family naming and semantic authority are owned by the operator-matrix docs
  under `specs/operator_matrix/`, not by this README.
- Runtime behavior remains subordinate to normative specs and
  `runtime/specs/config.json`.
- For remaining uncovered or partially covered matrix surface, see
  `specs/operator_matrix/missing_matrix_surface.md`.
