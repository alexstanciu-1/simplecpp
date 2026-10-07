# v0.2 inheritance and virtual contracts
Doc Status: normative

This bounded contract covers class-to-class and struct-to-struct single inheritance.
It extends the CH10 member contracts with abstract methods and interface implementation.
Interface-typed values and source-level derived/base conversions remain deferred. Availability and evidence
are tracked in [CH11](../compiler/my-try/docs/catalog/11_inheritance.md).

## Layout and lookup

The base is an inline subobject, independent of the owning instance's shared/unique/
inline storage. Construct it before the child's declared properties. Do not copy
inherited declarations into the child's member inventories. Resolve properties,
methods and constants in separate categories, retaining the original declaring type.
An inherited static property has one storage location owned by that declaring type.
Ordinary methods retain fixed, non-virtual dispatch unless implementing an inherited
virtual contract established by an abstract method.

Same-category redeclaration of any inherited property, nonvirtual method or constant
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
contract of its base, including parameter defaults, through abstract or concrete
intermediate bases. The original declaration owns that contract; it is not copied into the child's
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
remain. Hierarchies with virtual method contracts have virtual destruction, including
an implicit public nonthrowing virtual destructor when none is authored. Preserve
ordinary memberwise copy/move availability; a generated destructor must not silently
suppress moves. Ordinary nonpolymorphic hierarchies do not acquire virtual destruction.
Source base-handle conversions remain deferred.

## Abstract types and overriding

Classes and structs may be explicitly `abstract`, with properties, constructors and
concrete methods. They cannot be instantiated, even without abstract methods.
Abstract inline values cannot be stored, copied as complete values or passed/returned
by value. An abstract base subobject remains constructible/copyable as part of a
concrete descendant, subject to its fields. Absent ownership handles remain valid.

An abstract method is a bodyless, public/protected, nonstatic, non-final declaration
inside an explicitly abstract type. It establishes a virtual contract. Every concrete
descendant must implement all inherited abstract obligations. An abstract intermediate
may leave obligations unimplemented or redeclare a virtual method abstract.

An implementation of an inherited virtual method remains virtual automatically.
Optional `#[\Override]` asserts that such a target exists; reject it otherwise.
The bounded contract requires exact parameter types, passing/reference modes,
read-only qualification, variadic shape, optional-parameter positions, return type
and reference-return origin. Parameter names and default values need not match;
defaults are selected from the statically resolved callable contract. Visibility
may widen from protected to public, never narrow. Covariance/contravariance remain
deferred. A final implementation cannot be overridden; a final type cannot be extended.
`abstract final` types/methods are invalid. Nonvirtual hiding remains rejected.

Ordinary instance calls use virtual dispatch where the selected method has a virtual
contract. Explicit `parent::` calls select the ancestor implementation without virtual
redispatch; directly calling a bodyless abstract ancestor method is invalid.
Construction/destruction follow C++ dispatch rules: no dispatch into a more-derived
part before its construction or after its destruction. Reference-return safety keeps
the existing whole-parameter origin and conservative recursion restrictions, including
possible virtual targets; explicit parent calls retain their exact target.

Constructor contracts remain concrete; abstract/final/override destructor modifiers
and abstract constructors are not introduced by this slice.

## Explicit parent member access

`parent::method(...)`, `parent::CONSTANT` and `parent::$property` start lookup at
the immediate concrete base, with ordinary inherited lookup thereafter. Preserve
the selected member's original owner and public/protected/private checks. Static
properties still refer to that owner's single storage location. Nonstatic parent
methods require the current initialized instance and explicitly call the ancestor
implementation; static methods need no receiver. Arguments and reference-return
contracts reuse ordinary method-call admission. `parent` outside a member context
or without a concrete base is rejected. This is not late-bound `static::` dispatch.

## Interface implementation

Interfaces may extend multiple interfaces; classes and structs may implement multiple
interfaces using one shared contract path. This slice admits public, bodyless instance
method contracts only. Static methods, constants, properties, constructors, destructors,
trait use and method bodies in interfaces remain rejected. Interface declarations
have no source instance layout and cannot be constructed. Interface-typed storage,
parameters/returns and source upcasts remain a following slice, not implicitly enabled.

Concrete implementers must provide all required methods; abstract implementers may
leave obligations outstanding. The same exact signature/reference/readonly/variadic
and optional-parameter checks as abstract overrides apply. Implementations must be
public instance methods. Matching local implementations become virtual; a checked
`#[\Override]` assertion may refer to an interface requirement. One implementation
may satisfy multiple compatible same-named contracts. An interface diamond retains
one occurrence of the original requirement; incompatible same-named signatures reject
during preparation, including in abstract types and interface redeclarations.

A compatible inherited public method may satisfy a newly added interface. Preserve
its source identity and original dispatch policy. Preparation records a forwarding
binding where needed, and C++ emits a public overriding method in the implementing
descendant that calls its base implementation. No synthetic AST method, cloned source
body or retroactive virtualization of the original base method is introduced.
Forwarding preserves reference returns/arguments, packed variadics and by-value
ownership transfer. Descendants may override a non-final forwarding implementation.

Approved bounded restriction: reject a new forwarding binding when the inherited
implementation is already **virtual and final**. Such a C++ bridge would override
the final method. An interface already satisfied by the base remains valid, and an
inherited final **nonvirtual** implementation may be forwarded with a final bridge.
See [CH11-INTERFACE-FINAL-01](../compiler/my-try/docs/catalog/11_inheritance.md#ch11-interface-final-01--inherited-final-virtual-forwarding).

C++ interface edges use public virtual inheritance so diamonds share the same interface
subobject. Concrete record-base inheritance is unchanged. Interface destruction is
public, virtual and nonthrowing; implementing records use the existing polymorphic
cleanup/copy/move policy. This representation is not permission to slice an inline
struct or implicitly allocate/box it as an interface value.

## Remaining gates

Reject inheritance cycles, cross-kind bases and extension of final types. A concrete
final type may be instantiated. Interface-typed values/conversions, nonvirtual member hiding,
inherited data inputs, late-bound `static::`, derived/base
assignment/argument/return conversions and non-public/abstract constructor contracts remain
separate groups. C++'s ability to express these does not enable them in source.
