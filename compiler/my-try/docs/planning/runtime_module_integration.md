# Runtime module integration
Doc Status: planning

Approved 2026-10-10. This plan owns the completion sequence; the existing
[coverage inventory](runtime_catalog_coverage.md) owns individual API status.
Normative contracts remain in specs/runtime_module_metadata.md and owning API specs.

## Completion boundary

Ordinary runtime modules integrate through metadata and provider implementations,
without function/type-name branches in compiler processing. Templates are recipes,
never enumerations of element combinations. Metadata is decoded once into typed
records; registry resets reuse it. Shared preparation owns semantics, backend
bindings own spelling, and the build consumer owns compilation/linking/cache/PCH.
Every audited public asset must be registered, explicitly deferred, or internal.
This is not a promise to implement every deferred runtime feature.

## Sequence and checkpoints

| Phase | Bounded deliverable | Proof |
| --- | --- | --- |
| 1 — storage | Registry-owned runtime inline/shared policy; distinct handle and payload copying; ordinary null/reference/Result paths | Unfamiliar noncopyable provider payload, alias identity, empty state, Result and rejection of unauthorized payload construction/copy |
| 2 — members/factories | Register member contracts and backend bindings; reuse lookup/access/call admission; no fabricated source AST | One provider with property, method, constant and controlled factory |
| 3 — protocols | Move existing initialization/indexing/iteration declarations behind metadata-driven registration, using existing protocol models | Generic unfamiliar container, no enumerated applications or named worker branches |
| 4 — signatures | Metadata defaults, variadics and callable inputs using existing callable contracts; settle ambiguous reference overload bindings | One compact positive/negative fixture per new signature mechanism |
| 5 — build composition | Selected dependency closure supplies provider sources/link requirements to build owner; compatible PCH and precise invalidation | Small optional provider, cold/warm/body/config change |
| 6 — inventory/closeout | Reconcile public module inventory, remaining hard-coded facts and deferred prerequisites; concise operator documentation | Representative multi-module integration and configuration rejection |

Implement and commit coherent checkpoints. Existing free factories remain ordinary
callables; do not invent a second construction engine. Shared-handle proof initially
admits provider factories, not arbitrary new/value/ownership transformations of
opaque provider payloads. Broader ownership forms require explicit support.
Production module batches follow the relevant foundation, rather than interleaving
one API at a time. Use file locks, JSON documents and process outputs as concrete
consumers where their prerequisites are met.

## Validation budget and debt

Use the shared runner, a small affected PHP set, and generated-C++ proofs at actual
representation boundaries. No automatic whole-compiler native rebuild, broad suite,
or exhaustive cross-product testing. Add tests for discovered correctness failures.
Track extended portability, fuzzing, large catalogs, all API edges, full ownership
matrices and sustained build performance as CH15-RUNTIME-INTEGRATION-TESTS-01.
Memory-safety or false type admission is not deferrable for enabled behavior.

## Progress

- Phase 1 implemented: optional inline/shared policy, registry-owned per-definition
  facts, existing handle representation/null/reference paths and Result transport.
  Copying a shared value requires no payload copy capability. Explicit opaque
  payload construction/ownership transformations remain rejected.
- Focused evidence: /tmp/runtime-handles-20261010-3 (three PHP suites and three
  generated-program executions). The unfamiliar C++ payload deletes its copy
  operations; identity, slot replacement, Result and vector transport are proved.
- Phase 2 callable checkpoint: type-owned static/instance callables now use
  optional membership metadata and the existing concrete/generic signatures.
  Indexed membership is separate from free-function publication. Ordinary
  receiver retention/inline borrowing, argument admission and C++ binding paths
  are reused. Static factories need no special construction implementation.
- Focused callable evidence: /tmp/runtime-members-20261010-final; affected PHP
  handle/type/source-method/static-member suites plus five generated programs.
  Receiver replacement, generic inputs, observed temporary inputs, null-before-
  arguments and static qualification are covered.
- Phase 2 constant checkpoint: optional owner resolves through the registry;
  type-owned constants reuse settled-value facts and ordinary constant lowering.
  Their index does not publish member names into free scope. Methods and constants
  may share a name. Invalid owners/duplicates reject configuration transactionally.
- Focused constant evidence: /tmp/runtime-member-constants-20261010-final (four PHP
  suites and six generated programs). Includes source constant initializers,
  expression qualifier effects, same-named methods and failed configuration recovery.
- Phase 2 property checkpoint implemented after explicit approval: source-independent
  property storage and property-write facts, source-local lifetime membership, and
  metadata instance/static fields with backend-only bindings. Read-only access and
  heap-reference restrictions share the ordinary property path. No fake source AST.
- Focused evidence: /tmp/runtime-properties-20261010-final (four PHP suites, seven
  generated programs) and /tmp/storage-foundation-20261010-final (four affected PHP
  suites). Broad/native-compiler validation remains the existing testing debt.
- Phase 2 template-callable checkpoint: memberships index definition identities;
  owner arguments seed the existing generic matcher before input inference. One
  provider identity serves every application, including defaults and independent
  method type parameters. Static factories qualify the backend payload application.
  Type-qualified syntax now shares the existing type parser/AST, with contextual
  self/parent/static handling remaining analysis-owned.
- Focused proof: /tmp/runtime-owner-methods-20261010-final (four PHP suites and two
  generated programs). Covers exact owner/argument agreement, registration identity,
  invalid recipe rejection, defaulted owners, nominal member and free-generic regression.
- Phase 2 template constants implemented: lookup/duplicate checks use definition
  identity. Every application shares the same exact integer constant; type-dependent
  values are not introduced. Focused evidence: /tmp/runtime-owner-constants-20261010-final
  (two PHP suites, two generated programs), including nested/defaulted owner use,
  qualifier effects, identity sharing, duplicate rejection and no free-name leakage.
