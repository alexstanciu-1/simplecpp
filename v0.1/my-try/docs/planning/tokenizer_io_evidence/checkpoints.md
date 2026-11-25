# Typed tokenizer and strict IO exposure
Doc Status: historical

Approved goal, 2026-10-20: close S11 and S13 through runtime-first probes,
registered shared handles, focused source/state/error/lifetime proofs and dev002
production builds. Target two original closures: 39/64 to 41/64, +3.125 points.
**Not closed yet.** The original issue ledger remains unchanged.

## Current checkpoint

Base `608ef3b5`; remote work is isolated at
`alex-ai@dev002.travelfuse.ro:/home/alex-ai/tokenizer-io/20261020-01`.
The initial archive and source manifest preserve the pre-repair snapshot.

- `runtime-baseline-01`: 190 direct runtime checks, two observed failures:
  bounded and unbounded line reads drop embedded NUL bytes.
- `source-baseline-01`: tokenizer entrypoints, resource type and IO names fail
  admission before registration.
- `runtime-01`: all 190 checks pass after the byte-preserving line-read repair.
- `runtime-02`: the same checks and the unchanged native filesystem/stdio
  regression suite pass with ASan/UBSan enabled.
- `tokenizer-01`: initial metadata omitted the required explicit capabilities
  array; schema validation rejected it. Preserved as authoring evidence.
- `tokenizer-02`: the unchanged original typed-tokenizer fixture and generated
  controls pass. Four entrypoints, accessors, mixed adapter, source lifetime,
  extended lengths, invalid indices and handle snapshots are covered.
- `tokenizer-03`: the same executions plus six preparation rejections, one
  retained failure/retry/repair/no-op cycle and production cold/warm builds pass.
  Production stdout is `3\n`; warm compilation and linking counts are both zero.

The tokenizer's `table_()` helper already builds a mixed-owned hash. Its readable
adapter requires no migration; the earlier concern based on the helper's name is
resolved by implementation inspection and runtime/source proofs.

`read_file_line` now preserves binary bytes using buffered stdio character reads.
The positive width retains fgets' width-minus-one rule; width one returns a present
empty string without advancing. EOF before any byte remains false; partial final
lines remain present. This is a bounded shared provider repair, not a resource
ownership redesign.

## Remote integration and nullable metadata

Merged `origin/v0.2/develop` at `a0057fb13` through merge `fd80f3021`.
The merged compiler registers `nullable` as a canonical template and runtime
metadata already composes registered type applications. Both `io_seek` forms now
use `{"name":"nullable","arguments":["int"]}`. No new metadata schema,
retained structure or alternative runtime representation is needed. The earlier
approval request for a recursive nullable form is superseded by this inspection;
no such extension was implemented.

The remote also makes strict concrete prelowering the production default. Earlier
proofs remain historical evidence for their recorded source snapshot. Verification
must be repeated against the integrated source, distinguishing ordinary semantic
fixtures from production concrete transport coverage and recording any newly
exposed transport gaps separately.

## Integrated verification and prelowering review

Integrated checks run in `/home/alex-ai/tokenizer-io/20261020-02` on dev002.
Strict template prelowering is the production acceptance path. Ordinary lowering
is retained only as an explicitly selected semantic/runtime diagnostic control;
its passes do not close production support.

- `runtime-01`: all 190 direct runtime checks and the unchanged native filesystem
  regression pass against the integrated runtime with ASan/UBSan.
- `source-01`: default lowering rejects the original fixture's `take` operation
  with `prelower_templates: take operation requires concrete transport`.
  This is an explicit merged backend limitation, not missing IO metadata.
- `source-02`: ordinary source controls expose an authored probe mistake:
  `new Exception(...)` is unsupported. The probe now uses the established
  `Exception::make(...)` factory; original compatibility fixtures are unchanged.
- `source-03`: all five ordinary generated executions pass: original tokenizer,
  both declaration-only IO migrations, tokenizer controls and IO controls.
  The default production build rejects the test driver's implicit runtime PCH.
  The production invocation now explicitly uses the supported `--no-pch`.
- `source-04`: the same five executions pass. Default production publication
  succeeds, but native application validation rejects template leakage.
  Published `entry.cpp` includes `core/resource.hpp` and
  `modules/tokenizer/tokenizer.hpp` despite using concrete handle storage and
  compiled runtime-call interfaces. Cold build fails; warm build is not reached.

The first dependency-owner review points to `CPP_Types::representation_for`:
concrete handle representation can merge complete pointee headers into application
dependencies. Runtime handle bindings must use the existing concrete representation
and dependency protocol; suppressing template validation or switching production
back to ordinary lowering is not an acceptable resolution. Further inspection must
also cover opaque handle pointer interfaces and their payload declarations before
choosing a repair.

Earlier work needs mode-specific review as well. The merge deliberately preserves
the strict compiler-helper rejection ahead of ordinary layout-query emission.
Consequently the prior compact-layout proof is not evidence that layout queries
work in default prelowering. Its unchanged runner still constructs default
`Compiler()` instances and must be reviewed alongside the regex-callback and
boxed/coalesce proofs. Historical closures and counts are not silently rewritten;
new production-mode gaps need explicit evidence and disposition.

