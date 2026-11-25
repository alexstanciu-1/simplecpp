# Concrete type interoperability
Doc Status: supporting

Concrete C++ representations may participate in existing runtime operations without
changing source-language type identity. The compiler's type registry and prepared
operation facts remain authoritative. This protocol is a physical runtime/backend
contract, not a new source capability or an additional semantic registry.

The compiler-worker names below describe the existing `my-try` lowering path.
The new runtime-owned [metadata-driven public API generator](public_api_generation.md)
uses the same lifetime/storage obligations, with type-specific exposure and small
binding snippets in API metadata. It does not replace source evaluation/sequencing
rules or add another source capability registry.

## Ownership and proof boundary

Runtime [`cpp_types` metadata](../../specs/runtime_module_metadata.md#native-type-representation-recipes)
describes native representation recipes attached to existing identities. Registered
operation contracts and prepared facts select source operations, passing modes and
conversions. C++ bindings/adapters implement physical transport; the specialization
engine assembles demanded interfaces and dependencies. Runtime code retains container,
formatting, ownership and error algorithms. None of these roles is a second registry.

Native layout/trait probes remain authoritative for size, alignment and available
special members; prepared capabilities still determine which source copies/transfers
are permitted. Equal layout alone does not permit copying object bytes or changing
ownership. Concrete storage contains the actual native outer object, with concrete
stored arguments where selected by the recipe. Borrowed addresses/references designate
existing objects; owned results are constructed in final result storage. Existing
emitters retain operand sequencing, temporary lifetime and exceptional cleanup.

Fixed adapter protocols (indexing helpers, initializer buffers, iteration cursors and
operation transport) belong in their existing workers. Metadata records actual
provider variation, such as native argument roles and scalar/byte/pointee interfaces;
it does not prescribe each C++ implementation step. Providers must satisfy these
physical contracts as well as the declared source capabilities.

The [current coverage matrix](../../specs/planning/runtime_template_capability_matrix_2026_10_19.md#current-coverage-contract--2026-10-20)
separates registered bindings, concrete compatibility, complete closure proofs and
unsupported/unproved combinations. A template-free guarantee requires transitive
application-input validation without the broad runtime PCH. Recipe validation and
successful middleware compilation alone do not establish that guarantee.

## Transport and lifecycle contract

These are fixed backend protocols, not additional metadata recipes:

| Interaction | Existing owner and physical obligation |
|---|---|
| Value/reference arguments | `CPP_Declarations::selected_call` evaluates explicit arguments left-to-right and applies prepared conversions and copy/consume/reference boundaries. `CPP_Source_Calls` preserves those categories when invoking the provider; it must not add another snapshot. Defaults use the existing prepared-default path. |
| Receiver | Ordinary call emission uses the prepared receiver lifetime/dispatch: borrow inline storage or retain the existing handle as required. `CPP_Concrete_Members` forwards demanded interfaces to the native outer receiver while keeping stored arguments concrete. |
| Borrow/observation | Transport passes the address of a live object. Output and the single-observation call path keep the operand and any indirect temporary owner within the same full expression. No borrowed address may outlive that invocation; a local reference alone cannot extend an indirect owner's lifetime. |
| Owned result | `CPP_Runtime_Operations` constructs the native result in the final concrete object's storage through a synchronous callback. Runtime-call/observation results may instead already be the concrete type; the implementation checks the actual result type. Neither path permits byte-copying unrelated objects or retaining the callback's stack inputs. |
| Nested storage | `CPP_Types` applies stored/payload native argument roles from existing bindings. Container helpers return references to actual concrete stored elements. They do not recursively unwrap every operand to an unrelated native instantiation. |
| Initialization | `CPP_Initializer_Buffer` uses the existing runtime buffer/factory and ordinary entry sequencing. Its native object owns cleanup; list lvalues and keyed aggregate prvalues preserve their ordinary categories. Final construction adds no boundary-only result object. |
| Lifecycle | `CPP_Opaque_Representation` creates/destroys the actual native object. Clang probes provide size, alignment, available special members and their noexcept traits. Semantic copy permission remains an independent capability restriction. Middleware asserts matching layout and nonthrowing destruction. |

`CPP_Types::pointee` consumes either a declared formal argument or the modifier's
explicit payload binding (`pointee_modified_type`). Generated pointer/dereference
interfaces preserve the existing const behavior: by-value storage exposes a const
payload through a const wrapper; shared/unique handles retain their existing pointee
access. This is not a new source ownership rule. Declaration-only signature headers
permit forward references; fields still require complete concrete storage.

`CPP_Specializations` orders demanded layout dependencies and rejects unresolved
generated cycles. The native builder compiles/runs probes and caches their output
using existing tool/flags/dependency identities. Neither metadata nor the backend
supplies guessed ABI constants. Executable probes require an execution environment
for the target; this contract does not establish cross-target probe execution.

Current call transport is deliberately bounded. Runtime ordinary members accept
void results and nonvariadic parameters. Source-call/constructor transport rejects
reference results, variadics and observation parameters. Runtime free calls accept
the existing value/reference path (including prepared defaults), or an observation
path with at most one input, no conversion and matching parameter/argument counts.
Mixed observation/value inputs, reference results and variadics reject. These limits
belong to the existing call adapters, not family-name branches in the engine.
Provider declarations must match the selected physical contract; Clang compilation
is still required after metadata validation.

The [combined O0 acceptance](../../specs/planning/results/prelower_acceptance_2026_10_20/README.md)
links the behavior/counter, source lifecycle, layout-cache and strict-header proofs.
They establish no additional boundary allocations/copies for the tested cases, not
an allocation-free runtime or coverage of every provider/type combination.

## Integer representation contract

A generated integer representation exposes:

```cpp
using scpp_runtime_integer_rep = /* registry-selected signed/unsigned fixed-width type */;
scpp_runtime_integer_rep native_value() const noexcept;
explicit ConcreteInteger(scpp_runtime_integer_rep);
```

Its constructor and `native_value()` preserve the same value domain as the native
`int_t<Rep>` representation. The alias is an explicit opt-in promise, not structural
inference that any `native_value()` method denotes an integer. Current generated
representations retain the native object's layout, lifecycle, and constructor behavior.
They are regular copied/moved integer values where the native integer permits it.

`detail::is_int_t` and `int_rep` consume this contract. The shared `key_ops` integer
implementation uses `native_value()` for hashing/equality. Hash storage therefore
retains concrete keys while existing append code selects the correct width, sign,
next-key calculation and overflow behavior. Integer key behavior is not duplicated
in the compiler. Mixed-key admission retains the default signed 64-bit domain;
recognizing concrete integers does not broaden that admission to other widths.

Vector key adapters reuse native checked access after non-allocating scalar
adaptation. Matching integer representations can cross keyed-path helper boundaries;
these adapters neither allocate payload storage nor copy container elements.

## Concrete operation interfaces

The C++ backend publishes demanded, non-template overloads of existing `scpp`
indexing/path helpers. Declarations use concrete physical parameter/result types;
implementation units call existing runtime helpers on the native outer receiver.
Stored template arguments remain concrete. Keys and values use the helper contract,
not blanket unwrapping of every operand. Returned traversal references/pointers refer
to the actual stored concrete elements. Nullable pointer receivers preserve absence.

`CPP_Concrete_Index` consumes existing prepared read/write/keyed-path facts and uses
existing fragment operation requests, specialization entries and layout publication.
The ordinary emitters still sequence receiver/key/RHS evaluation, perform complete
path validation before mutation, and re-resolve lazy removal prefixes. The interface
worker does not evaluate syntax, infer types, or implement container-family behavior.
Native helper templates stay in support units for concrete receivers. Ordinary mode
can use native runtime interfaces. In strict concrete mode, an unsubstituted interface
is not permission to expose templates silently. The native builder enforces the
published concrete application's input policy across transitive headers and rejects
runtime PCH. CLI `--prelower-templates` and API `prelower_templates` select this
same strict path. Remaining operation coverage stays tracked in the solidification TODO.

The initial indexing proof covers vectors, integer-keyed hashes, nested writes/removal, copied
reads, append, reference identity, root replacement during key/RHS evaluation, missing
prefixes, and throwing payload copies. It does not establish complete support for
ownership-wrapper elements/keys, mixed/dynamic composition, compound assignment,
all probes, or initializers. Subsequent initializer/compound/source-storage proofs
are listed separately in the current coverage matrix; the initial proof must not be
read as either full-family coverage or a current list of every missing adapter.

See [compiler coverage](../../compiler/my-try/docs/architecture/cpp_template_prelowering.md)
and [indexing evidence](../../specs/planning/results/prelower_indexing_2026_10_19/README.md).
