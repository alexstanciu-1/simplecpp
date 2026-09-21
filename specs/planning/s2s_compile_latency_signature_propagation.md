# Coordinated parser signature propagation

Doc Status: planning

## Result

A coordinated change to **nine parser signatures and sixteen call sites across
two source files** rebuilt the complete compiler in **7.019 / 5.520 / 5.812 s**
native (median **5.812 s**). Adding the assumed future frontend cost gives
**7.312 s median**, with the slowest measured trial at **8.519 s** including that
allowance. Every trial rebuilt the same fourteen objects plus the executable,
without rebuilding a PCH or runtime library. Compiler caches were disabled;
Ninja used twelve jobs and mold, with the existing debug flags.

This extends evidence beyond adding one isolated helper: changing several real
callable signatures and their actual consumers can remain within the ten-second
target on the experimental output layout. No new output optimization was needed.

## Relationship to the historical 548.5-second case

Commit `e1b50b4e` threaded an expected-return TypeRef through parser methods,
updated the incremental-parser caller, and added expression/model helpers. Those
changes already exist in today's source and cannot simply be applied again.

The experiment uses the same ownership and propagation pattern: append an
explicit return-policy parameter to function-body, statement-list, body-statement,
if/while/for, return-statement and return-expression parsing. Update the actual
internal and external call sites, including
`resident_function_body_local_parse_proofs`. A marked source fixture disables
returns at the boundary; the propagated parameter causes a diagnostic in the
return-expression parser. This is an intentionally experimental behavior witness,
not a proposed language feature or a literal historical-patch replay.

The source edit touches `frontend_model_builder.phs` and the incremental parser
proof source. It retains the table/counter isolation and publication partitions
from the preceding experiment. The generator's stable per-callable declarations
invalidate the twelve affected frontend implementation objects, the location
sidecar and the incremental-parser implementation, rather than the project PCH.

## Correctness and replay limitation

Before editing, both the ordinary and marked int32 local-return fixtures compile
and produce LLVM executables exiting with 42. After editing, the ordinary fixture
still exits with 42 in every trial, while the marked fixture reports a frontend
block (reason 3). All existing application witnesses pass. Nested control-flow
signatures and their callers are compiled and linked, but their execution paths
are not individually covered by this fixture.

The historical int64-plus fixture was attempted first and is blocked at the
current backend-text layer (reason 5), before any signature edit. An int32-plus
precheck also blocks, whereas int32 local-return succeeds. A link-only control
using all 452 retained original-layout objects reproduces the int64-plus rejection,
as does the restored current isolated layout. This distinguishes the observed
limitation from a failure unique to the new output boundaries. It is not a fresh
build of the old historical revision, nor a diagnosis of the backend's root cause.

Consequently **do not call this a reduction of the entire historical 548.5-second
build to 5.812 seconds**. It proves a larger, historically motivated signature
edit on the current workload. A faithful full historical replay remains separate
work and needs a matching historical toolchain/source snapshot.

## Reproduction and evidence

Run `tools/compile_latency/run_return_policy_probe.py --app APP` against the guarded
scratch copy. It uses the solutions layout, verifies baseline acceptance, applies
the edit, measures three identical native work sets, validates both fixture paths,
then restores source and rebuilds the baseline. Later trials touch only the
content-changed generated inputs to force equivalent native compilation work.
Future frontend generation remains an assumed 1.5 seconds; Python/PHP experimental
postprocessing time is not included in the native measurement.

Archived evidence: `tools/compile_latency/results/2026-09-19/return-policy/` contains
the source patch, manifests, native logs, per-step timings, LLVM, executable exit
proofs, rejection rows, original-layout arithmetic control and restoration audit.
External source hashes match; scratch non-entrypoint source hashes match; no extra
scratch PHS sources remain; the restored Ninja graph reports no work to do.

## Lesson and remaining priorities

Prioritize declaration granularity before adding more build threads. Fourteen
necessary implementation rebuilds fit the target on this hardware once unrelated
class surfaces and project PCH contents remain stable. Keep diagnostic line maps
in independent artifacts so source-line shifts do not defeat that granularity.

Next historical coverage remains the broader int64-minus/type-traits patch
(333-second recorded build/gate cycle), followed by cleanup-preflight changes
(321.01 and 246.45 seconds). Their complete historical workflows have not been
replayed; the numeric backend limitation must be respected in any current-source
analogue or isolated from the timing study in a matching historical snapshot.