### Authorized prelowering prerequisites

The user authorized runtime-handle application visibility and concrete `take`
transport on 2026-10-20. The current uncommitted implementation:

- Keeps provider-owned nominal pointees behind compiled runtime calls; concrete
  stored handles retain their shared lifetime but no longer publish native pointer
  interfaces or complete provider headers into application code. Source-owned
  pointee interfaces retain their existing behavior.
- Adds `take` to the existing runtime-operation requests. The source is copied
  before output writes. The payload destination uses its concrete object identity,
  matching composed wrapper payloads; the optional bool/error output uses native
  storage through the existing ABI. The runtime `php::take` helper still owns all
  state-dependent assignment rules.

`source-05` passes the five ordinary executions and gets past the application
template-leak check. Its production smoke then exposes missing concrete
required-value extraction (`require_value` emitted directly on concrete wrappers).

`prelower-01` uses default production publication/native builds for the original
tokenizer and declaration-only IO fixtures:

- Original tokenizer passes cold/warm builds and execution.
- IO failure reaches the provider ABI and fails: `io_open` returns native
  `result_or_false<shared_p<resource_t>>`, while composed storage expects
  `result_or_false<concrete_resource_handle>`. The existing runtime-call result
  adapter accepts only identical native/composed results.
- IO surface exposes direct nullable `has_value`/`require_value` calls from
  coalescing on concrete storage.
- A combined take-state proof exposes `error_t` provider-header template leakage.
  Error transport is now a separate retained test case.

`prelower-02` isolates nullable/false/bool take states and a source-before-write
probe. It exposes a separate literal-to-nullable return constructor mismatch.
The snapshot probe now creates an explicit typed payload before returning its
wrapper, preserving its state/order assertions; the failed original remains in
the remote evidence. This does not claim to fix literal-to-wrapper returns.
`prelower-03` also rejects construction from that named typed payload. Inspection
confirms a broader wrapper-conversion transport gap, not merely literal handling:
the current concrete conversion selector does not route wrapper construction or
required extraction through the existing conversion bridge. The new `take`
implementation therefore remains unverified; no take-state pass is claimed.

These are production-mode integration gaps. They are not permission to disable
strict template checking, change failure carriers, or close S11/S13 on ordinary
passes. Provider/composed wrapper adaptation and wrapper observation/extraction
need a separate bounded design decision; exception transport stays excluded.

The user subsequently authorized this prerequisite: extend the existing physical wrapper transport to
cover construction/extraction through prepared conversions, native-provider to
composed-wrapper adaptation preserving every carrier state, and usable-value
observation for lazy coalescing. Keep the application error representation behind
its existing runtime metadata binding. Reuse the existing operation, representation
and provider-call owners; do not add source semantics, runtime algorithms, a second
registry, or an IO-specific adapter family. Verify scalar and resource-handle
payloads, false/null/error/bool states, lazy fallback, source snapshots, write-back,
provider round-trips and original IO cold/warm builds. This crosses beyond the
two initial prerequisites; the additional approval was received before implementation.

### Wrapper transport implementation checkpoint

The current implementation reuses compiled runtime-operation requests and the
existing prepared conversion decisions. `CPP_Wrapper_Transport` owns physical
native/composed adaptation, with runtime family states preserved explicitly.
Coalescing obtains presence through a compiled bridge while leaving fallback
evaluation in the selected application branch. Core error/mixed bindings select
concrete boundaries; owned runtime-member results use the shared result adapter.
Internal true/false sentinel types now have bindings alongside the existing
internal null binding.

`prelower-04` passes the original tokenizer, both original IO migrations and a
basic Result take proof. It exposes missing internal sentinel bindings.
`prelower-05` passes IO snapshots/lazy fallback and nullable/false/bool take states;
the error getter needs concrete member-result transport. A tokenizer control
mistakenly expected flag zero after whitespace; it now expects the established
flag one. `prelower-06` passes six cases and exposes a remaining error payload
header dependency. The payload mapping now honors metadata concrete boundaries.

`prelower-07` passes all seven default-mode cold/warm production cases:
original tokenizer, four tokenizer forms/readable adapter, both original IO
migrations, IO state/snapshot/lazy fallback controls, take states and error states.
`source-06` also passes the five ordinary ASan/UBSan executions and its default
prelowered production smoke. These remain checkpoints: final regression,
source-hash/evidence capture, documentation closeout and commits are outstanding.

## Remaining acceptance

- Verify both seek forms through the existing nullable application metadata.
- Verify all IO forms, existing declaration-only IO migrations and source effects.
- Finish preparation rejection/recovery, native regression and production cold/warm
  checks on dev002.
- Freeze final source hashes and raw evidence, update the report/ledger only after
  closure, then commit coherent owner changes.

S02 comment syntax remains deferred. Borrowed spans, stable hashing, curl, raw
descriptors, async IO, general ownership redesign and a full matrix run remain out
of scope. Unrelated Chapter 15 working-tree changes and existing caches are preserved.
