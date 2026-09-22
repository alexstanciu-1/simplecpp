# Historical pipeline boundary review
Doc Status: supporting

Date: 2026-09-07. Historical review against the previous compiler's docs,
active code, and selected retired v1 code. This preserves boundary analysis,
not a second current specification or an implementation claim. The
[current pipeline](../compiler_pipeline.md), [incremental rules](incremental_refresh_rules.md),
and [first-slice plan](first_slice.md) govern current work. The broad acceptance
list below is a future inventory, not prerequisites for the first slice.

The [original list](compiler_pipeline_original.md)
is preserved. The [comparison](compiler_pipeline_comparison.md)
records the evidence, coverage, differences, and limits.

## Main steps

These steps describe dependencies, not a mandatory whole-project barrier after
every step. A request asks for analysis, backend IR, objects, or a linked
program. It stops once that product is available, then publishes the result.
Cached outputs can satisfy steps without rerunning them.

1. **Read and validate the project manifest and request.** Normalize source
   roots, explicit files, exclusions, entry policy, dependencies, enabled
   languages/modules, target, build options, and requested output. Resolve
   dependency locations and configuration; package installation is external.
2. **Load and validate compiler/runtime metadata.** Establish the versioned
   authority for runtime types, operators, conversions, callable signatures,
   capabilities, ABI carriers/symbols, and enabled modules. Reuse validated
   generated catalogs where appropriate; do not regenerate them per file.
3. **Open or reuse the compiler session.** Obtain committed source, symbol,
   type, dependency, and backend state. Validate compatibility with the request
   and metadata. Optionally restore compatible persisted caches. An empty
   session is the cold-compilation case.
4. **Discover the project file tree.** Reconcile configured files and roots
   with the previous source set. Give files stable identities; detect additions
   and removals; exclude generated output directories. A rename may initially
   be represented correctly as removal plus addition.
5. **Refresh source snapshots and confirm content changes.** Read new or
   possibly changed files, including supplied editor buffers when that input
   mode is supported. Keep source bytes, source versions, hashes, and line
   indexes. File events and timestamps are hints; they do not alone prove
   semantic change. Preserve unchanged source snapshots.
6. **Start a transaction and select initial work.** Combine source, manifest,
   metadata, target, and missing-output changes with retained dependencies and
   stage cache keys. Invalidate affected products and queue required work.
   Subsequent stages may discover additional affected consumers.
7. **Tokenize changed source.** Produce compact tokens with source spans.
   Reuse valid token lists. Language-specific tokenizers own lexical rules.
8. **Parse into the common frontend model.** Produce declarations, statements,
   expressions, and type syntax, preserving source ownership and recoverable
   diagnostics. PHS and JSS frontends converge here; supporting a frontend
   requires its own proof before acceptance.
9. **Collect declarations and scopes.** Establish source, namespace, import,
   type, function, member, callable, and local identities and declaration
   indexes. Record declared signatures for resolution. Represent supported
   top-level executable code and initializers as explicit body owners with an
   entry/startup ordering policy.
10. **Resolve names and references.** Bind names to scopes and declarations,
    including cross-file references, members, and callable candidates. Diagnose
    missing, duplicate, ambiguous, or inaccessible declarations. Record lookup
    dependencies, including unresolved lookups that a later declaration could
    satisfy.
11. **Resolve types, constants, and generic instances.** Intern canonical
    TypeRefs and structured type/value arguments; evaluate required constant
    expressions; check family constraints; materialize demanded instances and
    callable/type signatures. Record value and layout dependencies. Recursive
    resolution must terminate or diagnose invalid cycles. This is not a full
    C++ template engine.
12. **Check operations and select semantic contracts.** Type expressions,
    choose calls/operators/conversions, match arguments and returns, and check
    storage, wrapper, range, object, and runtime/module capabilities. Record
    accepted or blocked decisions and their providers. Source legality belongs
    here and in the adjoining semantic analyses, before backend emission.
13. **Build shared typed executable bodies.** Represent values, places
    (addressable storage), calls, evaluation order, blocks, and control-flow
    edges. Functions, methods, closures, and synthetic initialization bodies
    use the same body model when supported. Type checking and body construction
    may be interleaved; these are logical responsibilities, not duplicate IRs.
