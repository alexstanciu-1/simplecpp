# Manifest rewrite evidence
Doc Status: derived

Final combined validation passed; 3 production files and 35 PHP/native outcomes.
Target: `9b4b33f35f053b487e018c94d6a4a7888d77c64a` (clean unreleased candidate).

Elapsed primary-activity time, including tool waits and consolidation, excluding the
final Git commit. This is not CPU time or a forecast for later compiler stages.

| Activity | Seconds |
| --- | ---: |
| inspection/design | 137.27 |
| implementation | 177.29 |
| validation | 252.39 |
| portability-debugging | 109.24 |
| consolidation | 240.15 |
| Total | 916.34 |

Final stage commands: PHP 0.058 s, check 0.156 s, conversion 0.078 s,
unchanged reuse 0.049 s, native build 18.801 s, native execution 0.035 s.

`combined/summary.json` records the full driver; `stage/` records the final native
proof, independent expected outcomes and source hashes. `initial-failure/` preserves
the native STAN return-path failure, resolved by removing unnecessary catch/rethrow
context in the reader. Commands retain original disposable absolute paths for audit;
rerun the checked-in stage runner with a fresh results directory. Generated binaries,
cache and filesystem fixtures are intentionally not retained here.

Review opportunity: native compilation dominates command execution; keep PHP-first
authoring and component-level native checkpoints. This first stage also paid for
shared JSON/filesystem adapters and restoring the active proof harness. Measure the
next stage separately before estimating throughput.
