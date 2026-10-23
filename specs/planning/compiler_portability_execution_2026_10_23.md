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

## SELF-01 — reuse proof reveals a runtime/prelowering redesign

The temporary acquisition slice passed ordinary native execution (14.99 s) for
returned self through a base/interface, self arguments, local assignment, identity
and retention. Prelowered native build failed (18.73 s) the template-free token gate:
shared_self.hpp introduces templates through the inherited application-visible base.
Moving acquisition to a concrete runtime-operation helper does not hide that base.
The production experiment was removed; no incomplete ordinary-only feature remains.
No new bootstrap measurement is claimed. Current production remains 5951486e7.

The existing observer relies on std::enable_shared_from_this being an accessible
base of the managed type. An opaque wrapper alone loses automatic registration.
A template-free observer facade therefore needs coordinated control-block binding
at shared allocation/adoption, preserving copy/move/assignment and adjusted-address
aliasing. This crosses runtime allocation/self state and both compiler transports.
The bounded design, risks, exclusions and exact proof are recorded in
results/compiler_bootstrap_backlog_2026_10_23/shared_self_reuse/README.md.
Approval is required under the repository's wide-refactor rule. Independent work
can continue, starting with struct self-value support; shared-struct retention stays
deferred. Existing constructor-promotion/parent-order question also remains open.

## SELF-02 — struct self values using existing copy boundaries

Reuse audit: prepared_receiver/prepared_place already model the original instance;
Value_Boundary_Preparation enforces normal copy capability and keeps receiver places
out of implicit move consumption. Existing receiver places are borrow-restricted,
so this slice does not widen native reference admission. Member receiver calls and
borrowed closure captures retain their existing behavior.

Prepared variable references now explicitly distinguish local storage, member
receiver access and receiver-value access. Struct self values emit dereferenced
receiver storage; ordinary value boundaries perform the copy. No new runtime wrapper
or shared-struct retention is introduced. Shared class self remains separately blocked
on the recorded runtime/prelowering redesign; portable struct copying remains COPY-01.

The first proof exposed duplicate nominal identities: Method_Preparation created a
new source definition for its receiver, different from the published signature type.
Receiver setup now uses Type_Preparation's existing observed lookup in the declaring
record's scope (without requesting layout recursively). Both ordinary method and
constructor receiver creation share it. Self-value setup reuses that receiver identity.
This is a local repair to receiver preparation, not a second registry or name-based
equality relaxation. Other Source_Types::definition consumers remain CH15 audit inputs.

Expanded native proof passes in ordinary and prelowered modes: local/argument/return
copies, member mutation, vector value isolation, shared-field identity, inherited
base snapshots, shared-allocated struct snapshots and copies produced inside borrowed
receiver closures. Eight negative cases and cross-file noncopyable-layout recovery
pass in both modes. The static negative probe must invoke its method because static
bodies are materialized on demand; the corrected probe does so.

Focused native evidence: `/tmp/struct-self-core-02` passes the initial program in
both modes; `/tmp/struct-self-core-03` passes the expanded native program in both
modes (21.34 s ordinary, 26.70 s concrete), while its earlier static negative test
needed invocation. Corrected `/tmp/struct-self-preparation-05` passes all eight
negative cases and repeated/cross-file recovery in both modes. Existing suites:
`/tmp/struct-self-method-regression-01` (26),
`/tmp/struct-self-inheritance-regression-01` (59), and
`/tmp/struct-self-capture-regression-01` (44) all pass.

CH15 follow-up: source definition creation is not an identity lookup. Remaining
Source_Types::definition calls in Member_Preparation and Inheritance_Preparation
must be classified in the final registry audit. Do not replace identity checks with
name equivalence or infer that receiver repair closes the broader registry debt.

Measured `/tmp/compiler-bootstrap-struct-self-01`: conversion 4.68 s / 195,112 KiB
RSS, 269 PHS files / 1,960,733 bytes. Bootstrap 206.26 s / 469,752 KiB (458.7 MiB)
RSS / 451.0 MiB PHP heap. The nullable-value boundary remains first; only the generic
self diagnostic changes to the precise pending class-retention diagnostic.
No fatal/OOM, emission or native compiler rebuild. Evidence:
results/compiler_bootstrap_backlog_2026_10_23/struct_self.
SELF-02 native value support is implemented; COPY-01 parity and SELF-01 redesign
are separate pending work. Shared-struct retention stays deferred.

## NULL-01/OWN-01 formation boundary — redundant nullable ownership

Reuse audit: nullable_semantics and ownership_semantics already identify the exact
roles; Type_Registry::validate_application owns formation before interning; both
nullable syntaxes share Type_Preparation::application. No new registry, family-name
list or per-type nullable implementation is needed. The existing early handle
normalization must distinguish explicit ownership from nominal default shared storage.

The registry now rejects nullable applications whose immediate payload definition
has ownership semantics. Source preparation lets those applications reach registry
validation instead of normalizing them away. Both ?owner<T> and nullable<owner<T>>
reject in locals, properties, parameters, returns and nested generic arguments.
This implements the accepted single-spelling rule for possibly-empty shared/unique/
weak handles. Required Node versus ?Node identity/transport remains the next larger
NULL-01 slice; nominal normalization is explicitly not claimed fixed here.

The native control fixture found a separate existing prelowering gap: isset on
explicit handles falls through the native-only value-probe path. Record this under
NULL/OWN consumer integration. The control now uses admitted boolean conditions to
observe empty shared/moved-from unique values, plus weak locking and retained object
identity. No isset implementation change or weakened template-free guard was made.
Existing PHP/legacy syntax may accept redundant nullable ownership; portable source
must use the single explicit handle spelling. The full compiler source/metadata scan
found no immediate redundant ownership wrapper use to migrate in this slice.

