# Build Invalidation Industry Standard Gap Report
Doc Status: planning

Date: 2026-07-22

## Purpose

This report compares the current Simple C++ project build model with common
large-C++ build practice, then estimates the performance value of completing
issues:

- #219, Build: avoid generated timestamp churn and separate public-surface invalidation
- #220, Build: add configurable grouping policies for fast incremental and release builds
- #221, Compiler modules: project-level compile-time surfaces and cached module artifacts

This is a planning report, not a semantic authority.

## Sources And Assumptions

Issue context:

- #219: generated timestamp churn, interface/body hash separation, rebuild-cause evidence.
- #220: configurable build grouping policies for incremental/debug and release/O3 workflows.
- #221: project-level modules with explicit public compile-time surfaces and private implementation hashes.

Current v2 datapoints from the issues:

- Warm no-change debug builds can reach Ninja no-work, but still spend about
  2.2-2.5 s in project source/S2S state collection in the large v2 compiler
  checkout.
- A post-edit debug validation after the v2 mitigation batch reported 433 PHS
  files transpiled, 435 Ninja steps, 433 generated objects rebuilt,
  `collect_project_php_files_and_s2s_state_ms: 65772`, and
  `ninja_subprocess_ms: 250908`.
- The immediate warm no-change build after that reported 0 transpiled files,
  0 rebuilt objects, and Ninja no-work.
- The v2 project had 433 project units, 335 distinct project-unit headers,
  334 active scoped units, and 99 broad-fallback units.
- The broad fallback blockers were concentrated in unmodeled body evidence:
  98 method-body blockers and 1 function-body blocker.
- A release/O3 sample previously recorded `main.cpp -> main.o` at about 3:33
  wall time and 662 MB peak RSS.

Workspace-sandbox sanity check on `sandbox/issue_215/compiler_v2_src` after the
current branch:

- First generated-state probe: 388 files transpiled.
- Immediate reuse probe with runtime available: 0 transpiled, 388 reused,
  Ninja no-work, 0 rebuilt outputs.
- Measured breakdown for that steady no-change run:
  `collect_project_php_files_and_s2s_state_ms: 248`,
  `render_and_write_build_ninja_ms: 60`,
  `ninja_subprocess_ms: 19`,
  `write_last_run_report_ms: 201`.
- The current branch reports `build.grouping_policy` as deterministic
  report-only grouping membership. The same smoke build reported 388 groups,
  0 changed groups, and 0 rebuilt objects.

Industry references:

- Ninja manual: `https://ninja-build.org/manual.html`
- Bazel remote caching: `https://bazel.build/remote/caching`
- LLVM Getting Started / CMake+Ninja guidance: `https://llvm.org/docs/GettingStarted.html`
- Buck2 rationale for very large monorepos: `https://buck2.build/docs/about/why/`

## Executive Summary

The Simple C++ direction makes sense.

Simple C++ already follows several important large-C++ norms: Ninja backend,
compiler depfiles, generated/build/cache directories, PCH support, build
profiles, cached source-to-source state, scoped project-unit packs, and
rebuild-fanout diagnostics.

The main gap is that Simple C++ is still moving from "file and generated header
invalidates a C++ graph" toward "action/interface/module identity invalidates
only the semantic consumers that must rebuild." That gap is exactly what
issues #219, #220, and #221 target.

If #219, #220, and #221 are completed, Simple C++ would be close to the
standard architecture used by mature large-C++ build systems for local
incremental builds. It would still not be a full Bazel/Buck-style system until
it has content-addressed action caches, remote cache/execution, and a persistent
build server/resident graph.

## What Simple C++ Already Does

| Area | Industry practice | Current Simple C++ status |
| --- | --- | --- |
| Fast build backend | Use Ninja or an equivalent low-overhead executor. | Uses Ninja as the project backend. |
| Explicit build tree | Keep generated/build/cache outputs out of source. | Uses `.prism/build`, `.prism/generated`, and `.prism/cache`. |
| Compiler dependency discovery | Use compiler depfiles and compact dependency logs. | Emits `depfile = $out.d` and `deps = gcc` for GNU-like compile rules. |
| Incremental no-change checks | Avoid work when inputs and commands are unchanged. | S2S state skips unchanged PHS files; Ninja can reach no-work. |
| PCH | Use PCH carefully for large repeated includes. | Supports app/runtime PCH when the compiler supports it. |
| Build profiles | Keep debug/release state separate. | Supports `--mode=debug|release` and profile-specific roots. |
| Fast linker/cache integration | Prefer fast linkers and compiler launchers when available. | Detects usable compiler launchers such as `sccache`; supports launcher override. |
| Project dependencies | Model project dependencies explicitly, not source includes. | Supports `dependencies`, export headers, and reusable dependency artifacts. |
| Runtime artifact reuse | Avoid rebuilding shared runtime on every project build. | Reuses shared runtime artifacts by default. |
| Rebuild observability | Provide "why did this rebuild?" diagnostics. | `scpp explain-build` reports transpile decisions, outputs, fanout, project units, and Ninja target hints. |
| Scoped include surfaces | Avoid one global project header when safe evidence exists. | Uses scoped project-unit packs for many safe generated PHS units, with visible broad fallback. |
| Broad fallback visibility | Make imprecision visible instead of hidden. | Reports active scoped/broad counts and blocker histograms. |
| Generated file no-touch | Do not rewrite byte-identical generated files. | `write_text_file()` is content-aware; #219 first slice also records interface/body hashes. |

