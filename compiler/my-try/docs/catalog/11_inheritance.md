# 11. Inheritance, interfaces and dispatch
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires classes and methods. Resolve relationships before advanced dispatch cases.

The user approved same-kind class/struct inheritance and preservation of existing
non-virtual-by-default dispatch. The [foundation plan](../planning/inheritance_foundation.md)
records those boundaries and the approved shared member-owner prerequisite.
That model migration and relationship/lookup foundation are complete. Bounded
concrete inheritance, abstract/override dispatch and interface implementations now
execute. Shared derived/base upcasts and interface-typed handles now execute.

## Trait composition foundation — 2026-10-08

The user approved simple instance-method/property composition for classes and structs,
with same-category collisions rejected and adaptations/nested uses/lifecycle/static
members/constants outside the first execution slice. Consumer-owned independent member
instances are the approved prerequisite; see the
[ownership plan and checkpoints](../planning/trait_member_instances.md).

The first checkpoint parses trait declarations and simple grouped `use` lists through
the common declaration AST. `collected_trait` and nominal `trait_type` preserve source
identity without granting record layout or handle capabilities. Methods retain their
original trait owner; uses do not flatten members into authored consumers. Parsing
tracks use-list edits and rejects adaptation/trailing-comma forms.

The second checkpoint materializes consumer-owned members through the shared parser
and proves independent method preparation for class and struct consumers, using
trait-local imports and consumer receiver/access context. Private token snapshots
preserve source spans; authored trait/consumer membership remains unchanged.
The third checkpoint feeds contributed members through shared read-only inventories:
observed lookup, record defaults/layout, collision validation, method scheduling,
virtual contracts, reference cycles and C++ artifact enumeration. Method implementations
belong to the consumer unit; lexical source spelling still belongs to the trait.
Source `use` execution remains gated. Next: automatic composition selection and
edit/removal/retry/reset integration, followed by generated-program execution proofs.

Proof: all 18 PHP-host model suites passed, including trait syntax/identity,
use/body edits, malformed-use recovery and removal; `/tmp/trait-model-20261008-1`.
Materialization checkpoint: all 19 PHP-host model suites passed in
`/tmp/trait-instances-20261008-3`; final atomic-failure/retirement probe passed in
`/tmp/trait-instance-focused-2`. The focused instance suite took 0.34 seconds in the
model run; the longest suite (`s2s`) took 12.55 seconds. FPM was unavailable in the
sandbox, so the shared runner used its CLI fallback.
Inventory checkpoint: all 19 PHP-host suites passed in `/tmp/trait-inventory-20261008-1`.
Final default-value/eligibility/cycle/artifact probe: `/tmp/trait-inventory-focused-4`.
The extended instance proof took 0.42 seconds, and the longest suite (`s2s`) took
12.42 seconds. It checks emitted artifacts but does not compile/run a trait program.
No native compiler build is claimed by these checkpoints.

## Interface handles and calls — 2026-10-08

The agreed handle group is implemented: bare interfaces use shared nominal storage,
with existing presence/absence, copying, checked calls and nullable normalization.
Class/interface and shared-struct/interface upcasts reuse dependency-aware conversion
decisions; interface/ancestor upcasts use the same path. No payload allocation/copy,
implicit struct boxing or interface-layout facts were introduced. Direct class
construction can feed a shared base/interface destination through the same boundary.

Method selection remains shared. Abstract bodies can call unimplemented interface
requirements via a call-only fallback; implementation validation still requires real
source implementations. Defaults follow the selected static contract. Distinct
same-named inherited contracts require an explicit redeclaration before an otherwise
ambiguous call, even when one concrete implementation satisfies both. This preserves
the existing unique-declaration lookup rule rather than arbitrarily choosing defaults.

Reference-cycle analysis links original interface declarations to their settled
implementation decisions, including inherited forwarders, in its transient graph.
Only actual bodies receive cycle-failure state. Cross-file failure/retry/repair covers
ancestry, default changes and a newly introduced reference cycle.

Covers the transferred CLASS-CALL-007 interface-call behavior; CLASS-MEMBER-013
continues to share ordinary signature/contract preparation. `value<I>`, explicit
ownership wrappers, weak observation, downcasts and static-interface dispatch remain
outside this slice. No native compiler rebuild.

