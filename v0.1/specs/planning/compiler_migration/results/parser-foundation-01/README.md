# First parser slice evidence
Doc Status: derived

Three new production files: syntax row vocabulary, arena owner and scoped angle helper.
This is parser foundation, not implemented statement/expression/declaration grammar.
Active total is seventeen production files. Exact native target remains clean
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`.

Validation: retained 5,000-stream PHP unit passes against the rewrite; 414 focused
PHP/native outcomes pass (408 streams plus six storage/precedence outcomes). The
final cumulative PHP/tool driver passes. Unchanged earlier native stage evidence
is retained; no redundant final native rebuilds were requested.

Build success took one attempt. Behavioral success took two native attempts and
one corrective cycle: uint32 token tags were explicitly normalized to int before
strict comparisons. Both attempts compiled; the first returned empty angle maps
and failed comparison. A host-only oracle-generation typo was corrected separately.
See `native_cycles.json` and `provenance.json` for accounting and reused test hashes.

Successful native build: 25.192 s; PHP run: 0.157 s; conversion: 0.146 s;
unchanged conversion reuse: 0.054 s; native execution: 0.044 s.

## Recorded elapsed time

Initial inspection and final Git commit are excluded. Primary-activity times include
waits, with documentation during validation; they are not CPU or throughput claims.

| Activity | Seconds |
| --- | ---: |
| implementation | 58.948 |
| validation | 134.515 |
| native-debugging | 43.379 |
| cumulative-fast-validation | 117.925 |
| consolidation | 31.711 |
| Recorded total | 386.479 |

Logs/outcomes/source hashes retain disposable command paths. Rerun the checked-in
`compiler/tests/parser_foundation/run.py` with fresh results. Binaries/caches are
excluded. Reusing retained tests caught a real PHP/native discrepancy beyond build
success. The next grammar writer should normalize stored numeric tags at comparison
boundaries and mutate records only through the syntax arena owner. The added last-child
field avoids reference-parameter bookkeeping; measure its memory tradeoff later.

`src-runtime-preparation` remains unchanged PHP. Next work: expression grammar,
then statement/declaration parsing and their existing unit cases.