14. **Analyze dataflow, storage, and lifetime.** Check definite initialization,
    scope, return paths, branch/loop merges, reference/escape rules, ownership,
    copy/move behavior, and cleanup obligations. Cover normal and nonlocal exits
    (return, break, continue, and supported exception/suspension forms).
    Unsupported obligations remain explicit blockers.
15. **Stabilize dependencies and check readiness.** Compare changed public
    surfaces, bodies, values, layouts, and metadata-dependent facts; update
    forward/reverse dependencies; schedule affected work until no changed
    semantic output requires more processing. Gate the requested next stage on
    its real prerequisites. Analysis may succeed for a backend-blocked feature;
    a blocked operation must not silently produce executable code.
16. **Lower accepted bodies and data.** Translate shared bodies, constants,
    globals, and layouts into explicit lower-level operations, copy/cleanup
    actions, runtime calls, and target ABI conventions. Preserve source and
    authorization provenance. Required closure/exception/async transformations
    belong to explicit lowering passes when those features are supported.
17. **Plan backend units and runtime demands.** Select owned function/module/
    object partitions and compute their dependency/cache inputs. Collect entry
    wrappers, globals, runtime symbols, demanded bridge specializations, and
    link requirements. Partition policy may change without changing semantics.
18. **Generate and verify backend representations and bridges.** Consume only
    completed lowering/ABI plans to produce LLVM IR through a text or future
    API sink. Produce demanded runtime bridge/shim code when required by the
    provider contract. Verify backend structure and symbol/signature agreement.
19. **Run the selected optimization pipeline.** Apply supported compiler IR or
    LLVM passes at their appropriate representation boundary; verify their
    output. This numbered step names optimization ownership, not a requirement
    that every optimization occur after LLVM generation. Preserve effects,
    lifetime semantics, debug mappings, and optimization dependency inputs.
20. **Compile or reuse object files.** Build changed user-code and generated
    bridge objects with the configured backend/native toolchain. Check cached
    output existence and compatibility. Record failures and real tool results.
21. **Link or reuse the requested final artifact.** Combine the current object
    set with selected runtime/dependency libraries, entry/startup support, and
    link options. Account for deleted objects, changed libraries/options, and
    missing output even when source has not changed. Static/shared and future
    LTO profiles share the same semantic and ABI decisions.
22. **Publish the transaction and return results.** Publish a consistent model
    generation, dependency indexes, valid cache entries, diagnostics, and
    requested output references. Retire superseded state safely. Persist
    selected caches when configured. A failed build may publish useful analysis
    with explicit error/blocked status; it must never label an older executable
    as a successful output for the new generation.

## Services that apply throughout

The numbered path needs these owners to be complete. They are not extra
language passes, and their first implementations can be small.

| ID | Owner | Required responsibility |
|---|---|---|
| C1 | Session and transaction coordinator | One cold/warm transaction algorithm; committed generations; selected-work execution; coalescing and stronger dirty-reason upgrades; fixed-point completion; failure/cancellation without publishing mixed generations. Optional CLI/daemon/watch transports feed this owner. |
| C2 | Scheduler and publication | Dependency-aware tasks with stable ownership, immutable inputs, real result payloads, deterministic merging, isolated outputs, and bounded work. One-worker and multi-worker runs use the same contracts. Counts/hashes alone are not published compiler results. |
| C3 | Model storage, identity, and caches | Compact rows, source spans, interned names, indexed lookups, stable logical identities, exact identity checks, bounded scratch/resident retention, and safe retirement. Physical segments do not define semantic dependencies. Cache keys include schema/compiler/provider/target/options and actual dependency inputs; output paths alone are insufficient. |
| C4 | Diagnostics, readiness, and verification | Structured diagnostics with source anchors at every stage, recoverable partial models where valid, explicit unsupported boundaries, and stage-specific acceptance. Parsing, a readiness row, and native execution are distinct claims. |
| C5 | Query, source-map, debug, and export views | Analysis/STAN, hover, completion, definitions/references, symbols, locals, breakpoint/source mappings, and debugger consumers share compiler facts. Derive readable artifacts on request. Export settings must not change semantic behavior or secretly run a program. |
| C6 | Measurements and instrumentation | Measure actual work/reuse, timings, memory, worker publication, backend, and child-toolchain costs separately. Optional debug/profile plans may request instrumentation through normal lowering. Report cold, no-change, body, public-surface, metadata, and cache-miss scenarios separately. |
| C7 | Metadata/provider and native-toolchain boundary | Versioned generated finite catalogs; demand-driven open families; explicit ABI carriers, effects, ownership, and symbol providers. Compiler language semantics remain compiler-owned. Runtime helper implementations remain runtime-owned. Native command/cache/side-effect handling has one owner. |
| C8 | External development and execution tools | Build the compiler itself; generate catalogs; prepare tests; compare exports; run purity/architecture/performance checks. Running/debugging/profiling the resulting program is an explicit consumer action. Test identities/expected files never drive compilation. |

