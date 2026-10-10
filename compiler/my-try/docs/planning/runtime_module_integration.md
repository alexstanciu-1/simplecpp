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
- Phase 2 remains in progress: registered properties and owner-template
  member recipes are still required. Do not replace owner recipes with enumerated
  concrete applications. Next checkpoint starts with shared property/storage facts.
- Phase 6 export/import audit must address runtime type aliases: direct language-scope
  lookup works, but `use ticket as TicketAlias;` currently cannot resolve the runtime
  export through namespace import lookup. This is not covered by the member proof.
- Deferred borrowed source views retain CH15-RUNTIME-SOURCE-01.
- Provider semantic conflicts retain their existing debts; no silent contract changes.

## Property prerequisite — approval boundary

Inspection found that `prepared_storage` requires a `collected_name`, property writes
manufacture a `prepared_binding` carrying that source declaration, and lifetime and
C++ field paths consume those facts. A metadata property has no source declaration.
Making that field optional or fabricating source AST would conceal the mismatch.

Before implementing registered properties, approve a coordinated refactor across
preparation storage/assignment facts, lifetime consumers and C++ field bindings:

- Introduce a source-independent storage/property contract; source fields and
  registered properties provide real identities through it.
- Keep source declaration ownership on source records, not on every storage target.
- Separate property-write facts from local-declaration binding facts while retaining
  the shared assignment conversion and value-boundary checks.
- Let lifetime analysis consume storage provenance; backend-owned bindings supply
  runtime property spelling. No fake source declaration and no type-name cases.

The recommended option is this shared foundation, then runtime properties. The
smaller alternative is to defer property support explicitly and finish other phases;
it does not complete the originally planned property functionality. Existing source
field writes, reference/lifetime rejection and runtime field reads/writes need focused
proofs because the refactor touches established source behavior. Broad/native-compiler
validation remains deferred under the existing testing debt. This prerequisite is
pending approval, not implemented and not counted as phase completion.
