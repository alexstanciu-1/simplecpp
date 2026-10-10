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
- Phase 2 remains in progress: registered properties/constants and owner-template
  member recipes are still required. Do not replace owner recipes with enumerated
  concrete applications. Next checkpoint starts with shared property/storage facts.
- Deferred borrowed source views retain CH15-RUNTIME-SOURCE-01.
- Provider semantic conflicts retain their existing debts; no silent contract changes.
