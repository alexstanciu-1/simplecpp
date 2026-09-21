# Uniform helper ownership: fixed historical coverage assessment
Doc Status: planning

The broader helper policy improves **one additional code case**, but is not a winning general configuration. The same historical sample now has **4 of 9 timed code edits** within the 8.5-second native budget, compared with 3 previously. Five cases remain at 163.5–192.5 seconds and are slower than the previous screens. The conclusion is conditional feasibility, not consistent ten-second compilation across this sample.

## Fixed comparison

The sample remains the last 15 non-merge commits touching compiler sources before pinned workload revision `9776900f6c305729ae6ef8474052363508913315`. Exact hunks are reversed and reapplied in current surroundings. Four textual-context exclusions and one incompatible semantic before-state remain visible; they are not timing passes. One additional comment-only case takes 0.076 seconds and compiles no objects.

Native timing covers Ninja scheduling through full executable linking, with Clang, Ninja 12 jobs, mold and compiler-cache launchers disabled. Add the **assumed 1.5 seconds** for future incremental analysis/generation to estimate readiness; the experimental Python transformation's own duration is not an implementation of that frontend budget. Fast cases use three-trial medians; each slow result is one successful screening trial above twenty seconds. No output-policy tuning occurred between cases.

| Commit | Change | Previous native seconds | Uniform native seconds | Result |
|---|---|---:|---:|---|
| 1a24a85d | Export backend call argument rows for FN proof | 139.143 | 192.538 | over budget |
| 1610af1a | Route literal return coverage through consumer plan | 4.268 | 4.319 | within budget |
| f1d3a846 | Retarget structure smoke away from fixtures | 2.995 | 2.973 | within budget |
| b54f2766 | Generalize call expression lookahead | 2.622 | 2.239 | within budget |
| 534d70a6 | Remove legacy case env fallbacks | 177.867 | 2.402 | within budget |
| e8e8a063 | Remove dead fixed argument LLVM materializer | 141.295 | 163.529 | over budget |
| 5ce42535 | Rename backend LLVM source filename label | 138.970 | 164.280 | over budget |
| 43a83246 | Route caller function text through composition input | 143.405 | 164.125 | over budget |
| cf3c2d08 | Remove obsolete LLVM module composition delegates | 139.094 | 163.811 | over budget |

The environment-helper removal improves from a 177.867-second screen to 2.669/2.402/2.183 seconds, median 2.402; the estimated total is 3.902 seconds. Conversely, export-artifact changes take 192.538 seconds and compile 493 objects plus the project PCH. Its 218.814-second reversed-hunk setup is excluded from edit latency. Development/conversion builds are also excluded; their logs are not clean-build benchmarks.

This small, recent, refactoring-heavy commit sample does not estimate the probability that an arbitrary developer save finishes within ten seconds. The over-budget times are far outside the target, but single slow screens do not establish a statistically precise regression percentage.

## Output policy and ownership

The experiment applies callable declaration isolation to **113 structurally eligible static helper classes**, independently of source-file ownership. Class-specific declaration/body matching replaces a source's link edge once, retaining other classes' implementations. Thirteen neighboring data classes in ten mixed files remain byte-identical in the callable mirror. Class identity helpers remain members.

Declarations remain per callable. Implementations use stable approximately 180-line buckets without splitting a function. Existing assignments and diagnostic-location IDs survive edits and removals; new functions get new buckets without repacking old ones. The resulting full application links 950 objects. The original sources and production generator remain unchanged.

The catalog rejects 118 matching class surfaces, including **21 helper owners with inline normalization templates**. It is a bounded generated-C++ recognizer, not a semantic resolver or an exhaustive classifier of arbitrary/inherited C++ classes. Ordinary data-bearing classes are not converted into helper owners.

The ownership refactor exposed and fixed two development errors before accepting the new policy's timings: class-header slicing used a shadowed match variable, and filename-prefix grouping could absorb similarly named owners. Exact class spans and exact definition ownership now govern both operations. A conservative raw-string check also incorrectly rejected quoted environment names ending in `R`; lexical detection preserves those strings while still rejecting real raw literals.

## What the historical exceptions teach