`/tmp/nullable-owner-core-02` passes registry-role tests (including renamed nullable
and ownership definitions), 30 source-admission cases with repeated rejection/recovery
in each output mode, and ordinary/prelowered empty-state native controls (13.94 s /
17.97 s). Invalid registry applications leave application counts unchanged.
`/tmp/nullable-owner-regression-01` passes all 18 generated programs after migrating
explicit ownership examples to the accepted single spelling. Nominal class controls
still describe the historical normalization pending the next NULL-01 slice.

Next NULL-01 integration stays with the existing owners: preserve nullable's canonical
application, distinguish presence from ownership queries, and let native representation
map optional class handles to the same stored handle without changing semantic keys.
Conversion_Preparation currently dispatches ownership before nullable policy, and
conversion_decision stores a single operation. Checked presence plus ancestry must
be composed explicitly there; semantic compatibility cannot be inferred from common
C++ spelling. Wrapper transport's has_value/require_value assumptions need the shared
representation distinction, not per-call bypasses. No implementation of those broader
consumers is claimed by this formation slice.

Measured `/tmp/compiler-bootstrap-nullable-owner-01`: conversion 4.99 s / 195,052 KiB
RSS, 269 PHS files / 1,961,038 bytes. Bootstrap 199.10 s / 470,052 KiB (459.0 MiB)
RSS / 451.0 MiB PHP heap. Diagnostic text set unchanged, nullable-value boundary
first; no fatal/OOM, emission or native compiler rebuild. Evidence:
results/compiler_bootstrap_backlog_2026_10_23/nullable_ownership.

## NULL-01 representation foundation — distinct semantics, shared handle storage

Reuse audit confirmed that canonical nullable applications, exact storage matching,
Preparation_Changes signature comparisons and opaque specialization naming already
retain semantic identities. Native storage mapping and wrapper transport were the
owners that incorrectly assumed every nullable application implied an optional payload
wrapper. They now use a pure Type_Queries::nullable_handle_payload interpretation
based on nullable semantics and the payload's registered/default shared-storage role.
This query does not make is_nominal_handle return true for a nullable application.

CPP_Types maps nullable default handles to the same native storage recipe/pointee as
the payload, while concrete representation keys remain based on the original semantic
application. CPP_Wrapper_Transport treats their native bytes as handles rather than
calling has_value/require_value; provider vector transport admits that same handle
interpretation. Scalar/inline optional storage remains a physical optional wrapper.
No frontend normalization, required/null conversion, member admission, override rule
or initialization guarantee is changed by this foundation alone.

`/tmp/nullable-handle-representation-03` passes O0/O2 and ASan+UBSan builds plus an
explicit application-token check. Its model uses the registry to form nullable<Node>,
proves distinct exact-reference and nested vector identities with the same native
storage, distinct concrete keys, parameter/return signature comparison, and a renamed
nullable definition. Actual generated concrete handles preserve object identity and
retention, default absence and vector provider round trips with present/absent elements.
The fixture uses the compiler's generated empty Node header; provider test code is
separate from template-free application code. The precursor signature fixture supplied
constructor arguments to a constructor-less test record; corrected code assigns its
actual typed fields. This was a test setup issue, not a production semantic change.

These are model/representation proofs, not source NULL-01 completion or whole retained
invalidation coverage. Source Node/?Node still normalizes until conversion, receiver
and initialization consumers can enforce the accepted distinction coherently. The
next slice must compose checked presence with existing ownership conversion and retain
invariant references. COPY-01 and pending SELF-01 redesign remain independent.

Adjacent `/tmp/nullable-handle-runtime-regression-01` passes four preparation suites
and seven native handle programs. Measured
`/tmp/compiler-bootstrap-nullable-representation-01`: conversion 4.69 s / 194,968 KiB
RSS, 269 PHS files / 1,962,398 bytes. Bootstrap 200.18 s / 470,180 KiB (459.2 MiB)
RSS / 451.0 MiB PHP heap. Diagnostic set unchanged, nullable-value boundary first;
no fatal/OOM, emission or native compiler rebuild. Evidence:
results/compiler_bootstrap_backlog_2026_10_23/nullable_representation.


## NULL-02 generic optional value boundaries

Reuse inspection confirmed that nullable formation already requires value_storable,
copy dependencies recurse through registered template arguments, value boundaries
already distinguish empty construction from copying, and both backends already
implement exact optional-value wrapping/extraction. Replaced the scalar/enum/inline
record admission whitelist with the existing settled Capability_Facts query. No new
retained model, payload-name cases or backend protocol is introduced. Condition
admission separately preserves established scalar/source-record behavior and rejects
unregistered payload truthiness; storage capability is not condition permission.
Generic take/output assignment, nested Result payload capability, optional-class
consumers and variance are not implemented by this slice.

`/tmp/nullable-values-03` passes ordinary and prelowered native execution (19.28 s /
25.88 s), plus four negative preparation cases. It covers vectors, hashes, nested
vectors and a distinct registered tray/tray<tray<int>> provider, independent copies,
null defaults, exact argument/return extraction, lazy coalesce, conditional values,
and the actual template_type_parameter absent-versus-empty contract-list algorithm
with an enum payload. Empty nullable<vector<Noncopyable>> requires neither payload
copying nor a valid default constructor; copying it rejects. References remain exact.
The first concrete attempt exposed the test provider's ordinary-only integer key
signature; the provider fixture now accepts the substituted key type. No production
indexing or transport change was necessary. The second pass preceded the expanded
contract-list/noncopyable-empty witnesses.

