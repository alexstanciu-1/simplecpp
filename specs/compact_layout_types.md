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

The original compact-layout slice supported public instance fields only. The v0.2
PHP bootstrap compiler also supports [private instance fields](instance_methods_v0_2.md#php-bootstrap-compiler-coverage)
with the same layout and copy semantics.

Excluded from the original compact-layout slice:

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

The bootstrap compiler admits `float` and `float64` as aliases for the same instance field type.
Each field uses the native eight-byte `scpp::float64` representation and its target alignment.
Value initialization produces positive zero. Record copies preserve signed zero and copy the field independently.

### 2.4 Initialization

In the v0.2 PHP flavor, a typed local declaration such as `$p Point;` creates
a default-initialized inline struct value. An ordinary `int` field without an
initializer starts at zero and can be read immediately. Assignment such as
`$q = $p;` copies the value using the field semantics above.

All three flavors use `new Point()` for a shared allocation with result type
`shared<Point>`. C++ and JS require the parentheses. PHP also accepts `new Point`.
For example, `auto p = new Point();` (C++) and `let p = new Point();` (JS)
both create a managed owner. The payload uses the same recursive default initialization as
an inline value. Copying an owner keeps the same allocation alive. Rebinding a
local releases its old ownership. The final strong owner destroys the payload. Its allocation is freed once every weak observer has also released it.
A local declared `$p shared<Point>;` starts empty and allocates nothing.
Assigning `null` releases that variable's ownership. An empty owner is false in
conditions. For a shared struct owner and a null operand, `==` and `===` test
absence, and `!=` and `!==` test presence, in either operand order. Each operand
is evaluated once. For owners of the same canonical struct type, `==` and `===` both compare
allocation identity, including equality of two empty owners. Both inequality
operators give the inverse. Comparing different payload types is rejected. Reading or writing
a field through an empty owner throws `std::runtime_error("ownership_empty_access")`.

A by-value `shared<Point>` parameter owns its argument during the call. Writes
to the payload are visible to the caller; rebinding the parameter affects its
own handle. Returning an owner preserves the allocation across function exit.
Parameter and return payloads must match the exact nominal struct type.
A writable reference parameter such as `shared<Point> &$p` binds to a named
local or parameter with that exact owner type. Binding and forwarding the
reference do not retain the payload. Rebinding or clearing it changes the
caller’s slot. Passing one slot twice aliases that slot; passing two owner
variables that share a payload still binds two independent slots. Fields and
temporary expressions cannot bind to a writable owner reference.

The bootstrap compiler supports local owners, by-value parameters and returns,
and construction with by-value arguments and trailing literal defaults. Shared construction accepts
namespace-qualified names. Struct imports remain a separate lookup feature.

| Flavor | Inline local | Shared expression | Constructor inside the struct |
| --- | --- | --- | --- |
| PHP | `$p Point(1, 2);` | `new Point(1, 2)` | `public function __construct(int $x, int $y) { ... }` |
| C++ | `Point p(1, 2);` | `new Point(1, 2)` | `Point(int x, int y) { ... }` |
| JS | `let p: Point(1, 2);` | `new Point(1, 2)` | `constructor(x: int, y: int) { ... }` |

Each struct can declare one constructor, public by default. A private constructor
is callable by methods of its declaring struct, including static factories.
Inline construction, shared construction and implicit zero-argument initialization
all use this access rule. For an embedded inline field, the accessing struct is
the one declaring the field. Constructor parameters use ordinary
function argument conversions and evaluation order. Its body may assign to
fields of the fresh receiver. A typed declaration without parentheses and an
inline struct field both require a constructor callable with zero arguments when a constructor
is declared. Every omitted parameter takes its prepared literal default. A required parameter
makes either default use invalid. A shared field still starts empty. Copying an existing
inline value uses memberwise copy and does not call the source constructor.
If initialization throws, constructed fields and argument temporaries are
destroyed; a failed shared construction also releases the allocation.

For this first slice, PHP and C++ flavors use `->` for value-field reads and
writes, and the JS flavor uses `.`. A value-field access lowers to native C++
`p.x`. The receiver's resolved type determines its storage.

Constructors, free functions and ordinary methods may accept an inline struct by value.
Free functions and ordinary methods may also return one by value. A parameter
requires the same nominal record type. Copying preserves independent inline fields and the
allocation identity of shared fields. A method may return a different record from its receiver.
Writable-reference parameters for inline records remain deferred in the bootstrap compiler.

The active PHP bootstrap compiler admits integer (`int`/`int64` and `byte`/`uint8`), boolean, float64 and string instance fields, inline structs and
`shared<T>` fields targeting a nominal struct. An inline field default-initializes
recursively. A written instance-field default must be a scalar literal accepted by the field's typed
destination conversion. Validate defaults even on unused records. Initialize each new inline or
shared record before its constructor body; a record copy takes the current field values.
A writable string field supports string append (PHP `.=`, C++/JS `+=`) and checked byte-index
assignment with the same conversion and bounds rules as a local. A string field without a default starts empty. Its native storage is an ordinary `scpp::string`; record copying and destruction preserve string value semantics, including embedded NUL bytes. A boolean field starts false. A byte field starts at zero and uses ordinary uint8 value semantics:
incrementing 255 wraps to zero, while adding an int64 literal with `+=` checks the narrowed result
before storing it. A shared field defaults to an empty, pointer-sized owner. Copying the
enclosing value copies each field: inline data is independent, and each copied
nonempty owner retains the same allocation. Clearing a field releases just that
ownership. Destruction of the final owner also destroys the owners in its payload.

The inline layout graph must be acyclic, including across source units. A shared
field needs the target's identity and may refer back to its containing struct,
as in `struct Node { public shared<Node> $next; }`. A runtime cycle of strong
references must be broken before reference counting can reclaim it. A field
path rooted in a writable local uses the same assignment rules at every depth.

| Source flavor | Declaration | Default-initialized local | Field write |
| --- | --- | --- | --- |
| PHP | `struct Point { public int $x; }` | `$p Point;` | `$p->x = 3;` |
| C++ | `struct Point { int x; };` | `Point p;` | `p->x = 3;` |
| JS | `struct Point { x: int; }` | `let p: Point;` | `p.x = 3;` |

The earlier construction slice supports only no-argument struct construction. Positional arguments, named
arguments, and argument unpacking must produce a source diagnostic, rather than
selecting aggregate positional initialization or class construction. Custom
constructors are not introduced by that original slice. The approved
[data-record contract](data_initialization.md#31a-explicit-data-classes-and-structs--2026-10-07)
extends the language contract to authored/synthesized struct construction in parity
with classes; its compiler implementation remains separately tracked in Chapter 10.
The v0.2
[record-storage amendment](type_use_modifiers.md#v02-bounded-owned-construction--2026-10-06)
additionally admits explicit shared/unique allocation at direct typed local
initializers and weak observation of shared struct allocations. The PHP allocation spelling follows the rule above.
An explicit `value<Struct>` remains redundant declaration storage.

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

### 3.0 PHP bootstrap compiler: integer enums

The active `v0.2/compiler/` accepts the following common nominal enum model:

| Flavor | Declaration | Case reference |
| --- | --- | --- |
| PHP | `enum Kind { case First = 1; case Large = 300; }` | `Kind::Large` |
| C++ | `enum Kind { First = 1, Large = 300 };` | `Kind::Large` |
| JS | `enum Kind { First = 1, Large = 300 }` | `Kind.Large` |

Every case needs an integer literal, optionally signed; decimal, `0x` and `0o`
spellings follow the ordinary literal rules. Empty enums, duplicate case names
and duplicate numeric values are errors. An enum occupies the same nominal type
namespace as a struct; conflicting declarations are errors. Namespace-qualified
references and plain `use` imports select that exact type.

Omitted backing means an integer-backed enum, not a unit enum. Omitted backing
and explicit `: int` expose `int64` through `enum_value` and select the smallest
native representation that covers **both the minimum and maximum case values**.
The number of cases does not determine storage:

- `{1, 300}` needs `uint16`; `{1, 65536}` needs `uint32`.
- `{-1, 200}` needs `int16`; `{-1, 2147483648}` needs `int64`.
- Nonnegative ranges use `uint8`, `uint16`, `uint32`, then `int64` in this slice;
  negative ranges use `int8`, `int16`, `int32`, then `int64`.

A written fixed backing (`: int64`, `: uint8`, `: byte`, `: int8`, `: int16`,
`: int32`, `: uint16`, `: uint32`) is exact, even when the values could fit a
smaller representation. Its availability follows the runtime module rules;
implicit compact storage does not require activating source numeric widths.
For example `enum Kind : uint8` lowers with backing `scpp::uint8`. Values outside
the chosen fixed backing are errors. Source values remain limited to signed
int64 literals; uint64 backing and its larger constant domain are deferred.

Enums support initialized locals, inference from a case/value, same-type copies,
by-value callable parameters and returns, and the equality rules below.
`enum_value(value)` returns the declared fixed backing, or `int64` for omitted
backing/`: int`. It extracts the numeric value explicitly; ordinary assignment
does not convert enums to integers or integers to enums. Enum truthiness and
arithmetic are rejected. Case values are immutable.

The native representation is a scoped C++ `enum class`, with the selected
underlying scalar alias and no wrapper allocation. The source keyword is still
`enum`. Scoping prevents case-name leakage and unwanted native integer coercion.

This bootstrap slice does not yet implement unit enums or enum globals,
nullable/result enum payloads or the selection forms described by the broader
language contract below.

#### String-backed enums in the PHP bootstrap compiler

An explicit `: string` backing accepts string literal cases in all three flavors.
PHP uses `case Name = "bytes";`; C++/JS use `Name = "bytes"` comma-separated entries.
Backing values are unique decoded byte sequences. Empty strings, embedded NUL and
UTF-8 bytes are supported; no numeric coercion or Unicode normalization occurs.
For example `"0"` and `"00"` are distinct values. Mixed integer/string cases reject.

The compiler stores a nominal compact integer tag, assigned in declaration order,
not a string object. It chooses the smallest available tag representation that
covers every ordinal. Tags are an implementation detail, not an exposed numeric
backing or stable serialization format. Equality and copying preserve nominal
identity. Existing explicit case field/parameter defaults apply unchanged.

`enum_value(value)` returns the original string bytes. `enum_name(value)` returns
the case name, independently of its backing bytes. For this backing,
`enum_from_value(Enum::class, string)` (JS: `Enum.class`) checks byte-exact membership
and returns the nominal case or throws. Only string inputs are admitted; other scalar,
boxed and nullable inputs require a separate boundary. No implicit enum/string
conversion or reflection API is introduced.

#### Public instance enum methods

Integer- and string-backed enums may declare public instance methods. PHP uses
`public function label(): string { return enum_name($this); }`; C++ uses
`string label() { return enum_name(this); }`; JS uses
`label(): string { return enum_name(this); }`. C++/JS may terminate the case list
with a semicolon before method declarations. PHP cases retain their semicolons.
Calls use `->` in PHP/C++ and `.` in JS, as with struct instance methods.

Methods reuse ordinary function parameter/return rules, generated conversions,
trailing defaults and exact-type named writable reference arguments. `this` is a
by-value copy of the selected enum case, not an object or writable reference to
the caller's slot. Returning/copying it preserves nominal identity. Replacing the
caller's enum through a reference parameter does not change the selected receiver.
The selected receiver is preserved before effectful argument evaluation. The
existing declaring-type spellings `self::` (PHP), `Self::` (C++) and `class.` (JS)
resolve enum cases in the declaring enum. Calls on another same-typed value and
calls through `this` use the normal method lookup.

Enums do not acquire instance fields, constructors, object identity or allocation.
Static/private/protected enum methods and enum interface/inheritance contracts are
not part of this slice. Public method names occupy the method index, independently
of the case-name index. Erroneous duplicate methods use normal declaration
publication and duplicate diagnostics. Type-qualified instance calls without a
receiver reject.

#### Enum instance fields in the PHP bootstrap compiler

Instance fields may use an enum type with an explicit case default of that exact
nominal type. The default is required even when a constructor later writes the field;
there is no implicit zero or first-case initialization.

| Flavor | Example inside a struct |
| --- | --- |
| PHP | `public Kind $kind = Kind::First;` |
| C++ | `Kind kind = Kind::First;` |
| JS | `kind: Kind = Kind.First;` |

The declared type and case resolve in the struct's source context, including plain
type imports. Integer literals, another enum's cases and executable expressions are
not enum field defaults. Every default is checked even if the struct is unused.
New inline and shared instances initialize the field before the constructor body.
Nested inline records use their own defaults. Copying a record takes the current
enum field value, without rerunning its default; subsequent edits to either copy
are independent. Shared-owner copies still refer to the same record.

Field reads and assignments use the existing access rules and exact enum identity.
This does not enable enum arithmetic, numeric assignment, enum static properties
or enum globals. Ordinary parameter defaults are covered below.

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

The active PHP bootstrap compiler implements these defaults for functions and
ordinary instance/static struct methods in all three flavors:

| Flavor | Function signature |
| --- | --- |
| PHP | `function pick(Kind $kind = Kind::First): Kind` |
| C++ | `Kind pick(Kind kind = Kind::First)` |
| JS | `function pick(kind: Kind = Kind.First): Kind` |

Constructors also accept these trailing by-value enum case defaults. Inline and
shared construction use the same default argument path. A constructor with defaults
for every parameter can initialize a nested inline field without explicit arguments;
its generated zero-argument entry delegates using the prepared defaults. Written
field defaults run before the constructor body, and copying a record preserves its
current field values without rerunning construction. Existing scalar constructor
defaults and the constructor's by-value parameter restriction are unchanged.

The default is checked when its signature is prepared,
including unused functions/methods. Calls with an explicit argument do not select
the default, but an invalid default still makes the declaration invalid.

### 3.4 v0.2 enum case names

The compiler-provided `enum_name(value)` accepts exactly one enum argument and
returns its original, unqualified case name as a `string`, preserving spelling and
case. The helper contract covers unit, int-backed, fixed-integer-backed and string-backed enums;
the bootstrap currently implements the integer and string-backed forms.
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

### 3.5 v0.2 explicit backing-value enum helpers

`enum_value(value)` accepts one enum value. String-backed enums follow the byte
contract above. For explicitly fixed-backed integer enums,
the result has the declared backing type, including full-width `uint64`. For plain
`int`-backed and unit enums it is `int`, independently of compact storage selection.
Unit enum values are zero-based declaration ordinals; reordering cases changes
those ordinals, so use explicit backing values for stable external encodings.

`enum_from_value(Enum::class, value)` (PHP/C++) or
`enum_from_value(Enum.class, value)` (JS) returns that exact enum type or throws if
the input does not identify a declared case. Its first argument is a compile-time
type marker resolved through ordinary type lookup, including imports; it is not
a runtime string or reflection object. Integer-backed enums accept any supported
integer width/signedness; string-backed enums require a string as specified above.
Integer comparison preserves the original mathematical value:
out-of-range values must not truncate or wrap into a valid case. Checking exact
membership before any narrowing also establishes representability.

Both helpers evaluate their runtime argument once and follow ordinary callable
lookup/shadowing, like `enum_name`. Wrong arity, non-enum targets, missing type
markers and inputs of the wrong backing family reject during preparation.
For integer backing, float, bool, string, mixed and dynamic inputs require an
explicit prior integer boundary; these helpers do not introduce implicit coercion. Well-typed backing
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

The byte identity/restriction below is superseded by the [2026-11-06 scalar model](compiler_scalars_v0_2.md#agreed-family-and-representation-model--2026-11-06): byte aliases uint8 with ordinary integer behavior. Canonical identity and representation are implemented; grouped numeric behavior remains pending. Module ownership remains.

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

Agreed 2026-10-10; integer promotion revised 2026-11-06. For `+`, `-`, `*`, `/`, `%`,
identical canonical integer types retain their type. Different signed representations
select int64; different unsigned representations select uint64. A signed integer and
an unsigned representation below 64 bits select int64. Signed + uint64 arithmetic
is rejected because neither domain covers both operands. These rules use complete
representation domains, never the particular value of an operand or literal.
`byte` aliases uint8, and `int` aliases int64. Ordinary integer literals remain int64.

Examples: `byte + byte -> uint8`, `byte + 1 -> int64`, `int32 + int16 -> int64`,
`uint16 + uint32 -> uint64`. This replaces the older blanket mixed-signedness
restriction. Operator promotion does not authorize cross-type ordinary assignment.

Integer `==`, `!=`, `<`, `<=`, `>` and `>=` compare exact values and return bool,
including signed/unsigned pairs. They do not require a common arithmetic domain;
in particular, comparing signed values with uint64 must not convert negative values
to unsigned or round through floating point. The current compiler activates int64
and uint8; wider representations remain separate activation work.

Arithmetic returns the selected integer identity. Narrow unsigned results preserve
the representation before an enclosing operation consumes them, despite host C++
integral promotions. Existing native unsigned reduction and signed-overflow limitations
remain; this decision adds no checked/saturating arithmetic. Compound write-back is
a separate policy: the active int64/uint8 compound slice uses checked result conversion
back to the destination, preserving same-type arithmetic. See [compound write-back](compiler_scalars_v0_2.md#integer-compound-write-back--2026-11-06).

Spaceship remains separately bounded; this slice does not implement it.

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

Implementation checkpoint 2026-11-06: the active v0.2 compiler implements the
int64/uint8 arithmetic and comparison subset through generated handlers and the
shared runtime integer-domain policy. Historical my-try behavior is reference
material, not the active implementation of this revision. Native helpers accept
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
