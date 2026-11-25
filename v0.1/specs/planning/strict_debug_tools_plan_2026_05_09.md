# Strict Debug Tools Plan
Doc Status: planning

Date: 2026-05-09

## Goal

Add a small, strict-safe debug surface for PHP++ strict projects:

```php
dbg($value);
dbg("label", $value);
dbg("label", $value, DBG_SHAPE | DBG_DEPTH_3 | DBG_PTR);

dbg_set("gate", $condition);
dbg_if("gate", "label", $value, DBG_SHAPE);
dbg_unset("gate", $condition);
```

## Design Notes

- `dbg` is the single inspection primitive.
- Flags are bitwise combinable.
- No flags means `DBG_DEFAULT`, a generous but bounded view.
- `DBG_DEPTH_0` through `DBG_DEPTH_5` select structural depth.
- Multiple depth flags are a debug API error.
- `DBG_PTR` prints compact hex identity values where meaningful.
- Recursion/shared-reference protection is mandatory.
- Value inspection must not crash the program; unsupported shapes degrade to type/kind plus `<not inspectable>`.
- `dbg_set` and `dbg_unset` are intentionally strict and throw on duplicate set or missing unset when their optional guard is true.

## MVP Scope

- Add runtime constants and functions for strict and legacy symbol maps.
- Add source-aware generator lowering for `dbg`, `dbg_if`, `dbg_set`, and `dbg_unset`.
- Support robust inspection for scalars, `mixed_t`, `hash_t`, `vector_t`, nullable/result wrappers, and runtime handle wrappers.
- Use identity tracking for heap-backed/runtime containers and handles.
- Document the feature in strict quick-learn and the strict skill.

## Known Limits

- Arbitrary user-object field reflection is not available yet. Until class metadata exists, object handles can show type name, null/non-null state, and identity when requested.
- `DBG_SOURCE` reports the PHP++ source path and line only for calls lowered by the PHP generator special case.
- `DBG_JSON`, `DBG_RAW`, and full object field reflection are future extensions.

