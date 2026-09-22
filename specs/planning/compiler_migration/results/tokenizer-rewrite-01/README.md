# Tokenizer rewrite evidence
Doc Status: derived

Final combined validation passes on exact clean target
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`: fourteen production files,
manifest 35 outcomes, discovery 62, verified reads 40, tokenizer 304.

The retained tokenizer unit body executes through a host-only reference shim. Its
41 captured scans are replayed against PHP/native, supplemented by 261 reference
oracle cases and two new batch cases. `test_provenance.json` pins reused files and
harness hashes. Old incremental session tests remain pending, not counted as passed.

Native attempts to first pass: two; corrective cycles: one. STAN rejected the runtime
Token_Buffer name collision and terminal-throw debug-return path. Lexical_Buffer and
a total unknown-name fallback clear attempt 2. The two batch cases were added before
attempt 2. Four subsequent verification builds passed: six native build invocations
total, one failed and five passed. Three checker-only syntax corrections and one
host import-sync scope correction are recorded separately in `native_cycles.json`.

Final tokenizer: native build 22.975 s, PHP 0.268 s, conversion 0.274 s,
unchanged reuse 0.101 s, native execution 0.339 s.

## Recorded elapsed primary activity

Initial orientation before the timer and final Git commit are excluded. Times include
waits; documentation proceeded during validation. They are not CPU or throughput
benchmarks, and the byte-by-byte PHP scanner is not performance-equivalent to native.

| Activity | Seconds |
| --- | ---: |
| implementation | 129.354 |
| validation | 94.836 |
| native-debugging | 41.709 |
| cumulative-validation | 169.928 |
| consolidation | 0.145 |
| Recorded total | 435.971 |

Logs, outcomes and source hashes retain original disposable paths. Reproduce with
`compiler/tests/tokenizer/run.py --results FRESH`, adding explicit native target flags
at a checkpoint. Binaries/caches are excluded. The generated Token_Row declaration
was inspected as a native struct with three uint32 fields; no exact sizeof or memory
benchmark is claimed. Test source spans are exact because byte locations are contracts.

Next review: profile keyword classification and byte scanning against representative
source sizes before optimizing. Do not drop the compact record model or invent an
incremental coordinator merely to replay old session tests. `src-runtime-preparation`
is unchanged and stays PHP as-is for now. Next stage is the parser.
