# Accepted portability backlog execution
Doc Status: planning

## Authorization and baseline

User authorized implementation on 2026-10-23 in the implementation review order,
with reuse analysis per entry, measured bootstrap checkpoints and discussion before
new semantics or wide redesigns. Continue independent work when one item is blocked.
The tracked goal is active. Deferred debts remain deferred.

Starting HEAD: `394e6edc08e184c4b74ab7df54335c809e1e5619` on `v0.2/develop`.
Existing workspace changes are planning documents and Chapter 15 debt/review updates;
preserve them. No source implementation changed at goal startup.

## Stage 0: registry discovery and baseline

Reuse: Language_Types installs registry exposures; Type_Queries separates semantic
record identity/storage and Capability_Facts consumes completed source facts. Existing
initialization/indexing/iteration registries and observed preparation dependencies
remain owners. Do not create a second type registry or capability cache.

Initial source-located findings (discovery incomplete):

| Location | Classification | Owner / next disposition |
| --- | --- | --- |
| compiler/my-try/compiler/types/language.php:10 | Registered exposure installation | Keep; existing registry owner. |
| compiler/my-try/compiler/types/language.php:18 | Intrinsic literal identities via registered lookup | Keep intrinsic policy; no arbitrary library allowlist. |
| compiler/my-try/04_analyze/prepare/semantics/types.php:58 | Intrinsic variadic vector ABI | Keep documented language role; not blanket container admission. |
| compiler/my-try/04_analyze/prepare/semantics/types.php:186 | Named vector/hash callable-payload gate | Audit capability requirements; TASK-01/registry owners, no automatic broadening. |
| compiler/my-try/04_analyze/prepare/semantics/object_construction.php:131 | Named vector/hash default-construction gate | Trace registered construction capabilities during CONVERT-01; do not conflate new and typed []. |
| compiler/my-try/04_analyze/prepare/semantics/cleanup.php:70 | Named vector/hash clean gate | Needs cleanup protocol audit; no inference from copyability/indexing. |
| compiler/my-try/04_analyze/prepare/semantics/value_probes.php:68 | Named vector/hash emptiness gate | Needs emptiness protocol audit; preserve null/empty distinctions. |
| compiler/my-try/04_analyze/prepare/semantics/types.php:257 | Registered Iterator identity | Inspect intrinsic protocol role before disposition. |
| compiler/my-try/compiler/types/README.md:5 | Stale guide: claims hardcoded installation and deferred JSON ingestion | DOC-01 reconciliation; code already installs exposures. |

This is not the full CH15 inventory and does not close its blocking debt. Expand
across formation, conversions, ownership, member lookup, retained keys, ordinary
and prelowered C++ consumers, and parked LLVM (inventory only).

Host prerequisite check passed with `verification_host.py --stages baseline frontend
native --results /tmp/compiler-backlog-host-20261023`. Record result before selecting
runner/PHP executable. Historical bootstrap measurements are not current baselines.

Next: finish host check and current conversion baseline, expand registry inventory,
then CONVERT-01 using its completed reuse audit. Bootstrap checkpoints record command,
revision/overlay, elapsed time, peak RSS, exit stage and whether output was produced.

## CONVERT-01 first implementation slice

Reuse confirmed: authored contextual [] defaults reuse Initialization_Preparation,
Property_Defaults and prepared_property_parameter_default, including existing
call-local omitted argument emission. No new retained structure or default scheduler.
Nonempty/arbitrary defaults and container references remain excluded.

The converter accepts explicit annotated array [] defaults in methods/constructors.
Cross-route proof exposed a legacy generator bug: renderParams discarded the local
parameter type for an empty initializer and emitted mixed. A common parameter-default
renderer now reuses typed array initialization for empty vector/hash defaults across
its three declaration renderers. This is a local structural lowering correction,
not a new semantic analysis stage or historic-baseline support claim.

Evidence:
- `/tmp/compiler-conversion-baseline-20261023`: 255 actual compiler files scanned;
  254 converted, cpp_commands.php:62 rejected its empty method default (4.45 s runner
  task; no full-bootstrap measurement). Diagnostic projection was not built.
