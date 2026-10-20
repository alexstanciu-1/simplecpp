# Concrete type interoperability
Doc Status: supporting

Concrete C++ representations may participate in existing runtime operations without
changing source-language type identity. The compiler's type registry and prepared
operation facts remain authoritative. This protocol is a physical runtime/backend
contract, not a new source capability or an additional semantic registry.

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
runtime PCH. Remaining operation coverage and migration of the public option remain
tracked in the solidification TODO.

The initial indexing proof covers vectors, integer-keyed hashes, nested writes/removal, copied
reads, append, reference identity, root replacement during key/RHS evaluation, missing
prefixes, and throwing payload copies. It does not establish complete support for
ownership-wrapper elements/keys, mixed/dynamic composition, compound assignment,
all probes, or initializers. Subsequent initializer/compound/source-storage proofs
are listed separately in the current coverage matrix; the initial proof must not be
read as either full-family coverage or a current list of every missing adapter.

See [compiler coverage](../../compiler/my-try/docs/architecture/cpp_template_prelowering.md)
and [indexing evidence](../../specs/planning/results/prelower_indexing_2026_10_19/README.md).
