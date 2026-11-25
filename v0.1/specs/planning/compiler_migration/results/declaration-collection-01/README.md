# Source declaration collection checkpoint
Doc Status: derived

Five files begin the rewritten 04_analyze/collect_symbols owner: compact file facts,
source symbol records/store, refresh results, extraction and a sequential coordinator.
Source syntax stays attached to exact frontend snapshots. Stable semantic IDs are
separate from dense storage positions and source paths. The candidate preserves
identity across edits/moves, omits removals and never mutates the previous watermark.

97 independent expected PHP/native outcomes pass: functions/constants/structs,
methods/templates/const receivers, implicit entries, lookup categories, exact record
reuse, full recollection, changed/reordered/moved files, removal/re-addition, duplicate
anchors, failed-candidate isolation, existing unsupported evaluation wrappers and
ID exhaustion. Nine additional host serialization assertions pass. These adapt the
retained symbol_collection.php and semantic_storage.php guarantees; their full compiler
session harness is not executed. Semantic comparison and runtime-provider behavior
are not claimed by this source-only proof.

## Reproduction and results

```sh
python3 compiler/tests/collect_symbols/run.py --results /tmp/FRESH-collection-php
python3 compiler/tests/collect_symbols/run.py --results /tmp/FRESH-collection-native --target-checkout /tmp/scpp-json-240-probe
python3 tools/php_portability/validate.py --results /tmp/FRESH-collection-fast
```

The native checkout was verified clean before and after, at exact revision
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`, with clang++-18 and strict mode.
All 97 native outcomes passed on the first build/run. The nine host purity assertions
were added during that build and passed separately and in the cumulative fast suite;
they are not additional native outcomes. Production sources did not change after the
successful native checkpoint. The current runner includes the host purity gate too.

Cumulative PHP/tool validation passed with **39 registered production files**. Native
evidence combines this component's dependency-closure build with earlier unchanged
stage proofs; the cumulative fast command itself did not request native execution.
Saved summaries contain commands, command durations, source hashes and the target pin.
Logs have trailing whitespace trimmed. Provenance records source/test/reference hashes.

## Timing and corrections

- Authoring to first passing PHP behavior: **253.558 s (4m14s)**.
- PHP-ready to native-ready: **145.344 s (2m25s)**.
- Native build alone: **47.233 s**; native behavior: **0.022 s**.
- **One native build, one native execution, zero native-failure corrective cycles.**
- Three checker correction rounds after PHP-ready: supported RuntimeException,
  qualified namespace constants, and supported concatenation instead of string casts.
- No converter, framework or target implementation changes.

The initial 97-outcome direct PHP run passed before structural checking. Its timestamp
is recovered from the captured output file's modification time, and its source hashes
are retained separately. Later PHP/check/conversion runs do not replace that first
checkpoint. This intentionally counts checker fixes after PHP execution worked as
stabilization cost, rather than moving them into authoring to improve the figures.
The first three checker failures and final passing run are saved.

Initial orientation and the final git commit are excluded. Milestone intervals include
waiting and overlapping test/documentation work; they are elapsed workflow times, not
isolated active typing measurements. Native-ready/regression milestones were recorded
when completion was observed. Timing/cycle JSON preserves these boundaries explicitly.

## Follow-ups

Next: entry-contract preparation in resolve_types. Runtime/family imports, semantic
comparison, source-only collector scheduling/segmented joins, exports, cache persistence
and compiler-session publication remain later work. Standalone constexpr/consteval
function collection still rejects as the prototype does, despite parser acceptance;
review that inconsistency explicitly. No provider state or resolved facts were invented.
`src-runtime-preparation` and the frozen reference remain unchanged.