`/tmp/nullable-values-portable-01` upgrades the existing CONVERT-02 expected-blocker
fixture to successful PHP, legacy native and ordinary/prelowered native behavior for
fields, promotion, defaults, arguments and returns. Five adjacent preparation suites
(nullable, records, conversions, probes, capabilities) pass. The initial defaults
invocation omitted its required output-directory argument; the proper runner exposed
its stale container rejection expectation. Replaced that with a noncopyable default
rejection, which passes retained recovery in `/tmp/nullable-values-default-contracts-02`.
Existing scalar null-default native controls also pass in both modes. These checks
prove generated programs, not a natively rebuilt compiler.

Measured `/tmp/compiler-bootstrap-nullable-values-01`: conversion 4.72 s / 194,824 KiB
RSS, 269 PHS files / 1,962,525 bytes. Bootstrap 208.90 s / 478,356 KiB (467.1 MiB)
RSS / 459.0 MiB PHP heap. The nullable-value payload whitelist diagnostic disappears;
no diagnostic text is added. Ownership-boundary admission is now first. No fatal/OOM,
emission or native compiler rebuild. Evidence: results/compiler_bootstrap_backlog_2026_10_23/nullable_values.


## NULL-01 coordinated integration approved; source boundary slice

The fresh five-case probe at bf5a667bf confirmed the remaining mismatch in both
output modes: ?Node raw-null arguments/returns reject, but required null assignment
and nullable storage passed to a required reference admit. The user approved the
coordinated design in compiler_null01_integration_design_2026_10_23.md after the
cross-owner review. SELF-01 remains separately gated. Saved pre-change evidence:
results/compiler_bootstrap_backlog_2026_10_23/null01_integration.

The source type owner now interns nullable nominal handles without normalization.
Conversion decisions compose nullable presence with a separately validated payload
conversion; only existing exact identities and object ancestry are admitted. Ordinary
and concrete emission evaluate once and reuse native shared storage. Literal null to
required assignment/argument/return/default boundaries rejects. Nullable receivers
have explicit payload/storage interpretation, without changing global ownership roles
or exact matching. Invariant references reuse existing storage checks. Callable
adaptation admits directional optional broadening plus ancestry, never throwing
narrowing. Optional base selections reuse supplied types; required fallback coalesce
uses the existing ownership join. Synthesized optional common-base types and optional
fallback coalescing remain unimplemented consumer work, not silently inferred rules.

Weak derivation accepts required/optional default shared handles without retention;
weak class/interface locking now publishes nullable object identity. Explicit ownership
families retain their existing spelling and empty-state behavior. Required class
local reads, constructor-success field checks, general nullable identity/probe/object
cast consumers and runtime API presence guarantees are not claimed complete.

`/tmp/nullable-objects-06` passes 14 tasks: positive and empty-extraction native
execution in both modes, nine negative boundaries/override cases, and two-mode
retained cross-file signature edit/rejection/revert/reset. Ordinary positive 22.29 s,
concrete positive 23.82 s. Checked absence fails before callee output. Present copies,
upcasts, optional fields/receivers, interface adaptation, weak acquisition, lazy
coalesce and conditional results preserve identity. Initial failure-test assertions
expected the runtime code in stderr, but the program entry reports only the message;
the corrected assertion checks that actual error surface and absence of callee output.

`/tmp/nullable-objects-portable-02` passes PHP, legacy native and both v0.2 modes.
The initial fixture used unsupported ?Class local annotations; corrected to the
existing nullable<Class> annotation spelling without expanding converter grammar.
`/tmp/nullable-objects-value-regression-01` passes both-mode generic nullable values.
Six adjacent preparation suites pass, after class cleanup/weak-owner fixtures that
intentionally assign null were migrated to optional declarations. Native regressions
pass 54 upcast/ownership programs and 32 abstract-dispatch programs. Broader historical
normalization fixtures still need systematic migration; no full-suite claim is made.

Measured `/tmp/compiler-bootstrap-nullable-objects-01`: conversion 4.67 s / 195,104 KiB
RSS, 269 PHS files / 1,968,322 bytes. Preparation 185.57 s / 457,108 KiB (446.4 MiB)
RSS / 436.5 MiB PHP heap. Old ownership-boundary diagnostic disappears. Added:
object-type operation requires shared handle, nullable indexed-key admission, and
required object boundary rejects null. The first is now the next consumer blocker.
No fatal/OOM, C++ emission or native compiler rebuild; a faster failed preparation is
not a throughput improvement claim. Saved evidence: results/compiler_bootstrap_backlog_2026_10_23/nullable_objects.


## NULL-01 optional object and key consumers

Optional class/interface operands now enter the existing presence-aware object-cast
and instanceof protocol. Semantic source identity stays optional; runtime behavior
and both emission paths are reused. Indexing_Preparation owns one checked key-value
conversion for reads, writes, nested paths, probes and removal. Payload key-domain
validation remains separate from conversion; nullable stored keys are not admitted.

PHP/legacy and both v0.2 cast modes pass in /tmp/nullable-object-casts-01, including
empty/mismatched failure and identity preservation. All 33 native object-type
regression programs pass in /tmp/nullable-object-casts-native-01. Six tasks pass in
/tmp/nullable-keys-01: positive and absent-key executions in both modes plus existing
indexing/key-contract preparation regressions. Absence fails before the write value
expression; present object keys safely upcast without losing identity.

