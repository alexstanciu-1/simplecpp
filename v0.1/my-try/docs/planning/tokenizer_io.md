# Typed tokenizer and strict IO closeout
Doc Status: planning

S11 and S13 are implemented and verified on dev002, 2026-10-20. This closes two
original issues: **41/64 (64.0625%)**, zero partial and 23 open, a gain of **3.125
percentage points**. Original failures and declaration-only migration provenance
remain historical evidence; S02 legacy syntax is still deferred.

Implementation commits: `da7a03886` (binary-safe line reads) and `50334b158`
(metadata, concrete wrapper transport and proofs). Their input files match all
5,337 hashes of the tested working snapshot based on `fd80f3021`.

## Implemented scope

- The tokenizer module declares the shared `token_buffer`, all four entrypoints,
  seven accessors and `token_buffer_to_mixed`. The existing adapter already owns
  mixed-family containers; it needed no runtime migration.
- The IO module declares shared `resource_handle` and twelve forms across ten
  helper names. Both seek forms use the existing `nullable<int>` application;
  both line-read forms preserve the established false-sentinel result contract.
- Buffered line reads now preserve embedded NUL bytes. Positive width retains
  the width-minus-one rule; width one does not advance. EOF-before-data is false,
  and partial final lines remain present. Other stream/provider semantics stay
  with the existing resource implementation.
- The authorized prelowering prerequisites reuse the existing representation,
  compiled-operation and metadata owners. Provider-owned handle payload headers
  remain behind compiled calls. Prepared `take`, wrapper construction/extraction,
  native/composed provider adaptation and lazy coalesce presence use those owners.
  Core error/mixed bindings request concrete application storage. Owned runtime
  member results use the existing owned-result adapter.

No alternate metadata registry, runtime wrapper algorithm, exception transport or
layout-query implementation was introduced. Mutable provider wrapper-reference
arguments reject explicitly pending their own write-back transport; this does not
restrict `take` output writes or the IO handle argument snapshots proved here.

## Remote integration and approval history

Remote `a0057fb13` was merged through `fd80f3021`, preserving local commits and
uncommitted work. Its runtime-owned representation recipes and default strict
prelowering are the implementation baseline. The earlier request for a new nullable
metadata form was withdrawn: the existing canonical template application suffices.
The user separately approved handle visibility and `take`, then the broader bounded
wrapper transport prerequisites after runtime/native probes exposed them.

[Checkpoint history](tokenizer_io_evidence/checkpoints.md) preserves the original
failures and decisions. Earlier ordinary-mode passes were not used as substitutes
for final default-prelowering acceptance.

## Final verification

All native work ran in `/home/alex-ai/tokenizer-io/20261020-final` on dev002 from a
clean archive. All **5,337 source-input hashes** match the frozen
[source manifest](tokenizer_io_source_final.json). No full operator matrix or native
compiler rebuild was run.

| Gate | Evidence |
|---|---|
| Runtime contracts | 190 checks pass; baseline had two embedded-NUL line-read failures. Unchanged native filesystem/stdio regression also passes with ASan/UBSan. |
| Source/runtime controls | Five generated programs pass with ASan/UBSan: original tokenizer, two declaration-only IO migrations, tokenizer controls and IO controls. Fourteen invalid-source cases reject; three failure/retry/repair/no-op cycles pass, including concrete `take`. |
| Default production | Eight cases pass cold/warm builds and exact stdout: original tokenizer, both IO migrations, nullable/Result take states, error states, IO snapshots/lazy fallback, all tokenizer forms/readable adapter, and bidirectional provider round-trips. Each warm build compiles and links zero units. |
| Production smoke | The ordinary-control runner also publishes through the default API and builds with strict application template validation and no runtime PCH. Cold/warm execution passes. |
| Representation regressions | Runtime binding proofs pass O0, O2 and sanitizer builds; source lifecycle passes ordinary/concrete parity, warm reuse, method edits and layout edits. |
| Contract/recovery regressions | Remaining unsupported-boundary rejection/recovery, closure coverage, acceptance reporting, production catalog composition, runtime protocols and retained take preparation all pass. |

IO proofs cover shared cursor/closed state, open failure, EOF versus empty data,
optional seek/read-line arguments, binary bytes, later-argument handle replacement,
once-only source evaluation and output writes, descriptor release and cleanup.
Exceptional source unwind is proved through the ordinary-mode ASan/UBSan control;
source exception transport remains explicitly outside the concrete-mode scope.
Tokenizer proofs cover byte locations, all accessor errors, owned input lifetime,
extended lengths, mixed adapter schema and handle snapshots.

The [machine-readable results](tokenizer_io_results.json) and
[hash-indexed archive](tokenizer_io_evidence/acceptance.tar.gz) retain **1,066 evidence
files**, including the baseline and intermediate failures. Every archived file was
hash-verified after retrieval. The archive README records its checksum.

## Review of earlier work under the new default

Separate final-snapshot probes confirm two existing repairs need concrete-mode
follow-up: regex callbacks reject for missing concrete callable transport; compact
layout queries reject for missing concrete compiler-helper transport. Their earlier
ordinary-mode evidence and original scoped closures remain intact. The 41/64 repair
count is not a claim that all 41 earlier scopes have passed the new default mode.
These gaps are recorded explicitly and are not fixed or folded into S11/S13.

Other exclusions remain: borrowed spans, stable hashing, curl, raw descriptors,
async IO, general ownership redesign, broader boxed operations and a full matrix.
