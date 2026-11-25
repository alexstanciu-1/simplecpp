# S2S output solutions for the over-twenty-second cases

Doc Status: planning

## Scope and owner

This is an experimental native-output feasibility assessment, assuming a future
resolved AST and full type information. Production generator semantics, the
external compiler source tree, and runtime implementation remain unchanged.
The owning future concept is a **native dependency boundary**: each generated
implementation depends on the callable declarations and complete types it uses,
not every declaration or layout reachable through its source file.

The non-goal is a production semantic compiler migration, arbitrary C++ rewriting,
or a promise that all ABI changes rebuild under ten seconds.

## Two concrete output changes

1. Extend the already-tested callable emitter to `frontend_model_tables`.
   Emit independently named native functions for resolved non-inherited static
   methods, individual declaration headers, stable implementation identities,
   and source-location sidecar data. Keep source method names in diagnostics and
   preserve generated class identity helpers. A helper addition need not change
   the containing class header or project PCH.
2. Give the known `FrontendModelKernelCounters` type a narrow native boundary.
   It already travels through `shared_p<T>`. Retain the real class and fields;
   move `create<T>()` into an out-of-line factory, and lower direct field access
   through typed, reference-returning functions with one declaration per field.
   Consumers can pass the incomplete type in its existing shared pointer.
   Only the counter boundary implementation and identity implementation require
   its complete layout. No extra heap allocation, pointer layer, padding reserve,
   or guessed offsets are introduced.

The second technique adds function calls to field accesses in debug builds.
It is a selective policy for high-fanout reference types, not a recommendation
for every scalar field or value record. Native hot-loop performance is a separate
tradeoff to measure before production adoption; release output may choose direct
access and accept broader recompilation. By-value records, inheritance, native
exports, constant evaluation, inline template code and layout-sensitive operations
need distinct handling. They are outside this bounded counter experiment.

## Experimental implementation and safeguards

- `expanded_layout.py` accepts explicit table/counter isolation options.
- `counter_boundary.py` uses known type metadata, generated typed variable names,
  and code-span rewriting that preserves comments and strings. It is not a
  general semantic analyzer; the future generator must use resolved expression
  identities and reject unsupported operations.
- Counter fields are supported numeric fields of this one known class. Accessors
  return the original field by reference; they do not copy values or change
  object identity. Factories preserve the original `create<T>()` behavior.
- Complete output is transformed in staging, then published only if final bytes
  differ. Intermediate include removal must not cause timestamp-only rebuilds.
- All linked implementations see consistent definitions. The first conversion
  rebuild is setup cost; edit timing starts only after the converted baseline is
  fully built. Existing source edits and behavioral witnesses are reused.

## Validation and measurements

Use the existing debug Clang/mold/Ninja setup with twelve jobs and caches disabled.
Run each edit three times; record full executable readiness, rebuilt object set,
PCH work, existing application smoke witnesses, new behavior, and restoration.
Add the assumed 1.5-second future frontend allowance only after native timing.

The initial converted-baseline build took **189.766 s**, recorded separately.
The shared-counter edit completed in **3.069 / 3.597 / 3.373 s** native
(median **3.373 s**, versus the prior 181.194 s screening run).
Each trial rebuilt five objects plus the full executable, with no PCH rebuild.
The changed writer/reader witness and the restored application passed all three.

The first helper trial was 2.964 s. Later preliminary repetitions reused an
orphaned new-helper object, so they are retained as exploratory evidence but
excluded from the final comparison. The final series explicitly removes that
new callable's object before timing, ensuring each addition actually compiles.

Raw results are archived under
`tools/compile_latency/results/2026-09-19/over20-solutions/`.

## Mapping to the historical queue

- **548.5 s int64-plus:** table-helper isolation directly addresses one boundary
  in that historical patch. Changed parser signatures must still rebuild actual
  callers, but independent callable headers avoid class-wide invalidation.
  The entire historical patch is not replayed by the helper experiment.
- **333 s int64-minus/type traits:** combine per-callable declarations, stable
  implementation partitions and complete-type dependency classification. New
  value-row layouts can still require real work. No timing claim for the full
  historical change until it is reproduced.
- **321.01 / 246.45 s cleanup preflight and related edits:** the recorded pattern
  was one transpiled source triggering roughly 187 native objects. Stable helper
  declarations and implementation-only body artifacts address the suspected
  mechanism. Those owners were retired; an exact historical replay is needed to
  quantify improvement rather than borrowing timings from current analogous code.

## Production migration direction

