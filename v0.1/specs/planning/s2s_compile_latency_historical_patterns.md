# Historical edit patterns and remaining exceptions

Doc Status: planning

## Purpose

Continue the assessment by learning which edit shapes remain uncovered, not by
repeating already-proven isolated speedups. Historical build/gate wall times are
used for prioritization; they are not directly comparable native timings or a
statistical sample of everyday edits.

## What the next historical patches actually changed

| Historical evidence | Actual source shape | Interpretation |
|---|---|---|
| `7d58f273`, int64-minus/type traits, recorded 333 s build/gate cycle | 12 PHS files; new 419-line `type_traits` owner with 53 static methods; new helpers and coordinated changes across frontend/readiness/lowering/LLVM; 20 new fields across three records | Several different costs occur together. Existing helper and signature probes cover only part of this patch. Value-record layout propagation is a distinct gap. |
| `93a00a4c`, SL-04 cleanup preflight, recorded 321.01 s rebuild | New 82-line cleanup-preflight owner with 11 static helpers; runner integration changes, including a new helper and signature changes | New-file discovery, callable declaration publication, and caller updates. The commit changes two source files although the timing log says only one source was transpiled in that measured build. The full commit and timed invocation are not necessarily identical scopes. |
| `d48ebcf5`, FN-02, recorded 246.45 s rebuild | One existing cleanup-preflight source adds 34 lines, four static helpers, and a branch in existing dispatch | A mixed body/public-surface edit, not a body-only edit. Whole-class declaration invalidation is a candidate cause of the recorded 187-object fanout. A causal native replay has not been done. |

Patch-derived inventory is archived in
`tools/compile_latency/results/2026-09-19/historical-patterns/patch-shapes.json`.
Counts refer to the actual diff; positive declaration lines can include changed
signatures and should not all be called newly introduced methods.

The cleanup owner was `compile/capabilities/control_flow_cleanup_preflight.phs`
in these patches and is retired from the current workload. It contained explicit
fixture-name/source-string gates. Those gates are historical workload evidence,
not a semantic design to copy into a future type-aware compiler.

## Separate value records from shared objects

The 333-second patch added eight fields to `ProviderTraitDescriptorRow`, eight to
`TypeTraitRow`, and four to `TypeTraitTable`. These are value records, including
vectors of rows. Adding a field can affect copies, layout, construction/destruction,
container element stride and native calls that use those values. The shared-counter
factory/accessor solution does not automatically solve this category.

A representative current-code screen adds a field to `TypeTraitRow`, populates it
in the existing `type_traits::row_from_descriptor` writer, and verifies a returned
row, an independently modified copy, and a vector-stored copy. Expected values:
`32:99:32`. It retains the native value representation and the full application.
This is intentionally narrower than the full historical three-record change.

Do not change value records to shared-pointer aliases merely to improve timings:
the copy witness makes the required independent value behavior explicit. A future
stable representation would need truthful copy/lifetime/ABI semantics and a
measured runtime tradeoff. First distinguish genuine complete-type consumers from
incidental umbrella/PCH dependencies. The user accepts long rebuilds for genuinely
widespread semantic changes.

## Coverage implications

- Public helper additions and coordinated signature changes have measured passing
  examples; extend their coverage to additional owners rather than recounting the
  same evidence as a new category.
- A new file with dozens of implementations deserves its own category: publishing
  it without invalidating unrelated code does not remove the cost of compiling
  the new implementation bodies.
- By-value layout evolution remains a separate exception candidate. Record it
  even when it is slower than the target, with correctness and native fanout.
- Full historical replay needs a matching source/toolchain snapshot. Current
  arithmetic backend rejection and retired cleanup owners limit exact replay.
- No claim about a percentage of everyday edits follows from this pain-case
  selection. A later independent history sample must supply that denominator.

## Value-layout screening result

The native build took **146.265 s** in one screening trial, rebuilding **429 objects,
one project PCH and the executable**. The PCH took 14.887 s. The real writer and
copy/vector witness produced `value_trait=32:99:32`, and the existing application
witnesses passed. This adds an observed exception to the passing helper/reference-
object/signature cases. It is not a median or a replay of all three historical
record changes. Some rebuilds are likely incidental PCH dependencies; the count
is not a proof that all 429 consumers require the complete record layout.

## Independent history sample

Archived the last **15 non-merge commits touching `compiler/src`** reachable from
workload revision `9776900f6c305729ae6ef8474052363508913315`, without filtering by
reported compile latency. The sample includes implementation routing, helper
extraction, deletion, names/wording, signature/representation changes and a broad
28-file correction. Commit size is not editor-save frequency; this is a candidate
pool, not yet a measured estimate of ordinary-edit success rates.

The manifest and exact patches are in `historical-patterns/recent-source-sample/`.
Promising distinct candidates include:

- `1610af1a`: one implementation reroutes literal-return readiness through a
  consumer-plan object; no public declaration change. The old helper still exists,
  making an exact hunk replay in the current program feasible.
- `b54f2766`: shared call-argument lookahead extraction/generalization, renamed
  callable surface and several updated callers. Tests removal/rename as well as
  addition; prior additive helper probes do not establish all of this behavior.
- `43a83246`: replace direct LLVM text-building helpers with composition-input
  helpers and update consumers. A cross-owner representation refactor; distinguish
  its staged edits from a single atomic commit rebuild.

Exact hunk replay still differs from rebuilding the entire historical revision:
current surrounding source and current experimental output layout remain in use.



## Exact implementation-hunk replay result

Replayed the exact source change from **1610af1a** in the current surrounding
program: replace the old literal-readiness call with consumer-plan construction,
population and readiness evaluation. Native times were **4.319 / 4.268 / 4.202 s**,
median **4.268 s**; with the assumed frontend, **5.768 s**. Each trial rebuilt
**one object plus the full executable**, with no PCH rebuild. The available twelve
jobs do not imply twelve active compilers when only one implementation is dirty.

Both the before-hunk and after-hunk implementations compiled an int32 literal
return fixture to LLVM and a native executable exiting with 42. This is stronger
historical fidelity than a fabricated analogue, but still not a full historical
revision/toolchain replay. Its result is one measured member of the independently
selected 15-commit pool, not a coverage percentage for that pool or everyday edits.

Evidence is archived in `tools/compile_latency/results/2026-09-19/literal-history-results/`;
the value-layout exception is in `value-trait-results/`. Both experiments restored
the source, passed restored application checks, and leave the active graph at a
no-op. External source hashes match; no scratch source additions remain.

## Next useful investigations

1. For the 146-second value-record case, first remove unnecessary complete-type
   dependencies from umbrella/PCH consumers and consider out-of-line special
   members for containing value types. Preserve independent copies. This remains
   a hypothesis, not a demonstrated speedup; some real consumers may still rebuild.
2. Replay `b54f2766` where current surroundings permit it, including method removal
   and rename. Verify graph ownership, stale generated artifacts and diagnostics,
   not only addition latency.
3. Study `43a83246` as a staged representation refactor. Identify which saved edits
   alter a callable surface, introduce a carrier type or change only implementations.
4. Expand independently selected edit coverage before estimating how often ten
   seconds is achievable. Keep cold/configuration and genuinely broad ABI changes
   distinct from ordinary implementation edits.


## Follow-up status

The original value-layout screen above is retained as historical evidence. The
successor value-publication policy measures its field/writer analogue at 6.117
seconds native with independent copy/vector proofs; it also retains all nine
runnable fixed-corpus code-case passes. See
`s2s_compile_latency_value_publication.md`. This does not yet measure the entire
coordinated `7d58f273` patch.
