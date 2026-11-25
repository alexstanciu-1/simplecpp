# Additional edits exceeding twenty seconds

Doc Status: planning

This follow-up deliberately searches for failures of the improved experimental layout. It does not replace the earlier passing, bounded edit matrix with a claim about all edits. External compiler sources remain read-only; changes run in the copied application.

## Measurement and priority

Use Clang 18, debug `-O0 -g1`, mold, Ninja `-j12`, disabled compiler caches, and the final expanded layout with the six publication consumers partitioned. Time Ninja launch through full application link; the assumed future analysis/generation allowance is another 1.5 seconds. One screening run is sufficient to identify a large failure, but is not a distribution or median.

Rank historical candidates by recorded latency **within comparable measurement boundaries**, then use measured current latency to prioritize experiments. Historical wall times include different frontends, hardware/build states, and sometimes gate orchestration; they are motivation, not directly comparable native results.

## Additional historical candidates, descending recorded duration

| Recorded duration | Source edit / evidence | Qualification and next experiment |
|---|---|---|
| 548.5 s | int64 binary-plus type propagation, `e1b50b4e`; `compiler/docs/core/compiler_core_roadmap_backlog.md:328` | Corrected/resumed debug build after a failed stale-helper build. Adds frontend helpers, changes parser signatures/callers, and adds `frontend_model_tables::update_expression_payload`. Screen the central model-table public surface on current code first. |
| 333 s | int64-minus and type-trait work, `7d58f273`; `compiler/docs/v2_current_state_summary.md:6736` | Source-edit build/gate wall time; compiler fixture itself reported 4 ms. Broad change across type traits, readiness, lowering and frontend, including new row types. The previous narrow numeric-pipeline experiment does not cover this whole patch. |
| 321.01 s | SL-04 cleanup preflight, `93a00a4c`; `compiler/docs/future/90_percent_functionality/00_inventory/implementation_progress_2026_08_05.md:878` | Direct compiler rebuild, one source transpiled, broad native rebuild, jobs16. Historical cleanup helper and fixture runner are retired from the current workload; faithful replay needs a separate historical snapshot. |
| 246.45 s | FN-02 cleanup-preflight change, `d48ebcf5`; same progress document:1181 | One PHS file transpiled, 187 native objects plus link. Retired owner; same replay limitation. |
| 231.37 s | FN-03 preflight change; same progress document:1234 | One PHS file, 187 objects plus link. |
| 204.99 / 200.12 s | SL-05 preflight changes; same progress document:931 / 925 | Repeated narrow source edits causing broad native rebuild. |
| 186.63 s | SL-06 preflight change; same progress document:977 | One source file, 186 objects plus link. |
| 173.09 s | FN-01 preflight change; same progress document:1134 | One source file, 187 objects plus link. |

Raw document candidates and four historical patches are archived under `tools/compile_latency/results/2026-09-19/over20-hunt/`. The document scan deliberately retains false positives for audit: timeout settings, compiler execution/proof durations, cold builds, and configuration switches are not normal native incremental edit measurements.

Separate configuration candidates: 491.80 s for an O3 source-edit build (versus 120.78 s debug, five changed sources/101 native steps; MT phase-2 backlog H45), 707.98 s release/cold recovery, and 412.07 s restoring debug after release. These must not inflate the debug incremental-edit ranking.

## Current screening experiments

`tools/compile_latency/run_over20_hunt.py` preserves and restores source inputs, rebuilds the restored application, and verifies the existing twenty witnesses plus the edited behavior.

- **Central model-table public helper:** introduce a forwarding helper beside `update_expression_payload`, redirect two actual frontend callers, and exercise a model payload update. No model layout change. This owner has not received callable-surface isolation; tests remaining declaration/PCH fanout.
- **Shared frontend-counter layout:** add and update a counter field and read it in the witness. This changes a genuinely shared native class layout. A broad rebuild is potentially legitimate and must be reported separately from avoidable public-helper fanout.

Both completed screening builds linked the full application and passed its existing witnesses plus the new behavior check. Each result below is one trial, not a median.

### Confirmed: central model-table public helper

One screening run: **141.744 s native**, or **143.244 s** with the assumed frontend allowance. Rebuilt **311 objects, one project PCH, and the executable**. Peak compiler concurrency reached all 12 requested jobs; the PCH took 23.713 s and linking 0.276 s. Existing application witnesses and `hunt_flow=1:1` passed.

This is a remaining declaration-dependency problem, not evidence that more compiler threads or a faster linker alone will meet the target. A static helper addition changed no model fields, yet the central class header and its PCH consumers were invalidated. Callable-level declaration isolation is the first candidate treatment; it has **not** been implemented or proven for this owner. Historical int64-plus involved additional signature/type propagation, so this analogous probe is not a reproduction of the entire 548.5-second patch.


### Confirmed: shared frontend-counter layout

One screening run: **181.194 s native**, or **182.694 s** including the assumed frontend allowance. Rebuilt **372 objects, one project PCH, and the executable**. Peak compiler concurrency was 12; the PCH took 18.798 s and linking 0.290 s. Existing witnesses and `hunt_flow=1:1:1` passed. Only three generated inputs changed: the counter header, model-table implementation, and entrypoint implementation.

This is the larger measured case, but a different optimization category: adding a field changes the native layout of a shared class. Some propagation is required for correct C++; not all 372 rebuilds have been proved semantically necessary. Assess complete-type dependence before considering opaque storage or other representation changes, which have runtime and design costs. The user explicitly accepts longer rebuilds for genuinely widespread changes.

## Current ranked failures and next priorities

| Native wall, descending | Rebuilt objects | Case | Priority interpretation |
|---|---:|---|---|
| **181.194 s** | 372 | Shared frontend-counter field + writer/reader | Largest observed; audit required complete-type consumers separately from incidental header dependencies. Broad genuine layout rebuilds are acceptable. |
| **141.744 s** | 311 | Central table helper + two production callers | Highest actionable avoidable-fanout candidate: isolate this owner's callable declarations and retest the identical edit. |

Next untested historical coverage, descending recorded duration: the **full** int64-plus signature/type propagation patch (548.5 s), the broader int64-minus/type-traits patch (333 s), then historical cleanup-preflight replay (321.01 s, 246.45 s, and related repetitions). Existing narrow alias tests do not establish those whole changes are fast. Keep release/O3 and build-mode transitions in a separate queue.

Neither failure was hidden by a compiler cache, reduced application link, skipped runtime implementation, or concurrent benchmark. Restoration builds are outside the timed edit result. Raw Ninja step timings, generated-input manifests, run witnesses and restored-run logs accompany the experiment results.


Final restoration audit: both restored full-application smoke checks passed; the
active expanded Ninja graph reports no work to do. All recorded external source
hashes match, all scratch non-entrypoint PHS files match the snapshot, and no
extra scratch PHS files remain. The twenty-witness entrypoint harness is retained
intentionally. See `over20-hunt/final-integrity.json` in the archived results.


## Follow-up treatment

Both current failures now have tested experimental output solutions. Table-helper
native median fell to **2.847 s**, and shared-counter native median to **3.373 s**,
with three final trials each and full application correctness. See
[s2s_compile_latency_over20_solutions.md](s2s_compile_latency_over20_solutions.md)
for exact transformations, the separate 189.766-second setup cost, the counter
accessor runtime tradeoff, and the still-unreplayed historical cases. The original
screening numbers above remain the evidence for the untreated improved layout.
