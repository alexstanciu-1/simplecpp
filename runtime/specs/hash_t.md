Doc Status: normative


See `../../specs/spec_map.md` for document hierarchy, authority, and v1 conflict-resolution rules.

# `hash_t` Import / Adaptation Contract (v1)

## 1. Purpose

`scpp::hash_t` is the Prism++ runtime container family used to represent the PHP surface concept of `array` and the typed runtime map family.

It is a specialized ordered key/value container with:

- integer keys
- string keys
- insertion-order iteration
- packed optimization when keys are `0..n-1`
- associative behavior when needed

`hash_t` is the runtime semantic container family.
`hash_t<mixed_t, mixed_t>` remains the dynamic PHP-array storage specialization in this phase.
Typed runtime maps use `hash_t<T_VALUE, T_KEY>`, with `T_KEY = string_t` by default.
PHP-array-like lowering targets the dynamic `mixed_t` / `hash_t<mixed_t, mixed_t>` path.
Object-like lowering uses `dynamic_t<>`, whose committed v1 meaning remains shared dynamic storage backed by `hash_t<mixed_t, mixed_t>`.
The runtime headers may expose a broader template form for `dynamic_t`, but that broader shape is not yet a language-surface commitment.

The donor implementation is `mem_container`, but generated/runtime-facing code must target `hash_t` only.

## 2. Import / adaptation rules

The goal is **not** to expose `mem_container` directly.
The goal is to:

- reuse its storage strategy
- keep its memory/performance-oriented structure
- adapt names and public I/O to the `scpp` runtime surface
- enforce the `hash_t` contract even where donor behavior differs

This means code flow is:

`mem_container` -> adapted implementation -> `scpp::hash_t`

Not:

`scpp::hash_t` -> thin wrapper around untouched `mem_container`

## 3. Placement rules

Current runtime layout uses lightweight wrappers in `include/scpp/`.
Heavier code-bearing runtime components should live under:

- `include/scpp/support/`

Public compatibility includes may remain in `include/scpp/`.

For `hash_t`:

- public include: `scpp/hash_t.hpp`
- implementation-bearing files: `include/scpp/support/hash_t.hpp` and `include/scpp/support/hash_t.cpp`

For `php` helpers:

- public include: `scpp/php.hpp`
- implementation-bearing file: `include/scpp/support/php.hpp`

## 4. Public runtime names

Official runtime names:

- `scpp::hash_t`
- `scpp::mixed_t`
- `scpp::maybe_value_t`

Rejected public names:

- `array_t`
- `container_t`
- direct `mem_container` exposure

## 5. Public key model

For the dynamic specialization `hash_t<mixed_t, mixed_t>`, allowed public key types are:

- `scpp::int_t`
- `scpp::string_t`

For the typed runtime family `hash_t<T_VALUE, T_KEY>`, the public key type is `T_KEY`.
Current supported typed key families are:

- `scpp::string_t`
- `scpp::int_t`
- `scpp::shared_p<T>`
- `scpp::weak_p<T>`
- `scpp::unique_p<T>`

Rules:

- integer keys and string keys are distinct
- no implicit normalization between `123` and `"123"` for the typed runtime family
- the dynamic PHP-target path keeps its documented runtime key behavior separately
- overloads are preferred over a public `table_key_t` in v1

## 6. Public value model

`hash_t<mixed_t, mixed_t>` stores `scpp::mixed_t`.
Typed runtime maps `hash_t<T_VALUE, T_KEY>` store `T_VALUE`.

Rules:

- `null_t` is a valid stored value
- missing key is **not** the same as stored `null_t`
- lookup absence uses `nullopt_t`, not `null_t`

## 7. Public lookup result type

Official lookup result type for the dynamic specialization:

```cpp
using maybe_value_t = nullable<mixed_t>;
```

Typed runtime maps use:

```cpp
nullable<T_VALUE>
```

Semantics:

