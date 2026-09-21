# Callable adapter boundary assessment
Doc Status: planning

This candidate extends the uniform-helper experiment to generated parameter adapters. It preserves the adapter templates, writable references, runtime kind checks, diagnostics and concrete execution bodies. It does not use guessed types to remove checks. Production lowering and original compiler sources remain unchanged; the future resolved AST and 1.5-second frontend allowance remain assumptions.

## Scope and proof requirements

The bounded adapter grammar and wrapper-to-normalizer/implementation closure are validated before emission. The candidate isolates 134 helper owners, including the 21 owners rejected by the previous template-free policy. Ordinary data classes retain their representations. General template lowering, inheritance, external native ABI changes and value-layout changes are outside this experiment.

Before accepting native edit timings, build and link the entire application, verify the twenty smoke witnesses and existing source diagnostic, exercise all 52 real emitted normalizers with typed and mixed arguments, check typed writable aliasing/mutations, matching-kind mixed rejection, and exact wrong-kind errors. Exercise an actual file-reading wrapper with both argument representations. Keep these correctness tests outside latency measurements.

## Measurement plan

Reuse the fixed historical corpus and prior native timing protocol in a separate `adapter-coverage` archive. Prioritize the export-artifact and LLVM helper edits that failed under the uniform-helper policy, then verify the existing ordinary fast cases. Keep the new composition-type edit and all replay exclusions visible. Stable callable adapters alone are not expected to fix global publication of a new type.

The separate new-type candidate will need a stable shared PCH and consumer-specific type dependencies. Do not silently fold it into this policy or claim a timing result for it before testing.

Status: assessment complete. **Seven of nine runnable code edits meet the 8.5-second native budget**, compared with four under the previous uniform-helper policy. The seven passing medians span 2.194–4.434 seconds native, or 3.694–5.934 seconds including the assumed 1.5-second frontend. One comment-only case needs no compilation. Four context exclusions and one incompatible before-state remain separate.

| Historical edit | Native seconds | Estimated with frontend | PCH rebuild |
|---|---:|---:|---|
| `1a24a85d` Export backend call argument rows for FN proof | 4.434 (median, 3 trials) | 5.934 | no |
| `1610af1a` Route literal return coverage through consumer plan | 2.934 (median, 3 trials) | 4.434 | no |
| `f1d3a846` Retarget structure smoke away from fixtures | 3.240 (median, 3 trials) | 4.740 | no |
| `cec8c616` Retarget direct call evidence wording | 0.085 (median, 3 trials) | 1.585 | no |
| `b54f2766` Generalize call expression lookahead | 2.681 (median, 3 trials) | 4.181 | no |
| `534d70a6` Remove legacy case env fallbacks | 3.374 (median, 3 trials) | 4.874 | no |
| `e8e8a063` Remove dead fixed argument LLVM materializer | 237.076 (single screen) | 238.576 | yes |
| `5ce42535` Rename backend LLVM source filename label | 3.162 (median, 3 trials) | 4.662 | no |
| `43a83246` Route caller function text through composition input | 290.232 (single screen) | 291.732 | yes |
| `cf3c2d08` Remove obsolete LLVM module composition delegates | 2.194 (median, 3 trials) | 3.694 | no |

Exact-patch correction: `e8e8a063` removes fields from both `EmissionLLVMWorkerInput` and `EmissionLLVMModuleCompositionSnapshot`; its commit title understates the dependency change. This case and the new composition type test separate type publication/layout dependencies from adapter ownership. Do not classify all former LLVM helper exceptions as adapter-only edits or claim all objects rebuilt by a global PCH are semantically affected.

## Existing mixed-reference restriction

The first runtime probe rejected an assumption made during the earlier header-only audit: matching-kind mixed arguments do not yield native writable references. The pinned runtime's `mixed_t::as_int_ref`, `as_bool_ref` and `as_string_ref` deliberately throw. `specs/dynamic_types.md` (by-reference boundary rule), `specs/native_reference_safety.md` and `specs/references.md` define this as the supported safe-subset restriction.

The proof compares original adapters and extracted adapters against the actual runtime: typed aliasing/mutation succeeds; matching-kind mixed references retain the original rejection (direct parameter errors for all 26 integer normalizers, disabled-bridge errors for 11 boolean and 15 string normalizers); wrong kinds retain the original parameter diagnostic. A typed file-reading wrapper succeeds, and its mixed-reference path remains rejected. Both executables pass all 52 normalizers. Enabling the bridge or inventing a copy/write-back conversion is outside scope. Earlier planning text has been corrected accordingly.

