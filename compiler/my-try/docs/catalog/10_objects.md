# 10. Classes, properties, construction and methods
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires functions, type identities and ownership. Start with a plain class before methods and lifecycle combinations.

Chapter closeout must include the [test-duration review](#ch10-perf-01--test-duration-review-before-native-build)
before starting the chapter's native compiler build.

## Shared declaration model and class/struct parity

Agreed direction (2026-10-06): develop `struct` alongside `class` throughout this
chapter. Every class slice (properties, construction, methods and lifecycle) must
include its struct counterpart and corresponding proofs, not leave structs for a
second implementation pass. Use the same AST node types, with an explicit
class/struct flag identifying the declaration form; do not create parallel AST
families or duplicate processing paths.

The October 7 [shared declaration agreement](../planning/type_declarations.md)
extends the single concrete node to classes, structs, interfaces and traits, with
separate typed property/method/constant/trait-use collections. Functions/methods and
namespace/member constants share abstract syntax bases. Shared syntax does not erase
storage, ownership, visibility or semantic differences.

The bounded model migration is implemented: existing structs, functions and source
constants use the new structures. The focused
15-suite PHP-host proof passes under `/tmp/my-try-ch10-model-final-20261007/`, including
incremental identity, traversal, unsupported-member rejection and C++ emission checks.
This model proof is not a conversion, generated-C++ execution or native compiler proof.
Interface/trait execution remains a follow-up; method/constant lookup is covered below.

## Plain records and storage — 2026-10-07

Implemented plain classes and public typed instance properties alongside structs.
Both use `collected_record`, common declaration preparation and property lookup.
Duplicate/unknown properties reject in preparation. Zero-argument construction follows
the [amended storage matrix](../../../../specs/type_use_modifiers.md#v02-bounded-owned-construction--2026-10-06):
bare class is shared, bare struct is inline; meaningful explicit storage overrides
share one construction path. `shared<Class>` and `value<Struct>` reject as redundant.
Weak references observe shared allocations and must be locked before member access.

Heap-backed properties are writable through stable receiver paths, but native
aliases/reference arguments to them reject; nested inline projections preserve this
restriction. Inline properties retain existing stable-borrow rules. At that checkpoint property defaults,
non-public/static members, methods, inheritance and constructor arguments remain gated.

Focused evidence: `/tmp/ch10-records-final/` (16 generated programs plus rejection and
incremental checks), `/tmp/ch10-records-ownership-02/` (14 PHP suites and 26 generated
programs), `/tmp/ch10-records-regress-final/` (15 focused PHP suites). These are PHP-host
compiler / generated-program proofs, not a rebuilt-native-compiler checkpoint.
The chapter-end performance review still precedes any native compiler build.

## Property defaults and data-construction agreement — 2026-10-07

The authoritative [data-record amendment](../../../../specs/data_initialization.md#31a-explicit-data-classes-and-structs--2026-10-07)
records approved `data class` / `data struct` opt-in. Authored constructors consume
literal keys by parameter name and retain their body/invariants. Without a constructor,
preparation synthesizes typed public-property inputs; lowering emits the constructor.
The input contract is heterogeneous and must not be disguised as a mixed-valued
collection factory. Ordinary property defaults are independent of `data` opt-in.

First implemented checkpoint: public explicitly typed property defaults for both
classes and structs. Scalar literals, numeric signs, scalar source/imported constants,
permitted null and exact enum cases use shared constant evaluation/conversion and
immutable declaration-owned snapshots. Defaults emit as C++ member initializers and
participate in declaration equivalence/dependency invalidation. A field with an explicit
default satisfies construction without requiring an implicit type default.

Focused proof: `/tmp/ch10-property-defaults-final/` (17 generated programs, unused
invalid declaration rejection, cross-file constant edits, failure/retry/repair,
default replacement/removal, fresh/incremental and cleanup agreement).
`/tmp/ch10-property-defaults-regress-03/` covers the 15 focused PHP regression suites.
Tests used the runner's CLI fallback because sandbox socket binding prevented FPM.
No conversion/native compiler rebuild is claimed.

At that checkpoint the remaining bounded slices were:

1. Typed container/nested-record default construction, preserving per-instance behavior.
2. `data` syntax and prepared typed construction contracts; synthesized keyed record
   execution, literal-key diagnostics and source-order evaluation without mandatory boxing.
3. Authored constructors and ordinary method support; keyed inputs reuse
   constructor contracts rather than bypassing their bodies.

Arbitrary calls, `$this`/sibling defaults, computed record keys, positional record
notation, inheritance/traits/non-public members and competing explicit keyed factories
remain gated. The following checkpoint implements the first two follow-ups.

## Container defaults and synthesized data construction — 2026-10-07

Implemented typed construction defaults for vector, hash, mixed and dynamic through
their existing common factory path, plus nested opted-in records. Defaults admit
constant leaves, execute per instance and preserve value/COW versus shared identity;
separately constructed objects do not share a mutable default object.

`data class` and `data struct` now use the same declaration modifier and prepared
heterogeneous property-input contract. Both `{name: value}` and `["name" => value]`
use the existing construction AST/parser. Literal names map to public properties;
supplied expressions run once in source order, omitted defaults follow property order,
and a generated tagged constructor initializes all members directly. Explicit values
suppress their defaults. Empty `[]`/`{}` use defaults; required enum/non-defaultable
inputs must be supplied. Duplicate/unknown/computed keys, incompatible inputs, missing
required inputs, absent opt-in and weak allocation reject during preparation.

The existing storage matrix applies without mixed boxing: bare class/struct plus
meaningful inline/shared/unique targets, with nested contextual construction and
ordinary value call/return boundaries. Ordinary zero-argument `new` keeps its default
construction behavior and rejects required omissions. That checkpoint did not expose positional
constructor arguments, authored constructor bodies, promotion or methods. Explicit
move expressions into record fields remain gated; fresh exact owned construction and
ordinary copyable inputs use existing value-boundary admission.

Proofs: `/tmp/ch10-data-records-final/` (28 generated record programs plus rejection,
cross-file schema/default edits, opt-in removal, failure/retry/repair, fresh/incremental
and cleanup agreement); `/tmp/ch10-container-defaults-final/` (23 default programs);
`/tmp/ch10-data-records-regress-02/` (5 PHP suites, 48 generated container programs and
one runtime input-lifetime proof); `/tmp/ch10-data-declarations-regress-01/` (15 PHP
suites). Shared runner/PCH used with CLI fallback on denied FPM sockets. These prove
PHP-host compilation and generated programs, not a native compiler rebuild/conversion.

Construction-default declaration rebuilds conservatively invalidate consumers; finer
effective-equivalence reuse is optimization debt, not a second retained snapshot tree.
Authored constructors with property-backed inputs and bounded instance methods are implemented below. PHP-style
property promotion is deferred to CH15-CTOR-SYNTAX-01. CH10-PERF-01 still precedes the
chapter's native compiler build. Reference tags/data-set references remain non-blocking
[CH15-DATA-REF-01](15_blocking_debt.md#ch15-data-ref-01--initialization-with-references-into-the-input-data-set).

### Source-callable lifecycle foundation — 2026-10-07

The approved constructor prerequisite generalizes retained source-callable identity,
signature facts, signature/body work, cleanup and retirement. Functions and members
share `collected_source_callable`, `prepared_source_callable`,
`source_callable_signature_work` and `source_callable_body_work`. Member identities
observe their declaring record and have a separate scope index: they never enter
namespace publication or free-function lookup. The common callable AST owns facts.
Workers continue to own all processing; no visitor or model processing hook is added.

The focused function runner passes 12 PHP suites and 77 generated programs
(`/tmp/ch10-callable-foundation-01`). Member identity/index/retirement checks live in
`tests/preparation_work.php`; the model checks passed in
`/tmp/ch10-callable-model-02`. These are PHP-host/generated-program proofs, not a native
compiler build. Constructor parsing and execution are implemented in the checkpoint below.

### First authored-constructor slice: adopted input spelling

User decision, 2026-10-07: implement `__construct(set:$name, set:$age)` from the
first constructor slice, resolving separately declared instance properties and their
types/explicit defaults. `set` is contextual, not a reserved type name. Explicit
parameter defaults override property defaults: `set:$age = 21`. Without either
explicit default, the input is required. This replaces the earlier `use` proposal,
not an additional syntax alias. Ordinary typed parameters can coexist with these inputs.
Use the [authoritative contract](../../../../specs/data_initialization.md#property-backed-constructor-parameters).
Public constructor inputs may initialize private properties without exposing those
properties for external access; basic visibility enforcement is a prerequisite.
Without an authored constructor, synthesized inputs remain public-instance-only.
PHP-style property promotion from parameter declarations is non-blocking
[Chapter 15 debt](15_blocking_debt.md#ch15-ctor-syntax-01--deferred-constructor-property-promotion),
not part of this first slice. Execution is implemented in the bounded checkpoint below.

### Authored constructors — 2026-10-07

One public `__construct`, ordinary fixed by-value parameters, contextual `set:$property`
inputs, supported constant/data defaults, positional `new`, and keyed `data` calls
now use the shared class/struct path. Constructors have no source return type.
Public/private instance properties share field facts and preparation-owned visibility.
`$this->property` reads/plain writes are supported in the constructor body; receiver
storage is non-borrowable and cannot be copied, rebound or escaped as `$this`.

Source constructor signatures own their named typed input schema. Synthesized
schemas expose public properties only. Both use `record_initialization_input`
referencing ordinary callable parameter contracts, with optional property mappings
only for synthesis. `set:` source parameters separately retain the resolved property
they initialize, without manufacturing/promoting properties. Parameter defaults use
the shared typed property-default machinery; ordinary function default admission
is unchanged. A property-backed input requires copyable storage because its parameter
remains independently usable after member initialization (no hidden move).

Supplied expressions are snapshotted in source order, followed by omitted defaults
in parameter order. Native constructors initialize `set:` properties directly before
executing the body; supplied inputs skip property defaults. Both construction forms
share storage/allocation lowering. Type headers contain member declarations, while
constructor definitions/bodies live in the existing per-source implementation unit.
Body-only edits preserve signature work, callers and headers. Constructor removal
retires both work identities and their fragments.

Legacy review: CLASS-MEMBER-011 and CLASS-CONSTRUCT-001 use real C++ constructors with
out-of-line bodies, which this slice preserves. Unlike the type-blind legacy path,
preparation resolves visibility, field/input bindings, conversions and missing inputs
before emission. No promotion expansion or second class/struct execution path is added.

Focused proof: `tests/records.py --suite constructors`; generated programs, contextual
`set` type naming, namespace/default edits, failed retry/repair, fresh/incremental
equality, preparation reset and method retirement. Ordinary function and synthesized
record regression groups also pass. These are PHP-host/generated-program checks;
the native compiler has **not** been rebuilt.

Evidence: 45 constructor programs pass with PCH (`/tmp/ch10-constructors-final-03`)
and without PCH (`/tmp/ch10-constructors-headers-final`). The final focused PHP run
passes 14 suites (`/tmp/ch10-constructor-php-final`); regression runs pass 77 function
programs (`/tmp/ch10-constructor-functions-final`) and 28 synthesized-data programs
(`/tmp/ch10-constructor-data-final`). The shared runner used CLI fallback because
FPM socket startup was unavailable.

Remaining constructor gates: reference/variadic parameters, inheritance/parent calls,
promotion, receiver escape, and implicit default initialization of
inline records with authored constructors. For that last case use explicit `new C()`
or eligible `data` notation; unsupported implicit sites reject during preparation,
even if every authored input has a default. Required non-defaultable properties must
use `set:` or a property default; body-only definite initialization is deferred.
Indexed/compound property updates retain their existing separate gates.

## Instance methods — 2026-10-07

Implemented fixed public/private instance methods for classes and structs together.
`$object->method(...)` and `$this->method(...)` use `call_node` with an optional
receiver, neutral collected call occurrences, common source-callable work and the
ordinary argument/default/return path. Property and method names remain separate;
C++ uses separate field/method names and emits method bodies out of line.

Preparation owns visibility, receiver access and lifetime admission. Shared receivers
are retained/checked before arguments; inline whole-storage receivers are borrowed,
not copied. Read-only receivers reject until a declared read-only-method contract
exists. Unique/weak receivers, projected inline receivers and inline temporaries
remain gated; explicit weak locking feeds the shared path. The authoritative
[instance-method contract](../../../../specs/instance_methods_v0_2.md) and
[CH15 receiver debt](15_blocking_debt.md#ch15-method-lifetime-01--remaining-instance-receiver-lifetimes)
record the boundary. Ordinary methods do not accept constructor `set:` parameters.

Legacy review: CLASS-CALL-001/004 and CLASS-MEMBER-001/014/016 retain fixed calls,
visibility, defaults and out-of-line C++ definitions. Unlike type-blind v0.1, shared
preparation selects the method and rejects unsupported access/lifetimes before C++.
The existing arithmetic operand-order gates are unchanged; materialize property/call
reads into locals where required rather than inferring method-body effects.

Proofs: `/tmp/ch10-methods-final` passes 26 generated programs without PCH, including
shared retention during argument-side handle reset, receiver/argument ordering,
checked absence, constructor calls, recursive methods, private peer access, storage
variants and cross-file incremental/default/visibility recovery. Model regression
`/tmp/ch10-methods-model-01` passes 15 PHP suites. Final focused checks
(`/tmp/ch10-methods-php-final`, 9 suites) also cover method retirement/re-addition and
method-mediated reference-return cycle rejection. Constructor regression
(`/tmp/ch10-methods-constructors`) passes 45 generated programs. These prove PHP-host compilation
and generated execution, not a native compiler rebuild.

Method reference returns are covered by the later checkpoint below.
Expression-qualified static calls are covered below. Inheritance/interface/trait behavior belongs to CH11.
CH10-PERF-01 still precedes the chapter's native build.

## Static members — 2026-10-07

Implemented the [bounded static-member contract](../../../../specs/static_members_v0_2.md)
for both classes and structs: fixed type/self method calls, scalar member constants,
and type/self property reads and plain writes. Shared modifier/access validation feeds
existing method, constant and property preparation. Static is a flag, not a new AST
family. Type-owned fields are excluded from instance layout/copy capability and all
constructor/data inputs. C++ headers declare storage; the declaring source unit owns
its single definition. Constants reuse the existing bounded evaluator (general
arithmetic folding is not newly enabled).

Qualified lookup first resolves nominal identity, without requiring instance layout;
this allows self constants in defaults without artificial declaration cycles. Member
constants retain their own declaration work, dependency observations and visibility
equivalence. Static signatures still use the common source-callable lifecycle.

Focused proof: `/tmp/ch10-static-final-02/` contains 35 generated programs compiled without
PCH, plus rejection, cross-file visibility/storage-kind invalidation, retry/repair,
fresh/reset and retirement/readdition checks. `/tmp/ch10-static-model-02/` passes
15 focused PHP suites. `/tmp/ch10-static-methods/` passes 26 existing instance-method
programs; `/tmp/ch10-static-constructors/` passes 45 constructor programs.
`/tmp/ch10-static-php-final/` passes 11 final focused PHP checks, including Result/take
and installed readonly constants. These are not a native compiler rebuild.

Still gated: static-property reference escapes, compound updates, executable static
initializers. Expression-qualified static methods, properties and constants are
covered below. Inherited lookup,
`parent::`, protected/abstract/final/override semantics, late-bound `static::` and
inherited construction transfer to the [ordered CH11 prerequisites](11_inheritance.md#qualified-member-prerequisites-transferred-from-ch10).

## Shared method parameters — 2026-10-07

Ordinary instance/static methods on classes/structs now admit the existing function
parameter contract: writable and read-only fixed references and typed trailing
variadics, alongside existing value parameters/defaults. The implementation removes
the method-only admission gate; `Declaration_Preparation`, `Call_Preparation`,
`Value_Boundary_Preparation` and the sequenced C++ call emitter already own the full
path. No new AST/prepared representation or separate method packing is needed.

Legacy review: `Generator::renderCallArgsForParams` shares function/method packing;
the v0.1 emitter borrows its pack as `const vector_t<T>&`. v0.2 preserves the approved
CH04 owned-vector contract instead. Existing CH07 native-reference eligibility and
read-only rules apply unchanged. The [authoritative amendment](../../../../specs/instance_methods_v0_2.md#shared-method-parameters--2026-10-07)
keeps receiver lifetimes, constructors and reference returns outside this extension.

`records.py --suite method_parameters --no-pch` owns focused class/struct ×
instance/static behavioral proofs, invalid-borrow/signature rejection, reference-only
and vector-to-variadic signature invalidation, repair, fresh/reset agreement, and
shared receiver retention when a reference argument resets its originating handle.
Evidence: `/tmp/ch10-method-parameters-final/` passes 50 generated programs without
PCH; `/tmp/ch10-method-parameters-regression/` passes 9 focused PHP suites. The runner
used CLI fallback because sandbox socket binding prevented FPM startup. No compiler
native rebuild or conversion was performed.
Reference returns are covered by the next checkpoint; receiver/property escapes remain gated.

## Same-origin method returns — 2026-10-07

Instance/static methods on both classes and structs now share CH07's exactly-one
writable-reference-parameter return contract. Direct returns, local aliases and
nonrecursive function/method forwarding preserve caller-owned storage. Existing
completion, exact-type, copy-capability, escape and recursion checks remain shared.
No new retained model or method-specific reference analysis was added. C++ callable
signatures now share their return prefix, including reference spelling and layout
requirements. Receiver/property lifetime support is not broadened.

Ordinary assignment copies with the type's normal value/COW/shared-handle semantics;
explicit `=&` aliases storage. Noncopyable value copies reject during preparation.
The [normative contract](../../../../specs/instance_methods_v0_2.md#same-origin-method-reference-returns--2026-10-07)
records these consequences. `records.py --suite method_returns --no-pch` covers
class/struct × instance/static calls, forwarding, alias/copy behavior, receiver
release, invalid escapes/cycles and cross-file invalidation/retry/repair/reset.

Legacy evidence preserves explicit reference markers without proving provenance;
v0.2 additionally checks same-origin safety during shared preparation. Focused
evidence `/tmp/ch10-method-returns-1/` passes 34 generated programs without PCH.
`/tmp/ch10-method-returns-regression/` passes 10 focused PHP suites, including
constructors, method parameters, reference forwarding and preparation recovery.
This proves PHP-host compilation and generated execution, not a native compiler build.

Expression-qualified static calls are covered below. Property reference returns,
multiple origins and broader lifetimes remain outside this checkpoint.

## Expression-qualified static calls — 2026-10-07

`$object::method()` and `produce()::method()` reuse the ordinary static callable
selection, argument and return path for concrete class/struct types. Parser syntax
distinguishes `::` from `->` on the common call node; prepared calls record discarded
qualifier evaluation separately from an instance binding. Generation uses the
existing sequenced-call prefix, not a second static-call implementation.

The approved [contract amendment](../../../../specs/static_members_v0_2.md#expression-qualified-static-calls--2026-10-07)
evaluates the qualifier exactly once before arguments, releasing its temporary
result before argument evaluation. This replaces v0.1's unevaluated `decltype`.
Qualification requires no copying, dereference, lock or presence check, including
read-only and shared/unique/weak/inline record storage. Ordinary effects/checks
inside the qualifier still execute. Fixed static target and visibility checks stay
in preparation. Dynamic names and fat-value qualifiers remain gated. Expression-qualified
properties/constants are covered below. Interface/inherited/late-bound selection is
tracked in [CH11](11_inheritance.md#expression-qualified-static-dispatch--ch10-follow-up-debt-2026-10-07).

Focused runner: `records.py --suite qualified_static_calls --no-pch`, covering
class/struct storage, evaluation order/count, absent handles, noncopyable qualifiers,
temporary release, reference returns, variadics, private access and cross-file
static/visibility edits, retries, repairs and fresh/reset equivalence.

Evidence: `/tmp/ch10-qualified-static-final/` passes 25 generated programs without
PCH; `/tmp/ch10-qualified-static-regression/` passes 11 focused PHP suites. The
shared runner used CLI fallback because sandbox socket binding prevented FPM startup.
No native compiler rebuild or conversion was performed.

## Expression-qualified static properties and constants — 2026-10-07

The shared static-member AST now owns either a type spelling or an expression
qualifier, with both forms participating in inspection and lifecycle maintenance.
Preparation uses the same constant/property lookup, visibility and assignment paths.
Compile-time-only consumers explicitly require type qualification, preventing loss
of expression effects in defaults, constant declarations and switch/match labels.

Reads evaluate/discard the qualifier before selecting the member, without forcing
a copy or exposing a new source reference capability. Plain writes emit the qualifier
before RHS evaluation/conversion and commit to the existing static storage only on
success. The [normative contract](../../../../specs/static_members_v0_2.md#expression-qualified-properties-and-constants--2026-10-07)
includes absent handles, read-only qualifiers, no rollback of prior effects and
the continued compound/reference/dynamic-name gates. No new runtime member family
or alternate static storage implementation was introduced.

Focused runner: `records.py --suite qualified_static_members --no-pch`. Inherited
and interface-qualified selection remains CH11 work; this does not close CH10.

Evidence: `/tmp/ch10-qualified-members-final/` passes 27 generated programs without
PCH, including native exception-boundary assertions that qualifier/RHS failure
preserves the static value and earlier effects. `/tmp/ch10-qualified-members-regression/`
passes 12 focused PHP suites. No native compiler rebuild or conversion was performed.

## Chapter 09 carryovers

CH09's approved bounded closeout transfers constructor integration for explicit
`value<T>` (TYPE-VAR-006), nullable inline records (TYPE-VAR-006A), and discussion of
bare `value`/`shared`/`unique` constructor shortcuts (TYPE-VAR-007) here. Preserve
existing struct value/copy behavior; do not mark argument-bearing construction or
nullable record operations complete from those earlier proofs. Explicit types remain
the current boundary until shortcuts are agreed. Include both class and struct forms.
Enum methods/interfaces (ENUM-004) must revisit the shared CH10/11 foundations,
not establish a parallel enum-only method system in CH09.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [CH10-DATA-001](#authored-constructors--2026-10-07) | supported-bounded | `data struct A { int $x; } $a A = {x: 1};` | prepared | verified | deferred | Shared synthesized/authored typed inputs; `records.py --suite constructors` |
| [CLASS-DECL-001](#class-decl-001) | supported-bounded | `class A {}` | prepared | verified | deferred | Plain class/struct records; `tests/records.py` |
| [CLASS-PROP-001](#class-prop-001) | pending-discussion | `class A { int $x; }` | unverified | unverified | deferred | Source variants need reconciliation |
| [CLASS-PROP-002](#class-prop-002) | supported-bounded | `class A { int $x = 1; }` | prepared | verified | deferred | Class/struct constant and data-construction defaults; `records.py --suite property_defaults` |
| [CLASS-PROP-003](#class-prop-003) | supported-bounded | `class A { int $x; }` | prepared | verified | deferred | Public typed instance property; no initializer |
| [CLASS-PROP-004](#class-prop-004) | supported-bounded | `$this->x = 1;` | prepared | verified | deferred | Constructor bodies; general method execution deferred |
| [CLASS-PROP-005](#class-prop-005) | supported-bounded | `$a->x = 1;` | prepared | verified | deferred | Stable receiver; heap interiors not borrowable |
| [CLASS-PROP-006](#class-prop-006) | supported-bounded | `A::$x = 1;` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-PROP-006A](#class-prop-006a) | supported-bounded | `self::$x = 1;` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-PROP-006B](#class-prop-006b) | deferred-CH11 | `parent::$x = 1;` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-PROP-006C](#class-prop-006c) | deferred-CH11 | `static::$x = 1;` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-PROP-007](#class-prop-007) | supported-bounded | `$a::$x = 2;` | shared static member | qualifier before RHS | deferred | Plain writes/read/constant checkpoint; no compound/reference forms |
| [CLASS-PROP-008](#class-prop-008) | deferred-CH11 | `class B extends A { function f(): void { $this->x = 1; } }` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-PROP-009](#class-prop-009) | pending-discussion | `$this->y = 1;` where `y` is undeclared | unverified | unverified | deferred | — |
| [CLASS-PROP-010](#class-prop-010) | pending-discussion | `$this->$name = 1;` | unverified | unverified | deferred | — |
| [CLASS-PROP-011](#class-prop-011) | supported-bounded | `class A { public int $x = 1; public static int $s = 2; }` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-CALL-001](#class-call-001) | supported-bounded | `$a->f(1);` | prepared | verified | deferred | Fixed instance slice; receiver lifetime gates apply |
| [CLASS-CALL-002](#class-call-002) | supported-bounded | `A::f(1);` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-CALL-003](#class-call-003) | supported-bounded | `$a::f(1);` | fixed concrete target | evaluate once before arguments | deferred | Expression-qualified static-call checkpoint |
| [CLASS-CALL-004](#class-call-004) | supported-bounded | `$this->f(1);` | prepared | verified | deferred | Fixed instance slice; receiver lifetime gates apply |
| [CLASS-CALL-005](#class-call-005) | deferred-CH11 | `parent::f(1);` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-CALL-005A](#class-call-005a) | deferred-CH11 | `static::f(1);`<br>`static::f(1)` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-CALL-006](#class-call-006) | pending-discussion | `function f(): int { return 1; }` | unverified | unverified | deferred | — |
| [CLASS-CALL-007](#class-call-007) | pending-discussion | `$i->f(1);` | unverified | unverified | deferred | — |
| [CLASS-CALL-008](#class-call-008) | supported-bounded | `new A(1)` | prepared | verified | deferred | Fixed public constructor; class/struct and storage variants |
| [CLASS-CALL-008A](#class-call-008a) | deferred-CH11 | `new static(1)` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-CALL-009](#class-call-009) | pending-discussion | `$a->$name();` | unverified | unverified | deferred | — |
| [CLASS-CALL-010](#class-call-010) | pending-discussion | `[$a, 'f']` | unverified | unverified | deferred | — |
| [CLASS-CALL-011](#class-call-011) | pending-discussion | explicit destructor invocation pattern | unverified | unverified | deferred | — |
| [CLASS-CALL-012](#class-call-012) | supported-bounded | `static function g(int $x): int { return $x; }` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-CONST-001](#class-const-001) | supported-bounded | `class A { public const X = 1; }` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-CONST-002](#class-const-002) | supported-bounded | `A::X` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-CONST-003](#class-const-003) | supported-bounded | `self::X` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-CONST-004](#class-const-004) | deferred-CH11 | `parent::X` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-CONST-005](#class-const-005) | deferred-CH11 | `static::X` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-MEMBER-001](#class-member-001) | supported-bounded | `function f(int $a): int { return $a; }` | prepared | verified | deferred | Fixed instance slice; receiver lifetime gates apply |
| [CLASS-MEMBER-001B](#class-member-001b) | pending-discussion | `function f(vector<int> $a): int { return 1; }` | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [CLASS-MEMBER-002](#class-member-002) | pending-discussion | `function f(int $a): int { return $a; }` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-003](#class-member-003) | pending-discussion | `function f(int $value): int { return $value; }` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-004](#class-member-004) | supported-bounded | `static function f(int $a): int { return $a; }` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-MEMBER-005](#class-member-005) | pending-discussion | `function f(int $a): int { return $a; }` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-006](#class-member-006) | deferred-CH11 | `abstract function f(int $a): int;` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-MEMBER-007](#class-member-007) | deferred-CH11 | `#[\Override] function f(int $a): int { return $a; }` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-MEMBER-008](#class-member-008) | deferred-CH11 | `final function f(int $a): int { return $a; }` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-MEMBER-009](#class-member-009) | deferred-CH11 | `abstract static function f(): int;` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-MEMBER-010](#class-member-010) | deferred-CH11 | `#[\Override] static function f(): int { return 1; }` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-MEMBER-011](#class-member-011) | supported-bounded | `function __construct(int $x) { $this->x = $x; }` | prepared | verified | deferred | Shared callable lifecycle and out-of-line body; also `set:` |
| [CLASS-MEMBER-012](#class-member-012) | pending-discussion | `function __destruct() {}` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-013](#class-member-013) | pending-discussion | `interface I { function f(int $a): int; }` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-014](#class-member-014) | supported-bounded | `private function f(): int { return 1; }` | prepared | verified | deferred | Fixed instance slice; receiver lifetime gates apply |
| [CLASS-MEMBER-015](#class-member-015) | pending-discussion | `function f(int $a): int {} function f(string $a): int {}` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-016](#class-member-016) | supported-bounded | `function f(int $a = 1): int { return $a; }` | prepared | verified | deferred | Fixed instance slice; receiver lifetime gates apply |
| [CLASS-MEMBER-017](#class-member-017) | supported-bounded | `function f(int ...$a): int { return 1; }` | prepared | verified | deferred | Shared method parameters; records.py --suite method_parameters |
| [CLASS-MEMBER-018A](#class-member-018a) | supported-bounded | `function f(int &$a): void {}` | prepared | verified | deferred | Shared method parameters; records.py --suite method_parameters |
| [CLASS-MEMBER-018B](#class-member-018b) | pending-discussion | `function &f(): int {}` | unverified | unverified | deferred | — |
| [CLASS-MEMBER-018C](#class-member-018c) | supported-bounded | `function &f(int &$a): int {}` | shared callable | same-origin checked | generated execution | Complete body required; same-origin method return checkpoint |
| [CLASS-MEMBER-018D](#class-member-018d) | supported-bounded | explicit PHP reference syntax on method/member signature | shared callable | exact stable storage | generated execution | Fixed references and same-origin returns only |
| [CLASS-MEMBER-018E](#class-member-018e) | pending-discussion | `function &f() { return $this->x; }` | unverified | unverified | deferred | — |
| [CLASS-PROP-002A](#class-prop-002a) | supported-bounded | `class A { static int $x = 1; }` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-PROP-002B](#class-prop-002b) | supported-bounded | `self::$x` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-PROP-002C](#class-prop-002c) | deferred-CH11 | `parent::$x` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-PROP-002D](#class-prop-002d) | deferred-CH11 | `static::$x` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-METHOD-001](#class-method-001) | supported-bounded | `class A { function f(): int { return 1; } }` | prepared | verified | deferred | Fixed instance slice; receiver lifetime gates apply |
| [CLASS-METHOD-002](#class-method-002) | supported-bounded | `class A { static function f(int $a): int { return $a; } }` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-THIS-001](#class-this-001) | supported-bounded | `class A { int $x; function f(): int { return $this->x; } }` | prepared | verified | deferred | Fixed instance slice; receiver lifetime gates apply |
| [CLASS-NEW-001](#class-new-001) | pending-discussion | `$a = new A();` | unverified | unverified | deferred | — |
| [CLASS-NEW-002](#class-new-002) | pending-discussion | `$a = new \A\B();` | unverified | unverified | deferred | — |
| [CLASS-CONSTRUCT-001](#class-construct-001) | supported-bounded | `class A { function __construct(int $x) { $this->x = $x; } }` | prepared | verified | deferred | Public constructor, declared properties, out-of-line body; no return type |
| [OBJ-PROP-READ-001](#obj-prop-read-001) | pending-discussion | `$a = $obj->x;` | unverified | unverified | deferred | — |
| [OBJ-PROP-WRITE-001](#obj-prop-write-001) | pending-discussion | `$obj->x = 1;` | unverified | unverified | deferred | — |
| [OBJ-METHOD-CALL-001](#obj-method-call-001) | supported-bounded | `$a = $obj->f();` | prepared | verified | deferred | Fixed instance slice; receiver lifetime gates apply |
| [OBJ-METHOD-CALL-002](#obj-method-call-002) | supported-bounded | `$a = $obj->f(1);` | prepared | verified | deferred | Fixed instance slice; receiver lifetime gates apply |
| [TYPE-VAR-003](#type-var-003) | pending-discussion | `$x A = new A();` | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [TYPE-VAR-004](#type-var-004) | pending-discussion | `$x ?A = null;` | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [TYPE-PARAM-004](#type-param-004) | pending-discussion | `function f(A $a, ?B $b, I $i): A { return $a; }` | unverified | unverified | deferred | — |
| [TYPE-PROP-001](#type-prop-001) | pending-discussion | `class A { protected string $s; protected B $b; }` | unverified | unverified | deferred | — |
| [TYPE-PROP-001B](#type-prop-001b) | pending-discussion | `class A { public int $x; }` | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [TYPE-PROP-002](#type-prop-002) | pending-discussion | `class A { protected ?string $x; protected ?B $b; }` | unverified | unverified | deferred | — |
| [NOTE-029](#note-029) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-050](#note-050) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |

### Deferred legacy syntax

These syntax-specific entries are retained for traceability, not scheduled before strict-mode features.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [CLASS-PROP-001B](#class-prop-001b) | deferred-legacy | `class A { public /** int */ int $x; }` | unverified | unverified | deferred | Legacy annotation syntax only; outside strict-first work |
## CLASS-DECL-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:22](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:185](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A {}
```

**Existing C++ lowering / result**

```cpp
class A { public: };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** class declaration is syntactically valid

**Normalized pattern:** `class <name> {}`

**General rule:** A PHP class lowers to a C++ class declaration in the generated header.

**Diagnostics:** Error if the class declaration cannot be normalized.

**Notes:** User PHP classes participate in header/source splitting.


## CLASS-PROP-001

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:45](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
class A { int $x; }
```

**Existing C++ lowering / result**

```cpp
class A { public: int_t x; };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** property has explicit supported type

**Normalized pattern:** typed instance property

**General rule:** Instance properties must be typed and are emitted in the header under their declared visibility section. Omitted visibility defaults to `public`.

**Diagnostics:** Error if property type is missing or member access violates declared visibility.

**Notes:** Visibility is part of the member contract.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:187](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { int $x; }
```

**Existing C++ lowering / result**

```cpp
class A { public: int_t x; };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** property has explicit supported type

**Normalized pattern:** `class <name> { <typed-property>; }`

**General rule:** Instance properties must be typed and are emitted in the generated header under their declared visibility section. Omitted visibility defaults to `public`.

**Diagnostics:** Error if a property type is missing or if source/build analysis detects access outside the declared visibility.

**Notes:** Visibility is part of the member contract.


## CLASS-PROP-001B

**Scope:** Legacy annotation syntax; deferred. This is not a strict-mode implementation prerequisite.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:46](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
class A { public /** int */ int $x; }
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** both native and doc-comment types are present

**Normalized pattern:** conflicting property type sources

**General rule:** A property must use exactly one explicit type source.

**Diagnostics:** Emit an error rather than choosing precedence between native PHP typing and doc-comment typing.

**Notes:** Applies to static and instance properties alike.


## CLASS-PROP-002

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:47](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
class A { int $x = 1; }
```

**Existing C++ lowering / result**

```cpp
class A { public: int_t x = static_cast<int_t>(1); };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** property type and initializer are supported

**Normalized pattern:** inline property initialization

**General rule:** Non-static property defaults lower to in-class default member initializers in the generated header.

**Diagnostics:** Error if the initializer expression is outside the supported property-default subset.

**Notes:** Static properties have separate storage-definition rules.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:188](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { int $x = 1; }
```

**Existing C++ lowering / result**

```cpp
class A { public: int_t x = static_cast<int_t>(1); };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** property type and initializer are supported

**Normalized pattern:** `class <name> { <typed-property> = <expr>; }`

**General rule:** Non-static property defaults lower to in-class default member initializers in the generated header.

**Diagnostics:** Error if the initializer expression is outside the supported property-default subset.

**Notes:** Static properties follow separate storage-definition rules.


## CLASS-PROP-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:48](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
class A { int $x; }
```

**Existing C++ lowering / result**

property declaration appears only in header

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** property is valid

**Normalized pattern:** property declaration placement

**General rule:** Properties are emitted in the header only.

**Diagnostics:** Error if a source file declaration/definition is emitted for non-static instance properties.

**Notes:** Static properties have separate rules.


## CLASS-PROP-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:49](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
$this->x = 1;
```

**Existing C++ lowering / result**

```cpp
this->x = static_cast<int_t>(1);
```

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current context allows `$this`

**Normalized pattern:** `$this-><prop>`

**General rule:** `$this->prop` lowers to `this->prop`.

**Diagnostics:** Error if `$this` is invalid in current context.

**Notes:** Body expression normalization still applies.


## CLASS-PROP-005

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:50](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
$a->x = 1;
```

**Existing C++ lowering / result**

```cpp
a->x = static_cast<int_t>(1);
```

**Category:** Object

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** object expression is valid handle-like instance expression

**Normalized pattern:** `<expr>-><prop>`

**General rule:** Object property access lowers using pointer/handle member access syntax.

**Diagnostics:** Error if generator emits direct object-dot semantics for user PHP objects.

**Notes:** Consistent with handle-like object model.


## CLASS-PROP-006

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:51](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
A::$x = 1;
```

**Existing C++ lowering / result**

```cpp
A::x = static_cast<int_t>(1);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** static property is declared on class

**Normalized pattern:** `<class>::$<prop>`

**General rule:** Static property access through a class name lowers to `Class::prop`.

**Diagnostics:** Error if generator rewrites static property access through instance syntax.

**Notes:** Static property declaration/definition rules apply.


## CLASS-PROP-006A

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:52](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
self::$x = 1;
```

**Existing C++ lowering / result**

```cpp
CurrentClass::x = static_cast<int_t>(1);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** current context is a class body/method

**Normalized pattern:** `self::$<prop>`

**General rule:** `self::$prop` lowers to the current generated class static storage.

**Diagnostics:** Error if no current class context exists.

**Notes:** Lexical current-class lowering.


## CLASS-PROP-006B

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:53](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
parent::$x = 1;
```

**Existing C++ lowering / result**

```cpp
ParentClass::x = static_cast<int_t>(1);
```

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class has a parent

**Normalized pattern:** `parent::$<prop>`

**General rule:** `parent::$prop` lowers to the parent class static storage.

**Diagnostics:** Error if there is no parent class in the current context.

**Notes:** Uses the already normalized parent-class name.


## CLASS-PROP-006C

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:54](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
static::$x = 1;
```

**Existing C++ lowering / result**

file-local late-static lowering helper path

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class-family candidates are visible in the current lowered file

**Normalized pattern:** `static::$<prop>`

**General rule:** Late-static property access is supported only through file-local structural lowering. The generator may use current-file class-family context but must not resolve symbols across source files.

**Diagnostics:** Error if the required current-file class context is unavailable or if cross-file knowledge would be required.

**Notes:** First-pass late-static property support.


## CLASS-PROP-007

**v0.2 decision / target C++:** Shared static storage; evaluate/discard the qualifier
before RHS evaluation/conversion. Reads and constants follow the same qualifier rule.
The imported unevaluated `decltype` spelling is intentionally superseded.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:55](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
$a::$x = 2;
```

**Existing C++ lowering / result**

```cpp
::scpp::class_t<decltype(a)>::x = static_cast<int_t>(2);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** source uses static access through instance expression

**Normalized pattern:** `<expr>::$<prop>`

**General rule:** Static property access through an instance expression lowers syntactically using `::scpp::class_t<decltype(expr)>::prop`.

**Diagnostics:** Let invalid resulting C++ fail at C++ compile time if the type lacks such a member.

**Notes:** Mirrors the already approved static method form.


## CLASS-PROP-008

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:56](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
class B extends A { function f(): void { $this->x = 1; } }
```

**Existing C++ lowering / result**

```cpp
this->x = static_cast<int_t>(1);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** property exists through inheritance

**Normalized pattern:** inherited instance property access

**General rule:** Inherited properties are accessed normally via `this->prop`.

**Diagnostics:** No cross-file validation of inherited members is performed.

**Notes:** The generator remains syntactic/local.


## CLASS-PROP-009

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:57](../../../../generators/php/specs/catalog.md)

**PHP input example**

`$this->y = 1;` where `y` is undeclared

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** property is not declared in class model

**Normalized pattern:** dynamic property creation

**General rule:** Dynamic properties are not supported.

**Diagnostics:** Emit an error when an undeclared property is introduced dynamically.

**Notes:** Explicit project decision.


## CLASS-PROP-010

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:58](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
$this->$name = 1;
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** dynamic property name

**General rule:** Dynamic property access is not supported.

**Diagnostics:** Emit an error for variable property names.

**Notes:** Applies to reads and writes.


## CLASS-PROP-011

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:59](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
class A { public int $x = 1; public static int $s = 2; }
```

**Existing C++ lowering / result**

`class A { public: int_t x = static_cast<int_t>(1); static int_t s; };` and `int_t A::s = static_cast<int_t>(2);`

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** property types and initializers are supported

**Normalized pattern:** property default initialization

**General rule:** Instance-property defaults stay in the header as in-class initializers, while static-property defaults become out-of-class storage definitions in the source file.

**Diagnostics:** Error if the initializer expression is outside the supported subset.

**Notes:** Keeps C++ storage duration rules correct.


## CLASS-CALL-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:60](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:225](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a->f(1);
```

**Existing C++ lowering / result**

```cpp
a->f(static_cast<int_t>(1));
```

**Category:** Object

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** object expression is valid handle-like instance

**Normalized pattern:** `<expr>-><method>(<args>)`

**General rule:** Instance method calls lower to handle/pointer member calls.

**Diagnostics:** Error if generator emits direct object-dot semantics for user PHP objects.

**Notes:** Consistent with `create<T>()` object model.


## CLASS-CALL-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:61](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:226](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
A::f(1);
```

**Existing C++ lowering / result**

```cpp
A::f(static_cast<int_t>(1));
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** static method is declared

**Normalized pattern:** `<class>::<method>(<args>)`

**General rule:** Static method calls through class names remain `Class::method(...)`.

**Diagnostics:** Error if instance-call syntax is emitted.

**Notes:** Straight syntactic lowering.


## CLASS-CALL-003

**v0.2 decision / target C++:** Evaluate qualifier once, discard, then run the
ordinary statically selected call. Intentionally replaces the imported unevaluated
`decltype` behavior; interface/runtime dispatch remains deferred to CH11.

Both imported versions describe the same unevaluated lowering. The explicit v0.2
decision above supersedes that behavior; the originals remain as historical evidence.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:62](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
$a::f(1);
```

**Existing C++ lowering / result**

```cpp
::scpp::class_t<decltype(a)>::f(static_cast<int_t>(1));
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** source uses static access through instance expression

**Normalized pattern:** `<expr>::<method>(<args>)`

**General rule:** Static method calls through an instance expression lower syntactically using `::scpp::class_t<decltype(expr)>::method(...)`.

**Diagnostics:** Let invalid generated C++ fail at C++ compile time if needed.

**Notes:** This preserves the earlier namespace/class decision.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:227](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a::f(1);
```

**Existing C++ lowering / result**

```cpp
::scpp::class_t<decltype(a)>::f(static_cast<int_t>(1));
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** source uses static access through instance expression

**Normalized pattern:** `<expr>::<method>(<args>)`

**General rule:** Static method calls through an instance expression lower syntactically using `::scpp::class_t<decltype(expr)>::method(...)`.

**Diagnostics:** Let invalid generated C++ fail at C++ compile time if needed.

**Notes:** Mirrors the earlier namespace/class decision.


## CLASS-CALL-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:63](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:228](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$this->f(1);
```

**Existing C++ lowering / result**

```cpp
this->f(static_cast<int_t>(1));
```

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current context allows `$this`

**Normalized pattern:** `$this-><method>(<args>)`

**General rule:** `$this->method(...)` lowers to `this->method(...)`.

**Diagnostics:** Error if `$this` appears in invalid context.

**Notes:** Body rules still normalize arguments.


## CLASS-CALL-005

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:64](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:229](../../../../generators/php/specs/rules_catalog.md)

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

**Preconditions:** current class has parent and `base` alias

**Normalized pattern:** `parent::<method>(<args>)`

**General rule:** Parent method calls lower to `base::method(...)`.

**Diagnostics:** Error if no parent exists.

**Notes:** This is the final approved form.


## CLASS-CALL-005A

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:65](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
static::f(1);
```

**Existing C++ lowering / result**

file-local late-static lowering helper path

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class-family candidates are visible in the current lowered file

**Normalized pattern:** `static::<method>(<args>)`

**General rule:** Late-static method calls are supported only through file-local structural lowering. The generator may use current-file class-family context but must not resolve symbols across source files.

**Diagnostics:** Error if the required current-file class context is unavailable or if cross-file knowledge would be required.

**Notes:** First-pass late-static method support.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:230](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
static::f(1)
```

**Existing C++ lowering / result**

file-local late-static lowering helper path

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class-family candidates are visible in the current lowered file

**Normalized pattern:** `static::<method>(<args>)`

**General rule:** Late-static method calls are supported only through file-local structural lowering. The generator may use current-file class-family context but must not resolve symbols across source files.

**Diagnostics:** Error if the required current-file class context is unavailable or if cross-file knowledge would be required.

**Notes:** First-pass late-static method support.


## CLASS-CALL-006

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:66](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:231](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(): int { return 1; }
```

**Existing C++ lowering / result**

ordinary non-virtual declaration unless another explicit rule applies

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** method is not abstract/interface and lacks explicit `#[\Override]`

**Normalized pattern:** ordinary method declaration

**General rule:** The generator does not infer `virtual`; virtual-related markers are emitted only from explicit source forms such as abstract/interface methods and `#[\Override]`.

**Diagnostics:** No semantic hierarchy analysis is performed.

**Notes:** Explicitly approved.


## CLASS-CALL-007

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:67](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:232](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$i->f(1);
```

**Existing C++ lowering / result**

```cpp
i->f(static_cast<int_t>(1));
```

**Category:** Interface

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** `i` is already typed as an interface-compatible handle

**Normalized pattern:** interface dispatch call

**General rule:** Interface instance calls use the normal handle member-call syntax.

**Diagnostics:** Error handling remains local/syntactic.

**Notes:** No special lowering beyond declared type.


## CLASS-CALL-008

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:68](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
new A(1)
```

**Existing C++ lowering / result**

```cpp
create<A>(static_cast<int_t>(1))
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** target is user PHP class

**Normalized pattern:** object creation expression

**General rule:** Object creation for user PHP classes lowers to `create<Class>(...)`.

**Diagnostics:** Error if raw `new` is emitted.

**Notes:** Preserved for method-call catalog completeness.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:233](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
new A(1)
```

**Existing C++ lowering / result**

```cpp
create<A>(static_cast<int_t>(1))
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** target is a user PHP class

**Normalized pattern:** object creation expression

**General rule:** Object creation for user PHP classes lowers to `create<Class>(...)`.

**Diagnostics:** Error if raw `new` is emitted.

**Notes:** Preserved for method-call catalog completeness.


## CLASS-CALL-008A

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:69](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:234](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
new static(1)
```

**Existing C++ lowering / result**

file-local late-static lowering helper path

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class-family candidates are visible in the current lowered file

**Normalized pattern:** `new static(<args>)`

**General rule:** Late-static construction is supported only through file-local structural lowering. The generator may use current-file class-family context but must not resolve symbols across source files.

**Diagnostics:** Error if the required current-file class context is unavailable or if cross-file knowledge would be required.

**Notes:** First-pass late-static construction support.


## CLASS-CALL-009

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:70](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:235](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a->$name();
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** dynamic method name

**General rule:** Dynamic method names are not supported.

**Diagnostics:** Emit an error for variable method names.

**Notes:** Explicit project decision.


## CLASS-CALL-010

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:71](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:236](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
[$a, 'f']
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** method callable array

**General rule:** Method callable indirection forms are out of scope for now.

**Diagnostics:** Emit an error for callable-array method forms.

**Notes:** Explicitly rejected for current S2S scope.


## CLASS-CALL-011

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:72](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:237](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

explicit destructor invocation pattern

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** explicit source-level destructor call

**General rule:** Explicit destructor invocation is not supported in source lowering.

**Diagnostics:** Emit an error if such a source-level form is encountered.

**Notes:** Lifecycle remains runtime/object-model driven.


## CLASS-CALL-012

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:73](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:238](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
static function g(int $x): int { return $x; }
```

**Existing C++ lowering / result**

header contains qualifiers; source contains plain out-of-line definition

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** method declaration is valid

**Normalized pattern:** declaration-site qualifiers

**General rule:** `static`, `override`, `virtual`, `final`, and default arguments belong to declaration site only; source definitions are emitted without redeclaring those qualifiers.

**Diagnostics:** Error if qualifiers/defaults are repeated in source definitions.

**Notes:** Pure virtual methods have no source definition.


## CLASS-CONST-001

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:74](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
class A { public const X = 1; }
```

**Existing C++ lowering / result**

```cpp
class A { public: static constexpr auto X = static_cast<int_t>(1); };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** initializer expression is inside the supported constant subset

**Normalized pattern:** class constant declaration

**General rule:** Class constant declarations are supported and lower to class-scope constant storage in the generated header.

**Diagnostics:** Error if the initializer expression is outside the supported constant subset.

**Notes:** Declaration-only constant emission.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:199](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { public const X = 1; }
```

**Existing C++ lowering / result**

```cpp
class A { public: static constexpr auto X = static_cast<int_t>(1); };
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** initializer expression is inside the supported constant subset

**Normalized pattern:** `class constant declaration`

**General rule:** Class constant declarations are supported and lower to class-scope constant storage in the generated header.

**Diagnostics:** Error if the initializer expression is outside the supported constant subset.

**Notes:** Declaration-only constant emission.


## CLASS-CONST-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:75](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:200](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
A::X
```

**Existing C++ lowering / result**

```cpp
A::X
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** constant is declared on class

**Normalized pattern:** `<class>::<const>`

**General rule:** Class constant access through a class name lowers directly to `Class::CONST`.

**Diagnostics:** Error if generator rewrites constant access through instance syntax.

**Notes:** Lexical class-name constant access.


## CLASS-CONST-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:76](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:201](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
self::X
```

**Existing C++ lowering / result**

```cpp
CurrentClass::X
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** current context is a class body/method

**Normalized pattern:** `self::<const>`

**General rule:** `self::CONST` lowers to the current generated class constant.

**Diagnostics:** Error if no current class context exists.

**Notes:** Lexical current-class constant access.


## CLASS-CONST-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:77](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:202](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
parent::X
```

**Existing C++ lowering / result**

```cpp
ParentClass::X
```

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class has a parent

**Normalized pattern:** `parent::<const>`

**General rule:** `parent::CONST` lowers to the parent class constant.

**Diagnostics:** Error if no parent class exists.

**Notes:** Uses the normalized parent class name.


## CLASS-CONST-005

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:78](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:203](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
static::X
```

**Existing C++ lowering / result**

file-local late-static lowering helper path

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class-family candidates are visible in the current lowered file

**Normalized pattern:** `static::<const>`

**General rule:** Late-static constant access is supported only through file-local structural lowering. The generator may use current-file class-family context but must not resolve symbols across source files.

**Diagnostics:** Error if the required current-file class context is unavailable or if cross-file knowledge would be required.

**Notes:** First-pass late-static constant support.


## CLASS-MEMBER-001

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:79](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
function f(int $a): int { return $a; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t a);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parameter types are explicit and supported

**Normalized pattern:** typed method parameter list

**General rule:** Method parameters must be typed.

**Diagnostics:** Error if a parameter type is omitted.

**Notes:** Applies to methods and, by extension, constructors where relevant.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:239](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a): int { return $a; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t a);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parameter types are explicit and supported

**Normalized pattern:** typed method parameter list

**General rule:** Method parameters must be typed.

**Diagnostics:** Error if a parameter type is omitted.

**Notes:** Applies to methods and constructors where relevant.


## CLASS-MEMBER-001B

**Strict-mode PHP input example:** `function f(vector<int> $a): int { return 1; }`

This is the working source spelling. Imported examples and C++ expectations below remain reference material; the v0.2 result still needs agreement and proof.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:80](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:240](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(/** vector<int> */ $a): int { return 1; }
```

**Existing C++ lowering / result**

```cpp
int_t f(const vector_t<int_t>& a);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** method parameter doc-comment type is explicit and supported

**Normalized pattern:** doc-typed method parameter

**General rule:** Method parameters may take their explicit type from an attached doc-comment when no native PHP type is present.

**Diagnostics:** Error if the doc-comment is detached or conflicts with a native PHP type.

**Notes:** The same parameter rule set applies to methods and constructors.


## CLASS-MEMBER-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:81](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:241](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a): int { return $a; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t a);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** return type is explicit and supported

**Normalized pattern:** explicit method return type

**General rule:** Method return type must be explicit.

**Diagnostics:** Error if return type is omitted.

**Notes:** Constructor/destructor are separate special forms.


## CLASS-MEMBER-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:82](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:242](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $value): int { return $value; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t value);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** declaration is otherwise valid

**Normalized pattern:** parameter name preservation

**General rule:** Parameter names are preserved in the generated declaration/definition.

**Diagnostics:** Error only if normalization makes the name invalid.

**Notes:** Name normalization rules still apply globally.


## CLASS-MEMBER-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:83](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:244](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
static function f(int $a): int { return $a; }
```

**Existing C++ lowering / result**

`static int_t f(int_t a);` in header, `int_t A::f(int_t a) { ... }` in source

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** static method declaration is valid

**Normalized pattern:** static method

**General rule:** Static methods remain static C++ methods; `static` appears only in the header declaration.

**Diagnostics:** Error if `static` is repeated in the source definition.

**Notes:** Approved explicitly.


## CLASS-MEMBER-005

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:84](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:245](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a): int { return $a; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t a);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** method declaration is valid

**Normalized pattern:** instance method

**General rule:** Non-static methods have implicit instance context (`this`).

**Diagnostics:** Error only on invalid member combinations.

**Notes:** Paired with `$this` rules.


## CLASS-MEMBER-006

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:85](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:246](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
abstract function f(int $a): int;
```

**Existing C++ lowering / result**

```cpp
virtual int_t f(int_t a) = 0;
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** abstract method syntax is valid

**Normalized pattern:** abstract method

**General rule:** Abstract methods lower to pure virtual declarations.

**Diagnostics:** Error if a body is present.

**Notes:** No source definition.


## CLASS-MEMBER-007

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:86](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:247](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
#[\Override] function f(int $a): int { return $a; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t a) override;
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** `#[\Override]` attribute explicitly present

**Normalized pattern:** override-marked method

**General rule:** Emit `override` only when the explicit attribute is present.

**Diagnostics:** No semantic override validation.

**Notes:** Mirrors CLASS-INH-011.


## CLASS-MEMBER-008

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:87](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:248](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
final function f(int $a): int { return $a; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t a) final;
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** final method syntax is valid

**Normalized pattern:** final method

**General rule:** `final` is preserved on method declarations.

**Diagnostics:** Error on invalid modifier combinations.

**Notes:** Declaration-site only.


## CLASS-MEMBER-009

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:88](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:249](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
abstract static function f(): int;
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** abstract + static method

**General rule:** `abstract static` methods are not supported.

**Diagnostics:** Emit an error for the combination.

**Notes:** Explicit project decision.


## CLASS-MEMBER-010

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:89](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:250](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
#[\Override] static function f(): int { return 1; }
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** override + static method

**General rule:** `static` and explicit `#[\Override]` cannot be combined.

**Diagnostics:** Emit an error for the combination.

**Notes:** Explicit project decision.


## CLASS-MEMBER-011

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:90](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:251](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function __construct(int $x) { $this->x = $x; }
```

**Existing C++ lowering / result**

`A(int_t x);` in header and `A::A(int_t x) { this->x = x; }` in source

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** constructor declaration is valid

**Normalized pattern:** constructor

**General rule:** `__construct` lowers to a C++ constructor named after the class.

**Diagnostics:** Error if a return type is declared on the constructor.

**Notes:** Constructor bodies are emitted out of line.


## CLASS-MEMBER-012

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:91](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:252](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function __destruct() {}
```

**Existing C++ lowering / result**

`~A();` in header and `A::~A() {}` in source

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** destructor declaration is valid

**Normalized pattern:** destructor

**General rule:** `__destruct` lowers to a C++ destructor.

**Diagnostics:** Error if parameters or return type are present.

**Notes:** Destructor bodies are emitted out of line.


## CLASS-MEMBER-013

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:92](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:253](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
interface I { function f(int $a): int; }
```

**Existing C++ lowering / result**

```cpp
virtual int_t f(int_t a) = 0;
```

**Category:** Interface

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** interface method has no body

**Normalized pattern:** interface member

**General rule:** Interface methods are always pure virtual and bodyless.

**Diagnostics:** Error if body is present.

**Notes:** Interface fields remain unsupported.


## CLASS-MEMBER-014

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:93](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
private function f(): int { return 1; }
```

**Existing C++ lowering / result**

declaration emitted under `private:` section

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** visibility syntax is otherwise valid

**Normalized pattern:** visibility modifier

**General rule:** Visibility is preserved for properties, methods, and class constants.

**Diagnostics:** Error if source/build analysis detects access outside the declared visibility.

**Notes:** Replaces the previous public-normalization compromise.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:254](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
private function f(): int { return 1; }
```

**Existing C++ lowering / result**

declaration emitted under `private:` section

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** visibility syntax is otherwise valid

**Normalized pattern:** visibility modifier

**General rule:** Member visibility is preserved for properties, methods, and class constants. Omitted visibility defaults to `public`; `protected` and `private` must be enforced by source/build analysis and reflected in generated C++ access sections.

**Diagnostics:** Error if source/build analysis detects access outside the declared visibility.

**Notes:** Replaces the earlier practical public-normalization compromise.


## CLASS-MEMBER-015

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:94](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:255](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a): int {} function f(string $a): int {}
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** same class/member namespace

**Normalized pattern:** same-name multiple methods

**General rule:** Method overloading is forbidden by Prism++ design.

**Diagnostics:** Emit an error on duplicate method names regardless of parameter differences.

**Notes:** Also applies project-wide to functions.


## CLASS-MEMBER-016

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:95](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:256](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a = 1): int { return $a; }
```

**Existing C++ lowering / result**

`int_t f(int_t a = static_cast<int_t>(1));` in header and `int_t A::f(int_t a) { return a; }` in source

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** default value expression is otherwise supported

**Normalized pattern:** default parameter value

**General rule:** Default parameter values are supported and are emitted in the declaration only.

**Diagnostics:** Error if default values are repeated in the source definition.

**Notes:** Approved explicitly after revision.


## CLASS-MEMBER-017

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:96](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:257](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int ...$a): int { return 1; }
```

**Existing C++ lowering / result**

```cpp
int_t f(const vector_t<int_t>& a) { return static_cast<int_t>(1); }
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variadic parameter type is explicit and supported; variadic parameter is trailing

**Normalized pattern:** variadic method

**General rule:** Typed trailing variadics are supported and lower to `const vector_t<T>&`.

**Diagnostics:** Emit a normal method signature/body using the mapped element type. Error if the variadic type is missing, unsupported, or the variadic parameter is not trailing.

**Notes:** Untyped/mixed variadics remain unsupported.


## CLASS-MEMBER-018A

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:97](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:258](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int &$a): void {}
```

**Existing C++ lowering / result**

```cpp
void_t f(int_t& a);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parameter reference syntax is explicit and valid

**Normalized pattern:** reference parameter

**General rule:** Reference parameters are supported for methods.

**Diagnostics:** Error if generator drops the reference marker.

**Notes:** Subject to the native-reference safety rule; explicit reference syntax alone does not justify exposing dynamic interior storage.


## CLASS-MEMBER-018B

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:98](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:259](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function &f(): int {}
```

**Existing C++ lowering / result**

```cpp
int_t& f();
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** return reference syntax is explicit and valid

**Normalized pattern:** reference return

**General rule:** Reference returns are supported for methods when the PHP return type is explicitly declared.

**Diagnostics:** Error if generator drops the reference marker.

**Notes:** Subject to the native-reference safety rule; explicit reference syntax alone does not justify exposing dynamic interior storage.


## CLASS-MEMBER-018C

**v0.2 decision / target C++:** Supported with a complete same-origin body; shared
callable signatures preserve `T&`. See the method-return checkpoint above.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:99](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:260](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function &f(int &$a): int {}
```

**Existing C++ lowering / result**

```cpp
int_t& f(int_t& a);
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit reference markers present

**Normalized pattern:** reference preservation

**General rule:** Reference markers are preserved in generated method signatures exactly as requested by source syntax.

**Diagnostics:** No inference is performed.

**Notes:** Purely explicit.


## CLASS-MEMBER-018D

**v0.2 decision / target C++:** Supported for fixed native-reference parameters and
bounded same-origin method returns; explicit markers do not bypass lifetime checks.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:100](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:261](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

explicit PHP reference syntax on method/member signature

**Existing C++ lowering / result**

literal reference-preserving C++ signature

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** source explicitly contains reference semantics

**Normalized pattern:** explicit-only reference semantics

**General rule:** Reference semantics are emitted only from explicit source syntax; the generator does not infer references.

**Diagnostics:** No semantic inference or alias analysis is performed.

**Notes:** Approved explicitly.


## CLASS-MEMBER-018E

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:101](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:262](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function &f() { return $this->x; }
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** by-reference method return lacks an explicit PHP return type

**Normalized pattern:** untyped method reference return

**General rule:** By-reference method returns must have an explicit declared PHP return type so the generated C++ signature can use a concrete `T&` instead of deduced `auto&`.

**Diagnostics:** Emit an explicit generator error when a method returns by reference without a declared PHP return type.

**Notes:** This keeps method declarations and out-of-class definitions consistent.


## CLASS-PROP-002A

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:189](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { static int $x = 1; }
```

**Existing C++ lowering / result**

`class A { public: static int_t x; };` and `int_t A::x = static_cast<int_t>(1);`

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** static property type and initializer are supported

**Normalized pattern:** `class <name> { static <typed-property> = <expr>; }`

**General rule:** Static property declarations stay in the header and their storage definitions are emitted in the source file.

**Diagnostics:** Error if generator keeps the initializer inline on the header declaration.

**Notes:** Matches C++ static member storage rules.


## CLASS-PROP-002B

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:190](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
self::$x
```

**Existing C++ lowering / result**

```cpp
CurrentClass::x
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** current context is a class body/method

**Normalized pattern:** `self::$<prop>`

**General rule:** `self::$prop` lowers to the current class static member.

**Diagnostics:** Error if no current class context exists.

**Notes:** Lexical current-class lowering.


## CLASS-PROP-002C

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:191](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
parent::$x
```

**Existing C++ lowering / result**

```cpp
ParentClass::x
```

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class has a parent

**Normalized pattern:** `parent::$<prop>`

**General rule:** `parent::$prop` lowers to the parent class static member.

**Diagnostics:** Error if no parent class exists.

**Notes:** Uses the normalized parent class name.


## CLASS-PROP-002D

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:192](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
static::$x
```

**Existing C++ lowering / result**

file-local late-static lowering helper path

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current class-family candidates are visible in the current lowered file

**Normalized pattern:** `static::$<prop>`

**General rule:** Late-static property access is supported only through file-local structural lowering. The generator may use current-file class-family context but must not resolve symbols across source files.

**Diagnostics:** Error if the required current-file class context is unavailable or if cross-file knowledge would be required.

**Notes:** First-pass late-static property support.


## CLASS-METHOD-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:193](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { function f(): int { return 1; } }
```

**Existing C++ lowering / result**

header declaration + out-of-line source definition

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** method declaration is otherwise valid

**Normalized pattern:** `class <name> { <method>; }`

**General rule:** Ordinary instance methods are supported and are declared in the header and defined out of line in the source file.

**Diagnostics:** Error on invalid member combinations.

**Notes:** Instance methods carry implicit `this` context.


## CLASS-METHOD-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:194](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { static function f(int $a): int { return $a; } }
```

**Existing C++ lowering / result**

`static int_t f(int_t a);` in header and `int_t A::f(int_t a) { return a; }` in source

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** static method declaration is valid

**Normalized pattern:** `class <name> { static <typed-method>; }`

**General rule:** Static methods are supported; `static` appears in the declaration only.

**Diagnostics:** Error if `static` is repeated in the source definition.

**Notes:** Same-namespace and rooted static access rules remain separate.


## CLASS-THIS-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:195](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { int $x; function f(): int { return $this->x; } }
```

**Existing C++ lowering / result**

```cpp
int_t A::f() { return this->x; }
```

**Category:** Class

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** current context is an instance method, constructor, or destructor

**Normalized pattern:** `$this-><property>`

**General rule:** `$this` is allowed only in instance methods, constructors, and destructors. `$this->member` lowers to `this->member`.

**Diagnostics:** Error if `$this` appears in static methods or outside instance context.

**Notes:** Applies to both properties and methods.


## CLASS-NEW-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:196](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = new A();
```

**Existing C++ lowering / result**

```cpp
auto a = create<A>();
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** class name is used in the same namespace context and lowering is syntactic

**Normalized pattern:** `<var> = new <class>()`

**General rule:** Object construction must not emit raw `new`. `new Class(...)` lowers to `create<Class>(...)`.

**Diagnostics:** Error if raw `new` is emitted.

**Notes:** Ownership/runtime semantics are delegated to `create<...>()`.


## CLASS-NEW-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:197](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = new \A\B();
```

**Existing C++ lowering / result**

```cpp
auto a = create<::scpp::A::B>();
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** class name is fully-qualified in PHP input

**Normalized pattern:** `<var> = new <qualified-class>(<args>)`

**General rule:** Fully-qualified class construction lowers syntactically into `create<::scpp::...>(...)`. The generator must not attempt semantic resolution beyond explicit fully-qualified name lowering.

**Diagnostics:** Error if the rooted `::scpp::...` form is not used for fully-qualified PHP class names.

**Notes:** Example: `new \A\X()` â†’ `create<::scpp::A::X>()`.


## CLASS-CONSTRUCT-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:198](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { function __construct(int $x) { $this->x = $x; } }
```

**Existing C++ lowering / result**

`A(int_t x);` in header and `A::A(int_t x) { this->x = x; }` in source

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** constructor declaration is valid

**Normalized pattern:** `constructor method`

**General rule:** `__construct` lowers to a C++ constructor named after the class. Constructor bodies are emitted out of line.

**Diagnostics:** Error if a return type is declared on the constructor.

**Notes:** Parent constructor calls have a separate base-initializer rule.


## OBJ-PROP-READ-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:278](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $obj->x;
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** Object

**Rule kind:** generation-minimum

**Normalized pattern:** `<var> = <expr>-><property>`


## OBJ-PROP-WRITE-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:279](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$obj->x = 1;
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** Object

**Rule kind:** generation-minimum

**Normalized pattern:** `<expr>-><property> = <expr>`


## OBJ-METHOD-CALL-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:280](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $obj->f();
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** Object

**Rule kind:** generation-minimum

**Normalized pattern:** `<var> = <expr>-><method>()`


## OBJ-METHOD-CALL-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:281](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $obj->f(1);
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** Object

**Rule kind:** generation-minimum

**Normalized pattern:** `<var> = <expr>-><method>(<expr>)`


## TYPE-VAR-003

**Strict-mode PHP input example:** `$x A = new A();`

This is the working source spelling. Imported examples and C++ expectations below remain reference material; the v0.2 result still needs agreement and proof.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:324](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x /** A */ = new A();
```

**Existing C++ lowering / result**

```cpp
shared_p<A> x = create<A>();
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit class/interface/abstract PHPDoc variable type present

**Normalized pattern:** `typed object local variable`

**General rule:** Explicitly typed object local variables lower to `shared_p<T>` handles.

**Diagnostics:** Error only if the explicit type form itself is unsupported.

**Notes:** Object-handle details are runtime-defined.


## TYPE-VAR-004

**Strict-mode PHP input example:** `$x ?A = null;`

This is the working source spelling. Imported examples and C++ expectations below remain reference material; the v0.2 result still needs agreement and proof.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:325](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x /** ?A */ = null;
```

**Existing C++ lowering / result**

```cpp
shared_p<A> x = null;
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit nullable object PHPDoc variable type present

**Normalized pattern:** `typed nullable object local variable`

**General rule:** Object-handle types are inherently nullable for current code generation, so `A` and `?A` both emit `shared_p<A>`.

**Diagnostics:** No generator-side distinction is required between `A` and `?A` for emitted handle type.

**Notes:** Runtime nullability enforcement for non-nullable object annotations is deferred.


## TYPE-PARAM-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:340](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(A $a, ?B $b, I $i): A { return $a; }
```

**Existing C++ lowering / result**

```cpp
shared_p<A> f(shared_p<A> a, shared_p<B> b, shared_p<I> i) { return a; }
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** object/interface/abstract types are explicit and supported

**Normalized pattern:** `object-handle parameter and return convention`

**General rule:** Class, interface, and abstract object types are emitted as `shared_p<T>` handles passed and returned by value. Current code generation emits the same handle type for `T` and `?T`.

**Diagnostics:** No generator-side nullability distinction is required for object-handle type emission.

**Notes:** Future runtime checks may enforce non-null annotations.


## TYPE-PROP-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:341](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { protected string $s; protected B $b; }
```

**Existing C++ lowering / result**

```cpp
class B; class A { protected: string_t s; shared_p<B> b; };
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** property types are explicit and supported

**Normalized pattern:** `typed property mapping`

**General rule:** Property types follow the same mapping rules as parameters and locals: value-like types map to runtime value wrappers, object types map to `shared_p<T>`, and declared property visibility determines the generated access section.

**Diagnostics:** Error if a property type form is unsupported or access violates the declared visibility.

**Notes:** Forward declarations should be emitted where sufficient for object-typed properties.


## TYPE-PROP-001B

**Strict-mode PHP input example:** `class A { public int $x; }`

This is the working source spelling. Imported examples and C++ expectations below remain reference material; the v0.2 result still needs agreement and proof.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:342](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { public /** int */ $x; }
```

**Existing C++ lowering / result**

```cpp
class A { public: int_t x; };
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** property doc-comment type is explicit and supported

**Normalized pattern:** `doc-typed property mapping`

**General rule:** Class properties may take their explicit type from an attached doc-comment when no native PHP type is present.

**Diagnostics:** Error if the property has no explicit type or if both native and doc-comment types are present.

**Notes:** Applies to static and instance properties.


## TYPE-PROP-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:343](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { protected ?string $x; protected ?B $b; }
```

**Existing C++ lowering / result**

```cpp
class B; class A { protected: nullable<string_t> x; shared_p<B> b; };
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** nullable property types are explicit and supported

**Normalized pattern:** `nullable property mapping`

**General rule:** Nullable value-like properties lower to `nullable<T>`, while nullable object properties remain `shared_p<T>` under the current object-handle model.

**Diagnostics:** Error if the generator emits `nullable<shared_p<T>>` for object properties.

**Notes:** Current object-handle typing keeps `T` and `?T` identical in emitted type.


## NOTE-029

**Source:** [generators/php/specs/rules.md:520](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 15. Class Construction and Static Access
>
> ### 15.1 Object Construction
> `new Class(...)` must be lowered to `create<Class>(...)`.
>
> This rule applies to ordinary classes. For a declared struct, `new Struct()`
> must emit a value initializer `Struct{}` and expression typing must report the
> same value type. Construction emission and typing must use the same declaration
> identity/kind classification, including project metadata and imported names.
> Struct construction with any arguments (including named or unpacked arguments)
> is rejected with a source diagnostic. Explicit ownership-wrapper initialization
> from `new Struct()` is unsupported and must not bypass value construction.
> See `specs/compact_layout_types.md`, section 2.4.
>
> Examples:
> - `new X()` â†’ `create<X>()`
> - `new \A\B\X()` â†’ `create<::scpp::A::B::X>()`
>
> The generator must not emit raw `new` for these supported construction forms.
>
> ### 15.2 Static Access
> - same-namespace static access remains unqualified, for example `X::make()`
> - fully-qualified PHP static access lowers to rooted C++ access, for example `\A\X::make()` â†’ `::scpp::A::X::make()`
>
> ### 15.3 Static Access Through Instances
> PHP static access through an instance must be lowered syntactically using `::scpp::class_t<decltype(...)>`.
>
> Example:
> - `$x::make()` â†’ `::scpp::class_t<decltype(x)>::make()`
>
> The generator must not attempt to validate whether `::scpp::class_t<decltype(...)>::member` is semantically valid for the produced C++ type.
>
> If the emitted C++ is invalid, it must fail at C++ compile time rather than being rejected by the generator.
>
> ---
>
> # Appendix: Full Original Rules (verbatim)
>
> # Prism++ â€“ rules.md
>
> This is the single source of truth for generation rules and runtime assumptions.

## NOTE-050

**Source:** [generators/php/specs/rules.md:1102](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 16. Classes, Inheritance, and Members
>
> ### 16.1 File Split
> - each user PHP class lowers to a header declaration unit and a source implementation unit
> - the header contains the class definition, fields, constructor declarations, destructor declarations, and method declarations
> - the source contains out-of-line constructor, destructor, and method bodies
>
> ### 16.2 Supported Forms
> - inheritance is supported
> - interfaces are supported
> - traits are not supported
> - only one parent class is allowed
> - multiple interfaces are allowed
>
> ### 16.3 Base Alias
> - if a class has a parent, emit `using base = Parent;` in the class body
> - `parent::method(...)` lowers to `base::method(...)`
> - `parent::__construct(...)` lowers to a base initializer call
>
> ### 16.4 Construction and Lifetime Surface
> - `new Class(...)` lowers to `create<Class>(...)`
> - direct construction of user-defined PHP classes is forbidden
> - direct construction is required for whitelisted runtime/value types such as `string_t` and `vector_t`
> - explicit runtime ownership forms such as `weak($object)`, `unique(new MyClass)`, and `shared(new MyClass)` are allowed surface forms when separately supported by the generator; `shared(new MyClass)` is the explicit counterpart of `create<MyClass>(...)`
>
> ### 16.5 Instance Context
> - `$this` is valid only in instance methods, constructors, and destructors
> - `$this->prop` lowers to `this->prop`
> - `$this->method(...)` lowers to `this->method(...)`
>
> ### 16.6 Properties
> - properties without defaults must be typed explicitly
> - properties with default values may omit the explicit type; the generator infers the emitted C++ member type from the default initializer
> - if both a native PHP type and a supported doc-comment type are present, emit an error
> - instance properties are emitted in the header only
> - non-static property default values are supported and lower to in-class default member initializers
> - dynamic properties are not supported
> - dynamic property names are not supported
> - object-typed fields lower to handle fields such as `shared_p<B>`
> - when needed for headers, forward declarations such as `class B;` may be emitted
> - static object-typed properties use the same handle model
> - static property fetch/read/write/increment lower to `Class::prop` storage access in generated C++
> - supported static-property class forms are `ClassName::$prop`, `self::$prop`, and `parent::$prop`
> - `static::$prop` is supported in the current pass as file-local late-static lowering only; the generator may use current-file structural class-family context but must not resolve symbols across source files
>
> ### 16.7 Methods and Special Members
> - non-static methods are supported
> - static methods are supported
> - `static::method(...)` is supported in the current pass as file-local late-static lowering only; the generator may use current-file structural class-family context but must not resolve symbols across source files
> - `static::CONST` is supported in the current pass as file-local late-static lowering only
> - `new static(...)` is supported in the current pass as file-local late-static lowering only
> - constructors are supported
> - destructors are supported
> - abstract classes are supported when explicitly declared abstract
> - abstract methods lower to pure virtual methods
> - interface methods lower to pure virtual methods
> - `#[\Override]` is required to emit `override`; the generator must not infer overrides
> - `final` is preserved on declarations
> - `abstract static` methods are rejected
> - `static` with `#[\Override]` is rejected
> - class and method overloading are forbidden
>
> ### 16.8 Dispatch and Validation Boundary
> - ordinary methods are not made virtual unless an explicit rule requires it
> - dispatch remains ordinary C++ dispatch
> - the generator must not attempt hierarchy validation, symbol resolution across files, or override correctness checks unless a generation rule explicitly requires a local structural check
> - let the C++ compiler fail for semantic issues outside generator scope
>
> ### 16.9 Static Access Through Instances
> - PHP static access through an instance must be lowered syntactically using `::scpp::class_t<decltype(...)>`
> - `$x::make()` â†’ `::scpp::class_t<decltype(x)>::make()`
> - `$x::$prop` â†’ `::scpp::class_t<decltype(x)>::prop`
> - the generator must not attempt to validate whether the generated C++ member access is semantically valid

## Chapter-end debt

### CH10-PERF-01 — Test-duration review before native build

Status: pending; required review at the end of CH10, before starting its native
compiler build/validation. Requested 2026-10-07. This does not block CH10 feature
discussion or focused implementation checks.

User decision, 2026-10-07: current focused-test cost is acceptable. Revisit test
performance when testing exceeds 30% of the overall work time for a slice. Compare
elapsed test wall time (accounting for parallel/overlapping runs), not summed child
process durations, against elapsed slice time. The constructor slice's saved logs
indicate approximately four minutes across its test runs; the complete development
time was not instrumented. This acceptance does not claim a measured total-time
percentage or remove the chapter-end native-test review below.

Investigate why the tests now take so long. The successful CH08/09 cumulative
attempt took 1,589.296 seconds with 12 jobs, fixture PCH and reused native build
objects; its evidence is recorded in the [native checkpoint](../portability/conversion_review.md#latest-native-checkpoint--2026-10-07-chapter-0809).
That elapsed time is not a clean compiler-build benchmark or an established cause.

Review phase timings separately: host startup/compilation, native compiler build,
fixture export and parity, generated translation-unit compilation, linking,
execution and runner overhead. Compare representative fixtures on equivalent
settings, including PCH use, header dependencies, separate-unit process counts,
worker contention and the PHP-FPM/CLI fallback. Inspect fixture deduplication and
the supplementary-probe increase from the earlier 526 to 997; establish whether
that reflects new coverage, repeated work or transport/identity differences.

Deliver measured causes and a bounded improvement proposal before proceeding to
the native build. Do not silently reduce coverage, combine production translation
units, or infer a compiler/runtime slowdown from total suite duration alone.
Coordinate with CH15's existing native-cache/PCH overhead debt, while retaining
this chapter-local pre-build review gate.
