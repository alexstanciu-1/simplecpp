# Runtime metadata static storage — 2026-10-25
Doc Status: planning

Base c49149ba7 plus candidate hashes. User approved private nullable static storage
and requested remaining compile/run blocker recap. Reuses private/static/nullable
field support and required value conversion. Model now owns runtime_metadata_value,
require_runtime_metadata and set_runtime_metadata. Compiler_Lifecycle publishes only
at its existing final point after private validation; no lazy IO or dummy catalog.
Current consumers and test/fixture readers use the getter. Historical docs remain
historical. No language/runtime/converter extension or other required-root fix.

## Proofs

Extracted production field/getter/setter passes PHP, legacy and both v0.2 native
modes. Native task durations 31.13/34.08/28.97 s. Covers unconfigured failure, exact
identity, mutation visibility, throwing RHS keeping prior state and replacement
while the old handle remains retained. Payload is a dependency double; this is not
a full native metadata subsystem or native compiler rebuild.

Actual runtime_catalog, runtime_type_bindings, startup_metadata (including fresh
process changed-provider rejection) and token_cleanup pass. They prove rejected
configuration preserving prior metadata, no live replacement, retained input across
semantic reset and renewed derived identities. initialization has a stale expected
rejection and runtime_inventory rejects its null-expression storage expectation;
both reproduce against an isolated HEAD boot/production/test overlay. Not fixed
or counted passing. Shared runner uses CLI fallback because FPM sockets are denied.

## Bootstrap

96.26 s wall / 354944 KiB RSS (346.625 MiB) / 324 MiB PHP heap.
Task 104.94 s includes conversion/setup. Required runtime_metadata diagnostic removed;
required global_scope initialization is newly exposed and first. Preparation still
fails, so no complete compiler C++ emission/build/run is claimed. No memory failure.

## Current compile/run blocker recap

Fresh report has 96 distinct aggregate diagnostic strings, not 96 source sites or
independent defects. Shared wording collapses multiple owners; earlier failures
can mask later ones. 85 strings concern initialization (including enum defaults).
11 others concern runtime-call signature selection, stable assignment storage,
projected receiver lifetime, canonical integer operands, value probes, enum
comparison, concrete data construction targets, Result payload construction,
required-object null rejection, conditional branch types and ownership joins.

1. Initialization: next Model.global_scope, then other required static roots and
   Parser_Run.source_scope/other instance fields. Audit by lifecycle: promote
   existing inputs, compute inputs via factories where safe, or use checked nullable
   storage for truly staged values. Select enum defaults explicitly. Do not apply
   nullable storage indiscriminately or reorder parser/publication work.
2. Other preparation diagnostics: inspect actual source/types and existing contracts
   before classifying as source adaptation, converter/binding bug, missing documented
   v0.2 support or unsupported portable PHP. Message text alone cannot decide.
   Projected-receiver lifetime is an explicit current gate and needs focused review.
3. Unreached gates: complete project validation and whole compiler C++ emission with
   template prelowering; compile/link emitted compiler and support runtime; execute
   native compiler on a small fixture and compare with PHP, then compile its own
   converted source and validate outputs. These are outstanding acceptance stages,
   not established failures or a promised fixed point.
4. Chapter 15 registry/type-use audit remains a final-catalog blocker separately
   from the next operational bootstrap error. A successful binary alone would not
   close that audit or other agreed acceptance/debt gates.

Recommendation: batch-audit remaining initialization owners into source adaptations
before implementing more isolated fields; review the 11 other diagnostic families
in parallel planning, without authorizing new semantics or broad redesign here.
