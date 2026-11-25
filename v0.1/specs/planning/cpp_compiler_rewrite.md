# C++ compiler rewrite discussion
Doc Status: planning

Reset: 2026-10-26.

This document replaces today's earlier rewrite plan with the direction agreed in
the renewed discussion. The earlier runtime-exclusion decision and implementation
proposals are removed. This is a discussion baseline, not an implementation schedule
or a change to current language semantics. The initial build foundation is now
implemented in `compiler/my-try-cpp/`, followed by the initial static catalog-model
startup slice. Source compilation is not implemented yet.

## Agreed direction

Rewrite `compiler/my-try/` in authored C++, using Simple C++ runtime types and
libraries through concrete middleware interfaces. Runtime extensions may be made
when concrete needs justify them. Reuse selected runtime components through narrow
interfaces rather than including a runtime umbrella throughout the compiler.

A later port to Simple C++ is an explicit design consideration. Keep compiler
algorithms and data contracts expressible through the runtime's concepts; isolate
C++ representation and integration machinery in runtime/middleware owners. This
direction does not establish that a later port will be automatic or fully supported.

Most of the rewrite should remain close to existing my-try. Apply lessons learned
through explicit, concept-correct improvements to the relevant owners. Existing
implementation details are not mandatory merely because they already exist.
Preserve parsing/collection, shared semantic preparation and backend ownership;
identify and discuss changes to their contracts before implementation. Language
specifications remain authoritative over both implementations.

## Template boundary

Compiler source files and their transitive included headers must be template-free.
Use concrete prepared classes such as `token_shared` and `vector_token`, generated
or handwritten. A type alias exposing a template instantiation does not satisfy
this boundary.

Template instantiation belongs inside separately compiled runtime and middleware
implementations. Their concrete interfaces prevent compiler translation units from
repeating that work. Reuse the existing middleware concept and functionality;
inspect its suitability before deciding what must be extended. Actual dependency
changes must still rebuild affected implementations.

## Runtime and compiler ownership

- Runtime: scalar and string types, ownership handles, containers, errors and
  reusable facilities such as filesystem, JSON and synchronization.
- Middleware: concrete specializations and representation bridges backed by runtime
  implementations, with template-free compiler-facing declarations.
- Compiler: tokens, syntax, symbols, semantic facts, dependency tracking and stage
  algorithms.

Compiler code uses runtime library/type interfaces rather than introducing a
parallel standard-library-based support layer. Exact concrete spellings and the
permitted C++ authoring subset remain to be specified. General-purpose additions
belong in the runtime; compiler-specific structures remain in the compiler.

## Memory safety discipline

- No manual allocation/deletion, pointer arithmetic or unchecked casts in compiler
  code; low-level machinery belongs behind reviewed runtime/middleware boundaries.
- Use concrete owning handles for retained ownership and weak handles for
  non-retaining links where appropriate. Acquire an owning handle before weak access.
- Check collection bounds and presence. Do not retain interior references across
  mutations that can invalidate them.
- Initialize required fields before publication; represent absence explicitly.
- Clean up resources automatically, including on exceptional paths.
- Give shared mutation explicit synchronization or exclusive ownership. Reference
  counting alone does not protect record fields.
- Preserve shared record identity initially; a wholesale arena/ID lifetime redesign
  is not selected.

These are a restricted C++ safety discipline, not a claim of static memory-safety
proof. Focused lifetime/invalidation checks and sanitizer-backed evidence should
validate implementation slices. Memory lifetime and semantic validity are distinct:
retaining a record does not make retired preparation facts current.

## Files and dependencies

Keep handwritten and generated code files under 200 KB, ordinarily much smaller.
Split by responsibility and dependency boundaries. The exact byte convention for
automated enforcement remains open.

Include only required declarations. Use forward declarations where valid, concrete
handles that can name incomplete records, and out-of-line implementations. Keep
runtime bridge implementation details out of compiler-facing headers.

## Lessons from my-try — 2026-10-26

The user considers my-try a success. Generated C++ fragmentation enabled very
fast recompilation after edits. Outside preparation, concurrency and performance
were good. Preserve these achievements; preparation needs particular attention.
This assessment is the starting point, not a claim of new measurements.

The following lessons are an open inventory; the user will add more as remembered.

### Development pace and visibility

The user identified insufficient close oversight of implementation as a lesson:
provide opportunities to steer structural decisions before they accumulate.
Slices need not be small; substantial coherent slices are appropriate. The failure
was leaving performance testing until roughly 99% of the code existed, not slice
size itself. Review and measure during development. The exact review cadence
remains to be discussed; this does not require approval for every routine edit.

Build multithreading into the implementation from the beginning. Demonstrate real
high concurrency while developing the relevant stages, rather than deferring it
until a serial implementation is complete. Apply the same early-development
discipline to incremental operation.

Check MT and incremental behavior at relevant structural changes and checkpoints,
not after every edit. Changes to scheduling, shared mutation, publication,
dependencies, invalidation and retained state warrant focused evidence. Most
ordinary edits do not require repeating those checks. Take time to evaluate the
structure before extending it.

### Fast compiler development loop

Keep compilation of the compiler itself fast, separately from the compilation
speed of programs it generates. Aim for compiler rebuilds under 15 seconds most
of the time. Some edits genuinely affect many translation units and may take
longer, but these should be uncommon. This is a target, not a measured guarantee;
machine, build mode and representative edit cases remain to be defined.

Keep routine tests limited, focused and fast. Select checks that prove the touched
behavior; broader validation belongs at relevant checkpoints. Record test time
separately so a fast rebuild does not hide a slow development loop.

### Memory and allocation discipline

