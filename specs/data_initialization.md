# Data notation and initialization interfaces
Doc Status: normative

Status: Notation and initialization-interface standards complete, 2026-10-05;
explicit data-record construction amendment approved 2026-10-07.
The compiler catalog tracks partial implementation; source-defined factories remain gated.
The [Chapter 10 checkpoint](../compiler/my-try/docs/catalog/10_objects.md#authored-constructors--2026-10-07)
implements bounded synthesized and authored record construction, property defaults,
and contextual `set:` parameters. Source-written sequence factories remain gated.

## Authority and version boundary

This is the Simple C++ language standard for data notation and opt-in construction,
not a compiler-local design. It supersedes older implicit literal-construction
defaults for v0.2. Existing v0.1 generator examples remain evidence of that version,
not permission to infer an unresolved v0.2 construction target as mixed.

See [the authority map](spec_map.md), [dynamic values](dynamic_types.md),
[array operations](array_semantics.md) and
[preparation diagnostics](v0_2_preparation_diagnostics.md).
Element assignment and append share this standard's value admission and contextual
nested-target rules; see [writes](array_semantics.md#22-write-path). This is an explicit
v0.2 container-element contract, not permission for implicit cross-family boxing.
Notation does not otherwise change indexing, iteration, copying, identity or reference safety.
The compiler [chapter 06](../compiler/my-try/docs/catalog/06_containers.md)
tracks implementation separately; examples here are contracts, not support claims.

## 1. Notation

| Form | Input shape |
| --- | --- |
| `[10, 20, 30,]` | PHP-like: ordered positional values |
| `["alice" => 10, "bob" => 20,]` | PHP-like: ordered key/value entries; keys may be expressions |
| `{alice: 10, "bob": 20,}` | JS-like: ordered named entries; quoted or bare string keys |

Both keyed forms select the same keyed initialization protocol. A bare property
name is not variable lookup or field resolution. Quote names that cannot be written
as ordinary identifiers. Quoted and bare names may coexist in a brace construction.
Bracket-keyed entries require `=>`; brace-keyed entries require `:`. Reject
`["key": value]` and `{"key" => value}`. Braces require named entries, not positional
values or computed property-key expressions. Bare names normalize to literal strings;
quoted property names use literal source strings, not interpolated expressions.

Values are expressions, including nested constructions. One trailing comma after
the final entry is optional in every form. Omitted elements, repeated separators,
spread, property shorthand and method definitions are not introduced by this standard.
`[]` is an empty bracket initializer whose target protocol must be resolved;
`{}` is an empty keyed initializer. Empty input still requires a known target type.

Braces introduce data construction in expression positions and remain blocks in
statement positions. Parenthesize a standalone brace expression to disambiguate it
from a block. There are only two representation categories: PHP-like and JS-like.
Quoted-key braces do not introduce a third representation. There is no JSON-validity
classification or check; source strings retain the source language's string rules.

### Shared syntax model

One parsing algorithm and one construction-node family handle both representations.
Each ordered entry owns an optional key expression and a required value expression;
an explicit null key is distinct from an absent key. The construction may also own
an explicit target type. Parsing preserves duplicate entries without selecting a
container, factory or runtime storage strategy.

Representation is derived on demand from the opening source token, not retained in
a duplicate flag. In my-try, `source_representation(token_list)` supplies that query
using the caller's retained token snapshot, including after span moves/compaction.
Separator spelling is enforced during parsing and remains recoverable from source;
generic semantic stages consume the normalized entries rather than branching on
PHP-like versus JS-like spelling. Semantically deferred mixed positional/keyed input
may be represented faithfully by syntax; it must not acquire preparation support.

### Function-valued entries — deferred

The notation is intended to accept function references and function expressions as
values when their callable and storage contracts are supported. Implementation is
deferred: unsupported function-valued entries must receive a preparation diagnostic,
not be silently converted or ignored. This standard does not introduce a new closure
syntax, method shorthand, automatic binding of a receiver, or new mixed callable kind.

## 2. Construction type must be known

The compiler must identify the target type and its applicable initialization
interface before lowering construction. It may obtain that type from an explicit
typed destination, an applicable interface input type, or explicit construction
notation. It must not guess a class by matching property names. If the target cannot
be identified unambiguously, compilation fails; there is no silent mixed/dynamic
fallback based only on bracket or brace spelling.

Explicit construction notation is `Type::[...]` or `Type::{...}`:

```php
$items vector<int> = [10, 20];
$scores hash<int> = {alice: 10, bob: 20};
$standalone = vector<int>::[10, 20];
$data = dynamic::{name: "Alex"};
```

The annotation names the constructed type, not a cast of an already-built mixed
container. An outer destination conversion, if needed, remains an ordinary separate
conversion. A known type must also support the selected input shape.

### Concrete targets and property contexts

A resolved property's declared type may supply the construction target, just like
a typed local, provided it identifies one concrete type with the applicable
recognized initialization interface or explicit `data` record contract. Property existence or a matching set of fields
does not grant initialization support.

An interface-typed or otherwise non-concrete destination does not select an
implementation for an unannotated construction. Reject during preparation when no
concrete target is established; do not guess from entry names, try alternative
implementations against the entries, or choose the only implementation currently
known to the compiler. The author must identify the concrete construction type:

```php
// settings is declared as an interface, not a concrete implementation:
$holder->settings = {enabled: true};                   // compile-time error
$holder->settings = ConcreteSettings::{enabled: true}; // explicit construction target
```

The explicit type must itself support the selected initialization interface, and
the resulting value must separately satisfy the property's assignment contract.
This restriction concerns choosing a construction target, not assigning an existing
value of a compatible concrete type to an interface-typed destination.

Property-driven object initialization remains gated until its compiler model is
available. The explicit `data` contract below establishes typed construction inputs;
ordinary homogeneous factories do not map keys to properties automatically.
A factory accepting mixed values supplies no
per-property target information merely because a property with that name exists.
The examples describe that future contract, not current property/interface support.

### Nested construction contexts

User-defined examples below require the deferred class/struct interface connection:

```php
$user User = {
    name: "Alex",
    address: Address::{city: "Bucharest"},
};
```

If an interface already supplies `Address` as the nested construction target,
the annotation can be omitted. An interface accepting mixed values does not reveal
that a key named `address` denotes an `Address` object. The compiler does not inspect
factory bodies or member names to invent that association.

Uncontextualized `$x = [];` or `$x = {name: "Alex"};` does not acquire a type merely
from the old v0.1 dynamic default. Use an explicit target when no applicable typing
context establishes one.

## 3. Recognized opt-in interfaces

Construction has two separate language-recognized interface roles:

- Positional initialization declares its element type and constructs the target
  from an ordered sequence of those values.
- Keyed initialization declares its key and value types and constructs the target
  from an ordered sequence of key/value entries.

A type may implement either role or both. Participation is explicit interface
opt-in, not duck typing or detection of a specially named method. The factory owns
how input becomes a completed instance of its target type. Implementing this role
does not automatically grant indexing, mutation, iteration or native references.

### 3.1 Interface declarations

The recognized interfaces and factory signatures are:

```php
interface list_initializable<TSelf, TValue> {
    public static function _from_list(
        initializer_list<TValue> $values
    ): TSelf;
}

interface key_value_initializable<TSelf, TValue, TKey = string> {
    public static function _from_key_value(
        initializer_list<initializer_entry<TValue, TKey>> $entries
    ): TSelf;
}
```

These are standard declarations for the v0.2 language API, not a claim that the
current parser accepts generic interface declarations. `TSelf` must denote the exact
construction target, including template arguments; it is not an arbitrary return
type. `TValue` names the input value type. Keyed parameters put value before key,
matching `hash<TValue, TKey>`; omitted `TKey` means `string`.

The compiler checks a public static factory against the selected interface binding.
Factories are direct static calls, not virtual instance dispatch. This recognized
static requirement extends the older instance-only interface model; it does not
silently enable arbitrary static-abstract interfaces. Binding must be unambiguous;
the compiler must not pick an input type by trying the literal's values against
multiple conflicting implementations.

A user implementation declares a concrete return type, for example:

```php
class User implements key_value_initializable<User, mixed> {
    public static function _from_key_value(
        initializer_list<initializer_entry<mixed, string>> $entries
    ): User {
        // Validate entries, construct an instance, and return that User.
        // Factory body intentionally omitted in this declaration example.
    }
}
```

The factory returns the completed target under its ordinary representation rules
(a class handle or a struct value as appropriate), not an implicit `result<TSelf>`
or nullable wrapper. Factory validation failures throw. The example body above is
schematic, not a complete executable function. These homogeneous sequence interfaces
do not supply automatic constructors or map keys to fields. The explicit `data`
record contract below is a separate opt-in, not implicit interface detection.

### 3.1A Explicit data classes and structs — 2026-10-07

`data class` and `data struct` opt into keyed construction through a typed
construction contract:

```php
data struct Settings {
    string $title = "Untitled";
    int $retries = 3;
    vector<string> $tags;
}
$settings Settings = {retries: 5};
```

`data` grants data construction only: not serialization, equality, immutability,
reflection, positional construction or different storage/copy semantics. Class
handles and struct values retain their ordinary representation. The modifier belongs
to the common type-declaration syntax, not a separate class/struct AST family.

With an authored constructor, keys name its parameters, not its properties. Parameter
names are consequently part of the data-construction API. For example:

```php
data class User {
    string $name;
    public function __construct(string $first, string $last) {
        $this->name = $first . " " . $last;
    }
}
$user User = {first: "Alex", last: "Smith"};
// {name: "Alex Smith"} is invalid: there is no parameter named name.
```

Construction invokes that constructor, including its validation and assignments;
it must not bypass its body by assigning properties afterward. PHP-style constructor
property promotion is deferred to Chapter 15; property-backed parameters below are
adopted for the first authored-constructor slice. Ordinary required
parameters remain required even when their types have defaults; optional parameters
use their declared defaults. Property defaults and parameter defaults are distinct:
property initialization precedes an authored constructor body, while parameter
defaults supply omitted arguments. This contract applies to both classes and structs;
it amends the earlier no-authored-struct-constructor restriction. The current bounded
implementation and remaining gates are recorded in Chapter 10.

#### Property-backed constructor parameters

Approved 2026-10-07 for classes and structs, independently of `data` opt-in:

```php
data class User {
    public string $name;
    private int $age = 18;

    public function __construct(set:$name, set:$age = 21) {
    }
}
$user User = {name: "Alex", age: 30};
```

Each comma-separated `set:$property` denotes a constructor parameter backed by an
already declared instance property. `set` is contextual in this parameter prefix,
not a globally reserved keyword; an ordinary parameter whose type is named `set`
remains distinguishable by the absence of `:`. This spelling replaces the earlier
`use $property` proposal; the two are not alternative accepted spellings.

The parameter obtains the property's type and initializes it directly before the constructor body, without
first initializing and overwriting its default. It does not declare a new property,
capture a closure variable, establish a reference/alias or change property visibility.
Default precedence is: an explicit parameter default, otherwise the property's
explicit default, otherwise a required argument. The type's implicit default does
not make the parameter optional. In the example, `name` is required and omitted
`age` uses `21`; spelling only `set:$age` would inherit `18`. A parameter-side
default override does not change the property's declaration default. Defaults must
satisfy the resolved property type and the supported constructor-default subset.

Property-backed parameters may coexist with ordinary typed parameters. Inside the
body, `$name` denotes the parameter and `$this->name` the initialized property; they
are not aliases. Normal value/handle copy semantics still apply. A public constructor
may explicitly accept a parameter initializing a private property: `age` is then a
valid construction key, but direct external property access remains forbidden.
An authored private/protected constructor is usable only from its authorized source
contexts, exactly as with ordinary `new`; keyed data notation does not bypass access.
Inherited constructors preserve the original visibility and declaring owner. See
[constructor visibility](inheritance_v0_2.md#constructor-visibility).

Only public instance properties participate in a synthesized constructor when no
authored constructor exists. Excluded non-public instance properties must still have
an explicit or established type default; otherwise an authored constructor is required.
Static properties never become instance-construction inputs. The bounded implementation
now supports public/private/protected instance properties; only public properties
participate in synthesized data inputs. Static members remain excluded from inputs.

The first authored-constructor slice adopts this spelling, not PHP-style promotion
such as `__construct(public string $name)`. See the
[non-blocking promotion debt](../compiler/my-try/docs/catalog/15_blocking_debt.md#ch15-ctor-syntax-01--deferred-constructor-property-promotion).
Parameter ordering, validity and initialization use the common constructor model;
inheritance and parameter-side overrides of property types are not introduced by this
agreement. Implementation availability remains tracked in Chapter 10.

Without an authored constructor, the compiler synthesizes typed construction inputs
from public instance properties in declaration order. Each supplied entry initializes
its corresponding property. For an omitted property, use its explicit default first,
then its established type default, otherwise require the input. An absent class handle
does not allocate a pointee. No implicit enum case or other invented default is allowed.
Non-public properties are excluded from synthesized inputs, not silently exposed.
Static members are excluded from inputs. Derived data initialization selects either
the effective authored constructor or the composed synthesized contract below. Public instance
properties contributed by the supported direct-trait slice participate through the
same property contract; composition must not expose non-public properties or bypass
construction invariants.

#### Derived records with authored constructor contracts — 2026-10-08

A derived class/struct must explicitly opt in with `data`; the modifier is not
inherited. Its effective constructor may be authored on the child or inherited
from an ancestor, which need not itself be `data`. Keys always name that selected
constructor's parameters. Inherited parameter types, names and defaults keep their
original declaration/lexical context. Extra public properties do not add implicit keys.

```php
class Base {
    int $number;
    function __construct(int $input = 7) { $this->number = $input + 1; }
}
data class Child extends Base {
    int $extra = 1;
}
$child Child = {input: 8}; // number == 9; extra == 1
// {number: 8} and {extra: 8} are invalid: neither names a constructor parameter.
```

Ordinary inherited-constructor eligibility and child defaults apply. An authored
child constructor runs normal base initialization before child initialization/body;
data notation never follows it with property assignments. Supplied inputs retain
source evaluation order, followed by omitted parameter defaults. The existing
duplicate/unknown/missing-key, type-boundary and storage rules remain unchanged.
Both record kinds reuse one construction path. Without an effective authored
constructor, the synthesized contract below applies. See [inheritance](inheritance_v0_2.md).

#### Synthesized inherited-property inputs — 2026-10-08

Every participating derived type explicitly opts in with `data`. Its immediate base
must also be `data` with a synthesized contract; reject synthesis through a non-data
base even if that base has a default constructor. An effective authored constructor
always takes precedence, including an inherited one; its parameter contract is not
augmented with child-property inputs.

```php
data class Base { public string $name; }
data class Child extends Base { public int $age = 18; }
$child Child = {name: "Alex", age: 25};
```

Compose base inputs first, followed by local public instance properties in declaration
order. Private/protected/static properties remain excluded and keep their ordinary
initialization. Inherited types and defaults retain their original lexical declaration
context. Existing same-category redeclaration restrictions prevent ambiguous inherited
keys. Duplicate/unknown keys and missing required values reject during preparation.

Evaluate supplied entries in source order, then omitted defaults in base-first input
order. Construct the base subobject directly through its synthesized contract before
initializing child members: no default-then-assignment, temporary complete base copy,
or bypass of access/constructor rules. Supplied base inputs suppress the corresponding
defaults. Multi-level composition repeats the same rule. Required base inputs do not
prevent declaring the child, but do prevent ordinary default construction unless all
base/member defaults are available. Source member inventories remain local.

The initial supported key contract is literal string names, in either `{name: value}`
or `["name" => value]` notation. Computed keys and positional record construction are
deferred. Reject unknown/duplicate keys, missing required inputs, incompatible types
and ambiguous nested targets during preparation. Each input has its own expected type;
concrete property/parameter types supply nested construction targets. This is not a
`key_value_initializable<Self, mixed>` bag and does not require boxing record inputs.

Evaluate supplied expressions once in source order, even when keys differ from
constructor/property declaration order. Then evaluate omitted parameter defaults in
parameter order (public-property order for synthesized inputs). For authored
construction, property initialization follows declaration order before the body;
`set:` inputs directly initialize their selected properties. A supplied property value replaces
its default directly; do not first construct and discard the default. Defaults are
per-instance: value containers are independent under their normal value/COW behavior,
and mutable default objects must not become shared global singleton instances.
Publish no partially initialized instance on failure; already executed source effects
are not rolled back.

Property defaults use ordinary typed expression/conversion rules. The agreed bounded
scope is scalar literals, supported constant expressions, permitted null values and
typed data notation for containers/eligible nested records. Arbitrary calls, `$this`
and sibling-property references in default expressions remain deferred. These default
rules also apply to ordinary class/struct construction, independently of `data` opt-in.

### Default-based property type inference — v0.2, 2026-10-07

Class/struct instance and static properties may omit their type when an admitted
default establishes one concrete type: `public $count = 0;` declares a fixed `int`
property. Preparation infers from the default's semantic type, including supported
constant references and enum cases, not from generated C++ or later assignments.
Explicit property types retain their normal assignment-conversion rules.

No default, bare `null`, and unqualified data literals such as `[]` or `{x: 1}`
require an explicit property type. An explicitly targeted data literal such as
`vector<int>::[1, 2]` supplies its own type where construction defaults are allowed.
Inference does not relax initializer restrictions: static properties still exclude
data constructions, and arbitrary calls/instance reads remain deferred. Inferred
properties participate normally in synthesized `data` construction and `set:` inputs.

The inferred type is declaration-owned. A default dependency changing type
invalidates layout and dependent semantics; a value-only change follows ordinary
default invalidation. Unsupported or ambiguous defaults reject even on unused types.

Preparation owns the authored or synthesized semantic construction contract, including
input names, types, defaults and dependencies. Lowering emits its concrete constructor
and calls from those facts; it must not discover the contract or perform source
validation. Parsing and collection remain generic. Reuse the existing construction
syntax and sequencing machinery with distinct homogeneous-sequence and typed-record
contracts. Source-derived contracts belong to completed declarations, not registry
caches. Combining `data` with an explicit keyed factory remains gated until selection
precedence is specified; do not silently select a competing construction path.

### 3.2 Input sequence and entry API

Two standard types provide input:

- `initializer_list<T>`: an ordered, read-only, call-scoped sequence.
- `initializer_entry<TValue, TKey = string>`: an immutable value record with readable
  fields `key: TKey` and `value: TValue`.

`initializer_list<T>` exposes `count(): int` and `at(int $index): T`, and supports
value iteration in input order. `at` rejects negative/out-of-range indices by throwing.
Reads return values, never native references to backing storage. Keyed initialization
uses `initializer_list<initializer_entry<TValue, TKey>>`; there is no separate
`initializer_entries` container. The first v0.2 input domain requires copyable
element/key/value types; move-only or consuming input is deferred.

The compiler materializes typed input without first constructing another vector/hash
through this same protocol. Typed input does not require mixed boxing unless the
selected interface declares mixed. The opaque sequence representation and allocation
strategy are backend concerns, not source-level native views or pointers.

Keyed input preserves source order and duplicate entries until the receiver handles
them. Do not first materialize a hash that discards duplicates. Duplicate keys at
the same construction level are errors; the receiving type determines key equality
and normalization, not whether duplicates may be silently overwritten.
Absent positional keys are not encoded using a null-key sentinel. Supporting an
explicit null key, if any, is a separate receiving-key contract.

A heterogeneous user factory may explicitly accept string keys and mixed values.
It then validates required keys and value kinds at runtime and returns its instance.
Only values admitted by the established boxing contract can enter that mixed input;
this does not introduce arbitrary heterogeneous unboxable argument packs.

### 3.3 Call-scoped ownership and lifetime

Initializer backing belongs to the construction call and stays alive throughout the
synchronous factory invocation. The input is not an owning, retainable container.
The factory may read/iterate it and copy element values into its result or other
owning storage. Copied values retain their ordinary semantics: copying a dynamic
handle preserves shared identity, not deep immutability.

The input sequence must not escape through a return, field/global store, enclosing
container, escaping closure, or unsafe forwarding. Copying/aliasing the sequence does
not extend its backing lifetime. No source operation exposes backing pointers or
native element references. Keeping an element value obtained by `at` is permitted;
keeping the input view itself after the call is not. Suspension or asynchronous
retention of the view is not admitted.

These restrictions must be enforced by general semantic lifetime/capability checks
before user-defined factories are enabled. Forwarding is permitted only where its
non-escape can be established; unsupported paths must reject rather than rely on
programmer discipline or Clang diagnostics. This is a bounded call-scoped input
contract, not authorization for arbitrary borrowed spans or a general borrow checker.

Empty input needs no backing allocation. Small inputs may use local temporary
storage; large inputs may use heap backing to avoid excessive stack use. No fixed
threshold or zero-allocation/zero-copy guarantee is part of the API. Factories
populate the destination directly; they may reserve capacity using `count()`.
Temporary elements may still be copied into destination storage. A factory that
needs persistent input must explicitly copy values into ordinary owning storage.

### 3.4 Built-in bindings and input shapes

| Target | List interface | Keyed interface |
| --- | --- | --- |
| `vector<T>` | `list_initializable<vector<T>, T>` | None |
| `hash<V, string>` | None | `key_value_initializable<hash<V, string>, V, string>` |
| `hash<V, int>` | `list_initializable<hash<V, int>, V>` | `key_value_initializable<hash<V, int>, V, int>` |
| `mixed` | `list_initializable<mixed, mixed>` | `key_value_initializable<mixed, mixed, mixed>` |
| `dynamic` | `list_initializable<dynamic, dynamic>` | `key_value_initializable<dynamic, dynamic, mixed>` |

Other typed hash key families retain their existing key restrictions and require
their corresponding supported keyed binding; this slice does not enable new key
families. List construction of an integer-keyed hash, mixed table or dynamic object
assigns consecutive integer keys starting at zero. Dynamic list construction is
integer-keyed shared object storage, not conversion to a vector.

Selection rules for homogeneous sequence interfaces (the `data` record contract
above separately admits keyed record inputs):

- Nonempty positional input requires the list interface.
- Nonempty arrow-keyed input and all named-brace input require the keyed interface.
- Empty `[]` selects the list interface if available, otherwise the keyed interface
  with zero entries. A target supporting neither rejects during preparation.
- Empty `{}` always requires the keyed interface. Thus `vector<int>::{}` rejects,
  while `hash<int>::[]` is an empty hash construction.
- Empty mixed construction produces an empty table payload, not null. Empty dynamic
  construction produces a valid empty container, not null.

Keyed factories process entries in source order and must reject duplicate keys at
the same construction level. This applies to built-ins and opted-in user-defined
factories. The same key in separate nested objects/tables is valid. This v0.2 rule
supersedes the earlier last-value-wins proposal; ordinary assignments that update an
existing key are unchanged. For v0.2, validation occurs at runtime after materializing
the typed input sequence. All entry expressions have therefore run before a factory
reports a duplicate; completed source side effects are not rolled back. A failed
construction does not publish a partial result or replace the assignment destination.

Duplicates are determined after the receiver's established key conversion and
normalization. Typed hashes do not implicitly equate integer and string keys. The
existing PHP-target dynamic key normalization remains in effect: for example `"10"`
normalizes to integer `10`, while `"08"` remains a string. See the
[runtime key contract](../runtime/specs/spec.md) and [hash contract](../runtime/specs/hash_t.md).
Dynamic keys must resolve to admitted integer/string kinds; a mixed-typed input key
does not permit arbitrary payload kinds. Statically invalid combinations reject;
unknown mixed kinds retain runtime validation. Null is not a positional sentinel.

Combining positional and keyed entries in one bracket literal, such as
`[10, "name" => "Alex", 20]`, is deferred in the v0.2 implementation and must reject
explicitly. This does not retroactively prohibit the existing v0.1 form.

## 4. Mixed and dynamic nesting and storage

The [v0.2 separated-family contract](dynamic_types.md#v02-separated-mixeddynamic-families--2026-10-05)
supersedes the earlier mixed-only entry proposal. Implementation is still migrating.

- Mixed containers store mixed values, including scalar/null leaves and mixed children.
- Dynamic containers store dynamic values, including scalar/null leaves and dynamic
  children. Scalar boxing does not allocate a shared object for each number/bool.
- Nested dynamic containers retain shared identity; nested mixed containers retain
  value/COW semantics. Neither family's entries may contain the other family's container.
- Arbitrary objects are not automatically boxed or converted into property bags.

Construction type and stored entry type are separate concepts. For nested,
unannotated data constructions, a mixed construction supplies the default target
`mixed`; a dynamic construction supplies `dynamic`, recursively. This contextual
rule makes the target known at compile time; it is not runtime type inference.

```php
$data dynamic = {
    name: "Alex",
    settings: {enabled: true},
};
$other mixed = ["settings" => ["enabled" => true]];
```

Here `settings` is constructed as dynamic in the first example, and as mixed in
the second. `settings: dynamic::{enabled: true}` in the first is allowed but
redundant. An explicit annotation selecting the other container family rejects; it
does not authorize a conversion. Existing values from the other family require an
explicit scalar/null conversion, checked at runtime when their kind is unknown;
container payloads reject. Key admission remains separate: the mixed key type in
the dynamic keyed interface admits only the established integer/string key kinds,
not mixed entry values.

## 5. Compile-time and runtime responsibilities

For v0.2 the compiler resolves syntax, target identity, recognized interface and
required conversion paths. Unknown targets, missing interfaces, invalid structural
combinations and unsupported conversions fail during shared preparation. Runtime
construction is not permission to emit statically invalid code.

The runtime executes the factory and validates its data-dependent requirements.
Do not execute user factories at compile time or add content-based class inference,
schema validation or literal-data precomputation in this slice. Typed input boundaries
retain ordinary checked conversion rules, including runtime checks for mixed kinds.
For explicit `data` records, checking literal keys against a prepared constructor
signature is ordinary static call validation, not execution of a user schema/factory.
Their duplicate/unknown/missing-input diagnostics therefore occur during preparation.

Evaluate entries exactly once in source order, including key before value for a
keyed entry. Complete nested construction before supplying that value to its parent.
Publish the completed construction result only on success. Failure does not imply
rollback of side effects already performed by expressions or user factory code.
Independent evaluations must not accidentally share a mutable constructed instance;
copying an existing dynamic handle still preserves its documented identity.

Compiler pre-preparation is a [v0.3 investigation](../docs/v0.3/literal_construction_preparation.md),
not a prerequisite for runtime-based v0.2 construction.

### General stages and registry contracts

Parsing preserves generic notation, ordered entries and optional explicit target
syntax. Collection registers ordinary declarations and interface implementations.
Preparation resolves targets, interface bindings, conversions, copy permission and
non-escape validity through general contracts, without container-name dispatch or
allocation algorithms. C++ S2S consumes settled facts and owns temporary storage,
sequencing, representation and direct-call spelling. Runtime factories own actual
construction and data validation. Lifetime errors do not move into C++ emission.

For built-ins, type registry definitions expose identities and declared contracts.
For source types, completed declaration facts supply their interfaces/capabilities
through the same general semantic query path, with dependency tracking; the registry
must not duplicate source-derived facts. Keep semantic capabilities, storage
representation and transfer/lifetime rules separate. Neither a collection label nor
a fat/reference-like representation automatically grants an initialization interface.
Passing a parameter by reference is distinct from a type's shared-handle representation.

## 6. Delivery and deferred work

Vector, hash, mixed and dynamic must be designed through the common protocol from
the start. Cover those families together per operation, with runtime kind checks
where mixed leaves the actual container kind unknown. Not every mixed value is a
container. Construction support alone does not implement iteration or access.

The standard includes explicit user-defined class/struct interface opt-in and the
approved `data` record contract above. Source factories wait for interface/method and
input-view lifetime support; synthesized record construction has its own Chapter 10
implementation checkpoint. Neither is implied implemented by this standard.
Dynamic `->` and reference iteration remain separate work.

The notation and interface documentation steps are complete. Concrete compiler
records, registration/binding implementation, bounded lifetime enforcement, runtime
adapters and C++ lowering remain implementation work, not unresolved source API.
Before enabling factories, prove ordering, conversions, duplicate handling, empty
states, failure cleanup, nested construction, non-escape rejection and incremental
contract invalidation. Moving/consuming sequence input, function-valued entries and
mixed-shape bracket literals remain explicit deferred work.
The [compiler implementation note](../compiler/my-try/docs/planning/data_initialization.md)
records the hybrid lowering approach and performance proof boundaries.
