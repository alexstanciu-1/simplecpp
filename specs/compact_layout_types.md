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

### 2.2 Struct Members

The first slice supports public instance fields only.

Rejected in current structs:

- methods
- constants
- inheritance
- implemented interfaces
- private or protected fields
- static fields
- explicit ownership/reference wrappers (ordinary class fields use their existing shared-handle storage)
- `mixed`
- `dynamic`
- nullable fields
- unrestricted union fields

### 2.3 Field Types

Current struct fields may use:

- `bool`
- `string` (stored as `string_t`)
- fixed-width integer aliases: `int8`, `int16`, `int32`, `int64`,
  `uint8`, `byte`, `uint16`, `uint32`, `uint64`
- fixed-backed enums
- other first-slice structs
- ordinary class types (stored as `shared_p<ClassName>`)
- restricted unions conforming to section 4
- `vector_t<T>` / `vector<T>`
- `hash_t<T>` / `hash<T>`, including the existing explicit key-type form `hash<T, T_KEY>`
- `fixed_array_t<T, N>` / `fixed_array<T, N>`

Container element/value type `T` follows this same field-type rule recursively.
Existing hash-key and fixed-array size restrictions still apply. `mixed` and
`dynamic` remain excluded, including inside nested containers. Explicit ownership
wrappers and nullable fields are not added by this extension.

For example:

```phs
class Some_Custom_Class {
	public string $name = "";
}

struct my_struct {
	string $my_string;
	Some_Custom_Class $my_property;
	public $strings vector<string>;
	public $objects hash_t<Some_Custom_Class>;
}
```

Copying a struct copies each field using its existing value/ownership semantics:
strings have independent values, ordinary class fields share the same object,
and containers copy their elements (including shared handles). Moving and
destruction use the existing member lifetimes. Structs do not acquire implicit
deep object cloning, raw-byte serialization, or shared-ownership cycle collection.

For the compiler-provided memberwise operations, a struct satisfies
`copyable_value` exactly when every field type satisfies that contract; an empty
struct satisfies it. A valid value-storable layout does not itself grant copying.
Field capabilities do not automatically provide struct comparison or hashing
operations. This capability rule does not expand the field types admitted by this
specification or introduce implicit ownership transfer.

`float` is not promoted by this spec in the current first slice.

### 2.4 Initialization

`new StructName()` constructs a struct value, including in inferred locals,
typed destinations, arguments, and returns. It does not allocate a shared object
or use the class construction helper. Conceptually, it lowers to `StructName{}`.
Declaration-kind metadata determines this behavior for same-file, cross-file,
qualified, and imported names alike. Ordinary `new ClassName()` retains shared
object ownership.

