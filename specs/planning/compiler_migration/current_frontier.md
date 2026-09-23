# Current migration dependency frontier
Doc Status: planning

Active readiness is **203 production files**: input preparation (137 outcomes),
tokenizer (304), parser storage/angle matching (414 plus a reused 5,000-stream PHP
unit), expression grammar (132), and file statements/declarations (82 plus nine host lifecycle assertions). Exact native target remains
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`.

The global helper convention is implemented: no imports, q_ for existing PHP names.
Whole-file grammar is implemented. Syntax access/comparison adds 134 outcomes. Project parser planning/join/reuse adds 66 outcomes plus nine host purity assertions.
Source declaration collection adds 97 PHP/native outcomes plus nine host purity assertions. Source entry selection adds 44 PHP/native outcomes and nine host purity checks. Representation vocabulary adds 98 PHP/native outcomes and retained constructor checks. Lifecycle contracts add 154 PHP/native outcomes. Scalar catalog and entry binding add 116 PHP/native outcomes. Declaration lookup adds 40 PHP/native outcomes. Lexical/body name resolution adds 320 PHP/native outcomes. Project resolution adds 182 PHP/native outcomes plus 25 host checks. Canonical type storage adds 133 PHP/native outcomes plus 20 host checks. Aggregate lifecycle composition adds 36 PHP/native outcomes and eight host checks. Normalized structural definitions are now proved; annotation preparation remains incomplete; provider imports and semantic comparison remain separate dependencies. The complete compiler CLI is not implemented.
See [file parsing](../../portability/file_parser.md). src-runtime-preparation remains
PHP as-is and outside conversion scope. The former 39-file coverage is historical.

The table below records pre-reset dependencies and reusable findings, not active
implementation status or a mandatory task sequence. Previously adapted code is
under `compiler/reference/pre-rewrite/`.

| Selected area | Concrete dependency | Next action |
| --- | --- | --- |
| Structural syntax access and semantic consumers | Three query files now pass cumulative PHP/native coverage on adopted candidate a1a1babd | Continue dependent semantic components; their ten cursor consumers are not yet native-ready as complete files. |
| Semantic records, lifecycle and backend vocabulary | 41 string-backed enums and seven enum methods exceed the selected native subset | Implement the staged typed-tag/codec plan accepted on 2026-09-22; see enum_portability_decision.md. |
| Lexical worker and source diagnostics | Explicit global base class resolves inside the derived namespace | #233 supplies d493525d and combined descendant 361b1e97; validate the diagnostic component before adoption. |
| Manifest parsing and runtime metadata ingestion | Native JSON tables erase object/list identity | Native shape-preserving document API requested in [#240](https://github.com/alexstanciu-1/simplecpp/issues/240); json_document_requirement.md defines the requirement and subsequent PHP adapter work. |
| Source path resolution | Existing host-sensitive absolute-path/separator rules | fs_is_windows is available in 361b1e97; add PHP adapter and prove Source_Paths on the candidate. |
| Verified source snapshot reading | Path/open-handle device/inode, regular-file kind and version checks, bounded read and guaranteed close | Linux fs_read_snapshot is available in 361b1e97; add PHP/native adapter and compiler proofs; other hosts explicitly unsupported. |
| Input_Selection as a whole | supports_increment consumes Symbol_Refresh and its semantic catalog | Follow semantic-record dependencies; do not split a tiny select-only fragment merely to increase ready-file counts. |

## Path resolution finding

Source_Paths::is_absolute passes `DIRECTORY_SEPARATOR === "\\"` to the already
proved pure Source_Path_Syntax helper. Source_Paths::resolve also normalizes native
Windows separators while preserving literal POSIX backslashes. The implementation
therefore needs both canonical path lookup and a target-host platform/separator fact.

The previously selected `2f0d667f` registry exposes fs_realpath, but a search of its PHP generator
and runtime found no DIRECTORY_SEPARATOR/PHP_OS_FAMILY support or equivalent
registered platform helper. This is a source/API inspection finding, not a native
execution proof that every possible expression is unavailable. Do not substitute a
constant chosen on the converter's host: that can diverge from the compiled target.
Do not infer the host from a filename's spelling.

A small v0.1 host fact such as a filesystem-native-separator or Windows-host query
would allow the existing Source_Paths policy to stay local and unchanged. Native
platform selection belongs to the target implementation, not the converter's symbol
index. No target code was changed by this inspection.

On 2026-09-22, at the user's request, both filesystem snapshot operations and
host-platform information were requested in [issue #233](https://github.com/alexstanciu-1/simplecpp/issues/233#issuecomment-5771113976),
including the read protocol and acceptance coverage. Native lossless JSON is an
accepted direction but is not included in that two-requirement issue comment.

## Work posture

The user accepted the enum, cursor and native lossless JSON recommendations on
2026-09-22. Enum and cursor implementation may proceed within their documented
scope. Continue independently where a coherent component
can be proved; do not weaken validation, discard serialization facts, flatten source
diagnostics, or replace a missing capability with fabricated state to bypass this
frontier. A shared portable JSON parser is possible, but is real implementation and
validation work rather than a trivial function-map addition.

See [the PHP adaptation record](php_adaptation_record.md) for completed changes,
optimization follow-ups and the approved cursor-first resume assessment.

[Candidate a1a1babd proof](../../portability/release_candidate_a1a1babd.md): all ten
established native suites pass. Subsequent explicit source guards clear the query
behavior failure; see `results/explicit-guards-01` and the adaptation record. That historical 39-file validation does not apply to the active rewrite manifest.

Native record layout contracts add 18 PHP/native outcomes and 441 retained-contract
comparisons. Resource-aware definition validation now adds 39 PHP/native checks and 90 retained allocation-effect cases. Record/array materialization adds 27 PHP/native checks. Next: concrete annotation and provider/storage dependencies.

Definition_View adds 14 PHP/native checks for provider/source precedence and accepted
identity. Instance contexts, typed arguments and exact integer literals add 35 PHP/native
outcomes and 200 host range cases. Instance allocation now adds 23 PHP/native outcomes and 60 retained allocator calls.
Symbolic terms and permission-result containers add 40 PHP/native checks and 529
retained symbolic comparisons. Provider integration precedes symbolic declaration
interpretation and the template-checking worker/joins, then registry publication
and instance bindings.
Annotation resolution remains incomplete.

Provider references and semantic signatures add 35 PHP/native checks and two PHP
carrier checks. The user approved provider integration, preserving the PHP
preparation tool and its output contract. Next: generic-family contracts and source
exposure records, then catalog/import and shared symbol integration.

Generic-family contracts and source exposure now add 51 PHP/native outcomes and
33 retained-validator cases. The declaration model is proved; next is real provider
import/catalog integration, followed by symbol origins and name bindings.

Normalized record catalogs and Family_Adapter acceptance now add 30 PHP/native
outcomes. Existing scalar catalog (116) and project resolution (182) native proofs
pass after the shared catalog extension. Next: prepared callable/storage contracts
and package metadata ingestion/composition, then provider symbol origins and bindings.

Prepared callable ABI contracts add 66 PHP/native outcomes, 32 retained compatibility
comparisons and direct/hidden slot-map agreement. Next: storage-family contracts,
then package ingestion/composition and shared provider symbol integration.

Typed storage families and exact descriptor/ownership validation add 53 PHP/native
outcomes. Next: package-local storage records and prepared-package import/lease
boundaries, before shared provider symbol origins and name bindings.

Package-local runtime storage/type records and Package_Syntax add 66 PHP/native
outcomes, with 520 retained-prototype attribute inputs tested through both ABI
validators. Evidence: `results/package-syntax-01`. Package acceptance, checksum
verification, leases and provider symbol integration remain incomplete.

Resource_Import now has 54 PHP/native outcomes for explicit direct resource
permissions and complete allocation-effect ownership/borrow validation
(`results/resource-import-01`). Next: package lifecycle ingestion and the remaining
package composition dependencies; checksum/lease and shared provider symbols remain.

Lifecycle_Import adds 156 PHP/native outcomes and 151 applicable retained-helper
comparisons (`results/lifecycle-import-01`). Construction/copy/move/assignment/
destruction evidence now normalizes to the shared lifetime model. Next: record and
callable metadata normalization, then complete package acceptance/composition.

Record_Import and its explicit batch result add 42 PHP/native outcomes, with 38
retained acceptance comparisons (`results/record-import-01`). Native scalar fields,
measured layout and unchanged-input publication are proved. Next: callable bindings
and metadata ingestion before complete package acceptance/composition.

Binding_Import adds 374 PHP/native outcomes and 370 retained comparisons
(`results/binding-import-01`) for language roles and explicit/text conversion
permissions. Next: physical result/parameter normalization and complete callable
metadata ingestion; package acceptance and provider symbol integration remain.

Callable_Abi_Import adds 113 PHP/native outcomes and 112 applicable retained
comparisons (`results/callable-positions-01`). Result transport and semantic-to-ABI
position normalization are proved. Next: complete callable identity/exposure and
binding composition, then package acceptance and shared provider symbols.

Complete callable metadata composition adds 34 PHP/native outcomes and 34 retained
comparisons (`results/callable-import-01`). Explicit compiler exposure/payload
projections preserve the original consumer behavior; accepted backend export ownership
is still a separate dependency. Next: remaining type/storage import and package
ownership/composition, then provider symbols. No end-to-end provider pipeline yet.

Storage_Import adds 144 PHP/native outcomes and retained comparisons for the complete
eight-primitive/six-operation storage protocol (`results/storage-import-01`). The
shared pointer syntax regression passes 66 native outcomes. Next: a narrow reserved
local-name preflight to eliminate repeated avoidable native build failures, then
remaining package type/import ownership and acceptance dependencies.


The reserved-local preflight is implemented. Cumulative PHP/tool checks pass for
107 ready files; keyword locals now fail before native build, while declared
parameters and fields retain their supported behavior. Evidence and focused native
regressions: `results/reserved-locals-01`. Next: package type/import ownership.


Package physical measurements now add 106 PHP/native outcomes and retained importer
acceptance comparisons (`results/package-measurements-01`). All six storage kinds,
alignment and integer width/signedness are validated without granting type ownership.
Next: language exposure and exact accepted native/source owner binding, then complete
package acceptance; those remain incomplete.


Ordinary package type exposure adds 38 PHP/native outcomes with retained importer
agreement (`results/type-exposure-01`): exact scalar/void catalog identity, explicit
opaque/span permissions and deferred record materialization. Native/source imports
cannot use this path. Next: exact accepted native imports and source export ownership,
then whole-package composition/acceptance.


Accepted native type imports add 40 PHP/native outcomes and retained-importer
comparisons (`results/native-type-import-01`). Exact definition identity, measured
storage and semantic copy/assignment permission are checked separately from C++
traits. Next: source-export ownership and complete package type publication/retention;
package target/checksum/lease acceptance remains incomplete.


Source-export project/backend provenance adds 279 PHP/native outcomes and retained
acceptance comparisons (`results/export-provenance-01`). Explicit project identity,
byte-preserving lexical roots and required target/revision keys are retained without
filesystem access or claims of verified backend support. Next: tagged export type
identity and accepted layout/dependency provenance, before source-export binding.


Typed export identity encoding adds 61 PHP/native outcomes and 215 exact retained
key comparisons (`results/export-identity-01`). Tagged keys preserve argument order,
declared constant types, nested structure and source flags without heterogeneous
parts arrays. Next: accepted layout/dependency provenance and source-export ownership;
source/provider identity projection itself remains pending.


Accepted layout/dependency records add 34 PHP/native outcomes, 17 retained layout
comparisons and six PHP carrier rejections (`results/layout-contracts-01`). Copied
container membership retains exact shared lineage and definition provenance. Native
measurement/join acceptance remain unimplemented. Next: source export capability
and task records, then compiler-side source payload binding and package composition.


Physical ABI and source-export records add 219 PHP/native outcomes, including 204
retained capability/semantic comparisons (`results/source-export-contracts-01`).
Source-only complete plans, explicit unavailable states and separate import/implementation
associations are preserved. Next: compiler-side source payload binding and package
type composition; source export production/join/linkage remain unfinished.


Package type composition adds 38 PHP/native outcomes and 38 retained acceptance
comparisons (`results/package-type-map-01`). Ordinary catalog exposure, accepted
native imports and exact source payload definitions now compose through one private
map, with conflict/unknown-binding rejection before publication. Source payloads
retain their existing definition and measured layout; no adapter definition is
substituted. Next: accepted package contracts and exact retained-binding comparison;
receipt validation, source export production/join and complete package acceptance
remain unfinished.


Runtime package/project records add 160 PHP/native outcomes and retained lifecycle
enumeration comparisons (`results/runtime-package-01`). Queries preserve exact
shared type/catalog identity, copied container membership and stable
destroy/copy/move/assign/default order. Rebound native/source owners are excluded
from duplicate lifecycle enumeration. Constructor calls do not authorize artifacts
or receipts. Next: explicit semantic contract comparison for package/type reuse;
`matches`, retained-type canonicalization, lease ownership and diagnostic projection
are not yet migrated.


Explicit callable comparison and retention add 925 PHP/native outcomes and original
prototype equality comparisons (`results/callable-contracts-01`). Nested reference
identity, ordered semantic parameters, result/effects, physical ABI and exposure
flags all participate. Equal rebuilt contracts retain old object identity; changed
contracts and new coverage keep their new objects and order. Next: type-definition
and binding/project comparison before package reuse; complete package acceptance
and integration of callable retention in the adapter remain pending.


Lifecycle equality adds 842 PHP/native and original-prototype comparisons
(`results/lifecycle-contracts-01`). All five permission categories and exact
imported/source operation plans participate; source member order matters, while
role-map insertion order does not. Local type/body IDs are comparable only within
the same accepted lineage. Next: complete definition/resource/layout/storage
comparison, then type and package reuse. This helper alone does not authorize reuse.


Full definition comparison adds 1,513 PHP/native matrix outcomes plus seven focused
retained-prototype contract checks (`results/definition-contracts-01`). Resource
paths, native layouts, record fields and typed-storage dependencies now participate
alongside names, representations and lifecycle permissions. Empty resource wrappers
normalize to no obligations; storage map insertion order is ignored while ordered
paths/fields retain meaning. Next: integrate exact type retention, then package
binding/project-context comparison; complete package acceptance remains unfinished.


Runtime type retention adds 172 PHP/native outcomes, including 169 comparisons
with the actual retained equality/retention implementation (`results/type-retention-01`).
Unchanged bindings and complete contracts retain old object identity; changed
contracts under unchanged bindings require a fresh type context. Missing/changed
old bindings keep current objects. Source payload bindings require exact export
definition identity, and late failure leaves both input maps untouched. Next:
package binding/project-context comparison and adapter acceptance integration;
the retention helper assumes the caller has established the same package context.


Package-context matching adds 33 PHP/native outcomes (`results/package-context-01`).
Current selection is an explicit record; directory/manifest bytes, base catalog,
binding maps and project receipt/export membership must agree. Rebuilt ordinary
bindings may match, but accepted native/source owners retain exact object identity.
This deliberately makes reconstructed accepted owners a cache miss instead of
returning an old package carrying stale owner associations. Artifact/receipt
validation must still precede the query. Next: package metadata/artifact acceptance
and source receipt validation; complete adapter integration remains unfinished.


Package manifest schema adds 77 PHP/native outcomes (`results/package-manifest-01`).
Pointer, manifest and metadata normalize into explicitly unverified typed records;
ordinary/project modes, artifact membership, module variants and link arguments are
checked. This does not verify file contents, executable status, receipts or leases.
Next: source receipt validation and artifact acceptance dependencies; full package
adapter integration remains unfinished.


Source-export validation adds 128 PHP/native outcomes and retained symbol comparisons
(`results/source-export-validation-01`). Exact capability/operation associations,
complete role membership, stable external symbols and physical pointer ABI checks
are proved. Nonzero stack address spaces reject lifecycle use. No export worker/join
or receipt acceptance is implied. Next: project receipt validation against these
accepted export facts; package artifact verification remains a separate dependency.


Project receipt authorization adds 122 PHP/native outcomes (`results/project-receipt-01`).
Exact captured receipt bytes and current source export contracts authorize the union
of required imports across all four artifact variants, retaining exact operation
identity and stable symbol order. Producer empty-map encoding is accepted explicitly;
nonempty lists do not substitute for maps. Next: package lease/artifact acceptance and
adapter composition. SHA-256/executable-query target dependencies and source-export
production/join remain separate unfinished work.


Runtime_Lease and the named framework Lock_Reservation add 18 PHP/native outcomes
with independent-process lock contention (`results/runtime-lease-01`). Transfer,
alias invalidation, exact package retention, idempotent release and scope/exception
cleanup pass. PHP lifetime proof disables Xdebug reference retention; native cleanup
is separately executed. Next: accepted package composition and artifact-verification
integration; pinned-target SHA-256/executable-query dependencies remain outstanding.


Package semantic composition adds 26 PHP/native outcomes (`results/package-composition-01`)
and the changed Record_Import's 42-case native regression passes. Type retention,
record-map publication, storage families, catalog/default identity and callable import/
retention now connect through one pure owner. The contract set does not authorize
artifacts. Next: artifact verification/adapter integration, or independent source-export
production while the SHA-256/executable-query target dependencies remain unresolved.


Source-export work/reuse/join adds 24 PHP/native scenarios (`results/source-export-work-01`).
Lifecycle ABI preparation retains accepted operations; stable import creation, full/
incremental selection and complete private-batch acceptance are proved. Capture from
current type/layout stores, export-body verification and coordinator integration are
still absent. Next: source identity/layout capture dependencies. Artifact hashing and
executable-file verification remain separate package-adapter blockers.


Selected layout capture adds 42 PHP/native graph scenarios (`results/layout-capture-01`).
Actual canonical stores now yield immutable reachable storage DAGs, sorted membership,
exact node reuse, subsets and lineage/configuration-gated currentness. Shared paths,
cycles, incremental replacement and a 513-node chain pass. No layout measurement is
claimed. Next: layout selection/measurement dependencies and source identity projection
before complete export capture; package hash/executable APIs remain separate blockers.


Layout selection/storage spelling adds 42 PHP/native outcomes
(`results/layout-selection-01`). Full, incremental, partial reuse, foreign lineage
and changed configuration selections preserve exact task provenance and command
arguments. Scalar/floating/opaque/array/record spelling, shared DAGs and a 201-node
chain pass. Native alignment is requested for reachable opaque storage. Next:
native witness generation, target layout measurement and result acceptance; source
identity projection and complete export capture remain incomplete.


Native layout witnesses add 35 PHP/native outcomes (`results/layout-witness-01`).
Generated C++ shells and primitive maps agree, including shared/deep DAGs and
unsupported floating constituents. Clang 18 folds 27 generated witnesses; independent
x86_64 size/alignment/offset expectations and LLVM integer GEP facts agree. Shared
Layout_Order now serves both spellings. Next: compiler-side measurement output
validation, result acceptance and tool execution; complete export capture remains
unfinished.


Measurement-output validation adds 76 PHP/native outcomes
(`results/layout-measurement-01`). Exact unique target headers, bounded folded i64
facts and primitive ABI agreement now precede private Layout_Result construction.
The corpus includes all 27 previously measured Clang witnesses, ordinary LLVM
storage, malformed/duplicate/missing facts, integer limits and escaped target text.
Next: layout batch acceptance and compiler-side tool execution. No complete
measurement worker, export capture or compiler pipeline is claimed.


Layout batch acceptance adds 37 PHP/native scenarios (`results/layout-join-01`).
Exact selected tasks, complete result membership, current layout identity, native
provider facts and unchanged inputs on rejection are proved. Completion order does
not change publication order; removed roots disappear and unselected current owners
are retained. Next: compiler-side measurement tool execution and coordinator
integration; no complete backend pipeline or source-export capture is claimed.


Compiler tool execution adds 16 PHP/native scenarios (`results/tool-run-01`), including
real Clang output and independent descendant cleanup checks. One dt_sleep_ms facade
supports non-busy polling; managed process tokens own resource cleanup. Next: remove
the obsolete launcher fact from compiler layout selection and connect probe source,
execution, measurement validation and batch acceptance. The preserved PHP runtime
preparation/tool service is unchanged.


Complete selected layout-worker execution adds 23 PHP/native scenarios
(`results/layout-worker-01`). Capture/selection, real Clang folding, primitive ABI
verification, measurement validation, batch join and incremental identity reuse now
compose. Instrumented command counts prove branch selection and early target failure.
The obsolete layout launcher field is removed; managed processes own isolation.
Toolchain discovery/coordinator integration and source-export identity/capture remain
unfinished. Next: source identity/provider symbol dependencies; package artifact
hashing/executable verification remain separate target API dependencies.


Provider declaration carriers add 67 PHP/native outcomes
(`results/provider-declaration-01`). Five authoritative provider payload kinds now
have checked access and explicit exact-owner retention, including regenerated member
wrappers and receiver passing checks. This replaces the union at the boundary;
shared Symbol_Record origins, namespace-aware indexing and provider-aware name
bindings remain to be integrated next. The carrier alone is not provider publication.


Shared source/provider symbol integration now passes 24 PHP/native scenarios and
the complete 68-stage native gate (`results/symbol-origins-01`). Explicit origins,
namespace indexes, exact owner retention, source-only task membership and provider
template argument roles compose without fabricated syntax. Provider families bypass
source-definition permissions; source templates still require them. Ready-file count
remains 156 because this changes existing owners. Next: provided-record bindings
and symbolic interpretation; package artifact acceptance and full coordinator execution
remain unfinished.


Provider-record bindings add 19 PHP/native scenarios; affected lookup (40), lexical
resolution (320), and project resolution (182) native regressions pass. Exact record
identity now participates in lookup and incremental validity. See
`results/provider-record-bindings-01`. Readiness remains 156 files. Next: symbolic
template interpretation, then template body checking and result orchestration.


Symbolic template interpretation adds 32 PHP/native scenarios
(`results/symbolic-interpretation-01`). Formal substitution, constant provenance,
source/provider fields and methods, provider signature mapping and exact dependency
capture are proved. Native required two builds and one STAN return-shape correction.
Two production files bring readiness to 158. Next: template body checking, then
selection/reuse and complete result acceptance. Concrete instantiation remains separate.

The existing symbolic-model native regression (40 outcomes) also passes after the
identity-field rename. The next body-checker slice must account for the prototype
Source_Lifecycle::role dependency; its full bodies/signature validation also serves
later concrete type preparation. Preserve that semantic owner when adapting it.


Template body checking adds 46 PHP/native outcomes and 20 host-only purity/task
assertions (`results/template-body-01`). Generic field, local, branch, expression and
declared-call permissions now compose through Terms. Source_Lifecycle supplies the
shared reserved-method role query; full lifecycle body/signature normalization remains
unfinished. Native attempt one passed without correction. Ready count is 160 files.
Next: template task selection/reuse and complete result acceptance, followed by
concrete instantiation/type preparation. No complete analysis pipeline is claimed.


Template selection, reuse and batch acceptance add 22 PHP/native scenarios and
13 host-only invariants (`results/template-project-01`); existing symbolic-model
PHP/retained-oracle coverage (40 outcomes) also passes. Removed dependencies now
invalidate permissions without throwing during lookup. Native build one passed
without correction. Ready count is 163 files. Next: concrete argument binding and
instance publication dependencies. Full coordinator/Step integration remains unfinished.


Instance registry, snapshots and shared concrete read views add 29 PHP/native outcomes
(`results/instance-registry-01`). Lineage-bound allocation history, new-work draining,
application/type lookup and snapshot isolation pass. Nullable interface returns remain
a converter restriction; concrete read handles preserve the shared query contract.
Native attempt three passed after two corrections. Ready count is 167 files. Next:
bound annotation consumption and concrete argument binding; application/member joins
and full concrete-preparation orchestration remain unfinished.


Bound annotation/concrete argument reading adds 30 PHP/native outcomes
(`results/concrete-bindings-01`); the 14-case existing definition-view PHP regression
also passes. Exact source/provider identity, pending application/record definitions,
integer literal/constant/parameter reading and attributed rejection are proved.
Native attempt one passed without corrections. Ready count is 169 files. Next:
literal-constant batch preparation, then explicit application argument normalization
and instance joins. No constant expression execution or complete pipeline is claimed.


Literal-constant workers and batch acceptance add 22 PHP/native scenarios and
10 host purity assertions (`results/constant-batch-01`). Exact typed values, integer
limits, normalized spelling, source provenance and complete task-ordered acceptance
are proved. Native build one passed without corrections. Ready count is 170 files.
Next: application argument normalization and its storage-element eligibility
dependency; preserve the Storage_Definitions owner when adapting that rule.
No expression evaluation or complete concrete-preparation pipeline is claimed.


Application argument normalization and storage-element eligibility/materialization
add 25 PHP/native scenarios and 23 host invariants (`results/application-arguments-01`).
Ordered arguments, explicit missing prerequisites, exact storage ownership and private
candidate isolation are proved. Native build two passed after one field/method naming
correction. Ready count is 173 files. Next: application acceptance and instance joins;
storage callable signatures and full preparation orchestration remain unfinished.


Application batch acceptance adds 32 PHP/native scenarios and 16 host invariants
(`results/instance-join-01`). Complete provenance checks precede ordered allocation;
identity reuse, pending prerequisites, concrete-context provenance and storage
publication pass. Native build two passed after one fixture naming correction. Ready count is 174
files. Next: concrete member-instance preparation and acceptance; callable signatures
and complete preparation orchestration remain unfinished.


Concrete member workers and acceptance add 28 PHP/native scenarios and 20 host
invariants (`results/member-instances-01`). Bound receiver annotations, source/provider
method ownership, inherited arguments, pending work and exact current/previous reuse
pass. Native build one passed without correction. Ready count is 177 files.
Next: concrete source-record preparation and acceptance, including its lifecycle-body
dependencies; callable signatures and full preparation orchestration remain unfinished.


Source/provider record preparation and acceptance add 33 PHP/native outcomes and
27 host invariants (`results/record-preparation-01`). Field/extent normalization,
lifecycle-body IDs, ordinary/template/provider provenance and complete batch
acceptance pass. The 46-case template-body PHP regression also passes after the
shared role-map refactor. Native build one passed without correction. Readiness is
181 files. Next: concrete callable signature preparation and its lifecycle validation;
full preparation queues and coordinator execution remain unfinished.


Concrete callable signature requests add 33 PHP/native outcomes and 26 host invariants
(`results/signature-requests-01`). Source parameter passing, exact lifecycle receiver/
copy types, imported/prepared method references and all six storage roles pass. Native
attempt two passed after one STAN return-flow correction; only it reached C++ build.
Readiness is 187 files. Next: callable/type-resolution result records and retained
signature validity, then signature selection and batch publication. Complete type-stage
and coordinator execution remain unfinished.


Callable provenance and local-type result records add 18 PHP/native outcomes and
23 host invariants (`results/type-result-records-01`). Exact declaration/instance
ownership, explicit provider origins and copied local-type membership pass.
Native attempt two passed after one fixture qualification correction; one C++
build. Readiness is 188 files. Next: signature validity and selection/publication,
plus the completed type snapshot's accepted-family associations. No complete
Type_Resolution or type-stage execution is claimed.


Signature selection, reuse and canonical publication add 32 PHP/native outcomes
and 28 host invariants (`results/signature-publication-01`). Complete provenance
validation precedes writes; deterministic publication, shared unchanged signatures,
and stale canonical/source/provider/instance rejection pass. Native attempt one
passed without correction. Readiness is 192 files. Next: complete type snapshot
assembly and its accepted-family associations, then local-type preparation/join
and coordinator integration. Signature_Set alone is not a completed type stage.


Core type-snapshot assembly and family result carriers add 26 PHP/native scenarios
and 26 host invariants (`results/type-snapshot-01`). Entry/local/signature/
instance/package identity, language/conversion indexing and copied membership pass.
Native attempt one passed without corrections. Readiness is 194 files. Construction
lookup and debug serialization remain to migrate, along with local-type preparation/
join, family preparation service acceptance and complete type-stage coordination.


Local type selection, workers and final association join add 22 PHP/native scenarios
and 26 host invariants (`results/local-types-01`). Signature parameter
prefixes, nested/template locals, full provenance acceptance, unchanged reuse and
local-only invalidation/rebuild pass. First native attempt passed without corrections.
Readiness is 198 files. Next: construction lookup and debug projections, then complete
concrete-preparation queue/coordinator dependencies. Full analysis is not yet runnable.


Construction lookup and association debug projection add 12 PHP/native scenarios
and 57 host invariants (`results/construction-types-01`). Read-only exact definition
lookup, attributed rejection, source/provider/receiver/local/family projection and
snapshot purity pass. Native attempt two passed after one fixture correction;
production passed its first C++ build. Readiness is 200 files. Remaining complete-dump
serializers are inventoried in type_debug_projection_inventory.md. Concrete queues,
family preparation acceptance and full analysis coordination remain unfinished.

Preparation queue dependency: single hash-slot removal is now proved in PHP/native
(`results/keyed-removal-01`), including missing keys, unaffected entries and fixed
member keys. Converter/checker rejection regressions pass; first native attempt
passed without corrections. Readiness stays 200 files. Next: migrate the typed
preparation queue and prove scheduling, dependency edges and payload release.

Concrete preparation queue adds 31 PHP/native scheduling assertions and a host-only payload-release check (`results/preparation-queue-01`). First native attempt passed without corrections. Readiness is 202 files. Next: concrete coordinator dependencies, including family preparation acceptance, layout/source-export services and bounded instantiation policy. Full analysis execution remains incomplete.

Bounded instantiation policy adds 22 PHP/native cases and retained-prototype agreement (`results/instantiation-policy-01`). First native attempt passed without correction. Readiness is 203 files. Host-provided data paths replace source-location assumptions; default session discovery remains unwired. Next: remaining coordinator dependencies and concrete body-checking consumers; complete analysis execution is still pending.