The resolved semantic pipeline should export callable IDs/signatures, resolved
call edges, type-use categories (forward declaration versus complete definition),
field access identities, allocation sites, ABI/export constraints and diagnostic
source mappings. The S2S artifact planner owns stable method partitions and
per-boundary content hashes. The build planner owns Ninja edges and PCH membership.
Current adjacent ownership is PHP lowering in `generators/php/` and project-unit
packs/Ninja planning in `bin/project_services.php`; the experimental postprocessor
is evidence for a future typed emission path, not a regex feature to merge there.

Prioritize callable isolation and honest include dependencies first. Add selective
allocation/field-access boundaries only where real layout fanout justifies their
runtime cost. Keep genuine broad value-layout rebuilds visible and accepted.


## Final result and recommendation

| Same edit | Earlier native screening | New native trials | Median native | With assumed 1.5 s frontend |
|---|---:|---|---:|---:|
| Central table public helper + two callers | 141.744 s | 2.976 / 2.847 / 2.847 s | **2.847 s** | **4.347 s** |
| Shared counter field + writer/reader | 181.194 s | 3.069 / 3.597 / 3.373 s | **3.373 s** | **4.873 s** |

Both final series have identical work sets across their three trials: five objects
plus the complete executable. No runtime/PCH rebuild and no compiler-cache hits
are involved. Helper trials 4–6 explicitly remove the newly added callable object
before timing; trials 1–3 remain archived exploratory evidence. Baseline numbers
were single screening trials, so do not interpret speedup ratios as distributions.
The converted application links **864 objects**; the full executable is retained.

The counter complete-header dependency audit finds exactly **two active native
consumers**: its identity implementation and the generated boundary implementation.
All other production call sites use forward declarations, stable callable headers,
and per-field accessor declarations. This demonstrates how resolved type-use
information can avoid a real layout change leaking into hundreds of consumers.

Five paired debug microbenchmark runs (alternating order, five million shared
counter updates through aliases) gave median **0.0797585 s direct** versus
**0.0920496 s through accessors**, approximately **15.4% overhead in this artificial
tight loop**, or 12.29 ms extra. Both paths verify the same aliased object's final
value. This is not a measured whole-compiler slowdown, nor a release-mode result.
Use the boundary selectively; measure real hot paths before production adoption.

A forced call-depth diagnostic preserves `frontend_model_tables::none_id`, the
original source filename and line 12. All six edited/restored application checks
passed, final Ninja reports no work, external source hashes match, and scratch
non-entrypoint source files match the snapshot with no additional PHS files.

**Recommendation:** yes, these are reasonable S2S output strategies and both new
slow cases now fit comfortably below the target in this workload. Prioritize
callable-level artifacts and complete-type use classification as general emitter
concepts. Select allocation/field boundaries for frequently edited, widely passed
reference types. Do not generalize these measurements to every historical patch,
value-layout change, native ABI, template, or inherited/virtual method case.


## Follow-up: coordinated signature propagation

Nine parser signatures and sixteen calls across the frontend and incremental
parser proof source now have a measured current-workload analogue: **5.812 s
median native**, **7.312 s** including the assumed frontend, three trials with
fourteen objects plus full link and no PCH rebuild. Accepted LLVM exits with 42;
the changed policy rejects the paired fixture at the frontend. The original
arithmetic fixture is backend-blocked even in the retained original-object layout,
so this is not an exact historical int64-plus replay. See
[s2s_compile_latency_signature_propagation.md](s2s_compile_latency_signature_propagation.md)
for evidence, limitations, and remaining coverage.


## Broader coverage changes the consistency conclusion

The frozen-output study now accounts for all 15 independently selected recent
source commits. Nine executable-code cases were timed: **three within target**
(native medians 2.622–4.268 s) and **six over target** (single native screens
138.970–177.867 s). One comment-only case required no native compilation. Four
commits have context mismatches, and one has an incompatible historical before-state.

All six slow replays rebuilt the project PCH and changed public surfaces outside
the selected isolated owner set. The feasibility demonstrations remain valid;
**consistent application across the program is not yet demonstrated**. The current
prototype isolates four callable owners, while a structural inventory identifies
103 simple candidates (not a semantic eligibility proof), plus more complex
mixed-owner files. Do not turn the passing-commit fraction into a normal editor-save
success estimate: this is a small, refactoring-heavy commit sample with replay limits.

See [s2s_compile_latency_coverage_study.md](s2s_compile_latency_coverage_study.md) for
the complete matrix, protocol, validation limits and archived evidence. Next apply
verified ownership/dependency rules consistently, then rerun this unchanged corpus;
do not keep adding only bespoke passing cases.
