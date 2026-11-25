# Type model
Doc Status: supporting

Source type spelling binds to a declaration before concrete preparation. The
[declaration-binding stage](details/template_bindings.md) can identify source
records, provider declarations and template parameter slots without layouts or
canonical type IDs. Concrete annotation consumers follow those accepted references;
the type model continues to own representations and materialized type identity.
A template definition is not a concrete type.

## Ownership

The prototype [type_model owner](../src/04_analyze/type_model/calls.md)
holds shared definitions, representations, normalized callable/record contracts,
input catalogs and canonical type storage. It depends on neither runtime import
nor source resolution. `load_runtime` validates and translates external metadata;
`resolve_types` selects work, resolves source requests and materializes accepted
definitions. Backend preparation owns verified target layout and ABI bindings.

Allocation resource obligations are an optional type property, separate from the
object lifetime contract. Imported callables retain normalized effects on semantic
parameter positions; body consumers use the exact checked callable dependency.
Source records share static `resource_paths` composed from eligible fields.
Receiver ownership summaries belong to lifetime analysis results, separately from
these immutable type definitions. See [owning fields](details/owning_storage_fields.md).
See [allocation ownership](details/allocation_ownership.md) for state transitions
and the original local-owner slice.

## Design

Direction: interpret types through shared contracts and capabilities. The PHP
prototype now has [type storage and action descriptors](details/type_storage.md).
The [first return-type slice](details/return_type_resolution.md) loads authoritative
named definitions and resolves explicit function annotations. [Declared bodies](details/body_checking.md) resolve integer literals, calls, locals
conversions and exact-type integer addition/comparison; checked blocks now describe branches
and loops. The common pipeline emits LLVM and optionally builds native
executables. Broader executable capabilities remain future work.

## Identity, facts, and operations

A canonical type reference identifies a type independently of its spelling.
A family instance is identified by its definition and ordered arguments,
including type arguments and, where supported, validated constant arguments.
Resolve aliases and reuse equivalent instances through the same path; do not
reserve special cases for examples such as `result<int>`.

[Explicit source instances](details/explicit_instantiation.md) now retain exact
definition/argument keys within a canonical type-store lineage. Concrete callable
IDs distinguish specializations while source definition IDs retain provenance;
ordinary and specialized functions share checking, lifetime and output contracts.

