# Dependency-Scoped Project Unit Headers
Doc Status: planning

## Summary

Large strict PHS/Simple C++ projects currently use one project-wide generated
force-include header, `.prism/generated/__project_units.hpp`, for every
generated/native C++ object in the same project. That header includes:

- `__project_fwd.hpp`
- every generated local project header
- generated `__project.hpp` export headers from transitive Prism project
  dependencies

This keeps same-project composition ergonomic, but it creates very broad native
rebuild fanout. A small PHS edit can update one generated header or the global
unit header and then every object that force-includes `__project_units.hpp`
becomes dirty.

The proposed fix is to replace the single broad force-include surface with
dependency-scoped project unit headers, using the same source-summary /
STAN-facing dependency knowledge that Simple C++ already computes.

## Implementation Goal

Implement dependency-scoped project unit headers end to end for issue 215:
precise enough dependency modeling to reduce rebuild fanout, deterministic
scoped pack generation for safe units, broad fallback for uncertain units,
continued `--no-stan` compatibility, source-first diagnostics, regression
coverage, and measured validation on a large strict PHS project.

## Evidence From v2 Compiler Work

In the v2 compiler project under `compiler/v2/src`, a small PHS edit recently
had this build shape:

```text
Transpiled PHP files: 3
Skipped unchanged: 383
Native objects rebuilt: 387
Fresh debug compiler build wall time: 398.3 s
```

A later no-change build was:

```text
Transpiled PHP files: 0
Skipped unchanged: 386
Ninja: no work to do
Wall time: 10.93 s
last_run duration_ms: 10804
```

That no-change sample was run with `--no-stan`, so STAN was not the cause of the
10.93 s sample. The saved timing breakdown only accounted for about 1.16 s:

```text
collect_project_php_files_and_s2s_state_ms: 1073
render_and_write_build_ninja_ms: 62
ninja_subprocess_ms: 26
```

The bigger pain point, though, is the fresh-after-edit native rebuild fanout.
The generated `build.ninja` currently applies the same force-include header to
each generated object:

```text
more_cxxflags = -include ../generated/__project_units.hpp
```

In the current v2 project, `__project_units.hpp` is about 390 lines and includes
all generated project headers. Editing a leaf-ish support file can therefore
make unrelated structure/frontend/backend objects dirty at the C++ level.

## Current Implementation Points

Current global project unit header generation lives in
`vendor/simple_cpp/bin/project_services.php`:

```php
function write_project_unit_force_include_headers(array $projectContexts): array
```

The current implementation merges all dependency headers and all local
generated headers into one project-wide header:

```php
$includeHeaders = array_merge(
    array_values(array_unique($dependencyHeaders)),
    sort_project_unit_include_headers(array_values(array_unique($localHeaders)))
);
...
$headerPath = normalize_path($projectContext['generated_dir'] . '/__project_units.hpp');
...
foreach ($includeHeaders as $includeHeader) {
    $lines[] = '#include "' . normalize_config_path(relative_path(dirname($headerPath), $includeHeader)) . '"';
}
```

`render_build_ninja(...)` already has a per-unit `force_include_header` field:

```php
$unitForceIncludeHeader = is_string($unit['force_include_header'] ?? null) ? $unit['force_include_header'] : null;
if ($unitForceIncludeHeader !== null && $unitForceIncludeHeader !== '') {
    $lines[] = '  more_cxxflags = ' . build_force_include_flags(...);
}
```

So the build graph already has the right hook. The missing piece is assigning
more precise force-include headers per generated unit instead of giving every
unit the same broad `__project_units.hpp`.

## Proposed Solution

Introduce dependency-scoped project unit force-include headers.

High-level shape:

```text
current:
  every .cpp -> -include __project_units.hpp
  __project_units.hpp -> all generated headers

proposed:
  every .cpp -> -include __project_units/<dependency-set-hash>.hpp
  each dependency-set header -> only the generated headers needed by that unit
  uncertain units -> fallback to existing broad __project_units.hpp
```

Keep `__project_fwd.hpp` global and stable. It provides namespace-correct
forward declarations and is cheap enough to include broadly. The dependency-set
headers should include complete generated headers only where the unit actually
needs complete declarations.

