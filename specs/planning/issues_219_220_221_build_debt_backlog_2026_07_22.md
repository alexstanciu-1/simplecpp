# Issues 219-221 Build Debt Backlog
Doc Status: planning

Date: 2026-07-22

## Purpose

This planning note turns the remaining debt from GitHub issues #219, #220, and
#221 into an implementation backlog.

Related implementation commits:

- `f312455` Resolve #219 generated artifact rebuild reporting
- `0d619e8` Resolve #220 build grouping reports
- `c29ada7` Resolve #221 project module surface reporting

Related planning report:

- `specs/planning/build_invalidation_industry_standard_report_2026_07_22.md`

The active implementation goal is to work this backlog in separately validated
slices while preserving clean issue-scoped commits.

## Current Baseline

#219 now records generated interface and implementation hashes, preserves
content-aware generated writes, annotates source rows with generated artifact
changes, and explains generated-object rebuilds from build fanout.

#220 now accepts `build.grouping_policy`, records deterministic report-only
group membership and object fanout, and exposes `scpp explain-build grouping`.

#221 now accepts explicit `project_modules`, writes public surface and private
implementation artifacts, records module cache status and consumer invalidation,
adds module explain-build views, and wires public module surfaces as generated
object implicit compile inputs.

The P0 benchmark slice now exposes `scpp build-benchmark`, which writes
`.prism/build_invalidation_benchmark.json` from an isolated copied work tree and
measures warm no-change plus explicit private, public, coordinator, and release
hot-edit scenarios when project-relative selectors are supplied. It mirrors
normal build behavior by reusing runtime artifacts unless `--build-runtime` is
passed for the benchmark seed.

## Debt By Issue

### #219 Generated Artifact And Rebuild Explanation Debt

- Object rebuild reasons are still inferred from saved build fanout; there is
  no complete Ninja action explanation model.
- Generated output hashes exist, but object reuse is not keyed by a complete
  action identity covering command line, toolchain, environment, inputs, and
  output hashes.
- Old caches need one warm-up build before hash-based explanation rows become
  fully precise.
- Native C++ rebuild explanations remain weaker than generated PHS source
  explanations.
- Generated header/source written-vs-preserved counters are not reported.

### #220 Build Grouping Debt

- `build.grouping_policy` is currently `report_only`; it does not change Ninja
  compile edges.
- `manual` grouping accepts the policy name, but manual group maps are not
  implemented.
- `auto` is a simple policy choice, not a measured cost model.
- Release/O3 grouped or unity object generation is not implemented.
- Hot-file isolation heuristics are not implemented.
- There is no benchmark command that compares grouping policies side by side.
- Native C++ units still use broad-equivalent project-unit behavior.

### #221 Project Module Debt

- Modules are explicit config only; Simple C++ does not validate declared module
  dependencies against actual source/STAN dependency evidence yet.
- Module surfaces are cache/report artifacts and implicit compile inputs, but
  module boundaries do not drive grouped objects.
- Module-level STAN/static-analysis summary caching is not implemented.
- `public_exports` participates in module identity, but public/private API
  enforcement is not implemented.
- Duplicate assignments and unassigned sources are reported, not
  policy-enforced.
- Project modules are project-local; cross-project module surfaces are not
  modeled.
- Module cache hit/miss data is reported, but not used to skip planner/source
  analysis work.

### Cross-Issue Debt

- No content-addressed object/action cache.
- No remote cache or remote execution.
- No persistent build daemon or resident build graph.
- Acceptance benchmarks still need to be formalized for the v2 compiler:
  warm no-change, private body edit, public surface edit, broad coordinator
  edit, and release/O3 hot edit.

## Implementation Backlog

### P0: Tighten Observability And Benchmarks

1. `BLD-219-OBS-1`: Add generated artifact write counters.
   - Count generated headers/sources written, preserved, first-recorded,
     interface-changed, and implementation-changed.
   - Store counts in `.prism/last_run.json`.
   - Render counts in `scpp explain-build` and focused generated/build views.

2. `BLD-219-OBS-2`: Capture stronger object rebuild provenance.
   - Persist normalized Ninja explain lines when `SCPP_NINJA_EXPLAIN` or an
     internal explain probe is enabled.
   - Map generated/native object causes to source rows when possible.
   - Keep current fanout inference as fallback.

