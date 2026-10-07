# v0.2 concrete inheritance
Doc Status: normative

This bounded contract covers class-to-class and struct-to-struct single inheritance.
It extends the CH10 member contracts; interface execution, virtual overrides and
source-level derived/base conversions remain deferred. Availability and evidence
are tracked in [CH11](../compiler/my-try/docs/catalog/11_inheritance.md).

## Layout and lookup

The base is an inline subobject, independent of the owning instance's shared/unique/
inline storage. Construct it before the child's declared properties. Do not copy
inherited declarations into the child's member inventories. Resolve properties,
methods and constants in separate categories, retaining the original declaring type.
An inherited static property has one storage location owned by that declaring type.
Ordinary methods retain fixed, non-virtual dispatch.

Same-category redeclaration of any inherited property, ordinary method or constant
is rejected in this first slice, including private inherited names. Cross-category
names remain independent. Constructors/destructors are lifecycle declarations,
not ordinary inherited members subject to this collision rule.

## Access

Public members are externally accessible. Private access requires the original
declaring type's context. Protected access requires the declaring type or a descendant.

For a protected **instance** member, additionally require the receiver's declared
type to be the accessing type or one of its descendants. Thus a child can use its
inherited protected member through `$this` or another child-typed object, but not
through an arbitrary base-typed or sibling-typed object. A member of the base itself
can use a base-typed receiver. This explicitly adopts C++'s receiver restriction,
not PHP's broader family access. Diagnose violations during preparation.

Protected static members have no instance-receiver restriction. Expression-qualified
static access still evaluates its qualifier once under the existing CH10 contract.
Potential broader protected access is [non-blocking discussion debt](../compiler/my-try/docs/catalog/15_blocking_debt.md#ch15-protected-01--broader-protected-receiver-access), not current behavior.

## Construction, copying and cleanup

Without an authored constructor, a child inherits the supported public constructor
contract of its base, including parameter defaults, through concrete intermediate
bases. The original declaration owns that contract; it is not copied into the child's
method inventory. Child properties must support their own defaults. The existing
fixed by-value constructor boundary remains; reference/variadic constructors are not
introduced here. Implicit inline-payload default construction does not acquire source
argument-default evaluation: use explicit `new` when an effective authored constructor
needs supplied/defaulted parameters, consistently with the existing record construction
contract. Previously supported implicit derived default construction with an inherited
zero-parameter constructor remains valid; this does not broaden direct authored-record
default construction.

An authored child constructor may begin with exactly one standalone
`parent::__construct(...)`. It must be the first executable statement, not conditional,
nested, repeated or used as an expression. It selects the base's effective authored
constructor and becomes base-subobject initialization, never a call after construction.
An explicit parent call without an authored ancestor constructor is rejected.

Without that explicit call, initialize the base with no supplied arguments. Apply
supported source defaults; reject missing required arguments during preparation.
Without any authored base constructor, ordinary default base construction applies.
Arguments may use constructor parameters and ordinary expressions, but `$this`,
instance properties and parent instance calls are unavailable until base initialization
finishes. Evaluate supplied arguments and their typed boundaries left-to-right, then
omitted defaults in parameter order. Base construction precedes child property defaults/
`set:` initialization and the remaining constructor body. No temporary complete base
object or extra base copy is required.

Derived `data` initialization remains deferred until inherited input
ownership is agreed; ordinary default construction of a supported data base is allowed.

Inline copy eligibility includes the base's payload eligibility as well as the
child's declared instance fields. Shared-handle copies retain identity as before.
Copying an inline base subobject does not invoke its default constructor again.

Cleanup follows subobject order: child destructor body, child members in reverse
declaration order, then the base subobject. Existing nonthrowing destructor rules
remain. This slice does not enable polymorphic deletion or base-handle conversions;
no virtual destructor policy is inferred.

## Explicit parent member access

`parent::method(...)`, `parent::CONSTANT` and `parent::$property` start lookup at
the immediate concrete base, with ordinary inherited lookup thereafter. Preserve
the selected member's original owner and public/protected/private checks. Static
properties still refer to that owner's single storage location. Nonstatic parent
methods require the current initialized instance and explicitly call the ancestor
implementation; static methods need no receiver. Arguments and reference-return
contracts reuse ordinary method-call admission. `parent` outside a member context
or without a concrete base is rejected. This is not late-bound `static::` dispatch.

## Remaining gates

Reject inheritance cycles, cross-kind bases and extension of final types. A concrete
final type may be instantiated. Abstract/interface contracts, method overriding,
member hiding, inherited data inputs, late-bound `static::`, derived/base
assignment/argument/return conversions and non-public/abstract constructor contracts remain
separate groups. C++'s ability to express these does not enable them in source.
