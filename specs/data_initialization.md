# Data notation and initialization interfaces
Doc Status: normative

Status: Notation and initialization-interface standards complete, 2026-10-05.
The compiler catalog tracks partial implementation; source-defined factories remain gated.

## Authority and version boundary

This is the Simple C++ language standard for data notation and opt-in construction,
not a compiler-local design. It supersedes older implicit literal-construction
defaults for v0.2. Existing v0.1 generator examples remain evidence of that version,
not permission to infer an unresolved v0.2 construction target as mixed.

See [the authority map](spec_map.md), [dynamic values](dynamic_types.md),
[array operations](array_semantics.md) and
[preparation diagnostics](v0_2_preparation_diagnostics.md).
Notation does not change indexing, iteration, copying, identity or reference safety.
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
recognized initialization interface. Property existence or a matching set of fields
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

Property-driven object initialization remains deferred until its compiler model is
available. In particular, this rule does not map a key in an outer construction to
an object property automatically. A factory accepting mixed values supplies no
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
schematic, not a complete executable function. This standard does not supply an
automatic constructor or map entry keys to fields.

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
| `dynamic` | `list_initializable<dynamic, mixed>` | `key_value_initializable<dynamic, mixed, mixed>` |

Other typed hash key families retain their existing key restrictions and require
their corresponding supported keyed binding; this slice does not enable new key
families. List construction of an integer-keyed hash, mixed table or dynamic object
assigns consecutive integer keys starting at zero. Dynamic list construction is
integer-keyed shared object storage, not conversion to a vector.

Selection rules:

- Nonempty positional input requires the list interface.
- Nonempty arrow-keyed input and all named-brace input require the keyed interface.
- Empty `[]` selects the list interface if available, otherwise the keyed interface
  with zero entries. A target supporting neither rejects during preparation.
- Empty `{}` always requires the keyed interface. Thus `vector<int>::{}` rejects,
  while `hash<int>::[]` is an empty hash construction.
- Empty mixed construction produces an empty table payload, not null. Empty dynamic
  construction produces a valid empty object, not an absent handle.

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

Both mixed container payloads and dynamic objects store entry values as **mixed
only**, with different payload kinds. There are not two alternative entry types
called mixed and dynamic.

- Supported scalar expressions are boxed into the corresponding mixed kinds.
- A nested mixed container is a mixed value.
- A nested dynamic object is carried in mixed's `dynamic_v` kind and preserves its
  shared identity.
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
redundant. An explicit nested annotation can select the other supported family;
the resulting entry still crosses the mixed-only storage boundary. This default
does not reinterpret scalars as dynamic objects or automatically convert existing
mixed values to dynamic. Key admission remains a separate type contract.

## 5. Compile-time and runtime responsibilities

For v0.2 the compiler resolves syntax, target identity, recognized interface and
required conversion paths. Unknown targets, missing interfaces, invalid structural
combinations and unsupported conversions fail during shared preparation. Runtime
construction is not permission to emit statically invalid code.

The runtime executes the factory and validates its data-dependent requirements.
Do not execute user factories at compile time or add content-based class inference,
schema validation or literal-data precomputation in this slice. Typed input boundaries
retain ordinary checked conversion rules, including runtime checks for mixed kinds.

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

The standard includes explicit user-defined class/struct opt-in. Connecting those
types to the protocol waits for their compiler interface/method support. Automatic
field matching and synthesized member initialization remain deferred, independently
of explicit factories. Dynamic `->` and reference iteration remain separate work.

The notation and interface documentation steps are complete. Concrete compiler
records, registration/binding implementation, bounded lifetime enforcement, runtime
adapters and C++ lowering remain implementation work, not unresolved source API.
Before enabling factories, prove ordering, conversions, duplicate handling, empty
states, failure cleanup, nested construction, non-escape rejection and incremental
contract invalidation. Moving/consuming input, function-valued entries, mixed-shape
bracket literals and automatic member initialization remain explicit deferred work.
The [compiler implementation note](../compiler/my-try/docs/planning/data_initialization.md)
records the hybrid lowering approach and performance proof boundaries.