- `/tmp/compiler-empty-defaults-01`: ordinary and concrete native executions pass
  (17.65 / 20.87 s task times); retained rejection/recovery passes. These are fixture
  builds, not bootstrap times or a native compiler rebuild.
- `/tmp/portable-empty-defaults-03`: PHP/current legacy route passes (18.10 s),
  ordinary/concrete native output passes (17.19 / 19.96 s). Earlier attempt 01 was a
  fixture missing PHP helper bootstrap; attempt 02 established the real legacy bug.
- FPM cannot bind sockets in this sandbox; shared runner falls back to PHP CLI.

Measured self-bootstrap harness added as tools/bootstrap_probe.py and .php under
compiler/my-try. It snapshots the existing seven-stage/four-driver envelope, records
source hashes and tracked overlay, assembles the framework, and uses production
Project_Compilation/Compiler stages. `/usr/bin/time -v` records conversion/bootstrap
separately; PHP records peak heap and the last stage. No native build is performed.
First checkpoint `/tmp/compiler-bootstrap-backlog-01` completed: conversion 4.67 s,
193,736 KiB RSS (189.2 MiB); 269 assembled PHS files / 1,958,956 bytes. Bootstrap
193.79 s, 441,864 KiB RSS (431.5 MiB), 422.5 MiB PHP heap; initialization 0.299 s,
sync/parse 4.081 s. Failed in preparation; no OOM/fatal, no C++ emission. First
message is the known AST-01 nonvirtual inherited-member redeclaration. The aggregate
also contains previously inventoried self, ownership, initialization and binding
failures. No new semantics are inferred from these diagnostics; continue accepted work.

CONVERT-01 remains in progress: generic provider coverage/integration, standalone
empty-expression lowering and broader retained/default effects still need proofs.
CH15 discovery remains incomplete and blocking for final closure.

Generic provider proof `/tmp/compiler-empty-defaults-02` passes all five tasks,
including ordinary/concrete tray<int> with counted registered factories. Omission
invokes the factory once each time; explicit arguments do not evaluate the default.
Nullable/handle/property-default regression checks pass in
`/tmp/empty-defaults-regressions-01`. Broad converter checker stops on its stale
nullable-interface-return rejection expectation. The unmodified HEAD converter
accepts that exact source too (`/tmp/empty-default-baseline-tools/logs`); this is
pre-existing test drift, not a new default regression. Keep it visible for DOC-01 /
verification reconciliation rather than weakening unrelated expectations here.

## CONVERT-01 empty-expression slice

Reuse analysis: the converter already recognizes standalone container annotations
and represents generic type spelling structurally. Source/index caches do not own
emission policy; the CLI conversion fingerprint does. Added an explicit target to
the existing Converter emitter and both CLI tools. Default remains v0.1; v0.2 emits
Type::[] rather than new Type() for annotated empty expressions. Target switches
invalidate output fingerprints, while token caches remain shared. Bootstrap selects
v0.2 explicitly. No compiler semantic changes are needed for this route distinction.

The sequence parser now distinguishes an empty literal from an index/append slot,
and carries explicit local-array intent only into directly nested literal groups.
Standalone untyped empties reject rather than infer receivers/callees. Typed local,
field and parameter contexts avoid redundant comments. Both-target structural
proofs pass; all 255 compiler files convert after adding missing call/reset comments
and replacing two empty-array comparisons with count checks. Parked LLVM changes
are annotations only because those files remain in the bootstrap envelope.

`/tmp/portable-empty-targets-01` passes PHP/current legacy/v0.2 ordinary/concrete
execution plus target switching/cache reuse/non-publication checks. The extended
annotation rejection/admission proof and all-source conversion inventory pass in
`/tmp/empty-expression-checks-02`. Final cross-route rerun after stricter untyped
rejection passes in `/tmp/portable-empty-targets-02` (PHP/legacy and both v0.2 modes).