## What Simple C++ Does Not Yet Do

| Gap | Industry expectation | Impact |
| --- | --- | --- |
| Full action identity | Every action has stable input hashes, command line, environment, toolchain, and output hashes. | Current build state is strong enough for project workflow, but not yet a full action-cache model. |
| Content-addressed object cache | Reuse outputs by action key locally and across machines. | No Bazel/Buck-style CAS or remote cache for generated/native outputs. |
| Remote execution/cache | Share build products across CI/developer machines. | Large cold/release builds remain local. |
| Generator actions inside Ninja | Generated artifacts are often first-class build actions with restat/no-touch semantics. | Simple C++ generates before Ninja. Content-aware writes help, but generator edges are not yet Ninja actions with `restat`. |
| Grouped compile edges | Debug/incremental and release/O3 usually use different grouping tradeoffs in the actual compile graph. | Initial #220 support now accepts `build.grouping_policy` and reports deterministic group membership/fanout, but Ninja still compiles one object per source. |
| Module-driven compile graph and analysis reuse | Large systems publish stable module/package surfaces and keep implementation private. | Initial #221 support now accepts `project_modules`, writes public surface/private implementation artifacts, and uses public surface artifacts as generated-object compile inputs. Module boundaries do not yet drive grouped objects or STAN reuse. |
| Native dependency manifests | Native units should have dependency evidence comparable to generated units. | Native C++ units still use broad-equivalent packs. |
| Persistent build server | Very large systems often keep analysis state resident. | No project build daemon yet; no-change builds still scan/load source state. |
| Precise body dependency model | Body-only changes should dirty only body consumers/backend owners. | Scoped packs model more than before, but 99 v2 units still had broad fallback from body evidence. |
| Stable public/private invalidation at module scale | Public surface changes dirty dependents; private changes stay local. | #219 begins per-file interface/body hashes; #221 is needed for module-scale boundaries. |

## Expected Effect Of Resolving #220 And #221

### Issue #220: Configurable Grouping Policies

Initial current-branch support:

- `build.grouping_policy` accepts `incremental`, `isolated`, `package`,
  `folder`, `manual`, `release`, `auto`, and `none`.
- Build reports store the active policy, policy source, deterministic group
  membership, changed groups, and object fanout.
- `scpp explain-build grouping` renders the active policy and group rows.

Remaining compile-graph work:

- Debug/incremental grouping can prefer isolated or very fine-grained generated
  objects.
- Release/O3 grouping can use larger stable groups without forcing that shape
  onto edit loops.
- Hot/coordinator files can be isolated even if nearby stable files are grouped.
- `explain-build` can report which grouping policy caused a changed group.

Expected value:

- Large local body-only debug edits should trend toward 1 changed generated
  object plus link when public surface is stable.
- Release/O3 can keep throughput-oriented grouping for stable code while
  isolating volatile high-cost files.
- Source layout no longer has to carry hidden build-performance meaning.

### Issue #221: Project Modules And Cached Surfaces

Initial current-branch support:

- `project_modules` accepts named project-local modules with `sources`,
  `source_roots`, `dependencies`, and `public_exports`.
- Builds write public `.prism/cache/project_modules/*.surface.json` artifacts,
  private `.implementation.json` artifacts, plus a stable manifest.
- Build reports store module interface hashes, implementation hashes, cache
  status, implementation artifacts, and consumer invalidation based on
  dependency interface hash changes.
- Generated object compile rules take the owning module surface and depended
  module surfaces as implicit inputs, while private implementation artifacts are
  not consumer inputs.
- `scpp explain-build modules` and `scpp explain-build module <name>` render
  the module overview/detail.

Remaining compile-graph work:

- Module-level STAN/static-analysis summaries and cache hits/misses.
- Grouped/module object topology beyond the current per-source objects.

Expected value:

- Broad coordinators such as `compile/pipeline/fixture_runner.phs` can depend
  on named surfaces instead of pulling broad textual generated-header reach.
- Central proof/support utilities can become private module implementation
  details when their public contract stays stable.
- STAN and future compiler analysis can run/reuse per module instead of always
  treating the project as one large source universe.

## Performance Estimate

These are planning estimates, not guarantees. C++ build performance is
non-linear because object size, template/include volume, PCH validity, linker
choice, CPU count, filesystem, and cache warmth all matter.

