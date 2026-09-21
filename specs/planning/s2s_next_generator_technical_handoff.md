# Next S2S: semantic inputs and incremental C++ generation
Doc Status: planning

Date: 2026-09-20

Audience: implementers of the next semantically informed Simple C++ S2S and its
build driver. This is a technical handoff from the compile-latency assessment,
not a new language specification or an implemented compiler API. Normative
language specs retain authority over semantics.

## 1. Decision and accepted performance envelope

Adopt the experimentally demonstrated output architecture: stable implementation
units, individual type headers, per-callable declarations, precise dependencies,
and incremental publication of changed artifacts. Supply it with resolved
semantic information rather than reproducing the experiment's lexical heuristics.

**The user accepts the measured results, including the approximately 30-second
compound edit.** Approximately ten seconds remains the common-edit target, not
an absolute limit for coordinated changes. The 28-source compatibility/ownership
cleanup took 32.680 seconds native, or 34.180 seconds with the assumed frontend.
That result is acceptable for this assessment; reducing it is not a prerequisite
for proceeding. This clarification supersedes earlier planning statements that
classified this particular result as outside the acceptable envelope. It does
not establish a universal limit for every future workload.

| Evidence | Result | Interpretation |
| --- | --- | --- |
| Twelve runnable historical code edits, two frozen samples | 36/36 trials within target; 1.593–4.815 s native medians | 3.093–6.315 s including assumed frontend |
| Coherent two-source change `41392c03` | 194.008 s prior screen → 3.955 s median | Seven objects plus link; no project PCH rebuild |
| Coherent 28-source change `9103637e` | 193.489 s prior screen → 32.680 s successful screen | 78 objects plus link; accepted broad-change result |
| Comment-only historical edit | 0.082 s native median | No native compilation required |

Frontend analysis plus C++ generation is **assumed to take 1.5 seconds**, not
measured. Native timing includes Ninja scheduling and full linking; do not add a
second scheduling allowance. The compound number is one successful slow screen,
not a median. Coverage retains sixteen exact-context exclusions and one
incompatible before-state. These are results for the studied workload, not a
statistical guarantee for arbitrary projects or saves.

Sources: [coverage and exact compound attribution](s2s_compile_latency_minimal_coverage.md),
[general type-publication results](s2s_compile_latency_minimal_type_publication.md),
[raw coverage summary](../../tools/compile_latency/results/2026-09-20/minimal-coverage/summary.json),
[coherent replay evidence](../../tools/compile_latency/results/2026-09-20/minimal-types/coherent/summary.json).

## 2. What must be provided to the next S2S

A typed AST alone is insufficient unless the generator can query resolved
bindings, representation semantics, dependencies and changes. The following is
the proposed input contract; field names and serialization are implementation
choices, not existing APIs.

| Required information | Contents | Why the emitter needs it |
| --- | --- | --- |
| Stable program identity | Module/source, symbol, type and callable IDs; overload identity; revision; source-to-symbol mapping | Preserve names, paths and unit membership across unrelated edits |
| Resolved types | Field order/types, class versus value-record semantics, generics, bases, qualifiers, nullability and relevant attributes | Emit faithful representations and distinguish declarations from complete definitions |
| Resolved expressions | Binding of each name/call/member, selected overload, instantiated type arguments, result type, implicit conversions, value/reference category and temporaries | Find dependencies absent from textual type names |
| Parameter and lifetime semantics | Value/reference passing, readonly constraints, writable aliases, ownership, copy/move/destruction and escape requirements supported by the language | Preserve behavior when choosing C++ signatures and outlining code |
| Callable contracts | Parameters/returns, defaults, calling/dispatch rules, templates/inline requirements, externally visible linkage and required adapters | Separate public declarations from execution bodies without losing required definitions |
| Runtime operations | Resolved helpers/builtins, conversions, checks, allocation, iteration, exceptions, task/callback support and feature requirements | Include the required runtime declarations and implementations |
| Diagnostic identity | Stable source/file/function identity and source-span/location mapping | Update diagnostics without renaming or recompiling unrelated bodies |
| Semantic changes | Added/removed/changed identities; body, contract, representation, inline/template, constant and location changes; previous revision mapping | Classify invalidation and detect new/deleted artifacts |
| Build context | Language/runtime ABI versions, target, native toolchain, flags, feature configuration and external dependencies | Make artifacts and caches reproducible and correctly invalidated |

The frontend owns language meaning and resolved facts. The S2S owns the lowering
plan, C++ representation/ABI choices, artifact placement and native dependency
edges derived from those facts. Do not require the frontend to guess C++ header
paths or reproduce a particular partition algorithm.

