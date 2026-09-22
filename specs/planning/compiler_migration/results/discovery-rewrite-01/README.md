# Discovery rewrite evidence
Doc Status: derived

Final combined validation passed on clean target
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`: seven production files,
35 manifest outcomes, 31 initial and 31 refreshed discovery outcomes in PHP/native.

## Native correction cycles

- Two attempts to the first successful discovery native build/proof.
- One corrective cycle: STAN stopped attempt 1 before C++ generation; rename the
  shared Json_Node schema wrapper to Json_View to avoid the runtime type collision.
- Two subsequent successful verification builds: manifest and discovery.
- Total four native build invocations, one failed and three passed.
- One separate checker-only correction: negative property default moved to explicit
  owner initialization. This is not a native iteration.

See `native_cycles.json` for explicit accounting. The first successful discovery
build took 19.134 seconds. Final verification: manifest 16.705 seconds, discovery
17.674 seconds. Final discovery PHP run 0.052 seconds, conversion 0.090 seconds,
unchanged reuse 0.047 seconds and native run 0.098 seconds. No native rebuild was
needed for the changed-files scenario; the same executable observed refreshed inputs.

## Elapsed activity time

Initial repository/skill/prototype orientation before the timer started is excluded.
Recorded times include tool waits. Documentation proceeded during cumulative test
waiting, so that activity is included in the cumulative-validation bucket. These are
wall-clock observations, not CPU time or a projection for all compiler stages.
The final Git commit is outside this measurement.

| Activity | Seconds |
| --- | ---: |
| implementation | 124.672 |
| proof-authoring | 16.820 |
| native-validation | 25.458 |
| native-debugging | 28.627 |
| cumulative-validation | 111.009 |
| consolidation | 40.422 |
| Recorded total | 347.007 |

`attempt-01/` preserves the failed build; `attempt-02/` the first successful proof.
`combined/`, `final-manifest/` and `final-discovery/` contain final logs, outcomes,
command durations and source hashes. Original disposable absolute paths are retained
for traceability. Rerun checked-in `compiler/tests/discovery/run.py` with fresh results;
fixtures are created by that runner. No binary, generated cache or symlink fixture is
committed. Windows policy branches are checked, but filesystem execution evidence is
Linux only. Refresh runs use new processes, not a persistent compiler session.

Optimization lesson: reuse the existing filesystem facade and keep conversion cheap;
new native failures can emerge at larger composition boundaries despite isolated
proofs. Reserve native checkpoints for those boundaries and final component proof.
