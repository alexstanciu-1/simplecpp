# Compiler portability decisions and backlog
Doc Status: planning

Started 2026-10-23, following the [inventory](compiler_portability_inventory_2026_10_22.md).
This is a discussion/implementation handoff, not a normative specification amendment.

## Workflow agreed with the user

Discuss the contracts, record decisions, and build the backlog before implementation.
No implementation starts now. Later work proceeds one agreed item at a time when
the user resumes implementation. Acceptance of a design does not override this pause.
Record new decisions here; distinguish accepted decisions from recommendations.
Do not turn every next bootstrap diagnostic into authorization for another feature.

**Pre-implementation gate, explicitly requested 2026-10-23:** before starting this
backlog's implementation, audit all accepted decisions against the v0.1 specifications,
documentation and established direction. Identify actual contradictions or semantic/
policy changes, with source references, compatibility impact and explicit disposition.
Exclude mere bugs and missing v0.2 implementations from the list of design conflicts;
track those separately. Do not label implementing an existing v0.1 contract a design
change. Do not assume historical generated behavior alone establishes specification
intent. Discuss unresolved conflicts before implementation. The
[2026-10-23 audit](compiler_portability_v01_decision_audit_2026_10_23.md) has now
been performed as a read-only documentary review; its open dispositions still gate
affected work. The implementation pause remains in force.

## Backlog-wide boundary — portable source and converter first

**Accepted on 2026-10-23; applies to every item below, including earlier decisions.**

Resolve portability requirements by adapting executable PHP or by converting it to
existing Simple C++ constructs. Preserve observable behavior across PHP and both
compiler routes. Portability markers and helpers do not automatically become new
Simple C++ syntax, types, or semantic protocols.

Explicit clarification, 2026-10-23: do not implement or change Simple C++ merely to
accommodate the PHP-to-PHS converter. A proposed language change needs an independent
language use case and coherent semantics; converter convenience is not sufficient.
Previously accepted core features must retain that justification. Resolve purely
portable representation issues through authored PHP and conversion.

Portability philosophy, agreed 2026-10-23: preserve required compiler behavior and
contracts, not identical implementation, allocation, destruction or cleanup timing.
Do not emulate native weak lifetime machinery in PHP solely for implementation parity.
PHP-only cleanup may be used where justified without invalidating live data. Known
observable differences must stay documented; this is not permission to hide a
demonstrated correctness defect or silently change native ownership semantics.

For each item, identify the existing target contract and a truthful source/converter
lowering first. Keep the converter within its structural role; this boundary does
not authorize adding general semantic analysis to it or patching generated C++.
If investigation establishes a compiler bug or missing Simple C++ capability,
classify and discuss that gap explicitly; fixes at the owning compiler/spec/runtime
layer remain possible. An unsupported PHP behavior alone is not authorization to
extend the language. If no equivalent lowering exists, report the limitation and
source adaptation options before proposing a separately agreed extension.

Earlier semantic directions describe the desired portable behavior, not blanket
approval of their suggested native mechanisms. Reassess those mechanisms under
this boundary before implementation. The subsequent explicit decision classifies
SELF-01/SELF-02 as core v0.2 capabilities: ordinary current-instance assignment,
passing and returning must be supported under the class ownership / struct value
contracts. Source workarounds are not a prerequisite for this agreed core support.
COPY-01 remains converter work using ordinary native value-copy operations; its
marker and method name do not become language features. All implementation remains
paused.

## SELF-01 — shared class self values

**Core v0.2 support accepted; implementation paused.**

Keep ordinary portable PHP `$this` spelling. For an instance with an existing live
shared owner, using `$this` as an ordinary value acquires another shared handle to
that same instance. It preserves identity and uses the existing control block;
there is no cloning, independent raw-pointer owner, or automatic ownership promotion.
Arguments, stored handles and returned handles follow ordinary shared-value rules.

Compiler requirements include transient worker/collector calls and retained child
iterators. `switch_children_iterator` stores its source node, so a borrowed pointer
alone cannot implement the required lifetime. The same distinction applies to workers
passing themselves into node operations.

The existing v0.1 generator emits `this->retain_self(this)` for ordinary class values
and adds a virtual `scpp::shared_self` base. The runtime acquires the existing weak
control-block observer and creates an aliasing shared handle to the adjusted address.
Use that mechanism as the implementation starting point, not a second ownership system.

Accepted boundaries:

- No automatic conversion of inline or unique ownership into shared ownership.
- No owning self escape during construction/destruction. Diagnose statically known
  invalid uses during preparation; retain a runtime guard for indirect acquisition
  without a live shared owner. Exact admission/propagation mechanics remain design work.
- Do not change existing borrowed closure receiver capture behavior.
- No automatic cycle prevention; storing shared self can form an ordinary strong cycle.
- Storage_Cursor widening and permission to transfer shared objects across tasks are
  separate items, not consequences of acquiring shared self.

Implementation scope, when resumed: amend the owning method/ownership contract;
represent self-value acquisition in shared preparation separately from receiver access;
integrate record/inheritance runtime support; emit both ordinary and concrete/prelowered
transport. Do not merely remove the current `$this` rejection or repair it in emission.
Assess layout/inheritance effects before assuming a narrow patch is sufficient.

Required proofs: object identity and mutation sharing, returned/stored self surviving
release of the original owner, inherited/base/interface address adjustment and shared
ownership identity, invalid acquisition, unchanged ordinary member access/borrowed
captures, and ordinary/prelowered parity. Use portable PHP/v0.1 witnesses where applicable.

Evidence inspected, not newly executed during this discussion:

- `generators/php/src/Generator/Generator.php`: class base emission and `$this` expression lowering.
- `runtime/include/scpp/shared_self.hpp` and `tests/runtime/native/test_shared_self.cpp`.
- `tests/portability/fixtures/specialized_ast/main.php` and its proof runner.
- `compiler/my-try/03_parse/structures/structures_switch.php` and `iterators.php`.
- `compiler/my-try/04_analyze/prepare/semantics/expressions.php` and `methods.php`.

## SELF-02 — struct and non-default storage self

**Core v0.2 receiver/value support accepted on 2026-10-23; implementation paused.**

Separate the current receiver from a value passed across a boundary. A struct method
operates on the existing struct; `$this->field = ...` must mutate that receiver, not
an implicit copy. Under an ordinary by-value `S` parameter/return contract, producing
an `S` value requires a copy of the receiver, and only if S is copyable. That copy
is memberwise under existing field semantics, not a deep clone of shared fields.
By-reference access borrows the original and requires its own lifetime admission.
The agreed principle is: current-instance access first; copying, borrowing and
ownership retention follow the operation's contract. This records intended semantics,
not current implementation availability or admission of new reference escapes.

Declaration kind alone is insufficient. `S` is normally inline, but `shared<S>` and
`unique<S>` exist; classes also admit `value<C>`. A shared struct self handle and a
struct value copy are different results. Do not choose silently based on whether the
same method happens to be invoked through a shared handle, or promote unique ownership.

Portable PHP complicates implicit copies: `@scpp-struct` is still a PHP class, so a
PHP object argument/return aliases the object. Its current bounded converter grammar
also excludes methods. Thus adding native struct self-value support alone cannot
establish portable struct-method parity. The current portable value-record discipline
uses explicit owner-defined copy operations for independent snapshots.

The native value-copy direction and the bounded portable-PHP protocol in COPY-01
are agreed. Shared struct self retention is deferred review debt SELF-DEBT-01
(user agreement, 2026-10-23), not a blocker for ordinary receiver/value-copy support.
Before admitting it, establish a concrete consumer and an explicit operation that
retains an existing shared allocation without promoting inline or unique storage.
Do not infer retention from allocation location or silently replace a value copy.
No implementation is authorized by this debt.