- empty `nullable<mixed_t>` means â€œnot foundâ€
- present `mixed_t(null_t{})` means â€œfound and the stored value is nullâ€

Helpers:

```cpp
is_nullopt(x)
was_found(x)
```

Rules:

- `is_nullopt(x)` is the generic helper
- `was_found(x)` is the semantic alias for table lookups
- `nullopt_t` must remain distinct from stored `null_t`

## 8. Public API surface (v1)

Priority note:
- `hash_t` and `mixed_t` runtime-facing behavior must be read together with `../../specs/dynamic_types.md` sections **1.2 Explicit Typed Boundaries** and **1.3 Technical Compromises to Preserve Explicit Typed Boundaries in v1**
- when current generated/user-visible behavior depends on a temporary dynamic-to-typed bridge at a valid explicit typed boundary site, that bridge remains part of the v1 contract until the generator can materialize the explicit cast itself


Dynamic specialization:

```cpp
class hash_t<mixed_t, mixed_t> {
public:
	bool_t empty() const;
	std::size_t size() const;
	bool_t is_packed() const;
	void clear();

	int_t append(const mixed_t& value);

	hash_t& set(const int_t& key, const mixed_t& value);
	hash_t& set(const string_t& key, const mixed_t& value);

	bool_t has(const int_t& key) const;
	bool_t has(const string_t& key) const;

	maybe_value_t find(const int_t& key) const;
	maybe_value_t find(const string_t& key) const;

	mixed_t _find_val(const int_t& key) const;
	mixed_t _find_val(const string_t& key) const;

	mixed_t& at(const int_t& key);
	mixed_t& at(const string_t& key);
	const mixed_t& at(const int_t& key) const;
	const mixed_t& at(const string_t& key) const;

	mixed_t& operator[](const int_t& key);
	mixed_t& operator[](const string_t& key);

	bool remove(const int_t& key);
	bool remove(const string_t& key);
};
```

Typed runtime family:

```cpp
template <typename T_VALUE, typename T_KEY = string_t>
class hash_t {
public:
	bool_t empty() const;
	std::size_t size() const;
	bool_t is_packed() const;
	void clear();

	int_t append(const T_VALUE& value);

	hash_t& set(const T_KEY& key, const T_VALUE& value);

	bool_t has(const T_KEY& key) const;
	nullable<T_VALUE> find(const T_KEY& key) const;

	T_VALUE& at(const T_KEY& key);
	const T_VALUE& at(const T_KEY& key) const;

	T_VALUE& operator[](const T_KEY& key);
	const T_VALUE& operator[](const T_KEY& key) const;

	bool remove(const T_KEY& key);
};
```

Typed-family note:
- `append(...)` remains present for generator/runtime structural compatibility
- it is semantically valid only when `T_KEY = int_t`
- other typed-key modes must fail clearly at runtime rather than pretending append semantics exist

## 9. Method semantics

### `set(key, value)`

- stores `value` under `key`
- if key already exists, it overwrites
- duplicate physical keys are forbidden at contract level
- returns `hash_t&` for chaining

### `append(value)`

- appends using the next numeric key
- append key is `max_existing_int_key + 1`
- does not go back to older gaps unless explicit keyed assignment is used
- returns the appended numeric key

### `has(key)`

- returns `true` iff the key exists
- stored `null_t` still counts as existing

### `find(key)`

- non-inserting lookup
- returns `maybe_value_t`
- missing key => empty `nullable<mixed_t>` / `nullopt` semantic state
- does not mutate the container
- intended for explicit logic that must preserve "was found" information

### `operator[](key)`

- mutable `operator[]` inserts a default-constructed slot when the key is missing and returns `mixed_t&`
- const `operator[]` does not insert and returns the shared null-like fallback when the key is missing
- generator-facing read-only nested array access now uses `_find_val(...)` / `mixed_t::get(...)`; mutating nested access still uses `operator[]`

### `_find_val(key)`

