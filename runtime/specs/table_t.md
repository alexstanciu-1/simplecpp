# `table_t` Import / Adaptation Contract (v1)

## 1. Purpose

`scpp::table_t` is the Simple C++ runtime type used to represent the PHP surface concept of `array`.

It is a specialized ordered key/value container with:

- integer keys
- string keys
- insertion-order iteration
- packed optimization when keys are `0..n-1`
- associative behavior when needed

`table_t` is the runtime semantic type.
`table_t` is the only dynamic storage type in this phase.
PHP-array-like lowering targets `table_t` directly.
Object-like lowering uses `shared_p<table_t>`.
`stdClass` wording is explanatory only and does not introduce a separate runtime type.

The donor implementation is `mem_container`, but generated/runtime-facing code must target `table_t` only.

## 2. Import / adaptation rules

The goal is **not** to expose `mem_container` directly.
The goal is to:

- reuse its storage strategy
- keep its memory/performance-oriented structure
- adapt names and public I/O to the `scpp` runtime surface
- enforce the `table_t` contract even where donor behavior differs

This means code flow is:

`mem_container` -> adapted implementation -> `scpp::table_t`

Not:

`scpp::table_t` -> thin wrapper around untouched `mem_container`

## 3. Placement rules

Current runtime layout uses lightweight wrappers in `include/scpp/`.
Heavier code-bearing runtime components should live under:

- `include/scpp/support/`

Public compatibility includes may remain in `include/scpp/`.

For `table_t`:

- public include: `scpp/table_t.hpp`
- implementation-bearing files: `include/scpp/support/table_t.hpp` and `include/scpp/support/table_t.cpp`

For `php` helpers:

- public include: `scpp/php.hpp`
- implementation-bearing file: `include/scpp/support/php.hpp`

## 4. Public runtime names

Official runtime names:

- `scpp::table_t`
- `scpp::value_t`
- `scpp::maybe_value_t`

Rejected public names:

- `array_t`
- `container_t`
- direct `mem_container` exposure

## 5. Public key model

Allowed public key types:

- `scpp::int_t`
- `scpp::string_t`

Rules:

- integer keys and string keys are distinct
- no implicit normalization between `123` and `"123"`
- overloads are preferred over a public `table_key_t` in v1

## 6. Public value model

`table_t` stores `scpp::value_t`.

Rules:

- `null_t` is a valid stored value
- missing key is **not** the same as stored `null_t`
- lookup absence uses `nullopt_t`, not `null_t`

## 7. Public lookup result type

Official lookup result type:

```cpp
using maybe_value_t = nullable<value_t>;
```

Semantics:

- empty `nullable<value_t>` means “not found”
- present `value_t(null_t{})` means “found and the stored value is null”

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

```cpp
class table_t {
public:
	bool_t empty() const;
	std::size_t size() const;
	bool_t is_packed() const;
	void clear();

	int_t append(const value_t& value);

	table_t& set(const int_t& key, const value_t& value);
	table_t& set(const string_t& key, const value_t& value);

	bool_t has(const int_t& key) const;
	bool_t has(const string_t& key) const;

	maybe_value_t find(const int_t& key) const;
	maybe_value_t find(const string_t& key) const;

	value_t _find_val(const int_t& key) const;
	value_t _find_val(const string_t& key) const;

	value_t& at(const int_t& key);
	value_t& at(const string_t& key);
	const value_t& at(const int_t& key) const;
	const value_t& at(const string_t& key) const;

	value_t& operator[](const int_t& key);
	value_t& operator[](const string_t& key);

	bool remove(const int_t& key);
	bool remove(const string_t& key);
};
```

## 9. Method semantics

### `set(key, value)`

- stores `value` under `key`
- if key already exists, it overwrites
- duplicate physical keys are forbidden at contract level
- returns `table_t&` for chaining

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
- missing key => empty `nullable<value_t>` / `nullopt` semantic state
- does not mutate the container
- intended for explicit logic that must preserve "was found" information

### `operator[](key)`

- mutable `operator[]` inserts a default-constructed slot when the key is missing and returns `value_t&`
- const `operator[]` does not insert and returns the shared null-like fallback when the key is missing
- generator-facing read-only nested array access now uses `_find_val(...)` / `value_t::get(...)`; mutating nested access still uses `operator[]`

### `_find_val(key)`

- non-inserting lookup
- returns `value_t`
- present key => returns stored `value_t`
- missing key => returns `value_t(null_t{})`
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
- missing read becomes `value_t(null_t{})`
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
- deep object-growth semantics through `value_t`

## 13. Summary

`table_t` v1 is defined as:

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

**Simple C++ targets `table_t`, not `mem_container` directly.**


## Key Stability Guarantees

Removing an element must **not change the keys of other elements**, including numeric keys.

Example:

```cpp
table_t t;
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

`table_t` operates in conjunction with `value_t`. All stored values are dynamic runtime values.

Invalid operations on retrieved values result in runtime errors, not compile-time errors.


## value_t chaining note

`value_t` provides `_find_val()` as the chained dynamic read helper.

Current rule:

- if `value_t` holds an owned or shared `table_t`, `_find_val()` forwards to that table
- if `value_t` is `null`, `_find_val()` returns `value_t(null_t{})`
- if `value_t` holds an expired weak table carrier, `_find_val()` currently returns `value_t(null_t{})`
- other receiver kinds fail at runtime

This keeps generator read lowering simple for patterns such as chained dynamic reads while leaving explicit presence-sensitive logic on raw `table_t::find()`.


## Nested table dim support

- Nested table dim reads chain through direct `operator[]` access so `$x["inner"][0]` follows the runtime `value_t` / `table_t` contract.
- Nested append on a table-valued slot is supported through chained `operator[]` plus `append(...)` on `value_t` / `table_t<value_t>`.
- Example: `x[0]["items"].append(1);`
- Table-valued assignments into table slots now use direct `value_t` assignment through the returned `operator[]` reference.


## Slot / value copy behavior

- Runtime `value_t` array carriers use shared table storage plus detach-on-write semantics.
- assigning a nested array element by value (for example `$copy = $x[0];`) may initially share the same table payload as the original slot.
- later writes through either value must detach shared table storage before mutation so PHP by-value behavior is preserved.
- by-value PHP `array` parameters use the same `value_t` ABI and rely on runtime detach-on-write instead of a generator-side `table_copy(...)` / `php::value_copy(...)` wrapper.
- explicit deep snapshot helpers may still be used when a truly eager owning copy is required, but they are no longer the default lowering for DIM reads or by-value array argument passing.


## Runtime helpers

- `php::count(const table_t<value_t>&)` is supported and returns the current logical element count of the PHP array wrapper.

## PHP target key semantics

When the runtime is compiled with `-DSCPP_LANGUAGE_TARGET_PHP=1`, `table_t<value_t>` normalizes decimal integer strings to integer keys at runtime through the table key path. This normalization must be shared by set/get/isset/unset/operator[] so behavior stays consistent. Append must continue from the current maximum integer key plus one.

