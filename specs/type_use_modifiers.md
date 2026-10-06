# Type-use storage modifiers
Doc Status: normative

Agreed 2026-10-02 for the my-try v0.2 frontend.

`value<T>` requests value storage. When the type registry already identifies `T`
as a value type, the request is a semantic no-op: `value<T>` and `T` have the same
semantic type, storage compatibility and operator/conversion behavior. The source
AST retains the explicit modifier node; prepared type identity does not retain a
redundant modifier.

The current registry establishes default value storage from its registered facts:

- boolean, integer and floating families;
- nominal definitions whose kind is `structure`;
- template applications whose registered result kind is `structure`.

This rule does not inspect source spelling, C++ type names or target layout. A custom
registered integer follows the same rule as `int`; a class named `int` does not.
Aliases use their resolved definition. Copyability and default value storage are
different properties: identifying a struct as a value does not prove that all its
fields can be copied. Source structs follow the
[memberwise copy contract](compact_layout_types.md); owned copies require the
[`copyable_value` contract](metaprogramming_contract.md#61-default-contract-for-a-bare-type-parameter).
Neither the presence nor the normalization of an AST modifier grants copy permission.

Under the [v0.2 separated-family contract](dynamic_types.md#v02-separated-mixeddynamic-families--2026-10-05),
both `mixed` and tagged `dynamic` are value-stored bindings. `value<dynamic>` is
therefore redundant, just like `value<mixed>`. This does not request deep copying:
dynamic container payloads still share identity, scalar payloads copy as values,
and replacing a binding does not replace another binding's payload.

Normalization precedes assignment, argument, reference, return and operator checks,
and template application interning. Therefore `vector<value<int>>` and `vector<int>`
reuse the same canonical application, and nested redundant requests such as
`value<value<int>>` normalize to `int` while retaining their source syntax.

For class-like definitions, the existing effective by-value modifier remains distinct;
repeated effective modifiers remain rejected. The currently registered nominal
`string` and runtime ownership/container families retain their class-like
classification. This slice does not redefine their storage contracts, introduce
class allocation, make `value<shared<T>>` valid, or add automatic pointer dereferencing.
`value<void>` remains invalid storage.

C++ emission consumes the normalized type use. Thus `value<int>` lowers to
`scpp::int_t<>`, without an extra `value_p` wrapper or header. Ordinary scalar
conversions and the existing native program-exit adaptation still apply when their
destination requires them. Source syntax alone never suppresses a required conversion.

## v0.2 ownership composition — 2026-10-06

Ownership roles are semantic definition facts, not spelling or storage modifiers.
`shared<T>`, `unique<T>` and `weak<T>` preserve their C++ ownership meanings, with
the safety/subset decisions in the [ownership contract](../runtime/specs/spec.md#v02-ownership-boundary--2026-10-06).

An ownership wrapper's immediate resolved target must not itself be an ownership
wrapper. All nine shared/unique/weak combinations reject during preparation; none
is flattened. `value<shared<T>>`, `value<unique<T>>` and `value<weak<T>>` also reject.
Registered aliases preserve the same role and restrictions. An unrelated definition
does not acquire ownership merely because it has a matching name.

This is not a recursive ban on containers of handles, named holders with ownership
fields, or all nullable/Result compositions. Those retain their separate formation
and operation rules. Rare shared-replaceable-handle use cases should use named
library-owned holder types/functions, optionally implemented in native C++, rather
than compiler-supported direct nesting. No extra nested-wrapper support is promised.

## v0.2 bounded owned construction — 2026-10-06

For currently supported source structs, `new T()` constructs one default-initialized
object with shared ownership. A direct explicitly typed local initializer can select
ownership: `$s shared<T> = new T();` or `$u unique<T> = new T();`. The constructed
type must exactly match the handle target. The unique form constructs directly as
unique; it never creates an intermediate shared handle. Parentheses around the
initializer do not change that rule. Weak handles cannot allocate.

Fresh construction can initialize exact storage without copying an existing handle.
It does not make unique handles copyable or authorize implicit moves from named
variables. Contextual unique construction on later assignment, arguments, returns,
fields and container entries remains deferred. In those contexts `new T()` retains
its default shared result, which must satisfy ordinary boundary rules.

The initial implementation admits no constructor arguments and requires a proven
default initialization for every field, recursively through nested value structs.
Its admitted field defaults are scalar, mixed/dynamic, error, empty ownership or
nullable handles, empty vector/hash containers and recursively admitted structs.
Result fields and unrecognized runtime construction contracts reject in preparation.
Runtime value-type construction, user constructor/destructor bodies, inheritance,
pointee member access and explicit allocation-helper source spellings are separate
slices; `new` does not turn those existing value types into heap objects implicitly.
