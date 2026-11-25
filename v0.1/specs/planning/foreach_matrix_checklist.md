# Foreach Matrix Checklist
Doc Status: planning

Purpose: define the `foreach` acceptance matrix for the current hotfix pass and record what coverage already exists versus what must still be added.

This note is planning only.
It does not override normative specs.

## Scope

Container families in scope:

- `vector_t<T>`
- `hash_t<T>`
- `hash_t<T, T_KEY>` when that path is active
- `mixed_t` when it carries iterable data

Loop forms in scope:

- `foreach ($data as $value)`
- `foreach ($data as $k => $value)`
- `foreach ($data as &$value)`
- `foreach ($data as $k => &$value)`

## Contract Notes

- `vector_t<T>` key/value form must work, with `$k` as the zero-based position.
- `hash_t<...>` key/value form must preserve the runtime key path and must not spuriously degrade into `mixed_t`.
- By-value forms must lower through `value_copy()`.
- By-reference forms must lower through `value_ref()`.
- By-reference `foreach` requires an addressable source expression, not just an iterable source expression.
- Unsupported by-reference temporary/rvalue sources may fail at compile time for now.

## Coverage Audit

### `vector_t<T>`

- value-only: present
  - `tests/php/control_flow/level_01/control_flow_006_foreach_value_basic.phs`
- key/value: present
  - `tests/php/control_flow/level_02/control_flow_015_foreach_key_value_negative.phs`
- ref value-only: present
  - `tests/php/control_flow/level_02/control_flow_013_foreach_ref_value_basic.phs`
- key/ref value: present
  - `tests/php/control_flow/level_02/control_flow_016_foreach_key_ref_value_negative.phs`

### `hash_t<T>`

- value-only: missing before this pass
- key/value: present
  - `tests/php/control_flow/level_01/control_flow_027_foreach_hash_key_value_basic.phs`
- ref value-only: present
  - `tests/php/control_flow/level_02/control_flow_029_foreach_hash_ref_value.phs`
- key/ref value: present
  - `tests/php/control_flow/level_02/control_flow_030_foreach_hash_key_ref_value.phs`

### `mixed_t` iterable object shape

- value-only: present
  - `tests/php/control_flow/level_02/control_flow_022_foreach_mixed_object_value_only.phs`
- key/value: present
  - `tests/php/control_flow/level_02/control_flow_023_foreach_mixed_object_key_value.phs`
- ref value-only: present
  - `tests/php/control_flow/level_02/control_flow_024_foreach_mixed_object_ref_value.phs`
- key/ref value: present
  - `tests/php/control_flow/level_02/control_flow_025_foreach_mixed_object_key_ref_value.phs`

### Typed object payload flows from `hash<shared_p<T>>`

- value-only typed property access: present
  - `tests/php/classes/level_02/classes_014_hash_sharedp_foreach_value_only.phs`
- key/value typed property access and typed re-indexing: present
  - `tests/php/classes/level_02/classes_013_hash_sharedp_foreach_typing.phs`
- explicit local stabilization: present
  - `tests/php/classes/level_02/classes_015_hash_sharedp_key_stabilization.phs`
- direct key-helper use from non-`this` method-call source without stabilization: missing before this pass
- direct object-field writes on loop value from non-`this` method-call source without stabilization: missing before this pass

## This Pass

Add tests for:

- `hash<T>` value-only iteration
- richer typed `hash<shared_p<T>>` flow where:
  - the `foreach` source is a method call on a non-`this` object
  - the loop key is passed directly to a typed helper
  - the loop value is used for typed property reads and writes
  - the loop value writes remain object-field writes, not keyed table writes

After these tests exist:

1. run the targeted `foreach`-related tests
2. inspect generator/runtime behavior against the matrix
3. make the focused `foreach` fixes
4. run the normal non-matrix PHP suite
