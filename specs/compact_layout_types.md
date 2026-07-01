# Compact Layout Types
Doc Status: normative

Status: Active
Purpose: define current Simple C++ compact-layout source semantics for first-slice value structs, restricted payload unions, exact-backed enums, and layout probes.

This document is top-level language authority for compact layout behavior. It
does not define unrestricted C++ layout control, packed structs, arbitrary
unions, or ABI promises outside the supported source surface below.

## 1. Source Forms

### 1.1 PHS Structs

PHS supports clean `struct` declarations:

```phs
struct CompactChildSpan {
	uint32 $first_child_index = 0;
	uint16 $child_count = 0;
}
```

Struct declarations are source-level value-layout declarations. They are not
class declarations with different spelling.

Struct fields are public by default. Explicit `public` is accepted as equivalent
to the default public field.

The implementation may internally bridge unsupported parser syntax through
temporary carrier forms, but authored `.phs` files should use the clean
`struct` form above.

### 1.2 JSS Structs

JSS supports clean struct declarations and lowers them to clean PHS syntax:

```js
struct CompactChildSpan {
	first_child_index: uint32 = 0;
	child_count: uint16 = 0;
}
```

JSS output must not expose internal parser bridge comments or carrier metadata.

### 1.3 PHS Unions

PHS supports clean restricted `union` declarations:

```phs
union ExpressionPayload {
	uint32 $int_value;
	CompactChildSpan $span;
}
```

Union declarations are source-level value-layout declarations. They are not
class declarations with different spelling.

Union fields are public by default. Explicit `public` is accepted as equivalent
to the default public field.

The implementation may internally bridge unsupported parser syntax through
temporary carrier forms, but authored `.phs` files should use the clean
`union` form above.

### 1.4 JSS Unions

JSS supports clean restricted union declarations and lowers them to clean PHS
syntax:

```js
union ExpressionPayload {
	int_value: uint32;
	span: CompactChildSpan;
}
```

JSS output must not expose internal parser bridge comments or carrier metadata.

### 1.5 Fixed-Backed Enums

PHS supports fixed integer enum backing:

```phs
enum ExpressionKind : uint16 {
	case Error = 0;
	case Variable = 1;
}
```

The declared backing type is exact generated storage width. It is not merely a
range hint.

Current fixed backing names are:

- `int8`
- `int16`
- `int32`
- `int64`
- `uint8`
- `byte`
- `uint16`
- `uint32`
- `uint64`

## 2. Struct Semantics

### 2.1 Value Storage

Struct-typed fields lower as inline value storage, not object handles. A field
declared as `CompactChildSpan` stores a `CompactChildSpan` value.

Classes remain object/reference-oriented in the current model. A field declared
as an ordinary class type continues to lower through the current class/object
storage rules.

### 2.2 First-Slice Struct Members

The first slice supports public instance fields only.

Rejected in current structs:

- methods
- constants
- inheritance
- implemented interfaces
- private or protected fields
- static fields
- class/object fields
- ownership/reference wrappers
- `mixed`
- `dynamic`
- nullable fields
- unrestricted union fields

### 2.3 First-Slice Field Types

Current struct fields may use:

- `bool`
- fixed-width integer aliases: `int8`, `int16`, `int32`, `int64`,
  `uint8`, `byte`, `uint16`, `uint32`, `uint64`
- fixed-backed enums
- other first-slice structs
- `vector_t<StructName>` / `vector<StructName>`
- `hash_t<StructName>` / `hash<StructName>`
- `fixed_array_t<StructName, N>` / `fixed_array<StructName, N>`

`float` is not promoted by this spec in the current first slice.

### 2.4 Initialization

Explicit struct field initializers lower to generated member initializers.
Omitted initializers use the generated C++ default for the field type.

Required-field and maybe-uninitialized guarantees remain subject to STAN and
generator diagnostics; they are not broadened by this first-slice spec.

## 3. Enum Semantics

Fixed-backed enum case values must be literal integer values that fit the
declared backing type.

Generated C++ enum storage must match the source backing exactly. For example,
`enum X : uint16` lowers to storage equivalent to `std::uint16_t`.

Unit enums and existing int-backed behavior continue under their current rules.

## 4. Union Semantics

Restricted payload unions lower as generated C++ `union` declarations.

### 4.1 First-Slice Union Members

The first slice supports public instance payload fields only.

Rejected in current unions:

- methods
- constants
- inheritance
- implemented interfaces
- private or protected fields
- static fields
- field default initializers
- class/object fields
- ownership/reference wrappers
- `mixed`
- `dynamic`
- nullable fields
- container fields
- nested union fields

### 4.2 First-Slice Payload Types

Current union payload fields may use:

- `bool`
- fixed-width integer aliases: `int8`, `int16`, `int32`, `int64`,
  `uint8`, `byte`, `uint16`, `uint32`, `uint64`
- fixed-backed enums
- first-slice structs

Union fields intentionally omit default initializers. Source code that needs a
tagged payload should store the tag separately, typically as a fixed-backed
enum field in an enclosing first-slice struct.

## 5. Project Composition

Within a project, declaration-kind metadata distinguishes `class`, `enum`,
`struct`, and `union` declarations for lowering. This metadata is limited
declaration metadata, not general expression type inference.

Cross-file enum, struct, and union fields must lower according to their declaration
kind:

- enum fields lower as raw enum storage;
- struct fields lower as raw value storage;
- union fields lower as raw union value storage;
- ordinary class fields continue to use the current object/reference-oriented
  storage rules.

Generated project force-include headers must order generated headers so by-value
struct, union, and enum uses see complete definitions before use.

## 6. Layout Probes

Layout probes expose generated C++ layout facts as integer expressions.

Current probe functions:

- `layout_sizeof(TypeName)`
- `layout_alignof(TypeName)`
- `layout_offsetof(TypeName, field_name)`
- `layout_field_sizeof(TypeName, field_name)`

The type argument may be a bare type name or `TypeName::class`. Field arguments
are bare field names.

Probe results are `int`/`int_t<>` values in the current source model.

Layout probes are compile-time generated-layout observations. They are intended
for tests, assertions, diagnostics, and compact-row validation inside generated
Simple C++ projects. They are not a portable external ABI promise.

## 7. Deferred Compact Layout Features

The following are not current first-slice semantics:

- explicit packing controls
- user-authored alignment attributes
- ABI compatibility guarantees outside generated Simple C++ projects
- broad reflection APIs beyond the layout-probe slice