Only no-argument struct construction is supported. Positional arguments, named
arguments, and argument unpacking must produce a source diagnostic, rather than
selecting aggregate positional initialization or class construction. Custom
constructors are not introduced by that original slice. The approved
[data-record contract](data_initialization.md#31a-explicit-data-classes-and-structs--2026-10-07)
extends the language contract to authored/synthesized struct construction in parity
with classes; its compiler implementation remains separately tracked in Chapter 10.
The v0.2
[record-storage amendment](type_use_modifiers.md#v02-bounded-owned-construction--2026-10-06)
additionally admits explicit shared/unique allocation at direct typed local
initializers and weak observation of shared struct allocations. Bare `new Struct()`
still creates a value; `value<Struct>` is rejected as redundant declaration storage.

Explicit struct field initializers lower to generated member initializers.
Omitted initializers use the generated C++ default for the field type.
Strings default to empty, vector/hash fields to empty containers, and ordinary
class fields to absent shared handles. Reading required fields and accessing
absent objects remain subject to the existing STAN and runtime rules.

Typed struct locals may use keyed array initializer sugar:

```php
$row Row = ["x" => 10, "y" => 20];
```

This lowers as a value struct initializer, not as a dynamic array/table. When
the struct declaration is available to the generator, fields are emitted in
declaration order so generated C++ designated initialization remains valid even
if source keys appear in a different order. Nested keyed initializers are
supported for fields whose declared type is another first-slice struct.

Required-field and maybe-uninitialized guarantees remain subject to STAN and
generator diagnostics; they are not broadened by this first-slice spec.

## 3. Enum Semantics

Fixed-backed enum case values must be literal integer values that fit the
declared backing type.

Generated C++ enum storage must match the source backing exactly. For example,
`enum X : uint16` lowers to storage equivalent to `std::uint16_t`.

Unit enums and existing int-backed behavior continue under their current rules.

### 3.1 v0.2 equality and selection

Enum equality (`==`, `===`) and inequality (`!=`, `!==`) require operands of the
same nominal enum type. Both equality spellings compare case identity; inequality
negates it. Comparing an enum with an integer or another enum type is a preparation
error, even if their backing values agree. Ordering and arithmetic are not admitted.

`switch` and `match` accept enum selectors with constant case references from that
same enum, including qualified/imported references. The selector is evaluated once.
Backing storage does not introduce an implicit integer conversion or permit integer
labels. This bounded slice does not admit runtime-valued labels.

`switch` preserves fallthrough and break behavior, rejects duplicate case labels,
and may omit default (an unmatched selector continues after the switch). `match`
selects the first matching label and evaluates only that result; repeated labels
preserve first-match behavior. Its optional default is considered after all labels,
regardless of source position. Without a matching label or default, it throws an
unhandled-match exception. Multiple defaults reject in either construct.

Exhaustiveness analysis is deferred; covering every enum case does not establish
total return coverage for a default-less switch in the current bounded compiler.
This selection extension does not expand supported match result types or define
enum default initialization, metadata helpers or conversion helpers.

### 3.2 v0.2 enum-valued selection results

Full ternary (`condition ? left : right`) and `match` may produce an enum value
when every result alternative has the same nominal enum type. Different enums,
enum/integer combinations and bare-null alternatives do not establish this result
type. All alternatives are checked during preparation, including unselected ones.

The result is an owned value copy, not a reference to branch storage. Only the
selected result expression executes. Ordinary typed value arguments and returns
accept that result; it cannot initialize a storage reference.

Result admission is independent of condition truthiness and selector admission.
This extension does not admit enum conditions or enum Elvis operands. The bounded
nullable/Result value boundaries and exact-enum `??` fallback are specified below.

### 3.2.1 v0.2 nullable and Result enum payloads

Unit, int-backed and fixed-integer-backed enums may be payloads of `nullable<E>`
and all three Result families: `result<E>`, `result_or_false<E>` and
`result_or_bool<E>`. `?E` denotes exactly the same nullable type as `nullable<E>`;
it does not erase absence or lower to a bare enum. Values preserve the exact
nominal enum identity and its backing representation. Integers and another enum's
cases do not implicitly initialize or replace the payload.

Nullable values contain either absence or an enum case. Result values contain
either an enum case or a genuine structured error. Falseable Results contain a
case or a false sentinel; boolean-sentinel Results contain a case, a false sentinel
or a true sentinel. A zero-backed case is a
successful payload. Nullable default initialization is empty; this does not add
implicit bare-enum or Result default initialization. Required storage and existing
explicit-initializer rules remain in force.

Ordinary exact-type initialization, assignment, by-value parameters, returns and
same-wrapper copying reuse the existing wrapper boundaries. Required extraction
checks the state and throws on absence or failure. Existing-output `take` preserves
the output on failure, copies the case on success, and clears the structured error
output after successful Result extraction. Its source is evaluated and snapshotted
once before write-back. Both existing take spellings have the same behavior.

`nullable<E> ?? E`, `result<E> ?? E` and `result_or_false<E> ?? E` return the exact enum value; the fallback
is evaluated only for a non-value state. There is no integer fallback conversion
or cross-enum join. Same-wrapper fallback and other selection extensions are not
added here. `result_or_bool` remains rejected on either side of `??`. Enum
truthiness, wrapper conditions and lifted equality/arithmetic remain separate.

Sentinel construction uses the existing explicit `false_sentinel` / `true_sentinel`
spelling. Bare bool literals or variables do not initialize non-bool enum payloads.
Existing null-to-false-sentinel conversion remains available. Families do not
implicitly convert into one another; structured errors do not initialize sentinel
families. Required extraction throws for either boolean sentinel, including true.

For `result_or_bool<E>`, value-state `take` copies the enum and leaves the boolean
output unchanged. A sentinel leaves the enum output unchanged, assigns the boolean
output and returns that sentinel's boolean value. Therefore a true return does not
by itself establish that an enum was extracted. For `result_or_false<E>`, failed
`take` preserves the enum output and returns false. Sources remain unconsumed.

`isset` observes state independently of payload extraction: enum payloads and the
true sentinel are present; false sentinels, absence and structured errors are not.
Neither probe copies nor consumes the source. This does not grant wrapper conditions
or emptiness. Default-prelowered helper and plain-wrapper presence proofs are
recorded in `compiler/my-try/docs/planning/enum_helper_transport.md` and
`compiler/my-try/docs/planning/enum_sentinel_carriers.md`. Plain and keyed enum
presence is admitted by the [2026-10-25 probe amendment](count_empty_isset_contract.md#v02-enum-presence--2026-10-25);
it preserves lazy lookup and does not grant enum conditions or emptiness.

### 3.3 v0.2 explicit enum parameter defaults

An ordinary by-value enum parameter may name a case of that exact enum as its
default, including qualified/imported case references. Omitted arguments receive
that value; explicit arguments retain normal typed-boundary rules. The default
resolves in the declaration's lexical scope, not the caller's scope.

Integer values, another enum's cases, null and computed expressions are not admitted
as enum parameter defaults. Existing optional-parameter ordering rules apply;
reference-parameter defaults remain deferred. This does not establish an implicit
enum default: local enum storage still requires explicit initialization, without
silently choosing zero or the first declared case.

### 3.4 v0.2 enum case names

The compiler-provided `enum_name(value)` accepts exactly one enum argument and
returns its original, unqualified case name as a `string`, preserving spelling and
case. Unit enums, int-backed enums and fixed-integer-backed enums are supported.
The argument is evaluated exactly once. Wrong arity and non-enum arguments are
preparation errors; no string/integer coercion selects an enum implicitly.

This is an ordinary callable name under the existing lexical lookup/import rules,
not reserved syntax. A source function or imported source-function alias can shadow
it. Its language-scope definition is compiler-provided, not an optional runtime
module registration. Analysis selects the concrete enum signature and observes its
declaration metadata before generation. An invalid enum value introduced through
native code throws at runtime rather than inventing a case name.

This helper does not enable numeric conversion, PHP enum reflection methods,
implicit enum defaults or user-defined generic functions.

### 3.5 v0.2 explicit numeric enum helpers

`enum_value(value)` accepts one enum value. For explicitly fixed-backed enums,
the result has the declared backing type, including full-width `uint64`. For plain
`int`-backed and unit enums it is `int`, independently of compact storage selection.
Unit enum values are zero-based declaration ordinals; reordering cases changes
those ordinals, so use explicit backing values for stable external encodings.

`enum_from_value(Enum::class, value)` returns that exact enum type or throws if
the integer does not identify a declared case. Its first argument is a compile-time
type marker resolved through ordinary type lookup, including imports; it is not
a runtime string or reflection object. The second argument may have any supported
integer width/signedness. Comparison preserves the original mathematical value:
out-of-range values must not truncate or wrap into a valid case. Checking exact
membership before any narrowing also establishes representability.

Both helpers evaluate their runtime argument once and follow ordinary callable
lookup/shadowing, like `enum_name`. Wrong arity, non-enum targets, missing type
markers and noninteger reverse-conversion inputs reject during preparation.
Float, bool, string, mixed and dynamic inputs require an explicit prior integer
boundary; these helpers do not introduce implicit coercion. Well-typed integer
inputs without a matching case throw at runtime; this slice adds no constant-call
evaluation. No nullable/Result alternative, general reflection or implicit
enum/integer conversion is introduced.

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

Struct payload eligibility is recursive: strings, ordinary class handles, and
containers are forbidden anywhere inside a union payload, including through
nested structs. Supporting these fields in ordinary structs does not add managed
union lifetimes or an active-member discriminator.

Union fields intentionally omit default initializers. Source code that needs a
tagged payload should store the tag separately, typically as a fixed-backed
enum field in an enclosing first-slice struct.

### 4.3 v0.2 restricted union construction and replacement

No-argument construction creates union storage with no active payload member.
A whole-member assignment evaluates and converts its RHS into a value snapshot,
then constructs that member in the union storage. This also permits replacing the
currently active member; eligible payloads have no managed destruction obligation.
An RHS failure does not perform the replacement. Existing receiver evaluation and
source assignment rules remain in force.

Copying a restricted union copies its active payload with value semantics. A
struct payload must be installed as a whole before accessing its nested fields.
Reads, nested mutations and references require the corresponding member to be
active and initialized. There is no implicit discriminator, inactive-member read
guarantee, runtime tag check or new active-member flow analysis. Applications
maintain their separate tag where required.

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

Current lowering limitation: declaration-kind metadata alone does not provide
the field schema for every cross-file literal assignment. When assigning a
container literal to a struct declared in another file, construct a typed
container local first and assign that value to the field. This extension does
not add general cross-file expression type inference.

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

The v0.2 queries use ordinary callable lookup and may be shadowed by source
functions. Their type and field arguments are compile-time names, not evaluated
values or general reflection objects. Both type spellings use ordinary lexical
type lookup, including imports. Field queries select a direct public instance
field; strings, computed field names and static fields are outside this slice.
Offset queries cover public-field layouts without inheritance or polymorphism;
wider record-layout queries remain separate. Sizes and alignments come from the
target C++ compiler, not from the compiler host or fixed padding assumptions.
This does not introduce source constant-call evaluation.

## v0.2 byte and numeric-module amendment — 2026-10-30

For the PHP-first v0.2 rebuild, `byte` is a distinct unsigned eight-bit core type,
not an alias of uint8. Core retains int64 and float64; additional integer widths
require the optional numeric module and explicit demand. Byte admits explicit
checked integer conversions, bitwise operations, shifts by int64 counts in 0..7,
and same-type equality. It does not participate in ordinary integer arithmetic
or implicit numeric promotion. Use an explicit integer conversion first.

This supersedes byte-alias statements below for the new v0.2 implementation;
legacy implementations remain migration donors. Full runtime admission and
publication details: [core byte and numeric module](../v0.2/specs/numeric_module.md).
Compiler/PRJ enforcement and byte string helpers remain separate future slices.

## 7. v0.2 integer arithmetic and numeric comparisons

Agreed 2026-10-10; promotion revised 2026-10-27. For `+`, `-`, `*`, `/`, `%`,
`==`, `!=`, `<`, `<=`, `>` and `>=`, integer operands must have the same signedness.
Identical integer types retain their type; different widths select the family
maximum (int64 for signed, uint64 for unsigned) before computation; `byte` is the `uint8` alias. Canonical `int` is signed 64-bit
and wins the result identity when either operand is canonical `int`.

Arithmetic returns the selected integer type; comparisons return `bool`.
Spaceship (`<=>`) uses the same operand selection but returns canonical `int`
with value -1, 0 or 1; it must compare, not subtract.

Examples: `int32 + int64 -> int64`, `uint16 + uint32 -> uint64`,
`int32 + int -> int`. Signed/unsigned combinations are preparation errors,
including `uint32 + 1`, because ordinary integer literals have type `int`.
An explicit conversion, such as `uint32 + (uint32)1`, establishes a common
signedness; there is no value-dependent literal exception.

Evaluation and conversions follow [ordered eager evaluation](expression_evaluation.md).
Division/remainder retain existing zero-divisor runtime errors and native integer
behavior. No new checked overflow, saturation or universal wrapping guarantee is
introduced; native signed-overflow limitations remain. Assignment, argument and
return conversions are unchanged by this amendment.

For the ordinary arithmetic/comparison group above, admitted integer/float pairs
select float64; different admitted floating widths select float64 and identical
floating types retain their type. Integer remainder remains integer-only. This rule
does not introduce new float16/float32 types. Documented integer-to-floating precision
loss is permitted; bool, null and numeric-looking strings do not gain admission.
Explicit casts establish operand types first and retain their own checked rules.

Implementation checkpoint 2026-10-27: the runtime public metadata resolver and
normalized arithmetic/comparison functions implement this revised policy. The
existing my-try frontend still selects the wider integer operand and has narrower
floating admission; frontend migration is tracked separately. Native helpers accept
already-normalized operands; their internal widening templates do not own source
promotion. Specialized operators, including spaceship, require their separately
admitted domains and are not implemented by this public slice.

This slice does not extend fixed-width unary/mutation/compound operators, bitwise
operators, shifts, strict identity or [power](integer_power.md).
The my-try frontend keeps their existing admission rules. It selects domains from
registered integer width/signedness facts; backend representation does not decide
source promotion. Remaining signed/unsigned, overflow and literal ergonomics are
[CH15 review debt](../compiler/my-try/docs/catalog/15_blocking_debt.md#ch15-integer-domains-01--integer-interoperability-and-overflow).

### 7.1 Checked integer shift counts

Agreed 2026-10-20. For `<<`, `>>`, `<<=` and `>>=`, a count must be
nonnegative and strictly less than the declared left operand's bit width
(64 for canonical `int`). Invalid counts throw `ValueError`; they are not
masked, saturated or passed to an undefined native shift. C++ integer promotion
does not widen the accepted count range for narrow integer representations.

Existing operand admission, result types and valid-count behavior remain unchanged.
Boxed operands must first satisfy their integer-kind requirements; lifted operands
retain left-before-right checked extraction and error precedence. Ordered source
evaluation and snapshots still apply. A failed compound shift does not write its
result back to the destination. This runtime contract does not expand frontend
admission for fixed-width, boxed or indexed compound forms.

## 8. Deferred Compact Layout Features

The following are not current first-slice semantics:

- explicit packing controls
- user-authored alignment attributes
- ABI compatibility guarantees outside generated Simple C++ projects
- broad reflection APIs beyond the layout-probe slice

### 7.2 Explicit boolean-to-integer casts

An explicit boolean cast to any supported fixed-width integer representation yields
exactly zero or one. This is the same value rule as the canonical int64 destination;
it does not admit booleans to arithmetic. Checked fixed-width casts from boxed
payloads follow [the original payload type](dynamic_types.md#v02-boxed-fixed-width-integer-casts--2026-10-27).
