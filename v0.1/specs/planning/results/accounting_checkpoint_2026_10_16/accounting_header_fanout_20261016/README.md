# Header fan-out investigation evidence
Doc Status: supporting

See the [report](../accounting_header_fanout_20261016.md).

`analysis.json` lists all 89 units, forward-only compile outcomes/diagnostics,
original three-trial timings, generated-output counts and isolated trace summaries.
`link-proof.json` records the 1,855 passing checks and unchanged publication hashes.
`probe.py`, `link_probe.py`, and `summarize.py` preserve the diagnostic scripts;
they use experiment-specific paths, not general production/test entry points.
The task pool's PASS for a probe means an observation was captured; the recorded
Clang exit code determines whether a complete definition was required.

Raw local inputs, diagnostics (including discarded attempt 1), and full traces:
`/home/alexv/__AI/simple_cpp/simple_cpp_01/sandbox/accounting-recovery-20261016/header-fanout-analysis-20261016/`.
Original timing input: sibling `native-edit-matrix-20261016/dev002-results/edit-results/`.

Remote scratch: `/home/alex-ai/accounting-edit-matrix-20261016/header-fanout-analysis/`.
Scripts were run there; successful artifacts are under `attempt-2/`. Baseline
`bench/published-1`, `bench/build-1`, accounting sources and runtime are unchanged.
For reproduction, select a fresh scratch directory and adapt pinned paths; scripts
intentionally refuse to overwrite existing copied-output directories. Stage
summarize.py in the local experiment layout above, not directly in this docs folder.
