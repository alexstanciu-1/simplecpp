# `foreach` Typed `hash<>` Expectation-Gap Catalog
Doc Status: planning

Date: 2026-05-16

Purpose:
- catalog the authored code shapes that matter for the current `foreach` plus typed `hash<>` stabilization pass
- separate true expectation-gap repros from contrast/control samples
- give the next implementation pass a concrete checklist of examples to preserve or inspect

This note is planning only.
It does not override normative specs.

## Scope

This catalog focuses on cases where authored typed-`hash<>` code is expected to remain strongly typed through `foreach`, but historically tended to degrade into dynamic or `mixed_t` behavior during lowering.

Main expectation:

- once lowering enters `foreach_range(...)`, the runtime iterator interface should carry key/value semantics
- typed `hash<T>` loop keys should stay concrete enough for direct helper boundaries and reindexing
- typed `hash<T>` loop values should stay concrete enough for direct property access and writes
- the generator should not repair earlier type loss with ad hoc casts or dynamic-table fallbacks

## Reading Guide

- `Expectation-gap repro`: a shape that historically exposed the bad lowering family we want to remove
- `Control sample`: a useful baseline or contrast case, but not itself the clearest failing-shape repro
- `Stabilization workaround sample`: a sample that proves manual typed stabilization works, and helps show what should become unnecessary

## Catalog

### 1. Minimal typed hash value/key loop shapes

#### A. Typed hash key/value basic

- File: [tests/php/control_flow/level_01/control_flow_027_foreach_hash_key_value_basic.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/control_flow/level_01/control_flow_027_foreach_hash_key_value_basic.phs:1)
- Shape:

```php
$items /** hash<int> */ = ["a" => 4, "b" => 5];

foreach ($items as $k => $value) {
	echo $k, "\n";
	echo $value, "\n";
}
```

- Category: control sample
- Expectation:
  - `$k` should behave like the hash key type path exposed by the runtime iterator
  - `$value` should behave like `int`
- Why it matters:
  - smallest direct proof that typed `hash<int>` key/value loops are fundamentally healthy
- Notes:
  - this sample is too small to expose the downstream property-write and helper-boundary failures by itself

#### B. Typed hash value-only basic

- File: [tests/php/control_flow/level_01/control_flow_031_foreach_hash_value_basic.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/control_flow/level_01/control_flow_031_foreach_hash_value_basic.phs:1)
- Shape:

```php
$items /** hash<int> */ = ["a" => 4, "b" => 5];

foreach ($items as $value) {
	echo $value, "\n";
}
```

- Category: control sample
- Expectation:
  - value-only `foreach` over `hash<int>` should preserve the concrete value type without degrading to `mixed_t`
- Why it matters:
  - this is the simplest value-only typed-hash loop form

#### C. Typed hash by-ref value-only

- File: [tests/php/control_flow/level_02/control_flow_029_foreach_hash_ref_value.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/control_flow/level_02/control_flow_029_foreach_hash_ref_value.phs:1)
- Category: control sample
- Expectation:
  - by-ref value loops should lower through `value_ref()`
  - writes through `$v` should update the original hash entry

#### D. Typed hash key plus by-ref value

- File: [tests/php/control_flow/level_02/control_flow_030_foreach_hash_key_ref_value.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/control_flow/level_02/control_flow_030_foreach_hash_key_ref_value.phs:1)
- Category: control sample
- Expectation:
  - key/value by-ref form should preserve both the key path and the reference-backed value path

### 2. Direct typed-object access inside typed hash loops

#### E. Typed hash loop with direct object property read and typed reindex

- File: [tests/php/classes/level_02/classes_013_hash_sharedp_foreach_typing.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_013_hash_sharedp_foreach_typing.phs:1)
- Category: expectation-gap repro
- Core shape:

```php
foreach ($m->properties as $k => $prop) {
	$by_name[$k] = $prop;
	echo $prop->name, "\n";
}
```

- Expectation:
  - `$k` should stay suitable for direct reindexing into another `hash<model_property>`
  - `$prop` should stay concrete enough for direct `->name` access
- Historical bad-family risk:
  - loop key/value locals degrade too early
  - typed reindexing path starts behaving like dynamic table access
  - object property access risks following a degraded dynamic path instead of typed object access
- Why it matters:
  - this is the smallest class-based sample that combines loop typing, typed reindexing, and direct object-field access

#### F. Typed hash value-only loop with direct object property read

- File: [tests/php/classes/level_02/classes_014_hash_sharedp_foreach_value_only.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_014_hash_sharedp_foreach_value_only.phs:1)
- Category: expectation-gap repro
- Core shape:

```php
foreach ($m->properties as $prop) {
	echo $prop->name, "\n";
}
```

- Expectation:
  - value-only iteration over `hash<model_property>` should preserve the concrete shared/typed object value path
- Historical bad-family risk:
  - `$prop` degrades toward `mixed_t`
  - property reads drift toward dynamic access policy
- Why it matters:
  - isolates the value-path problem without key/helper noise

### 3. Samples that should no longer need manual stabilization

#### G. Key/value stabilization through explicit typed locals

- File: [tests/php/classes/level_02/classes_015_hash_sharedp_key_stabilization.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_015_hash_sharedp_key_stabilization.phs:1)
- Category: stabilization workaround sample
- Core shape:

```php
foreach ($m->properties as $k => $prop) {
	$key /** string */ = $k;
	$tmp /** model_property */ = $prop;
	$copy[$key] = $tmp;
}
```

- Expectation:
  - explicit typed locals should work cleanly
- Why it matters:
  - proves the typed destination boundaries are valid
  - serves as contrast with direct-loop-local samples that should also work without needing this extra stabilization

### 4. Direct helper boundaries and typed field writes on loop locals

#### H. Loop key passed directly to helper, value used for direct field writes

- File: [tests/php/classes/level_02/classes_016_hash_sharedp_method_source_direct_key_usage.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_016_hash_sharedp_method_source_direct_key_usage.phs:1)
- Category: expectation-gap repro
- Core shape:

```php
foreach ($properties as $property_name => $typed_property) {
	echo accepts_nullable($property_name), "\n";
	echo $typed_property->name, "\n";
	$typed_property->disabled = true;
	$typed_property->attached_storage = $storage;
	echo $typed_property->attached_storage->name, "\n";
}
```

- Expectation:
  - `$property_name` should cross the `?string` helper boundary without synthetic repair casts
  - `$typed_property` should stay on the typed object path for field reads and writes
- Historical bad-family risk:
  - synthetic `cast<nullable<string_t>>(...)` as a repair for degraded key typing
  - object-field writes lowering as keyed table writes when the value local degrades
  - property reads lowering through dynamic `.get(...)`
- Why it matters:
  - this is one of the clearest “real code” repros of the unwanted fallback family

#### I. Direct helper read, typed override merge, and typed reindexing

- File: [tests/php/classes/level_02/classes_017_hash_sharedp_direct_key_helper_and_reindex.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_017_hash_sharedp_direct_key_helper_and_reindex.phs:1)
- Category: expectation-gap repro
- Expectation:
  - loop key should stay concrete through helper lookup and direct reindexing
  - loop value should stay concrete through field writes and downstream reads
- Historical bad-family risk:
  - same as sample H, with the added pressure of helper return flow plus write-back into another typed hash
- Why it matters:
  - stresses the full chain more than the simpler direct-key sample

### 5. Method-source and nullable helper-boundary cases

#### J. Method helper with nullable key boundary

- File: [tests/php/classes/level_02/classes_018_hash_sharedp_method_nullable_key_boundary.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_018_hash_sharedp_method_nullable_key_boundary.phs:1)
- Category: expectation-gap repro
- Expectation:
  - a typed hash loop key should satisfy a `?string` helper boundary naturally
  - the generator should not inject a synthetic `cast<nullable<string_t>>(...)` bridge just because the key came from `foreach`
- Why it matters:
  - isolates one concrete bad symptom called out in the task notes

#### K. Method-return local followed by typed-hash foreach nullable key boundary

- File: [tests/php/classes/level_02/classes_019_hash_method_return_local_foreach_nullable_key.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_019_hash_method_return_local_foreach_nullable_key.phs:1)
- Category: expectation-gap repro
- Expectation:
  - if a local object is initialized from a method return and then used as the typed-hash foreach source, loop key locals should still stay concrete enough for helper boundaries
- Historical bad-family risk:
  - non-`this` method-call or method-return source causes the generator to fall off the typed path too early
- Why it matters:
  - this directly matches a generator anti-pattern called out in the planning note: non-`this` method-call sources should not degrade just because they are composed expressions

## Current Working Checklist

For the current stabilization pass, the most important expectation-gap repros are:

- `classes_013_hash_sharedp_foreach_typing`
- `classes_014_hash_sharedp_foreach_value_only`
- `classes_016_hash_sharedp_method_source_direct_key_usage`
- `classes_017_hash_sharedp_direct_key_helper_and_reindex`
- `classes_018_hash_sharedp_method_nullable_key_boundary`
- `classes_019_hash_method_return_local_foreach_nullable_key`

