# Concrete type interoperability
Doc Status: supporting

Concrete C++ representations may participate in existing runtime operations without
changing source-language type identity. The compiler's type registry and prepared
operation facts remain authoritative. This protocol is a physical runtime/backend
contract, not a new source capability or an additional semantic registry.

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
Native helper templates stay in support units for concrete receivers. Unsubstituted
receivers continue using their native runtime interfaces.

Current proof covers vectors, integer-keyed hashes, nested writes/removal, copied
reads, append, reference identity, root replacement during key/RHS evaluation, missing
prefixes, and throwing payload copies. It does not establish complete support for
ownership-wrapper elements/keys, mixed/dynamic composition, compound assignment,
all probes, or initializers. Those remain separate integration work.

See [compiler coverage](../../compiler/my-try/docs/architecture/cpp_template_prelowering.md)
and [indexing evidence](../../specs/planning/results/prelower_indexing_2026_10_19/README.md).
