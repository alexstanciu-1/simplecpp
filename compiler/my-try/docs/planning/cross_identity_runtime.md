# Cross-family strict identity: runtime prerequisite
Doc Status: supporting

2026-10-19. The approved 96-signature E3 strict-identity slice is not implemented.
Runtime support was probed first on `alex-ai@dev002.travelfuse.ro`, using the unchanged
runtime from `/home/alex-ai/widening-compounds/20261019-01`.

All **96 signatures fail native compilation**: two operators (`===`, `!==`), four
canonical payloads, and 12 ordered distinct-carrier pairs. All **32 same-family
positive controls compile**. These are syntax/instantiation probes, not execution
proofs of states or snapshots. No compiler or runtime implementation changes were made.

Every failure reports ambiguous `identical` overloads. For example,
`identical(nullable<int>, result<int>)` matches both the nullable-left generic
normalizer and the Result-right generic normalizer. `not_identical` delegates to
`identical` and encounters the same conflict. This affects every distinct-family
pair, not a particular payload or unavailable state.

The existing normalized-value identity contract remains suitable. Runtime dispatch
needs a single unambiguous normalization owner/order for wrapper pairs. A repair
should preserve recursive normalization, bool payload/sentinel identity, null and
structured error distinctions, and existing same-family/scalar behavior. Adding
compiler-side normalization to bypass runtime ownership is outside this slice.
Discuss and authorize that runtime prerequisite before enabling compiler admission.
Ordinary equality and mixed payloads remain separate.

## Evidence

[Results](cross_identity_runtime_results.json),
[complete probe archive](cross_identity_runtime_evidence.tar.gz).
The archive contains source probes, commands, compiler diagnostics, summaries,
executed scripts and the tested snapshot source manifest. Runtime source hashes
match the current local tree. Clang 18.1.3 was used with 12 shared-runner task slots.
The initial collector catches command failures so all probes finish: task-level
PASS lines mean collection completed, **not** that compilation passed. The recorded
command exit codes and summary identify all 96 failures explicitly.

Reusable collection entry: `compiler/my-try/tests/cross_identity_runtime.py
--results FRESH`, with `--controls` for same-family controls. It collects all failures;
inspect summary values and command exits rather than treating process exit as a
support assertion. The archive retains the exact scripts executed for this probe.

No state/snapshot execution, full matrix rerun or native compiler rebuild was done.
V-L07 remains at 2,368 verified signatures and 1,584 design/scope candidates; none
of these 96 signatures is counted as completed. The implementation goal remains
unfinished pending discussion of the runtime prerequisite.
