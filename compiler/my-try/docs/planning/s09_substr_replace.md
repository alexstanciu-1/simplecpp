# S09 offset replacement slice
Doc Status: supporting

Both `substr_replace` signatures are implemented and verified on dev002. The
shared runtime now follows the published byte-window contract, and the full
original strict string-surface fixture passes unchanged with exact output.
Together with the preceding search slice, all six S09 API signatures and both
original S09 fixtures pass. S09 remains partial only for the separately tracked
S09-B1 runtime-call argument boundary in the false-branch fixture.

## Contract and owner

The [normative contract](../../../../specs/builtins/string/substr_replace.md)
admits string subject/replacement and canonical integer offset/length. Offsets
clamp to the beginning/end in bytes. Omitted length is exactly zero-length
insertion. Positive lengths remove at most the remaining suffix. Negative length
selects an end relative to the byte size; if that end is not strictly beyond the
normalized start, the operation returns the original value unchanged. This no-op
rule also applies at an append position. Arbitrary bytes need no UTF-8 decoding.

The shared `scpp::str::substr_replace` four-argument implementation reuses existing
`normalize_substr_start` and `normalize_substr_end`, including their overflow-safe
signed-minimum handling. It distinguishes unusable negative windows before one
output-construction path. The three-argument overload delegates with zero length.
This removes duplicate normalization/construction rather than adding a parallel
replacement implementation. Existing PHP adapters remain unchanged; two strings
metadata entries expose them through ordinary value parameters and snapshots.

This intentionally corrects existing runtime behavior, including existing generated
callers: omitted length formerly removed the suffix, and unusable negative windows
inserted text. Four native test expectations were reconciled with the normative
contract. Original v0.1 source files and stored output oracles were not changed.
Compiler implementation, other slicing/search behavior and implicit conversions
remain unchanged.

## Verification

Host: `alex-ai@dev002.travelfuse.ro`; snapshot
`/home/alex-ai/substr-replace/20261020-01`; starting commit `c37e0597`.
PHP 8.5.11 uses CLI fallback because FPM differs; Clang 18.1.3 compiles native
programs. Shared runners use twelve refilled slots, with independent regressions
scheduled concurrently. No full matrix or native compiler rebuild ran.

- Runtime-first baseline reproduces omitted-length suffix removal and insertion
  for unusable negative windows. Source baseline reproduces unresolved substr_replace.
- **3,312 direct runtime checks** and the same **3,312 compiled-source checks** use
  an independent wide-arithmetic, byte-by-byte oracle. Six subjects, five
  replacements, ten offsets and ten lengths cover empty strings, repeated bytes,
  UTF-8, embedded NUL, invalid UTF-8 and integer extrema. Short-string bounds may
  repeat; counts represent calls, not unique signatures. Additional self-alias
  checks verify both forms with identical subject/replacement storage. All inputs
  remain unchanged. Both suites use ASan/UBSan.
- Source observers verify once-only left-to-right producers, snapshots when later
  arguments mutate subject/replacement/offset storage, independent saved results,
  and an argument error that prevents later evaluations. A negative-window no-op
  still evaluates all four arguments and returns the original subject snapshot.
- **11 invalid calls** retain arity/type/reference-result boundaries. **Two
  recovery cycles** prove failed retries with withheld output, repaired/fresh
  equality and no-op stability.
- The unchanged `strict_strings_001_str_surface.phs` passes its full stored stdout,
  empty stderr and exit zero. No later blocker is exposed in that fixture. Its
  bytes match pinned v0.1.
- The existing full native `test_php_string.cpp` passes with the four corrected
  expectations, covering other string operations and historical code-point helpers.
  Focused string/catalog/generic regressions execute **33 native programs**.
  Search regression executes **three native programs**, repeating 1,760 search
  checks and the unchanged original surface/edge fixtures while retaining S09-B1.

Acceptance records **112 expected command exits**, **41 native executions** and
**6,285 matching remote source hashes**. Native executions include the baseline
probe, two runtime programs, two source programs and 36 regression programs.
All acceptance runs passed on their first native attempt. The shared strings
header and strings metadata are the only runtime implementation/binding changes;
the runtime catalog also receives supporting documentation. No compiler source
implementation changed.

## Evidence and remaining work

[Results](s09_replace_results.json), [all run logs](s09_replace_evidence.json.gz),
[initial manifest](s09_replace_source_initial.json),
[final manifest](s09_replace_source_final.json) and
[static audit](s09_replace_audit.json) retain provenance, exact fixture bytes and
runtime change bounds. Documentation closeout follows the tested snapshot. The old
string-suite unresolved-substr_replace assertion is replaced with a positive
insertion/no-op control; historical failure records remain in prior reports.

The approved replacement goal is complete with no remaining replacement blocker.
**S09-B1 remains open:** the false-branch fixture passes a `result_or_false<int>`
directly to `substr`'s integer length parameter. Search regression still observes
the exact-signature rejection, while explicit extraction succeeds. No implicit
extraction, flow-sensitive narrowing or fixture rewrite was introduced.

Overall closure remains **26/64 (40.625%)**, with S09 partial and 37 other open
issues. The next discussion can focus solely on S09-B1's call-boundary policy.
