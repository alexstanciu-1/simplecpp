# Provider declarations before full symbolic template checking
Doc Status: planning

## Evidence and decision boundary

At checkpoint d2cf6f64, active Symbol_Record requires a non-null Parse_Result and
Declaration_Fact. Type_Catalog indexes named definitions but not normalized records.
Declaration_Lookup supports source families and scalar provided types. Name_Binding
has no provided-record category. These are completed earlier migration subsets, not
the final compiler declaration model.

The retained prototype's collect_symbols/data/structures.php admits exactly one origin:
source frontend or provider payload (runtime callable, storage family/function, generic
family/method), with qualified names. check_templates/terms.php and body.php read these
payloads to interpret provider signatures, default construction, exposed members and
generic requirements. Their checks cannot be preserved by inventing source frontends,
ignoring external branches or granting empty permissions.

## Recommended integration

Restore an explicit source/provider origin in the common declaration owner. Use a tagged,
typed provider payload rather than dynamic PHP unions. Keep source frontend access guarded;
provider declarations must never acquire fabricated syntax. Preserve stable symbol IDs,
exact immutable declaration/catalog identity, namespace identity and dependency tracking.

Implement in dependency-coherent checkpoints:

1. Migrate semantic provider references, signatures, generic family operations/requirements
   and source exposure records. Preserve the distinction between semantic declarations and
   native ABI/implementation readiness. These are real models, not marker placeholders.
2. Extend Type_Catalog with normalized provider records and exact duplicate/default checks;
   connect provider declarations through their real import/adapter owner. Keep the existing
   scalar language JSON schema separate from runtime/provider metadata.
3. Extend Symbol_Record/collection/indexing with explicit origins and qualified identity;
   adapt source-only consumers to guarded source access and preserve incremental reuse.
4. Extend name bindings, lookup, coverage and validity for provided records/families/methods.
   A provider spelling must resolve to its authoritative declaration, not a source surrogate.
5. Migrate Terms and Template_Worker, then task selection/join, proving both source-generic
   restrictions and provider-specific construction/call/requirement rules.

## Areas affected and risks

- type_model and provider import/adaptation: exact type/family identity and signatures;
- collect_symbols: origin, namespace/indexing, import membership and stable IDs;
- resolve_symbols: provider binding categories, coverage and stale dependency checks;
- consumers (entry/type/template work): source-only access must remain explicit;
- tests and portable authoring: nullable/tagged payload paths require native proof.

Primary risks are lost provider identity, accidental source access on provider declarations,
incorrect incremental reuse after metadata changes, and granting capabilities from native
storage instead of semantic declarations. The registry/template-checking stage must not be
advertised complete while these paths are absent.

Validation cost is materially larger than a leaf migration: retain current parser/source
symbol/name-resolution proofs, add import collision and exact provider binding tests,
compare clean/incremental updates, exercise source/provider generic success and rejection,
and run PHP/native at each coherent model checkpoint. Final shared-declaration integration
requires cumulative native validation, not merely the latest small fixture. Record actual
phase timings/corrective cycles rather than an unsupported calendar estimate.

## Smallest reasonable choices

- Recommended: integrate the provider declaration model now, then migrate the complete
  symbolic worker against the truthful shared model.
- Alternative sequencing: migrate explicitly source-only symbolic interpretation first,
  leaving provider-aware template checking and full instance integration visibly pending.
  This yields earlier source-only behavior but requires a second integration pass.

Non-goals: new language functionality, v0.1 target modifications, speculative ABI/backend
implementation, removing preserved prototype code, or converting src-runtime-preparation.

The user-supplied AGENTS.md operating instructions require confirmation for a wide
refactor across multiple ownership areas (the global rule is supplied in the task,
not repeated in this checkout's project-only AGENTS.md).
This proposal requests that decision before changing the existing shared declaration model.

## Approved scope

The user approved compiler-side integration on 2026-09-22. Keep the existing
PHP `src-runtime-preparation` implementation and its output contract unchanged;
adapt only the compiler consumer. The user also requested that the retained
preparation configuration locate this checkout through relative paths, replacing
the old external vendor checkout. This permits configuration wiring changes,
not a rewrite of the preparation tool or its output schema.
The earlier confirmation requirement is satisfied.

## Implemented dependency checkpoints

- Provider type references and semantic signatures: 35 PHP/native outcomes, plus
  PHP list-shape rejections (results/provider-semantics-01).
- Family declarations, operations/requirements/effects, source exposure and
  Family_Contracts: 51 PHP/native outcomes and 33 retained-validator cases
  (results/provider-families-01).

These establish ABI-independent semantic inputs. Catalog/import integration,
source/provider symbol origins, provider-aware lookup, and template workers
remain to be migrated; these checkpoints do not claim a working provider pipeline.

Normalized record catalog storage and Family_Adapter acceptance/exposure validation
are also proved (results/provider-catalog-01). This completes record lookup and
family-to-catalog mapping checks, not prepared-package JSON ingestion/composition.
Existing scalar and project-resolution native proofs pass on the extended catalog.

Prepared callable ABI records and semantic compatibility checks are now proved
(results/callable-abi-01). Exact extension/result/binding/conversion spellings,
borrow modes, span expansion and hidden-result slots are retained. Storage-family
contracts and package ingestion remain dependencies before complete provider imports.

Typed storage-family models and Named_Definition's exact descriptor/ownership
invariant are now implemented and proved (results/storage-contracts-01). Package
ingestion/leases and symbol-origin integration remain pending.

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
