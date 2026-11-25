# Composition type publication assessment
Doc Status: planning

This bounded candidate extends the adapter policy with consumer-specific publication
of `BackendFunctionCompositionInput`. Its class definition is copied byte-for-byte
into a separate header. Callable signatures retain `shared_p<T>` and forward
declarations; construction, field access and class-identity implementation units
include the complete definition. The shared project PCH no longer publishes it.

The semantic owner is a generated type artifact and its complete-definition
dependencies. The experiment uses explicit metadata for this one eight-field
class; a future resolved AST must provide exact type identities and dependency
edges. General template parsing, production S2S integration and representation
changes are outside scope. Shared-handle findings must not be applied blindly to
by-value classes.

Before accepting timings, build the full application and prove unchanged class
bytes, class identity, factory construction, writes visible through a copied
shared handle, expected composition output, and empty-target rejection. Verify
that the expected LLVM output compiles and returns 42. Existing full-application
smoke and literal-pipeline checks remain part of each historical replay.

Replay `43a83246` first, using the same cache-disabled Clang/mold/Ninja -j12
protocol and the 8.5-second native budget. Conversion time is separate. If the
candidate passes, repeat the remaining unchanged corpus; retain the existing
field-removal exception and all five replay exclusions. Do not optimize between
cases. Keep logs and source restoration evidence in a separate `type-coverage`
archive.

Status: full conversion passed (388.76 seconds, excluded from edit timing).
The class-byte, identity, shared-aliasing, composition-output and rejection proof
passes; the expected LLVM program returns 42. The corrected corpus replay and all final archive checks are complete.

Setup/restoration consistency correction: `run_corpus.warm` formerly requested
16 jobs even though timed builds used 12. This candidate uses 12 for both. Prior
native trial timings remain unchanged; historical setup costs were not measured
with the same job count as timed trials and must not be compared as such.

## First draft correction

The initial full conversion and semantic proof passed, but the historical
before-state revealed unstable output: the absent-type branch skipped removal
of stale forward declarations from other headers. Its forward screen took
527.8 seconds and rebuilt the PCH. This is a development failure, archived
separately in `type-publication-development`, not the final candidate.

Declaration normalization now runs for both presence and absence. A focused
fixture asserts identical shared-header bytes across these states, including
unrelated stale forward declarations. The corrected fixed-corpus run uses
`type-coverage-stable` scratch results and the final `type-coverage` archive.
No representation or source-language semantics changed in this correction.

## Corrected new-type replay

The exact `43a83246` hunk now measures 3.899 / 3.913 / 3.599 seconds native
(median 3.899; estimated 5.399 including the assumed frontend), versus the
previous adapter candidate's 290.232-second screen. All trials rebuild five
objects and link, with no PCH rebuild. Before-state setup takes 4.822 seconds.
The subsequent complete fixed-corpus replay retains all seven previously passing
code cases and adds this eighth pass.

The three complete-type consumers are the backend composition implementation
group, the LLVM composition implementation group, and the residual class-identity
implementation unit. Two source-location objects also rebuild. The definition
may remain as an unused artifact while the type is absent; every active consumer
is still rebuilt when the historical addition is reapplied, so no cached native
object supplies the new type's behavior.

This is a useful distinction from the earlier shared-counter accessor experiment:
consumer-specific publication narrows compilation without adding field accessors
or changing runtime representation. A future emitter should identify which uses
need a complete definition and which need only a declaration, then let Ninja
schedule the actual changed dependency closure. The proof is bounded to a
non-inherited shared class with the recorded eight primitive fields.

## Next exception to study

The remaining `e8e8a063` edit affects two different shared carriers.
`EmissionLLVMWorkerInput` contains many primitive/vector fields, while
`EmissionLLVMModuleCompositionSnapshot` includes
`shared_p<BackendRequestAuthorizationArtifact>` and `shared_p<LoweringPlan>`.
The current eight-primitive-field metadata does not cover those surfaces.
A follow-up should generalize the type-artifact owner with explicit field/dependency
metadata, prove the private headers' complete/incomplete-type requirements,
preserve shared ownership and vector contents, and replay the exact field-removal
patch. Its small direct lexical consumer count is motivation, not proof of the
full dependency closure. Keep the independent by-value `TypeTraitRow` exception
separate; shared-object boundaries do not establish value-layout safety.


## Complete fixed-corpus results

Eight of nine runnable code edits meet the native budget, compared with seven
under the adapter-only policy. Passing medians span 2.054–4.238 seconds native,
or an estimated 3.554–5.738 seconds with the assumed frontend. Four context
exclusions and one incompatible before-state remain visible; the comment-only
case is separate. This small correlated commit sample is not an estimate of
normal save-time frequency.

| Edit | Native seconds | Estimated total | PCH rebuild |
|---|---:|---:|---|
| `1a24a85d` Export backend call argument rows for FN proof | 4.238 | 5.738 | no |
| `1610af1a` Route literal return coverage through consumer plan | 2.889 | 4.389 | no |
| `f1d3a846` Retarget structure smoke away from fixtures | 3.825 | 5.325 | no |
| `cec8c616` Retarget direct call evidence wording | 0.092 | 1.592 | no |
| `b54f2766` Generalize call expression lookahead | 2.845 | 4.345 | no |
| `534d70a6` Remove legacy case env fallbacks | 2.977 | 4.477 | no |
| `e8e8a063` Remove dead fixed argument LLVM materializer | 331.598 | 333.098 | yes |
| `5ce42535` Rename backend LLVM source filename label | 2.923 | 4.423 | no |
| `43a83246` Route caller function text through composition input | 3.899 | 5.399 | no |
| `cf3c2d08` Remove obsolete LLVM module composition delegates | 2.054 | 3.554 | no |

Fast cases use three-trial medians. The field-removal case is one successful
screen above twenty seconds: 331.598 seconds, 582 objects plus PCH and link.
It remains uncovered, with the same native action count as the adapter-only
screen. These sequential single screens are not a randomized paired comparison;
the higher wall time does not isolate a causal performance effect of moving the
unrelated composition type. The native miss and broad invalidation are clear.

No production S2S/runtime or original compiler source was changed. The experiment
assumes future resolved semantic information and a 1.5-second frontend; it does
not claim the current experimental regeneration scripts finish within that time.


## Final validation and archive

Final checks pass: unchanged original class bytes, class identity, shared aliasing,
real composition output and rejection behavior, LLVM exit 42, all 52 original
versus extracted adapter normalizers, source diagnostics, and callable ownership.
Each measured replay links the complete application and checks twenty smoke
witnesses plus the literal-return pipeline before and after the edit.

The original compiler source hashes are unchanged. Scratch non-main PHS sources
match the pinned workload, no extra scratch PHS sources remain, and the native
build reports no work to do. The intentional main harness is hashed separately.

Evidence: `tools/compile_latency/results/2026-09-19/type-coverage/` contains the
complete fixed-corpus results, native work sets/logs, final graph and retained
assignments, semantic proof fixtures/logs, policy hashes and source integrity.
`type-publication-development/` retains the failed first draft and conversion
costs separately. All work remains experimental; production integration is a
future task.


Follow-up: the separate shared-carrier policy in
`s2s_compile_latency_shared_carriers.md` makes the remaining field-removal case
fast and retains all eight passes above. This table remains the historical record
of the composition-only type policy.