Useful contrast/control samples:

- `control_flow_027_foreach_hash_key_value_basic`
- `control_flow_031_foreach_hash_value_basic`
- `control_flow_029_foreach_hash_ref_value`
- `control_flow_030_foreach_hash_key_ref_value`
- `classes_015_hash_sharedp_key_stabilization`

## Working-As-Expected Regression Guards

These are healthy samples that should stay healthy while we tighten the typed-hash lowering path.

### Typed hash controls already working

- [tests/php/control_flow/level_01/control_flow_027_foreach_hash_key_value_basic.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/control_flow/level_01/control_flow_027_foreach_hash_key_value_basic.phs:1)
  - current role: basic typed `hash<int>` key/value loop
- [tests/php/control_flow/level_01/control_flow_031_foreach_hash_value_basic.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/control_flow/level_01/control_flow_031_foreach_hash_value_basic.phs:1)
  - current role: basic typed `hash<int>` value-only loop
- [tests/php/control_flow/level_02/control_flow_029_foreach_hash_ref_value.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/control_flow/level_02/control_flow_029_foreach_hash_ref_value.phs:1)
  - current role: by-ref value mutation over typed `hash<int>`
- [tests/php/control_flow/level_02/control_flow_030_foreach_hash_key_ref_value.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/control_flow/level_02/control_flow_030_foreach_hash_key_ref_value.phs:1)
  - current role: key plus by-ref value mutation over typed `hash<int>`

### Typed hash class/object flows currently working

- [tests/php/classes/level_02/classes_013_hash_sharedp_foreach_typing.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_013_hash_sharedp_foreach_typing.phs:1)
  - current role: typed hash key/value loop with typed object reads and typed reindexing
- [tests/php/classes/level_02/classes_014_hash_sharedp_foreach_value_only.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_014_hash_sharedp_foreach_value_only.phs:1)
  - current role: typed hash value-only loop with direct typed object field reads
- [tests/php/classes/level_02/classes_015_hash_sharedp_key_stabilization.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_015_hash_sharedp_key_stabilization.phs:1)
  - current role: explicit typed stabilization control sample
- [tests/php/classes/level_02/classes_016_hash_sharedp_method_source_direct_key_usage.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_016_hash_sharedp_method_source_direct_key_usage.phs:1)
  - current role: direct helper boundary plus typed field write flow
- [tests/php/classes/level_02/classes_017_hash_sharedp_direct_key_helper_and_reindex.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_017_hash_sharedp_direct_key_helper_and_reindex.phs:1)
  - current role: helper lookup plus typed reindexing merge flow
- [tests/php/classes/level_02/classes_018_hash_sharedp_method_nullable_key_boundary.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_018_hash_sharedp_method_nullable_key_boundary.phs:1)
  - current role: nullable key-helper boundary without synthetic repair cast
- [tests/php/classes/level_02/classes_019_hash_method_return_local_foreach_nullable_key.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_019_hash_method_return_local_foreach_nullable_key.phs:1)
  - current role: method-return source plus nullable helper-boundary flow

### Generator know-how foreach references

These are not typed-hash-specific acceptance tests, but they are useful regression guards for the generic `foreach` lowering structure itself:

- [generators/php/samples/know_how/03_foreach_variants.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/samples/know_how/03_foreach_variants.phs:1)
- [generators/php/samples/know_how/04_foreach_by_ref.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/samples/know_how/04_foreach_by_ref.phs:1)
- [generators/php/samples/know_how/04_foreach_by_ref_value_only.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/samples/know_how/04_foreach_by_ref_value_only.phs:1)
- [generators/php/samples/know_how/04_foreach_by_ref_with_key.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/samples/know_how/04_foreach_by_ref_with_key.phs:1)

## What To Inspect In Generated Output

When validating whether a sample still violates expectations, inspect for these specific smells:

- loop key or value locals collapsing to `mixed_t` without necessity
- direct object property reads lowered as dynamic `.get(...)`
- direct object property writes lowered as keyed table writes
- synthetic `cast<nullable<string_t>>(...)` inserted only to repair a degraded loop key
- code paths that branch on guessed source-expression type instead of trusting the `foreach_range(...)` iterator interface

## Initial Implementation Bias

These samples suggest the first implementation pass should focus on:

- the generator-side `foreach` local typing path
- preserving runtime-iterator key/value semantics after `foreach_range(...)`
- reducing or eliminating downstream repair casts that exist only because loop-local typing degraded upstream