## Lessons for a future informed generator

The reusable owner is a callable artifact plus its required adapter closure.
Emitting the adapter in the original all-methods class header preserves an
unnecessarily broad dependency even when the execution body has moved out.
The experiment retains templates and their semantics but publishes each
wrapper, normalizer and concrete declaration through narrowly included headers.
A resolved AST could replace lexical recognition with exact callable identity,
parameter representation and dependency edges. General template specialization,
inheritance and externally consumed C++ APIs remain outside this proof.

Stable declarations also require stable *residual* artifacts. Removed adapter
spacing and unused forward declarations initially changed class-header bytes;
those incidental changes must not invalidate a shared PCH. The candidate
normalizes residual helper spacing and removes unused forward declarations
before publishing final files. Source-location data remains separately owned.

Type publication is a different dependency. Construction, field access and
layout-sensitive operations need a complete definition; passing an existing
shared handle may need only a declaration. The initial lexical audit finds six
active units mentioning `EmissionLLVMWorkerInput`, four mentioning
`EmissionLLVMModuleCompositionSnapshot`, and three mentioning
`BackendFunctionCompositionInput`. These counts include residual identity units
and do not prove the complete semantic dependency closure. They motivate a
consumer-specific type-header experiment; they do not justify skipping any
required C++ compilation.

Splitting implementation units increases the work done when a shared PCH is
invalidated. This candidate therefore has a real tradeoff: broader fast callable
coverage, but a worse uncovered layout edit. Do not claim an optimal build setup
until type publication is narrowed and both fast and slow cases are rerun.

## Remaining coverage limits

The fixed sample contains fifteen consecutive non-merge commits touching compiler
sources at the pinned revision. Its nine runnable code edits are a small,
correlated workload sample, not a statistical estimate of all normal saves.
Four patches have incompatible current textual context; one before-state is
semantically incompatible with a later caller. The comment-only case is tracked
separately. Replaying a patch in current surroundings is not reconstructing the
entire historical toolchain.

Before production confidence, the next candidate should preserve these exact
cases while adding changed-feature witnesses for export artifacts and function
composition, then retain a separate existing by-value layout test. Type changes
must be classified by new type versus existing layout, shared-handle versus
by-value representation, and actual consumer count. A broadly reused layout is
an acceptable exception, but an all-types PCH is not evidence of broad semantic
reuse. Future coverage should also distinguish local edits, coordinated
signature/call-site edits, and changes to runtime/template infrastructure.

Native results across successive policies are sequential measurements on the
same configured machine, not randomized paired trials. A single slow screen
establishes a large miss of the target; its precise duration is not a stable
population statistic. Initial policy conversion builds (307.05 and 291.85
seconds during development) are excluded from incremental edit measurements.


## Final verification and evidence

All measured before/after states link the complete application, pass twenty smoke
witnesses, and compile/run a literal-return LLVM executable with exit 42. Adapter
control/candidate probes pass all 52 real normalizers and the file-reading
wrapper. The source diagnostic retains its original function, file and line.
The final ownership audit accounts for 134 eligible helper owners, preserves 36
colocated classes, and finds 1,053 unique link inputs (including runtime.so).

Original-source hashes are unchanged; non-main scratch PHS sources match the
pinned workload, there are no extra scratch PHS sources, and the restored native
build is a no-op. The intentional main smoke harness is hashed separately.
The initial `f1d3a846` trials overlapped a filesystem snapshot attempt; they are
retained in `excluded-overlapping-filesystem-audit/`, with only the isolated
repeat used above. The repeat median is 3.240 seconds. No candidate policy changed
between cases.

Raw logs, coverage JSON/table, original/candidate adapter proof fixtures, final
integrity and ownership audits, build graph, stable assignment maps and policy
hashes are archived under
`tools/compile_latency/results/2026-09-19/adapter-coverage/`.
The original workload and production generator/runtime were not modified.


Follow-up: `s2s_compile_latency_type_publication.md` records the separate completed
per-type candidate, which retains these seven passing code cases and makes the
new composition-type addition fast. The adapter-only table above remains the
unchanged record of this policy.