Proof: 25 generated programs without PCH (`/tmp/interface-handles-20261008-final`),
32 interface-contract regressions (`/tmp/interface-handles-contracts-20261008-1`),
32 abstract-dispatch regressions (`/tmp/interface-handles-abstract-20261008-1`) and
17 PHP-host model suites (`/tmp/interface-handles-model-20261008-1`) passed.
Next discussion: reconcile the remaining CH11 groups before selecting trait composition,
late static binding or another ownership-conversion slice.

## Dependency-aware shared upcasts — 2026-10-08

First checkpoint of the agreed interface-handle group: conversion preparation accepts
a transient `conversion_relationship_query`. Its analysis-owned implementation observes
source ancestry through relationship work, including failed searches; no cached registry
answer, retained worker, backend lookup or second hierarchy is introduced. Conversion
facts retain only the selected `shared_upcast` operation and canonical types.

Bare-class and explicitly shared-struct derived/base boundaries now execute through
one path for assignments, by-value function/method arguments and returns, properties,
data inputs and individual container elements. Reference storage stays invariant;
downcasts, boxing, unique/weak conversions and whole-container conversions reject.
C++ constructs the target shared handle using existing runtime support and records
complete source/target header requirements. No runtime change or native compiler rebuild.

Proof: 16 generated programs without PCH, including cross-file ancestry removal,
failure/retry/repair and fresh/incremental agreement for unchanged assignment, argument
and return consumers. Also passed: 51 generated inheritance regressions and 17
PHP-host model suites. Evidence: `/tmp/shared-upcasts-20261008-final`,
`/tmp/shared-upcasts-inheritance-20261008-2`, `/tmp/shared-upcasts-model-20261008-1`.

The interface-handle checkpoint above completes the agreed follow-on admission,
abstract-body requirement lookup and reference-return cycle integration.

## Interface contracts and inherited implementations — 2026-10-07

Implemented through the existing member-owner, signature and type-contract paths:
public bodyless instance interface methods; multiple implements/extends; identity
deduplication through diamonds; exact compatible same-named requirements; public
local/inherited implementations; abstract outstanding obligations and checked Override.
Classes and structs share the path. Interfaces have contract/signature work, no record
layout or synthetic bodies. Requirements retain original declarations after satisfaction
for descendant validation. Source interface-typed values were gated at this checkpoint;
the subsequent interface-handle checkpoint above enables their bounded execution.

Inherited implementations use prepared forwarding decisions, never cloned AST members.
C++ emits public virtual interface bases and small forwarding overrides as required,
preserving reference/readonly arguments, reference results, variadic packs and unique
ownership transfer. Interface cleanup uses the existing virtual nonthrowing policy.
Relationship spelling is assembled outside cached field-layout fragments, so changing
only an implements/extends edge updates headers without unnecessary layout invalidation.

