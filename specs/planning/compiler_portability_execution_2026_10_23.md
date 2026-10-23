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
