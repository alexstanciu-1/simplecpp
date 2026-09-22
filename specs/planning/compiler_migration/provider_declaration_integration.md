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
