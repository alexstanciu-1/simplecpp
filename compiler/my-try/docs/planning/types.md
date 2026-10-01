# Type model direction
Doc Status: planning

Discussion started: 2026-10-01

## Purpose and authority

This document records the active `compiler/my-try` discussion about types. It is
an implementation plan and decision ledger, not a user-visible language authority.
Normative Simple C++ meaning remains under `specs/`; in particular, generic rules
must ultimately be reconciled with and promoted into
[`specs/metaprogramming_contract.md`](../../../../specs/metaprogramming_contract.md).

The intended implementation path is PHP-authored and must remain suitable for the
portable native compiler. C++ output is a backend representation, not the semantic
type model. LLVM remains parked and is not an implementation target for this work.

This plan covers type concepts, retained structures and the processes that own
them. Casts and operators will be discussed separately. They may consume the type
model, but must not determine its shape by accumulating pair-specific cases.

## Current implementation baseline

The compiler now separates these responsibilities:

- `type_node` describes source type syntax;
- specialized semantic definitions describe boolean, integer, floating, nominal,
  template and no-value invariants;
- `canonical_type_use` is the lightweight identity currently attached to prepared
  source occurrences;
- scopes own type lookup;
- preparation resolves names and publishes canonical type facts;
- the C++ backend maps prepared facts to target spelling and required headers.

The former flat `type_definition` record has been retired from the active frontend
and C++ path. Family-specific definitions now hold only meaningful invariant data,
and source declaration association remains a separate non-owning catalog index.

The redesign must preserve current strengths:

- source syntax, semantic identity and backend spelling remain separate;
- preparation is the sole owner of semantic resolution;
- prepared consumers reuse canonical identity instead of resolving names again;
- source records retain their declaration relationship;
- the backend fails on unsupported prepared types rather than guessing;
- retained structures do not retain workers or invocation contexts.

## Agreed value-type families

The working value-type classification is:

1. boolean;
2. integers, including `byte` and every fixed signed/unsigned width;
3. floating point;
4. nominal and constructed types, including structures, classes and predefined
   runtime types such as strings and containers.

Non-value compiler types remain outside those four value families. `void`, and a
possible future `never` or internal diagnostic type, must not pretend to be a
scalar, structure or class.

The last value family is deliberately broader than C++ `class`. It includes:

- source structures and classes;
- predefined nominal runtime types such as `string`;
- concrete applications of template definitions such as `vector<int>`;
- ownership/value wrappers once their semantic contracts are defined.

A predefined runtime implementation does not erase language-specific behavior.
For example, a string can be represented by a runtime class while still having
literal-construction rules that an arbitrary user class does not have.

## Common semantic contract and specialized state

Resolved types should share a meaningful read-only interface and an abstract base.
The interface exposes facts common to every resolved type, such as canonical
identity and broad family. The base owns only genuinely shared representation.

Concrete semantic types retain only properties meaningful to their invariants:

| Semantic type | Specific state |
| --- | --- |
| Boolean | No integer signedness property |
| Integer | Bit width and signedness |
| Floating point | Format/precision information, not integer signedness |
| Nominal | Its nominal definition |
| Applied template | Template definition plus ordered selected arguments |
| No-value | Its explicit non-value role |

The common interface must not offer questions such as `signed()` for every type.
A consumer establishes that a type is an integer before reading integer facts.
This makes invalid questions structurally difficult and avoids a growing bag of
irrelevant nullable properties.

The exact class names and whether some compact records replace allocated objects
remain implementation details. The semantic separation is the decision.

## Definitions, canonical resolved types and occurrences

Three compiler layers must remain distinct:

| Layer | Example | Responsibility |
| --- | --- | --- |
| Definition | declaration of `Box<T>` | Name, scope, parameters, members and provenance |
| Canonical resolved type | `Box<int>` | One interned semantic identity for the exact application |
| Source occurrence | each written `Box<int>` | Source span, spelling/bindings and prepared type identity |

