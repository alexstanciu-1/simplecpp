# `hash_t` Key Abstraction Task
Doc Status: planning

Status: temporary working note

Purpose: capture the current task objectives and discussion frame for evolving `hash_t` key handling without turning this note into semantic authority.

See `specs/spec_map.md` for repository authority order.
This document is a planning note for the current task and may be removed once the work is complete.

## 1. Current task intent

We want to make `hash_t` keys more abstract and extensible while preserving current `mixed_t` compatibility and current runtime behavior where required.

The plan is expected to proceed in stages:

1. define the key interface
2. route the current implementation through that interface
3. test and stabilize behavior
4. add more key capabilities and modes

## 1.1 Agreed design constraints

The following constraints are currently agreed for this task:

- dynamic mode remains specialized
- storage layout stays the same for the current dynamic mode
- generic machinery must be isolated so dynamic mode stays simple

Meaning:

- `hash_t<mixed_t>` remains the specialized dynamic implementation path
- the current dynamic storage model is not to be destabilized by the typed-key work
- new generic abstractions should not force the dynamic path into a heavier storage or dispatch model

## 1.2 Agreed implementation decisions

The following decisions are currently considered settled enough to guide implementation:

- codec/policy type names should be short and clear about what they do
- `table_key_t` should be removed rather than kept as a temporary bridge
- unsupported `append()` calls should fail with a short, clear runtime error
- expired `weak_p<T>` hashes to zero and should behave accordingly without extra complexity

## 2. Objectives

### 2.1 Size-configurable stored hash width

Current implementation anchor:

```cpp
using key_storage_t = std::uint32_t;
```

Goal:

- make the stored hash width configurable
- intended stored widths currently discussed:
  - 1 byte
  - 2 bytes
  - 3 bytes
  - 4 bytes
  - 8 bytes
- these sizes refer to how many bytes of hash are stored
- these sizes do not, by themselves, define canonical key identity width
- the rule that selects the stored hash width is intentionally deferred for later design

Important clarification:

- stored hash width and canonical key identity are separate concerns
- a truncated or folded stored hash may be useful as a lookup aid
- the stored hash should not be assumed to be the full semantic identity of the key unless the design explicitly says so

### 2.2 Explicit key-type support

We need a key model that can explicitly represent these key families:

- `string_t`
- `int_t`
- `shared_p<T>`
- `weak_p<T>`
- `unique_p<T>`

Open note:

- the exact ownership, identity, and hashing constraints for handle-like key families still need to be defined carefully

### 2.3 Preserve current compatibility for `mixed_t`

Requirements:

- current `mixed_t` use cases must keep working
- current generator/runtime expectations for `hash_t<mixed_t>` should remain supported during the transition
- compatibility should be preserved even if the internal key path is refactored behind a new interface
- `hash_t<mixed_t>` remains limited to integer and string key kinds in the current dynamic mode
- the current dynamic storage layout stays in place for this mode

### 2.4 Attachable fixed-key list

Desired capability:

- create or bind a `hash_t` against a fixed shared key set
- example allowed keys:
  - `"id"`
  - `"name"`
  - `"age"`
- only keys from that attached list should be accepted in that mode
- multiple `hash_t` instances should be able to share the same attached key set

This suggests a reusable key-domain or key-schema concept.

### 2.5 Performance and memory constraints

The key-abstraction work must keep performance and memory usage in view throughout the design.

Requirements:

- performance should not degrade materially versus the current implementation for current compatibility-oriented use cases
- memory usage should remain controlled and should not regress materially without an explicit tradeoff decision
- any abstraction introduced for keys should be evaluated not only for extensibility but also for runtime cost

Areas that need explicit attention during design and validation:

- lookup cost
- insert/update cost
- append behavior cost
- iteration cost
- key-storage footprint
- impact of pooled versus explicit-key modes on memory growth
- impact of fixed attached key domains on sharing and duplication

## 3. Candidate modes

Two current candidate modes are in scope:

### 3.1 Current pooled-lowered mode

Characteristics:

- keys are lowered to a compact integer storage form
- string keys use the existing string-pool style path
- this is the current compatibility-oriented path
- current storage behavior remains in place for this mode
- packed integer fast paths remain in place for this mode
- existing `mixed_t` compatibility is preserved in this mode
- this mode is the current home of `hash_t<mixed_t>`
- this mode admits only integer and string key kinds even if the PHP-facing dynamic surface uses `mixed_t` values for keys

### 3.2 Explicit key-types mode

Characteristics:

- keys remain explicitly typed at the key-model level
- the runtime interface should be able to distinguish the supported key families directly
- this mode stores the actual keys as explicit typed keys
- this mode is not required to lower keys through the current pooled `std::uint32_t` token model
- hashing and equality should operate on the explicit key representation in this mode
- typed hash direction currently under discussion:
  - `hash<T>` defaults to `string_t` keys
  - `hash<T, T_KEY>` uses explicit typed keys

### 3.3 Current working storage split

The current working understanding for the two modes is:

- current pooled-lowered mode keeps the current tokenized/internal storage strategy
- explicit key-types mode stores actual typed keys directly

