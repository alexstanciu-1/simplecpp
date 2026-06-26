# Fixed-Width Integer First Slice
Doc Status: planning

## Purpose

Capture the agreed first implementation slice for fixed-width integer source
types before runtime, S2S lowering, STAN, and tests are changed.

This is a planning note, not current semantic authority.

## Goals

- Add compact integer storage types for arrays, vectors, objects, and structured
  data where memory footprint matters.
- Keep `int` aligned with the same integer family rather than creating a second
  arithmetic model.
- Avoid broad operator-matrix expansion in the first slice.
- Keep `mixed` and `dynamic` out of this feature.

## Source Types

The first source names are:

- `int8`
- `int16`
- `int32`
- `int64`
- `uint8`
- `byte`
- `uint16`
- `uint32`
- `uint64`

`byte` is a pure alias of `uint8`. It should lower to the same runtime type and
should not have separate semantic identity in diagnostics or matrix data.

## Runtime Shape

Use one integer wrapper family:

```cpp
template <typename Rep = std::int64_t>
class int_t final;
```

`int` remains the default integer spelling and lowers to `int_t<>`, equivalent
to `int_t<std::int64_t>`.

Fixed-width names lower as:

| Source type | Runtime type |
| --- | --- |
| `int8` | `int_t<std::int8_t>` |
| `int16` | `int_t<std::int16_t>` |
| `int32` | `int_t<std::int32_t>` |
| `int64` | `int_t<std::int64_t>` |
| `uint8` | `int_t<std::uint8_t>` |
| `byte` | `int_t<std::uint8_t>` |
| `uint16` | `int_t<std::uint16_t>` |
| `uint32` | `int_t<std::uint32_t>` |
| `uint64` | `int_t<std::uint64_t>` |

Runtime code should use concepts or traits such as `is_int_t<T>` /
`semantic_int<T>` instead of exact `std::same_as<T, int_t>` checks where the
intent is "any semantic integer wrapper".

## First Supported Surface

The first slice should support:

- typed locals, parameters, returns, properties, and class fields
- typed containers such as `vector<int8>` and `vector<uint32>`
- construction from literals, with early range diagnostics where the literal is
  visible to the frontend or STAN
- same-type assignment and copy
- same-representation arithmetic and bitwise operators
- same-representation comparisons
- explicit casts among integer widths
- implicit widening to a higher-byte-count integer with the same signedness
- text conversion through the existing integer text route

## Conversion Rules

Allowed implicit conversions:

- signed integer to signed integer with a larger byte count
- unsigned integer to unsigned integer with a larger byte count
- fixed-width integer to default `int` when the conversion is signed-to-signed
  and widening

Rejected without explicit cast:

- narrowing conversions
- signed-to-unsigned conversions
- unsigned-to-signed conversions
- cross-representation arithmetic that does not have an allowed implicit
  same-signedness widening target

Examples:

```php
$a int8 = 1;
$b int16 = $a;       // allowed: signed widening

$u uint8 = 1;
$v uint16 = $u;      // allowed: unsigned widening

$bad uint16 = $a;    // rejected: signed to unsigned
$bad2 int8 = $b;     // rejected: narrowing
```

For mixed-width same-signedness arithmetic, the result type may be the smallest
allowed widening target that can represent both operands:

```php
$a int8 = 1;
$b int16 = 2;
$c int16 = $a + $b;
```

Signed/unsigned arithmetic requires an explicit cast:

```php
$a int16 = 1;
$b uint16 = 2;
$c int = (int)$a + (int)$b;
```

## Operator Semantics

All `int_t<Rep>` variants share one integer semantics policy. Today that policy
is C++-first native arithmetic, aligned with current `int_t` behavior. Existing
runtime guards such as division/modulo by zero remain in force.

For same-representation operators, the operation is evaluated through C++'s
usual native arithmetic behavior for the underlying representation and stored
back into `int_t<Rep>`.

Example lowering model:

```cpp
scpp::int_t<std::int8_t> c(
	static_cast<std::int8_t>(
		a.native_value() + b.native_value()
	)
);
```

If overflow/range rules are introduced later, they should apply consistently
across the whole integer wrapper family, including default `int`.

## Mixed And Dynamic

`mixed_t` and `dynamic_t` are not part of this feature's first slice.

The first implementation should not:

- add fixed-width integer runtime kinds to `mixed_t`
- preserve fixed-width identity through `mixed`
- add dynamic dispatch paths for fixed-width integers
- expand mixed/dynamic operator matrix rows for fixed-width integer types

## Operator Matrix Policy

Avoid concrete matrix expansion across all fixed widths.

The matrix should model this as one abstract integer wrapper family with
representation rules:

- same-representation operators are supported
- same-signedness widening is supported where the operation or assignment has a
  clear wider target
- signed/unsigned crossings are explicit-cast-only
- mixed/dynamic participation is absent

This keeps the first slice focused on compact storage and predictable static
typed code rather than a broad numeric promotion lattice.
