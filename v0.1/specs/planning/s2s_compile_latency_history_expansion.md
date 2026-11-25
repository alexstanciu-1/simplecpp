# Independent history expansion and coherent reconstruction
Doc Status: planning

Date: 2026-09-20

## Selection and interpretation

Select commits 16–30 from the same descending non-merge `compiler/src` history at
pinned revision `9776900f6c305729ae6ef8474052363508913315`. These fifteen commits do
not overlap the first sample and are selected without timing/applicability filters.
Only `b1f1fd46`, `4a4e0803` and `ea9d053c` accept exact hunks in the current source.
All three contain code changes; twelve context limits remain explicit. This is
broader chronological coverage within one project, not a random population sample.

Retain the provider-publication policy, Clang debug/mold/Ninja twelve jobs and
disabled cache launchers. Native 8.5 seconds plus the assumed frontend remains the
preferred ten-second target. Limited modest overruns can be usable; approaching
20–30 seconds deserves greater priority. Preserve exact target classifications
separately from that usability assessment.

## Coherent reconstruction

Test the excluded 28-file change `9103637e` and the two-file composition change
`41392c03` using complete Git `compiler/src` before/after snapshots. Every PHS file
must match its historical tree, except the unchanged instrumented main harness.
Reject source-file ownership or non-PHS configuration changes that require a new
build graph. Use each historical declaration-kind map (including the older
`EmissionLLVMCompositeTextSnapshot` name) and the exact historical return42 smoke
fixture. Keep the current generator, runtime and experimental output policy:
this reconstructs historical source, not the original historical toolchain.

Require a correctly linked/validated before-state before accepting forward-edit
timing. Keep setup/generation costs separate, retain failures and their phases,
and verify full smoke plus literal LLVM/native exit42 for accepted edits. Restore
the pinned source, declaration catalog and smoke fixture after the study. No
compiler feature repair or output-policy tuning is planned during these replays.

## Completed measurements

| Replay | Native seconds | With assumed 1.5 s frontend | Measurement |
| --- | ---: | ---: | --- |
| `b1f1fd46`, reference-row entry checks | 2.640 | 4.140 | Median of three |
| `4a4e0803`, expression labels | 2.158 | 3.658 | Median of three |
| `ea9d053c`, remove stale helpers | 1.617 | 3.117 | Median of three |
| `9103637e`, coherent 28-source change | 193.489 | 194.989 | One successful slow screen |
| `41392c03`, coherent two-source change | 194.008 | 195.508 | One successful slow screen |

The independent sample adds three validated code cases and twelve exact-context
exclusions. The label cleanup contains executable string/local-name changes;
it is not a comment-only case. Preserve the original fifteen-case sample as its
own protocol: the two coherent reconstructions use different surroundings and
must not silently change its runnable-case denominator.

Both coherent edits pass full application smoke and literal-to-LLVM/native exit42
checks before and after the edit. The 28-source edit rebuilds 603 objects, the
project PCH and the link (605 steps); the two-source edit rebuilds 588 objects,
the PCH and the link (590 steps). These are single successful screens, stopped
above twenty seconds, not medians. Snapshot setup and final restoration rebuilds
are excluded. Smoke witnesses do not prove every behavior in the compound patch.

## Reconstruction learned from an explicit failure

The first coherent attempts stopped during before-state generation, with
`Class outside recorded field/type metadata: EmissionLLVMWorkerInput`; neither
produced an accepted native timing. Current field metadata was not truthful for
the older source state. The accepted rerun projects exact generated definitions
and fields into the existing bounded publication roles, with one historically
verified snapshot-name alias. It preserves definitions and dependency metadata;
it does not add new private types or change historical source bodies. Metadata
input support and the projector were added between the failed and accepted runs;
initial and final policy hashes are retained separately.

The restored graph exactly matches the prior provider-policy graph. Final value,
shared-carrier, composition, adapter and callable-ownership proofs pass; native
build is a no-op. Original source hashes remain unchanged, and scratch PHS files,
the declaration catalog and fixture are restored.

## Priority exceptions and next experiment

The coherent changes introduce three data classes still exposed through shared
headers: `ScalarConditionOperand` and `ControlFlowLocalWriteSet` in `9103637e`,
and `BackendModuleCompositionInput` in `41392c03`. This last type is distinct from
the already privately published `BackendFunctionCompositionInput`. The new
classes are candidates for unnecessary PCH invalidation; private publication
has **not yet been tested** for them. In particular, source-file count alone is
not a useful predictor: the two-source edit is as slow as the 28-source edit.

A read-only audit of restored pinned output finds lexical mentions in 3, 4 and 5
active C++ units respectively. These counts are neither historical dependency
counts nor complete semantic closures: inferred values may require definitions
without spelling the type. An informed S2S must track those dependencies too.

Next extend the existing type-publication concept to these classes, prove exact
definitions and shared-reference behavior, then replay the same coherent states.
Prioritize the two-source edit, followed by the compound edit. Measure remaining
necessary implementation work separately from incidental PCH fan-out. Neither
case currently qualifies as a modest usable overrun. The full twelve-source
`7d58f273` replay and remaining exclusions are still coverage gaps.

Evidence: [archived measurements and integrity checks](../../tools/compile_latency/results/2026-09-20/history-expansion/).
Per-state source hashes, metadata, exact patches, smoke/LLVM logs and native logs
are retained. Per-state Ninja graphs were not captured; the restored final graph
is archived. Do not infer a precise necessary/incidental object split from these
measurements alone.

Status: independent sample and two coherent reconstructions complete; next
publication experiment identified, not implemented.


## Subsequent scope correction

The user requires dependency-minimal publication throughout the experiment:
no aggregate project-type declaration/definition header, including grouped
inventories that pull unrelated types into a consumer. The next candidate is
therefore a general publication rule, with the three identified classes serving
as witnesses rather than the complete implementation scope. The measurements
above describe the earlier partially isolated policy, not this requested shape.
See the next-edit plan for the updated contract.