The dependency data should be produced as a build-planning artifact, not only as
a diagnostics artifact. STAN can enrich and validate it when enabled, but
`scpp build --no-stan` must still work. A good model:

1. Extract a lightweight per-source build dependency summary from the same
   frontend facts / summaries that STAN consumes.
2. Use STAN semantic classifications when available to improve precision.
3. Fall back to the broad global header for any source whose dependency set is
   incomplete, unresolved, or not yet modeled.

## Dependency Set Contents

A first practical dependency set can include:

- generated export headers from transitive Prism project dependencies, as today;
- `__project_fwd.hpp`;
- local generated headers for directly referenced same-project classes,
  structs, unions, enums, interfaces, traits, functions, constants, and other
  generated declaration providers;
- base classes before derived classes;
- complete field/member types required by value layout;
- function/method signature types where the generated C++ needs complete
  declarations;
- any headers needed by generated helper/lowering code for a source unit.

For initially uncertain cases, prefer correctness over precision. Use the
existing broad `__project_units.hpp` fallback.

## Suggested Implementation Plan

### Phase 1: Instrument And Report

Add build diagnostics before changing behavior:

- number of generated units using broad force-include;
- number of distinct force-include headers;
- force-include header byte/line counts;
- generated units dirtied after a PHS edit;
- explain-build view for "why this object includes this project-unit header".

This gives us a baseline and helps prove the later change.

### Phase 2: Dependency Summary Artifact

Emit a per-source build dependency summary without changing the generated
Ninja graph yet.

Example internal shape:

```json
{
  "source": "compile/support/compiler_profile_events.phs",
  "status": "precise|fallback_broad|blocked",
  "local_headers": [
    "structures/artifacts/compiler_profile_event_row.hpp",
    "structures/pipeline/fixture_run_report.hpp"
  ],
  "dependency_export_headers": [],
  "reasons": [
    "direct type reference",
    "function parameter type",
    "mutable report append"
  ]
}
```

This can be stored in S2S state / build state so no-change builds do not have to
recompute more than necessary.

### Phase 3: Generate Multiple Headers, Broad Equivalent First

Teach `write_project_unit_force_include_headers(...)` to generate headers under
something like:

```text
.prism/generated/__project_units/
  broad.hpp
  <hash>.hpp
```

Initially, these can be broad-equivalent while plumbing is verified. Assign
`generatedUnits[*]['force_include_header']` from the dependency summary, but
keep all content broad enough to prove the build graph change separately from
semantic narrowing.

### Phase 4: Narrow Safe Units

Start narrowing only for safe, measurable source categories:

- leaf helper functions;
- support/proof/report files with explicit type references;
- files with resolved inheritance, signature, function-body, or property-layout
  dependency evidence;
- files where STAN/source summaries can name all same-project dependencies.

Everything else keeps using the existing broad fallback.

### Phase 5: Extend To Layout/Inheritance-Sensitive Units

Handle complete type requirements explicitly:

- base-before-derived include ordering;
- by-value struct/union/class field types;
- enum backing/type references;
- method signatures and return/parameter types;
- generated declarations whose C++ header itself needs another generated
  header to be complete.

This should reuse or extend the existing `sort_project_unit_include_headers(...)`
logic rather than inventing an unrelated ordering system.

## Implementation Backlog

Status legend:

- `done`: implemented and committed
- `ready`: next practical implementation slice
- `planned`: known follow-up, order may shift as evidence comes in
- `blocked`: needs data or a prerequisite implementation slice

### Done

- [x] `PUH-001` Save the original issue locally as this planning note.
- [x] `PUH-002` Add project-unit force-include diagnostics, broad-equivalent
  hash packs, dependency summaries, and C0 scoped-pack candidate reporting.
  Commit: `74823dc Add project unit force-include diagnostics`.
- [x] `PUH-003` Activate scoped pack headers for generated PHS units classified
  as `candidate_scoped`; keep blocked generated units, native C++ units, and
  `--no-stan` builds on broad-equivalent fallback packs.
  Commit: `23b9b23 Activate scoped project unit packs for safe units`.