Source model/runtime-catalog review: runtime_catalog passes with its required result
folder. type_catalog's fixed C++ binding count and initialization's hash<bool>-key
rejection fail identically on the unmodified 396e57e0d snapshot; baseline replay is
in `/tmp/empty-expression-regressions-02` (expected failures, not passing suites).
These stale expectations remain verification reconciliation work. The first
runtime_catalog invocation omitted its required folder; it was corrected and rerun.

Registry discovery now has a source-located candidate inventory and an initial
named-lookup review in compiler_registry_audit_2026_10_23.md. It identifies transfer,
provider transport and optimization-policy gates beyond the historical four cases;
CH15 remains open. These are findings to repair by owner, not blanket permission to
broaden operations on every registered type.

Second isolated measured checkpoint `/tmp/compiler-bootstrap-backlog-02`: conversion
4.69 s / 189.2 MiB RSS; 269 PHS files / 1,959,128 bytes. Bootstrap 197.22 s /
432.1 MiB RSS, 422.5 MiB PHP heap, still preparation-blocked first on AST-01.
No OOM/fatal or emission. The aggregate `S2S new requires a source struct or class`
diagnostic disappears after target-correct empty expression emission; no new message
text appears. Saved evidence is under results/compiler_bootstrap_backlog_2026_10_23/
empty_expressions. This is not completion of all CONVERT-01 provider/default proofs.

## CONVERT-01 empty-only binding design

The user selected an explicit empty-only binding using existing default construction;
nonempty literals remain excluded. The [concrete bounded design](compiler_empty_only_binding_2026_10_23.md)
separates this registration from sequence-input recipes and reuses runtime factory
call preparation/emission, including template prelowering. One prepared-data variant
retains the resolved construction. The implementation now registers
`empty_initialization: "default"`, shares runtime construction-call preparation
with `new`, and delegates settled emission to the same call path in both C++ modes.
Storage, Keyed_Storage and Key_Storage_List opt in explicitly. Sequence recipes and
empty-only registration cannot coexist; nonempty inputs require a sequence binding.

`/tmp/empty-provider-03` passes nine tasks: unfamiliar generic-provider and three
Storage-family native executions in both modes, invalid source/recovery, missing
opt-in, missing factory, conflicting recipes and unusable zero-argument construction.
The provider counter establishes one factory call per omitted argument and none
for an explicit argument. The Storage default proves independent initial state.
`/tmp/empty-defaults-regression-03` passes all five existing sequence-default tasks.
These tests ran concurrently, so their task times are not bootstrap measurements.
Both used the shared runner's CLI fallback after sandbox FPM socket binding failed.
First attempts exposed fixture mistakes (configuration after reset and an absent
Key_Storage_List::count method), then a too-generic rejection diagnostic; all were
corrected before the final pass. No claim of native compiler rebuilding follows.

Measured checkpoint `/tmp/compiler-bootstrap-empty-provider-03`: conversion 4.60 s,
195,060 KiB RSS; 269 PHS files / 1,962,586 bytes. Bootstrap 198.90 s,
442,724 KiB RSS (432.3 MiB), 422.5 MiB PHP heap. Preparation stops on the same
AST-01 diagnostic; no added/removed diagnostic text relative to empty_expressions,
no fatal/OOM and no emission. Saved results are under
results/compiler_bootstrap_backlog_2026_10_23/empty_provider. This closes the bounded
empty-only integration slice, not the whole backlog or remaining CONVERT-01 edges.

## CONVERT-02 reuse before implementation

The recursive Container_Type parser already owns explicit vector/hash payload
spelling. Array carrier validation belongs in the converter's containerAnnotation
helper, reused by method, constructor and return parsing. Constructor parsing
currently wraps a payload-only comment based on PHP's question mark; ordinary
method parameters reject nullable arrays earlier in signatureType. The bounded
change should parse one complete annotated target, compare its outer nullability
with the PHP carrier, and emit one existing nullable wrapper. Migrate actual
template_type_parameter constructor comments and matching fixtures. Keep nullable
formation/operations in NULL-01/02; annotation acceptance does not prove native
nullable-container execution. No additional registry or compiler protocol is needed
for this source/converter convention change.

## CONVERT-02 implementation and evidence

