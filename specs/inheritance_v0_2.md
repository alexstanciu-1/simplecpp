# v0.2 inheritance and virtual contracts
Doc Status: normative

This bounded contract covers class-to-class and struct-to-struct single inheritance.
It extends the CH10 member contracts with abstract methods and interface implementation.
Interface-typed shared handles and shared derived/base upcasts are enabled below. Availability and evidence
are tracked in [CH11](../compiler/my-try/docs/catalog/11_inheritance.md).

## Layout and lookup

The base is an inline subobject, independent of the owning instance's shared/unique/
inline storage. Construct it before the child's declared properties. Do not copy
inherited declarations into the child's member inventories. Resolve properties,
methods and constants in separate categories, retaining the original declaring type.
An inherited static property has one storage location owned by that declaring type.
Ordinary methods retain fixed, non-virtual dispatch unless implementing an inherited
virtual contract established by an abstract method.

Same-category redeclaration of any inherited property, nonvirtual instance method or constant
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

Derived `data` initialization requires explicit `data` opt-in on the child. With an
effective authored public constructor, declared locally or inherited, keys name that
constructor's parameters, with its original types/defaults; they do not expose extra
child or base properties. The base need not opt into data construction. An inherited
constructor retains its declaring owner and requires default-constructible child
properties, as for ordinary `new`. An authored child constructor uses the same
parent-initialization rules above. No post-construction property assignment bypasses
constructor bodies or access rules.

Without an effective authored constructor, a derived `data` type may compose its
base's synthesized data contract and its own public instance-property inputs. The
base must itself explicitly opt into `data` and provide a synthesized contract;
no automatic exposure of a non-data base is permitted. Inputs are base-first, then
local declaration order; original types/defaults and visibility remain unchanged.
Construct the base subobject directly with its supplied/defaulted inputs, then child
members. Required base inputs are allowed and checked at the data-construction site.
Ordinary `new`/implicit default construction still requires a default-constructible
base and local properties. An effective authored constructor always selects the
parameter-based contract instead; property inputs are never appended to it.

Inline copy eligibility includes the base's payload eligibility as well as the
child's declared instance fields. Shared-handle copies retain identity as before.
Copying an inline base subobject does not invoke its default constructor again.

Cleanup follows subobject order: child destructor body, child members in reverse
declaration order, then the base subobject. Existing nonthrowing destructor rules
remain. Hierarchies with virtual method contracts have virtual destruction, including
an implicit public nonthrowing virtual destructor when none is authored. Preserve
ordinary memberwise copy/move availability; a generated destructor must not silently
suppress moves. Ordinary nonpolymorphic hierarchies do not acquire virtual destruction.
Shared base/interface handle conversions follow the contracts below; other ownership
conversion forms remain deferred.

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
`abstract final` types/methods are invalid. Nonvirtual instance hiding remains rejected.

### Compatible static-method redeclarations

A descendant class/struct may redeclare a static method with the same exact callable
contract checked above, including reference-return origin, variadic shape and optional
parameter positions. Parameter names and default values may differ. Static and instance
methods cannot replace one another. Visibility may stay equal or widen (including
private to protected/public), never narrow. A final static method cannot be redeclared.

Static redeclaration does not establish virtual dispatch or make its record polymorphic.
`#[\Override]` continues to assert a virtual/interface contract and is rejected on
these static redeclarations. Named and expression-qualified calls use their selected
declared type; existing `self::`/`parent::` calls keep fixed implementation selection.
Inherited properties and constants retain their existing redeclaration restrictions.

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
have no source instance layout and cannot be constructed. Interface-typed storage
uses the shared-handle contract below.

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

## Trait composition

Classes and structs may compose direct traits using `use T, U;` in their member
body. Trait names follow the consumer's normal lexical namespace/import lookup.
Each target must resolve unambiguously to a trait; duplicate use is rejected.

This bounded slice admits explicitly typed instance/static properties, concrete ordinary
instance/static methods and member constants under their ordinary contracts. Each consumer owns independent working
members. Lexical names/imports in their types, defaults and bodies remain those of
the trait source; `$this`, private/protected access and member lookup use the consumer.
The authored trait is not prepared as an instance, is not constructible and is not a
runtime base or conversion target. Contributed methods reuse ordinary parameter,
reference-return and dispatch contracts, including satisfying interface obligations.

Reject duplicate same-category members between traits or between a trait and the
consumer, including an authored method replacing a trait method. Property, method and constant
names remain separate categories. Existing inherited-member restrictions still apply.
Public contributed instance properties participate in synthesized `data` inputs under
the ordinary public-property rules; private properties do not become data inputs.

Each direct consumer owns separate contributed static-property storage. A descendant
inheriting that property uses the original consumer's storage, not another copy.
Static fields remain excluded from instance layout and data inputs. Their initializers
follow ordinary static defaults/constant-expression restrictions. Constants use the
existing scalar constant evaluator and visibility rules, including eager validation
of consumer-owned constants even when unused.

Contributed static methods use the ordinary called-type instance path: `self` denotes
the consumer and `static` follows the called type. Static signatures are checked eagerly;
bodies are checked per demand. Lexical names/imports, including bare namespace constants,
remain those of the trait source; member constants require qualification. Trait types
themselves are not static-call or static-storage targets. Existing inherited collision,
compatible static redeclaration and finality rules remain in force.

