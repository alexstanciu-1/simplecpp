# Private dynamic value and shared-container storage

Doc Status: normative
Scope: v0.2 private ownership foundation; no generated public dynamic API.

## Owners and separation

`runtime/src/boxed/boxed_value.hpp/.cpp` owns common tagged-value mechanics with two
compile-time family instantiations. `mixed_value.hpp` and `dynamic_value.hpp` name
separate private types, not aliases of each other. Each table contains its own
family: `packed_table<mixed_value>` or `packed_table<dynamic_value>`. No runtime
family tag, mixed-valued dynamic table, or implicit conversion is introduced.
`scpp_boxed_storage` links the compiled packed-key pool.

The governing contract is the separated-family amendment in
`specs/dynamic_types.md`. Donor `runtime/include/scpp/support/dynamic_value.hpp/.cpp`
at `8492cb241` establishes inline scalars and shared container identity; traversal
bindings and type-blind adapters are excluded. Mixed's existing private COW behavior
is retained in the shared owner and tested independently.

Both families represent null, bool, int64, float64, string and same-family container.
Inline scalars copy by value. Strings share immutable indirect bytes internally;
extraction copies bytes and assignment replaces the binding. Copies, moves and
reset are nonthrowing. Moves leave reusable null sources. The 24-byte layout remains
a measured target, not a portable ABI contract.

## Shared identity versus binding replacement

Construct dynamic containers explicitly. Set/append on null throws before insertion;
null reads return null and absent removal stays lazy. Other scalar receiver kinds
throw on indexed operations. Key checks use the same bounded integer domain and
byte-exact already-classified strings as packed storage; source normalization remains
an adapter responsibility. Missing reads return dynamic null, never mixed.

Copying a container preserves its table identity. Mutation through any copied binding,
by-value parameter or container-valued read is visible through aliases, including
nested containers. A scalar/string read remains a value copy. Replacing a local,
parameter or slot changes that binding/slot only; it does not mutate the formerly
stored object. `clean()` resets one binding to null, leaving other bindings intact.

Dynamic mutation never clones a shared table. It uses the packed engine's checked
mutation and rollback directly. Allocation failure preserves logical membership,
values and order for every alias; private capacity/representation may change. Valid
interned keys may remain after a later insertion failure. Append limits, deletion
identity and order are the packed contract. No concurrent mutation guarantee is added.

## Explicit scalar conversion

`boxed_conversion.hpp/.cpp` owns two explicit private conversion functions. Null,
bool, int64, float64 and string preserve exact kind and value in either direction.
There is no numeric coercion; negative zero and NaN remain floating values. String
conversion copies bytes and can allocate. Every container conversion rejects,
including empty containers. Conversion finishes before receiving-value replacement,
so rejection/allocation failure preserves the prior target. Neither constructors,
assignment nor set/append admit an implicit family crossing.

These functions prepare the required runtime boundary; source compiler registration,
public cast signatures and the wider checked-cast operator matrix remain separate.

## Cycles and deferred work

Strong-reference cycles are supported, following
`runtime/specs/public_api_generation.md`'s dynamic contract. Reference counting does
not collect them. No cycle rejection, weak-edge rewrite or collector is introduced.
Tests explicitly remove cycle edges before teardown. The existing cycle-reclamation
debt is carried forward as RT-DEBT-02 in this rewrite: shared cyclic graphs require
an explicit future reclamation design; ordinary aliasing must remain unchanged.

Public family generation through the PHP API generator, operators, boxed keys,
source nested-write lowering, JSON, native weak/shared adapters and traversal are
not part of this private slice. RT-DEBT-01 remains unchanged. Public APIs must not
expose these templates, private storage, or mutable interior references.

See [validation](dynamic_storage_validation.md).