Focused proofs cover cross-file signature and relationship edits, failure/retry/repair,
reset/removal and fresh/incremental agreement. Native fixture checks exercise calls and
cleanup through C++ interface views without enabling source upcasts. The final focused
run also compiles separate generated units without PCH to expose missing header dependencies.
No native compiler rebuild. See [normative scope](../../../../specs/inheritance_v0_2.md#interface-implementation).

Passed: 32 generated interface programs without PCH, 32 abstract-dispatch regression
programs and 17 PHP-host model suites. Evidence: `/tmp/interfaces-20261007-final`,
`/tmp/interfaces-abstract-20261007-1`, `/tmp/interfaces-model-20261007-2`.

The subsequent interface-handle checkpoint closes the abstract-body requirement
lookup gap using call-only lookup and reference-return cycle integration.

The subsequent shared-upcast and interface-handle checkpoints preserve the agreed
ownership/lifetime boundaries and no implicit boxing of inline structs.

### CH11-INTERFACE-FINAL-01 — inherited final virtual forwarding

Open, explicitly accepted bounded restriction; does not block this implementation slice.
If a descendant adds a new interface that needs a forwarding override of an inherited
**final virtual** method, reject during preparation. A C++ forwarding method would
override the final method and is ill-formed. Existing interfaces implemented by the
base remain valid; final nonvirtual source methods may use final forwarding overrides.
Revisit with a separate interface adapter/dispatch representation if needed, rather
than dropping finality or retroactively changing base dispatch. This is not a general
ban on inherited interface implementations.

## Abstract contracts and virtual dispatch — 2026-10-07

Implemented for classes and structs: abstract types/methods, inherited obligations,
exact override signatures, optional checked `#[\Override]`, final methods/types,
virtual calls and virtual destruction. Ordinary methods remain nonvirtual; explicit
parent calls select a concrete ancestor implementation. Abstract complete inline
values and abstract construction reject during preparation, independently of whether
C++ would consider the emitted declaration abstract. Base-subobject copying remains
available. See the [normative contract](../../../../specs/inheritance_v0_2.md).

The approved prerequisite is independent `type_contract_work` in the existing
scheduler, after relationship/layout/signature work. It owns local dispatch decisions
and unresolved original abstract declarations, not flattened inherited inventories.
Bodyless methods have signature work only. Concrete-to-abstract edits retire old body
work before frontend disposal; abstract-to-concrete edits create new body work while
preserving signature identity. C++ assembly consumes completed contracts and preserves
ordinary copy/move operations when virtual cleanup is generated.

Proof: `abstract_dispatch.php` covers class/struct execution, signature/finality edits,
failure/retry/repair, body retirement, reset and fresh/incremental agreement. Generated
programs also check C++ virtual cleanup and noncopyable payload move traits. Passed:
32 generated abstract-dispatch programs, 51 inheritance regressions, 25 destructor
regressions and 17 PHP-host model suites. No native compiler rebuild. Final evidence:
`/tmp/abstract-dispatch-20261007-7`, `/tmp/abstract-inheritance-20261007-1`,
`/tmp/abstract-destructors-20261007-2`, `/tmp/abstract-model-20261007-3`.

The subsequent interface checkpoint above extends this implementation.
Conversions, trait composition, inherited data inputs, late static binding and
non-public/abstract constructors remain separate groups.

## Parent access and constructor forwarding — 2026-10-07

Implemented for both classes and structs under the
[normative contract](../../../../specs/inheritance_v0_2.md): explicit parent ordinary
methods/constants/static properties; inherited public fixed-value constructors and
defaults; leading `parent::__construct(...)`; implicit zero-supplied-argument base
initialization with missing required inputs rejected during preparation.

The effective constructor remains the original declaration. No inherited members or
parameter schemas are copied. Base initialization belongs to body preparation and its
C++ body fragment, independently of the public signature. Braced C++ base initialization
preserves argument order without copying a complete base. `$this` is unavailable in
base arguments; private access and non-public constructor gates remain in force.

Proof: 51 generated inheritance programs, 51 constructor regressions, 34 method-return
regressions and 17 PHP-host model suites passed. Includes ordering, noncopyable base/unique input, reference-return
parent calls, multilevel/cross-file forwarding, body-only header stability, defaults,
failure/retry/repair, removal and fresh/incremental agreement. No native compiler rebuild.
The inherited signature may name a descendant without forcing a layout/signature cycle;
previously supported implicit derived zero-parameter construction remains valid.

The subsequent abstract-contract checkpoint above extends this slice. Inherited `data`
inputs, non-public/abstract constructor contracts, late `static::` and source upcasts
remain outside this slice.

## Concrete inheritance and protected access — 2026-10-07

The [normative contract](../../../../specs/inheritance_v0_2.md) enables single same-kind
class/struct bases, inherited instance/static members and public/private/protected
access. Protected instance access uses C++'s receiver restriction; broader PHP-style
family access is [CH15 discussion debt](15_blocking_debt.md#ch15-protected-01--broader-protected-receiver-access).

Prepared records retain one inline base type, not copied member inventories. Base
copy eligibility participates in inline child copying. C++ inherits from the bare
record payload, never a `value<T>`/shared wrapper; complete base headers are dependencies.
Static storage remains defined once by the original owner. Lifecycle order is base
construction before child properties/body and base cleanup after child cleanup.

At this earlier checkpoint bases required implicit default construction or an authored public zero-
parameter constructor. Base argument forwarding (including defaulted parameters),
parent calls and inherited data inputs remain deferred. Same-category ordinary
member redeclarations reject; constructors/destructors are distinct lifecycle cases.
Interface/abstract execution, overrides and derived/base conversions remain gated.

Proof: 30 generated inheritance programs, 35 generated static-member regressions and
17 PHP-host model/regression suites passed. Includes cross-file changes, collision/
visibility/final errors, failure/retry/repair and fresh/incremental output agreement.
No native compiler rebuild. The subsequent parent/constructor checkpoint above
extends construction; overriding/virtual dispatch remains deferred.

## Member-owner foundation — 2026-10-07

Records and interfaces share `collected_member_owner`, while retaining distinct
identities. Methods/constants reference their original declaring owner; interface
registration grants neither record layout nor construction capabilities. Prepared
members use public/protected/private `member_visibility` instead of a boolean.
Declared inventories remain local. Interface source execution, protected access,
relationships and inherited lookup remain gated.

Proof: 16 PHP-host model/regression suites, 35 generated static-member programs and
51 generated constructor programs passed. No native compiler rebuild. Next: prepared relationships and observed
inherited lookup, without flattening declared-member inventories.

## Qualified-member prerequisites transferred from CH10

### Relationship preparation and inherited lookup — 2026-10-07

Independent relationship work shares the existing scheduler, dependency graph,
failure/retry and retirement paths. It is separate from record layout and grants no
construction or dispatch capability. The shared parser now records extends/implements
and interface declarations, including bodyless interface methods.

Analysis checks same-kind record bases, interface targets, duplicate direct interfaces,
cycles and final-base rejection. Observed lookup searches separate member categories,
returns original declarations/owners and tracks misses. Interface diamonds deduplicate
identical declarations; implements does not manufacture method implementations.

Focused proof covers cross-file edits, failure/retry, adding/removing targets and
members, fresh/incremental agreement, reset and retirement. The existing model runner
includes `relationships.php`: 17 PHP-host suites and 35 generated CH10 static-member
programs passed on 2026-10-07.
No native compiler rebuild was performed. Inherited execution, protected access,
override compatibility, constructors, conversions and dispatch remain disabled.

Next: discuss access/override admission and the first bounded inherited execution slice.

### Remaining execution order

CH10 implements fixed declaring-type/self static methods, constants and properties.
Continue on those same member categories, in this order:

1. Establish base/interface/trait relationships and inherited member lookup.
2. Resolve inherited lookup and public/protected/private access, then `parent::`
   method/constant/property access and inherited constructor behavior.
3. Validate abstract/final/override contracts and dispatch.
4. Define late-bound `static::` calls/constants/properties and `new static(...)`.

CH10 cards CLASS-PROP-006B/006C/008/002C/002D, CLASS-CALL-005/005A/008A,
CLASS-CONST-004/005 and inheritance-dependent member modifiers track this transfer.
The CH10 reconciliation also maps CLASS-CALL-007 and CLASS-MEMBER-013 here; the
interface/abstract part of TYPE-PARAM-004 and protected parts of TYPE-PROP-001/002
share these prerequisites. Their concrete/public counterparts do not await CH11.
Concrete-type `$object::method()` is owned by CH10; its inheritance-dependent
extensions are recorded below.

### Expression-qualified static dispatch — CH10 follow-up debt, 2026-10-07

CH10's agreed bounded direction selects a static method from the qualifier's
declared concrete record type, evaluating the qualifier once before arguments.
It does not select the runtime object's concrete class or require a present handle.
Evaluate-once replaces the old S2S unevaluated `decltype(expr)` behavior and reuses
the sequenced callable path. See the [normative contract](../../../../specs/static_members_v0_2.md#expression-qualified-static-calls--2026-10-07).

After relationship/member-contract preparation is available, explicitly settle:

- Inherited static-member lookup, hiding and access through base-typed qualifiers;
  distinguish fixed declared-type selection from late-bound `static::`.
- Interface-typed qualifiers, including `produce()::method()` where `produce`
  returns an interface. A static signature requirement alone does not identify
  an implementing class's callable body. Decide whether such calls remain rejected,
  require a concrete type, or need an explicitly approved runtime dispatch protocol.
- If runtime implementation selection is adopted, define absent-handle behavior,
  dispatch representation and lowering before enabling it; do not silently extend
  the CH10 no-presence-check rule to a call needing runtime type identity.

For the bounded CH10 slice, interface/abstract dispatch is deferred and unresolved
static targets reject during preparation. No runtime static-dispatch machinery is
authorized by this note.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## v0.2 contract review debt

CH10 now owns the [bounded destructor implementation](10_objects.md#destructors-and-automatic-cleanup--2026-10-07)
and [nonthrowing lifecycle contract](../../../../specs/destructors_v0_2.md).
The imported CLASS-ABS-001/002/003/004 examples overlap that completed base slice.
CH11 must still settle base/member destruction order with inheritance, virtual
destructor policy, abstract/interface ownership and non-public destructor access;
do not create a separate destructor AST/body path or infer polymorphic deletion from
CH10's non-inheriting records.

Review the Simple C++ inheritance contracts before implementing this chapter:

- Concrete versus abstract bases, `final`, interfaces and supported inheritance relationships.
- Non-public inherited constructors: settle protected/private access, especially
  implicit child default constructors. The legacy S2S public-constructor slice
  does not resolve these cases; unknown/external constructor metadata and abstract
  constructor contracts remain outside that slice. See
  [the lowering boundary](../../../../generators/php/specs/rules_catalog.md#public-inherited-constructor-boundary).
- Derived-to-base conversions at assignment, argument and return boundaries,
  including shared handles and nullable wrappers; distinguish these from downcasts.
- Override compatibility and covariant returns, including the distinction between
  object inheritance and C++ handle return types.
- Conditional-expression result types: derived/base and sibling branches, and
  whether an explicit destination type should influence their common type.
  The current [ternary contract](../../../../specs/conditional_expression_matrix.md)
  does not use the destination type or define inheritance joins.

Reconcile normative specs, runtime, lowering and focused proofs when each decision
is agreed. This records review work, not approval to expand the language contract.
The compiler implementation's abstract-base/final-concrete convention is separate
from the inheritance forms the language may allow.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [CLASS-INH-001](#class-inh-001) | implemented-bounded | `class B extends A {}` | verified | verified | deferred | Concrete same-kind bases; [checkpoint](#concrete-inheritance-and-protected-access--2026-10-07) |
| [CLASS-INH-002](#class-inh-002) | implemented-bounded | `class A extends B implements I, J {}` | verified | verified | deferred | Shared contract validation; local/inherited implementations; final-virtual forwarding restriction |
| [CLASS-INH-003](#class-inh-003) | model-in-progress | `class A { use T; }` | syntax/identity verified | gated | deferred | Consumer-owned instance model approved; materialization and execution pending |
| [CLASS-INH-004](#class-inh-004) | implemented-bounded | `interface I { function f(int $a): int; }` | verified | verified | deferred | Public bodyless instance contracts; multiple interface inheritance |
| [CLASS-INH-006](#class-inh-006) | pending-discussion | `$a = new A(1);` | unverified | unverified | deferred | — |
| [CLASS-INH-007](#class-inh-007) | pending-discussion | `A a(1);` | unverified | unverified | deferred | — |
| [CLASS-INH-008](#class-inh-008) | implemented-bounded | `$this->f();` | verified | verified | deferred | Ordinary fixed methods; abstract contracts dispatch virtually |
| [CLASS-INH-009](#class-inh-009) | implemented-bounded | `parent::f(1);` | verified | verified | deferred | Fixed original-owner dispatch; current instance required for instance methods |
| [CLASS-INH-010](#class-inh-010) | implemented-bounded | `parent::__construct($x);` | verified | verified | deferred | First standalone statement only; sequenced base initialization; public fixed-value constructors |
| CLASS-INH-013 | implemented-bounded | `class Child extends Base {}` | verified | verified | deferred | Inherits original public concrete constructor contract/defaults through abstract or concrete ancestors |
| [CLASS-INH-011](#class-inh-011) | implemented-bounded | `#[\Override] function f(): int { return 1; }` | verified | verified | deferred | Checked inherited virtual target; exact signature; nonvirtual hiding rejected |
| [CLASS-INH-012](#class-inh-012) | pending-discussion | `class B extends A {}` | unverified | unverified | deferred | — |
| [CLASS-ABS-001](#class-abs-001) | pending-discussion | `function __destruct() { $this->cleanup(); }` | unverified | unverified | deferred | — |
| [CLASS-ABS-002](#class-abs-002) | pending-discussion | `function __destruct() {}` | unverified | unverified | deferred | — |
| [CLASS-ABS-003](#class-abs-003) | pending-discussion | `function __destruct() { return $this->x; }`<br>`function __destruct() { $this->x; }` | unverified | unverified | deferred | Source variants need reconciliation |
| [CLASS-ABS-004](#class-abs-004) | pending-discussion | `static function __destruct() {}` | unverified | unverified | deferred | Source variants need reconciliation |
| [CLASS-ABS-005](#class-abs-005) | implemented-bounded | `abstract class A {}` | verified | verified | deferred | Cannot instantiate even without abstract members; class and struct |
| [CLASS-ABS-006](#class-abs-006) | implemented-bounded | `abstract function f(int $x): int;` | verified | verified | deferred | Bodyless public/protected instance contract; concrete descendants must implement |
| [CLASS-ABS-007](#class-abs-007) | implemented-bounded | `abstract function f(): int;` | verified | verified | deferred | Pure virtual declaration; no executable body work |
| [CLASS-ABS-008](#class-abs-008) | implemented-bounded | `abstract class A { int $x; function f(): int { return $this->x; } }` | verified | verified | deferred | Concrete members/constructors remain available to descendants |
| [CLASS-ABS-009](#class-abs-009) | implemented-bounded | `interface I { function f(): int; }` | verified | verified | deferred | Pure virtual declarations and virtual cleanup; no record layout |
| [CLASS-ABS-010](#class-abs-010) | implemented-bounded | `class A extends B implements I {}` | verified | verified | deferred | Source identities retained; public inherited forwarding; shared virtual interface bases |
| [TYPE-PARAM-003G](#type-param-003g) | implemented-bounded | `interface I { public function f(const string &$s): int; } class C implements I { public function f(string $s): int { return 1; } }` | verified | verified | deferred | Rejected during shared preparation; exact readonly/reference signature contract |
## CLASS-INH-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:24](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:204](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class B extends A {}
```

**Existing C++ lowering / result**

```cpp
class B : public A { public: using base = A; };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parent class is explicitly present in source form

**Normalized pattern:** `class <name> extends <parent>`

**General rule:** Single class inheritance is supported and lowers to public C++ inheritance.

**Diagnostics:** Error if more than one parent class is declared.

**Notes:** Also emits `using base = Parent;`.


## CLASS-INH-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:25](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:205](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A extends B implements I, J {}
```

**Existing C++ lowering / result**

```cpp
class A : public B, public I, public J { public: using base = B; };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** one parent class; zero or more interfaces

**Normalized pattern:** `class <name> extends <parent> implements <iface-list>`

**General rule:** A class may implement multiple interfaces; interfaces lower as additional public bases.

**Diagnostics:** Error if trait syntax is used.

**Notes:** Interface lowering is declaration-only.


## CLASS-INH-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:26](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:206](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { use T; }
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** `trait use`

**General rule:** Traits are not supported.

**Diagnostics:** Emit an error for any trait declaration or trait use.

**Notes:** Explicit project decision.


## CLASS-INH-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:27](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:207](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
interface I { function f(int $a): int; }
```

**Existing C++ lowering / result**

```cpp
class I { public: virtual int_t f(int_t a) = 0; virtual ~I() = default; };
```

**Category:** Interface

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** interface methods are declaration-only

**Normalized pattern:** `interface <name> { <methods> }`

**General rule:** A PHP interface lowers to a C++ class containing pure virtual methods.

**Diagnostics:** Error if interface members include bodies or fields.

**Notes:** Multiple interface inheritance remains allowed on implementing classes.


## CLASS-INH-006

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:28](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:208](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = new A(1);
```

**Existing C++ lowering / result**

```cpp
auto a = create<A>(static_cast<int_t>(1));
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** target is a user PHP class

**Normalized pattern:** `new <class>(<args>)`

**General rule:** User-defined PHP class construction lowers to `create<Class>(...)`.

**Diagnostics:** Error if raw `new` or direct stack construction is emitted.

**Notes:** Constructor arguments are forwarded to `create<T>(...)`.


## CLASS-INH-007

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:29](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:209](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
A a(1);
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** target is a user PHP class

**Normalized pattern:** direct native construction of user class

**General rule:** Direct construction of user-defined PHP classes is forbidden in generated Prism++.

**Diagnostics:** Emit an error if codegen attempts direct construction for user PHP classes.

**Notes:** Whitelisted native/runtime value types remain separate.


## CLASS-INH-008

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:30](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:210](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$this->f();
```

**Existing C++ lowering / result**

```cpp
this->f();
```

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current context is an instance method, constructor, or destructor

**Normalized pattern:** `$this-><member>`

**General rule:** `$this` is valid only in instance methods, constructors, and destructors.

**Diagnostics:** Error if `$this` appears in static methods or outside instance context.

**Notes:** Applies to properties and methods.


## CLASS-INH-009

**v0.2 decision / target C++:** Explicit original-owner call, using the current
instance for nonstatic methods; inherited access checks apply. See the parent checkpoint.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:31](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:211](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
parent::f(1);
```

**Existing C++ lowering / result**

```cpp
base::f(static_cast<int_t>(1));
```

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class has a parent and therefore a `using base = Parent;` alias

**Normalized pattern:** `parent::<method>(<args>)`

**General rule:** Parent method calls lower to `base::method(...)`.

**Diagnostics:** Error if `parent::...` is used in a class without a parent.

**Notes:** The alias is emitted in the class body.


## CLASS-INH-010

**v0.2 decision / target C++:** Leading standalone call becomes a braced base
initializer with shared argument conversions/defaults. No post-construction call.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:32](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:212](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
parent::__construct($x);
```

**Existing C++ lowering / result**

```cpp
A::A(int_t x) : base(x) {}
```

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class has parent constructor call in its constructor

**Normalized pattern:** `parent::__construct(<args>)`

**General rule:** Parent constructor calls lower to a base initializer call.

**Diagnostics:** Error if base initializer cannot be placed because the construct is outside a constructor.

**Notes:** Uses the approved `base` alias convention.


## CLASS-INH-011

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:33](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:213](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
#[\Override] function f(): int { return 1; }
```

**Existing C++ lowering / result**

```cpp
int_t f() override;
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** method declaration carries `#[\Override]` attribute

**Normalized pattern:** `#[\Override] <method>`

**General rule:** `override` is emitted only from explicit `#[\Override]`; the generator does not infer override relationships.

**Diagnostics:** No semantic override validation is performed by S2S.

**Notes:** Explicit user annotation is trusted as-is.


## CLASS-INH-012

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:34](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:214](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class B extends A {}
```

**Existing C++ lowering / result**

```cpp
class B : public A { public: using base = A; };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** class has a parent class

**Normalized pattern:** `class <name> extends <parent>`

**General rule:** If a class has a parent, emit `using base = Parent;` in the class body.

**Diagnostics:** Error if `parent::...` lowering is requested without the alias being present.

**Notes:** Provides a deterministic local base name.


## CLASS-ABS-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:35](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:215](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function __destruct() { $this->cleanup(); }
```

**Existing C++ lowering / result**

`~A();` in header and `A::~A() { this->cleanup(); }` in source

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** declaration occurs inside class body

**Normalized pattern:** `__destruct`

**General rule:** `__destruct` lowers to a C++ destructor with out-of-line body emission.

**Diagnostics:** Error if parameters or return type are present on destructor.

**Notes:** `$this` is valid in the destructor body.


## CLASS-ABS-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:36](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:216](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function __destruct() {}
```

**Existing C++ lowering / result**

header declaration + source definition

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** destructor is user-defined

**Normalized pattern:** destructor declaration/body split

**General rule:** Destructor declaration goes to header and destructor body goes to source.

**Diagnostics:** Error if destructor body is emitted inline.

**Notes:** Matches the general class split model.


## CLASS-ABS-003

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:37](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
function __destruct() { return $this->x; }
```

**Existing C++ lowering / result**

`$this` lowers normally; semantic body rules apply separately

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** destructor body is otherwise valid

**Normalized pattern:** `$this` inside destructor

**General rule:** `$this` is allowed inside destructors.

**Diagnostics:** Error only if `$this` appears outside valid instance context.

**Notes:** This extends the `$this` rule explicitly to destructors.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:217](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function __destruct() { $this->x; }
```

**Existing C++ lowering / result**

`$this` lowers normally inside the destructor body

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** destructor body is otherwise valid

**Normalized pattern:** `$this` inside destructor

**General rule:** `$this` is allowed inside destructors.

**Diagnostics:** Error only if `$this` appears outside valid instance context.

**Notes:** This extends the `$this` rule explicitly to destructors.


## CLASS-ABS-004

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:38](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
static function __destruct() {}
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** static destructor

**General rule:** Destructors are never static.

**Diagnostics:** Emit an error for any static destructor form.

**Notes:** Directly approved.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:218](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
static function __destruct() {}
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** static destructor

**General rule:** Destructors are never static.

**Diagnostics:** Emit an error for any static destructor form.

**Notes:** Explicit project decision.


## CLASS-ABS-005

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:39](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:219](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
abstract class A {}
```

**Existing C++ lowering / result**

`class A { public: };` with abstract semantics contributed by pure virtual members

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** class uses `abstract` keyword

**Normalized pattern:** `abstract class <name>`

**General rule:** Abstract classes are supported.

**Diagnostics:** Error only if later members violate abstract/class member rules.

**Notes:** Abstractness is represented through pure virtual members.


## CLASS-ABS-006

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:40](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:220](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
abstract function f(int $x): int;
```

**Existing C++ lowering / result**

```cpp
virtual int_t f(int_t x) = 0;
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** abstract method declared in abstract-capable context

**Normalized pattern:** `abstract function <name>(...) : <type>;`

**General rule:** Abstract methods lower to pure virtual C++ methods.

**Diagnostics:** Error if an abstract method includes a body.

**Notes:** No source definition is emitted.


## CLASS-ABS-007

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:41](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:221](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
abstract function f(): int;
```

**Existing C++ lowering / result**

declaration only in header; no `.cpp` body

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** abstract method is syntactically valid

**Normalized pattern:** abstract method declaration

**General rule:** Abstract methods have declarations only and no source body.

**Diagnostics:** Error if generator emits an implementation body.

**Notes:** Aligned with pure virtual lowering.


## CLASS-ABS-008

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:42](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:222](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
abstract class A { int $x; function f(): int { return $this->x; } }
```

**Existing C++ lowering / result**

fields + ctor/dtor + concrete methods remain allowed

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** class remains syntactically valid

**Normalized pattern:** abstract class with concrete members

**General rule:** Abstract classes may contain fields, constructors, destructors, and concrete methods.

**Diagnostics:** Error only on unsupported member forms.

**Notes:** Approved explicitly.


## CLASS-ABS-009

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:43](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:223](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
interface I { function f(): int; }
```

**Existing C++ lowering / result**

pure virtual interface class

**Category:** Interface

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** interface syntax is valid

**Normalized pattern:** interface declaration

**General rule:** Interfaces remain distinct from abstract classes.

**Diagnostics:** Error if interface tries to include fields or bodies.

**Notes:** Distinction preserved in the catalog.


## CLASS-ABS-010

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:44](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:224](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A extends B implements I {}
```

**Existing C++ lowering / result**

`virtual ~A();` declaration policy where applicable

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** class has parent or implemented interfaces

**Normalized pattern:** polymorphic class destructor policy

**General rule:** If a class has a parent or interfaces, emit a virtual destructor as generation policy.

**Diagnostics:** No hierarchy inference beyond local declaration form.

**Notes:** This is a generation policy, not semantic checking.


## TYPE-PARAM-003G

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:338](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
interface I { public function f(const string &$s): int; } class C implements I { public function f(string $s): int { return 1; } }
```

**Existing C++ lowering / result**

```cpp
STAN ERROR
```

**Category:** Type system

**Rule kind:** STAN diagnostic

**Source support status:** supported-with-diagnostic

**Preconditions:** interface or abstract method contract contains a const parameter and the implementation differs in constness

**Normalized pattern:** `const parameter contract`

**General rule:** Constness is part of function-like parameter contract metadata for interface and abstract method matching.

**Diagnostics:** STAN must report a build-blocking contract mismatch when an implementation drops or adds source-level const compared with the declared interface or abstract method parameter.

**Notes:** This does not turn the generator into a full overload resolver.
