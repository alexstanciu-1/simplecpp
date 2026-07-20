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
- files with no class inheritance or layout-sensitive declarations;
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

### Ready

- [ ] `PUH-010` Run a real-project baseline on `compiler/v2/src` with the
  current branch. Record active scoped units, active broad-fallback units,
  candidate blocker histogram, no-change build behavior, and rebuild fanout for
  the motivating support-file edit.
- [ ] `PUH-014` Make `project-units` and `generated-files` output suitable for
  large projects by keeping summaries compact and pushing verbose per-source
  detail behind the focused view only.

### Planned Dependency-Model Work

- [ ] `PUH-021` Store/reuse a build-owned per-source dependency summary artifact
  with direct source keys, local header paths, export header paths, candidate
  status, blockers, and the summary freshness inputs that produced it.
- [ ] `PUH-022` Expand candidate reasons from generic blockers into dependency
  categories such as direct type reference, inheritance, function signature,
  method signature, property layout, executable body, unresolved symbol, and
  missing summary.
- [ ] `PUH-023` Model function and method signatures separately from function
  and method bodies. Signature-only declarations can often use scoped packs
  earlier than body-heavy files.
- [ ] `PUH-024` Model function-body dependencies for leaf helper files. Start
  with conservative same-project type/function references already named by
  source summaries; fallback broad on unresolved calls, dynamic construction, or
  unmodeled generated helper needs.
- [ ] `PUH-025` Model complete-type requirements for class properties,
  constants, value-layout structs/unions, enum backing/type references, and
  generated declarations whose headers require another generated header to be
  complete.
- [ ] `PUH-026` Reuse/extend `sort_project_unit_include_headers(...)` for scoped
  packs so base-before-derived and generated-header dependency ordering stays
  deterministic.
- [ ] `PUH-027` Decide the native C++ unit policy. Initial likely rule: native
  C++ stays on broad fallback unless a project config or manifest explicitly
  declares a narrower generated-header dependency set.

### Planned Validation And Rollout

- [ ] `PUH-030` Add snapshot-style tests for saved `.prism/last_run.json`
  project-unit report shape so future diagnostic additions remain compatible.
- [ ] `PUH-031` Add compile probes for no-STAN, stale-STAN, dependency-reuse,
  and clean-build paths after build-owned dependency summaries exist.
- [ ] `PUH-032` Add rebuild-fanout measurement to the usability or build
  diagnostics harness: record transpiled files, changed project-unit packs,
  dirty native objects, and Ninja no-work behavior.
- [ ] `PUH-033` Validate on the motivating large strict project. Acceptance:
  a narrow support-file edit should dirty a much smaller slice than the previous
  ~387-object rebuild, while no-change builds remain Ninja no-work.
- [ ] `PUH-034` Update `specs/project_build_v1.md`, strict quick-learn, and
  getting-started docs once build-owned summaries and expanded scoped
  activation are stable.
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