- [x] `PUH-004` Add active scoped/broad and candidate scoped/blocked fanout
  counters to saved reports and `scpp explain-build project-units`.
  Commit: `5d893d6 Report scoped project unit fanout counters`.
- [x] `PUH-005` Add candidate blocker histograms so common broad-fallback
  reasons are visible without scanning every source row.
  Commit: `003f5de Summarize scoped project unit blockers`.
- [x] `PUH-006` Annotate saved generated source rows and `generated-files`
  output with the active project-unit status, pack mode, and force-include
  header.
  Commit: `ea36c85 Annotate build sources with project unit packs`.
- [x] `PUH-013` Add stale scoped-pack cleanup and manifesting so obsolete
  `.prism/generated/__project_units/scoped-*.hpp` and broad hash-pack files do
  not accumulate after dependency sets change.
- [x] `PUH-020` Split the dependency summary loader from STAN state for the
  no-STAN build path. `--no-stan` now writes a build-owned lightweight
  project-unit dependency state from frontend summaries, keeps scoped activation
  broad-safe, and reports direct dependency evidence when available.
- [x] `PUH-011` Add a focused regression for active scoped packs across nested
  namespaces and inheritance-only declarations, proving safe units compile with
  scoped packs while richer units stay broad.
- [x] `PUH-012` Add a focused regression for dependency projects and enforce the
  scoped-pack boundary: consuming project units include dependency `__project.hpp`
  exports, while dependency project units keep deterministic same-project local
  pack assignment.
- [x] `PUH-023` Model function and method signatures separately from function
  and method bodies. Source summaries now extract parameter and return type
  dependencies for top-level functions and methods, while candidate blockers
  distinguish executable bodies, function bodies, and method bodies. A
  signature-only interface regression proves method signature dependencies can
  activate scoped packs.
- [x] `PUH-025a` Start complete-type dependency modeling by extracting class
  property type dependencies into direct source/header evidence while keeping
  property-layout units on broad fallback until layout-sensitive scoped
  activation is implemented.
- [x] `PUH-022` Expose dependency categories in saved per-source rows and
  focused `project-units` output. Rows now categorize inheritance, direct type
  references, function signatures, method signatures, property layout,
  unresolved symbols, unresolved dependency keys, and missing source summaries.
- [x] `PUH-024` Model and activate conservative function-body dependencies for
  leaf helper files. Top-level function bodies may use scoped packs when body
  evidence is limited to resolved direct function/static calls and resolved type
  references; executable bodies, method bodies, property/static access,
  control-flow-heavy summaries, unresolved calls/types, and other unmodeled
  evidence stay broad.
- [x] `PUH-026` Reuse the existing generated-header ordering for scoped packs
  and expand same-project local dependencies through the dependency-key closure
  before writing candidate scoped headers. A helper returning a derived type now
  gets the derived header and its transitive base header in base-before-derived
  order.
- [x] `PUH-014` Make project-unit diagnostics suitable for large projects. The
  `project-units` overview now keeps header rows and dependency rows
  compact/capped, while `scpp explain-build project-unit <source>` provides the
  verbose candidate-pack, dependency, blocker, category, and reason details for
  one source row. `generated-files` remains one compact source/output/pack line
  per generated source.
- [x] `PUH-030` Add snapshot-style tests for saved `.prism/last_run.json`
  project-unit report shape. The explain-build regression now locks the
  top-level force-include report keys, header rows, blocker rows, dependency
  summary rows, and dependency category rows across STAN-backed, no-STAN, and
  direct-report paths.
- [x] `PUH-031` Add compile probes for no-STAN, stale scoped-pack cleanup,
  dependency-reuse, and clean-build paths. Existing explain-build coverage
  checks no-STAN build-owned summaries and stale pack cleanup; scoped-pack
  regression coverage now also rebuilds through dependency reuse and `scpp
  clean` before rechecking dependency export scoped packs.
- [x] `PUH-032` Add rebuild-fanout measurement to saved build diagnostics and
  `scpp explain-build rebuild-fanout`. Successful builds now record changed
  outputs, changed generated/native/runtime object counts, changed/removed
  project-unit packs, and Ninja no-work status; explain-build coverage locks
  the warm STAN no-work shape and no-STAN stale-pack removal reporting.