A runtime object is a fourth concept and is not compiler type metadata.

`Box<int>` is interned by exact structure, conceptually:

```text
(Box definition ID, [int type ID]) -> canonical Box<int> type ID
```

Repeated occurrences point to the same canonical identity. Interning is not merely
an optional performance cache: it establishes equality and dependency identity.
An entry may be registered as pending before it is prepared so valid recursion can
refer to an existing identity while invalid layout expansion is diagnosed by the
owning type/layout process.

Source occurrences should remain light. They retain the source-specific facts
needed for diagnostics and attach a compact prepared type identity. They do not
copy the canonical definition or specialization.

## Numeric identities

Closed classifications may use PHP 8.5 integer-backed enums. The open set of
actual types cannot be represented by an enum because user definitions and template
applications are created during compilation.

The working division is:

- `type_family`: an integer-backed enum for the small closed classification;
- `type_id`: a `uint32` identifying a canonical resolved type;
- `definition_id`: a compact positive integer identifying a declaration or
  predefined definition;
- a type registry/interner owned by the semantic type process.

Every concrete semantic type has a `uint32` `type_id`. A template definition is
not concrete and therefore has no `type_id` merely because it is declared. Only an
exact canonical application, such as `Box<int>` or `vector<string>`, receives a
`type_id`. Occurrences of that application reuse the same `uint32` identity.

The `uint32` boundary is part of the intended native representation, not merely a
PHP annotation. Allocation must reject exhaustion rather than wrap or reuse a live
identity.

Built-in identities may occupy reserved stable IDs. Source definitions and applied
types receive allocated IDs. IDs are not recycled while retained references can
observe them. The owner and lifetime of each allocation sequence must be explicit.

An applied template is interned by its complete exact key:

```text
(template definition ID, ordered (type ID, by-value flag) type arguments,
 ordered validated value arguments)
```

A hash table may index that key, but a hash is never proof of identity. Descriptive
strings are for diagnostics, not semantic keys.

Cross-session or serialized stability is not promised merely because IDs are
numeric. If artifacts later retain them, the artifact identity and invalidation
contract must be designed explicitly.

True aliases should share canonical identity while occurrences retain their written
names. For example, `byte` and `uint8` may share a `type_id` if the language defines
them as exact aliases. Equal storage alone does not establish aliasing: types with
different operation rules retain different identities even when their layout is
equal.

## Nominal definitions and inner structures

A nominal definition owns its member scope. That scope is the future home of fields,
methods and nested type declarations. An inner structure is a real nominal definition
with its own identity and the containing nominal definition as its lexical owner.

Example:

```cpp
struct Company {
    string name;

    struct Address {
        string street;
        string city;
    };

    Address location;
};
```

`Address` resolves within `Company` and has a qualified diagnostic identity such as
`Company::Address`. It has no implicit runtime reference to a `Company` instance.

Agreed direction: an inner type is available only inside the class or structure
that declares it. It is not published into the file/global type scope and cannot be
named externally as either `Address` or `Company::Address`.

Implementation is deferred. The debt includes:

- allowing member declarations, rather than only fields, in structure/class bodies;
- collecting inner type definitions into the containing member type scope;
- identity, lookup, shadowing and declaration-order rules;
- external visibility enforcement;
- deciding whether an externally accessible value may expose a hidden inner type
  indirectly through a public field, parameter, result or chained member access;
- dependency ordering, by-value cycle rejection and C++ emission;
- nested template declarations after the ordinary inner-type model is established.

The current model already handles a field whose type is another ordinary source
record. That composition is not the same feature as a lexically inner declaration.

## Template declarations and applications

Template declarations and uses are distinct concepts.

A source template should have one dedicated wrapper AST node that owns:

- ordered formal parameter syntax;
- the wrapped ordinary declaration, such as a structure, class or function;
- its source span and typed traversal edges.

