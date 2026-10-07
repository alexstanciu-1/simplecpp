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

The base must support implicit default construction or an authored public constructor
with zero parameters. Base constructors requiring parameters are deferred, including
parameters with source defaults: those defaults currently require caller-side sequencing.
Do not infer inherited constructor forwarding or enable `parent::__construct(...)`.
The child's own existing constructor contract remains available and follows base
construction. Derived `data` initialization remains deferred until inherited input
ownership is agreed; ordinary default construction of a supported data base is allowed.

Inline copy eligibility includes the base's payload eligibility as well as the
child's declared instance fields. Shared-handle copies retain identity as before.
Copying an inline base subobject does not invoke its default constructor again.

Cleanup follows subobject order: child destructor body, child members in reverse
declaration order, then the base subobject. Existing nonthrowing destructor rules
remain. This slice does not enable polymorphic deletion or base-handle conversions;
no virtual destructor policy is inferred.

## Remaining gates

Reject inheritance cycles, cross-kind bases and extension of final types. A concrete
final type may be instantiated. Abstract/interface contracts, method overriding,
member hiding, inherited data inputs, parent calls, late-bound `static::`, derived/base
assignment/argument/return conversions and broader constructor inheritance remain
separate groups. C++'s ability to express these does not enable them in source.
