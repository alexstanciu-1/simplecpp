# S09 byte-search slice
Doc Status: supporting

Four `strpos` / `strrpos` signatures are implemented and verified on dev002: each
accepts two string arguments, with an optional third canonical integer offset.
The unchanged original string-edge fixture passes with exact output. S09 remains
**partially implemented**: `substr_replace` is deferred, and the original
false-branch fixture exposes a separate runtime-call argument boundary, S09-B1.

## Contract and ownership

The normative [strpos](../../../../specs/builtins/string/strpos.md) and
[strrpos](../../../../specs/builtins/string/strrpos.md) contracts require byte offsets
and absolute byte positions. Results use `result_or_false<int>`; position zero is a
payload, distinct from the false sentinel. Both positive and negative offsets are
accepted within inclusive byte-size bounds. Out-of-range offsets throw `ValueError`,
including signed minimum/maximum and searches for empty or absent needles.

Forward search starts at the normalized offset. Reverse search uses nonnegative
offsets as the earliest permitted match start and negative offsets as the latest
permitted match start. A full match can extend beyond that latter start boundary.
Empty needles match byte boundaries; arbitrary bytes require no UTF-8 decoding.

The shared strings owner adds `byte_rfind`, using the existing overflow-safe
reverse-offset normalization. Dedicated PHP adapters `strpos_bytes` and
`strrpos_bytes` shape results into the falseable carrier. Forward search normalizes
signed offsets before calling existing `byte_find`. Four strings metadata rows
bind source names to these adapters with ordinary value parameters and snapshots.
No compiler implementation changed.

The historical `scpp::php::strpos` / `strrpos` adapters continue to call code-point
`find` / `rfind`, preserving existing generated consumers and portability text
helpers. The explicit `string_byte_find` API still rejects negative offsets. These
are separate named contracts, not a global semantic change to shared text helpers.

## Verification

Host: `alex-ai@dev002.travelfuse.ro`; snapshot
`/home/alex-ai/string-search/20261020-01`; starting commit `d0f18cf7`.
PHP 8.5.11 uses CLI fallback because FPM differs; Clang 18.1.3 compiles native
programs. Shared runners use twelve refilled slots with independent suites
scheduled concurrently. No full operator matrix or native compiler rebuild ran.

- The runtime-first baseline returns code-point positions `1,3` for `éaéa`, while
  byte search finds the first `a` at `2`; existing byte_find rejects offset `-1`.
- **1,760 runtime checks** compare four callable forms against an independent
  byte-by-byte oracle. Eight haystacks and ten needles include empty strings,
  overlapping matches, UTF-8, embedded NUL and invalid UTF-8. Ten offset cases per
  pair include negative/positive boundaries and signed integer extrema; repeated
  boundaries for short strings count as executed checks, not unique signatures.
  Inputs remain unchanged. UBSan is enabled.
- Compiled source repeats all **1,760 checks** under ASan/UBSan. Additional observers
  verify left-to-right once-only producers, mutation of earlier input storage by
  later arguments, snapshots for all four signatures, error propagation before
  the third argument, zero/false distinctions, coalescing, saved results and explicit
  extraction followed by substr. Historical code-point and explicit-byte controls
  pass in both the direct runtime and source observer programs.
- **18 invalid calls** reject wrong arity, wrong scalar/container/boxed arguments
  and borrowed results. **Four incremental recovery cycles** verify failed retries,
  output withholding, repaired/fresh equivalence and no-op stability.
- `strict_strings_002_str_edges.phs` passes unchanged with exact stdout, empty stderr
  and exit zero. All three inspected original fixtures match pinned v0.1 bytes.
- Focused regressions pass **32 string programs**, including catalog/generic checks,
  **six non-bool sentinel equality programs** covering 72 signatures/recovery sequences,
  and **46 conditional programs** with rejection, scope, span and recovery checks.

Acceptance records **236 expected command exits** and **88 native executions**:
84 regression programs, two source programs, one repaired runtime program and one
baseline runtime probe. All **6,281 remote source hashes** match. Production changes
are limited to the two runtime string headers and strings metadata; compiler
implementation remains unchanged.

## Remaining blockers and historical evidence

**S09-B1:** `strict_strings_004_strpos_false_branch_regression.phs` now resolves
`strpos`, but then passes its `result_or_false<int>` directly as `substr`'s integer
length argument. Preparation reports `S2S runtime call requires one exact signature;
use explicit argument types`. A minimal reproducer retains that failure after a
false-sentinel check. The check does not narrow the static wrapper type. An explicit
`take` control extracts the integer and returns `varchar` through the same substr
provider. Whether to add implicit checked extraction, narrowing or another source
compatibility rule requires a separate contract discussion; this slice chooses none.

`strict_strings_001_str_surface.phs` still stops at `substr_replace`. Its omitted
length and unusable negative-length behavior require the separate discussion already
recorded in the runtime inventory. Later masked failures are not claimed absent.

Two old string rejection cases became positive byte-search controls. The first
conditional regression run failed solely because its original-fixture assertion
still expected unresolved `strpos`. Updating it to the newly exposed S09-B1 boundary
made the focused suite pass; no conditional compiler logic changed. The failed run
and all earlier probes remain in [the evidence archive](s09_search_evidence.json.gz).

[Results](s09_search_results.json), [initial manifest](s09_search_source_initial.json),
[final manifest](s09_search_source_final.json) and [static audit](s09_search_audit.json)
retain source provenance and pinned-fixture checks. Documentation closeout follows
the tested snapshot. Earlier S06/S07 reports retain their dated observations and
must not be read as current first-blocker descriptions.

The approved search slice is complete. Original-issue closure remains **26/64
(40.625%)**, with **one partial issue** and 37 other open issues. Replacement,
implicit wrapper argument conversion, wider overloads and compiler narrowing were
not implemented. No fixture migration or full matrix rerun was performed.