The wrapped declaration retains its ordinary grammar and owners. This avoids a
separate `templated_struct`, `templated_class` and `templated_function` hierarchy.
The template wrapper is a declaration node, not a type node.

Formal parameter order has one owner: the template definition's ordered parameter
list. A parameter record stores its name and contract, not a duplicate position.
Its semantic identity is the template definition ID plus its zero-based list slot.

A template type use should have one application AST node that owns:

- the target type/template reference;
- ordered argument syntax;
- its attached prepared canonical type identity.

This node represents `Box<int>`, `vector<string>`, `nullable<T>` and nested
applications uniformly. It does not create special AST kinds for each library
family. Compile-time value arguments, when supported, require an explicit argument
variant rather than being silently treated as arbitrary expressions.

The shared definition AST is never copied or mutated for each specialization.
Materialization uses a per-instance binding environment from formal parameter slots
to canonical arguments. The instance identity and environment belong to the
materialization process, not to shared syntax.

## Default generic type contract

Simple C++ adopts the default generic contract developed in `scpp_compiler_3`.
The normative rule is recorded in
[`specs/metaprogramming_contract.md`](../../../../specs/metaprogramming_contract.md#61-default-contract-for-a-bare-type-parameter).
A bare type parameter `T` is not unconstrained. It implicitly uses the
`copyable_value` contract.

`copyable_value` requires:

- known concrete type and representation information;
- supported copy construction;
- supported copy assignment;
- a valid compiler-managed cleanup contract, including an explicitly established
  no-op cleanup contract.

It permits a generic definition to identify and store `T`, borrow it for a call,
copy-construct it, copy-assign it, return an owned copy and perform its required
cleanup. It does not grant default/zero construction, movement, arithmetic,
comparison, equality, hashing, arbitrary member access or `new T`.

A template definition is checked using only its declared parameter guarantees.
A favorable concrete specialization never expands those permissions. Separately,
each concrete type argument must satisfy the complete declared contract even if a
particular template body does not exercise every guaranteed operation.

The initial compact representation is intended to begin with one backed enum case:

```text
generic_contract::copyable_value
```

The normative adoption defines the language contract; it does not claim compiler
enforcement. Initial implementation does not need user-authored constraint syntax
or additional contracts.

Deferred enforcement debt includes:

- definition-level permission checking;
- concrete argument eligibility checking;
- diagnostics that name the missing capability and application path;
- runtime/provider template parameter enforcement;
- additional contracts such as hashability, equality, default construction or
  move-oriented storage;
- composing capabilities from source fields and declared custom operations.

## Formation and operation requirements

The ability to form a type and the availability of its operations are independent.
The model must reserve separate locations for:

- parameter contracts;
- requirements to form a template application;
- requirements for individual operations on the resulting type;
- real implementations that establish those operations.

For example, a future `nullable<T>` might be formable when `T` has valid storage
and cleanup, while copying `nullable<T>` additionally requires copying `T`. The
first bounded implementation may require `copyable_value` for every accepted `T`,
but the representation must not collapse formation and operation availability into
one permanent `allowed` flag.

This distinction also keeps source `unique<T>` honest: it can be a valid formed
type while the resulting unique handle does not satisfy another template's
`copyable_value` parameter.

## Predefined runtime templates and source wrapper names

Runtime-backed families exposed as `vector`, `hash`, `nullable`, `shared`, `weak`
and `unique` use the same semantic definition/application path as source templates,
but they do not have fabricated source AST declarations. Their provider/backend
identities and spellings remain separate.

The source-language ownership spellings are deliberately different from their C++
runtime implementation spellings:

| Source spelling | Runtime/backend spelling |
| --- | --- |
| `shared<T>` | `shared_p<T>` |
| `weak<T>` | `weak_p<T>` |
| `unique<T>` | `unique_p<T>` |
| `value<T>` | by-value flag; backend may use `value_p<T>` where required |

`shared`, `weak` and `unique` are semantic template definitions whose exact
canonical applications receive `uint32` type identities. Their `_p` spellings do
not enter source lookup.

`value` is a hard-coded source type-use modifier, not a runtime template definition.
It resolves the underlying type and records that this use requires by-value
representation. It must not allocate a fake `value` template definition or a new
canonical template type merely to select storage. The likely prepared shape is the
underlying canonical `type_id` plus a by-value flag. Canonical template applications
retain that same compact pair for every ordered type argument so `vector<Foo>` and
`vector<value<Foo>>` have distinct exact application identities.

Three identities remain separate:

| Concern | Example |
| --- | --- |
| Source-language exposure | `vector<T>` |
| Runtime provider identity | provider/package family `vector` |
| Backend binding | `scpp::vector_t<T>` and its header/operations |

Both source and runtime definitions should satisfy a common semantic template
definition interface. Their provenance differs:

- a source template definition references its source template declaration;
- a runtime template definition references its exact provider identity and declared
  semantic surface;
- a separate runtime/backend binding owns C++ names, headers, native adapters,
  measured layout and ABI information.

Semantic application and physical readiness are separate facts. The compiler can
resolve and intern `vector<int>` before its target representation and demanded
native operations have been prepared. Backend spelling or equal layout never
establishes semantic identity.

The initial runtime-family restrictions remain debt. Definitions may record their
intended parameter contracts now, but the compiler must not claim that it enforces
them until both definition permissions and concrete eligibility are checked.

`value_p<T>` remains a backend/runtime spelling selected by the hard-coded by-value
flag; it is not categorized as pointer ownership or registered as a semantic
template family. `shared_p<T>`, `weak_p<T>` and `unique_p<T>` retain their distinct
ownership semantics; those semantics are not inferred merely from their names.

`hash` demonstrates why more than the default contract will eventually be needed:
its key requires explicit hashing and equality capabilities. The compiler should
not replace those contracts with branches for known key type names.

## Process ownership direction

The intended flow is:

```text
parser
    -> typed name/application/template-declaration syntax
collector and scopes
    -> definition identities and declaration bindings
type preparation/materialization
    -> canonical type IDs, parameter bindings and readiness dependencies
ordinary semantic preparation
    -> concrete expression, storage, signature and lifecycle facts
C++ backend
    -> representation, headers, declarations and operations from prepared facts
```

The canonical type registry/interner belongs to the semantic type process. Scopes
own lookup membership, not canonical type storage. AST nodes own their exact syntax
and attached occurrence facts, not registries or materialization workers.

Runtime/provider metadata supplies definitions and capabilities. It does not place
C++ spelling into the frontend type model. Backend preparation supplies physical
layout/ABI readiness without adding source-language permissions.

## Lessons retained from `scpp_compiler_3`

The earlier compiler is design evidence rather than code to port wholesale. The
following decisions should be retained:

- a template definition is not a concrete type;
- instances have exact definition-plus-ordered-argument identity;
- definition permissions are checked independently of concrete eligibility;
- independent formal parameters retain independent contracts;
- specialization is demand driven;
- an instance is registered before preparation to support controlled recursion;
- source templates and provider families converge on the ordinary concrete type
  and callable paths;
- source exposure, provider identity and native binding remain separate;
- semantic readiness and physical/native readiness remain separate;
- formation requirements and per-operation requirements remain separate;
- hashes may index complete keys but never establish identity;
- the shared source AST is not copied per specialization.

Do not import the older compiler's complete worker, package, ABI or publication
machinery into `my-try`. Reuse the current retained model, preparation scheduling,
attached facts and C++ handoff unless a future concrete slice proves they cannot
truthfully own the requirement.

Reference evidence:

- `/home/alexv/__AI/scpp_compiler_3/docs/details/generic_type_contract.md`;
- `/home/alexv/__AI/scpp_compiler_3/prototype/src/04_analyze/type_model/data/generic.php`;
- `/home/alexv/__AI/scpp_compiler_3/docs/details/template_bindings.md`;
- `/home/alexv/__AI/scpp_compiler_3/docs/details/explicit_instantiation.md`;
- `/home/alexv/__AI/scpp_compiler_3/docs/details/provider_family_compiler_integration.md`.

## Implementation checklist for this pass

The following is the agreed broad implementation order. Each numbered item may be
split into independently reviewable concept slices, but later work must continue
through the owners established by earlier items.

1. **Adopt the stricter template-parameter requirements in the normative docs
   (complete).** Bare `T` is the implicit `copyable_value` contract, definition
   permissions remain separate from concrete argument eligibility, and a favorable
   specialization cannot expand permissions. Enforcement, runtime family
   restrictions and additional contracts remain explicit later debt.
2. **Create the type data model and structures (complete).** Represent boolean,
   integer and floating-point families; nominal structures/classes; template
   structure/class definitions; and exact canonical applied-template types. Add
   the `uint32` canonical type registry and exact application interning/reuse.
   Template definitions retain definition identity but receive a type identity
   only in an exact applied shape.
3. **Define and register the types that already exist in Simple C++ (complete).**
   Install the existing concrete language/runtime definitions and the known runtime
   template definitions with their separate source exposure and backend binding. Create a
   concrete applied-template instance only for an actual demand. There is no
   Cartesian enumeration or speculative combination generation. A later explicit
   fixed pre-preparation list may be considered independently, but is not part of
   this pass.
4. **Migrate the current `my-try` implementation (complete).** Move existing bool, integer,
   float, string, record, variable, constant, signature and expression facts to
   canonical type identities. Preserve current behavior and C++ output. Move lookup,
   assignment compatibility, field validation, dependencies and C++ representation
   onto the common path. Do not add templates merely to prove the refactor; first
   prove every currently supported type through the new model.
5. **Add one bounded constructed-type proof.** After migration stability, add one
   runtime-template application, initially `vector<int>` or `nullable<int>`, proving
   template-definition lookup, argument validation, canonical application creation,
   repeated-use reuse, lightweight occurrence attachment and backend binding.

Each slice must name its non-goals and prove that existing scalar/record lookup,
preparation and C++ emission still use the common path. Native compiler validation
remains explicit-request only.

No step authorizes generating all possible template arguments or combinations.
Canonical applications arise only from exact source/compiler demand and are reused
thereafter.

### Registered definition checkpoint (2026-10-01)

The reviewed model now has an isolated registration layer, still deliberately not
wired into the current compiler pipeline before item 4:

- concrete language definitions: `void`, `bool`, `byte`, `int`, `float`, `string`,
  and every signed/unsigned fixed-width integer from 8 through 64 bits;
- runtime template definitions: `vector`, `hash`, `nullable`, `shared`, `weak`, and
  `unique`;
- one source type-use modifier: `value`, whose backend binding may select
  `scpp::value_p` without creating a semantic template family;
- independent source-exposure, runtime-provider and C++-binding catalogs.

Runtime definitions record the requirements that are currently known:
`vector.Value`, `hash.Value`, and `nullable.Value` are `value_storable`, while
`hash.Key` requires both `hashable` and `comparable`. Pointer target parameters
currently carry an explicit empty requirement set rather than a false copyability
claim. Enforcement remains debt.

Current Simple C++ hash syntax is value-first: `hash<Value, Key>`. `Key` defaults
to `string`, matching the runtime and legacy S2S behavior. The registry completes
that default before interning, so `hash<int>` and `hash<int, string>` select the
same canonical type identity.

Registration creates canonical identities only for the concrete built-ins. It
creates no applied runtime-template types. Applications are materialized by exact
demand and then reused, including nested applications and the by-value bit carried
by each argument.

The legacy S2S review also found aliases such as `vector_t`, `hash_t`, `shared_p`
and `weakref`, plus rejection rules for nested ownership/value wrappers. Those
aliases are not added to the strict source-exposure catalog: backend names remain
backend-only, and the agreed strict spelling is `weak`. Nested-wrapper validation
is retained as runtime-family formation debt rather than being hidden in
registration.

The current PHP registration code is a bootstrap representation, not the intended
long-term source of predefined type metadata. Move the predefined language/runtime
catalog to validated JSON and load it during compiler initialization. The JSON
boundary should describe semantic definitions, source exposures, provider
identities, ordered parameters and declared contracts/defaults; backend bindings
may use either a separate backend-owned JSON document or a clearly separated
backend section. The loader must validate schema/version, duplicate identities,
unknown contract names, invalid/default-before-required parameter order, missing
referenced definitions and uint32 identity limits before publishing any catalog.
Loading must still create no applied-template Cartesian product: applications stay
demand-driven and interned by the canonical registry.

### Migration checkpoint: active frontend and C++ path

`Model` owns the registered
semantic catalog and the independent C++ binding catalog. Full compilation/syntax
resets replace both roots together; ordinary preparation and generation preserve
their identity. The semantic catalog also owns a definition-ID-to-`collected_struct`
association so source syntax remains owned by its collected file rather than being
embedded in every canonical type.

Active scopes now store semantic definitions. Type syntax, prepared expressions,
storage, constants, signatures and generation contexts attach lightweight
`canonical_type_use` facts. Lookup canonicalizes concrete definitions; assignment,
field access, record dependencies and C++ representation recover invariant/backend
metadata through the canonical identity. The old flat `type_definition`,
`type_kind` and `type_origin` records no longer own active behavior.

This migration intentionally adds no constructed source syntax and creates no
runtime template application. PHP-host validation covers the canonical catalog,
all 95 valid S2S fixtures, source-record behavior, preparation recovery and
incremental C++ generation with unchanged output expectations. Native compiler
validation remains explicit-request work.

The parked LLVM recovery proof can still prepare the current model, but its special
test that re-prepares an externally retained old syntax graph after a full lifecycle
reset no longer has the retired catalog that owns that graph's source-definition
associations. Repairing that cross-reset legacy lookup belongs to a future explicit
LLVM adaptation; it is not addressed by rebuilding LLVM semantics or adding a
parallel compatibility type model here.

## Open decisions and recorded debt

- Exact `type_family` cases and whether template application is a construction kind
  or a top-level family.
- Exact interface/base/concrete class names and compact native storage layout.
- ID allocation scope, built-in reservations and future artifact stability.
- Whether `byte` is a true alias of `uint8` or a distinct semantic type with equal
  storage.
- Floating formats beyond the current language `float`.
- Struct versus class semantic differences, visibility and reference/value behavior.
- Propagation rules for the hard-coded `value<T>` by-value flag at storage,
  parameter, result and backend boundaries.
- Inner-type exposure through public values and signatures.
- User-authored template constraint syntax and enforcement of the registered
  `value_storable`, `hashable`, and `comparable` contracts.
- Runtime-family formation and operation requirements, especially `hash` keys.
- Capability composition for source nominal types.
- Type aliases, qualified names and module-facing compile-time surfaces.
- Layout readiness, incomplete types, recursive indirection and error recovery.
- Cast representation and conversion selection.
- Operator capability/selection representation.
- Required tests, incremental invalidation and diagnostics for each later slice.
- JSON schema, loader and ownership for predefined language/runtime definitions and
  backend bindings, replacing the current bootstrap registration code.

## Non-goals of this planning pass

- Implementing template bodies, inner structures, runtime-family operations,
  casts or operators; this pass only registers runtime-family definitions.
- Resuming LLVM type semantics.
- Running native compiler validation.
- Treating C++ templates, names or traits as the Simple C++ semantic authority.
- Designing every future concept or capability before a real consumer requires it.
- Claiming compactness or performance improvements without native measurement.
