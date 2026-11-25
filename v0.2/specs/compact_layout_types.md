# Compact records and backed enums

Doc Status: normative

## Structs

A struct stores its fields inline. Copying a copyable struct copies each field according to its type. Copying a shared object field preserves object identity. A private field has the same storage and copy behavior as a public field. Container fields retain the element, key and ownership requirements of their declared type. A struct containing a noncopyable field cannot gain copyability through nesting or optional wrapping.

Field storage alone does not supply a readable value. Construction must initialize required fields, taking declared defaults into account. A typed record initializer resolves fields against the destination declaration. Constructor calls use the declared parameter types and defaults. Structural similarity does not convert between nominal record types. See the [required-field rules](type_use_modifiers.md#temporary-field-storage-exceptions).

## Backed enums

Each declaration introduces a nominal type. Case names and backing values must be unique. Integer literals must fit the declared backing. An omitted backing or explicit `int` exposes int64 through `enum_value`. Compact storage then covers both extreme case values. A fixed-width backing selects that exact representation. A string backing compares decoded bytes exactly, including empty strings and embedded NUL. Its compact internal tag is private.

A case is immutable. By-value arguments, returns, fields, declared case defaults, equality and selection preserve enum identity. Equality requires the same enum type. A selection returning enum values preserves that type. Nullable and result payloads retain the carrier's presence or failure state. Operators on a plain enum do not implicitly unwrap a carrier. Public instance methods receive the nominal enum value.

`enum_name` returns the case name. `enum_value` returns its backing value. `enum_from_value` checks membership in the requested enum and throws for an unknown backing value. No implicit assignment conversion exists between an enum and its backing. See [compiler coverage and frontend spellings](../compiler/docs/enums.md).

## Restricted unions

A restricted union has overlapping member storage. Payloads must support trivial lifetime management throughout their representation. Strings, object handles and containers are excluded even inside a nested struct payload. A union member cannot declare a default initializer.

No-argument construction leaves the union without an active member. Whole-member assignment evaluates and converts the replacement before installing it. A failure preserves the old payload. Copying a union copies its active value. A read, nested write or reference requires the corresponding member to be active and initialized. The application maintains a tag when it needs one.

## Layout queries

`layout_sizeof`, `layout_alignof`, `layout_offsetof` and `layout_field_sizeof` observe the target compiler's layout. Type and field arguments name declarations at compile time. Field queries require a direct public instance field. Offset queries cover public-field layouts without polymorphism or inheritance. Source functions may shadow the query names. Packing controls, user alignment, general reflection and a portable external ABI remain outside this contract.