- [x] `PUH-021` Store a build-owned per-source dependency summary artifact at
  `.prism/cache/project_unit_dependency_summary.php`. The artifact captures
  direct source keys, direct/scoped local headers, dependency export headers,
  candidate status/blockers, dependency categories, and per-source freshness
  inputs, while saved project-unit reports carry a compact artifact pointer.
- [x] `PUH-025b` Extend complete-type dependency evidence to class constant
  value expressions. Constant initializers that name another generated class now
  produce direct source/header evidence and a `class constant value` category,
  while class-constant files remain on broad fallback until scoped activation is
  validated for that header shape.
- [x] `PUH-027` Make the native C++ unit policy explicit. Native C++ files now
  stay on broad-equivalent project-unit packs under
  `broad_fallback_without_dependency_manifest`, with saved report counters and
  focused `project-units` output showing the native broad-fallback count.
- [x] `PUH-025c` Activate scoped packs for resolved class property-layout
  dependencies. Property-layout units now become scoped candidates when their
  direct property type dependencies resolve to generated headers, and focused
  coverage proves dependency headers are included before the owning header.
- [x] `PUH-025d` Activate scoped packs for safe class constants. Scalar class
  constants and resolved class-constant references now become scoped
  candidates, constant-specific descriptors preserve nested string-concat
  dependencies, and focused coverage proves referenced class headers are
  included before the owning constant class header.
- [x] `PUH-025e` Align class-constant dependency rows with the constant-specific
  descriptor path. Class constants nested inside string-concat initializers now
  produce direct dependency evidence for the owning constant row, so scoped-pack
  safety does not depend on another constant in the same class exposing the same
  referenced header.
- [x] `PUH-025f` Lock scoped-pack activation for compact-layout value
  dependencies. Separate enum, struct, union, and record units now have focused
  coverage proving direct value-layout dependencies, transitive union payload
  dependencies, and base generated-header ordering are preserved in scoped
  packs.
- [x] `PUH-025g` Add a conservative top-level constant scoped-pack gate.
  Top-level constants now share the safe constant-initializer rule used by class
  constants, while unmodeled initializer evidence such as arrays remains on
  broad fallback with an explicit blocker.
- [x] `PUH-034` Update user-facing build diagnostic docs for the current
  project-unit diagnostics surface. The strict quick-learn, getting-started
  guide, onboarding workflow, and project-build spec now mention rebuild
  fanout, focused project-unit rows, dependency summary artifacts, and native
  broad-fallback policy.

### Ready

- [x] `PUH-010` Run a real-project baseline on `compiler/v2/src` with the
  current branch. Record active scoped units, active broad-fallback units,
  candidate blocker histogram, no-change build behavior, and rebuild fanout for
  the motivating support-file edit.
  Measured against the sandbox clone of the pushed compiler `main` checkpoint
  after merging `codex/compiler-vector-runtime` into this branch. Current
  project-unit report: 388 total generated PHS units, 388 units with
  force-includes, 276 distinct project-unit headers, 275 active scoped units,
  and 113 active broad-fallback units. Candidate blockers: 112 units with
  method bodies, 87 units with an unresolved external strict-runtime shallow
  dependency key, and 1 unit with unmodeled function-body evidence.
  A no-change `scpp build --build-runtime --timings` kept 0 transpiled files,
  388 skipped files, 0 rebuilt outputs, and Ninja no-work behavior.
  A sandbox-only header-surface edit to
  `compile/support/compiler_profile_events.phs` transpiled 1 file, skipped 387,
  rebuilt 113 generated objects plus the linked output, and rebuilt 0 runtime or
  native C++ units. The probe edit was removed after measurement.
### Planned Dependency-Model Work

- [ ] `PUH-025` Finish any remaining complete-type activation requirements for
  generated declarations whose headers require another generated header to be
  complete beyond the covered inheritance, signature, property-layout,
  class-constant, and compact-layout value-field cases.
### Planned Validation And Rollout