- non-inserting lookup
- returns `mixed_t`
- present key => returns stored `mixed_t`
- missing key => returns `mixed_t(null_t{})`
- does not mutate the container
- does not preserve the distinction between:
	- missing key
	- stored `null_t`
- intended as the plain dynamic read path so generators can lower read-only dim access without autovivification

### `at(key)`

- checked access
- does not insert
- missing key => failure under throw-style checked-access semantics
- implementation currently uses `std::out_of_range`
- later runtime error routing may replace the low-level mechanism without changing the contract

### `operator[](key)`

- mutating/inserting access
- may create missing entry
- intended for assignment-capable access only

Important consequence:

```cpp
auto x = data["name"];
```

on a non-const table may create `"name"` if it was missing.

### `remove(key)`

- returns `true` if key existed and was removed
- returns `false` if key was not present

### `clear()`

- full internal reset
- container becomes empty
- packed state resets to packed-empty
- next append starts again from key `0`

## 10. Packed / associative state

Rules:

- packed vs associative is mostly an implementation detail
- public API may expose `is_packed()`
- no broader public control over storage mode in v1
- internal strategy may evolve later without changing public semantics

## 11. Generator rules

### Plain dynamic read access
When source semantics require ordinary dynamic lookup without creation:

- generate direct `operator[]` access for normal dim reads/writes
- missing read becomes `mixed_t(null_t{})`
- this keeps the generator simple and pushes null-on-miss behavior into the runtime

Preferred lowering:

```cpp
auto x = data["name"];
```

### Explicit presence-sensitive access
When source semantics require the distinction between:
- key not found
- key found with stored `null_t`

use:

- `find()`

Preferred lowering:

```cpp
auto x = data.find("name");
```

### Checked read access
When source semantics require checked access/failure:

- generate `at()`

### Assignment / mutation
When source semantics require assignable keyed access:

- generated keyed writes should keep using `set()`
- non-assignment generated reads should lower through `operator[]`

### Important warning
Because plain reads now use the direct `operator[]` path, read-only lowering stays non-materializing even though typed-reference contexts may still bind through the same generated expression.
Text/echo coercion of a slot must also stay non-materializing and route through the slot's value-read path before normal `to_string(...)` dispatch.

## 12. Deferred / out of scope for v1

The following are intentionally deferred:

- nested auto-promotion such as `t["address"]["city"] = ...`
- full nested slot promotion beyond the current direct table-dim model
- const `operator[]`
- merge/diff/intersect helpers
- sorting helpers
- public clone API
- generalized `table_key_t`
- deep object-growth semantics through `mixed_t`

## 13. Summary

`hash_t` v1 is defined as:

- ordered
- int/string keyed
- packed-optimized
- overwrite-on-set
- append by `max_int_key + 1`
- `find()` for explicit non-inserting lookup with presence information
- `_find_val()` for low-level non-inserting dynamic reads with null-on-miss
- `operator[]` for generator-facing dim reads and writes
- `at()` for checked non-inserting access
- `set()` for canonical keyed writes in generated code
- `remove()` returns `bool`
- `clear()` fully resets

And most importantly:

**Prism++ targets `hash_t`, not `mem_container` directly.**


## Key Stability Guarantees

Removing an element must **not change the keys of other elements**, including numeric keys.

Example:

```cpp
hash_t t;
t.append(100); // key 0
t.append(200); // key 1

t.remove(0);

// REQUIRED:
t.has(0) == false;
t.has(1) == true;

auto k = t.append(300);
// REQUIRED:
k == 2;
```

### Packed-mode removal rule

In packed mode, removal must not perform physical compaction that shifts later numeric keys.

If the underlying storage cannot preserve key stability in packed mode, the implementation must:

- transition to associative mode before removal, or
- use a tombstone-preserving strategy

### Forbidden behavior

The following is forbidden:

- removing a packed element via direct vector erase that shifts later elements
- implicitly renumbering keys after removal
- using packed-mode compaction when it changes visible key identity

## `remove(key)` additional contract notes