Nested trait uses, adaptations/aliases, trait lifecycle methods and bodyless trait
requirements remain deferred. This v0.2 contract
supersedes the legacy blanket rejection of trait syntax, not the deferred extensions.

## Remaining gates

### Late-static calls and shared factories

Late static binding distinguishes the lexical declaring type (names and access) from
the called type. An explicit `Child::method()` establishes Child, including when
the implementation is inherited. `self::method()` and `parent::method()` select a
fixed implementation and forward the current called type; `static::method()` selects
through that called type and forwards it. Expression-qualified static calls establish
the qualifier's declared type and evaluate that expression exactly once, consistently
with the [static-member contract](static_members_v0_2.md).

Late-selected properties keep the actual declaring owner's single storage location;
constants keep their selected declaration. No inherited storage duplication is implied.
Compatible descendant static-method redeclarations are part of this extension, with
exact callable contracts and no visibility narrowing, not C++ virtual overriding.
Inherited property/constant redeclaration restrictions do not change.

`new static(...)` constructs the called class using its checked constructor
contract. The first construction group admits shared class handles only and applies
ordinary checked-at-preparation result conversions; it does not slice inline records.
Called-type context is compiler-managed, never ambient global runtime state. Cross-file
families are supported by the agreed design rather than v0.1's file-local restriction.

Static declarations, signatures and declaration defaults are checked eagerly. Static
bodies are syntax-checked when parsed, then semantically checked per requested
called-type implementation. An uncalled static body does not receive full body-semantic
validation. An abstract base may declare `make(): Base { return new static(); }`:
`Child::make()` checks construction of Child, whereas `Base::make()` rejects abstract
construction. Ordinary functions and instance methods retain their existing body checks.

Availability: static calls establish concrete called-type implementations, including
cross-file calls, `static::` methods/properties/constants and shared-class `new static`.
Runtime-called-type propagation through ordinary instance methods requires a separate
design; `static::`/`new static` without a concrete called-type context reject. Do not
substitute a receiver's declared type silently. See the
[implementation plan](../compiler/my-try/docs/planning/called_type_instances.md).

### Current rejection boundary

Reject inheritance cycles, cross-kind bases and extension of final types. A concrete
final type may be instantiated. Nonvirtual instance member hiding,
runtime-called-type instance propagation, non-shared derived/base
conversions and non-public/abstract constructor contracts remain
separate groups. C++'s ability to express these does not enable them in source.

## Shared derived/base upcasts

An existing shared derived handle may implicitly convert to a shared handle of a
proven base at a typed assignment, by-value argument or value-return boundary.
Bare classes use their ordinary shared representation; explicitly shared structs
use the same conversion path. The conversion shares the existing object/control
block, preserves absence and never copies, slices or boxes the payload. It does not
make ordinary methods virtual: calls still follow the existing dispatch contract.

The same per-value boundary applies to property assignment, data inputs, individual
typed-container elements and assignment into a reused foreach variable. This does
not convert a whole `vector<Derived>` into `vector<Base>` or widen another mutable
container's element type.

Reference parameters (including read-only references), aliases and reference returns
retain exact storage-type requirements. No implicit downcasts, inline-record base
copies, unique upcasts, wrapper-payload conversions or new conditional joins
are enabled by this slice.

## Weak derived/base upcasts

`weak<Derived>` may implicitly convert to `weak<Base>` for proven class or struct
ancestry, at the same per-value boundaries as shared upcasts. Both types must stay
in the weak family. The conversion preserves the control block and empty/expired
state without locking, allocating, copying the payload or extending its lifetime.
An expired observer can therefore still be upcast; its lock remains empty.

`lock()` uses the target payload type: a bare base-class handle or `shared<Base>`
for a struct. Only a successful explicit lock acquires strong ownership. References,
including read-only references, aliases and reference returns remain invariant.
Interface weak handles, downcasts, unique conversions, implicit shared-to-weak
conversion, whole-container conversions and new conditional joins remain deferred.

## Interface handles and calls

A bare interface type denotes an absent-or-present shared handle, with the same
copying, presence, null-assignment and checked dereference behavior as a class handle.
`?I` and `nullable<I>` normalize to `I`; there is no nested absence wrapper.
Interfaces cannot be allocated or stored inline. Explicit `shared<I>` is redundant;
`value<I>`, `unique<I>`, `weak<I>` and interface weak observation are not admitted in
this bounded slice.

Proven shared upcasts include implementing class to interface, explicitly shared
struct to interface, and derived interface to ancestor interface. All reuse the
typed value boundaries above. An inline struct is never implicitly boxed. A direct
`$i I = new C()` constructs the concrete class in its ordinary shared storage, then
applies the checked-at-preparation upcast; it does not allocate an interface object.

Calls expose only the receiver's statically selected contract and dispatch to the
concrete implementation. Default arguments come from that selected declaration,
not the runtime object's implementation. Calls retain the shared receiver and check
presence before evaluating arguments. Reference parameters/results and variadic
arguments reuse the ordinary callable protocol; handle-storage references remain
invariant, including read-only reference parameters.

An abstract implementer may call an outstanding interface requirement from its body
without redeclaring it as an abstract class/struct method. This call-only lookup
does not turn an interface requirement into an implementation. If distinct inherited
same-named declarations leave no unique call contract, reject the call; an explicit
compatible redeclaration selects the contract/defaults. Identity diamonds remain
unambiguous. Reference-return cycle checking includes interface implementations and
inherited forwarders, using original source callable identities.
