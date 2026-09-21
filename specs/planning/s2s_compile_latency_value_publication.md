# By-value trait publication assessment
Doc Status: planning

This bounded candidate moves the unchanged `TypeTraitRow` and `TypeTraitTable`
value-struct definitions outside the project PCH. The table explicitly includes
the complete row definition for `vector_t<TypeTraitRow>`. Callable declarations
use struct forwards; actual consumers include complete definitions. Native value
representation, fields, pointer-to-self helpers and vector semantics are retained.

The common type-publication owner now records declaration kind and explicit
complete-header consumers, alongside the earlier shared-handle dependencies.
Metadata admits only the known optional `latency_integer_width` field used by the
existing probe. General value-type publication, production integration and ABI
indirection are outside this experiment.

Repeat the earlier real-writer field addition, checking `value_trait=32:99:32`
for independent row copies and stored values. Add `value_table=1:2` to prove that
appending to a copied table does not mutate the original vector. Link the complete
application and run the existing twenty smoke witnesses. Use three equal native
work sets if fast, one screen if above twenty seconds, with Clang/mold/Ninja -j12,
caches disabled and the 1.5-second frontend assumption. Conversion is separate.

Status: completed bounded assessment; value-layout probe, fixed-corpus regression, final proofs and source-integrity checks passed.

## Value-layout probe result

Native trials are 6.098 / 6.135 / 6.117 seconds (median 6.117), or an estimated
7.617 seconds total. Each rebuilds 20 objects plus link, with no PCH rebuild and
up to twelve Clang jobs. Both `value_trait=32:99:32` and `value_table=1:2` pass;
the full twenty-witness smoke suite passes and original scratch source is restored.

The earlier field/writer probe took a 146.265-second screen on its older output
policy. This run retains that field and writer, adding a stronger table-copy
witness. It is not a randomized paired comparison. The rebuilt object set and
absence of global PCH invalidation establish the mechanism more clearly than an
exact speedup ratio.

The baseline standalone proof also passes: definition bytes match, row copies
and retrieved vector values are independent, and appending to a copied table
leaves the original size unchanged. The full conversion took 237.08 seconds and
is excluded from incremental timing. Fixed-corpus regression results follow.


## Remaining historical coverage

Prioritize `7d58f273`, whose recorded build/gate cycle was 333 seconds. Its twelve
source files combine twenty added fields across `ProviderTraitDescriptorRow`,
`TypeTraitRow` and `TypeTraitTable`, a new 53-method owner, and coordinated callers.
The measured single-field/writer probe establishes only one component of that
change. The historical 333 seconds is not an isolated native timing comparator.

The next assessment should first establish coherent before/after source states,
then measure the coordinated change and useful staged edits separately. Record
which declarations, complete value layouts, method bodies and generated locations
actually changed; count necessary complete-type consumers separately from global
PCH invalidation. Keep the same twelve-job native configuration and unchanged
representation, with copy/vector witnesses and full executable validation. If an
exact replay is incompatible, retain that exclusion and label any current-source
analogue explicitly. Do not silently fuzz patches or count an analogue as replay.

An informed S2S would need to own declaration/definition dependencies, including
complete types required by containing value types, and keep output identities and
contents stable across edits. This experiment supplies those facts as restricted
metadata. It demonstrates an output shape the future generator could emit; it
does not implement semantic dependency analysis or measure its assumed 1.5 seconds.


## Frozen-corpus regression

The same fifteen-commit sample retains nine runnable code cases, one comment-only
case, four context exclusions and one incompatible before-state. All nine code
cases pass the native 8.5-second budget under the combined policy. Native medians
are 1.573–3.821 seconds, estimated totals 3.073–5.321 seconds. Each measured case
has three trials, full application smoke checks and literal LLVM/executable
validation returning 42. These checks do not prove every changed feature.

| Commit | Change | Result | Native seconds |
|---|---|---|---|
| 1a24a85d | Export backend call argument rows for FN proof | within target | 2.831 (median, n=3) |
| 1610af1a | Route literal return coverage through consumer plan | within target | 2.180 (median, n=3) |
| f1d3a846 | Retarget structure smoke away from fixtures | within target | 2.755 (median, n=3) |
| cec8c616 | Retarget direct call evidence wording | within target | 0.077 (median, n=3) |
| db047e10 | Retarget semantic gate to project runner | context_limit | — |
| b54f2766 | Generalize call expression lookahead | within target | 2.000 (median, n=3) |
| 534d70a6 | Remove legacy case env fallbacks | within target | 2.073 (median, n=3) |
| e8e8a063 | Remove dead fixed argument LLVM materializer | within target | 3.821 (median, n=3) |
| b085d952 | Rename LLVM worker handoff as module composition | context_limit | — |
| 5ce42535 | Rename backend LLVM source filename label | within target | 2.144 (median, n=3) |
| 43a83246 | Route caller function text through composition input | within target | 2.451 (median, n=3) |
| cf3c2d08 | Remove obsolete LLVM module composition delegates | within target | 1.573 (median, n=3) |
| 41392c03 | Route LLVM module composition through input rows | context_limit | — |
| 4abe635f | Generalize scalar capability consumer plans | replay_incompatible | — |
| 9103637e | Correct definition-driven compiler drift | context_limit | — |

The by-value field probe is separate from these nine historical code cases. The
sample does not estimate how frequently normal editor saves meet the target.
The incompatible `4abe635f` before-state removes a method still required by a later
caller in the pinned workload; no timing is accepted for it. Exact replay and
restoration restrictions were retained throughout.


## Final verification and evidence

Final standalone checks preserve exact row/table definition bytes, independent
row and vector/table copies, shared-carrier identity/mutation, composition output,
and type-presence/absence stability. All 52 adapter normalizers retain typed
aliasing/writes and existing mixed-reference rejection. Source diagnostics still
report the authored function/file/line. Ownership audit finds 134 eligible owners
and 1,053 unique full-application link inputs. No production generator or runtime
code was changed.

Original source hashes match. Scratch PHS matches the pinned source except for the
intentional main smoke harness; no extra PHS files remain. The final native build
is a no-op. Logs, exact source patch, generated graph, policy hashes, replay
exclusions and copy proofs are archived in
`tools/compile_latency/results/2026-09-19/value-coverage/`, with the separate field
experiment under `value-probe/probe/`. Policy hashes match the archived final
scripts; the only post-corpus script change clarified the publisher docstring.

Conclusion: narrowly publishing complete value types is feasible without changing
copy semantics, and does not regress this historical sample beyond its budget.
The coordinated three-record historical change remains the next coverage gap.
