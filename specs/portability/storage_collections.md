# Portable shared object collections
Doc Status: supporting

Executable PHP uses `scpp\compiler\Storage` and `Keyed_Storage` helpers. Native
source binding is provided by the compiler runtime module in PR #244 (candidate
`d8ddde93b04d0e23d295e30f662c3a81b0d50fd1`). The configured portability target has
not been advanced. This document records PHP/conversion coverage, not a native
proof of the complete compiler.

Preferred spellings (avoid repeating the construction type for direct local initializers):

```php
public static Storage $rows /** Storage<Row> */;
public Keyed_Storage $names /** Keyed_Storage<Row> */;
$rows /** Storage<Row> */ = new Storage(8);
public function alias(Storage $rows /** Storage<Row> */): Storage /** Storage<Row> */ {
    return $rows;
}
```

Conversion emits `Storage<Row>` / `Keyed_Storage<Row>` at each declaration and
`new Storage<Row>(8)` at construction. Capacity stays a constructor argument.
For a direct annotated local initializer (`$rows /** Storage<Row> */ = new Storage(...)`),
the converter reuses the declaration type when the constructed family matches.
An explicit comment after the constructed class name remains supported and takes
precedence; assignability remains the target's responsibility. Reuse is local to
that exact `new` token, not the last annotation anywhere in the file. It does not
reach later assignments, properties, call arguments, nested constructions, ternaries
or other expressions. Those sites still require a construction annotation.
This is structural annotation reuse, not symbol lookup or generic type inference.
Short names are the fixed
binding spellings; the element may be a qualified literal record name. PHP `array`
properties/parameters/returns continue to require vector/hash annotations.

Fields, static fields, locals, concrete method parameters/returns and constructor
parameters support the explicit shapes. Nullable fields and signatures retain
explicit nullability; a nullable collection is distinct from an empty collection.
Storage interface signatures remain rejected until separately proved on the native target.
No broad generic-class system or element-type lookup is introduced. The target
checks that the named element is an appropriate record. Obvious scalar elements,
bare Storage annotations, nested collection elements and wrong arities are rejected.

Methods, subscripts, append, keyed isset/unset and foreach are structurally retained;
the native module owns their implementation. Collection assignment shares membership;
records use shared handles. Numeric removals leave holes, string keys retain exact
spelling, and a retained record survives removal. No Storage_View or inline record
layout contract is restored.

Proof: `php tests/portability/storage_bindings.php` checks PHP alias/record identity,
string-key distinctions, holes, expected PHS boundaries and invalid annotations.
Existing collection tests own the fuller behavioral contract. Native validation
against the selected source-binding revision remains required, including explicit
local annotations where native cross-file metadata is insufficient.

## Duplicate-key object lists

`Key_Storage_List<T>` is a separate compiler collection. `add(string, T)` records
every insertion, including repeated object identities. `named(string)` returns a
`vector<T>` snapshot in per-key insertion order; `items()` returns a snapshot in
full insertion order. Neither exposes mutable collection membership. Objects remain
shared, and assignment aliases the collection. `is_empty()` observes membership.
Numeric-looking strings remain distinct keys. There are no positional writes,
unique-key replacements, removals or implicit identity deduplication in this API.

The converter accepts the same explicit one-record type annotations and local
constructor reuse as Storage. The native implementation belongs to the compiler
runtime module; its S2S and STAN bindings describe this exact method surface.
Scopes use it for variable/function candidates and type definitions; transient
declaration comparison uses it for candidate groups. Unique registries remain
Keyed_Storage, and the parked LLVM index maps are unchanged.

Focused native proof (explicit opt-in):
`python3 tests/portability/key_storage_list_native.py --target-checkout CHECKOUT --results FRESH`.
The shared my-try S2S proof also exercises these invariants in the native compiler.
