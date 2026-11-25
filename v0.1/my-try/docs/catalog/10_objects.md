# 10. Classes, properties, construction and methods
Doc Status: planning

### Accounting follow-up: indexed shared property writes — 2026-10-10

Plain `$entry->lines[1]->credit = value` now admits shared record results from
ordinary indexed reads. Property-write facts retain selected storage rather than
requiring a native-reference place. Existing property lookup/access/conversion and
backend owner binding are reused; no container-family branch is added. Owners stay
alive across key/RHS replacement, bounds/null failures precede the RHS, and inline
elements/compound updates/native borrows remain rejected. RHS assignment chains on
the new target remain deferred; outer assignment chains consume its value snapshot.
The [authoritative contract](../../../../specs/instance_methods_v0_2.md#plain-property-writes-through-indexed-shared-values--2026-10-10)
records the boundary. Focused runner: `tests/indexed_properties.py`.
Evidence: `/tmp/indexed-properties-20261010-final` (three PHP checks, three generated
programs). Field write/probe regression evidence is in
`/tmp/field_container_writes-20261010-indexed-verified` and
`/tmp/field_container_probes-20261010-indexed-verified` (eight generated programs).
These are generated-program checks, not a native compiler rebuild.

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires functions, type identities and ownership. Start with a plain class before methods and lifecycle combinations.

Chapter closeout must include the [test-duration review](#ch10-perf-01--test-duration-review-before-native-build)
before starting the chapter's native compiler build.

## Native validation — 2026-10-07

The pre-build timing review and requested native validation are complete.
The [CH10 native checkpoint](../portability/conversion_review.md#latest-native-checkpoint--2026-10-07-chapter-10)
passes cumulative regressions plus 426 dedicated class/struct native/PHP artifact
comparisons and 428 generated-program executions across 15 suites. Native repairs
preserve the approved language contracts. Dedicated CH10 rejection/incremental
assertions remain PHP-host proofs; the cumulative runner retains its own native
rejection/recovery and incremental scope. Chapter 11/15 deferrals remain unchanged.

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

## Destructors and automatic cleanup — 2026-10-07

Public instance `__destruct()` on classes/structs uses the existing method declaration
and body lifecycle. Shared preparation admits no parameters, return annotation,
reference marker or explicit source invocation. C++ emits out-of-line `noexcept`
destructors. Ownership determines cleanup; the body runs before reverse-order member
destruction. Memberwise copy/move declarations preserve existing storage capabilities
despite C++ implicit-move suppression by a user destructor; noncopyable copies remain
rejected. No separate AST/body family or runtime destructor dispatch was introduced.

The [normative contract](../../../../specs/destructors_v0_2.md) records nonthrowing
termination, moved-from objects, assignment versus destruction, construction failure,
and deferred non-public/inherited destruction policies. Focused proof owner:
`records.py --suite destructors --no-pch`. This is generated-program validation,
not a native compiler rebuild.

Evidence: `/tmp/ch10-destructors-2/` passes 25 generated programs without PCH,
including noncopyable move traits, reverse member order, unwinding cleanup and a
test-only termination handler proving the nonthrowing boundary. Cross-file edits,
failed retries, destructor removal/readdition and fresh/reset agreement pass.
`/tmp/ch10-destructors-regression/` passes 12 focused PHP suites. No compiler native
rebuild or conversion was performed.

## Handle parameter null defaults — 2026-10-21

`Parameter_Defaults` now accepts null for copyable ownership-handle parameters,
using the existing empty-storage conversion and copy eligibility. This includes
normalized nullable class/interface types and weak handles. Non-null defaults,
unique defaults, reference defaults and raw null arguments remain rejected.
`tests/handle_defaults.py` checks ordinary/prelowered generated execution plus
rejection/recovery. Presence conditions verify empty defaults; direct handle/null
comparison still has the separately recorded prelowered emission defect and was
not repaired. No new runtime or lifetime protocol is introduced.

## Nullable handle normalization — 2026-10-07

The [approved contract](../../../../specs/type_use_modifiers.md#v02-nullable-records-and-handle-normalization--2026-10-07)
distinguishes nullable inline values from already-nullable owning/observing handles.
Both `?Handle` and `nullable<Handle>` now normalize in one shared type-preparation
application path before wrapper interning. Reference-compatible identity is the
original handle type, not a second runtime wrapper. No emitter-only spelling fix,
registry capability cache or change to the runtime nullable template was introduced.

Bare class, explicit shared struct, unique and weak handles preserve their existing
construction/copy/move/reference/presence rules. Redundant storage overrides still
reject; nullable spelling does not grant weak presence tests without locking.
`?Struct` and `?value<Class>` retain nullable model identity. At this checkpoint their value operations
were gated pending payload-dependent copy capability and shared boundary admission;
removing the scalar-only guard alone would wrongly advertise noncopyable payloads
as copyable. Record payload truthiness also needs an admitted payload condition path,
not a substitution of presence for the established delegation rule.

Focused runner: `records.py --suite nullable_handles --no-pch`.
Evidence: `/tmp/ch10-nullable-handles-final-2/` passes 18 generated programs without
PCH, including exact-reference compatibility, unique movement, weak locking, fields
and preserved inline-wrapper identity. `/tmp/ch10-nullable-handles-regression-final/`
passes 8 focused PHP suites after refreshing the older nested-field rejection
diagnostic expectation. No native compiler rebuild or conversion was performed.
The following checkpoint enables the bounded value operations; probes/truthiness
and joins remain separate.

## Nullable inline-record boundaries — 2026-10-07

Agreed source contract: `?Struct` / `?value<Class>` support default/null absence,
exact-payload wrapping, same-type copying and checked copying extraction at existing
value boundaries. Extraction leaves the source unchanged and throws on absence.
Null comparisons inspect presence without copying. Empty construction/reset does
not require a copyable or default-constructible payload; actual copying does,
even for an absent source. Contextual data literals, payload moves, interior borrows,
probes/truthiness and selection joins are not enabled by this checkpoint.

Preparation owns admission in `Nullable_Conversions`, payload-derived capability
queries in `Capability_Preparation`, and noncopying empty construction in the shared
value-boundary worker. Concrete `new` constructs its payload before ordinary nullable
wrapping. No new AST/facts, registry capability cache or class/struct-specific lowering
was needed. Existing C++ `nullable<T>{value}` and `T(optional.require_value(...))`
provide the implementation; only runtime/header uses already required by those types
are emitted. No new runtime helper or extra translation unit is introduced.

Legacy review: `TypeMapper` already distinguishes nullable inline values from handles;
`wrapExprForExpectedType` uses runtime construction and cast bridges at recognized
boundaries. The semantic compiler instead checks exact payload identity and copy
permission before emission, retaining runtime checks for actual absence.

Focused proof: `records.py --suite nullable_records --no-pch`, with checks in
`tests/nullable_records.php`. Evidence: `/tmp/ch10-nullable-records-final/` (16 generated
programs, no PCH) and `/tmp/ch10-nullable-records-regression/` (9 focused PHP suites).
Includes class/struct copy independence, null checks, calls/returns/properties,
empty access error code, noncopyable empty/reset, destructor cleanup and cross-file
copy-capability invalidation/retry/repair. No native compiler rebuild or conversion.

The following checkpoint settles record observations; selection joins follow.

## Record observation and collection-only empty — 2026-10-07

Approved normative change: [collection-only emptiness](../../../../specs/count_empty_isset_contract.md#v02-collection-only-emptiness--2026-10-07).
`empty` means zero elements for lists, keyed collections and strings. Records,
scalars, null and wrappers do not acquire that contract. Known unsupported operands
and keyed leaf types reject during preparation; mixed/dynamic kind failures and
missing/invalid paths throw `invalid_empty_operand`. Traversal remains lazy and
non-creating. `isset`/`!isset` retain presence/absence semantics; the possible `missing`
spelling is non-blocking CH15 debt. User-defined collection registration is deferred.

Inline records are always present/truthy, independent of fields. Nullable records
delegate to this rule: absent false, present true. Observations evaluate the operand
once without copying or consuming it, including noncopyable record types.
Shared preparation selects `always_true` or general `value_presence` facts;
emission never rediscovers record semantics. Plain and keyed probes share
operation-specific admission, not one catch-all supported-type list. Runtime `empty`
uses explicit collection overloads and boxed-kind dispatch, with no generic false
fallback or wrapper delegation. Existing stage ownership and AST shapes are unchanged.

Evidence: `/tmp/ch10-collection-probes-final/` passes 28 generated programs without
PCH; `/tmp/ch10-probe-migration-final/` passes 9 focused PHP suites and 134 selected
generated regressions with PCH; `/tmp/ch10-emptiness-runtime/` passes 7 affected
runtime executables without PCH. Earlier fixture failures exposed a missing
`value_p` presence overload and test-transport newline; both are repaired.
Legacy absence tests now use `!isset`, with explicit new empty rejection/error tests.
Authoritative specs and operator-matrix empty rows record the intentional v0.2 break.
No native compiler rebuild or conversion was performed.

The following checkpoint adds nullable-record joins. Property-path probe admission
and custom emptiness registration are not added by these slices.

## Nullable-record selection joins — 2026-10-07

Exact inline-record payloads now reuse the nullable selection path for both structs
and `value<Class>`. Ternary/Elvis retain `nullable<T>` when either branch has that
wrapper; `nullable<T> ?? T` produces an owned `T`. Shared selection preparation
validates exact payload agreement and copyability before emission. The existing
conversion facts, AST and lazy C++ lowering are unchanged; match and Result
admission do not widen. Noncopyable payloads reject even on an unselected branch.

Bare-null branches, nullable `??` fallbacks, contextual data literals and
cross-record conversions remain gated. See the authoritative
[selection matrix](../../../../specs/conditional_expression_matrix.md#v02-nullable-inline-record-selections--2026-10-07).

Evidence: `/tmp/ch10-nullable-joins-2/` passes 22 generated programs without PCH,
including class/struct joins, copy independence, lazy single evaluation and prior
nullable boundaries. PHP checks include rejection and copy-capability invalidation,
retry and repair. `/tmp/ch10-nullable-joins-final-php/` verifies wrapper result
spelling and incremental join invalidation; `/tmp/ch10-nullable-joins-regression/`
passes five focused PHP suites. No native compiler rebuild or conversion was performed.

The following checkpoint covers bounded property updates.

## Property mutation integration — 2026-10-07


**Current scope — 2026-10-19 (CS394).** Effectful property RHS values are admitted under ordered expression evaluation. H04 replaced the stale negative with class/struct result/store/call-count checks (3:3:1); all 14 property native programs passed on dev002 in fixture_repairs_02. Existing failure observers use H01 entry handling. The earlier replay and receiver-replacement trace remain separate evidence. division_by_zero is preserved; the property failure observer proves unchanged storage, while the scalar error-code suite owns the exact error-code assertion.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [static_members_v0_2.md](../../../../specs/static_members_v0_2.md), [data_initialization.md](../../../../specs/data_initialization.md), [type_use_modifiers.md](../../../../specs/type_use_modifiers.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS394`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

Instance/static properties now join locals/parameters in the existing mutation
target admission. Prepared property places already carry stable storage and
writability; existing operator selection, conversions and C++ lowering implement
compound and prefix/postfix updates without parallel member implementations.
Class/struct and expression-qualified static forms share this path. RHS effect
restrictions, instance receiver lifetime gates and nonescaping result values remain.
The [authoritative contract](../../../../specs/static_members_v0_2.md#property-updates--2026-10-07)
records ordering and failed-write-back behavior. No AST or retained-model changes.

Focused suite: `tests/property_mutations.php`, run through `tests/records.py`.
`/tmp/ch10-property-mutations-final/` passes 12 generated programs without PCH:
instance/static updates, boxed payloads, expression-qualifier single evaluation,
read-only static qualification and failed write-back. Preparation rejects constants,
private/read-only instance targets, weak/temporary receivers and effectful RHS.
`/tmp/ch10-property-mutations-regression/` passes five focused PHP suites.
No native compiler rebuild or conversion was performed.

The following checkpoint covers property inference; construction shortcuts remain separate.

</details>

## Default-based property inference — 2026-10-07

Class/struct instance and static properties may omit their type when an admitted
default establishes it. Parsing retains optional type syntax in the existing node;
declaration/default preparation resolves the type and conversion once. Constant
references, enum cases and explicitly targeted instance data literals reuse their
existing owners. Missing defaults, bare null and untargeted data require explicit
types. Static construction restrictions and all other initializer gates remain.
Synthesized data construction consumes inferred fields without a parallel path.

The [authoritative default contract](../../../../specs/data_initialization.md#default-based-property-type-inference--v02-2026-10-07)
defines inference and dependency behavior. Focused proofs extend
`tests/property_defaults.php`, including imported constant type edits, fresh-build
agreement, failed null inference/retry and repair.
`/tmp/ch10-property-inference-1/` passes 31 generated programs without PCH;
`/tmp/ch10-property-inference-regression/` passes six focused PHP suites.
No native compiler rebuild or conversion was performed.

## Direct local ownership shortcuts — 2026-10-07

Bare `value`, `shared` and `unique` local declarations with direct `new` initializers
now resolve to complete storage types in shared preparation. Parentheses normalize
normally; registered ownership identities and the existing storage policy govern
admission. No AST/backend alternative or intermediate construction is added.
Factories, existing variables, weak allocation and nonlocal incomplete types remain
rejected. Redundant class/shared and struct/value requests remain errors.

Evidence: `/tmp/ch10-shortcuts-2/` passes 51 generated constructor programs with PCH;
`/tmp/ch10-shortcuts-final-php/` adds declaration-kind invalidation/retry/repair and
preparation-reset proof. Five other PHP suites passed in
`/tmp/ch10-shortcuts-regression/`; its constructor fixture initially needed its
entry restored before the older re-add check, corrected in the final PHP run.
`/tmp/ch10-shortcuts-equivalence/` also proves identical emitted artifacts for
shortcut and explicit storage spellings across all four supported class/struct pairs.
No native compiler rebuild. The timing review below is now complete.

## Chapter 09 carryovers

CH09's approved bounded closeout transfers constructor integration for explicit
`value<T>` (TYPE-VAR-006), nullable inline records (TYPE-VAR-006A), and discussion of
bare `value`/`shared`/`unique` constructor shortcuts (TYPE-VAR-007) here (now bounded complete). Preserve
existing struct value/copy behavior; do not mark argument-bearing construction or
nullable record operations complete from those earlier proofs. Explicit types remain
the boundary outside the approved direct-local shortcuts. Include both class and struct forms.
Enum methods/interfaces (ENUM-004) must revisit the shared CH10/11 foundations,
not establish a parallel enum-only method system in CH09.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Progress

### Reconciliation — 2026-10-07


**Current scope — 2026-10-19 (CS399).** Historical row totals and nine diagnostic/emission probes are dated evidence, not coverage. Later nullable, protected/interface, inherited/static dispatch and callable work supersedes early gates; exact current cards and owning suites govern. Native checkpoint status above also supersedes the old pending native-build statement. Historical source examples stay intact and do not require identical lowering.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [static_members_v0_2.md](../../../../specs/static_members_v0_2.md), [data_initialization.md](../../../../specs/data_initialization.md), [type_use_modifiers.md](../../../../specs/type_use_modifiers.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS399`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

Reconciled the working rows and individual card decisions against the dated CH10
proofs, current preparation/emission and authoritative amendments. Original imported
examples remain intact as historical evidence, not additional implementation work.
Counts include one synthetic data row and two prose rows; they are not code coverage:
50 supported-bounded, 17 deferred-CH11, 5 deliberate rejections, 3 gated,
4 partial-split, 1 reconciled-bounded and 1 deferred-legacy
(81 rows including the separate legacy table).

`partial-split` means the imported example combines supported concrete-record work
with a still-unimplemented qualifier such as protected/interface
access. `gated` records an intentional current rejection, not a promise that its
broader lifetime/callable model has been implemented. No new language behavior was
enabled by this reconciliation.

Focused PHP-host audit `/tmp/ch10-reconciliation/` confirms emitted qualified
construction and vector-valued method parameters; preparation rejects unknown fields
and overloads, parsing rejects dynamic member names/callable-variable invocation,
and at the audit revision `?Class`/`?Struct` initialization hit the scalar-only
nullable-operation gate (superseded by the nullable checkpoints above).
These nine diagnostic/emission probes are not generated-program execution or
a native compiler rebuild. Existing checkpoint suites supply behavioral evidence.

</details>

### Actual remaining work and owners


**Current scope — 2026-10-19 (CS400).** Nullable joins, property operations and direct-new shortcuts have separate bounded proof above. The table routes CH11 relationships/dispatch and CH12 callable/lifetime obligations rather than certifying them here; stale deferred-CH11 and native-build-pending labels require later reconciliation. Static reference escape and broader receiver lifetimes remain explicit gates. Per-card proof is still required below.

Contracts and owners: [expression_evaluation.md](../../../../specs/expression_evaluation.md), [static_members_v0_2.md](../../../../specs/static_members_v0_2.md), [data_initialization.md](../../../../specs/data_initialization.md), [type_use_modifiers.md](../../../../specs/type_use_modifiers.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS400`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

1. **Nullable inline-record joins — bounded complete.**
   The normalization checkpoint above settles TYPE-VAR-004 for class handles and
   updates the earlier audit result. Nullable inline portions of TYPE-PARAM-004/
   TYPE-PROP-002 and the CH09 carryover now have bounded construction,
   payload-dependent copy capability, checked extraction, presence and condition
   admission and exact copyable-payload selection joins. Bare handles
   retain their own absence state; inline records require a real nullable wrapper.
2. **Property operation completeness — bounded updates complete.** Plain reads/writes,
   compound assignment and prefix/postfix mutation share existing property places.
   Static-property native-reference escapes and nested plain assignment expressions
   remain gated; property probes retain their separate admission boundary.
3. **Construction conveniences — bounded complete.** Explicit/default-inferred
   fields and direct-local value/shared/unique shortcuts are covered. Complete
   storage types remain required outside the approved direct-new declaration form.
4. **Existing later owners, not new CH10 blockers.** Interface/abstract/protected,
   inherited lookup and virtual/late-static dispatch belong to CH11. Method receiver
   lifetime extensions are already CH15-METHOD-LIFETIME-01. Broader reference returns
   retain CH07/CH15 debt and current same-origin restrictions. Bound method callable
   values await the CH12 callable/lifetime foundation; this reconciliation does not
   approve that feature or reinterpret a plain two-element list as a callable.
5. **Closeout.** The grouped feature work and CH10-PERF-01 review are complete.
   Native compiler validation remains; include the dedicated CH10 suites, not only
   the older CH08/09 native acceptance set. Property probes, nested plain property
   assignments and static reference escapes remain explicitly gated, not silently enabled.
   CH10 is not closed by replacing stale pending labels.

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [CH10-DATA-001](#authored-constructors--2026-10-07) | supported-bounded | `data struct A { int $x; } $a A = {x: 1};` | prepared | verified | deferred | Shared synthesized/authored typed inputs; `records.py --suite constructors` |
| [CLASS-DECL-001](#class-decl-001) | supported-bounded | `class A {}` | prepared | verified | deferred | Plain class/struct records; `tests/records.py` |
| [CLASS-PROP-001](#class-prop-001) | supported-bounded | `class A { int $x; }` | prepared (bounded) | see proof | deferred | Typed public/private fields; protected access CH11 |
| [CLASS-PROP-002](#class-prop-002) | supported-bounded | `class A { int $x = 1; }` | prepared | verified | deferred | Class/struct constant and data-construction defaults; `records.py --suite property_defaults` |
| [CLASS-PROP-003](#class-prop-003) | supported-bounded | `class A { int $x; }` | prepared | verified | deferred | Public typed instance property; no initializer |
| [CLASS-PROP-004](#class-prop-004) | supported-bounded | `$this->x = 1;` | prepared | verified | deferred | Constructors, methods and destructors; declared writable fields |
| [CLASS-PROP-005](#class-prop-005) | supported-bounded | `$a->x = 1;` | prepared | verified | deferred | Stable receiver; heap interiors not borrowable |
| [CLASS-PROP-006](#class-prop-006) | supported-bounded | `A::$x = 1;` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-PROP-006A](#class-prop-006a) | supported-bounded | `self::$x = 1;` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-PROP-006B](#class-prop-006b) | implemented-CH11 | `parent::$x = 1;` | verified | verified | deferred | Original-owner static storage; CH11 parent checkpoint |
| [CLASS-PROP-006C](#class-prop-006c) | deferred-CH11 | `static::$x = 1;` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-PROP-007](#class-prop-007) | supported-bounded | `$a::$x = 2;` | shared static member | qualifier before RHS | deferred | Reads, plain/compound writes and updates; reference escapes gated |
| [CLASS-PROP-008](#class-prop-008) | deferred-CH11 | `class B extends A { function f(): void { $this->x = 1; } }` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-PROP-009](#class-prop-009) | rejected | `$this->y = 1;` where `y` is undeclared | rejected | not emitted | deferred | Unknown property; preparation rejection verified in records.php and audit |
| [CLASS-PROP-010](#class-prop-010) | rejected | `$this->$name = 1;` | rejected | not emitted | deferred | Computed property names reject in parsing; dynamic-family -> stays CH15 |
| [CLASS-PROP-011](#class-prop-011) | supported-bounded | `class A { public int $x = 1; public static int $s = 2; }` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-CALL-001](#class-call-001) | supported-bounded | `$a->f(1);` | prepared | verified | deferred | Fixed instance slice; receiver lifetime gates apply |
| [CLASS-CALL-002](#class-call-002) | supported-bounded | `A::f(1);` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-CALL-003](#class-call-003) | supported-bounded | `$a::f(1);` | fixed concrete target | evaluate once before arguments | deferred | Expression-qualified static-call checkpoint |
| [CLASS-CALL-004](#class-call-004) | supported-bounded | `$this->f(1);` | prepared | verified | deferred | Fixed instance slice; receiver lifetime gates apply |
| [CLASS-CALL-005](#class-call-005) | implemented-CH11 | `parent::f(1);` | verified | verified | deferred | Fixed ancestor dispatch; CH11 parent checkpoint |
| [CLASS-CALL-005A](#class-call-005a) | deferred-CH11 | `static::f(1);`<br>`static::f(1)` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-CALL-006](#class-call-006) | supported-bounded | `function f(): int { return 1; }` | prepared (bounded) | see proof | deferred | Ordinary methods are nonvirtual; methods.php; inheritance CH11 |
| [CLASS-CALL-007](#class-call-007) | deferred-CH11 | `$i->f(1);` | see reconciliation | see reconciliation | deferred | Interface relationships and dispatch |
| [CLASS-CALL-008](#class-call-008) | supported-bounded | `new A(1)` | prepared | verified | deferred | Fixed public constructor; class/struct and storage variants |
| [CLASS-CALL-008A](#class-call-008a) | deferred-CH11 | `new static(1)` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-CALL-009](#class-call-009) | rejected | `$a->$name();` | rejected | not emitted | deferred | Computed method names reject in parsing; existing contract |
| [CLASS-CALL-010](#class-call-010) | gated | `[$a, 'f']` | see reconciliation | see reconciliation | deferred | Bound callable invocation needs a callable-value contract; plain list data is not forbidden |
| [CLASS-CALL-011](#class-call-011) | rejected | explicit destructor invocation pattern | preparation rejection | not emitted | deferred | Automatic ownership-driven cleanup only |
| [CLASS-CALL-012](#class-call-012) | supported-bounded | `static function g(int $x): int { return $x; }` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-CONST-001](#class-const-001) | supported-bounded | `class A { public const X = 1; }` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-CONST-002](#class-const-002) | supported-bounded | `A::X` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-CONST-003](#class-const-003) | supported-bounded | `self::X` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-CONST-004](#class-const-004) | implemented-CH11 | `parent::X` | verified | verified | deferred | Original-owner constant access; CH11 parent checkpoint |
| [CLASS-CONST-005](#class-const-005) | deferred-CH11 | `static::X` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-MEMBER-001](#class-member-001) | supported-bounded | `function f(int $a): int { return $a; }` | prepared | verified | deferred | Fixed instance slice; receiver lifetime gates apply |
| [CLASS-MEMBER-001B](#class-member-001b) | supported-bounded | `function f(vector<int> $a): int { return 1; }` | prepared (bounded) | see proof | deferred | Strict vector<T> parameters; method_parameters.php; PHPDoc spelling remains legacy |
| [CLASS-MEMBER-002](#class-member-002) | supported-bounded | `function f(int $a): int { return $a; }` | prepared (bounded) | see proof | deferred | Explicit ordinary return types; constructor/destructor exceptions |
| [CLASS-MEMBER-003](#class-member-003) | supported-bounded | `function f(int $value): int { return $value; }` | prepared (bounded) | see proof | deferred | Parameter identity and normalized names preserved; shared callable tests |
| [CLASS-MEMBER-004](#class-member-004) | supported-bounded | `static function f(int $a): int { return $a; }` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-MEMBER-005](#class-member-005) | supported-bounded | `function f(int $a): int { return $a; }` | prepared (bounded) | see proof | deferred | Implicit instance context; methods.php |
| [CLASS-MEMBER-006](#class-member-006) | deferred-CH11 | `abstract function f(int $a): int;` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-MEMBER-007](#class-member-007) | deferred-CH11 | `#[\Override] function f(int $a): int { return $a; }` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-MEMBER-008](#class-member-008) | deferred-CH11 | `final function f(int $a): int { return $a; }` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-MEMBER-009](#class-member-009) | deferred-CH11 | `abstract static function f(): int;` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-MEMBER-010](#class-member-010) | deferred-CH11 | `#[\Override] static function f(): int { return 1; }` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-MEMBER-011](#class-member-011) | supported-bounded | `function __construct(int $x) { $this->x = $x; }` | prepared | verified | deferred | Shared callable lifecycle and out-of-line body; also `set:` |
| [CLASS-MEMBER-012](#class-member-012) | supported-bounded | `function __destruct() {}` | shared callable | out-of-line noexcept | deferred | Public, zero inputs; storage-driven cleanup |
| [CLASS-MEMBER-013](#class-member-013) | deferred-CH11 | `interface I { function f(int $a): int; }` | see reconciliation | see reconciliation | deferred | Interface contracts and dispatch |
| [CLASS-MEMBER-014](#class-member-014) | supported-bounded | `private function f(): int { return 1; }` | prepared | verified | deferred | Fixed instance slice; receiver lifetime gates apply |
| [CLASS-MEMBER-015](#class-member-015) | rejected | `function f(int $a): int {} function f(string $a): int {}` | rejected | not emitted | deferred | Duplicate method names reject during preparation; no overloading |
| [CLASS-MEMBER-016](#class-member-016) | supported-bounded | `function f(int $a = 1): int { return $a; }` | prepared | verified | deferred | Fixed instance slice; receiver lifetime gates apply |
| [CLASS-MEMBER-017](#class-member-017) | supported-bounded | `function f(int ...$a): int { return 1; }` | prepared | verified | deferred | Shared method parameters; records.py --suite method_parameters |
| [CLASS-MEMBER-018A](#class-member-018a) | supported-bounded | `function f(int &$a): void {}` | prepared | verified | deferred | Shared method parameters; records.py --suite method_parameters |
| [CLASS-MEMBER-018B](#class-member-018b) | gated | `function &f(): int {}` | see reconciliation | see reconciliation | deferred | No-origin reference return rejected; exactly one writable reference parameter required |
| [CLASS-MEMBER-018C](#class-member-018c) | supported-bounded | `function &f(int &$a): int {}` | shared callable | same-origin checked | deferred | Complete body required; same-origin method return checkpoint |
| [CLASS-MEMBER-018D](#class-member-018d) | supported-bounded | explicit PHP reference syntax on method/member signature | shared callable | exact stable storage | deferred | Fixed references and same-origin returns only |
| [CLASS-MEMBER-018E](#class-member-018e) | gated | `function &f() { return $this->x; }` | see reconciliation | see reconciliation | deferred | Receiver/property reference escape not enabled; method_returns.php |
| [CLASS-PROP-002A](#class-prop-002a) | supported-bounded | `class A { static int $x = 1; }` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-PROP-002B](#class-prop-002b) | supported-bounded | `self::$x` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-PROP-002C](#class-prop-002c) | implemented-CH11 | `parent::$x` | verified | verified | deferred | Original-owner static storage; CH11 parent checkpoint |
| [CLASS-PROP-002D](#class-prop-002d) | deferred-CH11 | `static::$x` | unverified | unverified | deferred | CH11 relationship, access and dispatch prerequisites |
| [CLASS-METHOD-001](#class-method-001) | supported-bounded | `class A { function f(): int { return 1; } }` | prepared | verified | deferred | Fixed instance slice; receiver lifetime gates apply |
| [CLASS-METHOD-002](#class-method-002) | supported-bounded | `class A { static function f(int $a): int { return $a; } }` | prepared | verified | deferred | Static member slice; records.py --suite static_members |
| [CLASS-THIS-001](#class-this-001) | supported-bounded | `class A { int $x; function f(): int { return $this->x; } }` | prepared | verified | deferred | Fixed instance slice; receiver lifetime gates apply |
| [CLASS-NEW-001](#class-new-001) | supported-bounded | `$a = new A();` | prepared (bounded) | see proof | deferred | Class/struct storage matrix; records.php and constructors.php |
| [CLASS-NEW-002](#class-new-002) | supported-bounded | `$a = new \A\B();` | prepared (bounded) | see proof | deferred | Qualified construction accepted/emitted in audit; no new generated execution proof |
| [CLASS-CONSTRUCT-001](#class-construct-001) | supported-bounded | `class A { function __construct(int $x) { $this->x = $x; } }` | prepared | verified | deferred | Public constructor, declared properties, out-of-line body; no return type |
| [OBJ-PROP-READ-001](#obj-prop-read-001) | supported-bounded | `$a = $obj->x;` | prepared (bounded) | see proof | deferred | Fixed record properties; records.php; dynamic-family -> CH15 |
| [OBJ-PROP-WRITE-001](#obj-prop-write-001) | supported-bounded | `$obj->x = 1;` | prepared (bounded) | see proof | deferred | Plain fixed property writes; records.php; compound/receiver extensions separate |
| [OBJ-METHOD-CALL-001](#obj-method-call-001) | supported-bounded | `$a = $obj->f();` | prepared | verified | deferred | Fixed instance slice; receiver lifetime gates apply |
| [OBJ-METHOD-CALL-002](#obj-method-call-002) | supported-bounded | `$a = $obj->f(1);` | prepared | verified | deferred | Fixed instance slice; receiver lifetime gates apply |
| [TYPE-VAR-003](#type-var-003) | supported-bounded | `$x A = new A();` | prepared (bounded) | see proof | deferred | Explicit record storage construction; records.php and constructors.php |
| [TYPE-VAR-004](#type-var-004) | supported-bounded | `$x ?A = null;` | normalized class handle | shared existing handle path | deferred | nullable_handles.php; inline record operations remain separate |
| [TYPE-PARAM-004](#type-param-004) | partial-split | `function f(A $a, ?B $b, I $i): A { return $a; }` | bounded concrete/nullable boundaries | proved bounded | deferred | nullable_records.php; interfaces/abstract types CH11 |
| [TYPE-PROP-001](#type-prop-001) | partial-split | `class A { protected string $s; protected B $b; }` | see reconciliation | see reconciliation | deferred | Public/private typed fields supported; protected access CH11 |
| [TYPE-PROP-001B](#type-prop-001b) | supported-bounded | `class A { public int $x; }` | prepared (bounded) | see proof | deferred | Strict public typed field; records.php; legacy spelling separate |
| [TYPE-PROP-002](#type-prop-002) | partial-split | `class A { protected ?string $x; protected ?B $b; }` | bounded nullable fields | proved bounded | deferred | nullable_records.php; protected access CH11 |
| [NOTE-029](#note-029) | reconciled-bounded | — (example pending) | see reconciliation | see reconciliation | deferred | Construction/static slices supersede imported struct-argument and unevaluated-qualifier restrictions |
| [NOTE-050](#note-050) | partial-split | — (example pending) | see reconciliation | see reconciliation | deferred | Layout/members/lifecycle, inference, updates and shortcuts supported; relationships/dispatch CH11 |

</details>

### Deferred legacy syntax

These syntax-specific entries are retained for traceability, not scheduled before strict-mode features.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [CLASS-PROP-001B](#class-prop-001b) | deferred-legacy | `class A { public /** int */ int $x; }` | unverified | unverified | deferred | Legacy annotation syntax only; outside strict-first work |
## CLASS-DECL-001

**v0.2 decision / target C++:** supported-bounded. Plain class/struct records; `tests/records.py`.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Typed public/private fields; protected access CH11.
See the progress table and dated checkpoints; imported text below is historical.

Different imported versions share this ID and remain below for provenance. The current v0.2 decision and bounded checkpoints above govern; their presence does not imply additional pending implementation.

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

**v0.2 decision / target C++:** deferred-legacy. Strict typed properties are covered separately.

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

**v0.2 decision / target C++:** supported-bounded. Class/struct constant and data-construction defaults; `records.py --suite property_defaults`.
See the progress table and dated checkpoints; imported text below is historical.

Different imported versions share this ID and remain below for provenance. The current v0.2 decision and bounded checkpoints above govern; their presence does not imply additional pending implementation.

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

**v0.2 decision / target C++:** supported-bounded. Public typed instance property; no initializer.
See the progress table and dated checkpoints; imported text below is historical.

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


**Current scope — 2026-10-19 (CS407).** Completed ordinary set():void method assigns this->x=1 and caller reads 1. This directly supersedes the card line claiming general method execution deferred. Missing-instance/static-this contexts still reject under separate method tests; the source spelling does not authorize arbitrary receiver escape.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [static_members_v0_2.md](../../../../specs/static_members_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS407`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** supported-bounded. Constructor bodies; general method execution deferred.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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

**v0.2 decision / target C++:** supported-bounded. Stable receiver; heap interiors not borrowable.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Static member slice; records.py --suite static_members.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Static member slice; records.py --suite static_members.
See the progress table and dated checkpoints; imported text below is historical.

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


**Current scope — 2026-10-19 (CS411).** Child static method parent::$x updates original A storage and caller reads A::$x=1. The card deferred-CH11 label is stale. Normative lookup begins at immediate base while retaining actual owner/access; this probe does not establish all protected/private/ancestor cases.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [static_members_v0_2.md](../../../../specs/static_members_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS411`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** deferred-CH11. CH11 relationship, access and dispatch prerequisites.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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


**Current scope — 2026-10-19 (CS412).** Inherited static method using static::$x, called as B::set(), updates original A storage to 1. Original probe added a redeclared B::$x and correctly failed inherited-member-redeclaration admission; follow-up removes that out-of-scope feature. The pass proves inherited call/original-owner storage, not overridden static properties or by itself a discriminator between self and called-type selection. Broader late-static dispatch retains CH11 proofs; v0.1 file-local-only restriction is superseded.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [static_members_v0_2.md](../../../../specs/static_members_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS412`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** deferred-CH11. CH11 relationship, access and dispatch prerequisites.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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


**Current scope — 2026-10-19 (CS414).** Completed derived B method writes inherited A::x through this and native read returns 1. Deferred-CH11 status is stale; inherited member identity/access belongs to shared preparation, superseding historical no-cross-file-validation policy. The single-file probe does not itself prove cross-file edits.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [static_members_v0_2.md](../../../../specs/static_members_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS414`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** deferred-CH11. CH11 relationship, access and dispatch prerequisites.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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

**v0.2 decision / target C++:** rejected. Unknown property; preparation rejection verified in records.php and audit.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** rejected. Computed property names reject in parsing; dynamic-family -> stays CH15.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Static member slice; records.py --suite static_members.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Fixed instance slice; receiver lifetime gates apply.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Static member slice; records.py --suite static_members.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Fixed instance slice; receiver lifetime gates apply.
See the progress table and dated checkpoints; imported text below is historical.

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


**Current scope — 2026-10-19 (CS422).** Completed B::g instance method calls parent::f(1) on the current instance and returns1. Deferred-CH11 label is stale. Immediate-base lookup/access and original owner govern selection; no literal base alias spelling or general virtual-dispatch claim follows.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [static_members_v0_2.md](../../../../specs/static_members_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS422`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** deferred-CH11. CH11 relationship, access and dispatch prerequisites.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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


**Current scope — 2026-10-19 (CS423).** Inherited A::g uses static::f(1), with A::f returning9 and B::f returning its argument. Native B::g result1 distinguishes called-type child selection from lexical-base selection. Deferred label and historical file-local restriction are superseded; this single-file probe does not independently establish cross-file specialization.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [static_members_v0_2.md](../../../../specs/static_members_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS423`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** deferred-CH11. CH11 relationship, access and dispatch prerequisites.
See the progress table and dated checkpoints; imported text below is historical.

Different imported versions share this ID and remain below for provenance. The current v0.2 decision and bounded checkpoints above govern; their presence does not imply additional pending implementation.

</details>

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

**v0.2 decision / target C++:** supported-bounded. Ordinary methods are nonvirtual; methods.php; inheritance CH11.
See the progress table and dated checkpoints; imported text below is historical.

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


**Current scope — 2026-10-19 (CS425).** Completed interface I, implementing A and interface-typed handle invoke f(1) and return1. This is an actual interface call, not a concrete-only method probe. Historical deferred-CH11 and local/syntactic diagnostics are superseded; interface obligations and dispatch are prepared semantically. Multiple-interface/override variance behavior retains CH11 proof.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [static_members_v0_2.md](../../../../specs/static_members_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md), [destructors_v0_2.md](../../../../specs/destructors_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS425`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** deferred-CH11. Interface relationships and dispatch.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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

**v0.2 decision / target C++:** supported-bounded. Fixed public constructor; class/struct and storage variants.
See the progress table and dated checkpoints; imported text below is historical.

Different imported versions share this ID and remain below for provenance. The current v0.2 decision and bounded checkpoints above govern; their presence does not imply additional pending implementation.

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


**Current scope — 2026-10-19 (CS427).** Inherited static factory on abstract A returns new static(1); B supplies constructor and read implementation, and B::make()->read() returns1 through the A result boundary. This proves called-class shared construction and initialized payload, not merely accepting new static syntax. File-local-only and deferred-CH11 text is superseded; cross-file/more storage families retain separate scope.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [static_members_v0_2.md](../../../../specs/static_members_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md), [destructors_v0_2.md](../../../../specs/destructors_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS427`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** deferred-CH11. CH11 relationship, access and dispatch prerequisites.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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

**v0.2 decision / target C++:** rejected. Computed method names reject in parsing; existing contract.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** gated. Bound callable invocation needs a callable-value contract; plain list data is not forbidden.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** Rejected during preparation. Destruction follows ownership.

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

**v0.2 decision / target C++:** supported-bounded. Static member slice; records.py --suite static_members.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Static member slice; records.py --suite static_members.
See the progress table and dated checkpoints; imported text below is historical.

Different imported versions share this ID and remain below for provenance. The current v0.2 decision and bounded checkpoints above govern; their presence does not imply additional pending implementation.

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

**v0.2 decision / target C++:** supported-bounded. Static member slice; records.py --suite static_members.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Static member slice; records.py --suite static_members.
See the progress table and dated checkpoints; imported text below is historical.

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


**Current scope — 2026-10-19 (CS435).** B::f reads parent::X declared on A and returns1. Historical deferred-CH11 wording is superseded by prepared inheritance lookup. Missing-parent rejection is a separate negative obligation, not proved by this positive.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [static_members_v0_2.md](../../../../specs/static_members_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS435`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** deferred-CH11. CH11 relationship, access and dispatch prerequisites.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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


**Current scope — 2026-10-19 (CS436).** Inherited A::f invoked as B::f admits static::X and returns inherited constant1. Because both class contexts resolve the same declaration, this fixture does not distinguish lexical from called-class selection. Historical file-local-only and deferred-CH11 restrictions are superseded by the inheritance contract; broader late-static and cross-file proofs remain in CH11.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [static_members_v0_2.md](../../../../specs/static_members_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS436`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** deferred-CH11. CH11 relationship, access and dispatch prerequisites.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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

**v0.2 decision / target C++:** supported-bounded. Fixed instance slice; receiver lifetime gates apply.
See the progress table and dated checkpoints; imported text below is historical.

Different imported versions share this ID and remain below for provenance. The current v0.2 decision and bounded checkpoints above govern; their presence does not imply additional pending implementation.

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

This strict spelling is supported by the shared method parameter contract. The
imported PHPDoc annotation remains legacy-only.

**v0.2 decision / target C++:** supported-bounded. See method_parameters.php and the reconciliation probe.

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

**v0.2 decision / target C++:** supported-bounded. Explicit ordinary return types; constructor/destructor exceptions.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Parameter identity and normalized names preserved; shared callable tests.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Static member slice; records.py --suite static_members.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Implicit instance context; methods.php.
See the progress table and dated checkpoints; imported text below is historical.

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


**Current scope — 2026-10-19 (CS443).** Abstract A::f is implemented by B and invoked through an A-typed handle with result1. Historical CH11 deferral is superseded. Bodyless declaration and virtual contract are exercised; abstract-body rejection retains dedicated inheritance-suite scope.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md), [destructors_v0_2.md](../../../../specs/destructors_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS443`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** deferred-CH11. CH11 relationship, access and dispatch prerequisites.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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


**Current scope — 2026-10-19 (CS444).** Explicit Override implementation of abstract A::f dispatches through A handle and returns1. Historical no-semantic-validation claim is superseded: the attribute asserts an inherited virtual/interface contract during preparation. Positive dispatch is not by itself an invalid-override rejection proof.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md), [destructors_v0_2.md](../../../../specs/destructors_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS444`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** deferred-CH11. CH11 relationship, access and dispatch prerequisites.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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


**Current scope — 2026-10-19 (CS445).** Inherited final method on B returns supplied argument1. This proves final declaration admission and inherited call, not rejection of a further override; finality negatives remain owned by CH11. Historical deferred label is stale.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md), [destructors_v0_2.md](../../../../specs/destructors_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS445`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** deferred-CH11. CH11 relationship, access and dispatch prerequisites.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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


**Current scope — 2026-10-19 (CS446).** Abstract static method in an explicitly abstract class rejects with Abstract methods must be non-final public/protected instance methods. The diagnostic isolates the prohibited modifier combination; historical deferred label should describe current rejection.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md), [destructors_v0_2.md](../../../../specs/destructors_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS446`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** deferred-CH11. CH11 relationship, access and dispatch prerequisites.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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


**Current scope — 2026-10-19 (CS447).** Child static method with Override over an existing base static method rejects with Override assertion requires an inherited virtual method. Both signatures and bodies are valid; static hiding does not establish a virtual override contract. Historical deferred label is stale.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md), [destructors_v0_2.md](../../../../specs/destructors_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS447`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** deferred-CH11. CH11 relationship, access and dispatch prerequisites.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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

**v0.2 decision / target C++:** supported-bounded. Shared callable lifecycle and out-of-line body; also `set:`.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** Public instance destructor with no inputs/return
annotation; out-of-line nonthrowing body on the shared method lifecycle.

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


**Current scope — 2026-10-19 (CS450).** Bodyless interface I::f is implemented by A and called through I handle with argument/result1. Deferred-CH11 wording is superseded. This proves this instance interface contract; it does not generalize the historical always-pure-virtual statement to later static-interface extensions.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md), [destructors_v0_2.md](../../../../specs/destructors_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS450`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** deferred-CH11. Interface contracts and dispatch.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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

**v0.2 decision / target C++:** supported-bounded. Fixed instance slice; receiver lifetime gates apply.
See the progress table and dated checkpoints; imported text below is historical.

Different imported versions share this ID and remain below for provenance. The current v0.2 decision and bounded checkpoints above govern; their presence does not imply additional pending implementation.

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

**v0.2 decision / target C++:** rejected. Duplicate method names reject during preparation; no overloading.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Fixed instance slice; receiver lifetime gates apply.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Shared method parameters; records.py --suite method_parameters.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Shared method parameters; records.py --suite method_parameters.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** gated. No-origin reference return rejected; exactly one writable reference parameter required.
See the progress table and dated checkpoints; imported text below is historical.

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


**Current scope — 2026-10-19 (CS457).** Completed same-origin reference-return body produces an owned copy1 and explicit alias; alias write2 updates original local while copy remains1, combined result12. Empty imported body is not treated as executable valid code. Current preparation validates origin despite historical purely-explicit wording.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md), [destructors_v0_2.md](../../../../specs/destructors_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS457`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Supported with a complete same-origin body; shared
callable signatures preserve `T&`. See the method-return checkpoint above.

</details>

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


**Current scope — 2026-10-19 (CS458).** Shared same-origin method probe distinguishes ordinary copied result from explicit alias with result12. Explicit markers preserve reference intent, but historical no semantic inference or alias analysis claim is superseded by preparation lifetime/origin validation.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md), [destructors_v0_2.md](../../../../specs/destructors_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS458`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Supported for fixed native-reference parameters and
bounded same-origin method returns; explicit markers do not bypass lifetime checks.

</details>

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

**v0.2 decision / target C++:** gated. Receiver/property reference escape not enabled; method_returns.php.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Static member slice; records.py --suite static_members.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Static member slice; records.py --suite static_members.
See the progress table and dated checkpoints; imported text below is historical.

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


**Current scope — 2026-10-19 (CS462).** B::f reads parent::$x from A and returns1. Prepared inherited lookup is supported; historical deferred-CH11 label is documentation drift. Missing-parent contexts are separate negatives.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [static_members_v0_2.md](../../../../specs/static_members_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS462`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** deferred-CH11. CH11 relationship, access and dispatch prerequisites.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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


**Current scope — 2026-10-19 (CS463).** Follow-up B::f reads inherited A::$x via static::$x and returns1 without redeclaring the property. Original failed redeclaration attempt remains distinct. This proves original-owner inherited storage, not a lexical/called-class discriminator or permitted property overriding. Historical file-local-only and deferred labels are superseded.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [static_members_v0_2.md](../../../../specs/static_members_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS463`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** deferred-CH11. CH11 relationship, access and dispatch prerequisites.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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

**v0.2 decision / target C++:** supported-bounded. Fixed instance slice; receiver lifetime gates apply.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Static member slice; records.py --suite static_members.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Fixed instance slice; receiver lifetime gates apply.
See the progress table and dated checkpoints; imported text below is historical.

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


**Current scope — 2026-10-19 (CS467).** Ordinary new A produces a present shared handle observed by isset and native result1. This proves construction and presence, not a mandatory literal `create<A>` spelling. Current preparation resolves nominal targets and storage policy; generation is not solely syntactic.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [type_use_modifiers.md](../../../../specs/type_use_modifiers.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS467`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** supported-bounded. Class/struct storage matrix; records.php and constructors.php.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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


**Current scope — 2026-10-19 (CS468).** Rooted new \A\B constructs the declared namespaced class and reads initialized field1. This supplies actual generated execution beyond the card outdated no-execution checkpoint. Historical type-blind/rooted C++ spelling restrictions are superseded by prepared identity and partitioned backend naming.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [type_use_modifiers.md](../../../../specs/type_use_modifiers.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS468`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** supported-bounded. Qualified construction accepted/emitted in audit; no new generated execution proof.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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

**v0.2 decision / target C++:** supported-bounded. Public constructor, declared properties, out-of-line body; no return type.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Fixed record properties; records.php; dynamic-family -> CH15.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Plain fixed property writes; records.php; compound/receiver extensions separate.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Fixed instance slice; receiver lifetime gates apply.
See the progress table and dated checkpoints; imported text below is historical.

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

**v0.2 decision / target C++:** supported-bounded. Fixed instance slice; receiver lifetime gates apply.
See the progress table and dated checkpoints; imported text below is historical.

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

This strict spelling uses the existing record construction and storage boundary.

**v0.2 decision / target C++:** supported-bounded. See records.php and constructors.php.

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


**Current scope — 2026-10-19 (CS475).** Strict ?A local initialized null compares null and returns1. Current ?handle normalization is semantic identity, not a pending non-null runtime check. This absence observation alone does not prove reference identity normalization; dedicated nullable-handle suite owns that.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [type_use_modifiers.md](../../../../specs/type_use_modifiers.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS475`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**Strict-mode PHP input example:** `$x ?A = null;`

For a class, nullable spelling now explicitly normalizes to its already-nullable
handle identity. For a struct/inline class payload, nullable remains a real wrapper.

**v0.2 decision / target C++:** supported-bounded for class handles under the explicit
normalization contract, not merely accidental backend equivalence. See checkpoint above.

</details>

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


**Current scope — 2026-10-19 (CS476).** Corrected context supplies A, B and interface I implementations; function checks nullable B and calls I::n before returning the A handle. Caller mutates returned handle to7 and original A reads7. Wrong null/interface branch returns fresh A and would fail this identity observer. Initial failed probe remains separate; current interface support supersedes partial-split label.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [type_use_modifiers.md](../../../../specs/type_use_modifiers.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS476`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** partial-split. Concrete and nullable-record value boundaries supported; interfaces/abstract types CH11. See nullable_records.php.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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


**Current scope — 2026-10-19 (CS477).** Protected string and B fields are assigned/read inside their owner, producing exact stdout ok and present-handle result1. Corrected probe uses local B for isset rather than unsupported direct property operand. This proves legal protected-field use, not every external/inherited access boundary. Historical blanket protected-access deferral is stale.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [type_use_modifiers.md](../../../../specs/type_use_modifiers.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS477`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** partial-split. Public/private typed fields supported; protected access CH11.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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

This strict spelling uses the shared typed property model. Imported PHPDoc spelling
is not newly enabled.

**v0.2 decision / target C++:** supported-bounded. See records.php.

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


**Current scope — 2026-10-19 (CS479).** Protected nullable string and nullable class fields default to null and native combined predicate returns1. This observes both empty states. Wrapper versus normalized handle representation follows current type-use contract; result alone does not prove exact C++ wrapper spelling. Historical protected-access deferral is stale.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [type_use_modifiers.md](../../../../specs/type_use_modifiers.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS479`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** partial-split. Nullable scalar and inline-record fields supported; protected access CH11. See nullable_records.php.
See the progress table and dated checkpoints; imported text below is historical.

</details>

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


**Current scope — 2026-10-19 (CS480).** Imported struct no-argument-only restriction and unevaluated expression-qualified static access are intentionally superseded. Current constructor/storage proofs cover class and struct arguments plus explicit local ownership; expression qualifiers execute once and release temporaries before arguments, with preparation rejecting invalid static targets. Rooted construction now has an actual initialized-field execution observer. No literal create/class_t spelling or C++-only semantic rejection is required.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [static_members_v0_2.md](../../../../specs/static_members_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md), [language_verification_plan.md](../planning/language_verification_plan.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS480`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**Source:** [generators/php/specs/rules.md:520](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** reconciled-bounded. Record construction, rooted/imported
lookup and static access have dated checkpoints above. v0.2 now supports constructor
arguments for structs and explicit storage overrides; expression qualifiers execute
once rather than using unevaluated `decltype`. Preparation diagnoses invalid targets.
The incompatible v0.1 restrictions below are superseded, not outstanding tasks.

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

</details>

## NOTE-050


**Current scope — 2026-10-19 (CS481).** Consolidates previously reviewed fields, initialization, method signatures, reference safety, cleanup, static qualification and partitioned emission. Historical type-blind validation, mandatory base alias, file-local late static rules, blanket inline-class prohibition and no-traits statement do not govern v0.2. Relationships, virtual/interface dispatch, trait composition and parent construction retain CH11-specific obligations; this summary does not certify those pending sections. PHPDoc stays legacy-only.

Contracts and owners: [instance_methods_v0_2.md](../../../../specs/instance_methods_v0_2.md), [static_members_v0_2.md](../../../../specs/static_members_v0_2.md), [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md), [language_verification_plan.md](../planning/language_verification_plan.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS481`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**Source:** [generators/php/specs/rules.md:1102](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** partial-split. Partitioned output, typed fields,
public/private members, methods, constructors/destructors and qualified static access
are covered above, along with default-based property inference and compound updates.
Relationships/dispatch belong to CH11. Direct-local ownership shortcuts are covered;
legacy annotation forms remain deferred. Explicit
source types and current preparation diagnostics supersede the v0.1 type-blind rules.

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

</details>

## Chapter-end debt

### CH10-PERF-01 — Test-duration review before native build

Status: reviewed 2026-10-07. See the [measured review](../testing_ch10_review.md).
The pre-build investigation gate is satisfied; optimization is proposed, not applied.
The subsequent [native checkpoint](#native-validation--2026-10-07) passes.

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