A trusted semantic snapshot plus a previous snapshot may replace an explicit
change feed if differences can be computed within the agreed frontend budget.
Stable identities must not depend on source line numbers, traversal position or
a global counter that shifts after inserting an unrelated declaration.

### Concrete dependencies that must not be missed

- A returned value or `auto` temporary may require a complete type without naming
  that type in the consuming source.
- `state->diagnostics->error_count` requires the intermediate diagnostic type,
  not only the type of `state`.
- A value record inside a vector/table can require complete definitions and copy
  or destruction operations in its consumer.
- Inline/template adapters require their normalizers, execution declarations and
  any definitions used in the header body.
- A changed C++ parameter-passing contract requires rebuilding its callers even
  when their source text is unchanged.
- Construction, field access, base classes, layout-sensitive operations and
  instantiated inline/template bodies can require definitions; an existing
  shared handle may need only a forward declaration. Exact rules depend on the
  generated C++ operation, not merely the source type name.

The first four were exposed by actual failed experimental generation/compilation
attempts. See [compound value dependencies](s2s_compile_latency_compound_values.md),
[general publication setup failures](s2s_compile_latency_minimal_type_publication.md)
and [adapter boundaries and reference behavior](s2s_compile_latency_adapter_boundaries.md).
Inheritance and general template lowering were not demonstrated by these bounded
experiments; support them through truthful semantic rules and dedicated proofs.

## 3. The S2S-owned artifact and dependency model

Maintain a versioned output plan rather than directly streaming files from AST
traversal. A minimal conceptual record is:

```text
Artifact
  stable_id, kind, owner_symbol_ids, path
  final_content_hash, lowering_version, build_context_fingerprint
  dependencies[]
    target_artifact_id
    requirement: declaration | complete_definition | inline_definition |
                 runtime_interface | diagnostic_data | native_link
    reason: call | field_access | inferred_result | value_member | ...
  native_unit_id / exported_symbols, where applicable
```

These are proposed records. Dependency requirements are part of the emitter's
C++ model; a plain list of identifiers found in source is not a substitute.
Record reasons so a slow rebuild can be explained and audited. Track source-level
contracts separately from lowered C++ ABI/representation fingerprints. A target's
ABI fingerprint must account for relevant target/toolchain configuration, not
just field names.

The plan should distinguish:

- type declarations and complete definitions;
- callable declarations, inline/adapter closures and execution bodies;
- location data and other volatile metadata;
- runtime interfaces and native compilation/link inputs.

Publish a manifest mapping each source/symbol to its emitted artifacts, each
artifact to its dependencies, and each implementation unit to its native object.
Every required definition must have exactly one correct owner. Every expected
object must appear once in the full application link. Presence, absence and
removal are states of this same model, including cleanup of obsolete declarations.

Reference implementations to study, not copy as semantic parsers:
[minimal type layout](../../tools/compile_latency/minimal_type_layout.py),
[type publication](../../tools/compile_latency/type_publication.py),
[callable surfaces](../../tools/compile_latency/callable_surface.py),
[adapter surfaces](../../tools/compile_latency/adapter_surface.py),
[stable callable groups](../../tools/compile_latency/callable_groups.py).
The experiment's metadata is bounded and sometimes conservative; the next S2S
must replace its class lists, token scans and historical aliases with resolved
facts. Names of experimental files are not prescribed production architecture.

## 4. C++ output rules to carry forward

### Individual types and narrow declarations

Emit one definition header per type. Do not include an all-project-type header,
grouped type inventory or project-type PCH in native consumers. Give each header
and implementation unit only the declarations/definitions its operations need.
Local forward declarations are appropriate where valid. Necessary transitive
complete-type dependencies remain explicit.

The final experiment publishes all 485 observed project types this way. Its 1052
active implementation units have a median of one directly included complete type
and a maximum of 25, before transitive includes. Those counts are observations,
not fixed design limits. A new independent type must not modify a global type
inventory consumed by every unit.

### Stable, reasonably small implementations

Separate callable declarations from execution bodies and unrelated class members.
Use deterministic, stable groups of complete callables where useful; one file per
function is not a requirement. Do not repack all groups after a nearby insertion
or small size change. Persist membership or use an equivalent stable scheme and
make deliberate repartitioning observable.

Static helper extraction was demonstrated for a bounded set of owners. Preserve
instance dispatch, inheritance, external symbols and other language contracts when
extending the model; do not flatten every class into free functions blindly.
Keep required inline/template definitions narrowly published with their callable
closure. Templates and runtime checks must not be deleted to obtain faster builds.