- `remove(key)` returns `true` only if the key existed and was removed
- `remove(key)` returns `false` if the key was not present
- `remove(key)` must preserve key identity of all remaining entries
- after removing numeric key `n`, a later `append()` still uses `max_existing_int_key + 1`



## Dynamic Runtime Integration

`hash_t` operates in conjunction with `mixed_t`, but not every instantiation stores dynamic values.

Current rule:

- `hash_t<mixed_t, mixed_t>` stores dynamic runtime values and is the PHP-array storage specialization
- typed runtime maps `hash_t<T_VALUE, T_KEY>` store typed values directly
- mixed/dynamic helper behavior such as `_find_val()` is specific to the dynamic specialization and to `mixed_t`-mediated paths

Invalid operations on retrieved values result in runtime errors, not compile-time errors.


## mixed_t chaining note

`mixed_t` provides `_find_val()` as the chained dynamic read helper.

Current rule:

- if `mixed_t` holds an owned or shared `hash_t`, `_find_val()` forwards to that table
- in current practice this means the dynamic/default `hash_t<mixed_t, mixed_t>` path used by `mixed_t`
- if `mixed_t` is `null`, `_find_val()` returns `mixed_t(null_t{})`
- if `mixed_t` holds an expired weak table carrier, `_find_val()` currently returns `mixed_t(null_t{})`
- other receiver kinds fail at runtime

This keeps generator read lowering simple for patterns such as chained dynamic reads while leaving explicit presence-sensitive logic on raw `hash_t::find()`.


## Nested table dim support

- Nested table dim reads chain through direct `operator[]` access so `$x["inner"][0]` follows the runtime `mixed_t` / `hash_t` contract.
- Nested append on a table-valued slot is supported through chained `operator[]` plus `append(...)` on `mixed_t` / `hash_t<mixed_t, mixed_t>`.
- Example: `x[0]["items"].append(1);`
- Table-valued assignments into table slots now use direct `mixed_t` assignment through the returned `operator[]` reference.


## Slot / value copy behavior

- Runtime `mixed_t` array carriers use shared table storage plus detach-on-write semantics.
- assigning a nested array element by value (for example `$copy = $x[0];`) may initially share the same table payload as the original slot.
- later writes through either value must detach shared table storage before mutation so PHP by-value behavior is preserved.
- by-value PHP `array` parameters use the same `mixed_t` ABI and rely on runtime detach-on-write instead of a generator-side `table_copy(...)` / `php::value_copy(...)` wrapper.
- explicit deep snapshot helpers may still be used when a truly eager owning copy is required, but they are no longer the default lowering for DIM reads or by-value array argument passing.


## Runtime helpers

- `php::count(const hash_t<mixed_t, mixed_t>&)` is supported and returns the current logical element count of the PHP array wrapper.

## PHP target key semantics

When the runtime is compiled with `-DSCPP_LANGUAGE_TARGET_PHP=1`, `hash_t<mixed_t, mixed_t>` normalizes decimal integer strings to integer keys at runtime through the table key path. This normalization must be shared by set/get/isset/unset/operator[] so behavior stays consistent. Append must continue from the current maximum integer key plus one.



## `dynamic_t` relation

The committed v1 public/default meaning of `dynamic_t<>` reuses `hash_t<mixed_t, mixed_t>` as its payload, but it remains a distinct runtime form.
Runtime headers currently generalize this to `dynamic_t<T_VALUE, T_KEY> = shared_p<hash_t<T_VALUE, T_KEY>>`.
That broader template shape is a runtime-side generalization for now, not a language-surface expansion.
Explicit conversion remains required between the default dynamic form and plain hash payloads.


## `try_ref(...)` in the current safe subset

`hash_t<T_VALUE, T_KEY>::try_ref(...)` is a restricted escape hatch. It currently succeeds only when `T_VALUE` is `shared_p<T>` and returns a copy of that handle. All other element types throw. This preserves memory/lifetime safety without exposing native references or pointers to table interior storage.