The converter now shares complete-array-annotation checking across properties,
ordinary/promoted constructors, method parameters and returns. `?array` requires
`?vector` or `?hash`; nonnullable `array` rejects a nullable comment. Existing
recursive payload parsing is reused, producing one nullable wrapper. Container
interface signatures remain rejected, including nullable returns. The compiler's
two template_type_parameter comments and current guides/fixtures are migrated.

`/tmp/nullable-annotations-03` passes both-target declaration/mismatch checks and
PHP/current-legacy native execution for omission, absence, present empty arrays,
constructor input, ordinary-method input and return. Both v0.2 modes reproduce
`S2S nullable value boundary requires a scalar or inline record payload`; this is
explicit pending NULL-01/02 execution coverage, not a passing native behavior claim.
The first fixture attempt missed project initialization; it was corrected before
the passing execution. No compiler nullable semantics were changed for the converter.

`/tmp/nullable-conversion-scan-02` converts all 255 production compiler files plus
four driver/proof files for both targets. Existing empty-annotation and nullable
field rejection/non-publication checks pass. All 259 v0.2 PHS outputs match the
last measured bootstrap input except one previously committed explanatory comment
in prepare/data/structures; the exact comment replacement was checked. No emitted
compiler code changes. Therefore no new full-bootstrap timing is claimed: the last
measured checkpoint remains 198.90 s / 432.3 MiB RSS / 422.5 MiB PHP heap and AST-01.
The initial byte-equality assertion correctly exposed that comment-only difference;
the final comparison records it explicitly rather than silently ignoring all comments.

CONVERT-02 spelling/consistency work is complete. Its native v0.2 execution proof
must be upgraded when NULL-01/02 remove the payload restriction. INIT-01 is next;
CONVERT-01's remaining retained/provider edge verification stays on the final gate.

## INIT-01 reuse and bounded implementation

Reuse: constructorDeclaration already has explicit promotion association, property
types, defaults and body nodes. Native Constructor_Preparation already resolves
`set:` against existing properties and owns default precedence, base ordering and
initialization before the body. Legacy Generator::extractParentConstructorArgs only
recognizes an explicit base call as the first body statement. No new retained model
or core syntax is needed for ordinary promotion mapping.

The converter now emits separate properties plus `set:` parameters for v0.2;
the legacy target retains typed parameters plus assignments. Actual switch_label
uses PHP promotion for its required label_kind, with no fabricated enum default.
Arbitrary body assignments are not rewritten. Root-class enum/default/private-field,
mixed ordinary/promoted parameter and effect-order proofs pass PHP and legacy native
plus both v0.2 modes in `/tmp/constructor-promotion-06`. The separate checked-arithmetic
failure proof also passes PHP and all three native modes: the initialized enum and
explicit property default are observed before failure, and no later body/caller
effect executes. This proves the bounded slice, not the open parent combination.

Open parent combination: `/tmp/constructor-promotion-02` proves the legacy native
failure when promotion inserts assignments before an explicit parent call. PHP
passes. Actual async_task_type_definition at 03_parse/types/model.php:345 has this
shape. Reordering could alter child-state observations, so the user has been asked
to choose source adaptation or a bounded no-child-observation contract. No reorder
has been implemented; this combination remains open while independent work proceeds.

The first fixture had a reserved C++ local name, corrected before behavioral proof.
Attempts 03/04 exposed unrelated framework admission: removing unused string facade
inputs left the exception facade's shared-identity helper failure. Focused probes
in `/tmp/promotion-diagnostic-01` through `03` show ordinary/set integer arithmetic
and exception classes prepare, but the free same_exception shared-object comparison
hits integer-operator admission. Preserve this evidence under NULL-01/OWN-01 and
BIND-03; do not repair identity semantics as constructor/converter work.

`/tmp/promotion-empty-defaults-01` passes PHP/legacy and both v0.2 modes with promoted
typed container defaults, including fresh omission and explicit arguments. The
constructor fixture was consolidated by removing an unused parent declaration;
the failing explicit-parent case remains recorded above, not claimed as passed.