All five remaining slow cases change headers containing unsupported adapters, but their semantic causes are not identical.

Three cases primarily motivate **per-callable adapter ownership**: `1a24a85d`, `5ce42535`, and `cf3c2d08`. Correction from exact-patch review during the adapter experiment: `e8e8a063` also removes `EmissionLLVMWorkerInput::target_literal_right_operand_value` and `EmissionLLVMModuleCompositionSnapshot::literal_right_operand_value`. It is a combined helper-removal and existing-class-layout edit, not an adapter-only case. The read-only adapter audit recognizes all 21 inspected helper surfaces without leftover members: **37 forwarding wrappers and 52 parameter normalizers**. Every checked wrapper calls its own normalizers and matching concrete `__exec` implementation. Normalizer results are writable `bool_t&`, `int_t<>&` or `string_t&` references.

Correction from the subsequent runtime proof: typed arguments retain their original writable references. All 26 integer normalizers reject mixed arguments directly with the parameter-specific diagnostic. The 11 boolean and 15 string normalizers call a legacy `.as_*_ref()` bridge for matching kinds, but the pinned runtime disables that bridge and throws. Wrong kinds retain the parameter-specific diagnostic. Reading the adapter headers alone was insufficient to establish mixed-reference behavior. A future emitter can retain necessary templates in individual callable artifacts, or use resolved argument information for direct concrete calls where legal. It must not discard checks or introduce copied values where mutation of the original is required. This audit supports a bounded experiment; it is not a transformed-native correctness proof.

The fifth case, `43a83246`, additionally introduces **BackendFunctionCompositionInput with eight fields** and coordinates calls across two source owners. Adapter extraction alone would still leave that new class invalidating a global all-types PCH. This requires **per-type artifacts and consumer-specific dependencies**, with stable contents for the shared PCH. Merely creating one header per class while including them all in the same PCH does not solve the problem.

The composition API uses `shared_p<BackendFunctionCompositionInput>`, supporting an opaque declaration boundary similar to the previously tested shared-counter boundary. Construction and field access still need the full definition. This is a feasibility observation, not a measured replacement for the 164.125-second composition replay. It is distinct from changing an existing widely shared value layout, as in the earlier TypeTraitRow experiment.

The broader configuration increases shared-PCH consumers and therefore makes uncovered edits more expensive. Smaller implementation files and narrower dependencies must be designed together. Adding more helper names to a class-level rule cannot by itself cover these remaining cases.

## Validation and archived evidence

All measured cases link the full application, pass the twenty smoke witnesses, and compile/execute the literal-return LLVM fixture before and after the edit (exit 42). This is bounded validation, not exhaustive coverage of every changed feature. The known incompatible before-state still removes a method required by a later caller; it is recorded separately and restoration succeeds.

Final checks pass: restored pinned PHS sources except the intentional main harness, no extra scratch PHS files, unchanged original-source hashes, a native no-op build, and the source diagnostic for `frontend_model_tables::none_id` at line 12. A separate executable regression fixture validates two extracted helper owners plus a data carrier in one file, including cross-owner calls, carrier data, distinct class identities and methods with no depth guards. It runs after the native timing batch.

Evidence is in `tools/compile_latency/results/2026-09-19/uniform-coverage/`: the complete coverage table/JSON, native work sets and logs, ownership and adapter audits, patch classifications, restored-source integrity, diagnostic and multi-owner proofs, final graph, retained assignment maps and policy hashes. The previous coverage archive remains intact.

## Next bounded assessment

1. Extend artifact ownership to a callable and its required adapter closure. Initially preserve adapter bodies and template semantics; prove typed writable references, matching-kind mixed rejection, wrong-kind diagnostics and source diagnostics before measuring.
2. Separate the new composition type from global declaration/PCH publication using its actual consumers. Preserve its shared-pointer representation and class identity.
3. Rerun the same corpus against that fixed candidate, retaining the ordinary fast cases, all slow cases and all replay exclusions. Include the earlier shared value-layout exception separately.

Non-goals: production S2S integration, general C++ template extraction, inheritance rewriting and changes to value/copy semantics. Full resolved AST/type information and the 1.5-second frontend allowance remain assumptions for the future generator.
