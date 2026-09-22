# Entry selection checkpoint
Doc Status: derived

Two files migrate the source-policy portion of Entry_Resolver: select the implicit
manifest-file callable and reject top-level statements in supporting files. They use
fixed frontend/symbol snapshots, retain exact selected identity, and never inspect the
filesystem or infer a return type. Supporting declarations remain valid. Even empty
blocks and apparently dead branches are executable syntax for this policy.

**The full typed entry contract is not complete.** It needs the provider catalog's
shared integer named definition, including lifetime/family/operation semantics. The
result is therefore named Entry_Selection. No type descriptor stub, default native int,
backend exit convention or generic session lifecycle was fabricated. Next is the
actual type-model/catalog dependency, followed by return-type binding.

## Evidence

44 independent expected PHP/native outcomes cover selection/switches/reordering,
empty files, supporting declarations/statements, byte diagnostics after a UTF-8
comment, repair, exact snapshot identity and invalid/missing/extra/stale membership.
Nine PHP serialization assertions prove purity of success and failure. Guarantees and
representative inputs were adapted from the retained features/program_entry.php test;
its full provider/signature/body/session suite was not executed.

The native checkout was clean before/after and pinned to
`9b4b33f35f053b487e018c94d6a4a7888d77c64a` with clang++-18 and strict mode.
The first build passed 43 outcomes. Post-checkpoint source review added a position
upper-bound guard and a 44th case for stale path indexes after malformed membership
truncation. The final native run verifies that revised source and the host-purity gate.

```sh
python3 compiler/tests/entry_preparation/run.py --results /tmp/FRESH-entry-php
python3 compiler/tests/entry_preparation/run.py --results /tmp/FRESH-entry-native --target-checkout /tmp/scpp-json-240-probe
python3 tools/php_portability/validate.py --results /tmp/FRESH-entry-fast
```

Cumulative PHP/tool validation covers **41 registered production files**. Native proof
combines this dependency-closure build with earlier unchanged component proofs; the
cumulative fast command itself does not request native execution. Saved summaries
contain exact commands, source hashes, target revision and per-command durations.
Source/test/reference hashes are also recorded in provenance.json. Logs have trailing
whitespace trimmed. Converter/framework/target and src-runtime-preparation are unchanged.

## Timing boundaries

- Authoring to first passing PHP behavior: **89.956 s (1m30s)**.
- First PHP-ready to first native-ready: **110.394 s (1m50s)**.
- PHP-ready to final native verification: **295.546 s**, including overlapping consolidation and the review correction.
- Initial native build: **48.111 s**; native execution: **0.007 s**.
- One build to first pass, zero native-failure corrective cycles, then one additional
  verification build after the source-review correction. Both native runs passed.
- Zero checker/converter corrections; one post-checkpoint source-review correction.

Initial inspection and final commit are excluded. First PHP-ready records the direct
host run and its source hashes; subsequent checker/converter gates do not replace it.
The first native-ready milestone remains intact after the review correction. A separate
final-native-ready milestone records the final source; do not describe first-native-ready
as the completion of all work. Milestones are elapsed observed workflow boundaries,
including waits and overlapping documentation/regression work, not isolated typing time.
Regression and consolidation intervals are not additive. timing.json preserves the
final verification and consolidation costs separately.