Measured `/tmp/compiler-bootstrap-promotion-01`: conversion 4.66 s / 194,972 KiB RSS;
269 PHS files / 1,956,338 bytes. PHP-hosted bootstrap 190.83 s / 440,544 KiB RSS
(430.2 MiB), 420.5 MiB heap. Seven property-requires-set diagnostic texts disappear,
including label_kind; none are added. AST-01 remains first. No fatal/OOM, emission
or native compiler rebuild. Evidence: results/compiler_bootstrap_backlog_2026_10_23/
constructor_promotion. INIT-01 remains open for the parent combination; this is a
validated bounded slice, not backlog completion.

## BIND-01 next independent reuse slice

Eleven existing leaves in runtime/include/modules/filesystem/output_files.hpp map
to the facade's scpp_output_* and scpp_input_digest calls. Legacy signatures are
already explicit in RuntimeShallowSourceGenerator's strict inventory. Register
matching signatures and C++ symbols in filesystem.json; no source inference,
new ownership type or cleanup protocol is needed for these leaves. Preserve
result<nullable<string>> for output_read and direct string for output_sha256;
absence, failure and unchanged are separate outcomes. Existing result extraction
and prelowered runtime-call transport should carry them. Prove those outcomes and
staged promotion/close using existing runtime behavior before claiming BIND-01
closure. This work is independent of the open constructor-parent convention.

## BIND-01 output-file registration slice

Filesystem metadata now registers all eleven existing leaves with exact signatures
and existing C++ symbols. No runtime behavior, facade cleanup, core result semantics
or compiler dispatch code was changed. `/tmp/output-bindings-02` executes all eleven
on legacy and ten on ordinary/prelowered v0.2 output, including lock-held mutations,
empty/unchanged/failure distinctions, digest, permissions, promotion and discard.
The first legacy fixture used top-level integer returns, which its entry wrapper
does not transport; assertions now live in an ordinary typed function.

The read leaf remains a real dependent blocker: result<nullable<string>> extraction
fails Result_Conversions::require_payload because nullable lacks result_payload.
The exact return type remains registered. Two focused tests reproduce the error;
they are not positive read-execution proofs and must be upgraded with NULL-02.
No string sentinel, nullable-specific name branch or unconditional capability
expansion was added to bypass this dependency.

`/tmp/output-adapter-reuse-01` reruns the existing PHP adapter fixture under an
exclusive lock, covering missing/empty files, binary contents, edited-output refusal,
path protections, SHA-256 boundary sizes and Output_Stage alias/close behavior.
This plus the new direct binding tests does not claim full converted facade admission:
the separately recorded shared-identity helper and other BIND-03 surfaces remain.

Measured `/tmp/compiler-bootstrap-output-bindings-01`: conversion 4.65 s /
194,992 KiB RSS; PHP-hosted bootstrap 191.17 s / 441,568 KiB RSS (431.2 MiB),
422.5 MiB heap. All eleven unresolved output-leaf diagnostic texts disappear;
the expected result_payload capability diagnostic is added. AST-01 remains first.
No fatal/OOM, emission or native compiler rebuild. Evidence is saved under
results/compiler_bootstrap_backlog_2026_10_23/output_bindings. BIND-01 remains open:
read execution depends on NULL-02, JSON factory/method exposure is next, and facade
dependencies still require BIND-03 tracing.

## BIND-01 JSON reader reuse before implementation

The native json_pull_reader in modules/json/pull_reader.hpp already owns a unique
parser and start-once state. Its fourteen methods return existing scalar Result
types; the parser owns input bytes. Reuse these methods unchanged. Add a free
json_pull_reader_create() allocation adapter returning a fresh shared wrapper,
register the shared provider type and methods in json.json, and replace only the
facade allocation expression. Legacy class-method stubs already exist in
RuntimeShallowSourceGenerator; the new free function needs legacy symbol/contract
exposure too. Test unstarted/restarted errors, alias state, input ownership and
startup/protocol failures on both routes and prelowering. Do not mark the underlying
unique parser copyable or enable generic shared-provider `new`.

## BIND-01 JSON reader factory and exposure slice

