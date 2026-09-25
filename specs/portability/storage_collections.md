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