Bootstrap conversion: 4.76 s / 195,184 KiB RSS. Preparation: 200.43 s wall time /
486,208 KiB RSS (474.8 MiB) / 465 MiB PHP heap. No fatal/OOM or C++ emission.
The nullable object-operation and indexed-key diagnostics disappear; none are added.
Class-self retention is now first, still separately gated. This is generated-program
validation and PHP-hosted preparation, not a native compiler rebuild. Full NULL-01
initialization/consumer work and broader source migration remain outstanding.

Reuse review for the next initialization slice: Constructor_Preparation already
requires set: for fields without valid defaults. Object_Construction_Preparation's
supports_default currently admits empty required nominal handles; that source-value
policy must be distinguished from native backing storage and local predeclarations.
Body_Preparation and Completion_Preparation remain the local-flow owners. No new
constructor-body assignment inference is needed for the accepted direct-init contract.


## Required fields and data-construction transport

Reuse: Constructor_Preparation already validates set: inputs and nondefault fields;
synthesized data inputs, inherited constructors and record defaultability share
Object_Construction_Preparation::supports_default. Required nominal handles now have
no source default. Plain/static required fields without a construction route reject.
Explicit defaults, set: inputs and synthesized required inputs initialize directly.
Struct defaults remain supported; a struct lacking a valid default needs direct
initialization. Local predeclarations remain separate LOCAL-01 work.

The focused proof exposed a pre-existing data-construction prelowering gap: allocation
emitted raw shared<T> in application code. The backend now transports settled record
construction using the existing concrete construction input/result protocol. It
preserves evaluated snapshots, source order and private constructor access keys.
No semantic lookup or new frontend construction contract is added in the backend.
The first transport retry exposed a null constructor-key query; fixed. The private
factory extension then exposed temporary key addressing; materialized the key in the
calling context, as ordinary constructor calls already do.

/tmp/required-fields-05 passes sixteen tasks: both-mode positive execution and seven
negative cases per mode. Positive execution covers required object identity through
set:, synthesized data, inherited and private construction, plus struct defaults and
a valid local predeclaration. /tmp/required-fields-portable-01 passes PHP, legacy
native and both v0.2 routes, including promoted object identity and failure ordering.
/tmp/required-fields-constructors-03 passes all 60 native constructor regressions.
Two historical constructor fixtures used empty required storage; their intended
nullable fields/inputs are now explicit. This is not a full-suite or native compiler
rebuild claim. Unsafe local reads and runtime API presence guarantees remain open.

Bootstrap /tmp/compiler-bootstrap-required-fields-01: 80.25 s wall time /
340,500 KiB RSS (332.5 MiB) / 310 MiB PHP heap. Preparation fails first on
"Property requires set: initialization before the constructor body: file".
Many required field declarations now expose the planned source/converter migration:
constructor-body assignments need accepted direct initialization; deliberately staged
fields need INIT-02's converter-owned protocol. Diagnostics disappearing after earlier
failure do not establish their resolution. No fatal/OOM, emission or native compiler
rebuild. Lower elapsed time and memory reflect earlier rejection, not faster bootstrap.


## Direct constructor source adaptation

Reuse: accepted PHP constructor promotion already emits v0.2 set: inputs. Adapted
source_record's owned file and 33 AST cursor constructors to that existing protocol.
Cursor parameter names are preserved. source_record's snapshot parameter becomes file
so promotion initializes the existing public field; its sole repository call is
positional. No explicit parent-constructor ordering is changed. Nullable staged
fields, Storage_Cursor construction and converter-owned INIT-02 remain separate work.

/tmp/direct-source-init-02 passes AST behavior, AST invariants and lifecycle handoff.
/tmp/direct-source-init-portable-05 extracts production source_record, child_iterator_i,
children_iterator and pair_children_iterator, with small dependency owners. PHP,
legacy native, ordinary and prelowered v0.2 preserve file identity by mutation,
source-order traversal and independent cursors. Native times: legacy 21.47 s,
ordinary 23.33 s, prelowered 20.59 s. This is a bounded production-component proof,
not execution of every cursor or a native compiler rebuild.

Fixture setup corrections: removed a stale copied harness task; assembled the required
exception framework; scoped v0.2 fixture dependencies to the two exception classes it
uses. The full exception framework still fails on scpp_portability_same_exception's
object-identity comparison. The saved owner probe identifies that independent consumer
blocker. It was not a cursor failure and is not resolved by this source slice. Initial
full framework loading also included unrelated string/collection functions. The fixture
does not claim full framework compatibility. Legacy builds use the assembled framework.

Bootstrap /tmp/compiler-bootstrap-direct-source-init-01: 78.39 s wall time /
340,820 KiB RSS (332.8 MiB) / 310 MiB PHP heap. Preparation still fails first on a
file field in another owner. The first-field diagnostic disappears; no diagnostic
text is added. Repeated file/source diagnostics are aggregated across owners, so
this does not measure how many declarations were repaired. No fatal/OOM, emission or
native compiler rebuild; required-field source migration and other backlog work remain.


## Shared object identity admission

Reuse: existing runtime section 7a defines compatible shared identity, including
nullable absence and adjusted base pointers. Shared_Identity now admits that contract
using registry ownership roles and observed ancestry, then existing binary transport
and runtime helpers. No runtime or new equality semantics. Unique/weak identity and
structural equality stay outside this slice.

Both native modes pass same/distinct objects, compatible views, nullable absence,
explicit shared structs, evaluation order and single evaluation; invalid unrelated,
weak and scalar pairs reject. Retained ancestry edit/reject/revert/reset passes in
both modes. The existing operator suite passes. The production-component portability
fixture now loads the complete exception framework and executes same_exception in
PHP, legacy native and both v0.2 modes. This supersedes the prior declaration subset.
Evidence: results/compiler_bootstrap_backlog_2026_10_23/shared_identity/.