This means the design is not currently trying to force both modes into one identical key-storage representation.

Instead, the current goal is:

- shared or compatible external key-facing behavior where useful
- mode-appropriate internal storage where needed

Reasoning:

- current mode primarily serves compatibility and compactness goals
- explicit mode primarily serves richer key semantics and richer key families
- keeping these concerns separate reduces risk and avoids unnecessary disruption to current `mixed_t` behavior

### 3.4 API-surface constraint from the type-blind generator

The current PHP S2S generator is intentionally type-blind at the structural lowering layer.

Implication:

- methods such as `append()` may need to remain present on the relevant runtime surface even when a particular key mode cannot support them semantically

Current working direction:

- `append()` remains available on the runtime surface because generated code may still target it structurally
- unsupported `append()` use in a given key mode should fail clearly, either:
  - at runtime, or
  - at compile time where the concrete typed context allows it cleanly
- current preference is a short, clear runtime failure message when the call reaches runtime in an unsupported key mode

This applies similarly to any other structurally-lowered operation whose semantic validity depends on the concrete key mode.

## 4. First concrete design direction: hashing

The first concrete design area selected for this task is key hashing.

### 4.1 Hashable key families

For the current design pass, keep these key families in scope for hashing:

- `int_t`
- `string_t`
- `shared_p<T>`
- `weak_p<T>`
- `unique_p<T>`

Explicitly out of scope for this pass:

- `bool_t`

### 4.2 Hashability concept

Hash-able key types should participate through a C++20 concept.

Intent:

- key admission for hashing should be explicit
- the concept should describe the required hashing surface
- unsupported key families should fail clearly at compile time where possible

Working requirement:

- a hash-able key type should provide:

```cpp
get_hash(byte keysize = 8)
```

Open note:

- the exact return type still needs to be defined
- the exact `byte` type spelling still needs to be pinned down
- the meaning of `keysize` must be specified precisely before implementation
- the relationship between `keysize` and stored hash width must be defined explicitly

### 4.3 Initial hashing policy by key family

Current working direction:

- `int_t` uses a normal value hash
- `string_t` uses a normal string hash
- `shared_p<T>` hashes by pointer address value
- `weak_p<T>` hashes by pointer address value
- `unique_p<T>` hashes by pointer address value

Notes:

- pointer-address hashing is the current working assumption for handle-like keys
- equality semantics must be kept aligned with the chosen hashing semantics
- expired `weak_p<T>` currently resolves to the simple zero case
- object-identity hashing and pointee-value hashing are intentionally different models; this pass is selecting object identity
- if a reduced-width hash is stored, collision implications must be treated as a performance concern unless that stored value is promoted to identity by design

Working simplification for expired weak handles:

- expired `weak_p<T>` hashes to zero
- expired `weak_p<T>` should behave like the zero case rather than introducing extra identity complexity

### 4.4 Candidate key codec abstraction

One current design direction is to introduce a generic key codec layer with operations such as:

- `pack_key(...)`
- `unpack_key(...)`

Intent:

- current pooled-lowered mode can continue to pack dynamic integer/string keys into its current compact/tokenized representation
- explicit key-types mode can use a different representation without forcing all key kinds through the pooled token path
- shared container logic can talk to a key codec/policy layer rather than hardcoding one representation

Open note:

- the codec boundary should not hide important semantic differences between:
  - dynamic integer/string key mode
  - explicit typed-key mode
- the codec should help share implementation where useful, without forcing identical storage models

## 5. Working design direction

The current preferred sequencing is:

1. introduce an abstraction/interface for keys
2. make current `hash_t` operations go through that abstraction
3. preserve behavior for current `mixed_t` use
4. validate and fix regressions
5. extend toward additional key families and attached-key-domain support

This sequence is preferred so refactoring risk stays separate from semantic expansion risk.

Performance note:

- design choices should favor preserving current fast paths where possible
- abstraction layers should avoid forcing unnecessary heap allocation, virtual dispatch, or per-key payload growth in the compatibility path
- generic machinery should be isolated so the dynamic path remains specialized and simple

## 6. Discussion topics still open

The following points still need design discussion:

- what the public key interface should look like
- whether key abstraction is compile-time, runtime, or hybrid
- whether `hash_t` should stay value-typed-only and gain a separate key policy/domain object
- what the canonical key identity representation is for each key family and mode
- whether `hash_t<mixed_t>` remains a true specialization or becomes one mode of a broader keyed-container family
- how explicit object/handle keys should define equality and hashing
- how attached fixed-key lists should be declared, shared, and validated
- how much implementation can be shared between pooled-lowered mode and explicit key-types mode without forcing one storage model on both
- how packed-array behavior interacts with the abstract key model
- whether stored reduced-width hashes remain useful after the key-identity model is defined
- how much current numeric-string normalization, if any, remains valid in compatibility mode

Resolved enough for implementation and no longer treated as open:

- naming should be short and clear
- `table_key_t` is to be removed rather than preserved for backward compatibility
- unsupported `append()` should fail with a short, clear message
- expired `weak_p<T>` uses the simple zero behavior
