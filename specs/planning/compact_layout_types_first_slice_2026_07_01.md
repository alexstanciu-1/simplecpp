# Compact Layout Types First Slice
Doc Status: planning

## Purpose

Capture the first implementation slice for compact fixed-layout source types
needed by issue #212: inline structs, exact-width enum backing, and enough
layout discipline to support compact compiler parser rows.

This is a planning note, not current semantic authority.

## User-Facing Surface

Authored `.phs` and `.jss` files should use clean Simple C++ syntax. Parser
bridge forms such as generated doc comments are internal implementation
artifacts only and should not become the public authoring style.

Example PHS target:

```phs
struct CompactChildSpan {
	uint32 $first_child_index = 0;
	uint32 $child_count = 0;
}

enum ExpressionKind : uint16 {
	Error = 0,
	Variable = 1,
	IntLiteral = 2,
}
```

Example JSS target:

```js
struct CompactChildSpan {
	first_child_index: uint32 = 0;
	child_count: uint32 = 0;
}
```

## First-Slice Decisions

- `struct` and `class` remain separate source concepts.
- `class` keeps the current object/reference-oriented model.
- `struct` is a compact inline value-layout type.
- Struct fields are public by default.
- Explicit `public` may be accepted as equivalent to the default public field.
- Struct methods are out of scope for the first slice.
- Struct equality operators are out of scope for the first slice.
- Struct assignment is intended to be by-value copy, but implementation must
  audit the current object-default mapping before enabling broad assignment.
- `enum X : uint16` and similar fixed backing declarations mean exact generated
  storage width, not merely "values fit this range".
- `vector<StructName>` should be part of the first useful implementation path.

## Type Eligibility

The first slice should reuse the existing central type acceptance/mapping
helpers where they express scalar/container validity, but struct layout
eligibility needs an additional filter. The current class/object path defaults
unknown object-like declared types to `shared_p<T>`, which is wrong for inline
struct fields.

Initial struct field candidates:

- `bool`
- `int8`
- `int16`
- `int32`
- `int64`
- `uint8`
- `byte`
- `uint16`
- `uint32`
- `uint64`
- fixed-backed enums
- other first-slice structs

Open decision: whether `float` is admitted immediately. If admitted, it should
use the same layout and initialization rules as other scalar value fields.

Out of scope for first-slice struct fields:

- `string`
- `vector`
- `hash`
- `mixed`
- `dynamic`
- `class` object references
- nullable/reference/ownership wrappers
- union fields until the union slice lands

## Defaults And Initialization

Struct field initialization should follow the current class property initializer
subset where possible:

- explicit field initializers lower to in-class/default member initializers;
- omitted initializers use the generated C++ default for the field type;
- required-field and maybe-uninitialized STAN behavior should be audited before
  user-visible guarantees are promoted.

This reuses current class initializer behavior where it is already valid, while
keeping struct-specific type eligibility separate.

## Parser Bridge Direction

The S2S generator currently relies on PHP-AST, so unsupported PHP syntax such as
`struct`, `union`, and exact-width enum backing may need a pre-tokenizer bridge.

Preferred layering:

1. Authored `.phs` / `.jss` uses clean Simple C++ syntax.
2. JSS lowers clean JSS compact-layout syntax to clean PHS syntax.
3. PHS pre-tokenizer rewrites only the unsupported syntax into a temporary
   PHP-AST carrier form plus explicit metadata.
4. PHP-AST parses the carrier form.
5. IR builder reconstructs real `StructDecl`, later `UnionDecl`, and
   fixed-backed `EnumDecl` nodes.
6. STAN and S2S consume explicit IR nodes, not doc comments as semantics.

The parser bridge must preserve source locations well enough for diagnostics.

## Implementation Slices

## Implementation Progress

- Slice B has an initial implementation: project builds now create a
  declaration-kind catalog and feed it to S2S lowering.
- Cross-file enums are the first catalog proof case: enum fields from another
  source file lower as raw enum storage instead of `shared_p<T>`.