Bootstrap: 78.92 s wall time / 341,820 KiB RSS (333.8 MiB) / 312 MiB
PHP peak allocation. Preparation still fails first on required initialization of a
file field. The aggregated diagnostic texts are unchanged from the preceding source
adaptation checkpoint: the integer-operation diagnostic remains at other sites.
The focused exception-helper execution proves this repair; aggregate text alone
cannot identify repaired owners. No fatal/OOM, emission or native compiler rebuild.
Required fields, local flow and the remaining backlog remain open.

## LOCAL-01 completion integration audit

Mapped the existing local-storage, reference lookup, structured completion, loop and
exception owners in compiler_local01_completion_integration_2026_10_23.md. Concrete
hazards are write-only nested target lookup, do-while header preparation order, and
runtime-initialized catch/iteration bindings. These are part of the accepted bounded
flow integration; no implementation or additional bootstrap result is claimed yet.

## LOCAL-01 structured assignment implementation

Extended existing completion with transient pending storage facts for normal, return,
break and continue paths. Branch/lazy-expression isolation, loop and switch joins,
conservative catch/finally entry, successful finally writes, reference/capture reads
and runtime catch/iteration binding entry are covered. Do-while conditions now prepare
after body/continue paths while retaining lexical scope. No CFG, retained assignment
cache, effect analysis or deferred struct lifetime. Ordinary write targets and take
outputs use storage lookup without value-read admission.

65 focused tasks pass in /tmp/local-assignment-03; the same tasks re-pass in -04.
Two added take-output fixtures needed the json metadata module; corrected focused
retry /tmp/local-assignment-take-01 passes both native modes. Production completion
composition passes PHP, legacy native and both v0.2 modes in
/tmp/local-assignment-portable-02. Explicit vector annotations corrected the first
legacy fixture's property-array literals; no implementation change was needed.

137 existing generated control-flow programs pass under
/tmp/local-assignment-control-native-01. The encompassing control_flow.py suite is
not green: incremental_cpp's prototype-default assertion and specialization_dispatch's
unprepared-expression check fail identically on archived baseline 8299abaee.
Saved baseline/current diagnostics remain regression debt. Evidence is in
results/compiler_bootstrap_backlog_2026_10_23/local_assignment/.

LOCAL-01 remains in progress. Existing take semantics conditionally initialize outputs;
truth-conditioned assignment facts must still connect that guarantee to subsequent
reads, including !take early failure exits and lazy conditions. A true result_or_bool
sentinel does not establish the payload. Empty file-lock factories separately expose
the pending runtime ownership/default-contract audit. Do not invent dummy objects or
change those contracts merely to bypass the check.

Final bootstrap /tmp/compiler-bootstrap-local-assignment-02: 74.09 s wall time /
339,964 KiB RSS (332.0 MiB) / 310 MiB PHP allocation. Preparation still stops first
on required file-field initialization. Against shared_identity, the only added
aggregate diagnostic is "Required local may be read before assignment: out"; none
are removed. Facade take success-flow and empty-handle contracts remain open. No
fatal/OOM, emission or native compiler rebuild. The earlier rejection point prevents
interpreting these numbers as a full-bootstrap performance improvement.

## LOCAL-01 conditional extraction integration

Reuse: take operation facts already distinguish payload success from boolean sentinels.
Added invocation-local local_condition_paths to existing expression preparation. Only
direct take and admitted logical composition publish correlations; ordinary expressions
discard child correlations. If/loop/conditional branches use these paths. This keeps
state out of retained facts and avoids a second AST traversal/CFG. The documented
extraction semantics are unchanged. Saved booleans, arbitrary comparisons/calls and
constant folding remain outside the bounded analysis. Generic nullable take is NULL-02.

/tmp/take-assignment-03 passes all 23 tasks, including native success/failure and
true-sentinel controls, negative correlations and retained guard edit/reject/revert/reset.
The fixture privately opts one shared runtime provider into result_payload; production
metadata is unchanged. /tmp/take-local-regression-01 passes all 67 local-assignment
tasks. Existing take preparation passes in /tmp/take-assignment-retained-01. Production
condition-path composition passes PHP, legacy native and both v0.2 modes in
/tmp/take-assignment-portable-01. Initial fixture true-sentinel spelling and runtime
catalog configuration ordering were corrected; no language widening for those fixtures.
Evidence: results/compiler_bootstrap_backlog_2026_10_23/take_assignment/.

Bootstrap /tmp/compiler-bootstrap-take-assignment-01: 75.10 s wall time /
339,992 KiB RSS (332.0 MiB) / 310 MiB PHP allocation. Preparation fails first on
required file-field initialization. Aggregate diagnostic texts are unchanged from
the prior local-assignment checkpoint; this does not measure individual repaired
owners. No fatal/OOM, emission or native compiler rebuild. The isolated production
scpp_portability_lock_empty factory still reproduces the out read-before-assignment
diagnostic (/tmp/take-empty-lock-probe-01). Its empty required-handle contract belongs
to the pending runtime ownership/binding audit, not conditional take assignment.

## NULL-02 capability-based nullable take