Memory use was higher than desired. Design frequent retained structures carefully:
small per-record costs accumulate across the compiler. Measure actual layouts,
record counts and retained/peak memory rather than judging only source declarations.
Preserve safety and identity contracts while reducing avoidable storage overhead.

Avoid unnecessary allocations and repeated string concatenation. Prefer a reusable
buffer/builder for text assembly. The user expects an existing framework facility;
verify the appropriate runtime/framework API before selecting or adding one.
No particular allocator, pool, packed layout or buffer API is selected yet.

### Early measurement

Measure performance during early slices: stage elapsed time, allocation counts
and volume, concurrency, and retained/peak memory. The user reports that preparation
became by far the most expensive stage, exceeding parsing plus symbol collection
combined. Treat preparation performance as a primary design concern from the
beginning, with measurements of its internal work rather than only total pipeline
time. This observation does not yet establish which preparation mechanisms caused
the cost.
Measure actual worker activity and serialization rather than equating configured
thread count with useful concurrency. For incremental checks, inspect work repeated
after representative changes as well as correctness of the resulting artifacts.

Use bounded representative workloads and focused instrumentation so measurement
itself does not dominate the development loop. Establish baselines early; exact
workloads, tools and thresholds beyond the rebuild target remain open.

### Existing comparison baseline

The comparison workload and measurements already exist from 2026-10-25, linked
from `compiler/my-try/docs/README.md`. Reuse them rather than inventing a replacement
baseline. The new rewrite has no compiler functionality yet; compare corresponding
stages as they become executable, and the whole pipeline once available.

- [Performance slices](results/compiler_performance_2026_10_25/README.md): fixed
  converted-compiler workload, native O2 on dev002 (Ryzen 9 5950X, 32 logical CPUs).
  Optimized 32-worker execution/publication: 12.54–12.56 seconds, approximately
  1,008,004–1,012,204 KiB peak RSS. Frontend approximately 1.0 second, preparation
  5.1 seconds, parallel rendering 1.06 seconds. These exclude Clang compilation.
- [Partitioning report](results/compiler_partition_profile_2026_10_25/README.md):
  generated-unit sizes, phase timings and partitioning tradeoffs.
- [Production native pool](results/compiler_native_pool_2026_10_25/README.md):
  actual Clang concurrency, cold/warm production builds, resource measurements
  and second-generation execution evidence. These measure a different boundary
  from compiler execution/publication and from ordinary compiler edit rebuilds.

Keep workload, revision, configuration and measurement boundary attached to each
comparison. The under-15-second routine compiler rebuild target is distinct from
the existing approximately 12.5-second compiler execution result. New workloads
are needed only where these existing fixtures do not exercise the relevant slice.

The user clarified that incremental compilation was not pursued in the old
implementation. Its incremental results are not a relevant comparison baseline
or an acceptance target for the rewrite. Reuse the old workload and its applicable
performance/concurrency measurements; define and prove incremental correctness,
invalidation scope and recomputation costs independently in the new implementation.
Generated-C++ rebuild efficiency does not establish semantic incrementality.

## Build foundation — 2026-10-26

The user approved the initial project at `compiler/my-try-cpp/` with Ninja, Clang
and Mold. CMake generates separate Ninja projects for the runtime, middleware and
compiler. Explicit commands publish matching dependency headers/static libraries;
normal compiler builds consume those packages and never rebuild dependencies.
Middleware pins its runtime package. The initial handwritten concrete facade uses
the runtime text builder behind a template-free header. No general specialization
generator or compiler stage is introduced.

See the [setup and update commands](../../compiler/my-try-cpp/README.md) and
[bootstrap evidence](../../compiler/my-try-cpp/docs/bootstrap_validation.md).
The initial local profile is Linux/C++23/O2 without LTO or PCH. This bounds the
bootstrap evidence; it is not a cross-platform acceptance claim.

## Next discussion

The [initial startup slice](../../compiler/my-try-cpp/docs/startup_slice.md) now runs
configuration, catalog graph loading/validation and static input-model publication.
Its `catalog_metadata_ready` state does not claim full metadata-schema/semantic
registration, source compilation or multithreaded preparation. The linked contract
records checked fields and remaining prerequisites explicitly.

The [startup trace](../../compiler/my-try-cpp/docs/startup_review.md) follows my-try's
host entry, runtime metadata validation, model installation and source discovery.
It identifies the first functional startup scope. Model ownership is now agreed:
one process-global static Model, incremental status/revision updates on retained
records, and bounded previous/candidate versions where needed rather than two
complete models. Lifecycle remains responsible for initialization/reset/retirement;
worker mutation and publication protocols still need explicit contracts.
Project package references are now
relative, with a successful moved-project build using existing dependency archives.

Concrete naming decisions and the initial implementation are recorded in the
[middleware naming contract](../../compiler/my-try-cpp/docs/concrete_names.md):
registered families, explicit short mappings, trailing underscore reserved for
generated names, and preorder depth markers. The old SHA256 naming proposal is
not selected for concrete type names. Dependency package content hashes remain
separate build identities. String and vector_string facades are implemented;
integer short names are registered without claiming integer facade support.

- Extend the lessons inventory and identify the owner of each intended improvement.
- Agree the C++ authoring subset with a later Simple C++ port in mind.
- Inspect reusable middleware functionality and identify concrete runtime gaps.
- Select the first compiler functionality and the broader middleware generation approach.

No allocator/pool design, scalar-width policy, build-system recipe, platform matrix
or migration schedule is carried forward as an approved decision. Those choices
will be discussed when their requirements are concrete. Existing migration/reset
work elsewhere in the repository is separate and is not reset by this document.
