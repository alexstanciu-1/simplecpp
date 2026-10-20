# V-R04 / V-R09 regex filter and replacement limits
Doc Status: supporting

Completed 2026-10-20 on dev002. Both approved issues close: **31/64 fully closed
(48.4375%)**, up from 29/64 (45.3125%), a gain of **3.125 percentage points**.
Zero partial repairs and 33 other original issues remain. This is issue-count
progress, not an engineering-effort estimate.

## Settled contracts and implementation

Strict `regex_filter` exposes three arguments and a fourth value limit;
`regex_replace` now exposes its fourth value limit. Both use positional
backreferences (`$1`, `${1}`, `\1`) with existing unmatched-capture behavior.
Filter keeps input order and includes rows where a replacement occurred even if
the output bytes are identical. Successful empty strings/vectors remain distinct
from false. Limits are unlimited at -1, perform no replacements at zero, and
apply per input string when positive. Other negative values throw existing
`ValueError`. Limit validation precedes pattern validation, including empty input
and zero limit; otherwise invalid patterns return false.

The existing shared regex implementation owns these semantics. A validated limit
and compiled pattern feed a single replacement worker; filter compiles once before
visiting rows. Zero-length matching now separates the copied-output offset from
the search offset, preserving untouched bytes. It tries a nonempty match at the
same position before advancing search by one character, respecting UTF mode.
The old loop's dropped-byte behavior was reproduced before repair.

Metadata binds four-argument forms to `replace_limited` and `filter_limited`.
These explicit entry points forward to the existing five-argument provider
contracts with a private ignored count. A generated writable value snapshot can
therefore never select the older count-output overload. There is no compiler
name-specific logic, changed overload ranking or new callable model.

Owning contracts: [filter](../../../../specs/builtins/regex/preg_filter.md) and
[replacement](../../../../specs/builtins/regex/preg_replace.md). Core regex metadata
now exposes 11 helper names / 21 signatures. Selection remains opt-in and carries
its existing PCRE2 build dependency.

## Verification and provenance

[Results](regex_replacement_results.json), [audit](regex_replacement_audit.json),
and [raw evidence](regex_replacement_evidence.json.gz) record the snapshot at
`/home/alex-ai/regex-batch/20261020-01` on `alex-ai@dev002.travelfuse.ro`.
The base revision is `6c25e526`; [initial](regex_replacement_source_initial.json)
and [final](regex_replacement_source_final.json) manifests identify the overlay.
All 6,302 final snapshot files passed the remote hash audit; local implementation
and tests match the tested bytes. Documentation closeout follows the snapshot.

- Baseline source probes reproduce all three missing forms. The initial runtime
  oracle records 13 failures among 51 checks, covering zero-length bytes, skipped
  validation and negative-limit precedence. These are expected historical failures,
  not candidate passes.
- The final oracle has 54 checks and zero failures, both directly against runtime
  providers and through compiled strict source. Added filter controls explicitly
  cover all three backreference spellings, zero-length matches and binary strings.
- Cases include no matches, identical-byte replacements, empty payloads, empty
  inputs, zero/positive/unlimited limits, invalid negative and minimum integer,
  invalid patterns and error precedence, empty/nonempty alternatives and UTF-8
  zero-length traversal. Sources remain unchanged by provider operations.
- Strict-source checks additionally prove default arities, unchanged limit values,
  left-to-right evaluation, once-only side effects and snapshots before later
  argument mutation. Provider failures occur after ordinary argument evaluation.
- Five preparation rejections keep wrong types and public count-output forms out.
  Two retained-session cycles prove failed/retried output withholding, repair
  equivalence and no-op stability.
- Production publication and native cold/warm PCRE2 builds pass with exact
  `Xba:Xba:XX:1` output. The warm build compiles/links nothing.
- Existing regex tests pass: five PHP preparation/configuration suites, ten source
  programs and the native runtime suite (including existing legacy forwarding
  controls). Two obsolete held-call rejections were migrated to a positive native
  limit/filter case. The initial regression failure is preserved.

There are 56 accepted commands and 16 native executions including baseline capture;
15 native executions belong to the candidate acceptance/regression suites. The new
runtime/source oracles use ASan/UBSan; this is not a claim that the existing regex
regression runner enables both. Shared runners use 12 slots and PHP CLI fallback
because CLI/FPM versions differ. Toolchain and command timings are in the evidence.
No full matrix or native compiler rebuild was performed.

## Remaining boundaries

No blocker remains for V-R04/V-R09. V-R05 callback ABI, public count-output
signatures and legacy wrapper migration remain separate. The PHP legacy filter
adapter still has its own input/key and identical-value filtering behavior; this
slice neither edits that adapter nor claims strict/legacy filter parity. Shared
provider fixes reach existing forwarding callers, and their regression controls
pass. Match-all/split/callback traversal algorithms were not rewritten.

Compiler implementation hashes are unchanged. Runtime edits are confined to
`modules/regex/regex.cpp`, its public header and `metadata/modules/regex.json`.
Historical held-contract findings remain in the ledger and baseline evidence;
the current coverage map supersedes their filter/limit holds.

## Implementation commits

- `cea1d5d4`: shared regex runtime, contracts and direct-provider oracle.
- `1d4fcbb2`: strict bindings, source/production proofs and regression migration.

The evidence/ledger commit follows these changes. Unrelated Chapter 15 edits and
local Python caches are preserved and excluded.