Added the accepted fresh shared factory and fourteen existing instance bindings;
legacy symbols/contracts and the shallow free-function declaration match the
runtime's shared_p boundary. The facade uses the factory and keeps its start/Result
translation unchanged. Parser ownership and failure semantics are unchanged.

`/tmp/json-reader-bindings-02` executes all methods on legacy (19.78 s) and ordinary
v0.2 (23.23 s): shared alias advancement, independent factories, owned input,
before-start errors, failed-start/restart rejection, scalar values, nested traversal,
consumer rejection and terminal protocol errors. `/tmp/json-reader-facade-01` passes
the existing PHP/native consumer, native reader protocol and document regression.
The FPM socket is unavailable in this sandbox; the shared runner uses CLI fallback.

Concrete prelowering is explicitly **blocked**, not executed: the same full fixture
reaches CPP_Concrete_Members::entry's existing rejection of shared runtime members
with arguments. Ordinary calls already retain/check the receiver before evaluating
arguments. Concrete provider handles deliberately expose no payload pointer in
application headers (CPP_Types::application_pointee), so removing that rejection
would lose the early check and currently discard method arguments. A follow-up
must reuse selected_call argument sequencing and add a backend-owned check bridge
before arguments, keeping provider payloads hidden. This is missing backend support,
not a reason to change the factory, copy the parser, expose its payload or grant
new source ownership semantics. Reference/variadic runtime member transport remains
outside this reader slice. BIND-01 remains open for this bridge and facade tracing.

Measured bootstrap `/tmp/compiler-bootstrap-json-reader-01`: conversion 4.73 s /
194,804 KiB RSS; bootstrap 200.67 s / 455,508 KiB (444.8 MiB) RSS / 434.5 MiB
PHP heap. The unresolved reader type diagnostic disappears; none are added.
First blocker remains AST-01, with no fatal/OOM, emission or native compiler rebuild.
Evidence: results/compiler_bootstrap_backlog_2026_10_23/json_reader.

## BIND-01 concrete shared runtime receivers — reuse and repair

Reuse audit: prepared_method_receiver already records retain_shared;
CPP_Declarations::selected_call already sequences its prefix before snapshots;
CPP_Opaque_Representation already owns native storage and unwrap; and
CPP_Concrete_Members already maps parameters and Result transport. A check-only
free overload on the opaque representation is sufficient. No new retained records,
source contract, callback/ownership rules or provider names are needed.

The representation now emits require_receiver(const concrete_handle&) for runtime
nominal shared handles, delegating to checked native operator-> without exposing
its pointer. Call lowering retains and checks before arguments in both modes.
The member adapter forwards existing prepared arguments instead of rejecting shared
calls with parameters. Reference results, variadics and borrowed-this arguments
remain outside this slice; mutable provider write-back is not generalized.

`/tmp/json-reader-prelower-01` passes legacy, ordinary and concrete execution of
all fourteen reader methods. `/tmp/runtime-member-prelower-02` passes six unrelated
provider cases: null-check-before-side-effects, receiver replacement during argument
evaluation, generic scalar calls and observed shared inputs, in both v0.2 modes.
Earlier failed fixture attempts used unsupported explicit member template syntax
and expected a diagnostic code not printed by the native entry; those fixture
expectations were corrected to supported inferred calls and the actual error message.
The former JSON concrete-blocker test is now positive execution.

The existing runtime_handles.py suite also passes (`/tmp/runtime-handles-receiver-01`):
four preparation/metadata suites and seven native handle/identity/property/member/
Result/container checks. No provider-specific compiler conditions were added.

## BIND-03 next reuse finding (source audit, not yet a behavioral proof)

The converter function-map branch in src/converter.php emits the selected target
name verbatim. Map entries for scpp_portability_strlen/output_root/process_spawn
and their siblings name ordinary global source facade functions, unlike runtime
registry leaves. Converted compiler consumers live in scpp\compiler and currently
call these names without a root qualifier. specs/namespaces_v0_2.md explicitly
forbids fallback to unrelated global user functions; Observed_Lookup::functions
correctly follows namespace scopes. The bootstrap's unresolved-function error is
raised on an empty lookup set, before callable argument checking. This identifies
a converter target-qualification candidate, not a need to duplicate facade functions
in binding metadata or change native namespace semantics. Next: prove a rooted
mapped call from a namespace on both routes and v0.2 modes, verify converter cache
invalidation, then separately trace the facades' known body/capability dependencies.

