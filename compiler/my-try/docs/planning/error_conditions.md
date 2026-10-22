# VR08-H01 — concrete error conditions
Doc Status: planning

## Agreed scope and closeout — 2026-10-22

VR08-H01 is repaired and verified on dev002 with default prelowering. The original
64/64 strict-milestone dispositions remain unchanged; this closes a supplementary
finding. No runtime implementation changes or full matrix rerun were made.

The shared prepared `error_condition` decision now uses the existing compiled
runtime-operation owner. Its helper delegates to `scpp::php::condition_truthy` and
borrows the operand within the full expression. The existing boolean observation
transport is shared with presence observations; the operation retains its distinct
semantic identity. No generic truthiness admission, boolean cast or new registry
is introduced. Ordinary lowering is unchanged.

Default/no-error is false; explicit errors are true even with an empty message.
Copies preserve state and details. Observation neither clears nor consumes an
error and does not repeat expression evaluation. The owning semantic authority
remains `specs/error_values_and_take.md`.

## Runtime-first evidence and approved prerequisite

`20261022-error-condition-baseline` proves the native runtime helper supports the
required states/copy/once-only behavior. All three source probes reproduce the
prior `runtime condition requires concrete transport` rejection.

After the bridge repair, `20261022-error-condition-acceptance` passed extraction
but exposed a separate constant-emission defect: `error::$no_error` named a native
static member on concrete storage. The user approved bounded repair of that
prerequisite. Concrete emission now materializes the existing default error value
through its stored representation; immutable value semantics are unchanged.

The first retained fixture omitted explicit core metadata setup and failed binding
lookup. The fixture was corrected; no compiler registry or runtime changes were
needed. These earlier attempts remain archived, not counted as acceptance passes.

## Final acceptance

All runs are on `alex-ai@dev002.travelfuse.ro` under `/home/alex-ai/curl/`, using
the shared runner, 12 task slots and CLI fallback. No native compiler rebuild is
claimed.

| Run suffix after `20261022-error-condition-` | Evidence |
| --- | --- |
| `final` | Runtime helper plus three source programs: state/copy/constant, snapshots/once-only/if/ternary/while/do/for, and take failure followed by success clearing |
| `retained-closeout` | Five edits plus fresh publication; four diagnostic-checked rejections and recovery cycles; fresh/retained artifact equivalence |
| `disabled` | Four original disabled-provider UI cases, including restored direct `!$error` assertion, build/SDK contracts and recovery |
| `gui` | Native enabled smoke plus four GTK/WebKitGTK cases; direct error-state assertion after failed load; existing snapshots/replies/lifecycle retained |
| `contracts` | 17 existing error-value generated programs using ordinary lowering, plus their preparation/recovery checks |
| `take` | Existing take preparation/recovery suite |

All new source/retained and GUI publications forbid application templates and pass
cold/warm execution. Warm builds compile zero units without linking. The disabled
lane isolates package discovery with `PKG_CONFIG_LIBDIR=/nonexistent-vr08-sdk` and
empty `PKG_CONFIG_PATH`; installed SDKs remain untouched. Enabled acceptance uses
GTK 3.24.41 and WebKitGTK 2.52.6.

Rejected cases remain error-to-bool assignment, explicit bool cast, error/null
comparison and error arithmetic. Other condition families, broader error operators,
other GUI platforms and full-matrix/native-compiler parity remain outside this goal.

[Compact results](error_condition_results.json) and the
[evidence archive](error_condition_evidence/README.md) preserve command logs,
publication manifests, historical failures and the final eleven-input source hashes.