Reused nullable formation's value_storable gate and copyable_value (including copy
assignment) rather than a scalar/enum whitelist. Outputs retain exact type/writable
storage checks; source snapshots/copies remain unchanged. Runtime nullable<T> already
provides generic value behavior. Added the same extraction semantics for erased
optional shared handles. The first concrete handle proof exposed a transport mismatch;
both output address and adapter unpacking now consult CPP_Wrapper_Transport::payload
to distinguish physical wrappers from erased optional handles. No new capability,
semantic family whitelist or lifetime protocol.

/tmp/nullable-take-03 passes 17 tasks across both modes, including nested registered
tray<tray<int>>, vectors/hashes, non-defaultable structs, class/interface identity,
noncopyability/exactness rejections and retained capability recovery. The additional
nullable value<Class> control passes both modes in /tmp/nullable-take-inline-01.
/tmp/nullable-take-result-regression-01 passes all 23 conditional Result tasks. The
existing runtime take helper fixture, extended for shared absence/identity/self-alias,
passes under /tmp/nullable-take-runtime-01.

/tmp/nullable-take-portable-04 passes PHP, legacy native and both v0.2 modes using
take_nullable. Early fixture attempts exposed unsupported generic nullable local
annotations and used an unsupported free-function authoring form. The final static
method uses supported ?array parameter annotation. The local annotation grammar gap
is recorded as a converter limitation, not fixed by this adaptation. Evidence is in
results/compiler_bootstrap_backlog_2026_10_23/nullable_take/. Nested Result<nullable<T>>
capability/transport, runtime empty-handle contracts and broader CH15 work remain open.

Bootstrap /tmp/compiler-bootstrap-nullable-take-01: 77.21 s wall time /
341,376 KiB RSS (333.4 MiB) / 312 MiB PHP allocation. Preparation still fails first
on required file-field initialization. Aggregate diagnostic texts are unchanged
from the prior conditional-take checkpoint. No fatal/OOM, emission or native compiler
rebuild. These early-failure measurements do not establish full-bootstrap performance.

## NULL-02 nested nullable Result integration

Reused nullable registration's unconditional transport opt-in, conditional copying,
runtime result_core storage and recursive prelower transport. No capability-model or
runtime ABI change. Restricted the optional-handle conversion shortcut to nullable
operations, preserving Result's outer wrapper. Plain wrapper isset now publishes the
existing outer-state presence fact for all admitted payloads rather than enum only.

Focused ordinary/concrete state/provider/handle/sentinel/copyability proofs pass;
retained payload edits reject and recover in both modes. Actual output_read agrees
with legacy native and the PHP helper. The full output suite no longer excludes
reads but still rejects its uninitialized required lock argument in v0.2; legacy
passes. No full-publication success claim. See
[dated evidence](results/compiler_bootstrap_backlog_2026_10_23/result_nullable/README.md).

Bootstrap /tmp/compiler-bootstrap-result-nullable-01: 74.34 s wall time /
341424 KiB RSS (333.4 MiB) / 312 MiB PHP allocation. First blocker remains required
file-field set: initialization. Aggregate Result payload-capability diagnostic is
removed; none added. No fatal/OOM, emission or native compiler rebuild. Runtime handle
contracts, source initialization adaptations, other consumers and CH15 remain open.

## OWN-01 lock-handle contract audit (no implementation)

The [lock audit](compiler_lock_handle_contract_audit_2026_10_23.md) identifies an
existing PHP/native carrier mismatch: PHP lock_empty returns a present resource-free
object; the native facade returns an unassigned required handle. Native runtime
already supports present released tokens but exposes no factory for them. Recommend
an additive fs_lock_empty factory using existing private construction and shared
return transport, keeping ordinary required-object rules intact. The alternative
nullable API would require wider invariant-reference/source propagation. Public
factory/platform disposition is pending discussion; no runtime/compiler fix made.

## OWN-01 provisional empty lock factory

User approved fs_lock_empty with an explicit end-of-goal keep/remove review. The runtime
factory returns a present descriptor-free token, reusing existing allocation; try and
transfer share that path. Both binding catalogs and the portable facade are updated.
No compiler semantic changes. The full output suite passes on all native routes;
PHP/native facade lifecycle, native process/cleanup and regenerated catalog checks pass.
See [evidence](results/compiler_bootstrap_backlog_2026_10_23/lock_empty/README.md).

Bootstrap /tmp/compiler-bootstrap-lock-empty-01: 74.35 s wall / 341280 KiB RSS
(333.3 MiB) / 312 MiB PHP allocation. First failure remains required file-field
initialization. Removes required-local out diagnostic; adds none. No fatal/OOM,
emission or native compiler rebuild. Reservation initialization and other backlog
items remain. **The final goal review must ask whether to keep this factory.**

## TASK-02 synchronized retained association snapshots

Collector-owned retained_types now takes the same publication lock as registration,
returns declaration handles through a transient source_type_snapshot, and releases
before source-local reconciliation. Standalone lookup remains direct. No new retained
state, runtime synchronization protocol or task semantic permission. PHP identity,
new registration/failure/retry and standalone regressions pass. Extracted-helper
legacy native overlap/ThreadSanitizer passes; removing only reader synchronization
reproduces a data race. Both v0.2 modes reject shared callback captures, so TASK-02
remains partial pending TASK-01. See
[evidence](results/compiler_bootstrap_backlog_2026_10_23/retained_lookup/README.md).

A first native fixture silently lost its callback for-loop in legacy emission; its
pass was discarded as concurrency evidence. Converted PHS preserved the loop. This
legacy generator defect is recorded separately; the corrected typed-method fixture
asserts work totals and registry growth and adds a four-worker scheduling barrier.