### Stable names, diagnostics and final publication

Allocate generated locals and helper IDs within stable semantic owners. Keep
source-location data separate from method bodies where possible, preserving
reported file, function and line identity and call-depth checks. A comment that
changes locations may legitimately update diagnostic data even when code meaning
is unchanged; do not promise every comment edit is a complete no-op.

Compute final artifact contents in staging and publish each changed file once.
Preserve bytes and timestamps for unchanged artifacts. Do not write a broad
header and then narrow it in place: that can invalidate native objects even if
the final bytes return to their original contents. Publish a consistent artifact
set and build manifest before scheduling native compilation.

Evidence: [stable-output and diagnostic lessons](s2s_compile_latency_lessons.md),
[location metadata implementation](../../tools/compile_latency/location_metadata.py),
[real diagnostic verification](../../tools/compile_latency/verify_locations.py),
[final idempotence and restoration checks](../../tools/compile_latency/results/2026-09-20/minimal-coverage/final-integrity.json).

## 5. Required invalidation behavior

| Edit | Expected affected artifacts/native work |
| --- | --- |
| Non-inline body change | Owning implementation unit(s), changed location data if any, then link |
| New independent callable/type | New owned artifacts and actual consumers; no unrelated project inventory invalidation |
| Callable contract/ABI change | Declaration/adapter closure, implementation and affected callers |
| Type representation change | Definition and all consumers requiring that layout/complete type, including inferred uses |
| Inline/template/compile-time constant change | Actual definition consumers and required instantiations |
| Location-only change | Diagnostic data and whatever is needed to preserve accurate diagnostics |
| Symbol/source deletion | Remove obsolete graph/link entries and declarations; prevent stale objects from satisfying the build |
| Runtime/toolchain/flags change | All affected artifacts, PCHs and objects through the build-context dependency |

“Compile only changed files” means changed **or transitively invalidated native
units**. It does not mean passing only edited authoring files to Clang. Let Ninja
schedule the valid dependency graph. True layout and ABI changes cannot be made
safe by ignoring dependency edges or reusing incompatible objects.

The compound audit demonstrates legitimate unchanged callers: seven objects
consume three changed callable headers whose shared-handle parameters change
from const-reference to value. See the [exact header/input analysis](../../tools/compile_latency/results/2026-09-20/minimal-coverage/compound-analysis/analysis.json).

## 6. Representation and runtime correctness constraints

Preserve shared-object identity and aliasing, independent value-record copies,
vector/table contents, writable-reference behavior, ownership/lifetime rules,
runtime checks and diagnostics. Do not turn a value record into a shared object
or erase parameter semantics as a compile-time optimization.

Accessors or out-of-line boundaries can be useful for highly reused mutable
structures, but require separate runtime evaluation. One earlier debug counter
microbenchmark measured 15.4% overhead through accessors; this is not a whole-
application regression estimate. Per-type publication later demonstrated that
many layout edits need no representation change or accessor layer.

Current mixed-reference restrictions must be preserved until the owning language
and runtime contracts change. The adapter study verified all 52 emitted
normalizers, including typed mutation and the existing mixed-reference rejection.
A future type-aware emitter may specialize only where resolved semantics justify
it; it must not silently enable a previously rejected aliasing path.

References: [adapter proofs](s2s_compile_latency_adapter_boundaries.md),
[value-copy publication](s2s_compile_latency_value_publication.md),
[counter tradeoff](s2s_compile_latency_over20_solutions.md),
[final representation/adapter witnesses](../../tools/compile_latency/results/2026-09-20/minimal-coverage/final-proofs/).

## 7. Build-driver contract

Use the measured debug setup as the starting reference: Clang 18, C++23,
`-O0 -g1`, Ninja, mold, and a runtime-only PCH. The measured host has six physical
cores/twelve logical CPUs and used twelve jobs. Interpret the user's CPU-core-job
preference as available logical CPU concurrency here, not a permanent hardcoded
12 for every machine; respect actual CPU/memory limits and verify on deployment
hardware. Increasing the job limit cannot create more ready units or remove a
single-unit critical path. The studied workload did not improve by blindly
increasing jobs beyond twelve.

The driver must preserve native depfiles, toolchain/runtime dependencies, complete
link inputs, correct addition/removal of units, and reproducible flags. Record
ready/rebuilt units, compiler-job durations, PCH activity and link time. Surface
why an apparently unchanged unit rebuilt.

Fresh-edit measurements were made with compiler caches disabled. Cache hits from
ccache or sccache may be an additional benefit, but are not required for the
accepted results. Cache keys/invalidation must include the relevant toolchain,
flags, runtime/system-header contents, PCH and external configuration. The cache
miss path must remain usable. Earlier ccache experiments do not establish a
production-safe cache policy or validate sccache equivalently.

