# Operator Matrix Generator (v1 Skeleton)

## Purpose
Provide a minimal executable pipeline that:
- reads operator_matrix specs
- expands a small subset of types/profiles
- emits valid matrix rows

## Inputs
- specs/operator_matrix/catalog_v1.md
- specs/operator_matrix/type_universe_v1.md
- specs/operator_matrix/generation_rules_v1.md

## Phases
1. Load configuration (hardcoded v1 subset)
2. Expand types → profiles
3. Generate rows for selected families
4. Output JSON

## Scope (v1)
- Only:
  - condition_truthiness
  - casts_explicit
- Limited types:
  - int_t
  - bool_t

## Output
- JSON rows matching output_schema_v1.md

## Notes
- This is NOT full generator
- Only validates pipeline shape
