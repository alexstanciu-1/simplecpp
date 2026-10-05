# Data notation and initialization interfaces
Doc Status: normative

Status: Agreed v0.2 contract, 2026-10-05; implementation pending.

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
| `[10, 20, 30,]` | Ordered positional values |
| `["alice" => 10, "bob" => 20,]` | Ordered key/value entries; keys may be expressions |
| `{alice: 10, "bob": 20,}` | Ordered named entries; bare names are literal string keys |

Both keyed forms select the same keyed initialization protocol. A bare property
name is not variable lookup or field resolution. Quote names that cannot be written
as ordinary identifiers. Quoted and bare names may coexist in a brace construction.

Values are expressions, including nested constructions. One trailing comma after
the final entry is optional in every form. Omitted elements, repeated separators,
spread, property shorthand and method definitions are not introduced by this standard.
`[]` is an empty bracket initializer whose target protocol must be resolved;
`{}` is an empty keyed initializer. Empty input still requires a known target type.

Braces introduce data construction in expression positions and remain blocks in
statement positions. Parenthesize a standalone brace expression to disambiguate it
from a block. This is JSON-like / JavaScript-like source notation, not a new JSON
text format or a commitment to JavaScript semantics. JSON decoding retains its own
grammar; source strings retain the source language's string rules.

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

`_from_list` and `_from_key_value` are the agreed conceptual factory roles. Final
interface identifiers, exact source declarations, generic binding rules and physical
input representation must be settled before implementation; the following notation
describes typed inputs, not existing callable APIs:

```text
vector<int>._from_list(initializer_list<int> [10, 20])
hash<int>._from_key_value(initializer_entries<string, int> [
    entry("alice", 10), entry("bob", 20)
])
```

These inputs must not depend on constructing another vector/hash through the same
protocol first. The backend may use a suitable independent temporary representation;
this contract does not require boxing typed values or virtual dispatch.

Keyed input preserves source order and duplicate entries until the receiver handles
them. Do not first materialize a hash that discards duplicates. Duplicate-key and
accepted-key policies belong to the receiving type's contract, not the notation.
Absent positional keys are not encoded using a null-key sentinel. Supporting an
explicit null key, if any, is a separate receiving-key contract.

A heterogeneous user factory may explicitly accept string keys and mixed values.
It then validates required keys and value kinds at runtime and returns its instance.
Only values admitted by the established boxing contract can enter that mixed input;
this does not introduce arbitrary heterogeneous unboxable argument packs.

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

## 6. Delivery and remaining API decisions

Vector, hash, mixed and dynamic must be designed through the common protocol from
the start. Cover those families together per operation, with runtime kind checks
where mixed leaves the actual container kind unknown. Not every mixed value is a
container. Construction support alone does not implement iteration or access.

The standard includes explicit user-defined class/struct opt-in. Connecting those
types to the protocol waits for their compiler interface/method support. Automatic
field matching and synthesized member initialization remain deferred, independently
of explicit factories. Dynamic `->` and reference iteration remain separate work.

Before coding, settle interface/input API declarations and each built-in adapter's
empty-input selection, duplicate-key policy, positional-key policy and admissible
input shapes against its existing contract. Mixed positional/keyed entries in one
bracket initializer are not standardized by the three forms above; reconcile the
legacy form explicitly rather than inventing a null sentinel or silently rejecting
it as an existing language-wide prohibition. These API decisions do not reopen the
agreed known-target, runtime-construction or mixed-only storage rules.
