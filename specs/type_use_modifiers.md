# Type-use storage modifiers
Doc Status: normative

Agreed 2026-10-02 for the my-try v0.2 frontend.

`value<T>` requests value storage. When the type registry already identifies `T`
as a value type, the request is a semantic no-op: `value<T>` and `T` have the same
semantic type, storage compatibility and operator/conversion behavior. The source
AST retains the explicit modifier node; prepared type identity does not retain a
redundant modifier.

Exception agreed 2026-10-07 for source record declarations: `value<MyStruct>`
is an error, as is `shared<MyClass>`. These repeat the declaration's default
storage and can misrepresent its kind to readers. This overrides the earlier
struct-normalization rule; scalar/enum/runtime-value normalization is unchanged.

The current registry establishes default value storage from its registered facts:

- boolean, integer and floating families;
- nominal definitions whose kind is `structure` or `enumeration`;
- template applications whose registered result kind is `structure`.

This rule does not inspect source spelling, C++ type names or target layout. A custom
registered integer follows the same rule as `int`; a class named `int` does not.
Aliases use their resolved definition. Copyability and default value storage are
different properties: identifying a struct as a value does not prove that all its
fields can be copied. Source structs follow the
[memberwise copy contract](compact_layout_types.md); owned copies require the
[`copyable_value` contract](metaprogramming_contract.md#61-default-contract-for-a-bare-type-parameter).
Neither the presence nor the normalization of an AST modifier grants copy permission.

Enums retain their own nominal identity even when stored in an integer representation.
Redundant `value<Enum>` normalization does not grant integer conversions, arithmetic,
reference compatibility with the backing integer, or default initialization. Enum
backing/storage rules remain governed by [compact layout types](compact_layout_types.md#3-enum-semantics).

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

Amended 2026-10-07: the original CH07 shared-by-default struct construction was
temporary and is replaced by declaration-directed storage. `new Class()` produces
a shared object; `new Struct()` produces a struct value. One construction path
selects the exact payload identity and storage independently.

| Source form | Class | Struct |
| --- | --- | --- |
| `T` | Shared object handle | Inline value |
| `value<T>` | Inline object | Error: redundant storage |
| `shared<T>` | Error: redundant storage | Shared object handle |
| `unique<T>` | Exclusive ownership | Exclusive ownership |
| `weak<T>` | Non-owning observation | Non-owning observation |

A direct explicitly typed local initializer may select inline/shared/unique
storage: `$v value<Class> = new Class();`, `$s shared<Struct> = new Struct();`,
or `$u unique<T> = new T();`. The payload must match exactly; construction does
not allocate an intermediate object. Weak never allocates: derive from an existing
shared owner, then lock before accessing. `lock(weak<Class>)` returns bare `Class`,
not the rejected spelling `shared<Class>`. Parentheses preserve these rules.

Ownership wrappers take an unmodified payload; `unique<value<Class>>` and similar
stacked storage requests reject alongside the existing direct ownership nesting.
Inline record copies require memberwise copyability. Shared/weak copies do not
copy the payload. Existing handles never implicitly clone into inline objects.

Fresh construction can initialize exact storage without copying an existing handle.
It does not make unique handles copyable or authorize implicit moves from named
variables. Contextual unique construction on later assignment, arguments, returns,
fields and container entries remains deferred. In those contexts `new T()` retains
its declaration-directed default result, which must satisfy ordinary boundary rules.

The initial implementation admits no constructor arguments and requires a proven
default initialization for every field, recursively through nested value structs.
Its admitted field defaults are scalar, mixed/dynamic, error, empty ownership or
nullable handles, empty vector/hash containers and recursively admitted structs.
Result fields and unrecognized runtime construction contracts reject in preparation.
Runtime value-type construction, user constructor/destructor bodies, inheritance,
and explicit allocation-helper source spellings are separate slices. Plain public
typed instance properties now share the class/struct lookup and read/write path.
Missing/duplicate properties reject during preparation. Heap-backed properties may
be written through stable receiver paths but cannot bind native aliases/reference
arguments; inline projections preserve the existing stable-storage rules. Weak
member access rejects until locked. Property initializers, methods and access-control
extensions remain separate slices; runtime value types do not become heap objects.

## v0.2 explicit unique transfer — 2026-10-06

`move($source)` extracts unique ownership into an owning value when evaluated.
Unlike C++ `std::move` alone, it performs the move construction immediately.
The source becomes empty but remains valid storage; its native aliases remain
bound and observe that empty state. No pointee copying or allocation is involved.
Moving an empty handle is valid. Replacement releases the destination's previous
ownership after extraction; self-transfer, including through an alias, preserves
the original ownership. There is no Rust-style moved-variable invalidation.

Local transfer accepts an exact-type unique transfer as the direct initializer or
statement assignment of whole local/parameter storage. Valid aliases of that whole
storage are accepted on either side. The result owns its value, is not addressable,
and cannot be bound by native reference. Ordinary named unique copies still reject.
Discarded moves, presence tests of moves, value-producing/chained assignments,
conditional joins, direct fields (including locally known aliases), container
elements, shared/weak moves and cross-type transfers remain outside this slice.
Invalid combinations reject in preparation; no source transfer happens at compile time.

Transfers are not transactional: a later runtime failure does not undo a completed
extraction. Owning temporaries release their resources during unwinding.

## v0.2 consuming arguments and unique returns — 2026-10-06

Fixed by-value `unique<T>` parameters consume exact-type owned values. A named
variable requires `move($variable)`; passing it directly is a rejected copy.
Reference parameters still bind stable storage without transfer; a move result
cannot bind to a reference parameter.

A by-value unique function result is an owned temporary: it may initialize or
replace local storage, feed another fixed by-value parameter, or be returned onward
without another `move`. A reference-returning call is not an owned temporary.
Named value returns require `return move($variable);`, including named by-value
parameters. This also permits explicit extraction from a reference parameter;
the caller's referenced storage becomes empty. `return produce();` forwards a
unique-valued call result. Contextual `return new T();` remains rejected for a
unique result: first construct an explicitly typed unique local.

Explicit arguments are evaluated left-to-right into temporaries. Each move extracts
at its own evaluation point; consuming temporaries then transfer into the callee.
A later argument failure prevents entry into that callee, destroys already-created
argument temporaries and does not restore moved sources. Two explicit moves of the
same source therefore supply its prior ownership first, then an empty handle.
This is not Rust exclusive-borrow enforcement.

Unique variadic packs, cross-type ownership conversions, conditional ownership joins,
field/container transfer and implicit null argument/return conversions remain deferred.

## v0.2 weak derivation and explicit locking — 2026-10-06

`weak(expression)` derives `weak<T>` from an exact `shared<T>` operand. It neither
allocates nor copies the pointee, consumes the shared handle, or retains strong
ownership. Empty shared produces empty weak. Weak handles are copyable independently
of pointee copyability. `unique<T>` and other families are not accepted; implicit
shared-to-weak conversions and cross-payload conversions remain rejected.

`lock(expression)` accepts exact `weak<T>` and returns `shared<T>`. A successful
lock acquires ownership of the same object. An empty or expired weak returns empty
shared, not an exception or an additional nullable wrapper. Both operations evaluate
their single operand exactly once and produce non-addressable handle values. They
are language ownership operations, not user-overloadable calls or general member
dispatch. The helper spelling does not enable source `$observer->lock()`.

Retain the acquired handle for the required lifetime:

```php
$owner = new Item();
$observer = weak($owner);
$held = lock($observer);
if ($held) {
    $owner = null;
    // $held still owns the same Item.
}
```

Weak truthiness, negation, `isset`/`empty` and strict-null comparisons remain
rejected; apply those operations to the locked shared result instead. A condition
such as `if (lock($observer))` tests acquisition but does not retain that temporary
owner throughout the body. Lock once into a local when the body needs ownership.
Locking is ownership acquisition, not a mutex or a guarantee of pointee thread safety.

Shared-valued temporary expressions are valid operands. `weak(make_item())` expires
after the temporary's full expression if no other strong owner exists. In contrast,
`$held = lock(weak(make_item()));` acquires ownership before that temporary is released.
No hidden persistent owner is introduced to make an otherwise expired weak usable.
General handle member access, expiration/count observers, inheritance conversions
and broader construction remain separate slices.