Resolve parameter-free named types once per valid definition context and reuse
compact IDs from the ordinary type table. Look up before computing a definition;
an existing identity may still be pending. Workers read the fixed table, while
the coordinator establishes missing identities and joins definitions. Definition
or provider/configuration changes invalidate reuse; spelling alone is not a cache
validity proof. Cache preparation compares explicit configuration/provider/target
keys and requests full recomputation on changes. Pending references, registered
declarations and representation readiness are separate facts; representation
readiness never implies operation/layout/ABI readiness.
See the [implemented foundation](details/type_storage.md#reuse-on-encounter).

Providers supply the facts and contracts needed to use a type. These can be
compact records and IDs; they do not require a class per type or inheritance.

The complete definition serves both frontend checking and LLVM preparation.
For an integer it includes kind, bit width **and signedness**. Signedness is
required semantic information, not something to infer from a name or storage
width. Both stages access the same definition through the canonical type ID;
the AST keeps its compact reference. Equal-width signed and unsigned types may
share a representation while retaining different meaning and operation rules.
The prototype binds the shared provider definition to the canonical type row;
`Type_Store::definition_for_type(id)` retrieves it without repeating name lookup
or copying facts into signatures. Checked bodies expose their shared contracts
through `signature_for(callable_id)` and `definition_for(type_id)` for downstream
consumers. This does not yet establish conversion rules
or implement any LLVM operations.

Lifetime rules also belong to that shared definition. Supported scalars explicitly
declare `copy: value` and `cleanup: none`; `void` has no value-lifetime contract.
Missing rules never imply trivial behavior. A particular value's ownership,
scope and transfer state will belong to the value/binding analysis, which uses
these shared rules to decide cleanup. The [scalar analysis](details/lifetime_analysis.md) now records temporary lifetimes
and graph reachability. [Opaque inline values](details/inline_runtime_storage.md)
add direct construction and call-scoped borrowing under verified no-cleanup
contracts. Their copy policy is `unavailable` until a supported operation is
provided. [Cleanup obligations](details/runtime_cleanup.md) now describe destruction
of locals and full-expression temporaries. [Copy construction](details/runtime_copy_construction.md)
adds `copy: construct` with a shared explicit implementation. Same-type local
initialization borrows the source and creates separately owned destination storage;
assignment has its own independent `unavailable` / `value` / `call` capability.
Source records and fixed arrays compose this capability from their fields; custom
source assignment supplies a complete operation on a live destination. Scalar
catalog v1 value contracts normalize to value assignment at import; opaque runtime
assignment needs a future explicit metadata contract. No copy constructor implies
assignment availability. See [source assignment](details/lifecycle_contracts.md#copy-assignment).

Checked `local_write_kind` distinguishes zero initialization, direct construction,
copy construction, value writes and called assignment. Zero initialization follows
the construction contract even when copying needs a custom body. Assignment reads
follow the assignment capability, without accidentally invoking a custom copier.
Body checking first preserves an addressable expression as a private pending location.
Its consumer chooses the read or borrow once. Completed checked results contain only
the established value kinds; no pending state or new dataset crosses the stage boundary.

The shared lifecycle role supplies a compact composition order: constituent role,
custom-body placement and member direction. Source composition, ownership preparation
and LLVM emission consume that same contract. Executable plans still refer to direct
constituents; semantic ownership plans also include obligations needing no emitted
operation. State analysis and target ABI preparation retain their separate owners.

Integer literals use the catalog's default `int` definition; fractional literals
will use `float`. Destination annotations do not implicitly retype literals.
All typed-boundary conversions go through one resolver. It supports identity and
[integer widening](details/integer_conversions.md): both definitions explicitly
share an `integer_family`, signedness matches, and the destination is wider.
Family membership authorizes this semantic rule; equal representations alone
never do. Other conversions produce an unsupported-conversion diagnostic. See the
[literal and return rules](details/body_checking.md#language-decisions-and-the-conversion-boundary).

| Contract | Questions it answers |
|---|---|
| Value semantics | How are the bits interpreted, including integer signedness? |
| Representation | What storage, layout, alignment, and target constraints apply? |
| Lifetime | How are values initialized, copied, moved, borrowed, and cleaned up? |
| Operations | Which operand combinations, conversions, and results are valid? |
| Structural access | What fields, payloads, elements, or iteration bindings are available? |
| Calls and ABI | How are values passed/returned, and which runtime adapters are required? |

These are areas of responsibility, not a requirement for one giant descriptor
or a complete capability catalog before the first working slice.

Type identity is separate from representation: distinct language types may
share a representation without sharing their operations. Representation kinds
are void, integer, floating point, pointer, fixed array, structure and function
signature, plus opaque inline storage with measured size/alignment. Kind-specific payloads carry scalar bit width or floating format,
element type/count, or ordered member/parameter ranges. Signatures have a
separate return-type reference and ordered semantic parameter-passing modes. Modes
participate in signature interning for both source and provider calls. Void represents absence of a value; it is not
a zero-width integer. Floating format determines width, avoiding two independent
facts that could disagree. Native payloads should use an inline tagged union.

Keep value width separate from memory size. `prepare_backend/Layout_Preparation` derives
verified storage size, alignment and field offsets from the type graph and the
configured LLVM target; Layout_Join accepts private probe results.
A future ABI planner consumes the complete signature and call convention to
decide general argument/return passing. The current [runtime adapter](details/runtime_package_consumption.md)
imports measured storage and normalized scalar/address arguments and caller-storage results. Neither
decision is a universal field on a type.
The [backend context](details/lowering_inputs.md) separately holds target/provider
configuration and prepared callable bindings; its identity participates in
lowering reuse. [Native entry adaptation](details/native_executable.md#native-entry-contract)
now uses a probed hosted-C integer result and a separate conversion plan.
LLVM compatibility belongs at these boundaries; source checking must not branch
on LLVM type names. SIMD representations remain deferred.

A capability is more than `can_add = true`. Resolution considers the operation,
all operand types, and the use context. Its result identifies the selected
operation, result type, conversions, effects/lifetime obligations, and the
implementation contract. Missing or ambiguous support produces a diagnostic.

Operation definitions map an operation and ordered operand-type list to a
result type and implementation binding. The [binary-operation implementation](details/binary_operations.md)
selects exact same-type integer operations with explicit wrapping or ordering
permission. The catalog boolean role supplies comparison results independently of
operand type and type spelling. Exact matching is sufficient initially;
generic patterns and richer selection grow through later slices. Definitions
come from their authoritative providers, not a manually enumerated table of
sample-specific combinations.

Source forms such as `echo` and `curl_init` resolve to callable contracts and
share call/ABI lowering. An operator's selected binding can instead identify a
native target operation. Different bindings need distinct implementations while
sharing resolution and operand handling. No helper-name dispatch in the backend.

## Generic consumers

`foreach` asks for an iteration contract: traversal operations, key/value types,
binding rules, and cleanup. A vector, array, or map provider supplies that
contract. Adding a provider for an existing contract must not require another
type-name branch in `foreach`.

Likewise, copying a value consumes its copy contract. A trivial copy and a
managed copy can have different implementations while sharing the consumer.
Families derive their contracts from the family definition and argument
contracts, checking constraints recursively. Recognizing a type does not imply
that all its operations are implemented.

Generic interpretation does not mean every type behaves identically. Distinct
semantics belong in provider definitions and owned algorithms. Primitive
lowering belongs in target adapters. Neither should be rediscovered by name in
each compiler stage. New semantics may require a new explicit contract.

## Value and operation lowering

Lower expression operands through one recursive `get_value(typed_expression)`
path. It returns a compiler value reference and emits any instructions needed
to obtain it, preserving evaluation order. Literal values, local reads, call results and conversions use this entry point
with their own real implementations. Conversions have distinct result values;
identity keeps the existing value.
Calls themselves are execution records with an optional result. A void call
executes without creating a typed value. The prototype records each statement's
ordered call segment separately from the value it consumes or discards; later
lowering must execute each call once and reference its result when present.

```text
left  = get_value(resolved_left_expression)
right = get_value(resolved_right_expression)
result = apply(resolved_operation, [left, right])
```

Both `10 + 10` and `$a + $b` follow this flow after resolution has selected the
operation contract. `apply` consumes the selected binding; it does not repeat
overload/type selection in the backend. These are conceptual contracts, not
implemented APIs or a requirement for C++ templates/overloads. Constant folding
and compiler dispatch optimizations are later work. Required compile-time
constant evaluation, when a language feature needs it, is a separate semantic
responsibility and must not be replaced by a guessed value.

## Ownership and proof

- `src/resolve_types/resolve.phs` owns canonical identity and family materialization;
  `src/load_runtime/load.phs` imports authoritative runtime provider definitions.
- `src/check_bodies/check.phs` resolves operation contracts; lifetime analysis
  checks their obligations. The typed body records the resolved behavior.
- Lowering consumes that behavior and makes target/ABI actions explicit.
  LLVM emission consumes those actions without deciding type semantics again.

Grow this through small goals. Before declaring a new shared path reusable,
prove it with distinct providers or family arguments and a relevant composed
case, plus rejection of an unsupported operation. Add only the contracts needed
for that goal; do not build a speculative framework for every future type.

The [repository rule](../reference/source-repository/working_rules.md) forbids committed hardcoded shortcuts.
The [old compiler assessment](details/type_capability_assessment.md) records
which parts of its plan and implementation support this direction.

## Source structural definitions and locations

[Source structs](details/structured_values_layout.md) normalize field declarations before
annotation workers query one accepted source/provider definition view. Canonical type
IDs stay nominal; ordered field shapes may be shared. `struct_field` is explicit language
metadata permission, not a consumer-side list of fixed-width type names.

Checked `place` and lowered `storage_address` carry a root and ordered field/index
projections. [Fixed arrays and source methods](details/fixed_array_list_plan.md) use
these shared locations; array capacity belongs to the canonical element/count type. Scalar fields share the containing object's lifetime. Structural default
values and copies use ordinary value semantics; whole-object borrowing remains
explicit for runtime and [source reference parameters](details/source_record_borrowing.md). Selected backend workers prepare target-specific size, alignment and offsets,
which field load/store emission consumes. Complete plain provider records normalize
through the same definition join; their native measurements constrain the generated
layout. `Record_Definitions` owns materialization independently of the producer.
`lifetime_contract` now describes default construction separately from copy and
cleanup. Imported operations and compact compiler-owned member plans are distinct
records in `type_model/data/lifecycle.php`; `Lifecycle_Composition` derives the latter
in resolution. Nested records and metadata-eligible managed fields use these plans;
array plans retain one element operation and an extent. A containing lifetime owns
its field cleanup. Aggregate ABI and exposing private opaque fields remain separate
capabilities; a visible field list never proves a native object's full layout.

## Planned native export boundary

The [source/native contract](details/source_native_contract.md) defines export of
compiler-owned source operations to native runtime templates. Internal canonical
IDs remain scoped to their retained lineage; exported identity uses exact declaration
and typed-argument keys. Accepted layout and checked operation capabilities are
separate referenced contracts. Native adapters do not create a second semantic
type model or own source field lifecycle. This export/import protocol is not yet
implemented in the shared model or compiler pipeline.


Custom source lifecycle plans additionally retain a stable member declaration ID.
The concrete containing type supplies specialization identity; the backend binds
that pair to a checked source callable. Body contents are not copied into type
contracts. Each field plan carries its operation role: a custom copy defaults
fields, while an automatic copy selects field copying. Owning records forbid
value/byte copying but may expose a complete source copy operation. Field
composition and custom body ordering are documented in
[custom source lifecycle](details/lifecycle_contracts.md#custom-source-lifecycle-bodies).

## Typed native storage

A `storage_family` declares native descriptor/primitive contracts independently of
its element. Instance acceptance creates a noncopyable allocation-owning definition
with an `element_storage` contract: exact family, element definition and canonical
element ID. Ordinary representation remains opaque inline storage; the new property
expresses static meaning rather than a new machine representation. Source records
remain structural types. See [typed storage](details/typed_storage_plan.md).

## Default generic parameter contract

Formal type parameters reference `generic_contract::copyable_value`; literal value
parameters have no type-capability contract. `Generic_Contracts::missing()` queries
accepted lifecycle facts for copy, assignment and valid cleanup. Symbolic terms
remain private to definition checking; they never acquire canonical type IDs or
invented layout. See [language rules](details/generic_type_contract.md) and
[implemented owners](details/generic_contract_implementation_plan.md).


## Provider-family declaration boundary

Shared [semantic signatures](../src/04_analyze/type_model/data/semantic_calls.php)
separate type references, passing and result production from the physical callable ABI.
Ordinary imports resolve named references through the existing concrete signature
store. [Family records](../src/04_analyze/type_model/data/families.php)
add exact definition/formal/self identities, default formation contracts and operation
requirements/effects. `Family_Contracts` validates definition permissions before any
concrete substitution. C++ names and adapter fields belong to runtime preparation.

[Runtime-only preparation](details/provider_family_implementation_plan.md#implementation-outcome)
now proves selected tasks, private results, coverage extension and stable publication.
`Family_Adapter` accepts normalized semantic declarations and explicit source mappings.
Collection now registers exposed family/member symbols; symbolic template checking
uses their declared signatures and permissions without specialization. Concrete native
family instantiation and method calls now consume those declarations through the
coordinator preparation bridge. Imported const integer-address calls do not enable
source scalar reference declarations. No placeholder canonical IDs or ABI
layouts are stored for unresolved family parameters.

### Native family type readiness

A registered provider family is a semantic template definition, not a concrete layout.
Ordered argument acceptance creates an instance identity in the common registry.
Coordinator-driven preparation then imports measured opaque storage and lifecycle
contracts with an exact internal name binding; only accepted results materialize
through `Type_Cache` and publish readiness. The semantic `Instance_Set` keeps concrete
type provenance; `Type_Resolution::families` separately retains prepared package
associations. No metadata rewrite or guessed size connects these states. Concrete
method signatures now associate prepared callables with semantic method instances;
receiver position belongs to the shared concrete signature. Unchanged normalized
types/callables retain identity when native coverage grows. Const integer-address
arguments borrow matching places or compiler-materialized expression/conversion storage
through the ordinary call path. See the [integrated append proof](details/provider_family_compiler_integration.md#gates-4-and-5-const-scalar-borrowing-and-integrated-append).

### Owned source results

Concrete signatures retain semantic `result_production` independently of physical ABI.
Owned objects use caller-provided uninitialized storage; source returns select direct
construction, primitive copying or an accepted copy/expiring-source operation before
cleanup. `lifetime_contract::expiring` distinguishes legal copying fallback from missing
preparation. Composed cleanup alone does not suppress automatic source movement;
user-declared copy/assignment/destruction does. Bare generic returns preserve their
copy permission. [Contract, owners and proofs](details/owned_source_results_plan.md).

### Imported copy assignment

Runtime metadata explicitly binds `copy_assign` to an accepted native operation.
The adapter populates the existing `lifetime_contract::assignment` and
`copy_assignment`; ordinary objects and provider families need no new semantic
type or lowering representation. Native traits alone grant no permission.
Generic eligibility reads these accepted capabilities, and source-field assignment
composes them in declaration order. Both borrowed operands remain live, including
during self-assignment. [Contract and proof](details/lifecycle_contracts.md#copy-assignment).