- Slice C has a first PHS parser bridge for clean `struct Name { ... }` syntax.
  The bridge rewrites to a PHP-AST-compatible carrier internally and marks the
  IR class declaration as a struct.
- Slice D has an initial simple-struct lowering path: public instance fields
  lower into C++ `struct` fields, and cross-file struct fields lower by value.
- Struct validation now rejects class-like features in the first slice:
  methods, constants, inheritance/interfaces, non-public fields, static fields,
  object/class fields, wrappers, `mixed`, and `dynamic`.
- `vector_t<StructName>`, `hash_t<StructName>`, and
  `fixed_array_t<StructName, N>` are covered as raw-struct container element
  paths.
- PHS `enum X : uint16` and sibling fixed-width integer backing types now use a
  pre-tokenizer carrier and lower to exact generated C++ enum storage.
- JSS has clean `struct` syntax that emits clean PHS `struct` syntax; internal
  parser bridge comments remain hidden from `.jss` and `.phs` authoring.

### Slice A: semantic/spec foundation

- Add a top-level normative spec for compact layout types.
- Promote implemented fixed-width integer aliases from planning/implementation
  evidence into current semantic authority.
- Specify `struct` as inline public value layout, distinct from `class`.
- Specify exact-width enum backing.
- Define the first-slice trivial layout type set.

### Slice B: declaration-kind catalog

- Extend frontend summaries from `is_enum` toward a general declaration kind:
  `class`, `enum`, `struct`, and later `union`.
- Reuse STAN's existing per-file summary cache and project-wide symbol/index
  machinery to build a declaration-kind catalog across all project files.
- Feed the declaration-kind catalog into S2S generation as narrow symbol
  metadata.
- Fix the existing cross-file enum lowering gap as the first proof case:
  a property declared as an enum from another source file must lower to the raw
  enum type, not `shared_p<EnumName>`.
- Keep unknown user types on the current fallback path so existing class-like
  code continues to lower as before.

This catalog is declaration metadata, not broad expression inference. S2S should
not ask STAN to type arbitrary expressions for generation.

### Slice C: parser and IR

- Add pre-tokenizer support for clean PHS `struct` syntax.
- Add exact-width enum backing carrier support.
- Extend IR with an explicit struct declaration kind.
- Preserve source locations through the carrier rewrite.

### Slice D: lowering and checks

- Add struct-aware type mapping so struct names lower inline instead of through
  `shared_p<T>`.
- Reuse class property initializer lowering where valid.
- Reject first-slice-ineligible struct field types early.
- Support struct fields inside classes and other structs.
- Support `vector<StructName>`.

### Slice E: fixed-backed enums

- Accept enum backing types such as `uint8`, `uint16`, `uint32`, and signed
  fixed-width equivalents.
- Emit the exact corresponding C++ enum storage type.
- Range-check literal enum case values against the declared backing type.

### Deferred Slices

- Restricted union support over trivial fixed-layout fields.
- Layout probe support for `sizeof`, `offsetof`, field size, and alignment.
- Compact parsed-expression acceptance fixture with enum discriminator, child
  span, and union payload.

## Known Implementation Cautions

- The current type mapper maps object-like declared types to `shared_p<T>` by
  default. Structs need declaration-kind awareness before type mapping.
- Existing enum lowering has the same project-scope problem today: enums known
  only from another source file are treated as object-like and can lower to
  `shared_p<EnumName>`. Fixing that with a declaration-kind catalog should
  precede struct lowering.
- Existing `value<T>` support is not a substitute for first-class `struct`
  semantics because the source goal is direct inline layout without wrapper
  spelling.
- Generator behavior should stay structural. The parser/IR layer should provide
  explicit declaration kinds so later passes do not infer structs from comments
  or naming conventions.
- Build `--no-stan` should not necessarily disable lightweight frontend symbol
  summaries needed for lowering. If declaration metadata is sourced from the
  STAN stack, separate "symbol catalog available to build" from "diagnostics
  block/advisory pass".
- The first implementation should validate with focused transpilation and small
  project builds before attempting the larger compiler-row migration.