3. `BLD-X-BENCH-1`: Add a repeatable v2 build invalidation benchmark harness.
   - Status: implemented.
   - Measure warm no-change, private body edit, public surface edit, broad
     coordinator edit, and release/O3 hot edit.
   - Record transpiled/reused counts, generated writes, object fanout, grouping
     fanout, module cache status, Ninja time, and total wall time.

### P1: Turn #220 Grouping From Report To Compile Graph

4. `BLD-220-GRAPH-1`: Implement manual grouping config.
   - Add an explicit `build.grouping` map or equivalent config surface.
   - Validate unknown sources, duplicate group membership, and path escapes.
   - Keep deterministic explain-build output for all manual groups.

5. `BLD-220-GRAPH-2`: Emit grouped generated object edges for selected policies.
   - Start with an opt-in policy or experimental flag.
   - Preserve isolated debug behavior as the default safe path.
   - Report grouped object membership and changed group reasons.

6. `BLD-220-GRAPH-3`: Add release/O3 grouping strategy.
   - Prefer stable folder/package groups.
   - Keep known volatile or high-cost files isolated.
   - Compare cold build, hot edit, and link-time behavior against isolated mode.

7. `BLD-220-AUTO-1`: Make `auto` evidence-based.
   - Use prior build timing, object size, fanout, and volatility.
   - Store why a file/group was isolated or grouped.
   - Keep the policy deterministic for a given saved history.

### P1: Turn #221 Modules Into Enforced Build Boundaries

8. `BLD-221-VALIDATE-1`: Validate declared module dependencies.
   - Compare `project_modules.*.dependencies` with STAN/source dependency
     evidence when available.
   - Warn or fail on undeclared cross-module dependencies based on policy.
   - Keep no-STAN behavior conservative and explicit.

9. `BLD-221-STAN-1`: Add module-level STAN summary artifacts.
   - Store per-module dependency summaries and hashes.
   - Reuse module analysis when implementation changes do not affect public
     surface evidence.
   - Report module analysis cache hit/miss reasons.

10. `BLD-221-GRAPH-1`: Use module boundaries for grouped object topology.
    - Allow a module to map to isolated per-source objects, a grouped object, or
      a hybrid shape.
    - Use public surface artifacts for consumer invalidation.
    - Keep private implementation artifacts out of consumer compile inputs.

11. `BLD-221-PUBLIC-1`: Enforce public/private module API policy.
    - Define what `public_exports` means for PHS-generated surfaces.
    - Detect private dependency leaks in generated header reach.
    - Add diagnostics that point to the source/module boundary.

### P2: Broader Build-System Maturity

12. `BLD-219-ACTION-1`: Define full local action identity.
    - Include toolchain, command line, environment, source/generator inputs,
      generated artifacts, module surfaces, and runtime signatures.
    - Store action keys for generated and native object compile actions.

13. `BLD-X-CAS-1`: Prototype local content-addressed object reuse.
    - Reuse object outputs by action key in one checkout.
    - Keep safety-first invalidation and clear explain-build evidence.

14. `BLD-X-DAEMON-1`: Prototype a resident build planner.
    - Keep project graph, source metadata, module summaries, and STAN summaries
      warm across invocations.
    - Measure warm no-change planner time against normal process startup.

15. `BLD-X-REMOTE-1`: Scope remote cache/execution requirements.
    - Document trust, platform, compiler, path mapping, and cache invalidation
      requirements before implementation.

## Sequencing

Recommended order:

1. Finish P0 observability and benchmark harness first.
2. Implement #220 manual grouping before generated grouped object edges.
3. Implement #221 dependency validation before module-driven grouped objects.
4. Use benchmark results to decide whether `auto` grouping or module STAN reuse
   is the next highest-value slice.
5. Defer CAS, daemon, and remote cache until local invalidation evidence is
   stable.

## Acceptance Checks

Each backlog slice should include the smallest relevant validation:

- `php -l bin/project_services.php`
- Focused tool tests under `tests/tools/`
- `scpp build --no-stan --timings` on `sandbox/issue_215/compiler_v2_src`
  when the slice affects build fanout or build reports
- `scpp explain-build` focused view checks for any new report field
- A clean `git status --short`

Performance-sensitive slices should record before/after numbers in a planning
or benchmark artifact rather than relying on anecdotal command output.