Measured receiver checkpoint `/tmp/compiler-bootstrap-reader-prelower-01`:
conversion 4.73 s; bootstrap 199.64 s / 455,704 KiB (445.0 MiB) peak RSS /
434.5 MiB PHP heap. Same diagnostic texts, AST-01 first, no fatal/OOM or emission.
Evidence: results/compiler_bootstrap_backlog_2026_10_23/reader_prelowering.

## BIND-03 mapped source facade qualification

Reuse analysis confirmed that map targets already accept rooted names and the
conversion cache fingerprints serialized map contents. Rooted all 34 global source
facade target spellings in function_map.php, including the two-argument substring
overload. Registered builtin target spellings remain governed by their existing
mapping. No symbol inference, namespace fallback or duplicate binding was added.

The shadowing proof also exposed a legacy emitter bug: renderNameExpr discarded
source-root qualification, allowing C++ lookup to see a same-name namespace member.
It now renders explicitly rooted source calls under ::scpp. This uses the existing
AST name flag and target namespace convention; no new language semantics.

The focused fixture checks PHP results, both conversion targets, prior-map cache
invalidation, warm reuse, and legacy/ordinary/prelowered native behavior with a
same-name namespace shadow. Its three small helper definitions isolate lookup;
it does not claim full framework admission. The existing string_split.py suite
passes full PHP/native conversion including real rooted byte-construction facade
calls (`/tmp/facade-string-regression-01`). Early lookup fixture failures included
unsupported uppercase portable namespace/free-function authoring and source includes;
these were corrected to ordinary project composition and native test declarations.
The missing string operations in that fixture were caused by selecting core alone;
they exist in strings metadata, and the final fixture enables that module.

Final focused lookup evidence: `/tmp/facade-qualification-08`, all routes and cache
checks pass. v0.2 explicitly loads strings metadata in this low-level fixture;
production Project_Compilation already includes strings automatically. Legacy
exposes these operations through its core surface, not a strings project module.
The helper bodies call the real string operations, with a same-name namespace
shadow proving that the rooted target wins. No string metadata changes were needed.

Measured `/tmp/compiler-bootstrap-facade-root-01`: conversion 4.60 s / 195,040 KiB
RSS; bootstrap 214.69 s / 464,688 KiB (453.8 MiB) RSS / 442.5 MiB PHP heap.
All fourteen unresolved scpp_portability function diagnostic texts disappear;
none are added. AST-01 remains first. No fatal/OOM, emission or native compiler
rebuild. Evidence: results/compiler_bootstrap_backlog_2026_10_23/facade_qualification.
B03 lookup classification is resolved; facade body failures remain their own
OWN/NULL dependencies, rather than duplicate runtime registrations.

## SOURCE-01 private instance fields — reuse and implementation

Reuse audit: Inherited_Lookup selects the nearest declaration independently of access;
Expression_Preparation resolves against the receiver's static payload type and then
checks the original declaring owner's access; prepared_field retains declaration
identity; record layouts and constructor set inputs are per owner. Base layout
preparation alone had a blanket inherited-name rejection. Legacy C++ already keeps
base/child storage separate. No field flattening, new lookup mode or converter-aware
semantics are needed.

Inheritance_Preparation now admits a collision only when both selected declarations
are private instance properties. It observes the original ancestor declaration before
reading prepared visibility/storage. Static properties, public/protected hiding,
method and constant collision rules are unchanged. Existing class-to-class and
struct-to-struct layouts share this rule. The normative inheritance contract now
states lookup-before-access, no base retry, independent storage and explicit portable
base views. This is the accepted source-admission extension, not a historical v0.1
bug claim.