Source: [scheduling, linker and cache findings](s2s_compile_latency_lessons.md).
Do not mistake early experimental commands using sixteen jobs or a project PCH
for the final adopted output/build configuration.

## 8. Validation package to deliver with the implementation

Provide these reviewable outputs alongside the next S2S:

1. A documented semantic-input contract and lowering/dependency model, with
   explicit handling or rejection for unsupported constructs.
2. A stable artifact manifest, explainable invalidation report and correct native
   build graph, including addition/deletion and clean-build behavior.
3. Full-program correctness proofs: shared identity, value copies, references,
   adapters/checks, native exports/link ownership and accurate diagnostics.
4. Incremental tests for body changes, new symbols, signatures, field layouts,
   inferred results, nested members, inline adapters and location-only edits.
5. Replays of the frozen history samples and both coherent cases, preserving
   exclusions and fixture limitations. Match the semantic source states and
   compiler/runtime context before comparing results.
6. Idempotent generation and native no-op checks, plus content/hash evidence that
   unrelated artifacts remain stable and no project-type umbrella is reachable.
7. Fresh-edit timing reports with setup/generation, native compilation/linking,
   cache replay and runtime-performance effects distinguished.

The current reference workload is pinned at compiler revision
`9776900f6c305729ae6ef8474052363508913315`. Reproduction must use an isolated copy
of `/home/alexv/__AI/simple_cpp_compiler/compiler/src`; the original remains
read-only. Do not depend on `/tmp` paths surviving. Durable evidence and frozen
policy source copies are in the repository archives linked below.

The scratch harness preserves the full application link and adds smoke witnesses.
Coherent replays reconstruct historical source except the unchanged instrumented
main, using the current generator/runtime. They are not reproductions of the
original historical toolchain. The three-record/twenty-field `7d58f273` analogue
is useful value-copy evidence, but it is not the complete twelve-source historical
feature change. Its full replay remains a coverage gap, not a blocker imposed by
this handoff on the accepted architecture.

## 9. Optional follow-ups, not acceptance prerequisites

The accepted roughly 30-second case need not be optimized further to proceed.
Promising subsequent experiments are:

- Narrow runtime headers to resolved direct/transitive needs. Two isolated Clang
  traces spend about 85–90% in the frontend, with substantial template work.
  The runtime umbrella imports PHP regex adapters even with that backend disabled;
  backend-disabled status alone does not prove a header can be omitted.
- Compile primitive location data without the full runtime PCH, while preserving
  every exported symbol/value and diagnostic mapping.
- Study reuse of repeated template work, partition sizes and runtime boundaries
  where profiling warrants it. Validate behavior and runtime cost before adoption.

These have not been demonstrated as end-to-end speedups. They must not delay the
measured architecture or be presented as completed capabilities. If a needed
boundary requires a wide refactor across production ownership areas, report the
scope and validation cost before undertaking that refactor.

## 10. Evidence index and implementation boundary

| Reference | Use in the next S2S |
| --- | --- |
| [General publication study](s2s_compile_latency_minimal_type_publication.md) | Final per-type output shape and two coherent timings |
| [Coverage and attribution](s2s_compile_latency_minimal_coverage.md) | Twelve runnable cases, limits, real ABI fan-out, compiler traces |
| [Coverage archive](../../tools/compile_latency/results/2026-09-20/minimal-coverage/) | Raw trials, captures/diffs, graph manifests, frozen policy sources and proofs |
| [Coherent archive](../../tools/compile_latency/results/2026-09-20/minimal-types/) | Before/after historical states and the accepted compound screen |
| [Compound value study](s2s_compile_latency_compound_values.md) | Inferred value dependencies and faithful copy semantics; analogue limits |
| [Adapter study](s2s_compile_latency_adapter_boundaries.md) | Per-callable adapter closure and actual reference/check behavior |
| [Lessons](s2s_compile_latency_lessons.md) | Earlier failures, stable output, grouping, diagnostics and build tuning |
| [Experiment README](../../tools/compile_latency/README.md) | Tool entry points, setup, replay modes and archive organization |

The current production PHP S2S is still type-blind. The experiments establish
bounded output feasibility and coverage; they do not supply an integrated
resolved frontend or a general C++ parser. Do not transplant the experimental
regular expressions or class-specific metadata as the next compiler's semantic
model. Build the artifact rules from the supplied resolved program, preserve the
language contracts, and use the archived experiments as acceptance evidence.