Bootstrap /tmp/compiler-bootstrap-retained-lookup-01: 73.71 s wall / 341916 KiB RSS
(333.9 MiB) / 312 MiB PHP allocation. First required-file initialization blocker and
aggregate diagnostic set unchanged. No fatal/OOM, emission or native compiler rebuild.

## TASK-01 runtime permission carrier foundation

The existing callable_t and provider transfer_callback now carry explicit task
permissions independently of the consumer's isolated/joined policy. Boolean callers
retain their old isolated meaning; existing true grants the owned environment both
policies. Queries/guards default to isolated admission. Copies preserve permissions;
erased empty and moved-from values cannot claim permission. Provider adaptation can
carry the full permission value without upgrading it. A shared-capturing runtime
fixture passes ordinary invocation and joined checking while rejecting isolated
checking after copies, provider adaptation and erasure.

This is the runtime foundation only: compiler metadata, preparation, closure emission
and concrete facade transport still need the coordinated policy integration. No
source shared-task admission or TASK-02 v0.2 completion is claimed. No bootstrap
checkpoint was run for this runtime-only change.

Existing task_transfer.py passes all 15 tasks (ordinary/concrete cold/warm and
metadata rejection) in /tmp/task-permissions-existing-transfer-01. Runtime sanitizer
checks in /tmp/task-permissions-runtime-02 pass callable transfer, existing async,
shutdown and ownership. The coordinator test failed with an empty-vector access
during the parallel run. The unchanged HEAD-header baseline passes, and three
sequential reruns each of the current and baseline binaries pass in
/tmp/task-permissions-coordinator-recheck-01. The cause of the intermittent failure
is unresolved; do not claim a proven pre-existing defect or a clean full-suite run.
The first sandboxed run could not execute LeakSanitizer under ptrace; the second run
was outside that restriction with leak detection enabled.

## TASK-01 compiler permission transport

Shared source class/interface captures now carry joined-only permission using
existing record-storage/payload queries. Existing recursive owned-container
classification propagates that restriction; isolated payload/known-closure checks
still reject it. Closure erasure, nested captures, provider adapters and concrete
callable facades preserve the full runtime permission value. The dependency-free
task_permissions header keeps templates out of application declarations.

All 19 task_transfer tasks pass in /tmp/task-permissions-transport-02, including
new joined-only/nested-empty controls and prior isolated rejection/retained repair.
Initial header-dependency and metadata fixture-order failures were corrected;
see results/compiler_bootstrap_backlog_2026_10_23/task_permissions/README.md.
API metadata selection and shared payload/callback admission remain pending;
this does not close TASK-01 or the pending TASK-02 v0.2 execution proofs.

Bootstrap /tmp/compiler-bootstrap-task-permissions-01: 75.94 s wall /
342272 KiB RSS (334.3 MiB) / 312 MiB PHP heap. Same first required-file initialization
failure and aggregate diagnostic texts as retained_lookup. No fatal/OOM, emission
or native compiler rebuild.

## TASK-01 API policy integration and TASK-02 execution closure

Registered parameter contracts now carry task_transfer_policy instead of a Boolean.
Legacy requires_transfer metadata maps to isolated admission; explicit transfer_policy
selects isolated_transfer or joined_shared. Generic payload requirements independently
carry their policy. Registration, exact callable contracts, substitution, preparation
and emitted guards preserve it. Only the unordered joined API selects shared admission
in the production catalog. No scheduler or synchronization redesign.

23 callback tasks, six shared payload/identity/rejection tasks, extended metadata
rejection and six existing submission/regression tasks pass. The production extracted
retained lookup helper now executes in ordinary and concrete v0.2 modes as well as PHP.
Together with prior legacy overlap/TSAN and unlocked race-control evidence, this closes
TASK-02's bounded repair. TASK-01 still needs explicit shared-owner lifetime and
worker/publisher failure proof closure; no whole-compiler race-freedom claim.

Initial bootstrap conversion rejected unnecessary enum parameter defaults in the new
internal methods; all callers already supplied policy, so defaults were removed.
Retry /tmp/compiler-bootstrap-task-policy-02: 74.18 s wall / 341684 KiB RSS
(333.7 MiB) / 312 MiB PHP heap. First required-file initialization blocker and aggregate
diagnostic texts unchanged. No fatal/OOM, emission or native compiler rebuild.
See results/compiler_bootstrap_backlog_2026_10_23/task_policy/README.md.

## TASK-01 lifetime and failure acceptance closure

No production changes after c53a66687. task_joined_shared now passes 12 tasks in
/tmp/task-joined-shared-02, adding temporary callback owners and source worker/publisher
exception/recovery in both modes. The deterministic source failure tests use one
worker; the separate native proof forces two workers through a barrier and delays one
before completion. Under per-call threads and a two-worker shared pool, success and
both failure paths join all resident work, preserve original errors, release captured
shared owners and permit a subsequent batch. ASan/UBSan/leak checks pass in
/tmp/task-joined-lifetime-01; fixture/tasks.cpp instrumentation does not extend to the
runner's unsanitized fixture-runtime object. See task_lifetime evidence.

This closes accepted TASK-01 and the TASK-02 dependent execution scope, not a
whole-compiler concurrency audit. No bootstrap rerun for test-only changes; latest
production checkpoint remains 74.18 s / 333.7 MiB RSS / 312 MiB PHP heap, with required
file-field initialization first. Remaining source/converter, SELF-01 and CH15 work
remain open. Exact PHP cleanup timing is not required by the native lifetime proof.

## INIT-01 required source input migration

source_work now receives source/record/position before queue publication; Tokenizer
receives source by promotion; token_list receives file/content at tokenization and
private-snapshot creation. Existing converter promotion emits set parameters. These
values were available at creation, so no _uninitialized_ protocol or new flow analysis
is appropriate. Synthetic token-list test constructors now supply their inputs.

