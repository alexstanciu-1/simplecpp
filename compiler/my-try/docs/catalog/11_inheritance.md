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
execute. Shared/weak derived/base upcasts and interface-typed shared handles now execute.

## Remaining-work reconciliation — 2026-10-08

The seven previously pending imported rows below are now reconciled with existing
contracts and retained proofs, not newly implemented. Construction is covered by
`tests/constructors.php` and `tests/inheritance.php`; explicit inline class storage
by `inline_class_copy`. Parent selection uses prepared original owners, not a required
literal C++ `using base` alias. Destruction is covered by `tests/destructors.php`,
inheritance cleanup ordering and `tests/abstract_dispatch.php` virtual-destruction
checks. Existing dated execution evidence remains in their owning checkpoints.

The old blanket prohibition on native inline class construction does not apply to
v0.2's explicit `value<Class>` representation. C++-style `A a(1);` is still not PHS
source syntax. For destructors, valid instance access and an invalid returned value
are distinct; the imported examples must not conflate them.

Derived `data` construction is complete within its agreed bounds: authored/inherited
constructors and composed synthesized inputs. The checkpoints below record evidence.

User scope decision — 2026-10-08:

| Group | Decision |
| --- | --- |
| Shared cross-casts | Implemented: observed relationship admission through existing checked access |
| Non-public destructors | Implemented: independent lifecycle contract and access-aware ownership |
| Variance/covariant returns | Implemented: shared-value adaptation and declaration-specific dispatch bindings/bridges |
| Instance late static binding; inline/struct `new static()` | Deferred to CH15 |
| Unique/weak downcasts; unique/weak conditional joins; sibling conditional joins | Deferred to CH15 |
| Trait adaptations; abstract constructor contracts | Deferred to CH15 |
| Trait constructors/destructors | Previously deferred to CH15 |
| Inherited final-virtual interface forwarding | Implemented: interface view delegates without overriding the inherited final slot |
| Interface-qualified static dispatch | Deferred to CH15; interface-typed static qualifiers remain rejected |