`/tmp/private-fields-portable-01` passes host PHP, legacy native, ordinary v0.2 and
prelowered v0.2: a three-level class chain, separate constructor values/mutation and
a parent method accessing a child through an explicit base-typed local.
`/tmp/private-fields-native-01` passes independent struct constructor set inputs with
different field types in both modes, plus nine rejection cases and repeated
rejection/recovery in both modes. Parent access through a child-typed receiver rejects
the selected child's private field; inaccessible access never falls back to the base.
Public/protected/static exclusions remain checked. Compiler source position fields
in children_iterator and pair_children_iterator can now retain their authored names.
Algorithm-specific uint32 checks remain a separate SOURCE-01 numeric audit; no
mechanical checks or numeric policy changes were added.

Adjacent regression `/tmp/private-fields-inheritance-01`: all 59 generated inheritance
programs pass, including retained constructor/default/body edits and access exclusions.

Cross-file retained proof `/tmp/private-fields-retained-03` also passes in both modes:
editing only an ancestor from private to public invalidates an unchanged descendant;
restoring private recovers identical output. The first fixture attempt lacked explicit
multi-file entry selection; the final one selects main.phs.

Measured `/tmp/compiler-bootstrap-private-fields-01`: conversion 4.64 s / 195,012 KiB
RSS; bootstrap 210.63 s / 464,404 KiB (453.5 MiB) RSS / 442.5 MiB PHP heap.
The inherited position-field diagnostic disappears; none are added. AST-01 stays
first, no fatal/OOM, emission or native compiler rebuild. Evidence:
results/compiler_bootstrap_backlog_2026_10_23/private_fields.

## AST-01 — explicit virtual defaults: reuse and implementation

Reuse audit: prepared source callable signatures own modifier intent; Type Contract
Preparation already owns virtual slots, inherited/default bodies, final checks,
interface bridges and parent-call selection. CPP_Dispatch already emits nonabstract
slot bodies. This slice adds explicit Virtual signature intent, includes it in
retained signature comparisons, and feeds the existing contract decision. No emitter,
new template protocol or generic source-method model is needed.

The converter carries rooted zero-argument Virtual/Override attributes through
ordinary methods and direct traits, with local structural restrictions only. The
same marker scanner participates in declaration indexing and both cache fingerprints;
PHP's framework supplies a method-targeted Virtual marker. PHP inheritance remains
PHP's responsibility and native signature/slot checking remains compiler-owned.

A host reflection audit found six converted AST roots needing markers: ast_node's
four occurrence/preparation hooks, expression_node::require_preparation and
loop_node::body_runs_first. They are now marked. Storage_Abstract::public_key also
has a concrete override in host PHP, but is runtime-bound rather than converted;
it does not need a new generated source slot. Existing interface/abstract contracts
and unrelated methods are unchanged.

Core proofs pass in both output modes: inherited default invocation, base/interface
dispatch, direct trait implementation, final override, explicit parent invocation
and vector parameter/result transport. Retained proof removes only the ancestor's
marker, rejects the unchanged descendant and recovers identical output when restored.
Focused rejection/converter regression and measured bootstrap evidence follow below.

Final evidence: `/tmp/virtual-defaults-core-02`, `/tmp/virtual-defaults-preparation-03`,
`/tmp/virtual-defaults-portable-03`, and `/tmp/virtual-defaults-regression-01` pass.
The extended preparation proof has fifteen rejection cases in both modes, plus
ancestor-only marker removal/recovery. Portable proofs include ten structural
rejections/collision cases, method-target reflection and warm converter reuse.
All 32 existing abstract-dispatch generated programs pass. The initial portable
fixture used the reserved C++ local name `default`; the final fixture uses `fallback`.

Measured `/tmp/compiler-bootstrap-virtual-defaults-01`: conversion 4.65 s / 195,064 KiB
RSS, 269 PHS files / 1,959,421 bytes. Bootstrap 199.05 s / 472,348 KiB (461.3 MiB)
RSS / 451.0 MiB PHP heap. AST-01's nonvirtual inherited-method diagnostic disappears;
no new diagnostic text. The first remaining failure is the existing nullable-value
boundary limitation (NULL-01/02). No fatal/OOM, emission or native compiler rebuild.
Evidence: results/compiler_bootstrap_backlog_2026_10_23/virtual_defaults.