PHP tokenizer/publication/retained/AST-invariant checks pass. Extended extracted-record
source_initialization passes PHP, legacy native and both v0.2 modes. model.php still
fails its fixed registry-root count assertion before the changed fixture; a HEAD-source
overlay reproduces it. Keep that test reconciliation open. Evidence: required_inputs.

Final bootstrap /tmp/compiler-bootstrap-required-inputs-02: 74.73 s wall /
341924 KiB RSS (333.9 MiB) / 312 MiB PHP heap. First blocker advances from file to
collection initialization; aggregate message set unchanged. No fatal/OOM, emission
or native compiler rebuild. COPY-01 helper proposal remains pending; independent
required-constructor/staged-field inventory continues.

## INIT-01 collection constructor migration

Reuse existing promotion/set lowering for Symbol_Collector collection/source_scope,
Parser tokens, collected_name collection, parsed_file inputs and trait_member_instance
origin/working. Parent constructor calls and algorithmically initialized workers are
unchanged. Six focused PHP collection/trait checks pass. Bootstrap conversion succeeds;
preparation first advances from collection to syntax_node. Measured 73.72 s wall /
342324 KiB RSS / 312 MiB PHP heap. Evidence: collection_initialization. Extracted parsed_file and trait_member_instance component proof passes PHP, legacy
native and ordinary/concrete v0.2 output, including identity and aliased scope storage.
This does not prove native execution of the full parser/collector. The next syntax_node
constructor calls parent before its assignment; that ordering decision remains pending.
No end-to-end bootstrap success claimed.

## CH15 default-constructor outlining name gate

Reuse settled sequence/empty-only construction plans and property-default emission;
remove vector/hash identity checks from CPP_Default_Constructors. No source admission
or provider protocol change. Thirteen provider/Storage/admission checks pass across
O0/O2 and ordinary/concrete output; retained policy regression passes. Evidence:
registry_outlining. Bootstrap 77.17 s / 342424 KiB RSS / 312 MiB PHP heap, same first
syntax_node blocker and diagnostic set. The remaining registry audit stays blocking;
transfer admission cannot be replaced with copying/storage capability alone.

## Converter nullable local annotations

Reuse the existing explicit container grammar behind one local nullable layer.
Both ?vector/hash and nullable<vector/hash> forms now convert, including nested
payloads. Existing parser restrictions remain; no target semantic change. PHP,
legacy native and ordinary/prelowered v0.2 nullable_take proofs pass; annotation
admission/rejection checks pass for both converter targets. Evidence: nullable_locals.
Bootstrap 1:14.20 wall / 342344 KiB RSS / 312 MiB PHP heap, same syntax_node blocker and
diagnostic set. This closes the recorded generic nullable-local container grammar gap,
not arbitrary user-template annotations or all remaining NULL-01/02 consumers.

## NULL-01 supplied-base optional conditional joins

Conditional/Elvis joins now intern the optional form of the existing supplied-payload
ownership join. ?Derived/Base no longer requires a preexisting ?Base branch. Fifteen
focused tasks pass, including exact-reference inference checks, absence/identity,
sibling rejection and existing retained/conversion regressions. Portable PHP uses
explicit nullable branch widening because direct legacy deduction fails; normalized
PHP/legacy/ordinary/concrete proofs pass. Evidence: nullable_selection.
Bootstrap 1:14.31 wall / 342132 KiB RSS / 312 MiB PHP heap, same syntax_node blocker and
diagnostic set. Optional-fallback coalescing remains open; no unseen-base inference.

## INIT-02 explicit layout acceptance prerequisite

No production marker declarations found. Parser_Run.current_scope is a later-stage
assignment candidate; constructor-computed inputs remain a separate source decision.
The explicit private-nullable backing/checking layout now passes PHP, legacy native,
ordinary v0.2 and concrete prelowering for class and scalar-struct payloads. Proof:
tests/portability/staged_storage.py; evidence staged_storage_layout. Identity, early
reads, replacement, failed RHS preservation and independent explicit struct copies pass.
Automatic marker/access rewriting and its rejection/hygiene/null-write/retained proofs
remain open. No production change, so no new bootstrap checkpoint for this test slice.

## INIT-02 explicit Parser_Run scope adaptation

The audited post-construction current_scope now has private nullable storage,
checked reads and required whole-value selection. All assignment/read call sites
preserve existing scope transitions. Nine PHP parser/trait/AST/namespace/retained
checks pass; extracted production accessor methods pass PHP and all three native
routes. Evidence: parser_scope. This is authorized explicit source adaptation;
automatic marker rewriting, field-place classification and remaining INIT-02 proofs
stay open. Bootstrap 1:14.42 wall / 342388 KiB RSS / 312 MiB PHP heap, same first
syntax_node blocker and diagnostic set.

## CH15 registered default construction and inventory refresh

Core vector/hash implicit construction now uses existing default_constructible
metadata rather than name gates in supports_default. Fourteen provider/core/control
checks pass in ordinary/prelowered O0/O2 output; absent metadata rejects and explicit
factory counts are unaffected. The reproducible registry_audit.py tool refreshes
255 stage/core files to 181 current and 32 retired/changed candidates; a repeat is
byte-stable, not a full semantic audit. Evidence: registry_default.
Bootstrap 1:14.80 wall / 342620 KiB RSS / 312 MiB PHP heap; same syntax_node first blocker
and diagnostic set. Remaining CH15 operations and nonlexical inventory stay open.
