# Historical compile-latency pain cases

Doc Status: planning

This evidence supplements the assessment plan. Small getter edits are supporting
measurements, not sufficient acceptance evidence. Original compiler repository is
read-only; probes run in `/tmp/scpp-edit-latency-20260919/app`.

## Evidence and resolution status (checked 2026-09-19)

| Evidence | Actual trigger / result | Assessment use |
|---|---|---|
| [#215](https://github.com/alexstanciu-1/simplecpp/issues/215), closed | Profiling changes: 3 transpiled, 387 native objects, 398.3s wall. Shared force-include header invalidated unrelated objects. | Public helper/signature additions; avoid recreating global invalidation. |
| [#218](https://github.com/alexstanciu-1/simplecpp/issues/218), open with accepted partial-fix comments | `--no-stan` collapsed scoped packs into broad fallback. Fixed in `70c1906`. Later comment: 2 files, 100 native steps, 122.35s debug. | Remaining dependency breadth, not no-change timing. |
| [#219](https://github.com/alexstanciu-1/simplecpp/issues/219), closed | MT proof-counter edits: 3–4 transpiled, about 100 objects. Helper extraction in compiler commit `4840c64f`: 433 transpiled, 435 steps, 250.908s Ninja. | Helper extraction/new source, shared row/counter additions. Distinguish old timestamp churn from unavoidable layout consumers. |
| [#220](https://github.com/alexstanciu-1/simplecpp/issues/220), closed | Grouping requested for hot edits and expensive release translation units. | Group stability and volatile entrypoint isolation. |
| [#224](https://github.com/alexstanciu-1/simplecpp/issues/224), closed | Final validation at toolchain `80490d94`: public-surface edit in `token_kinds.phs`, 5 objects plus link, 17.6–19.0s Ninja under load. Earlier broad-fallback and inventory problems fixed. | Small public method additions remain relevant after prior fixes. |
| [#96](https://github.com/alexstanciu-1/simplecpp/issues/96), closed | Clang ignored app PCH because it was not first forced include. | Verify PCH actually consumed; do not treat this old bug as current. |

Compiler docs `core/compiler_core_roadmap_backlog.md` contain the 398.3s
profiling case and 548.5s corrected int64 build. Commit `86445c02` added two
public profile-stage helpers and changed a frontend helper signature to return
timing through reference parameters. The doc reports three transpiled files;
the committed source diff contains two PHS paths, so exact historical working
state must not be inferred solely from that commit.

Compiler docs `future/capability_catalog_multithreading_milestone_plan_2026_07_20.md`
record the int16 change: 34.71s wall, 26.752s Ninja, two transpiled files,
20 rebuilt outputs plus link. Commit `6764cad0` adds a public primitive-type
helper and branches in frontend mapping and LLVM alignment/type/exit lowering.
Current source has evolved to generic numeric traits; a replay must be labeled
as an analogous edit or run against an isolated historical checkout.

The 278.313s measurement in `incremental_benchmark_matrix_2026_07_19.md` is a
post-O3 debug restore, not a narrow source edit. Do not count it as edit evidence.

## First analogous historical probe

`tools/compile_latency/historical_profile_probe.py` adds a public profiling-stage
ID method, adds its stage-name dispatch branch, and exercises the new method and
branch from the real application entrypoint. Full application remains linked.
The existing structure smoke and 20 getter witnesses also pass. Cache launchers
are disabled. Source files are restored afterward; native artifacts need rebuilding
before the next restored-source measurement.

| Native layout | Seconds | Ninja outputs | Meets 8.5s native budget |
|---|---:|---:|---|
| Current per-source scoped headers | 13.459 | 12 | No |
| Stable partitions/location metadata with global project PCH | 57.530 | 116 | No |

One measured run each, not statistical estimates. Both executions verified
`historical_profile=latency_probe`. Header is touched before each layout's build
to prevent shared objects from the first trial concealing second-trial work.

This disproves using the global project-declaration PCH as the general solution:
its excellent body-edit measurements conceal broad public-surface invalidation.
Keep runtime/stable declarations in PCH; investigate narrow callable declarations
and consumer-specific dependencies. Merely splitting methods is insufficient.

## Revised required cases

1. Public helper addition and dispatch use (first probe above).
2. Small token-kind public helper addition, corresponding to #224 final evidence.
3. Extract real MT proof helpers into a new source, corresponding to `4840c64f`.
4. Add/update a shared proof counter and its actual writers/readers; record layout
   dependencies separately from callable dependencies.
5. Signature change with affected callers, following `86445c02`.
6. Multi-method numeric capability change, following `6764cad0`.

Prioritize these over more repetitions of small body-only getters. A credible
future output shape must preserve language behavior, class/layout consistency,
source diagnostics, and full linking. Do not claim sub-10-second common edits
until the historical pain categories are measured with that shape. Cache hits
must be reported separately from genuinely new edited-code compilation.

## Additional historical qualification

The contemporaneous file at compiler commit `4840c64f`,
`compiler/v2/docs/build_fanout_strategy_status_2026_07_22.md`, explicitly describes
its 250.908-second Ninja run as paying a cold/full generated cost. The extraction
also changed eight existing implementation files, not just one new helper. It is
useful as a painful workflow example, but cannot by itself prove that a one-file
helper addition intrinsically requires a full rebuild. Record warm initial state
and exact changed sources in the new reproduction.

## External tooling context

ROOT/CERN's [module migration paper](https://arxiv.org/abs/1906.05092) addresses
repeated parsing of largely immutable headers through compiled module data.
That supports testing reuse of stable declarations. It does not establish a
10-second executable build for edited Simple C++ application declarations.
ROOT/Cling interactive execution also differs from this assessment's freshly
linked executable requirement; no Cling integration has been measured here.

[ccache 4.9 documentation](https://ccache.dev/manual/4.9.html) describes reuse of
previous compiler results; [sccache's cache design](https://github.com/mozilla/sccache/blob/main/docs/Caching.md)
keys C/C++ cache results from preprocessed inputs. Neither provides evidence that
a genuinely new implementation can skip its first compilation. Keep cache replay
measurements separate from the cache-disabled edit results. PCH/module support
and configuration require their own checks before recommending a deployment.

## Expanded issue/comment audit (2026-09-19)

The follow-up reads issue bodies **and comments** for #126, #162, #163, #216,
#217, and #221. Raw API responses and source diffs `60011d4c`, `6764cad0`, and
`4840c64f` are archived under `results/2026-09-19/evidence-audit`. The targeted
active/archive document query found nine candidate documents; its file list is
archived. This is bounded search coverage, not an exhaustive claim over all misc
files, untracked logs, or every historical revision.

| Issue | Evidence interpretation | Assessment consequence |
|---|---|---|
| [#126](https://github.com/alexstanciu-1/simplecpp/issues/126) | Closed; comments trace initial locking fix, regression, and successful 0.1.55 parallel retest. | Preserve one writer per shared artifact, locking and atomic publication. More compiler jobs does not authorize racing builds on identical outputs. No fresh source-edit timing case here. |
| [#162](https://github.com/alexstanciu-1/simplecpp/issues/162) | Open; reuse integration passes, but real projects reported missing runtime/stale dependency recovery friction. | Keep runtime fixed during edit proofs; distinguish missing-artifact recovery from ordinary compile latency. Direct Ninja experiments do not validate CLI recovery. |
| [#163](https://github.com/alexstanciu-1/simplecpp/issues/163) | Open; original invalid-object symptom improved, later duplicate-main composition and opt-in stale dependency rebuild reports. | Link correctness and dependency artifact selection remain requirements; do not label original corruption repro as currently reproduced. |
| [#216](https://github.com/alexstanciu-1/simplecpp/issues/216) | Open, but comment documents profile-separated roots implemented in `6b4f60f`, with a custom-root cleanup caveat. | Do not infer missing implementation from open status. Debug/release switching is a separate configuration-identity scenario. |
| [#217](https://github.com/alexstanciu-1/simplecpp/issues/217) | Open; no comments returned; overlaps #216. | Deduplicate the workflow requirement rather than count it as a second source-edit case. |
| [#221](https://github.com/alexstanciu-1/simplecpp/issues/221) | Closed; comments list module surface/reporting/dependency/grouping implementation and remaining method/property/transitive boundary limitations. | Reuse existing module concepts where appropriate; a closed issue is not proof of complete method-level isolation or fresh-edit performance. |

Commit `60011d4c` explains why the earlier numeric body-only experiment was partial:
it adds a **public frontend helper** plus a spelling branch and two LLVM mapping
branches. The next contemporary analogue retains all those edit shapes rather
than only changing existing method bodies. It does not claim to reproduce the old
vendor's scoped-header omission on the current evolved source tree.