No requirement here mandates one class or one file per row, a generic pass
framework, duplicate readiness/lowering representations, or full serialization
of internal state. Create boundaries where they own real decisions, state,
reuse, or independently verifiable outputs.

## Incremental and parallel execution rules

- Initial change selection is provisional. Parsing/resolution/materialization
  reveal semantic changes; their consumers feed the same work queue until it
  reaches a fixed point. Legal recursion may need a dependency component;
  invalid constant/layout cycles need diagnostics.
- A body-only edit normally invalidates its body/lowering/backend owner. If
  inlining, constant evaluation, or another transformation consumes body
  contents, record that dependency and invalidate those consumers too.
- Adding/deleting a symbol can change previously failed or ambiguous name
  lookup. Dependency tracking must include lookup scopes/candidate sets, not
  just successful call edges. Remove obsolete dependencies when recomputing.
- A cache hit means compatible output was actually retained or loaded and
  used. Avoided disk writes and emitted reuse counters do not prove skipped
  parsing, analysis, lowering, or native compilation.
- The session owns semantic generations. Artifacts can be staged and promoted
  separately, but their recorded input generation and validity must agree.
  Readable exports can be produced at any safe stage, including failure.
- The original optional-daemon assumption is superseded: the current design
  requires a resident process retaining sessions across requests. A watcher or
  other transport is optional and must not own semantic invalidation.

## Scope and acceptance

The non-goal of this document is implementing all language features or choosing
every storage, transport, optimizer, and native-link mechanism now. JIT,
ThinLTO/full LTO, PGO, BOLT, detached daemon startup, persistent cache codecs,
and advanced dependency clustering have owners/extensions above but remain
separate future implementation decisions. PHS/JSS support, exceptions, async,
closures, objects, wrappers, and containers require accepted slices or explicit
diagnostics; having a place in this pipeline does not accept them.

Before claiming the architecture works, prove:

1. A small project reaches native output through shared semantic/body/lowering
   owners, with errors attributed to the right stage.
2. Cold and warm compilation use the same transaction API; a no-change request
   reuses real outputs and performs no unnecessary frontend/backend work.
3. A body edit changes the program result and only the required owners; a
   signature/value/layout change invalidates actual dependent consumers.
4. File/symbol addition and deletion update name resolution and the link set,
   including a formerly unresolved reference that becomes resolvable.
5. Metadata/target/option changes invalidate affected stages; missing objects
   or executables rebuild without requiring a source edit.
6. Two requests against one retained session work; failure followed by repair
   does not expose stale executable success or inconsistent model generations.
7. One-worker and multi-worker execution consume real payloads and produce
   equivalent semantics, diagnostics, and deterministically normalized outputs.
8. Calls, expressions, loops, and owned values compose through shared bodies;
   the backend sink cannot inspect frontend syntax to select special cases.
9. Analysis-only and export-on/off requests share semantic results; exports
   cause no native execution. Explicit execution is tested separately.
10. A metadata-backed runtime call has matching native symbol/ABI/ownership
    behavior; an unsupported contract is diagnosed rather than guessed.
11. Nested family/type/value arguments use the shared materializer and record
    dependencies; invalid constraints and cycles are diagnosed.
12. Repeated edits release superseded state; timings and memory are measured
    from the actual path, with compiler build cost separated from target build.