| Scenario | Current observed shape | Expected with #219 only | Expected with #219 + #220 | Expected with #219 + #220 + #221 |
| --- | --- | --- | --- | --- |
| Warm no-change build | v2 issue datapoint: Ninja no-work, about 2.2-2.5 s source/S2S collection. Sandbox: 245 ms collection, 19 ms Ninja. | Mostly unchanged, but generated hashes are available for explanation/cache migration. | Mostly unchanged. Grouping policy should not matter when no inputs changed. | Potential 2x-5x reduction in planner/analysis overhead for large projects if module summaries let no-change checks load module fingerprints instead of all file detail. |
| Small body-only leaf/helper edit, debug | Bad case: 433 transpiled, 433 generated objects rebuilt, Ninja about 251 s. Earlier common cases: about 100 objects plus link. | Best case becomes 1 changed generated source/object plus link when unchanged generated headers/packs are preserved. Estimated 10x-50x wall-time gain in debug, 80%-99% fewer object rebuilds. | More reliable 1-5 object rebuilds because debug policy can force volatile code isolation. Estimated 20x-100x object-count reduction versus 100-433 object cases. | Similar or better for private module edits; consumers stay clean if module interface hash is stable. |
| Body-only edit in broad-fallback/coordinator file | Current v2 has 99 broad-fallback units; coordinator files can drag large generated-header surfaces. | No-touch generated outputs avoid unrelated timestamp churn, but body evidence can still be hard to explain precisely. | Grouping can isolate coordinator files from stable groups, reducing collateral rebuilds. Estimated 3x-20x wall-time gain depending coordinator size. | Modules can turn coordinator dependencies into named public surfaces. Private implementation movement should avoid consumer rebuilds unless surface changes. Estimated 5x-30x for recurring coordinator/proof plumbing edits. |
| Public surface change | Correct behavior is to rebuild true dependents. Current fallback can overbuild. | Per-file interface hash can identify that the public surface changed. | Grouping can report and bound which group changed. | Module interface hash limits fanout to dependent modules rather than broad project-unit reach. Expected object-count reduction depends on architecture, often 2x-10x versus broad fallback. |
| Release/O3 validation after small edit | Known expensive case: `main.cpp -> main.o` around 3:33 wall, 662 MB RSS; saved release fanout still showed 100 generated objects plus link. | Avoids rebuilding byte-identical generated files, but if a huge volatile object remains dirty, cost remains high. | Release policy can group stable code while isolating volatile/high-cost units. Estimated 5x-30x improvement for edit-loop O3 validation when the huge object is no longer dirty. | Modules can make stable public surfaces reusable and private changes local. Best recurring private edits should avoid broad O3 recompiles; cold release throughput may improve modestly, roughly 5%-25%, if grouping reduces repeated include/PCH overhead without hurting parallelism. |
| Cold build | Current cold build compiles all generated units and links. | Little direct gain; cold build still has real work. | Grouping may improve or hurt cold time depending object size and parallelism. Expected range: -10% to +25%. Needs measurement. | Module artifacts may improve repeat cold-ish builds across checkouts/cache roots if module caches persist. Without CAS/remote cache, pure cold gains are limited. |

## Priority And Sequencing

Recommended order:

1. Finish #219 enough that every generated source row has stable interface/body
   hashes and every object rebuild has a useful cause.
2. Extend #220 from report-only grouping into actual grouped compile edges once
   dependency precision is sufficient.
3. Extend #221 from project-config surface artifacts into module-driven STAN
   reuse and Ninja consumer edges.
4. Use the v2 compiler as the acceptance target, with separate measurements for:
   warm no-change, private body edit, public surface edit, broad coordinator edit,
   and release/O3 hot edit.

This order gives quick local-edit value before taking on the larger module
surface design.

## Suggested Metrics To Track

Every benchmark should capture:

- PHS files transpiled/reused.
- Generated headers written vs preserved.
- Generated sources written vs preserved.
- Project-unit pack headers changed/removed.
- Active scoped vs broad-fallback units.
- Broad fallback blocker histogram.
- Generated object rebuild count.
- Native object rebuild count.
- Runtime object rebuild count.
- Final link count/time.
- Largest object compile wall time and peak RSS.
- Source/S2S collection time.
- Ninja no-work status.
- Total wall time.

For #220:

- Active grouping policy.
- Group membership.
- Changed groups.
- Object count and generated byte count per changed group.
- Whether grouping increased fanout compared with isolated mode.

For #221:

- Module interface hash before/after.
- Module implementation hash before/after.
- Module cache hit/miss reason.
- Consumer module fanout.
- STAN/module analysis reuse status.

## Bottom Line

Simple C++ is already doing the right foundational things for a Ninja-based
large-C++ workflow. The remaining high-value gaps are the same ones solved by
mature monorepo build systems: stable action identity, public/private surface
separation, configurable grouping, and module/package cache boundaries.

Resolving #220 and #221 on top of #219 should plausibly move the common v2
compiler edit loop from "hundreds of generated objects and minutes" toward
"one to a few generated objects plus link and seconds to tens of seconds" for
private/body-only edits. Cold builds will improve less, but release/O3 edit
validation should improve sharply when volatile code is isolated from huge
stable generated units.
