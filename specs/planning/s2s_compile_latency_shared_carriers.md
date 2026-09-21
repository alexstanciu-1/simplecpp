# Shared carrier publication assessment
Doc Status: planning

This candidate extends per-type publication to the two carriers changed by
`e8e8a063`: `EmissionLLVMWorkerInput` and
`EmissionLLVMModuleCompositionSnapshot`. The pinned after-state has 158 and six
fields respectively. The reversed patch adds one integer field to each. Explicit
metadata records field names/types, those two optional historical fields, and
the snapshot's shared dependencies on `BackendRequestAuthorizationArtifact` and
`LoweringPlan`.

A common `type_publication.py` owns extraction, metadata validation, declaration
normalization in presence/absence states, and consumer includes. The old
composition entry point delegates to it, preserving its policy interface.
Definitions are copied byte-for-byte: no field accessors, vector rewrites or
changes to shared ownership. Unknown fields/types and unsupported header users
are explicit errors. This remains bounded experimental lowering, not a semantic
C++ parser or production S2S integration.

Before timing, build the complete application and prove exact class bytes,
identity, shared mutation, primitive/string vector contents, and mutation through
the two shared dependency fields. Compile the private headers with runtime-only
PCH support to check incomplete-type requirements independently of global project
definitions. Then replay the exact historical field removal with full smoke and
literal LLVM validation. Retain the same Clang/mold/Ninja -j12, cache-disabled
native timing boundary and assumed 1.5-second frontend.

Status: initial conversion passed (298.35 seconds, excluded from edit timing).
All carrier proofs pass, including standalone headers with runtime-only PCH.
The fixed-corpus replay, final validation and archival checks are complete.

## Historical field-removal result

`e8e8a063` measures 3.226 / 3.169 / 3.191 seconds native (median 3.191),
or an estimated 4.691 seconds with the assumed frontend. All trials compile eight
objects and link; none rebuilds the PCH. The previous per-composition-type policy
screen took 331.598 seconds and rebuilt 582 objects plus PCH and link. Before-state
setup now takes 3.383 seconds. These native results include scheduling and linking,
with caches disabled and eight compiler jobs active within the twelve-job limit.

The changed type headers invalidate seven active implementation/identity units;
the separate source-location object also rebuilds. The complete application links
and passes twenty smoke witnesses and the literal LLVM exit-42 check before and
after the patch. The subsequent fixed-corpus replay retains all eight previous passing code cases.

The source file's broad include fan-out did not represent the carriers' true
layout dependency fan-out. Preserving the original fields and shared handles,
but publishing their definitions only to consumers, removes that accidental
invalidation. This does not establish a bound for genuinely widely consumed
by-value layouts, public ABI changes, or runtime/template infrastructure changes.

## Scheduling lesson

“Only changed files” must include invalidated consumers, not just C++ files whose
text changed. In this patch, two private type headers change; several unchanged
implementation files still need recompilation because their layout dependency
changed. Ninja's compiler depfiles select eight objects, run up to eight Clang
processes within the twelve-job limit, then link. Skipping those unchanged-text
consumers would risk incompatible object layouts. The optimization is a truthful,
narrow dependency closure, not suppressing necessary rebuilds.


## Complete fixed-corpus comparison

All nine runnable code edits are within the native budget, compared with eight
under the previous type-publication policy. Each uses three successful trials
with the same native work set. Native medians span 1.612–3.191 seconds; estimated
total medians span 3.112–4.691 seconds including the assumed frontend. No measured
case rebuilds the project PCH. The comment-only case compiles no objects.

| Historical edit | Native median | Estimated total |
|---|---:|---:|
| `1a24a85d` Export backend call argument rows for FN proof | 2.915 | 4.415 |
| `1610af1a` Route literal return coverage through consumer plan | 2.221 | 3.721 |
| `f1d3a846` Retarget structure smoke away from fixtures | 2.756 | 4.256 |
| `cec8c616` Retarget direct call evidence wording | 0.082 | 1.582 |
| `b54f2766` Generalize call expression lookahead | 2.073 | 3.573 |
| `534d70a6` Remove legacy case env fallbacks | 2.059 | 3.559 |
| `e8e8a063` Remove dead fixed argument LLVM materializer | 3.191 | 4.691 |
| `5ce42535` Rename backend LLVM source filename label | 2.245 | 3.745 |
| `43a83246` Route caller function text through composition input | 2.503 | 4.003 |
| `cf3c2d08` Remove obsolete LLVM module composition delegates | 1.612 | 3.112 |

The same four context mismatches and one incompatible before-state remain
separate. Nine passing commit replays are not a normal-save success probability.
They are evidence that one fixed output policy covers all runnable cases in this
small, correlated historical sample. These are sequential policy measurements,
not randomized paired experiments; do not attribute every small timing difference
to one emitter change.

The unchanged earlier by-value `TypeTraitRow` field/copy witness remains an
unresolved exception outside this corpus. It previously took 146.265 seconds;
that timing is from its earlier policy, not a measurement of this candidate.
Other remaining material includes the excluded coordinated historical patches
and the broader multi-file/value-layout pain cases. Reconstructing coherent
historical surroundings is preferable to weakening their replay checks.


## Final validation and next boundary

Final checks pass: original carrier bytes, standalone headers, class identity,
shared mutation, vector contents, shared dependency fields, the prior composition
proof, all 52 original/extracted normalizers, source diagnostics, callable
ownership, and type-presence/absence header stability. The metadata source header
hash matches the restored pinned generated header. Original compiler source hashes
are unchanged; scratch non-main PHS sources are restored, no extra scratch PHS
sources remain, and Ninja reports no work to do. The intentional main harness is
hashed separately. Production generator/runtime code was not modified.

Evidence is archived in `tools/compile_latency/results/2026-09-19/carrier-coverage/`,
including full coverage results, native logs/work sets, proofs, graph/assignments,
policy and metadata hashes, and final source integrity.

A post-timing read-only audit finds direct `TypeTraitRow` mentions in 18 active
C++ units. Outside callable headers, its definition and `TypeTraitTable` mention
it; the latter embeds `vector_t<TypeTraitRow>`. Both are generated value structs,
not the shared classes supported by this candidate. This motivates a value-type
publication experiment with explicit complete-type dependencies and independent
copy/vector witnesses. Eighteen lexical users are not the full semantic closure,
and no new latency result is claimed for that case. The audit is archived as
`next-value-layout-audit.json`.