Authorities: [storage selection](../type_use_modifiers.md#v02-bounded-owned-construction--2026-10-06),
[struct copy semantics](../compact_layout_types.md),
[current receivers](../instance_methods_v0_2.md#receiver-evaluation-access-and-lifetime),
and [portable value records](../portability/value_records.md).

## COPY-01 — `_struct_` / `_struct_copy_` portable protocol

**Protocol direction accepted on 2026-10-23; implementation paused.**

Use the consistent spellings `implements \_struct_` and `_struct_copy_()`.
The marker identifies an explicitly opted-in portable value record. Ordinary PHP
classes remain Simple C++ classes. This replaces the proposed blanket exclusion
of portable structs; it does not admit arbitrary PHP classes as native structs.
The discussed source form retains the existing `/** @scpp-struct */` annotation
alongside the marker. Marker/annotation consistency checks belong to the converter
slice; no automatic migration of existing records is performed now.

In executable PHP, the author supplies a record-specific `_struct_copy_()` method
that constructs an independent record of the same type. In native output, the
operation denotes an independent value under existing struct-copy semantics.
The converter may lower the copy method to a typed by-value `return $this;` once
the separately agreed core struct-self support is available, or use another ordinary
admitted value-copy operation. Native struct self-value returns belong to SELF-02,
not to recognition of this portability protocol. Preserve the copy boundary instead
of unconditionally deleting calls. The lowering is not implemented yet.
It is a portability protocol, not new Simple C++ copy semantics or a generic
reflection-based clone operation. The PHP marker must not silently become a native
interface/base that changes record layout. The recognized copy method does not
by itself authorize arbitrary methods in the portable struct grammar.

Accepted obligations:

- Preserve the source value; the copy must contain equivalent field values.
- No extra observable behavior such as logging, counters, unrelated mutation,
  validation or value transformations. Allocation/copy failure remains subject
  to ordinary route behavior; no identical allocation-count or OOM-timing claim.
- Nested value records require independent value copies; shared class fields keep
  their object identity. Container contents follow their intended field-copy rules.
  The first proof is scalar-only and does not claim general nested-container support.
- Evaluate the receiver once. Keep expression evaluation order and observable effects.
- At an ordinary by-value boundary, native lowering may reuse the copy already
  required by that boundary. It must not blindly erase the operation in all contexts.
- A reference-taking consumer must not gain access to the original through an
  erased copy. Materialize independent value storage under an admitted lifetime
  contract, or reject the use in the initial bounded subset. Existing restrictions
  on binding native references to temporaries remain in force.
- Copying still requires the native record to be copyable; no implicit moves,
  shared-self acquisition or ownership promotion are introduced.

Custom PHP implementations permit direct field copying without reflection. Their
semantic equivalence must be reviewed and demonstrated by focused tests; the
structural converter does not prove arbitrary method purity/equivalence. Do not
introduce general type inference to silently discover all required copy sites.
Explicit copies are required wherever mutable aliasing in PHP would otherwise
violate the intended independent-value behavior. Existing owner-defined copy
functions remain valid; this protocol does not invalidate them.

### First proof: cpp_specialization_ref

Keep the existing named record in
`compiler/my-try/05_backend/cpp/structures.php` as the first bounded candidate.
It has one `uint32`-annotated `entry_id` field, initialized by
`CPP_Specializations::reference()` and subsequently read by `entry()`.
No general portable struct-method or nested-copy expansion is part of this proof.

When implementation resumes:

1. Supply the executable PHP marker and explicit typed copy method, using ordinary
   fresh construction and field assignment; no new constructor form is needed.
2. Integrate recognized conversion and target value-copy transport for both routes,
   including v0.2 prelowering, without patching generated C++.
3. Prove field preservation and independent mutation of source/copy in PHP and
   generated native programs from v0.1 and both v0.2 output modes.
4. Prove single receiver evaluation, correct by-value use and rejection or safe
   handling of unsupported reference/escape uses; verify marker/method diagnostics.
5. Check specialization request/lookup behavior on the real compiler record.
   Separate this focused proof from full compiler self-bootstrap.

The exact internal copy representation, marker declaration signature, admitted
expression contexts, and negative diagnostics are bounded implementation design
work. If they require a wider semantic/ownership redesign, report that before
expanding the slice. No code has been changed to implement this protocol.

## INIT-01 — constructor inputs and explicit defaults

**Direction accepted on 2026-10-23; implementation paused.**

1. Use PHP constructor property promotion for direct required inputs, for example
   `__construct(public switch_label_kind $label_kind)`. The converter can express
   the corresponding separate PHS property and `__construct(set:$label_kind)`.
   The explicit declaration association avoids inference from arbitrary body
   assignments. Preserve the agreed initialization/evaluation order; inheritance,
   parent-constructor interactions and combinations outside the bounded form need
   explicit validation, not silent reordering.
2. For computed body assignments, give the property an explicit valid default where
   available, such as `public int $value = 0;`, then execute `$this->value = $arg + 1;`
   in the constructor body. Preserve the distinction between initialization and
   subsequent assignment, including observations/failures between them.
3. Enum defaults can explicitly select an existing case (including the first case).
   Do not invent an `_unset_` case solely to satisfy initialization. This is not an
   implicit language-wide first-enum-case default: the selected case is written in
   the source and must be acceptable as the initial value.
4. Optional object fields use `public ?Node $node = null;`. Required object fields
   assigned in a later stage use INIT-02. No fabricated sentinel object is required.

The discussed `public Node $node = Node::$_uninitialized_;` is not a valid PHP
property initializer: it reads a static property rather than a constant value.
A separately assigned sentinel object would also introduce shared identity and
mutation behavior. The accepted annotation below avoids that approach.

This does not authorize rewriting every ordinary constructor assignment into `set:`,
inventing defaults for required data, or widening all constructor syntax. Proofs
should cover direct promoted enum inputs, computed assignment after an explicit
default, explicit enum defaults, and preservation of order/failure behavior in both
compiler routes. Existing PHP promotion expansion must be reviewed as part of the
converter work; accepting PHP syntax alone does not establish native initialization.

## INIT-02 — `_uninitialized_` required fields with staged assignment

**Portable behavior and nullable-storage/accessor direction accepted on 2026-10-23;
implementation paused. No new Simple C++ initialization feature is approved.**

Portable spelling:

```php
public Node $node /** _uninitialized_ */;
```

This is a required field awaiting assignment, not a nullable field or a special Node
value. PHP already leaves this typed property uninitialized and rejects ordinary
reads before assignment. The converter must preserve that contract natively.

Accepted semantics:

- Assignment may happen after construction, in an explicit later initialization stage.
  Constructor completion does not alone require this annotated field to be populated.
- Every ordinary value read before successful initialization must fail; it must not
  silently expose null, an empty handle, a default object or a sentinel.
- After assignment, reads/writes follow the declared type's ordinary semantics.
  The marker is not readonly or write-once and does not make the field nullable.
- The authoring obligation remains to populate required fields before a record is
  used as complete. This does not introduce a universal publication phase or whole-
  program definite-initialization analysis into the converter.
- `public ?Node $node = null;` remains the distinct contract for legitimate absence.

The hidden uninitialized state is a portable-PHP contract to represent using
existing Simple C++ constructs, not a new native value or initializer. The proposed
`Node node = uninitialized` / `Point point = uninitialized` language syntax is
withdrawn. No new native checked-storage or initialization-state protocol is approved.

Use private nullable backing storage and checked accessors, expressed with existing
Simple C++ constructs. For a class field Node, ?Node stores an empty handle until
assignment; a read accessor explicitly checks absence and then returns the same
object identity. A required-looking native handle return type alone does not enforce
the check. For a copyable struct Point, ?Point stores an absent optional payload;
existing nullable construction/destruction manages the payload, without a fabricated
default Point or raw zeroed storage. Required extraction returns a value copy.
These are accepted lowering directions, not completed converter/native proofs.

Bounded converter/source obligations:

- Generate backing storage/accessors from explicit annotated declarations; rewrite
  accesses only where the declaration is structurally known. Do not infer arbitrary
  receiver types or resolve whole-program members. Use explicit PHP accessor methods
  where transparent rewriting cannot be justified within the structural converter.
- Initially admit whole-field assignment and checked value reads. Preserve receiver
  and right-hand-side evaluation count/order, declaration visibility, and failure
  behavior. Generated names must avoid collisions. Absent reads must fail on both
  routes; identical exception classes/messages are not yet promised. Callers that
  depend on failure categories require separate review.
- Keep internal absence inaccessible as an ordinary required field value. Required
  writes must not silently treat an invalid null input as a reset; validate admitted
  write boundaries on both routes. Public field reflection/dynamic access is not
  automatically supported by a private-storage/accessor rewrite.
- Struct values require explicit independent PHP copies under COPY-01 wherever
  native by-value boundaries would otherwise disagree with PHP object aliasing.
  For an update, author an explicit copy/update/whole-value replacement:

  ```php
  $point = $holder->point->_struct_copy_();
  $point->x = 10;
  $holder->point = $point;
  ```

  This defines replacement behavior; it does not preserve arbitrary aliases to the
  previous PHP object. Never blindly translate `$holder->point->x = 10` into mutation
  through a by-value getter, which would modify a temporary copy.
- References, nested/compound mutation, presence probes, and unset need explicit
  handling or rejection in the bounded subset. `isset` is not an ordinary value read.
  No general interior borrow, reset protocol, or arbitrary portable struct methods
  are admitted by this decision.

Both routes, including ordinary/prelowered v0.2 output, need proofs. If preserving
this behavior requires unavailable semantics, record the gap and discuss it before
extending Simple C++. Merely stripping the annotation is not sufficient.

Initial proof scope: a required Node field, construction followed by delayed
assignment, rejection of a pre-assignment read (including reading the field as an
ordinary argument/return value), successful post-assignment reads preserving object
identity, reassignment, and a nullable-field control. Add a copyable struct proof for
absent storage, independent copies and explicit replacement, plus rejection of unsafe
mutation/reference forms. Ensure a failed assignment does not falsely mark the field
initialized. Validate both v0.1 and v0.2 ordinary/prelowered routes before rollout.

Still to specify in the bounded design: lowering into existing native constructs, state
preservation on enclosing-record copies/moves, applicable field/storage families,
and behavior of presence probes/references/unset where admitted. PHP `isset` is not
an ordinary value read; do not accidentally change it by inserting blanket read
guards. No reset/unset protocol, concurrent initialization, all-type extension, or
extra reference escape is authorized by accepting staged checked reads.

## TASK-01 — bounded shared tasks with programmer-owned synchronization

**Direction explicitly accepted on 2026-10-23; implementation paused.**

Simple C++ should admit the compiler's bounded shared-task pattern with programmer-
owned synchronization, without claiming whole-program thread-safety analysis.
This is an agreed v0.2 admission gap, not a converter workaround or missing API
registration. It does not classify all classes/resources as transferable everywhere.

The concrete first use is Compiler::frontend(): a Source_Work_Queue contains one
source_work per source record and seals membership before dispatch. Workers mutate
their assigned per-source work; shared namespace/type/symbol registration uses
task_synchronize. Final source publication is serialized, and the coordinator
inspects completion and performs subsequent processing after the task batch joins.
Incremental parsing reuses mutable retained declarations, not wholly independent
snapshots. Preserve those identities rather than introducing an implicit deep copy.

Accepted responsibilities and bounds:

- Shared handles preserve identity and keep objects alive for admitted work/capture
  lifetimes. Reference counting alone does not make object fields thread-safe.
- The programmer ensures exclusive per-source mutation, synchronization of conflicting
  shared reads/writes, and phase separation from downstream consumers until joining.
- Reuse the existing unordered task executor, batch publication mutex, and synchronous
  task_synchronize callback. Different batches do not share that mutex; the caller
  must prevent overlapping unprotected access to a common destination.
- Preserve join-before-return/rethrow and partial-publication behavior. There is no
  rollback promise. File parse failures may remain work-result data so independent
  files finish before the coordinator reports errors.
- Distinguish transferable task environments from synchronous callbacks executed under
  the active batch lock. No automatic permission for arbitrary borrowed captures,
  resources, detached/background task graphs, or unique-to-shared promotion follows.
- Do not disguise shared graph access as scalar IDs plus globals merely to evade
  admission. Ordinary type/lifetime checks remain; this is not static race-freedom proof.

Existing v0.1 typed runtime templates supply the executor/locking mechanism; the PHP
facade is sequential. v0.2 metadata exists, but Transfer_Preparation rejects shared
source objects as payloads and capture environments. The bounded admission policy,
callback roles and any needed transport facts must be designed in their owning
contracts/preparation paths, including ordinary and prelowered output. Exact
admission mechanics remain open; removing the general transfer guard is not the plan.

The read-only shared-access audit found the separate TASK-02 source defect below.
The inspected source establishes intended discipline, not complete race freedom.
Required proofs include same-object payload/result/capture
identity, retention through join, per-item exclusive dispatch, serialized registration
and publication, worker/publisher failures and joining, incremental parse identity,
and continued rejection outside the agreed bounded domain. Native concurrency proofs
are required; sequential PHP success is insufficient. No new scheduler or general
ownership/thread-safety analysis is authorized by this decision.

Evidence inspected: compiler/my-try/compiler/{structures,work_queue,frontend,
publication,compile}.php; 03_parse/parser.php and collect/collect.php;
04_analyze/prepare/semantics/{transfer,captures,runtime_calls}.php;
runtime/include/modules/tasks/tasks.hpp; runtime/metadata/modules/tasks.json;
tools/php_portability/runtime/tasks.php; specs/builtins/tasks/unordered_publication.md;
compiler/my-try/docs/planning/task_acceptance_inventory.md. No new execution proof.

### Accepted bounded admission rules

- Initially extend only the joined task_run_publish_unordered API and its associated
  task_synchronize callback contract. Admit shared object handles in inputs, results
  and callback captures; do not automatically widen other task APIs/background work.
- Represent shared-handle admission separately from isolated owned-value transfer.
  Handle copies preserve identity/lifetime; they do not isolate the reachable graph.
- Capture shared handles by value in this initial slice. Arbitrary borrowed captures,
  unrestricted resources and implicit unique-to-shared promotion remain excluded.
- Model callback execution roles: concurrent work; serialized publication that may
  execute on a worker thread; synchronous, nonescaping task_synchronize callbacks
  under the active batch lock. Preserve existing context/nesting restrictions.
- Require programmer synchronization for conflicting reads as well as writes,
  exclusive mutation of per-item state, frozen shared configuration during execution,
  and coordination between batches touching common destinations.
- Preserve retention through joining, join-before-return/rethrow and visible partial
  publication. Do not claim rollback or automatic race-freedom verification.

These are API-level rules, not a source_work/Source_Work_Queue whitelist and not a
recursive immutability proof for every reachable object. Shared preparation and
ordinary/prelowered callable transport must enforce the admitted lifetime/category
boundaries. Exact internal representation remains implementation design work.

## TASK-02 — synchronize retained-type association lookup

**Separate portable PHP concurrency fix accepted for the backlog; implementation paused.**

The source-inspected reader path is Parser -> previous_member_owner/previous_enum ->
scope::source_types_named -> Source_Type_Declarations::declaration. It starts from
a file-local scope but reads the shared declarations hash without the publication
lock. Concurrent new-type registration inserts into that same hash inside
task_synchronize. The native hash has mutable storage without internal locking;
locking only writers does not protect these reads.

A concrete overlap is incremental parsing that reuses an existing class/enum in one
file while another file registers a new type. This is a source-inspected race path,
not a reproduced runtime failure. It exists independently of v0.2 admission and
belongs to the portable compiler source; sequential PHP execution hides the overlap.

Repair direction when resumed: obtain retained declaration handles while holding
the same batch publication lock, then continue exclusively owned per-source work
outside the lock. Preserve standalone parsing and avoid recursive task_synchronize.
Choose the truthful lookup/collector boundary during implementation; do not lock the
entire parse, deep-copy declarations, or add implicit synchronization to every hash.

Audit context: queue membership is sealed; individual retained syntax/collection data
is per-source mutable; namespace, type and symbol publication writers use the batch
lock; exported-symbol retirement follows joining. Modifier lookup reads a separate
configuration table that must remain frozen. These findings are conditional on
phase separation from analysis/external readers, not whole-program race-freedom proof.

Proofs must deliberately overlap retained class/enum lookup with new registrations,
preserve declaration identity, exercise standalone parsing and failure/join behavior,
and cover native concurrency (race detection where available) across both routes.
No test or fix was performed during the audit. TASK-01 admission success alone is
not proof that this source defect is fixed.

Evidence: 03_parse/parser.php (previous enum/member-owner calls),
03_parse/collect/collect.php (previous lookup and synchronized registration),
03_parse/scopes/structures.php::source_types_named,
03_parse/types/catalog.php::Source_Type_Declarations and
runtime/include/scpp/support/hash_t.hpp.

## AST-01 — explicitly virtual methods with default bodies

**Core feature direction accepted on 2026-10-23; implementation paused.**

Allow an explicitly virtual instance method to establish a dispatch contract while
retaining its concrete default body. The portable PHP converter carries explicit
author intent into that contract using the agreed spelling below.
Do not automatically virtualize every ordinary method or
infer all override intent through whole-program converter analysis.

Actual needs include ast_node::clear_preparation and occurrence hooks,
expression_node::require_preparation, and loop_node::body_runs_first. Preserve useful
defaults and trait-supplied overrides instead of making every default abstract.
children/kind/maintain already have interface/abstract contracts. v0.1 broadly emits
virtual class instance methods; v0.2 currently establishes slots through abstract,
interface and existing special protocols, rejecting ordinary concrete-method hiding.
This is an agreed extension of the owning contract, not a demonstrated violation
of the current v0.2 specification.

Reuse prepared method contracts, dispatch ownership, signature adaptations, final
restrictions and explicit-parent call behavior. CPP_Dispatch already separates
source entries, authored bodies and virtual slots, including nonabstract slot bodies.
Ordinary and template-prelowered output must consume the same semantic decision.
Concrete parameter/return transport must preserve slot/override compatibility and
any bridges; an emitted default body must not turn a virtual call into fixed dispatch.

Prelowering is technically compatible with this direction, but not yet proved for
the new source feature. Required proofs in both modes: inherited default invocation,
derived override through a base/interface handle, a trait-supplied implementation,
explicit parent invocation, final/signature rejection, and virtual signatures using
admitted template payloads. Do not infer support for arbitrary generic source classes
or independently generic virtual methods from runtime-template prelowering. No new
template model or general generic virtual dispatch is authorized.

### Agreed spelling — accepted on 2026-10-23

Use `#[\Virtual]` on the initial concrete virtual method in both executable
portable PHP and Simple C++ PHS/PHP++. Retain the existing `#[\Override]` assertion
on descendants. This pairs two explicit method-contract attributes without adding
a PHP-incompatible `virtual function` spelling or a second annotation convention.

```php
abstract class BaseNode
{
    #[\Virtual]
    public function clear_preparation(): void {}
}

final class PreparedNode extends BaseNode
{
    #[\Override]
    public function clear_preparation(): void
    {
        // Clear this node's facts.
    }
}
```

Virtual is a project attribute to implement, not a claim that PHP supplies a built-in
Virtual attribute. The portable framework should supply its method-targeted marker;
ordinary PHP dispatch already permits the override. The converter explicitly
recognizes and preserves/maps the intent for each route; v0.1 retains its existing
virtual dispatch and v0.2 records the explicit contract in preparation. Current
converter/parser support is not claimed by this decision.

Use a zero-argument marker on public/protected nonstatic concrete instance
methods, excluding constructors/destructors. Abstract/interface methods already
establish virtual contracts and need no marker. Use Override for inherited slots;
overrides remain virtual without repeating Virtual. Override stays optional under
the existing language contract, but is recommended in portable source. Reject
contradictory combinations such as Virtual with abstract, static or final on the
new-slot declaration; an overriding implementation may be final under existing rules.
The spelling and bounded usage above are agreed; implementation remains paused.

## NULL-01 — distinct frontend nullability for class handles

**Direction accepted on 2026-10-23; implementation paused.**

Node and ?Node are distinct frontend contracts even when both lower to the same
shared-handle representation. Node requires a present compatible object; ?Node
permits absence. Known null arguments/returns to Node reject; nullable destinations
accept null. Possibly-null values crossing a required boundary must be proved present
or checked, never silently transported as an empty required value. Preserve the
distinction in assignments, signatures and override compatibility, not only syntax.

This supersedes the assumption that handle normalization makes ?Node and Node
semantically identical. The current normative type_use_modifiers.md explicitly says
otherwise and must be amended in an authorized implementation/specification slice;
this planning decision does not silently change the current spec. No extra native
optional wrapper is required. Prelowering may share representation but must retain
frontend nullability and boundary checks. Direct null admission must not be widened
before the destination's nullability is represented truthfully.

The desired behavior follows PHP's explicit nullable argument/return contract.
The v0.1.77 mapper uses shared_p<Node> for both spellings; that is representation
evidence, not proof of complete non-null enforcement. Audit and reconcile the v0.1
route as part of common-ground proofs. Do not introduce implicit nullability through
a `Node $node = null` default. Required-field uninitialized storage remains hidden
under INIT-02; it cannot escape as a readable Node value.

Before implementing this cross-owner change, review required-field/local initialization,
runtime binding boundaries, and ownership operations
such as weak locking and moved-from handles. Extending this exact rule to every explicit
shared/unique/weak spelling is not decided merely by accepting Node versus ?Node.
Specify the smallest coherent slice and its v0.1/v0.2 ordinary/prelowered proofs.

### Accepted reference, override and value-boundary rules

Agreed on 2026-10-23; implementation paused:

- References are invariant in frontend type including nullability, even when native
  storage representations match. Keep existing readonly-reference/reference-result
  invariance and lifetime restrictions. Neither Node storage as ?Node& nor nullable
  storage as Node& is admitted. Do not insert a temporary or a one-time presence
  check to pretend a reference has a different persistent storage contract.
- By-value virtual/interface/abstract-trait compatibility is directional: parameter
  acceptance may broaden (Node to ?Node), and return guarantees may strengthen
  (?Node to Node). Reverse directions reject. Compose with already-admitted ancestry
  adaptation through the shared callable owner, not per-type rules. This does not
  grant covariance to generic container arguments or widen other passing modes.
- Nullable-to-required ordinary by-value arguments, returns and assignments use a
  checked conversion when presence is not otherwise established. Known literal null
  to a required destination rejects statically. A successful check preserves value/
  identity semantics; failure occurs before callee entry or destination replacement.
  This preserves existing require_preparation accessors without requiring an explicit
  unwrap spelling or whole-program flow narrowing.
- Override adapters may only establish substitutable contracts, never hide an invalid
  narrowing with a potentially failing null check. PHP reference behavior is more
  permissive than native invariant storage; record that portable-subset distinction
  in the required v0.1 compatibility audit rather than claiming full PHP equivalence.

## CONVERT-02 — complete nullable container annotations

**Spelling direction accepted on 2026-10-23; implementation paused.**

Use `?array $contracts /** ?vector<generic_contract> */ = null`. The annotation
describes the complete target type, not just a payload whose nullability is supplied
separately by PHP. The PHP and annotation question marks describe one absence layer,
not two nested wrappers. Nonnullable arrays use `array ... /** vector<T> */`.
Validate consistency; migrate the former `?array ... /** vector<T> */` convention
explicitly rather than silently changing its interpretation. The actual first source
is template_type_parameter's contracts/dependencies in 03_parse/types/model.php.
Current converter support is not claimed; its existing constructor path takes
nullability from ?array and expects a vector/hash payload annotation.

## NULL-02 — definition- and capability-based nullable support

**General design direction accepted on 2026-10-23; implementation paused.**

Replace the scalar/enum/record payload-family whitelist with admission based on the
resolved type definition, storage representation and operation capabilities. Do not
implement a separate nullable feature per container or type spelling. This supersedes
the proposed vector-specific scope. ?My_Class, ?My_Class_2<T>,
?My_Class<T, Xyz<W>> and ?vector<T> use the same nullable model where their underlying
types are otherwise valid and supported; this does not independently add arbitrary
source generic declarations or missing payload types.

- Forming ?T requires a valid admissible payload type/storage contract.
- Absence construction does not construct a T; presence testing does not copy T or
  depend on its truthiness.
- Wrapping a present payload requires the admitted construction/copy/move operation.
- Copying ?T requires the corresponding copy capability of T, even when absent.
- Required extraction checks presence and uses an admitted value/reference operation;
  nullable alone does not introduce interior borrowing, ownership promotion or moves.
- Resolve nested generic arguments and dependent capabilities through the existing
  type model. Keep exact-payload conversions distinct from separately admitted
  ancestry/variance conversions; no new container covariance is implied.

Handle absence may use empty native handles while retaining NULL-01's distinct
frontend contracts. Inline payloads use optional storage and its existing lifetime
machinery. Reuse conversion and ordinary/prelowered transport owners; do not insert
container-name branches in the nullable policy.

vector<generic_contract> is the first real compiler witness, not the implementation
boundary: absent contracts select the default copyable_value requirement, whereas a
present empty vector selects no requirements. Prove this distinction, exact wrapping,
defaults, assignment/argument/return boundaries, copying and checked extraction.
Additional proofs should cover a class handle, a supported nested generic value,
and noncopyable rejection/capability propagation in both output modes. These are
required future proofs, not current support claims. CONVERT-02 only addresses spelling.

## OWN-01 — ownership edges of required object contracts

**Direction accepted on 2026-10-23; implementation paused.**

- Ordinary Node requires a live compatible object; ?Node admits absence. Explicit
  ownership handles retain their existing possibly-empty protocols rather than
  automatically acquiring the ordinary Node guarantee.
- Weak derivation never retains the target. Locking a weak class/interface observer
  produces an optional shared object value; successful locking retains identity and
  lifetime, while expiry/absence produces null. Required accessor returns check that
  result. A weak observer's initial presence never promises continued target liveness.
- Keep unique<T> empty-but-valid after explicit move, with aliases observing that
  state. Do not introduce non-null unique or Rust-style moved-variable invalidation
  as a side effect of NULL-01.
- Explicit shared<T> remains a possibly-empty ownership handle where already admitted.
  This does not legalize redundant shared<Class> spelling. A conversion to a required
  object boundary checks presence. Reject redundant ?shared/?unique/?weak spellings;
  no extra absence layer is implied.
- Registered runtime contracts must distinguish optional results from guaranteed
  present results. Required factories return a live object or fail. Do not infer
  non-nullness solely from native pointer representation or add per-function bypasses.
- PHP weak/weakref_get currently retain ordinary strong PHP references, unlike native
  weak observers. Preserve this fact in the compatibility review: host success does
  not prove native expiry behavior. Whether strict cross-route expiry parity is
  required remains an explicit compatibility disposition, not an invisible promise.

## LOCAL-01 — class-local definite assignment and struct default construction

**Direction accepted on 2026-10-23; implementation paused.**

Allow typed class-local predeclarations with no readable value until assignment.
Require successful assignment on every reachable path to a value read. Assignment
in both if/else branches suffices; branches that return/throw do not reach a later
read. Passing/returning/capturing a value and receiver use count as reads. An unused
predeclaration need not acquire a value. Ordinary reference passing requires an
initialized local initially; no output-parameter protocol is added.

This requires bounded intraprocedural definite-assignment facts in preparation.
It is not implemented by trusting Clang: an empty native shared pointer is valid
C++ storage and does not preserve the source non-null requirement. The existing
compiler admits supported predeclarations but has not established this branch-sensitive
read-before-assignment guarantee. No implementation or fresh execution proof is claimed.

Preserve ordinary default construction of structs where admitted. If no valid default
exists, require direct initialization (for example a conditional expression or a
helper returning the chosen value). Do not introduce deferred struct construction,
raw unconstructed storage, per-local optional lifetime emulation, whole-program
initialization inference or general constructor-body assignment detection. Other
value-type defaults retain their existing contracts.

Required class fields use existing direct constructor initialization (including set:
and converter-expanded promotion), rather than arbitrary body-assignment/exit checks.
INIT-02 remains the separate converter-owned staged-field exception. These decisions
do not silently prohibit historical local predeclarations or authorize new native
uninitialized syntax. Loop/exception/capture handling must be bounded soundly during
design; unsupported cases must not be claimed safe merely because Clang accepts them.

Portability principle clarification: preserve specified results, failures, identity
and resource-lifetime obligations. Implementations/cleanup timing may differ where
the program does not depend on them. PHP-only cleanup is acceptable; exact native
weak-lifetime emulation remains deferred, not a goal.

## Remaining decisions before affected implementation slices

### OWN-DEBT-01 — PHP strong carriers versus native weak observers

**Deferred by user decision, 2026-10-23; no new implementation gate.**

Current PHP weak/weakref_get helpers preserve strong references, whereas native
weak observers do not retain the target. The semantic difference is established;
no actual compiler failure from this difference has been demonstrated by this
discussion. It can affect expired-access behavior as well as retention/cleanup.
Do not implement exact PHP/native weak-expiration emulation now. Revisit only when
concrete usage or measurements justify it; PHP-only cleanup is an allowed option
subject to live-owner/lifecycle correctness. Do not claim exact expiration parity.

### Updated explicit-handle and required-field direction

User decision, 2026-10-23: weak<T> and unique<T> may carry empty state; reject
?weak<T> and ?unique<T> as duplicate spellings rather than expose two equivalent
frontend forms. This supersedes earlier consideration of distinct nullable forms
for those families. Follow-up agreement: explicit shared<T>, where admitted, is
also empty-capable; reject redundant ?shared<T>. This does not admit shared<Class>
or weaken the required Node frontend contract.

Ordinary required Node fields must contain an object when construction succeeds;
native empty backing during initialization must never escape as a readable Node.
Nullable fields may contain null, but an uninitialized PHP ?Node property is still
different from ?Node = null. Use direct initialization under LOCAL-01; no general
constructor-exit checking feature is approved. INIT-02 retains its explicit portable
staged-field protocol through checked backing/accessors.

Accepted directions do not require another general approval round. The mandatory
v0.1 decision-conflict audit above must precede implementation. After that gate,
independent accepted items can start when implementation is explicitly resumed; unresolved
decisions below block only the affected slices.

1. Resolved after the v0.1 audit: apply the explicit staged-initialization discipline
   to portable PHP; preserve typed local predeclarations under LOCAL-01. Required
   class fields use direct constructor initialization; use INIT-02 for portable
   staged fields. Structs retain valid default construction. No invented readable
   required-object value or whole-program initialization proof is implied.
2. Resolved: checked nullable-to-required by-value argument/return/assignment boundaries
   are accepted, including require_preparation accessors. Full flow narrowing is not
   a prerequisite.
3. Resolved: invariant references and directional by-value override nullability are
   accepted under NULL-01; implementation/proofs remain pending.
4. Explicit shared<T>/weak<T>/unique<T> remain empty-capable where admitted and
   their redundant ? forms reject. Weak-expiration parity is deferred under
   OWN-DEBT-01. Required-field initialization uses the accepted direct-init path.
5. Deferred review debt: shared-struct self retention (SELF-DEBT-01),
   wider _uninitialized_ access forms, and generic shared-provider constructors.
   Source, converter-default and binding directions are resolved in the sections below.

Choosing internal fact layouts, exact backend helpers, test fixtures and proof order
is implementation design/validation work, not a new user-visible contract decision.
Native v0.1 parity remains a verification obligation; do not claim it from shared
representations. Before any wide cross-owner nullability redesign, present its bounded
scope and validation cost under the repository rules. Storage_Cursor remains excluded.

## Boundary review of previous decisions

Review earlier implementation mechanisms before resuming work; accepted portable
behavior does not imply approval of new language semantics. This is a documentation
triage, not a fresh implementation audit.

| Item | Required boundary review |
| --- | --- |
| SELF-01 | Review resolved: core v0.2 shared-class self support is accepted. Reuse existing ownership; no inline/unique promotion or constructor/destructor escape. Update the owning contract and preparation when implementation is resumed. |
| SELF-02 | Review resolved for core receiver/value support: mutation affects the receiver; assignment, by-value parameters and returns copy a copyable struct. References follow existing admission/lifetime rules. Distinct shared-struct retention is deferred review debt SELF-DEBT-01. |
| COPY-01 | Converter-owned protocol; PHP performs explicit independent copies, native output uses ordinary value copies. A generated by-value `return $this` can use SELF-02 without making the protocol a native language feature. Prove evaluation/alias behavior. |
| INIT-01 | Fits the boundary in direction: promotion lowers to existing constructor initialization, and explicit valid defaults remain source adaptations. Prove order and both-route admission. |
| INIT-02 | Nullable backing/accessors fit the boundary in direction, subject to the bounded conversion and copying proofs above. No new native uninitialized feature. |
| Remaining open items | Start with existing target contracts and source/converter adaptations. TASK-01 must not grant blanket shared-graph transfer; BIND-01 must distinguish metadata gaps from new runtime protocols. Existing cursor exclusion remains. |

The SELF/COPY distinction and bounded TASK-01 rules are accepted. The shared-access
audit produced TASK-02 as a separate source fix. Implementation design and native
proofs remain pending. AST-01's explicit concrete-virtual direction is also accepted;
the Virtual/Override spelling is agreed and focused ordinary/prelowered proofs
remain pending. NULL-01 and CONVERT-02 now record the accepted nullability and
annotation directions. Initialization, reference/override boundaries, explicit-handle spelling and weak
portability dispositions are now agreed. SOURCE-01 private-field/numeric direction,
CONVERT-01 typed empty expressions/defaults and BIND-01 factory/exposure direction
are also agreed in the later sections below. NULL-01 still
requires a bounded cross-owner implementation-scope review before coding. CURSOR-01's
metadata-driven constructor relationship is agreed below; implementation is paused.
Independent accepted slices need not wait for every
unrelated backlog item to be resolved.
Self retention does not itself imply thread safety or task-transfer permission.
No decision here authorizes implementation.

Technical feasibility: the accepted bounded directions have known implementation
mechanisms (existing shared-self runtime, ordinary struct copying, nullable storage
and accessors). This is a design assessment, not a completed cross-route proof or
a claim that each slice is small. Preparation, inheritance/ownership, prelowering,
converter access resolution and PHP/native alias parity need focused validation.
General transparent rewriting of arbitrary PHP property accesses is not promised.
Report any required wide cross-owner redesign before expanding implementation.

## SOURCE-01 — numeric boundary direction

User clarification, 2026-10-23: retain uint32 where intended. Algorithm-specific range checks belong explicitly in authored PHP, added only where
logically necessary, not as a blanket conversion pattern. Clarification: the compiler
remains independent of and unaware of the converter. It may and must enforce its own
language contracts, including overflow checks through lowering where specified.
This does not authorize converter-driven checks or suppress native compiler checks.
Accepted private-field decision, 2026-10-23: allow independent same-named private
instance fields in base and derived classes. Use C++ lookup: resolve against the
receiver's static type, then check accessibility; do not retry with a base field
when the selected child field is inaccessible. Parent methods' ordinary $this
access remains bound in the parent context. Portable PHP must use an explicit
base-typed local/view where PHP lexical-private selection would otherwise differ.
No converter awareness in core semantics. Keep public/protected hiding, static
fields, methods and constants outside this bounded slice. Prepared declaration
identity and per-owner layouts already provide suitable model owners. Verify
constructor initialization, access failures, ancestor chains and ordinary/prelowered
execution before claiming support. No implementation yet.

## BIND-01 — initial runtime exposure review

Source inspection, 2026-10-23; recommendations, not implementation authorization:

- B01: eleven publication/digest leaves already exist in filesystem/output_files.hpp.
  Inputs and outputs use existing string/int/bool, result and nullable contracts.
  output_sha256 returns string directly; output_read returns result<nullable<string>>.
  Preserve failure versus absence versus unchanged distinctions. Metadata registration
  under the existing filesystem owner is the first proposed slice; no new lifetime
  protocol is indicated. Output_Stage already owns explicit promote/close behavior
  in the portable facade; do not replace it with invented compiler cleanup rules.
- B02: distinguish scpp::json::pull_reader (noncopyable parser owning source bytes)
  from scpp::json_pull_reader (language wrapper owning that parser via unique_ptr,
  start-once state and Result-returning methods). No borrowed returned string or
  caller-input lifetime requirement was found. Preserve shared object identity for
  the exposed class; never declare the underlying parser copyable. Construction is
  the concrete integration issue: runtime type registration currently permits
  construction metadata only for inline provider storage. Review an ordinary runtime
  factory returning a shared reader plus a portable facade adaptation before adding
  generic shared-provider new support. Either needs focused construction/state/error
  proofs; metadata-only support for the existing new expression is not established.
- B03: unresolved portability facade names are not necessarily missing runtime leaves.
  Existing strings/filesystem/process facades must be traced through signatures,
  exports and provider calls before adding bindings; no duplicate helper registrations.

User dispositions, 2026-10-23: B01 metadata-first exposure and B03 dependency
tracing are agreed. The B02 factory approach is now accepted; generic shared-provider
construction is deferred to CH15-SHARED-PROVIDER-CONSTRUCTION-01.

Factory approach accepted on 2026-10-23 (not implemented): add an ordinary JSON runtime
function, provisionally json_pull_reader_create(), returning a fresh non-null shared
json_pull_reader. Replace only the facade's allocation expression; retain its existing
start(text,file,limit) call and Result-to-exception translation. The empty wrapper
is a valid allocated object, not a started parser; premature operations retain the
existing initialization error. This preserves the current two-step runtime protocol.
A combined start factory is possible but unnecessary for this bounded exposure.
Register the type as shared and bind its existing Result-returning methods. The
factory needs a small native allocation adapter and v0.1 symbol/contract exposure
as well as v0.2 metadata; it is not metadata-only work. Keep allocation failure under
existing runtime policy. Host PHP Json_Reader already implements its own constructor
and need not acquire a synthetic native reader. Verify fresh factory identity,
alias state, input ownership, startup failure and subsequent protocol failures on
both compiler routes, including prelowering. Exact factory spelling remains proposed.

No fresh bootstrap or native validation was run for this review. BIND-01 direction
is agreed; binding completeness, concrete emission and cross-route proofs remain
implementation/validation work, not a pending general semantic decision.

## CONVERT-01 — explicitly typed empty containers

User direction, 2026-10-23: portable PHP must supply the concrete container type
through a doc comment. The converter must diagnose missing type information instead
of guessing vector versus hash or an element type. A directly associated typed
local/parameter declaration can supply that explicit information; a standalone
empty expression needs its own supported annotation. Do not add whole-program
inference or require redundant comments on every [] when the local declaration
already supplies the type.

Follow-up user decision, 2026-10-23: v0.2 must accept `$var ContainerType = []`
when the target's registered metadata identifies it as a container. Empty bracket
construction is admitted by default for such types; no per-type name whitelist or
new opt-out flag in this slice. A possible flag to prohibit empty construction is
future work. This extends the existing target-directed data-initialization model,
including positional/keyed and brace notation, rather than treating PHP arrays as
a special native construction mechanism. It does not authorize [] for every class
or struct: ordinary records retain their declared data/initialization contracts.

Resolve the concrete target and use its registered construction/initialization
binding to produce a valid empty container, never null or uninitialized storage.
Metadata providers must supply a truthful construction path; indexing or iteration
alone must not be mistaken for a complete container-construction contract. Exact
schema integration belongs to implementation design, using existing owners.
Ordinary and prelowered preparation must share admission. Converter output should
use supported typed data initialization rather than assume new vector<T>() works.

Follow-up user decision, 2026-10-23: parameter defaults accept typed [] under
exactly the same metadata-driven container-initialization rule. The parameter's
explicit type supplies the target, including its concrete generic arguments;
portable PHP supplies that type through its adjacent doc comment. The converter
must accept and preserve this intent. The actual link() provider_options parameter
already declares vector<string>, so it must not require another annotation or a
caller rewrite merely to avoid its empty default.

Integrate the callable-default owner with the shared initialization contract,
including ordinary and prelowered paths; do not create a vector-only exception.
Omitted arguments construct valid empty containers under their normal semantics,
without accidentally sharing one mutable default instance across calls. Preserve
explicit supplied arguments and distinguish empty from null. This is an accepted
requirement, not a claim that current implementation supports it. SOURCE-01 and
BIND-01 directions are also now reviewed. Implementation remains paused.

## CURSOR-01 — metadata-driven safe record widening

**Direction accepted on 2026-10-23; implementation remains paused.**

Expose the existing runtime Storage_Cursor<T> through binding metadata. Metadata
must explicitly authorize the relevant generic relationship; ancestry alone must
not make arbitrary generic applications convertible. Reuse v0.2 shared-handle
base/interface relationship checks, including ownership and nullability admission,
without hard-coding Storage_Cursor in semantic consumers.

The concrete existing runtime operation is construction of Storage_Cursor<T> from
Storage<U>, requiring safe record-handle conversion U -> T. Represent both types:
T is fixed by the owner, U inferred from the source. This does not by itself supply
conversion of an existing Storage_Cursor<U> into Storage_Cursor<T>, nor make mutable
Storage covariant. Any broader cursor conversion needs a matching runtime operation
and separate scope review. Exact metadata schema remains implementation design;
a blanket parameter flag must not authorize unsupported operations.

When related element types cannot cross a generic boundary because its metadata
provides no permitted conversion, diagnose the source and destination types, the
relevant argument relationship, and the missing declared conversion contract.
Explain that element ancestry does not imply container conversion. Distinguish
this from reverse/unrelated ancestry and ownership/nullability failures. Do not
suggest a PHS annotation that users cannot author. Ordinary and prelowered paths
must share the check and diagnostic; include accepted widening, forbidden reverse
conversion, unrelated types, and absent-metadata rejection in future proofs.

Native behavior already lives in runtime/include/modules/compiler/storage_cursor.hpp;
metadata/preparation integration is pending. No new lifetime protocol or runtime
redesign is approved. This records discussion, not authorization to resume implementation.

## Backlog

Implementation authorized on 2026-10-23 for accepted entries, in the dependency
order in the implementation review. Earlier paused statements record discussion
checkpoints and are superseded by this authorization for accepted scope only.
Reuse analysis precedes each entry; new semantics and wide redesigns require
discussion while independent work continues. Deferred debts remain deferred.

| Item | Subject | Decision state / dependency |
| --- | --- | --- |
| CH15-RUNTIME-TYPE-METADATA-01 | Compiler-wide registry/type-use audit and remediation | First-class final-catalog blocker; inventory early, repair by owning slice, prove ordinary/prelowered parity; not closed by discovery alone. |
| SELF-01 | Shared class self acquisition, including prelowering | Core v0.2 support accepted; implementation paused. |
| SELF-02 | Struct self: receiver, value copy and distinct shared retention | Core v0.2 receiver/value support accepted; shared retention deferred as SELF-DEBT-01; paused. |
| COPY-01 | `_struct_` / `_struct_copy_` portable value protocol | Converter work accepted; first proof is `cpp_specialization_ref`; may consume SELF-02; paused. |
| INIT-01 | Promoted required constructor inputs and explicit valid defaults | Direction accepted; inventory C03; implementation paused. |
| LOCAL-01 | Class-local definite assignment and existing struct defaults | Bounded local flow checks accepted; no general init inference or deferred struct lifetime; direct-init required fields; paused. |
| INIT-02 | `_uninitialized_` required fields, staged assignment and checked reads | Nullable backing and checked accessors accepted; reuse audit complete, 6–12x retained; bounded source/converter proofs pending; no new language feature approved; paused. |
| TASK-01 | Bounded shared-handle task admission in v0.2 | Joined API rules accepted; reuse audit complete, revised 8–12x; bounded API-policy/callback-permission design accepted; implementation/proofs pending; inventory D04; paused. |
| TASK-02 | Retained-type lookup synchronization in portable PHP | Source race path identified; same-lock lookup repair direction accepted; native concurrency proofs pending; paused. |
| AST-01 | Explicit concrete virtual methods and portable PHP intent | Core support and converter mapping with `#[\Virtual]` / `#[\Override]` agreed; ordinary/prelowered proofs pending; inventory A01; paused. |
| NULL-01 | Node versus ?Node frontend contracts, shared lowering | Direction accepted; four-consumer reuse audit complete; revised 12–20x; bounded presence/representation design and proofs pending; inventory A02; paused. |
| NULL-02 | Definition- and capability-based nullable operations | Capability/consumer reuse audit complete; revised 4–8x incremental to shared NULL-01 model; vector is a proof case, not a whitelist extension; inventory D03; paused. |
| OWN-01 | Weak locks, moved-from unique/shared handles and runtime result guarantees | Ownership and explicit-handle spelling agreed; weak-expiry parity deferred under OWN-DEBT-01; paused. |
| OWN-DEBT-01 | PHP strong carriers versus native weak lifetime | Deferred; no demonstrated compiler failure; no exact emulation planned; PHP-only cleanup may be considered; not a new gate. |
| SOURCE-01 | Private fields and numeric boundaries | Same-name private instance fields with C++ lookup accepted; explicit portable base views; source-owned algorithm checks independent of compiler contracts; paused. |
| CONVERT-01 | Empty container expressions and method defaults | Explicit doc-comment type required; registered containers accept typed [] by default. Parameter defaults use the same rule; reuse audit complete, revised 4–8x; Typed defaults and annotated expressions have PHP/legacy/v0.2 proofs; explicit empty-only provider binding passes ordinary/prelowered generic and Storage proofs. Remaining retained/provider edge verification stays open; see execution ledger; inventory C01/C02; in progress. |
| CONVERT-02 | Complete nullable array target annotations using ?vector | Spelling agreed; consistency validation and migration/proofs pending; paused. |
| BIND-01 | Publication/digest APIs and JSON pull-reader exposure | Metadata-first leaves, shared-reader factory and facade tracing accepted; inventory B01/B02/B03; implementation/proofs pending. |
| CURSOR-01 | Storage_Cursor registration and safe read widening | Metadata-driven relationship and explanatory diagnostics accepted; runtime constructor scope recorded above; implementation paused. |
| VERIFY-01 | Reconcile remaining diagnostics and retry measured bootstrap | After agreed slices; historical failures are not fresh defects. |
| DOC-01 | Reconcile stale portability guidance | After relevant decisions; inventory S01. |

Readonly enforcement remains separately deferred as CH15-READONLY-FIELDS-01.
No class/struct semantic change, converter change, runtime change, or bootstrap retry
has been made by recording this backlog.

## Decision checkpoint — 2026-10-23

No known unresolved user-facing decision prevents starting an independent accepted
slice when the user resumes implementation. This is not a guarantee that later
source/binding audits will uncover no new protocol or semantic questions.
Before coding, reconcile the completed v0.1 audit with subsequent decisions (notably
private-field lookup and container defaults), record the bounded implementation
order, and present any required wide cross-owner redesign, especially NULL-01.
The CH15 compiler-wide registry/type-use audit remains an explicit final-catalog
blocker. Deferred debts remain deferred; no implementation is authorized here.

## Reconciled audit and proposed implementation review — 2026-10-23

The [v0.1 documentary audit](compiler_portability_v01_decision_audit_2026_10_23.md)
now incorporates accepted private-field lookup, typed container/default construction,
local initialization and ownership dispositions. The
[per-entry review and proposed order](compiler_portability_implementation_review_2026_10_23.md)
records feasibility, relative work bands, current-contract changes and dependencies
for every backlog entry and associated deferred debt. Estimates are directional,
not time commitments; NULL-01 and LOCAL-01 need explicit design scope, and registry
remediation volume is unknown until the blocking audit inventories it. No normative
contract, implementation or bootstrap was changed by this reconciliation.

## LOCAL-01 implementation-owner decision — 2026-10-23

Reuse existing Body_Preparation and Completion_Preparation structured flow, extending
assignment facts for locals rather than adding a separate traversal/control-flow
subsystem. Preserve distinct fallthrough/return/break/continue paths and account for
exceptional exits and actual evaluation order. User accepted revised relative workload
6–10x (3–5x basic state/branches; 3–5x loop/exception integration and proofs).
No implementation authorized. See the implementation review's nullable reuse audit
for existing NULL-01 foundations; its retained representation design is still pending.

## NULL-01 consumer audit and revised estimate — 2026-10-23

The [focused consumer audit](compiler_null01_consumer_audit_2026_10_23.md) inspects
member access, ownership queries, wrapper transport and retained type keys. Reuse
existing nullable applications and registry IDs, lookup/access/place owners,
value/ref/override contracts and semantic retained comparisons. Do not build a new
nullable registry or key system. Presence must be distinct from physical ownership;
removing normalization alone is insufficient because ownership dispatch and nullable
wrapper transport currently encode different assumptions. Proposed shared-handle
nullable representation needs integration in the existing backend transport owner,
including nested provider/prelowered paths, without an extra optional runtime layer.

Work estimate revised from 15–30x to 12–20x, still a cross-owner integration task.
No behavior/retained proofs were executed; incremental change/revert, exact reference,
override and provider transport proofs remain necessary. The audit does not close
the compiler-wide CH15 registry debt, admit new nullable dereference semantics, or
authorize implementation. See its four proposed slices and explicit escalation gates.

## NULL-02 reuse audit and updated estimate — 2026-10-23

The [focused NULL-02 audit](compiler_null02_consumer_audit_2026_10_23.md) identifies
reusable nullable formation/copy dependencies, recursive capability queries, optional
runtime storage, conversions and existing nullable-record/retained tests. No new
nullable model or per-type implementation is needed. Replace family gates with
operation-specific admission and migrate presence, checked/take extraction, null
defaults and existing selection consumers. Preserve separate truthiness, reference,
move and wrapper-delegation policies; value_storable alone grants none of them.

Estimate revised from 6–12x to 4–8x incremental after the shared NULL-01 model is
settled. Optional-class identity/representation changes belong to NULL-01; capability
payload generalization and unfamiliar/nested type proofs belong to NULL-02. Existing
record tests are reusable but were not run in this audit. New protocol/capability or
ABI gaps require discussion before broadening the slice. No implementation authorized.

## TASK-01 reuse audit and revised estimate — 2026-10-23

The [focused task audit](compiler_task01_reuse_audit_2026_10_23.md) identifies existing
bounded executor, per-batch mutex/context, synchronous synchronize, join/error protocol,
generic metadata, captures and ordinary/prelowered callable transport. Reuse these.
Missing shared admission must be scoped to the joined API; the existing erased
callback transfer bool cannot safely grant this permission without widening unrelated
APIs. Propose extending existing permission/required-policy facts and transport;
exact bounded representation design must precede implementation. No class-name
whitelist, scheduler redesign, arbitrary borrowed captures or race analysis.

Relative TASK-01 estimate revised 8–15x -> 8–12x, with native behavioral and rejection
proofs included. TASK-02 remains an independent source synchronization fix required
before concurrent compiler bootstrap. Existing tests were inspected, not executed.
No implementation or new protocol expansion is authorized by recording the audit.

## TASK-01 bounded policy/permission design accepted — 2026-10-23

User accepted the following design; implementation remains paused.

- Registered API metadata selects a compiler-defined admission policy. Existing
  isolated_transfer semantics remain unchanged; joined_shared additionally permits
  the agreed shared-object handles for the joined unordered API. Policy names here
  are conceptual, not finalized metadata syntax. Metadata cannot override resource,
  ownership, borrowing or lifecycle contracts.
- Callback environments carry supported permissions separately from signatures and
  API requirements. Supported owned-value captures qualify for both policies; shared
  object handles captured by value qualify only for joined_shared. Arbitrary borrowed
  captures and unsupported resource/ownership forms qualify for neither task policy.
  Ordinary synchronous callability remains governed by existing rules.
- Capture_Preparation computes the environment permissions from existing capture
  facts, conservatively combining every capture and nested callable permission.
  No recursive race-freedom claim or source_work/queue name whitelist is introduced.
- Known closures can reject at preparation; erased callable values retain the existing
  guard mechanism extended to check the requested policy. Copies, erasure, nested
  captures, provider adaptation and ordinary/prelowered transport preserve permissions.
  Passing a callback to the joined API never upgrades its permissions for other APIs.
  Existing requires_transfer retains its restrictive meaning.
- Work and publish both require joined_shared admission because both may execute on
  workers. Serialization is not a caller-thread guarantee. task_synchronize remains
  synchronous/nonescaping under its existing context/nesting checks and ordinary
  lifetime rules, without a task-submission permission or new borrowed-capture grant.
- Reuse metadata, payload/capture preparation, callable carriers and C++ transport.
  No scheduler/publication redesign or second callable abstraction. Exact enum/flags
  layout is implementation design. Wider owner redesign still requires discussion.

Decisive proof: the same shared-capturing callable is accepted by the joined API,
rejected by an isolated API and callable ordinarily, including after erasure and
prelowering. Preserve existing TASK-01 identity/join/failure proofs. Estimate remains
8–12x; design agreement is not execution proof or authorization to implement.

## CONVERT-01 reuse audit and revised estimate — 2026-10-23

The [focused construction/default audit](compiler_convert01_reuse_audit_2026_10_23.md)
identifies existing metadata-driven typed empty construction, positional/keyed factory
selection, ordinary/prelowered emission, construction defaults for synthesized
property parameters and fresh omitted-argument call temporaries. Reuse/factor those
owners instead of building a new default-expression subsystem. General authored []
default admission, missing registered container empty factories and converter output
remain work. Registry container/construction identity must be truthful, not inferred
from iteration alone or hard-coded by family. Preserve explicit PHP type comments.

Revise 6–12x to 4–8x, sharing constructor grammar work with INIT-01. Existing scalar
runtime-metadata defaults are a separate provider-schema surface; source [] support
must not claim that unproved coverage. No arbitrary expression/reference defaults or
general source-factory expansion. Verify fresh defaults, explicit-argument suppression,
retained generic changes and PHP/v0.1/v0.2 ordinary/prelowered behavior. No implementation.

## INIT-02 converter reuse audit — 2026-10-23

See [the focused audit](compiler_init02_reuse_audit_2026_10_23.md). Reuse explicit
property/method emission, local declaration facts, trait expansion, diagnostics and
nullable storage. The missing owner is a bounded converter field-access transformation:
current property nodes contain a spelling, not receiver identity or read/write intent.
Keep **6–12x**, incremental to NULL-01/NULL-02 and COPY-01. No implementation started.

Recommend directly identifiable field reads/standalone writes first; use explicit PHP
accessors for unresolved flows. Preserve single evaluation, visibility and checked
failure; reject/adapt reference/nested mutation and presence/reset operations. Distinguish
struct payloads inside a class from struct owners: current value-record grammar and
v0.1 restrictions do not admit a general nullable-field/method rewrite in structs.
Prove an existing route-safe source/helper representation or leave that form unsupported;
never extend Simple C++ solely for INIT-02. Both route and prelowering proofs remain pending.