The [CH15 scope register](15_blocking_debt.md#ch15-inheritance-extensions-01--deferred-ch11-extensions)
retains the seven newly deferred items and their prerequisites. Existing unsupported
paths stay rejected; recording the queue does not enable new language semantics.
The three selected groups are implemented; see the
[variance scope and checkpoints](../planning/callable_variance.md).
The two remaining dispatch decisions are now resolved: final-method interface views
are enabled and interface-qualified static dispatch is deferred. CH11's agreed
implementation scope and the requested bounded native compiler validation are complete.

All 25 pre-audit progress rows are resolved within their documented bounds; the
six ownership/type-access rows added below make already-proven coverage explicit. This is
not a claim that every extension above is required for v0.2. The selected groups are
implemented; deferred extensions remain explicitly gated. Broader protected receiver access is already
non-blocking [CH15 debt](15_blocking_debt.md#ch15-protected-01--broader-protected-receiver-access).

Verification: the [CH11 native checkpoint](../portability/conversion_review.md#latest-native-checkpoint--2026-10-08-chapter-11)
passes cumulative native validation plus 1,569 chapter source comparisons (three
runs of 523 snapshots) and 518 generated-program executions. Chapter-specific
rejection/edit assertions remain host checks; the checkpoint states the cumulative
native rejection/incremental scope. Legacy STAN and LLVM are explicitly skipped.
The dated sections below are historical checkpoints;
their slice-local exclusions and next steps do not override this current handoff.

## Shared callable variance — 2026-10-08

Virtual overrides, interfaces and trait requirements share fixed shared-parameter
contravariance and shared-result covariance. References, variadics and other ownership
families remain invariant. Source identities and member lookup stay unchanged; prepared
bindings distinguish required contracts and slot owners from selected implementations.
C++ source entries, authored bodies and virtual slots have separate backend names.
Typed bridge definitions stay in the declaring source's implementation unit and preserve
shared identity, defaults and explicit-parent behavior without copies or runtime casts.
The [authoritative contract](../../../../specs/inheritance_v0_2.md#shared-value-callable-variance)
and [model checkpoint](../planning/callable_variance.md) record scope and ownership.

Validation: 219 generated-C++ cases across variance, abstract dispatch, interfaces,
traits, method parameters and reference returns passed without PCH in
`/tmp/variance-final`, including 20 new variance fixtures. The same suites include
preparation rejections, cross-file failure/retry/repair and reset/fresh checks.
Consolidation re-exported all 219 fixtures in `/tmp/variance-consolidated`: 217 artifact
sets were byte-identical and the two changed sets were recompiled and passed. Thirteen
adjacent PHP suites passed in `/tmp/variance-adjacent-final`.
No native compiler rebuild was performed.

## Non-public destructors — 2026-10-08

Classes and structs accept protected/private destructor declarations without granting
runtime wrappers privileged access. Protected base cleanup works through public
authored or implicit child destruction; private base cleanup rejects. Shared base
views retain original concrete cleanup. Allocation requires a public actual-payload
destructor; unique storage and runtime-owned value/container payloads require public
cleanup. Bare inline structs use lexical access, including caller-side return cleanup.
Ordinary new/data construction, locals, fields and callable boundaries share this
admission; no custom deleter, runtime wrapper rewrite or AST extension was added.

The prerequisite is `type_lifecycle_work`, an independent declaration-only sibling
of relationship/dispatch work. Its visibility snapshot never requires layout or
callable signatures. Existing scheduling, lookup observations, version comparison,
failure/retry, reset and retirement own its lifecycle. Recursive `unique<Self>`
fields and value-return signatures do not introduce a signature/layout cycle.

Validation: 36 generated-C++ destructor cases without PCH in
`/tmp/nonpublic-destructors-final2`; focused PHP proofs include access rejections,
cross-file visibility failure/retry/repair, body-only/no-op version stability,
reset/fresh agreement and retirement unlinking. Thirteen adjacent PHP suites passed
in `/tmp/nonpublic-destructors-adjacent-final2`. No native compiler rebuild.

## Shared cross-casts — 2026-10-08

Checked casts and type tests now admit interface/interface and record/interface
cross-views with possible runtime overlap. No known common implementer is required.
Unrelated records and final records lacking the requested interface are rejected
during preparation; runtime narrowing/cross-casts require a polymorphic source.
Ancestry, finality and polymorphism use observed preparation dependencies. The existing
AST, prepared facts, C++ lowering and shared runtime helpers are unchanged: success
preserves identity/control-block ownership; mismatch/absence throws
`invalid_object_cast` for casts and yields false for tests. Shared struct results
stay shared, without payload copies. Unique/weak casts remain deferred.

Validation: 33 generated-C++ cases passed without PCH in
`/tmp/shared-cross-casts-final`, including class/shared-struct cross-views, mutation
identity, single evaluation, final implementers and mismatch/absence. PHP checks
cover impossible relationships and cross-file finality failure/retry/repair in both
directions, plus reset/fresh agreement. Seven adjacent PHP suites passed in
`/tmp/shared-cross-casts-adjacent`. No native compiler rebuild.

## Interface weak handles — 2026-10-08

Interfaces now participate in the existing weak ownership family: explicit `weak($view)`
derivation, `weak<I>` storage/copying, and `lock(weak<I>) -> I`. The nominal-handle
query selects the shared result for both classes and interfaces; no interface-specific
AST, prepared record, runtime carrier or C++ lowering path was added. Existing observed
ancestry supports weak implementing-record/interface and derived-interface/base-interface
upcasts at ordinary value boundaries. Empty/expired observations stay empty on locking,
without allocation or strong retention. References remain invariant; locking precedes
presence checks or calls. Unique interface ownership and weak downcasts/joins stay gated.

The interface-handle suite covers class/shared-struct derivation and upcasts, interface
ancestry, arguments/returns/assignment, data/container inputs, expiry, successful lock
retention, destruction without weak retention and cross-file failure/retry/repair/reset/
fresh agreement. Validation: 44 generated-C++ cases passed without PCH in
`/tmp/interface-weak-01`; seven focused PHP suites passed in
`/tmp/interface-weak-adjacent-final`. No native compiler rebuild.

## Shared selection joins — 2026-10-08

Full ternary, Elvis and coalescing use the existing selection-result owner to choose
one supplied shared type via dependency-aware ancestry. Class and shared-struct
derived/base pairs normalize to the supplied base; record/interface pairs normalize
to the supplied interface. Destination types never determine the join. No sibling
common ancestor inference, unique/weak joins, inline slicing or wrapper-payload
widening. Match admission remains unchanged.

Existing branch conversion facts carry shared upcasts; the backend reuses ordinary
handle construction. Coalescing has an explicit handle-presence fact and reuses its
evaluate-once temporary/lazy fallback flow. No new AST, allocation or runtime helper.
The [conditional contract](../../../../specs/conditional_expression_matrix.md#v02-shared-handle-joins)
records this intentional v0.2 extension to the old exact-type rule.

Focused proofs cover class/struct joins, invariant-reference checks of inferred types,
interface views, identity, empty results, lazy/evaluate-once behavior, sibling rejection
even with an explicit destination, and all three spellings across ancestry edits with
failure/retry/repair/reset/fresh agreement. Validation: 29 generated-C++ fixtures passed
without PCH in `/tmp/shared-joins-final2`; eight adjacent PHP suites passed in
`/tmp/shared-joins-adjacent`. No native compiler rebuild.

## Checked object access — 2026-10-08

`object_cast($value, Target::class)` and literal `instanceof` share one object-type
syntax/facts path. Preparation resolves imported target names and observes ancestry
and source polymorphism; emission reuses runtime shared-handle checked casts/tests.
Successful casts retain identity/control-block ownership; empty/mismatched casts
throw `invalid_object_cast`, while tests return false. No flow narrowing, implicit
downcasts, payload copies or new virtual members. Struct cast results are shared handles.

Bounds: same/upward views, class/struct base-to-derived and interface-to-implementing
record narrowing. Cross-casts, unique/weak inputs, inline inputs, dynamic target names
and bare-null inputs remain deferred. See the [language contract](../../../../specs/inheritance_v0_2.md#checked-shared-object-access-and-type-tests).

Validation: 17 generated-C++ fixtures passed without PCH in `/tmp/object-types-02`:
class/struct identity, success/mismatch/absence, interface inputs, evaluate-once,
namespace imports and cross-file failure/retry/repair/reset/fresh agreement. Eight
adjacent PHP suites passed across `/tmp/object-types-adjacent` and
`/tmp/object-types-parser`. The model suite's built-in-root-count assertion also fails
on an isolated `2b566321` baseline (`/tmp/object-types-baseline-check`); it is unchanged
and outside this slice. No native compiler rebuild.

## Unique ancestry transfers — 2026-10-08

Unique class/struct upcasts now reuse dependency-aware ancestry conversion with an
additional observed target-contract check: the base must already have virtual destruction.
The `unique_upcast` decision permits consuming owned temporaries at existing whole-local,
fixed-value argument and return boundaries; named values still require move. References
remain invariant. The C++ backend reuses target-handle construction. Runtime converting
moves reject unsafe deletion through nonvirtual bases without changing handle storage.

Tests cover class/struct transfer, arguments/returns, owned call results, empty handles,
aliases, native guard traits, pointer identity/replacement cleanup, generated derived/base
cleanup and removal of virtual destruction with failure/retry/repair/reset/fresh agreement.
Interface targets, fields/elements, joins and unique method receiver lifetimes remain
outside this slice. Validation: 54 generated-C++ fixtures passed in
`/tmp/unique-upcasts-final2`; seven adjacent PHP suites passed in
`/tmp/unique-upcasts-adjacent` (ownership, conversions, references, interface handles,
abstract dispatch, method parameters and method returns). No native compiler rebuild.

## Nested trait uses — 2026-10-08

Nested uses now expand through an analysis-owned, source-ordered traversal. Active-path
cycles reject; repeated origins reached through different paths contribute once per
consumer. Direct duplicate uses still reject. Materialization and lifecycle remain
consumer-owned, preserving each originating trait's namespace/imports. Intermediate
origins remain in the batch so their use-list edits participate in existing retirement.
No retained graph, copied inherited inventory or trait-specific backend was introduced.

Proof: 34 generated C++ programs without PCH in `/tmp/nested-traits-20261008-1`, plus
six focused PHP suites in `/tmp/nested-traits-regression-2`. Includes diamonds, static
storage, lexical provenance, nested-origin/use removal, cycle failure/retry/repair,
reset and fresh agreement. No native compiler rebuild.

The subsequent requirement/implementation separation completes the agreed group below.
Adaptations and lifecycle members remain deferred.

## Abstract trait requirements — 2026-10-08

The approved separation is implemented: requirements retain consumer-owned signature
work and lexical provenance, but do not compete with concrete method declarations.
Type contracts validate all signatures/access modes, retain inherited obligations and
select abstract slots only where unresolved. Ordinary lookup prefers implementations;
backend enumeration emits either the selected slot or the implementation, not both.
Interface/trait requirement edges share reference-return cycle analysis. No new AST
or runtime trait base is introduced. See the
[ownership plan](../planning/trait_member_instances.md#abstract-requirement-separation--implemented)
and [normative rules](../../../../specs/inheritance_v0_2.md#abstract-trait-requirements).

Evidence: `tests/traits.php` covers authored/inherited/contributed satisfaction,
protected/public requirements, compatible multiple requirements, abstract propagation,
reference returns/cycles and cross-file signature/body-shape failure/retry/repair/reset/
fresh agreement. Passed: 51 generated C++ programs without PCH in
`/tmp/trait-requirements-final`, all 22 model suites in `/tmp/trait-requirements-model-1`
and six adjacent PHP suites in `/tmp/trait-requirements-adjacent`.
The compiler itself has not been rebuilt natively.

## Constructor visibility — 2026-10-08

Public/protected/private constructor access now uses shared member-access checks at
ordinary new, data, late-static and explicit/implicit base-initialization boundaries.
Inherited constructors retain their original owner and visibility. Source signatures
retain visibility; no new AST or callable path is introduced. Non-public implicit
inline default construction requires an explicit new/data boundary; the old public
zero-parameter inherited default remains unchanged.

The approved backend prerequisite uses a public keyed constructor entry and an
access-controlled key accessor owned by the original record. Keys are non-aggregate
and not source-visible. Existing allocation helpers preserve make_shared allocation;
inline/unique/shared/data/base paths share the protocol. Key declarations are assembled
from current signatures, not cached field-layout fragments, so visibility edits are
reflected without stale layout output. Destructors and abstract/reference/variadic
constructor contracts remain outside this slice.

Passed: 60 generated constructor programs (`/tmp/constructor-access-final`), 59
inheritance programs (`/tmp/constructor-access-inheritance-1`), 40 derived-data programs
(`/tmp/constructor-access-data-1`), all without PCH; six adjacent PHP suites passed in
`/tmp/constructor-access-regression`. Coverage includes private factories, protected
explicit/implicit base calls, original-owner inherited access, data/unique allocation,
visibility rejection and signature-edit failure/retry/repair/fresh agreement.
No native compiler rebuild. Earlier checkpoints' public-only
constructor limits are historical and superseded by this section and the
[normative contract](../../../../specs/inheritance_v0_2.md#constructor-visibility).

## Derived data through constructor contracts — 2026-10-08


**Current scope — 2026-10-19 (CS495).** Class/struct inherited and authored data constructors observe initialized payload9, original parameter names, defaults plus child field defaults, multilevel selection, nested data inputs and explicit storage. Reversed keyed source inputs produce21, proving source evaluation order before parameter association. Extra fields do not become keys; missing/duplicate/wrong typed keys and missing explicit data opt-in reject. Cross-file inherited parameter name/type/constructor removal rejects on change/retry; authored-child replacement executes9 and reset/fresh output agrees. Historical public-only/non-public gate is superseded by protected inherited data factories, which execute9 under legal descendant access. No inherited set-property rebinding is claimed.

Contracts and owners: [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md), [data_initialization.md](../../../../specs/data_initialization.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS495`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

Implemented explicit `data` opt-in on a derived class/struct with an effective authored
public constructor, either local or inherited. Keys are that constructor's parameter
names; original types/defaults and lexical ownership remain unchanged. Extra child/base
properties never become implicit inputs. Base construction, child defaults and authored
body execution reuse ordinary constructor semantics without post-construction assignment.

The existing preparation model already represents the selected inherited constructor
and exposes its shared initialization contract. The change narrows the declaration gate
to derived data types requiring synthesized inputs, after effective constructor selection.
No new retained structure, AST variant, class/struct fork or C++ lowering path was needed.
See [the normative contract](../../../../specs/data_initialization.md#derived-records-with-authored-constructor-contracts--2026-10-08).

Proof: 21 generated programs passed without PCH in `/tmp/derived-data-20261008-1`;
six adjacent PHP suites passed in `/tmp/derived-data-regression-20261008-1`.
`tests/derived_data.php` covers names/defaults, both constructor forms, multilevel
inheritance, input order, nested targets, storage, rejection and cross-file retry/repair,
reset and fresh/incremental agreement. No native compiler rebuild.

The following checkpoint adds composed synthesized inputs. `data` is not inherited,
non-public/reference/variadic constructors retain their existing gates, and inherited
`set:` property rebinding is not introduced.

</details>

## Composed synthesized data inputs — 2026-10-08

Implemented the agreed explicit-data ancestry boundary. Each synthesized contract
references its base's settled contract plus local public inputs; no inherited AST or
member inventory is copied. The transient input view is base-first. C++ forwards the
base prefix into its tagged constructor directly, then initializes child members.
Authored/inherited constructors keep exclusive ownership of their parameter contracts.

Required base inputs are checked at construction, not rejected while declaring the
derived type. Ordinary default construction still checks the base. Base-contract
identity participates in declaration equivalence so ancestor schema/default edits
invalidate unchanged descendants and callers. Non-data bases, excluded member keys,
duplicate/missing inputs and inherited redeclarations reject during preparation.

Proof: `records.py --suite derived_data --no-pch` in
`/tmp/synthesized-data-20261008-2`; the expanded suite has 38 generated programs.
Six focused PHP suites passed in `/tmp/synthesized-data-regression-20261008-1`.
Includes multilevel composition, required enum inputs, private defaults, suppressed
base defaults, ordered effects, ordinary new, storage and cross-file schema/default
edits with failure/retry/repair, no-op/reset and fresh/incremental equality.
No native compiler rebuild. Specs and model/ownership guidance record the shared path.

## Late-static execution — 2026-10-08

Static source calls now demand original-method/called-type implementations. Lexical
names/access remain with the declaring record; self/parent forward called type,
explicit qualifiers establish it and static selects through it. Shared-class
`new static` uses ordinary checked construction and result conversions, including
factories declared on abstract bases. Static property storage remains single-owner.

The user approved eager signature/declaration checks and per-demand static body
checks; uncalled bodies remain syntax-checked without full semantic validation.
Backend-only static C++ helpers preserve declaring-type access and stay out of source
lookup. They have stable names, header declarations and out-of-line definitions.
Call-shape edits conservatively retire demand families so removed calls do not retain
obsolete implementations/errors; constant-only external dependencies use normal
invalidation. See the [current implementation plan](../planning/called_type_instances.md).

Proof: `/tmp/late-static-20261008-3` runs 12 generated programs without PCH, including
two contexts, recursion and cross-file failure/removal/repair. All 22 model suites
passed in `/tmp/late-static-model-20261008-2`, ten adjacent PHP suites in
`/tmp/late-static-regression-20261008-2`, and 34 generated reference-return programs
in `/tmp/late-static-returns-20261008-1`. The shared runner used CLI fallback because
sandbox FPM startup was unavailable. No native compiler rebuild.

Next: reconcile remaining CH11 groups. Runtime-called-type propagation through
ordinary instance methods and inline `new static` remain outside this slice.

## Called-type callable foundation — 2026-10-08

The approved [late-static extension](../../../../specs/inheritance_v0_2.md#late-static-calls-and-shared-factories)
requires independent callable instances, not repeated mutation of a method's single
prepared body. The [model/lifecycle checkpoint](../planning/called_type_instances.md)
is implemented: origin-owned instances keyed by called type, private working syntax,
unchanged lexical declaring owner, shared token snapshot/retirement support, no-op
identity and join/reset cleanup. `tests/callable_instances.php` is included in the
model runner. This initial checkpoint did not yet prove source late-static execution.

Proof: 21 PHP-host suites passed in `/tmp/callable-instances-model-20261008-2`;
the instance suite took 0.57 seconds and the longest suite (`s2s`) 12.72 seconds.
The shared runner used its expected CLI fallback because sandbox FPM startup failed.
Final focused imported-name, called-type removal and shared trait-lifecycle checks:
`/tmp/callable-instances-final-20261008-2` (three suites passed).

Ordinary scheduling is now integrated through a read-only callable collection view.
Existing instances participate in signature/body scheduling and reference-cycle checks,
including external dependency invalidation and failure/retry/repair. Their called type
is carried separately in the transient preparation context. Private implementations
do not become program files. All 21 PHP-host model suites passed in
`/tmp/callable-scheduling-20261008-1`; no native compiler rebuild was performed.

Coordinator-owned demand materialization is now integrated. Private-body static calls
publish requests and suspend without marking work failed; the coordinator interns
the missing implementation before resuming. Tests seed the initial private instance,
then prove transitive self/parent forwarding, explicit-type context reset, recursion
and no-op reuse through real body calls. At that checkpoint public calls still used their old path.
Proof: all 22 PHP-host suites passed in `/tmp/callable-demands-20261008-2`;
the demand test took 0.47 seconds, and the longest suite (`s2s`) 12.73 seconds.
No native compiler rebuild or generated late-static execution is claimed.

Compatible static redeclarations are now enabled for classes/structs through shared
type-contract validation, without virtual dispatch. Exact signature, reference/variadic,
visibility and final checks apply; `Override` remains virtual-only. Focused proof:
`tests/static_redeclarations.php`, run through `records.py --suite static_redeclarations`.
Ten generated programs passed without PCH in `/tmp/static-redeclarations-20261008-1`;
five adjacent PHP-host suites passed in `/tmp/static-redeclarations-regression-20261008-1`.
No native compiler rebuild.

The subsequent execution checkpoint above completes source activation, the approved
body-validation timing and backend identities. Runtime instance propagation is separate.

## Trait composition foundation — 2026-10-08

The static-member extension now adds static methods, typed static properties and
constants through the same consumer-owned instances. Each direct consumer has its
own static storage; inheritance preserves that storage owner. `self` selects the
consumer; late `static` follows the existing called-type contract. Lexical namespace
constants/imports retain trait source provenance. Constants participate in eager
preparation and cross-contribution/inherited collision checks, not a new backend path.

Proof: 28 generated programs without PCH in `/tmp/trait-static-20261008-3`; model
regressions in `/tmp/trait-static-model-20261008-2`. Includes class/struct storage,
late-static dispatch, private constants/defaults, reference returns, variadics and
trait-edit failure/retry/repair/reset/fresh agreement. No native compiler rebuild.

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
Direct source `use` now executes. The coordinator resolves through analysis, publishes
consumer batches, and retires stale instances before source-scope disposal. No-op
input preserves identity; edits conservatively rebuild affected consumers. Failure
retries, removed uses/origins, preparation/full reset and fresh-output agreement are
covered. The subsequent extension above adds static members/constants. Nested uses,
adaptations, lifecycle members and bodyless
trait requirements remain deferred. Next: reconcile remaining CH11 groups.

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

Automatic composition proof: 20 PHP-host suites passed in `/tmp/traits-model-20261008-2`.
Nine generated programs compiled and ran without PCH in `/tmp/traits-cpp-20261008-2`,
covering class/struct consumers, independent state, inherited/interface calls,
reference returns, `data` inputs and edited/fresh output. The compiler was not rebuilt
natively. The normative contract is [trait composition](../../../../specs/inheritance_v0_2.md#trait-composition).
Final focused retirement/fragment-eviction checks: `/tmp/traits-final-20261008-2`.

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

## Weak ancestry upcasts — 2026-10-08

Class/struct `weak<Derived>` to `weak<Base>` boundaries now reuse the dependency-aware
ownership conversion path. The common `ownership_upcast` fact selects the existing
runtime converting constructor; shared and weak conversions stay within their own
family. No lock, allocation, payload copy or lifetime extension is introduced.
References remain invariant, including read-only references. Interface weak handles,
unique conversions, downcasts and new joins remain outside this checkpoint.

`tests/shared_upcasts.php` now covers both families: empty/expired observers, identity,
explicit-lock lifetime, arguments/returns, properties/data inputs/container elements,
iteration and cross-file ancestry failure/retry/repair with fresh-output agreement.
Proof: 37 generated C++ programs passed without PCH in
`/tmp/weak-upcasts-20261008-2`; five focused PHP suites passed in
`/tmp/weak-upcasts-regression-20261008-2`. No runtime change or native compiler rebuild.

## Dependency-aware shared upcasts — 2026-10-08

First checkpoint of the agreed interface-handle group: conversion preparation accepts
a transient `conversion_relationship_query`. Its analysis-owned implementation observes
source ancestry through relationship work, including failed searches; no cached registry
answer, retained worker, backend lookup or second hierarchy is introduced. Conversion
facts retain only the selected `ownership_upcast` operation and canonical types.

Bare-class and explicitly shared-struct derived/base boundaries now execute through
one path for assignments, by-value function/method arguments and returns, properties,
data inputs and individual container elements. Reference storage stays invariant;
downcasts, boxing, unique conversions and whole-container conversions reject. Weak
class/struct upcasts are covered by the weak-ancestry checkpoint above.
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

Resolved on 2026-10-08 using the shared dispatch-binding model. A compatible public
inherited final method may satisfy a new interface for either classes or structs.
The interface binding targets the inherited final virtual slot without overriding
it. A descendant-owned final entry keeps source lookup unambiguous; all views call
the unchanged authored implementation. Nonfinal forwarding still connects ancestor
slots to descendant dispatch. Visibility, variance and final-override checks remain.

Validation: 58 generated-C++ interface/variance cases passed without PCH in
`/tmp/final-interface01`. New cases cover both record kinds, multiple interface views,
shared variance, descendant override rejection and cross-file finality changes with
visibility failure/retry/repair and fresh/incremental agreement. No native compiler rebuild.

Interface-qualified static dispatch is separately deferred to
[CH15](15_blocking_debt.md#ch15-interface-static-01--interface-qualified-static-dispatch).

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

### Original execution order (completed within the bounds above)

CH10 implements fixed declaring-type/self static methods, constants and properties.
The implemented sequence reused those same member categories:

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
- Deferred to CH15 on 2026-10-08: interface-typed qualifiers, including `produce()::method()` where `produce`
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


**Current scope — 2026-10-19 (CS513).** Review queue is largely historical: non-public destructor rejection is superseded by access-aware cleanup CS486, constructor visibility by CS494, shared variance by CS485, and shared ancestry joins by CS489. Remaining sibling/unique/weak joins and abstract-constructor extensions stay deferred. Implementation internal abstract/final convention does not constrain source language admission. This queue does not authorize new semantics or compiler fixes.

Contracts and owners: [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md), [static_members_v0_2.md](../../../../specs/static_members_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS513`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

CH10 now owns the [bounded destructor implementation](10_objects.md#destructors-and-automatic-cleanup--2026-10-07)
and [nonthrowing lifecycle contract](../../../../specs/destructors_v0_2.md).
The imported CLASS-ABS-001/002/003/004 examples overlap that completed base slice.
CH11 has settled child/member/base cleanup order, virtual destruction for virtual
contracts, and shared abstract/interface ownership. Non-public destructor access
remains rejected. No separate destructor AST/body path is needed.

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
  whether an explicit destination type should influence their common type: settled
  for shared handles by the [selection amendment](../../../../specs/conditional_expression_matrix.md#v02-shared-handle-joins).
  The result is one supplied base/interface, independent of destination. Sibling
  inference and unique/weak joins remain deferred.

Reconcile normative specs, runtime, lowering and focused proofs when each decision
is agreed. This records review work, not approval to expand the language contract.
The compiler implementation's abstract-base/final-concrete convention is separate
from the inheritance forms the language may allow.

</details>

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [CLASS-INH-001](#class-inh-001) | implemented-bounded | `class B extends A {}` | verified | verified | deferred | Concrete same-kind bases; [checkpoint](#concrete-inheritance-and-protected-access--2026-10-07) |
| [CLASS-INH-002](#class-inh-002) | implemented-bounded | `class A extends B implements I, J {}` | verified | verified | deferred | Shared contract validation; local/inherited implementations including final virtual methods |
| [CLASS-INH-003](#class-inh-003) | implemented-bounded | `class A { use T; }` | nested uses, abstract requirements, instance/static members and constants | verified | deferred | Independent consumer members/storage; edits/retry/reset; adaptations/lifecycle deferred |
| [CLASS-INH-004](#class-inh-004) | implemented-bounded | `interface I { function f(int $a): int; }` | verified | verified | deferred | Public bodyless instance contracts; multiple interface inheritance |
| [CLASS-INH-006](#class-inh-006) | implemented-bounded | `$a = new A(1);` | verified | verified | deferred | Shared class construction; checked constructor arguments; inherited constructors reuse original contract |
| [CLASS-INH-007](#class-inh-007) | superseded-contract | `$a value<A> = new A(1);` | verified | representation verified | deferred | C++-style source spelling is not PHS; explicit inline class storage is intentionally supported by CH10 |
| [CLASS-INH-008](#class-inh-008) | implemented-bounded | `$this->f();` | verified | verified | deferred | Ordinary fixed methods; abstract contracts dispatch virtually |
| [CLASS-INH-009](#class-inh-009) | implemented-bounded | `parent::f(1);` | verified | verified | deferred | Fixed original-owner dispatch; current instance required for instance methods |
| [CLASS-INH-010](#class-inh-010) | implemented-bounded | `parent::__construct($x);` | verified | verified | deferred | First standalone statement only; sequenced base initialization; fixed-value constructors with original-owner access checks |
| CLASS-INH-013 | implemented-bounded | `class Child extends Base {}` | verified | verified | deferred | Inherits original concrete constructor visibility, contract/defaults through abstract or concrete ancestors |
| CLASS-INH-014 | implemented-bounded | `static::method(); new static();` | verified | verified | deferred | Demanded static called-type instances; shared-class factories; runtime instance propagation deferred |
| CLASS-INH-015 | implemented-bounded | `data class C extends B {} $c C = {input: 9};` | verified | verified | deferred | Explicit opt-in; authored/inherited parameter contracts or composed synthesized public-property inputs through data bases |
| [CLASS-INH-011](#class-inh-011) | implemented-bounded | `#[\Override] function f(): int { return 1; }` | verified | verified | deferred | Checked inherited virtual target; shared-value variance; nonvirtual hiding rejected |
| CH11-CALLABLE-VARIANCE | implemented-bounded | `f(Animal $x): Dog` implementing `f(Dog $x): Animal` | verified | verified | deferred | [Directional shared adaptation and dispatch bridges](#shared-callable-variance--2026-10-08); invariant references/other ownership |
| [CLASS-INH-012](#class-inh-012) | implemented-equivalent | `class B extends A {}` | verified | verified | deferred | Prepared base identity and qualified ancestor calls replace reliance on a literal C++ base alias |
| [CLASS-ABS-001](#class-abs-001) | implemented-bounded | `function __destruct() { $this->cleanup(); }` | verified | verified | deferred | Shared method lifecycle; out-of-line nonthrowing body; ordinary instance calls |
| [CLASS-ABS-002](#class-abs-002) | implemented-bounded | `function __destruct() {}` | verified | verified | deferred | Type-header declaration and implementation-unit definition; [non-public cleanup access](#non-public-destructors--2026-10-08) |
| [CLASS-ABS-003](#class-abs-003) | implemented-bounded | `function __destruct() { $n = $this->x; }` | verified | verified | deferred | Instance property access allowed; returning a value rejects under the void-body contract |
| [CLASS-ABS-004](#class-abs-004) | implemented-rejection | `static function __destruct() {}` | verified | verified | deferred | Rejected eagerly during signature preparation, even if unused |
| [CLASS-ABS-005](#class-abs-005) | implemented-bounded | `abstract class A {}` | verified | verified | deferred | Cannot instantiate even without abstract members; class and struct |
| [CLASS-ABS-006](#class-abs-006) | implemented-bounded | `abstract function f(int $x): int;` | verified | verified | deferred | Bodyless public/protected instance contract; concrete descendants must implement |
| [CLASS-ABS-007](#class-abs-007) | implemented-bounded | `abstract function f(): int;` | verified | verified | deferred | Pure virtual declaration; no executable body work |
| [CLASS-ABS-008](#class-abs-008) | implemented-bounded | `abstract class A { int $x; function f(): int { return $this->x; } }` | verified | verified | deferred | Concrete members/constructors remain available to descendants |
| [CLASS-ABS-009](#class-abs-009) | implemented-bounded | `interface I { function f(): int; }` | verified | verified | deferred | Pure virtual declarations and virtual cleanup; no record layout |
| [CLASS-ABS-010](#class-abs-010) | implemented-bounded | `class A extends B implements I {}` | verified | verified | deferred | Source identities retained; public inherited forwarding; shared virtual interface bases |
| [TYPE-PARAM-003G](#type-param-003g) | implemented-bounded | `interface I { public function f(const string &$s): int; } class C implements I { public function f(string $s): int { return 1; } }` | verified | verified | deferred | Rejected during shared preparation; exact readonly/reference signature contract |
| CH11-SHARED-UPCAST | implemented-bounded | `$base Base = $derived;` | verified | verified | deferred | [Shared ancestry](#dependency-aware-shared-upcasts--2026-10-08); invariant references; no slicing |
| CH11-INTERFACE-HANDLE | implemented-bounded | `$view I = $object; $view->method();` | verified | verified | deferred | [Shared interface handles](#interface-handles-and-calls--2026-10-08); checked calls; no implicit struct boxing |
| CH11-WEAK-UPCAST | implemented-bounded | `$base weak<Base> = $derived;` | verified | verified | deferred | [Weak ancestry](#weak-ancestry-upcasts--2026-10-08), including [interfaces](#interface-weak-handles--2026-10-08); no lock or lifetime extension; invariant references |
| CH11-UNIQUE-UPCAST | implemented-bounded | `$base unique<Base> = move($derived);` | verified | verified | deferred | [Unique ancestry](#unique-ancestry-transfers--2026-10-08); virtual base destruction required; consuming boundaries only |
| CH11-OBJECT-TYPE | implemented-bounded | `object_cast($base, Derived::class)` / `$base instanceof Derived` | verified | verified | deferred | [Checked access](#checked-object-access--2026-10-08) and [shared cross-casts](#shared-cross-casts--2026-10-08); shared identity, checked failure, observed finality, no flow narrowing |
| CH11-SHARED-JOIN | implemented-bounded | `$condition ? $derived : $base` / `$derived ?: $base` / `$base ?? $derived` | verified | verified | deferred | [Shared joins](#shared-selection-joins--2026-10-08); one supplied base/interface, no sibling inference |

## CLASS-INH-001

**v0.2 decision / target C++:** Concrete same-kind bases; [checkpoint](#concrete-inheritance-and-protected-access--2026-10-07). See the progress table and checkpoints above; imported lowering below is historical evidence, not the v0.2 contract.

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


**Current scope — 2026-10-19 (CS516).** Completed B supplies f1 and g2; A inherits both and implements I,J. Both source interface views are actually invoked and sum3, stronger than declaring multiple interfaces alone. Historical declaration-only/trait-rejection text and final-virtual restriction label are superseded by current contracts and CS504.

Contracts and owners: [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS516`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Shared contract validation; local/inherited implementations; final-virtual forwarding restriction. See the progress table and checkpoints above; imported lowering below is historical evidence, not the v0.2 contract.

</details>

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

**v0.2 decision / target C++:** Supported direct instance/static members and constants for classes and
structs, with consumer-owned member instances and ordinary C++ member emission.
Same-category conflicts reject. See the normative trait contract and handoff above.
The imported v0.1 rejection below is historical, not current v0.2 behavior.

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

**v0.2 decision / target C++:** Public bodyless instance contracts; multiple interface inheritance. See the progress table and checkpoints above; imported lowering below is historical evidence, not the v0.2 contract.

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

**v0.2 decision / target C++:** Shared class construction; checked constructor arguments; inherited constructors reuse original contract. See the progress table and reconciliation above; imported lowering below is historical evidence, not the v0.2 contract.

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

**v0.2 decision / target C++:** C++-style source spelling is not PHS; explicit inline class storage is intentionally supported by CH10. See the progress table and reconciliation above; imported lowering below is historical evidence, not the v0.2 contract.

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

**v0.2 decision / target C++:** Ordinary fixed methods; abstract contracts dispatch virtually. See the progress table and checkpoints above; imported lowering below is historical evidence, not the v0.2 contract.

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


**Current scope — 2026-10-19 (CS524).** Explicit Override implements abstract f and executes through A handle returning supplied1. Current preparation validates the inherited virtual/interface target; historical trust-as-is/no-semantic-validation guidance is superseded. Broader variance and invalid override signatures retain CS485/505 proofs.

Contracts and owners: [inheritance_v0_2.md](../../../../specs/inheritance_v0_2.md). [Scoped evidence and limits](../planning/language_verification_section_reconciliation.json) (`CS524`).

<details>
<summary>Earlier checkpoint / imported guidance — historical</summary>

**v0.2 decision / target C++:** Checked inherited virtual target; shared-value variance with invariant references/other ownership; nonvirtual hiding rejected. See the progress table and checkpoints above; imported lowering below is historical evidence, not the v0.2 contract.

</details>

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

**v0.2 decision / target C++:** Prepared base identity and qualified ancestor calls replace reliance on a literal C++ base alias. See the progress table and reconciliation above; imported lowering below is historical evidence, not the v0.2 contract.

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

**v0.2 decision / target C++:** Shared method lifecycle; out-of-line nonthrowing body; ordinary instance calls. See the progress table and reconciliation above; imported lowering below is historical evidence, not the v0.2 contract.

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

**v0.2 decision / target C++:** Type-header declaration and implementation-unit definition. See the progress table and reconciliation above; imported lowering below is historical evidence, not the v0.2 contract.

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

**v0.2 decision / target C++:** Instance property access allowed; returning a value rejects under the void-body contract. See the progress table and reconciliation above; imported lowering below is historical evidence, not the v0.2 contract.

Imported versions are preserved for provenance. The v0.2 decision above separates valid instance access from invalid value-returning destruction.

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

**v0.2 decision / target C++:** Rejected eagerly during signature preparation, even if unused. See the progress table and reconciliation above; imported lowering below is historical evidence, not the v0.2 contract.

Both imported variants describe the same rejection, now enforced in shared preparation.

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

**v0.2 decision / target C++:** Cannot instantiate even without abstract members; class and struct. See the progress table and checkpoints above; imported lowering below is historical evidence, not the v0.2 contract.

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

**v0.2 decision / target C++:** Bodyless public/protected instance contract; concrete descendants must implement. See the progress table and checkpoints above; imported lowering below is historical evidence, not the v0.2 contract.

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

**v0.2 decision / target C++:** Pure virtual declaration; no executable body work. See the progress table and checkpoints above; imported lowering below is historical evidence, not the v0.2 contract.

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

**v0.2 decision / target C++:** Concrete members/constructors remain available to descendants. See the progress table and checkpoints above; imported lowering below is historical evidence, not the v0.2 contract.

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

**v0.2 decision / target C++:** Pure virtual declarations and virtual cleanup; no record layout. See the progress table and checkpoints above; imported lowering below is historical evidence, not the v0.2 contract.

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

**v0.2 decision / target C++:** Source identities retained; public inherited forwarding; shared virtual interface bases. See the progress table and checkpoints above; imported lowering below is historical evidence, not the v0.2 contract.

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

**v0.2 decision / target C++:** Rejected during shared preparation; exact readonly/reference signature contract. See the progress table and checkpoints above; imported lowering below is historical evidence, not the v0.2 contract.

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
