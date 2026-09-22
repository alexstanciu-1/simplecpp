# Type representation foundation
Doc Status: supporting

The type_model owner now supplies validated value shapes, semantic passing/result
mode codecs, cache context keys and lineage/member records. This is the first dependency
of the authoritative type catalog, not a substitute catalog or named type definition.

Representation factories cover all nine prototype shapes: void, integer, floating,
pointer, fixed array, structure, function signature, opaque inline storage and byte
span. Private state and checked accessors replace the PHP union of unrelated payload
objects. Factories establish valid tag/payload combinations; the default object is
explicitly the payload-free void representation. Consumers cannot mutate payloads.
No native union or compact-width limit is imposed yet. Integers retain the prototype's
positive host-int widths, including values larger than uint32. Representation identity
is not named-language-type identity; signedness, operations and lifetimes belong to
named definitions, which remain the next dependencies.

Callable shapes retain canonical return/member references, ordered passing modes and
result production. Empty passing input expands to value passing, as in the prototype.
Input vectors are copied explicitly, avoiding PHP/native aliasing differences. Type
IDs are positive; member ranges are nonnegative zero-based ranges whose actual membership
must be checked by the future store. Validation does not add first+count or infer target
layout. The rewrite rejects negative ranges/invalid return IDs at construction rather
than waiting for store validation; valid algorithms are unchanged.

Passing/result codecs preserve the exact provider strings and reject unknown modes.
Borrowed object/scalar modes remain distinct from byte-span passing. Floating formats
preserve their five names and value widths without claiming native storage size or
operation support. Opaque layout validation checks positive size/alignment, power-of-two
alignment and size divisibility. Its arithmetic guards run before modulo or doubling,
so safety never depends on short-circuit evaluation or floating-point conversions.

Type_Context requires nonempty configuration/provider/target version keys. Type_Lineage
is an identity anchor, not a hash of those keys. Type_Member preserves the type ID,
field name and writability flag. These records do not yet implement canonical storage,
cache reuse, named definitions, provider ingestion, signatures or entry return binding.

98 independent PHP/native outcomes cover shapes, modes, context, invalid inputs,
accessor rejections and signature vector independence. The original representation
constructors also run in a retained host oracle for floating widths, passing defaults,
borrow classification and result vocabulary. Five additional host purity/boundary
assertions cover immutable observations and large integer alignment. Those large
alignment boundary cases are host-only evidence, not additional native outcomes.

See [timing, corrections and provenance](../planning/compiler_migration/results/type-representations-01/README.md).
The first checker correction replaces unsupported bitwise AND with a bounded integer
power-of-two check. A native STAN missing-return diagnostic required explicit codec
success branches and a common return; no unreachable fallback value or diagnostic
suppression was added. No converter, framework or target implementation was changed.
