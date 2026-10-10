# 15. Accumulated blocking debt and final review
Doc Status: planning

[Catalog and workflow](README.md)

This is the final catalog chapter. Revisit the accumulated blocking design debt
after the feature chapters and the planned rest break, before declaring the overall
catalog pass complete. Keep this chapter last if more feature chapters are added.

Moving a debt here changes its review milestone, not its resolution status or the
language contract. Immediate correctness and memory-safety prerequisites still gate
the features that need them. This register is initially seeded with the explicitly
transferred iteration-policy and reference-iteration safety debts; it is not an exhaustive migration of existing
debts. Other chapter blockers remain in place unless explicitly transferred.

## Blocking inventory

Non-blocking follow-ups are recorded separately below; their presence in this
chapter does not make them v0.2 release gates.

| Debt | Origin | Status | Final completion requirement |
| --- | --- | --- | --- |
| CH06-ITER-01 — mutation/replacement during iteration | [Chapter 06](06_containers.md#ch06-iter-01--mutation-during-iteration) | Open; blocks final catalog completion, not Chapter 06 design closeout | Agree the long-term policy, reconcile contracts, implement required changes and prove behavior/safety |
| CH06-REF-01 — reference foreach safety foundation | [CTRL-FOREACH-003/004](06_containers.md#ctrl-foreach-003) | Open; reference foreach stays disabled; final-catalog blocker | Establish runtime guards and binding lifetime/COW guarantees before compiler integration |
| CH15-LIB-DYN-01 — library redesign and mixed/dynamic migration | [Chapter 06](06_containers.md) | Deferred by user; does not block core type migration | Review library-owned models, adapt affected APIs and prove enabled/disabled paths |
| CH15-DYNAMIC-PROP-01 — dynamic `->` | [Chapters 05/06](06_containers.md) | Deferred by user; not part of core family/JSON integration | Agree property semantics and implement shared prepared access with runtime checks and proofs |
| CH15-NAMED-ARGS-01 — named arguments/parameters | Chapters 04/10 and callable/lambda support | Deferred to final chapter; lambda support explicitly non-blocking | Agree and implement the shared function/method contract; review lambda extension separately |

User confirmation on 2026-10-05: library redesign, reference foreach and dynamic
`->` belong to this final chapter, not the remaining Chapter 06 implementation
queue. Reference foreach remains disabled until CH06-REF-01 is resolved. This
deferral does not change ordinary typed-object member access or existing contracts.

### CH15-NAMED-ARGS-01 — named arguments across source callables

Recorded by user request on 2026-10-07. Cover named arguments and their parameter-name
contracts for functions and methods, including constructors. Include lambdas in the
design, but **lambda support is non-blocking**. This is deferred work, not an
authorization to enable new call syntax during the current constructor slice.

Use the shared callable signature/argument-binding foundation, not separate
function, method and lambda implementations. Discuss syntax, parameter-name identity
and compatibility, positional/named mixing, defaults, duplicate/unknown/missing names,
variadics, reference/ownership boundaries and source-order argument evaluation.
For callable values/lambdas, establish whether parameter names survive in the callable
type/signature and how invocation works when names are unavailable. Coordinate with
keyed `data` constructor inputs without conflating data notation with ordinary calls.
Reconcile authoritative contracts before implementation; prove binding diagnostics,
evaluation order and incremental signature-name invalidation.

CH12 agreement (2026-10-08): preserve original parameter names while giving each
closure an anonymous source-occurrence identity in its enclosing context. Identity
and generated C++ naming are separate from parameter-name contracts. Concrete
closures can retain their names; an erased `function<int(int)>` boundary carries
no authored names in that spelling. Named calls across such a boundary require
an explicit name-bearing contract or rejection; do not recover names from whichever
closure happened to initialize the value. Named-call implementation remains deferred.

## Deferred feature scope

### CH15-CALLABLE-DEFAULTS-01 — authored callable property defaults

Explicitly deferred by the user on 2026-10-09 from [Chapter 12](12_closures.md#reconciliation--2026-10-09).
This covers closure/arrow defaults on instance and static callable properties, plus
callable leaves in instance vector/hash property defaults. No new release-blocking
classification is assigned. Existing absent defaults, constructor/data inputs and
ordinary assignments remain supported; authored closure defaults remain rejected.

The last proposal was capture-free defaults, explicit expected callable signatures,
per-object creation for instance defaults and once-per-declaring-type static
initialization. It was deferred before approval: treat these as discussion candidates,
not normative semantics. Revisit creation context, access to static members, capture
admission, constructor/data override behavior, copy semantics and initialization order.
Static container literals and arbitrary factory-call defaults remain separately gated.
Use the shared property-default and callable preparation/lifecycle owners; do not
introduce separate closure, vector and hash initialization implementations.

### CH15-CALLABLE-PARAM-DEFAULTS-01 — defaults across erased callable boundaries

Explicitly deferred by the user on 2026-10-09 from CH12. Concrete closures retain
their supported parameter defaults. Conversion to explicit `function<...>` storage
continues to reject defaulted signatures rather than silently discard the default.
This is distinct from authored property defaults above. Revisit whether defaults
belong to the destination contract, whether adaptation may require explicit arguments,
and how default identity, evaluation and compatibility interact with named arguments.
Use the shared callable signature/argument owners. No new release-blocking status
or callable-type spelling is assigned by this deferral.

### CH15-CALLABLE-RECEIVER-01 — owning and escaping receiver captures

Explicitly deferred by the user on 2026-10-09 from CH12. Current `$this` captures
remain borrowed and invocation-bounded; they cannot escape into erased callable
returns, properties or containers. Revisit explicit ownership syntax, class versus
inline-record behavior, shared retention, cycles, weak alternatives and destruction
ordering before enabling an owning alternative. Do not silently turn existing
borrowed capture into owning capture. This deferral does not defer the separately
selected captured-field borrow work, nor grant it receiver lifetime extension.
No new release-blocking classification is assigned.

### CH15-INHERITANCE-EXTENSIONS-01 — deferred CH11 extensions

Explicit user decision on 2026-10-08: remove the following from the current CH11
implementation queue and revisit in final review. Existing rejection boundaries remain;
this deferral assigns no new release-blocking classification or language semantics.

| Item | Review prerequisite |
| --- | --- |
| Instance late static binding | Runtime called-type representation, fixed-method selection, forwarding, specialization scheduling and invalidation; hidden virtual machinery is only a proposal |
| Inline/struct `new static()` | Called-type construction, value representation, result typing and no accidental slicing |
| Unique/weak downcasts | Ownership-specific checked narrowing and failure/consumption guarantees; weak locking remains explicit |
| Unique/weak conditional joins | Selection result rules, unique consumption and weak presence without hidden lifetime acquisition |
| Sibling conditional joins | Common-ancestor/interface selection and ambiguity; current shared joins choose only one supplied type |
| Trait adaptations | Alias/conflict-resolution syntax integrated with consumer-owned members |
| Abstract constructor contracts | Descendant signature obligations, inheritance, access and ordinary/data construction |

Shared cross-casts, non-public destructors and variance/covariant returns remain selected
CH11 work, not this debt. Inherited-final-virtual interface forwarding is implemented;
interface-qualified static dispatch is explicitly deferred below. Trait lifecycle
support has its separate entry below.

### CH15-INTERFACE-STATIC-01 — interface-qualified static dispatch

Deferred by user approval on 2026-10-08; not a CH11 implementation/closeout blocker.
Keep interface-typed expression qualifiers (`produce()::method()` returning an
interface) rejected. Ordinary expression-qualified static calls select a concrete
declared type and evaluate the qualifier once; they do not select its runtime class.

Before enabling this extension, decide whether static interface contracts are useful
and how a concrete implementation is selected. A signature alone supplies no callable
body. Runtime selection would need an explicit dispatch representation, absent-handle
policy, evaluation/lifetime rules and incremental dependencies. Preserve the existing
declared-type semantics unless a coordinated contract change is approved. Explicit
concrete static calls and ordinary interface instance calls remain available.
This deferral assigns no new release-blocking classification.

### CH15-TRAIT-LIFECYCLE-01 — trait constructors and destructors

Deferred by user on 2026-10-08 from CH11; not part of its next implementation queue.
Trait constructors/destructors remain disabled. Final review should decide whether
and when to enable them; no release-blocking classification was assigned by this
deferral.

Before implementation, reconcile authoritative contracts for local-versus-trait-versus-
inherited constructor precedence, conflicting trait lifecycle members, constructor
`set:` bindings to consumer properties, visibility, parent initialization and `data`
construction. Review destructor selection, nonthrowing cleanup and base/member order;
do not silently chain multiple trait destructors. The discussed precedence rules were
proposals, not approved language semantics. Reuse consumer-owned trait method instances
and the common callable/constructor/destructor paths rather than new lifecycle ASTs.
Trait adaptation syntax is separate deferred work, not enabled by this debt.

## Non-blocking follow-ups

### CH15-RUNTIME-PROCESS-01 — environment/shell edge and resource review

The core environment/shell catalog preserves the existing falseable-string contract:
getenv absence and shell pipe-open failure are false; present empty values/output
are success. Nonzero child exit is not a failure state in shell_exec. Byte capture
now preserves embedded NULs. Review embedded-NUL input names/commands (currently
passed through c_str), stream-read failures/partial output, ignored pclose status,
unbounded capture, environment concurrency and Windows behavior before broader
guarantees. Do not silently change failure states or introduce managed-process
semantics into this helper. The separate managed-process APIs still need registered
handle/output types. This follow-up does not block the bounded catalog pass.

### CH15-RUNTIME-HASH-01 — stable hashing implementation and persisted-key compatibility

The core-utility audit found `scpp::stable_hash::string_u64` delegates to
`hash_detail::key_ops<string_t>::hash`, which mixes `std::hash<std::string>`.
This does not establish the stable-hash header's promise of repeatable output across
processes and host standard-library versions. Existing golden tests establish only
the tested toolchain's values, not the portable algorithm contract.

Before registering `hash_string` / `stable_hash_string_u64` in my-try metadata,
agree a fixed byte-hashing algorithm and a persisted-key/version migration policy,
or explicitly revise the promised scope of stability. Do not silently change the
runtime algorithm: current compiler caches and other consumers already use it.
Prove string/span agreement, empty and binary/UTF-8 inputs, and cross-toolchain
goldens under the chosen contract. `stable_hash_bytes_u64` additionally needs the
registered source/byte-span type model. The existing providers remain unchanged;
the new catalog intentionally leaves these helpers unavailable pending review.

### CH15-RUNTIME-TYPE-METADATA-01 — review metadata-driven types, not hardcoded families

User-requested review (2026-10-09): verify that runtime types, template parameters,
capabilities/dependencies and backend bindings come from module metadata and reach
consumers through the shared registry, rather than hardcoded type names or per-family
branches. Audit registration, preparation and lowering, including nested Result and
container combinations. Identify language-intrinsic semantic roles separately and
justify any compiler-owned exceptions; do not confuse runtime declaration data with
the compiler algorithms that interpret it.

Foundation implemented 2026-10-09: ordinary nominal/template definitions, formation
requirements, defaults, capability dependencies and separate C++ bindings are JSON-driven.
Vector/hash now use that path, and scope publication enumerates registered exposures.
Language-interpreted types remain explicit compiler-owned exceptions. This does not
close the audit: initialization/indexing/iteration protocol declarations, runtime
members/factories, specialized storage and handle-lifetime facts still need review.

Acceptance proof: introduce an unfamiliar runtime type and nested applications through
metadata alone, with separate C++ bindings, and exercise admission/copy checks without
editing parser, preparation or emitter logic. Verify selected-module isolation and
reuse of decoded metadata across registry resets. This entry records review work,
not a claim that the entire audit or migration is complete. The bounded foundation's
metadata-only receipt/parcel proof lives in `tests/runtime_types.php` and its JSON/C++
fixtures; broader owner/protocol migration remains open.

### CH15-RUNTIME-REGEX-01 — regex contracts and binding prerequisites

The ready-contract module pass registers ten strict helpers (18 signatures), not
the entire regex surface. Held forms remain unavailable in executable metadata:

- `regex_filter`: the builtin spec promises literal replacement, while the runtime
  expands positional backreferences through `replace_impl`. Empty input skips
  pattern/limit validation. Reconcile before exposing it; no behavior change is approved.
- `regex_replace` with a limit: C++ overloads distinguish `const int_t& limit` from
  `int_t& count`. The emitter's ordinary value snapshots are mutable lvalues and
  can select count instead of limit. Use an unambiguous provider binding or a
  general signature-aware backend binding model, not a regex-name branch or a
  reference parameter pretending to be a value limit.
- Count outputs require shared overloaded-reference admission; the current metadata
  consumer intentionally rejects reference-bearing overload sets.
- Callback APIs need explicit callable-type recipes in metadata, reusing the
  callable registry and normal lifetime rules, not string/mixed placeholders.

The module remains opt-in. Automatic optional-provider linkage and CLI selection
remain with their build/composition owners. Current matching behavior, including
UTF-8 zero-length advancement, warrants later edge review; registration is not a
claim of new PCRE/PHP compatibility. These items do not block the registered subset.
See the [inventory](../planning/runtime_catalog_coverage.md#regex-module-inventory--2026-10-09).

### CH15-RUNTIME-FILESYSTEM-01 — filesystem platform and edge review

The nineteen-function strict catalog pass proves ordinary operations and the Linux
snapshot contract, not comprehensive cross-platform filesystem behavior. Validate
the directory-only `_wrmdir` branch on Windows, including directory-link behavior.
Review `fs_mtime`'s file-clock/system-clock conversion near second boundaries before
depending on exact round-tripping into `fs_read_snapshot`. Iterator increment and
stream close failures also need a focused ordinary-filesystem error-path review;
do not silently extend the checked snapshot protocol to ordinary get/put/scan.
Registered file-lock/stream handle types and automatic optional-provider linkage
remain separate prerequisites in the [module inventory](../planning/runtime_catalog_coverage.md).
The `fs_scan` prerequisite is resolved: shared Result operations now accept typed
container payloads with recursive copy checks. This does not resolve handle types
or filesystem platform/error-path debt above.

### CH15-RUNTIME-DATETIME-01 — datetime edge/platform review

The strict module catalog preserves all 11 existing datetime signatures, including
uint64 monotonic micro/nanosecond counters. Generalize fixed-width integer operations
in their shared compiler owner before claiming direct arithmetic/comparison support
for these return values; catalog registration must not narrow them to int.

Review the local parser's unconditional mktime `-1` failure check (that Unix second
can represent a valid pre-epoch time), host time_t narrowing/formatting range, and DST
normalization. These are inherited runtime edge limitations, not approved changes to
the datetime contracts. Add focused timezone/platform proofs with the eventual repair.
The module pass proves ordinary UTC/local forms and errors; it is not full calendar
or timezone support. Automatic optional-runtime linkage in my-try's native cache also
remains with the build owner, not the semantic catalog.

### CH15-RUNTIME-INTEGRATION-TESTS-01 — extended module validation

Non-blocking testing debt requested 2026-10-10. The runtime integration plan uses
focused positive/negative and generated-C++ boundary proofs. After foundations
settle, review large catalogs, malformed-input fuzzing, all provider API edges,
cross-platform linkage, full ownership/callable/protocol combinations, PCH/cache
performance and the native compiler rebuild. Do not treat this deferral as
permission to enable unsafe lifetime paths or skip representative rejection tests.

2026-10-10 protocol checkpoint found a stale `tests/data_construction.php` parser
expectation: it rejects `{a: function() {}}`, despite subsequent lambda syntax
support. Reconcile that older negative inventory with current callable contracts;
the initialization/model and data-preparation suites were used for this checkpoint.

### CH15-RUNTIME-SOURCE-01 — borrowed source views

Open; prerequisite for enabling span APIs, not a blocker for owning source calls.
The current byte_span stores a raw source_buffer pointer and a generation.
Destruction leaves the pointer dangling; replacement/move is not uniformly
invalidated. Do not register it as an ordinary owned value. Agree a reusable
borrow-origin/escape/invalidation contract or a safe runtime view representation,
then prove return, storage, temporary, release, replacement and move cases before
enabling source_buffer_span, span queries, hashing and builder span append.
Existing native consumers also need review. Separately review uint32 endpoint,
column and line-count representability for maximum-size sources; this batch
repairs reverse-map addition overflow but does not claim exhaustive 4-GiB proofs.

### CH15-RUNTIME-STRINGS-01 — unresolved string contracts and runtime types

The 2026-10-09 [module inventory](../planning/runtime_catalog_coverage.md#strings-module-inventory--2026-10-09)
holds these symbols outside executable catalog JSON pending review:

- `strpos`/`strrpos`: top-level byte-position contracts conflict with runtime and
  subordinate catalog code-point positions. Agree reconciliation before registration;
  do not extrapolate the `strlen` byte decision to every text operation.
- `substr_replace`: omitted length and unusable negative windows disagree between
  top-level specs and runtime. Do not silently choose insert versus suffix replacement.
- Boxed `implode`: string-only element contract versus historical adapter casts;
  establish dynamic admission explicitly without mixing boxed families.
- Builder byte-span append still needs the source module's span/view model; do not
  flatten it into mixed. The two ordinary builder value types and their twenty other
  helpers are now metadata-registered, using shared value/reference/observation rules.

These gaps do not block the ready-contract module pass. Boxed formatting retains its
separate debt below. No decisions to change these semantics are implied by this record.

### CH15-RUNTIME-FORMAT-01 — boxed number formatting

The v0.2 runtime catalog intentionally registers `number_format` only for canonical
int/float inputs (1/2/4 arguments). Before enabling mixed/dynamic formatting, establish
explicit accepted numeric kinds and failure behavior. The historical mixed runtime
overload currently falls back to formatting zero for unsupported kinds such as tables;
that is not permission to expose this behavior through the new compiler. Reconcile
null/bool admission as part of that review, preserve family separation and reject
unsupported boxed kinds at runtime. Add focused kind/error tests before registration.
This does not block concrete int/float formatting or current catalog progress.

### CH15-TEXT-LENGTH-01 — Unicode length and cross-surface consistency

User decision on 2026-10-09: v0.2 `strlen` counts bytes, using the existing runtime
byte-length helper. Review a separately named Unicode/code-point length API and its
performance, and reconcile older strict S2S metadata (`strlen -> str::length`) with
the byte-oriented authoritative builtin contract. Preserve existing portable compiler
managed-text helper behavior until its callers have been audited. This is non-blocking
review debt; it does not authorize reverting v0.2 `strlen` to code-point counting.

### CH15-CALLABLE-TARGETS-01 — precise erased-call target analysis

Recorded with the approved CH12 reference-return prerequisite on 2026-10-09.
The current reference-cycle audit connects an erased invocation to every prepared
anonymous callable with an exact compatible signature. This includes value-returning
intermediaries. It is conservative: an unused/unassigned compatible closure can make
a safe program reject. No assignment-flow or points-to analysis is claimed.

Review a more precise target model across assignments, parameters, returns, properties
and containers, including incremental invalidation and unknown targets. Preserve the
recursive-reference-return safety boundary until that analysis is proved. Reuse the
shared signature comparison; do not introduce a backend-only exception or silently
drop indirect edges. Non-blocking precision debt, not a new release gate.

### CH15-PROTECTED-01 — broader protected receiver access

User decision: use C++-compatible protected access for the first CH11 inheritance
slice. A descendant accesses a protected instance member through a receiver whose
declared type is that descendant or one of its descendants, not an arbitrary
base-typed or sibling-typed object. Protected static members have no instance
receiver restriction. Check access during preparation, not by waiting for Clang.

Discuss whether to adopt PHP's broader family access later. If adopted, design
controlled lowering helpers for both properties and methods; merely emitting
`protected:` cannot implement that contract. Keep visibility enforcement, evaluation
order, storage identity and references intact. This discussion is non-blocking;
the broader behavior is not currently authorized.

### CH15-TEST-FIXTURES-01 — transport-aware fixture deduplication

The [CH10 timing review](../testing_ch10_review.md) identifies 471 duplicate
supplementary executions caused solely by artifact source-path identity. Normalize
test transport identity for deduplication while preserving source text, unit boundaries,
include graphs, assertions, expected outcomes and host/native parity. Add negative
deduplication proofs before changing the runner. Also consider compatible runtime-object
reuse and phase interval timestamps alongside the existing cache/PCH work.
Non-blocking optimization; no coverage reduction is approved.

### CH15-MISSING-01 — optional absence spelling

Agreed 2026-10-07: consider a `missing` operation/name after the collection-only
`empty` change. For now use `!isset(...)`. Decide whether any new spelling is just
an alias or needs distinctions among null, missing keys, expired handles and Result
non-value states. No syntax or additional semantics are approved yet. Non-blocking
for v0.2. Future custom collection emptiness opt-in must mean zero elements, not
invalid/uninitialized/failed state; its registration surface is deferred too.

### CH15-METHOD-LIFETIME-01 — remaining instance receiver lifetimes

Recorded 2026-10-07 after the approved bounded instance-method slice. Unsupported
paths remain preparation errors, so this follow-up does not permit unsafe calls or
block the supported shared/whole-inline path. Review unique-owned receivers,
projected inline receivers and inline temporaries, plus a declared read-only-method
contract. Weak receivers require explicit locking for now.

Receiver selection precedes arguments. For example,
`$owner->method(consume(move($owner)))` must not retain a dangling raw receiver or
silently select a replacement object. Shared calls retain the selected object;
unique/projection cases need call-scoped lifetime/invalidation guarantees before
admission. Assess compile-time borrowing versus checked runtime protection, including
aliases, nested calls, replacement and exceptions. Do not infer a read-only API from
the current method body. Preserve the shared callable path; do not add family-specific
call implementations or change ownership-wrapper semantics as a workaround.

### CH15-CTOR-SYNTAX-01 — deferred constructor property promotion

Recorded by user request on 2026-10-07. **Non-blocking for Chapter 10 and v0.2.**
Decision updated on the same date: property-backed `set:$property` parameters are
adopted for the first authored-constructor slice in Chapter 10. They are no longer
deferred syntax-design debt. Their [authoritative contract](../../../../specs/data_initialization.md#property-backed-constructor-parameters)
keeps properties declared separately and reuses their types and explicit defaults.

PHP-style promotion, which declares properties in the parameter list, is deferred
to this non-blocking Chapter 15 debt instead. For example, this remains deferred:

```php
data class User {
    public function __construct(public string $name, private int $age = 18) {
    }
}
```

Later assess whether promotion adds enough value alongside property-backed inputs,
including compatibility needs, layout readability, duplicate declarations, defaults
and visibility. If admitted, normalize it to the same constructor input/member-
initialization model; do not add a competing execution path. Deferring promotion does
not defer ordinary typed constructor parameters or Chapter 10's adopted `set:` syntax.

### CH15-DATA-REF-01 — initialization with references into the input data set

Recorded by user request on 2026-10-07. **Non-blocking for Chapter 10 and v0.2.**
Design a solution for initialization from PHP-array-like or JS-like data notation
when there is a reference to an element inside that same input data set. Coordinate
with the [data-construction contract](../../../../specs/data_initialization.md#31a-explicit-data-classes-and-structs--2026-10-07)
and the class/struct constructor model; do not introduce a separate notation parser.

Clarify whether the reference denotes the source element or the corresponding
constructed value, whether it is used only during construction or retained afterward,
and how identity, copying/COW, mutation and owner lifetime affect it. Include references
between entries, forward references and cycles in the design review where applicable;
no syntax or support for those cases is approved by this note. Define evaluation order,
failure cleanup and rejection/runtime checks before enabling the feature. Preserve
existing reference restrictions and non-escaping initialization-input lifetimes until
a safe contract and focused behavioral/lifetime proofs replace them.

Proposed notation (user suggestion, 2026-10-07; not approved syntax): an entry value
can be tagged with `(#tag)` and referenced elsewhere in the same data set as `#tag`:

```php
{
    customers: [
        {id: 1, name: "Alice"},
        (#12){id: 2, name: "Alex"},
        (#vipA){id: 3, name: "Bob"},
    ],
    best_customer: #12,
    alternate_customer: #vipA,
}
```

Tags are arbitrary identifiers, not array positions or object property values.
They may be numeric or alphanumeric; each tag must be unique across the data set,
including nested constructions. Repeated uses of a tag reference the same tagged
element; they do not declare it again. The proposed spelling must be evaluated for
both PHP-array-like and JS-like notation through the shared parser. Exact tag grammar,
case sensitivity, data-set scope boundaries, forward references, missing/duplicate
tag diagnostics and reference-versus-copy behavior remain design questions under
this debt. In particular, the notation alone does not override value/COW semantics,
authorize escaping input views or establish cycle ownership.

### Chapter 09 closeout follow-ups

Approved on 2026-10-06; these do not block the bounded CH09 feature closeout.
Review PHP-style enum reflection (`cases/from/tryFrom`, pseudo-properties), string
backing, nullable/Result enum payloads, enum field/default initialization, broader
constant case expressions and exhaustiveness analysis. Explicit `enum_name`,
`enum_value` and checked `enum_from_value` are implemented; they do not establish
support for the imported reflection example. Existing rejections stay in force.
Constructor/nullable-object/shortcut cards belong to CH10; enum methods/interfaces
await CH10/11 shared models. Reconcile those owning chapters before final closure.

Keep pre-existing test maintenance visible: `tests/model.php` hardcoded built-in
counts still need repair before claiming that suite passes. The tokenizer byte
expectation was repaired during native validation: CH08 namespace separators are
accepted punctuation. Its complete byte-contract suite now passes.

### Runtime CMake static/shared linkage

The CH08/09 runtime-prerequisite integration exposed a link-order issue when an
otherwise core-independent test links `scpp_runtime`: shared JSON/filesystem
modules reference symbols in the preceding static core archive, which is not
extracted when the executable itself has no core reference. Preserve this for
build-system review; do not alter mixed/dynamic semantics to satisfy linkage.
Focused process/lock/document safety targets depend only on their actual implementation;
strict CLI composition/build/run is proved separately. Evidence:
`/tmp/scpp-v02-process-lock-native-20261006/`, initial `test_process` link failure.
The JSON document integration reproduced the same issue with `test_json_document`;
its isolated document test and complete strict JSON module build/run both pass.

### CH15-CLEANUP-PERF-01 — compiler cleanup performance and IDE debug endpoint

Recorded by user request on 2026-10-06. **Not a blocker for v0.2.** Review compiler
cleanup/retirement and normalization performance, including repeated edits, removed
sources/bodies, failed-parse recovery and long-lived IDE sessions. Measure work
visited, retained/released state, allocation cost and latency before optimizing;
preserve identity, dependency invalidation and join-ordered lifetime guarantees.
Include retained-scope handoff costs and repeated handle membership across failed
parse retries; the namespace parser currently prioritizes keeping every required
scope alive until retirement over minimizing that inventory.

Also design cleanup observability as an IDE **debug endpoint**: make it possible
to inspect what cleanup would visit, what remains retained and why, and the measured
cost of an actual cleanup. Agree the endpoint's scope and read-only versus explicitly
requested cleanup behavior before implementation. Reuse the owning cleanup processes;
do not introduce an IDE-only second cleanup algorithm or run cleanup implicitly as
a side effect of inspection. This records the requirement, not an implemented API.

## CH06-ITER-01 — mutation policy

Transferred here by user decision on 2026-10-05. Review structural mutation and
source replacement during value and reference iteration, including changes through
aliases/calls, visited entries/values, owner lifetime, copy-on-write and invalidation.
Compare performance and implementation costs. Chapter 06 retains the detailed
snapshot-on-first-structural-mutation alternative; it is a candidate, not an approved
implementation. In reference iteration, preserving a snapshot must not silently
lose the binding's write-through relationship to the intended source.

Agreed interim direction for reference foreach:

- The value variable is a loop-scoped binding to the current element, not an
  escaping native C++ reference. Updating an existing element is permitted.
- Reject unsafe structural mutation and source replacement at runtime before
  invalidation. Enforce the guard at the owning mutation/storage paths, including
  aliases and calls; release it on every loop exit, including exceptions.
- Coordinate copy-on-write and owner lifetime; checking only at the next iteration
  is insufficient if the current binding can be accessed earlier.
- No unconditional snapshot or reliance on undefined behavior is permitted.

This records an agreed implementation direction, not delivered runtime protection.
The safety guard and focused proofs remain prerequisites for enabling reference
foreach. Deferring the broader policy does not expand existing value-iteration
support beyond its bounded non-mutating contract.

Close this item only after an explicit policy decision, updates to the owning
normative specifications, required compiler/runtime work, and focused proofs for
normal/exceptional exits, alias mutation, source replacement and binding validity.

## CH06-REF-01 — reference foreach safety foundation

Deferred here by user decision on 2026-10-05 after inspection showed that safe
runtime rejection requires a cross-owner foundation, not a local foreach adapter.
Both `foreach ($items as &$value)` and `foreach ($items as $key => &$value)` remain
disabled in my-try. Do not enable parsing/emission as a partially safe feature or
substitute the legacy key-driven source-slot rewrite. This is no longer scheduled
as the next Chapter 06 implementation slice.

Keep the agreed target: a loop-scoped current-element binding, independent of the
visible key variable, using the iterator's known position without inherently
repeating key lookups. No native reference may escape. The interim guard direction
above remains approved, but unimplemented.

Inspection findings and affected owners:

- Vector/hash structural mutation and replacement lack a common active-iteration
  guard. Some clear/move operations are `noexcept`; throwing from them without
  reviewing their contracts would terminate rather than deliver a runtime error.
- The original inspection concerned the old shared-hash `dynamic_t` alias. The
  approved tagged dynamic refactor now owns its binding and shared container payload,
  but does not supply active-iteration guards. Recheck replacement at that owner;
  guarding table entries alone still cannot protect source-binding replacement.
- Mixed copy-on-write can replace table storage during an ordinary write. Preserve
  the intended source's write-through semantics and independent copies; an old
  table pointer or extra shared owner alone does not establish this guarantee.

Resume with a separately agreed runtime foundation covering owner lifetime,
structural mutation, wrapper replacement, aliases/calls, COW and exception cleanup.
Then integrate one prepared element-binding path across the supported families.
Validate direct runtime behavior first, then generated programs and retained
compiler preparation/recovery. Include unchanged state after rejected mutation,
normal copy/move behavior, key independence, binding non-escape and all loop exits.
Review affected `noexcept` and native-access boundaries explicitly.

No runtime/compiler implementation was changed by this deferral. Existing bounded
value foreach remains available; neither safe mutation nor reference iteration is
claimed by its existing tests. The separate CH06-MIX-DYN-01 core integration gate
is now resolved; see Chapter 06's positive-pass conclusion.

## Design considerations for final review

These items require review, not automatic implementation or an implicit promotion
to blocking feature debt. Preserve the distinction from the blocking inventory above.

### CH15-NATIVE-CACHE-PERF-01 — native cache validation overhead

Transferred by user request on 2026-10-06; optimization is deferred, not a reason to
weaken Chapter 08 invalidation/recovery proofs. The PHP-hosted cache on `f27de2e6`
took 1.88–1.92s for a no-op two-source build. Approximate measured costs: standalone
PCH validation 0.74s, four fresh full dependency scans 0.65s, dependency-list parsing
0.25s, input hashing 0.15s, and compiler-version query 0.07s. Hashing made 1,705
calls for 531 distinct paths. Direct PCH-backed compilation of the tiny function
still took 0.78–0.80s, plus about 0.24s linking. These are isolated observations,
not a universal performance guarantee or a native-compiler rebuild measurement.

Evidence: `/tmp/scpp-cache-investigation-20261006-1/` (temporary profiling only).
The earlier 0.88s compile/link observation in [performance findings](../planning/compile_performance.md)
excluded this cache-validation machinery and PCH construction.

Review redundant standalone validation, dependency reuse with sound change detection,
per-build digest deduplication and process-start costs in the native-build owner.
Preserve Clang's normal PCH checks, header-search/shadowing correctness, content-based
invalidation, reservations and failed-build recovery. Do not substitute timestamp-only
reuse or silently trust stale dependencies. Require measured cold/no-op/edit phases
and the Chapter 08 acceptance matrix before closing; broader toolchain/search and
parallel-action work retain their own scope. No optimization is implemented by deferral.

### Chapter 07 closeout debt — 2026-10-06

Transferred for final review by user approval; these do not require expanding the
bounded Chapter 07 implementation before Chapter 08. Existing restrictions remain
enforced until their respective contracts and proofs are agreed.

- **CH07-WARN-01 — advisory delivery:** establish a retained, non-fatal diagnostics
  owner and publication/retraction behavior for reference-return advice and relevant
  performance guidance. Coordinate with Chapter 14. Do not equate hard safety errors
  with delivered warnings or add emitter-only reporting as a substitute.
- **CH07-OWN-EXEC-01 — broader ownership operations:** review ownership selection and
  variadic transfers, broader const forms and precise additional target constraints.
  Constructor arguments remain with Chapter 10; member lifetime work belongs with
  Chapters 09/10 and their established type models. This entry tracks final review,
  not a competing implementation owner. Low-level pointer escapes, specialized
  deleters and intentional direct ownership nesting restrictions are not enabled.
- **CH07-REF-EXT-01 — broader reference returns:** review different origins, recursive
  reference returns, registered runtime reference returns and loop-header aliases.
  Same-origin multiple returns and control-flow body aliases are already implemented,
  not outstanding work. Require a truthful lifetime contract before widening origin
  admission; container-interior safety and disabled reference foreach remain unchanged.

Close each item by an explicit supported/deferred/excluded disposition, updated
owning contracts where needed and focused behavioral/lifetime/recovery evidence.
The inherited-limit review below should link these items rather than duplicate them.

### CH15-S2S-LIMITS-01 — review inherited S2S limitations

Requested on 2026-10-06: review all old S2S limitations against the capabilities of
the v0.2 semantic compiler. Do not preserve a restriction solely because the old
type-blind generator could not resolve types, storage origins or control flow.
This is a catalog-wide review debt, not approval to enable every excluded feature.

Inventory restrictions and workarounds from the imported catalog cards, legacy
generator rules/diagnostics/tests and relevant top-level contracts. For each, record
its source, original reason, current semantic owner, prerequisites and disposition:
already solved with proof, safely removable in a bounded slice, still a deliberate
language/safety rule, or deferred with an explicit blocker. Link existing debts
rather than duplicating their implementation queues.

Candidates include conditional/loop alias restrictions (body-local aliases are
already proved in Chapter 07), single-final-return restrictions, type-blind lowering
workarounds, delayed C++ diagnostics and avoidable runtime checks. Preserve runtime
checks for genuinely value-dependent failures; compile-time knowledge does not
remove their necessity. Native interior-reference safety, family separation and
intentional ownership restrictions are not automatically obsolete.

Complete the review with a traceable disposition ledger and agreed follow-up slices.
Any changed language contract needs explicit approval and an owning-spec update;
implementation needs focused accepted/rejected, lifetime and incremental-recovery
proofs where relevant. Record generated-program and native-compiler evidence
separately. Reviewing a limitation does not itself resolve it or enable its syntax.

### CH15-CALLABLE-01 — callable values in mixed/dynamic entries

Recorded at the user's request on 2026-10-05. Consider function/closure values in
both families, for example `{my_func: function (): int { return 1; }}`. Implementation
belongs with [Chapter 12](12_closures.md), not the current mixed layout/dynamic refactor.
The [storage discussion](../planning/mixed_dynamic_storage.md) should leave room for
this extension without implementing an unused callable kind or placeholder execution.

Candidate direction, to validate with the callable model:

- One callable concept for locals, arguments, returns and entries: code entry,
  signature (parameter types, passing modes and return type), and optional captured
  environment. A shared handle may preserve closure identity and environment lifetime;
  capture-free functions need no captured environment.
- Both value families may admit the same callable category without admitting each
  other's container payloads. Storing a callable as a property does not implicitly
  bind a receiver or `$this`; copying it out preserves its invocation meaning.
  Explicit receiver-bound methods would be a separate callable form.
- Consider treating captures as private callable state, not entries in the enclosing
  data tree. Decide whether captures may include either family, while preserving
  ownership, reference-escape rules and cycle handling. This is not yet a settled
  exception to family separation.
- Known signatures are validated during preparation; runtime-selected callables
  need kind/signature/argument checks before invocation. Share existing call contracts
  where truthful rather than adding container-specific invocation semantics.
- Target a callable handle within the 16-byte payload budget so tagged values may
  retain the proposed 24-byte layout; measure before claiming this is achieved.
  Reuse scalar/callable support without treating callbacks as JSON values.
- JSON decoding does not construct functions. Proposed encoding behavior is explicit
  failure for callable payloads; reconcile with the JSON contract when enabling them.

Review proofs should cover storing/extracting/copying/invoking callables in both
families, preserved capture state and lifetime, absent/non-callable entries,
signature mismatch, receiver independence, reference non-escape and encoding failure.

### CH15-SHAPE-01 — preserving array versus object identity

Deferred by user decision on 2026-10-05. For the mixed/dynamic refactor, continue
classifying container shape through the internal hash query (currently
`hash_t::is_packed()`, called `is_pure_array` in discussion). Do not introduce a
separate persistent array/object identity tag in this slice.

Later review whether construction/JSON decoding must retain `[]` versus `{}`
independently of the current keys, especially for empty containers. Current shape
classification must not be advertised as lossless preservation of original notation.
Review numeric-key objects, mutations that change packed shape, JSON round-trips,
property access and any storage/ABI cost before choosing a retained representation.
This is a deferred design consideration, not a prerequisite for the agreed refactor.

### CH15-JSON-01 — optional string-producing `to_json` alias

The user approved removing the misleading mixed-tree-producing runtime `to_json`
helper on 2026-10-05. `json_encode` / `scpp::json::encode` remain the checked
string-producing API, using direct traversal rather than intermediate boxed trees.
Later decide whether a `to_json` alias is useful. If introduced, it must produce
`result<string>` through the same writer, not construct a mixed/dynamic tree or
silently bypass checked failures. This naming debt does not block Chapter 06 or
authorize restoring the removed helper.

### CH15-LIB-DYN-01 — deferred library mixed/dynamic integration

User direction on 2026-10-05: do not delay the core mixed/dynamic migration for
library integration issues. Record affected APIs here; do not restore cross-family
boxing or claim deferred libraries work with the new types. Keep base-runtime
compilation independent of deferred optional-library integration where needed.

Review library-owned models before mechanically substituting dynamic for mixed.
The user identified hurried generic boxed APIs: use specific structures/classes for
known library records, scheduling state and results where appropriate. Reserve
mixed/dynamic for genuinely runtime-shaped user data. This design review is deferred,
not authority to redesign every library during the core runtime migration.

Tasks decisions already agreed:

- Boxed task results and `task_join` use the new dynamic family, not mixed.
- Workers may receive dynamic containers with shared identity. This does not imply
  automatic locking or make simultaneous unsynchronized mutation safe.
- Complete the worker-transfer/synchronization contract and enforcement separately;
  test shared reads, synchronized mutation, alias lifetime and result publication.

The attempted integration found that `hash<dynamic, mixed>` is rejected by typed
key admission; only `hash<mixed, mixed>` currently wraps the shared packed/associative
engine for mixed int/string keys. Resolve this through a reusable wrapper or a
task-owned scheduling representation, not by converting dynamic values into mixed.
Keep source key-domain support separate from private scheduler storage capability.

Task batch storage, result boxing/join, dynamic input scheduling, error/cancellation
paths, library registrations and disabled-module diagnostics must migrate together.
The compile-blocked task attempt was removed from the active tree; it is not a
completed feature. Existing task code still carries its old mixed assumptions until
this debt is handled. Existing JSON/MySQL runtime checkpoints remain implemented;
JSON's boxed source APIs are now registered in my-try; MySQL source registration
and remaining library consumer integration still need review.

### CH15-DYNAMIC-PROP-01 — dynamic property access

Deferred to Chapter 15 by user direction on 2026-10-05. Keep dynamic `->` disabled
while completing core family and JSON integration. Discuss fixed/computed property
names, reads/writes/probes/removal and eventual callable members before admission.
Reuse existing prepared place/access and runtime container machinery where truthful;
do not create a second mixed/dynamic traversal or revive cross-family payloads.

Resolve missing properties, null/scalar receivers, sequencing, shared alias effects
and the relation to CH15-SHAPE-01. Callable values do not automatically imply method
binding; coordinate with CH15-CALLABLE-01. Completion requires an agreed normative
contract plus preparation, generated-program and failure-state proofs. This debt
does not defer ordinary typed class/struct member work in its owning chapter.
