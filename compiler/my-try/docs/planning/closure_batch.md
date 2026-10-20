# S09 / V-R07 / V-H10 closure batch
Doc Status: supporting

Completed 2026-10-20 on dev002. All three approved issues close: **29/64 fully
closed (45.3125%)**, up from 26/64 (40.625%), a gain of **4.6875 percentage points**.
There are no partial repairs and 35 other original issues remain open. This is
issue-count progress, not an estimate of remaining engineering effort.

## Changes and ownership

S09-B1 now uses exact concrete/generic runtime overload selection first. On zero
exact matches, shared preparation may select one unique concrete fixed-arity
signature through identity or existing checked wrapper-to-exact-payload value
conversion. Normal conversion and argument emission own extraction, snapshots and
errors. References/observation remain exact; generic inference, defaults, variadics,
numeric promotion, wrapping and conversion chains do not participate in fallback.
The unchanged original false-branch fixture now passes, as do the two original S09
surface/edge fixtures. No source flow narrowing was introduced.

V-R07 exposes seven vector names/eight forms through core metadata. The existing
public runtime header supplies their implementations. Contracts and acceptance
semantics are in [the vector helper spec](../../../../specs/builtins/vector_helpers.md).
Mutation requires writable storage, capacity uses unary observation, and fill types
match exactly. Both clear names retain capacity, explicit compact preserves contents,
unary compact is best-effort, and negative counts/capacities preserve `value_error`.

The runtime-first provider probe passed, but strict explicit-move testing exposed
a missing contract: vector_resize can receive a moved unique fill but its provider
must copy that value. The user authorized the bounded prerequisite during this goal.
`requires_copy` now travels from runtime parameter metadata through concrete/generic
contracts into shared call preparation. Existing source-aware capability queries
reject noncopyable fill types regardless of caller transport or requested count.
There is no new inference/ranking rule, runtime implementation, or builtin-name branch.
Both fill helpers declare the requirement. Unconstrained consuming calls still admit
explicit unique moves. Structural callable types, references, observation and packs
cannot use this provider-only flag.

V-H10 now initializes `vector<mixed>` explicitly before attempting unsupported closure
boxing. The negative fixture reaches that intended boundary. Typed callable vector
append/invocation passes, while an incompatible callable signature rejects. Its old
source, hash and masked diagnostic remain in baseline evidence.

## Verification

[Results](closure_batch_results.json), [audit](closure_batch_audit.json), and
[compressed raw evidence](closure_batch_evidence.json.gz) identify the tested snapshot
at `/home/alex-ai/closure-batch/20261020-01` on `alex-ai@dev002.travelfuse.ro`.
The base revision is `4416616e`; [initial](closure_batch_source_initial.json) and
[final](closure_batch_source_final.json) manifests identify the exact overlay.
The audit checks the real compiler ownership directories and all 6,297 snapshot
files, rather than assuming compiler implementation lives under a `src/` prefix.

- 282 accepted commands and 90 native executions across the accepted suites.
- S09: 16 payload signatures, 20 missing-state cases, exact/generic priority,
  ambiguity and nine other rejected boundaries; successful and failing argument
  evaluation order; four retained-session failure/retry/repair/no-op cycles.
- V-R07: all eight forms; scalar/string/container copies; aliased fills and mutation
  snapshots; readonly/temporary observation; noncopyable non-filling operations;
  nine negative-size/effect checks; nine ordinary source rejections; two explicit-move
  rejections; five invalid metadata forms; generic/concrete copy-contract rejection;
  unconstrained-move positive control; two retained recovery cycles.
- Production vector publication and native cold/warm builds pass, with no compilation
  or linking on the warm build and exact `3:9:0` output.
- V-H10: valid initializer, intended negative diagnostic, typed callable invocation,
  and incompatible-signature rejection.
- Focused regressions cover runtime catalogs/generics/signatures, conversion,
  nullable/Result/reference models, callable storage/variance/inputs, JSON, search,
  replacement and 46 conditional programs. JSON composition through checked payload
  extraction passes for mixed/dynamic payloads, including malformed-input failure.

Generated behavior probes use ASan/UBSan where their runners declare those flags;
this does not claim all regression runners use both sanitizers. Execution used the
shared 12-slot runners with PHP CLI fallback because installed CLI/FPM versions differ.
No full matrix or native compiler rebuild was performed. Runtime implementation
hashes are unchanged; only core metadata changed under `runtime/`.

## Preserved failed attempts and scope

The evidence keeps initial S09/V-H10 failures, synthetic catalog lifecycle mistakes,
vector fixture syntax/fixed-width comparison mistakes, the non-self-contained lower
provider header, the confirmed moved-fill copy failure, and the corrected probe that
tried assigning structured errors to sentinel-only carriers. Result error states and
sentinel states retain their existing distinct contracts. An obsolete JSON negative
expectation was migrated to positive/error behavioral coverage under the approved
fallback policy; it was not treated as a compiler regression.

No batch blocker remains. Wider language gaps, indexed operations, legacy syntax,
generic inference extensions and runtime redesigns remain outside this closure.
Historical reports retain their dated partial-S09 counts; the current ledger and
[overall progress](language_repair_progress.md) supersede those snapshots.

## Implementation commits

- `28f11160`: shared call contracts, S09-B1, authorized provider-copy prerequisite
  and focused regression expectation updates.
- `6ddba5c1`: V-R07 core bindings, vector contract and source/production proofs.
- `caf7adc7`: V-H10 fixture migration and callable-container controls.

The final evidence/ledger commit follows these implementation commits. Unrelated
Chapter 15 review/debt edits and local Python caches are excluded.