- [ ] `PUH-033` Validate on the motivating large strict project. Acceptance:
  a narrow support-file edit should dirty a much smaller slice than the previous
  ~387-object rebuild, while no-change builds remain Ninja no-work.
  Current C2 measurement reduces the motivating header-surface edit to 113
  generated object rebuilds plus link, which is a clear reduction but still above
  the ideal under-50 target. Next precision work should focus on the remaining
  broad-fallback blockers and public-surface/body dependency separation.
- [ ] `PUH-035` Prepare a GitHub issue update for
  `alexstanciu-1/simplecpp#215` summarizing implemented slices, measured
  results, remaining blockers, and next release/risk notes.

### Deferred / Blocked

- [ ] `PUH-040` Shared-library packaging semantics remain out of scope for this
  backlog. Project dependencies still mean source-built Prism projects in v1.
- [ ] `PUH-041` Full semantic symbol resolution remains out of scope for the PHP
  S2S generator boundary. Any precision that needs semantic guarantees should
  come from STAN/build-planning artifacts or remain broad fallback.
- [ ] `PUH-042` PCH interaction tuning is deferred until real-project fanout
  measurements show whether changed scoped packs are still poisoning an app PCH
  path.

## Current Execution Policy

Move one backlog item or tightly related group at a time. Each slice should:

1. Preserve broad fallback for uncertain units.
2. Preserve `scpp build --no-stan` behavior.
3. Add focused tests at the smallest layer that proves the change.
4. Run `php -l bin/project_services.php`, focused build tests, and
   `git diff --check`.
5. Commit after the verified slice.

## Important Constraints

- Do not break same-project PHP++/PHS composition. Source code should still not
  need `require`, `include`, or generated C++ header names.
- Do not make STAN mandatory for `scpp build --no-stan`.
- Keep broad fallback behavior for any incomplete or ambiguous dependency set.
- Preserve dependency project export behavior: consuming projects still receive
  dependency `__project.hpp` export headers.
- Keep generated headers deterministic so no-change builds remain no-work Ninja
  builds.
- Keep PCH independent from project unit headers if possible; otherwise a
  changed dependency-set header may still poison the shared app PCH.

## Acceptance Criteria

Functional:

- Existing strict same-project build tests still pass.
- Existing project dependency/export tests still pass.
- `scpp build --no-stan` still works.
- Uncertain/deferred language shapes compile through broad fallback.
- Dependency cycles or unresolved symbols still produce clear diagnostics.

Performance / rebuild-fanout:

- On a large strict PHS project such as `compiler/v2/src`, editing a narrow
  support file such as `compile/support/compiler_profile_events.phs` should not
  rebuild hundreds of unrelated native objects.
- Initial target: reduce a small support-file edit from ~387 native object
  rebuilds to a much smaller affected slice, ideally under 50 objects before
  deeper precision work.
- No-change builds should still report 0 transpiled files and Ninja no work.

Diagnostics:

- `scpp explain-build` should be able to show which project-unit header a
  generated object force-includes and whether it was precise or broad fallback.
- Build timing / last-run details should include enough data to see whether
  dependency-set generation itself becomes expensive.

## Risks And Open Questions

- Some generated C++ dependencies may not yet be visible in current summaries.
  Those units should use broad fallback until modeled.
- Class/struct/union layout requires complete types in more places than function
  calls do. Narrowing layout-heavy headers too early could cause C++ compile
  failures.
- Template/generic materialization may need a separate dependency identity model
  later.
- Function body dependencies and public surface dependencies are different.
  A body-only edit should not force consumers to rebuild unless generated header
  surface changed.
- Header ordering must remain deterministic and preserve base-before-derived
  requirements.

## Why This Is Better Than Manual Module Splitting

Manual project/module splitting can reduce rebuild fanout, but it asks users to
structure source around build invalidation. Dependency-scoped project unit
headers fix the underlying mechanism while preserving the Simple C++ authoring
model: project composition remains automatic, and the build system gets smarter
about which generated declarations each C++ unit actually needs.

This also aligns with the longer-term compiler direction: source/symbol/body
ownership, dependency edges, public-surface identity, and dirty/reuse decisions
should be first-class compiler/build rows rather than one global invalidation
surface.