- Phase 2 complete within its bounded contract: generic property recipes share
  callable type substitution, stable storage identities are materialized per owner,
  and backend bindings remain definition-owned. No enumerated registrations.
  Focused evidence: /tmp/runtime-owner-properties-20261010-final (four PHP suites,
  three generated programs): generic/nominal reads and writes, inline borrowing,
  read-only rejection, nested types, static application isolation and recipe identity.
- Phase 3 indexing/iteration checkpoint: core vector/hash declarations moved into
  their type metadata; one startup reference resolver installs the existing protocol
  records. A distinct provider `tray<T>` exercises int/string applications, copied
  reads, writes/appends, value iteration and copy isolation without named compiler
  branches. Independent opt-ins, malformed metadata, reset/no-op and unsupported
  probes/removal/construction have focused PHP proofs. Provider headers implement
  the existing helper ABI; metadata does not prove C++ implementations.
  Evidence: /tmp/runtime-protocols-20261010-1 (existing indexing/iteration/type PHP
  suites; initial provider test corrected to avoid an unrelated compound-expression
  limitation) and /tmp/runtime-protocols-20261010-2 (provider/type PHP suites and two
  generated C++ programs), plus /tmp/runtime-protocols-20261010-final (independent
  opt-in assertions). No native compiler rebuild.
- Phase 3 complete within the existing protocol model: initialization recipes and
  separate C++ adapters now come from metadata too. Exact formal constraints retain
  core hash shape/key policy; overlapping recipes and missing/orphan adapters fail
  startup. The unfamiliar container proves list/keyed/empty/nested construction,
  followed by ordinary indexing/iteration. Internal input lifetimes and boxed
  language registrations remain unchanged. Closed or owner-parameter protocol
  references reuse the current bounded model; nested open references remain deferred.
  Evidence: /tmp/runtime-initialization-20261010-1 (initialization/runtime-types PHP),
  /tmp/runtime-initialization-20261010-2 (protocol/data-preparation PHP and two generated
  programs). The older data_construction parser suite fails its pre-lambda rejection
  expectation; this is tracked with extended testing debt, not a protocol pass.
- Phase 4 scalar defaults checkpoint: typed scalar metadata resolves to the existing
  source-independent callable default snapshots, with no fabricated AST or call
  to preparation from registration. Fixed and inferred-generic calls accept omitted
  optional inputs; exact default types, optional ordering and ambiguity are checked.
  Defaults never infer missing generic variables. Provider C++ functions without
  native defaults prove that ordinary lowering materializes the supplied values.
  Evidence: /tmp/runtime-defaults-20261010-1 (three PHP suites) and
  /tmp/runtime-defaults-20261010-cpp (generated executable after fixture-only C++
  type corrections). No native compiler rebuild.
- Phase 4 variadic/reference checkpoint: fixed and inferred generic packs reuse
  vector packing and source copy checks. Exact overload matching handles optional
  prefixes and packs; ambiguous empty packs reject. Generic reference inputs and
  concrete reference overloads use ordinary place/access/lifetime admission, without
  pass-mode ranking. Shared admission rejects move syntax when a pre-prepared
  argument resolves to a borrowing/copying consumer. Reference returns remain deferred.
  Focused evidence: /tmp/runtime-signatures-20261010-2 (three PHP suites and a generated
  executable); /tmp/runtime-signatures-20261010-final additionally covers an empty
  generic pack inferred from a fixed argument and the existing method-parameter
  suite, with another generated-program pass. No native compiler rebuild.
- Phase 4 complete within the existing callable contract: structural callback
  metadata interns ordinary function types; recursive patterns infer callback
  input/results without combinations. Functions and callback types share signature
  records and concrete parameter registration. Closed/contextual and generic/overloaded
  callbacks reuse existing erased conversion, including copyability and borrowed
  capture rejection. Callback reference inputs describe invocation, not capture
  lifetime. Value callbacks may be retained as owning copies. Reference returns,
  borrowed erased callbacks and speculative contextual overload typing stay outside
  the established contract.
  Evidence: /tmp/runtime-callbacks-20261010-1 (three PHP suites and generated C++);
  /tmp/runtime-callbacks-20261010-final adds a contextual reference-parameter callback,
  repeats the generated proof and checks runtime type registration. No native compiler
  rebuild.
- Next: Phase 5 module build composition and Phase 6 coverage/export audit.
  Broader validation remains CH15-RUNTIME-INTEGRATION-TESTS-01.
- Phase 6 export/import audit must address runtime type aliases: direct language-scope
  lookup works, but `use ticket as TicketAlias;` currently cannot resolve the runtime
  export through namespace import lookup. This is not covered by the member proof.
- Deferred borrowed source views retain CH15-RUNTIME-SOURCE-01.
- Provider semantic conflicts retain their existing debts; no silent contract changes.

## Property prerequisite — approved and implemented

Inspection found a source-declaration assumption in storage, property-write and
backend facts. The user approved its removal on 2026-10-10, preserving semantics.

The implemented separation spans preparation, lifetime consumers and C++ bindings:

- Introduce a source-independent storage/property contract; source fields and
  registered properties provide real identities through it.
- Keep source declaration ownership on source records, not on every storage target.
- Separate property-write facts from local-declaration binding facts while retaining
  the shared assignment conversion and value-boundary checks.
- Let lifetime analysis consume storage provenance; backend-owned bindings supply
  runtime property spelling. No fake source declaration and no type-name cases.

Existing source field writes and runtime properties now use those common facts.
The provider fixture proves same-named properties/methods/constants, shared identity,
static storage through empty handles, source inline-field borrowing, and preparation
rejections. This completes the property prerequisite, not the overall phase or goal.
