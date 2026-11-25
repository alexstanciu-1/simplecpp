# Verified-read evidence
Doc Status: derived

Final combined validation passed on clean exact target
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`. Ten production files now have
PHP/native outcomes: manifest 35, discovery 62, verified reads 40.

Native accounting: one attempt to first pass, zero corrective cycles, then three
successful final verification builds (manifest, discovery, snapshot). Four native
build invocations total, all successful. One PHP-only correction moved a bootstrap
include into the existing bracketed namespace; its failed output is retained.
No converter grammar changes or generated-code fixes were needed.

First snapshot build: 23.013 s. Final snapshot build: 23.894 s; PHP execution:
0.052 s; conversion: 0.115 s; unchanged reuse: 0.058 s; native execution: 0.011 s.
The refresh scenarios use the same executable, without another native build.
The separate PHP cleanup probe preserves stream count across 100 successes and
100 rejected reads. It does not inject post-open kernel races or prove native flags.

## Elapsed primary activity

Initial inspection before the timer started and the final Git commit are excluded.
Times include waits. Documentation was written during validation waits, so validation
buckets include that work. These are elapsed observations, not CPU measurements.

| Activity | Seconds |
| --- | ---: |
| implementation | 74.336 |
| validation | 107.159 |
| cumulative-validation | 111.866 |
| consolidation | 32.129 |
| Recorded total | 325.489 |

Original disposable command paths are retained for audit. Reproduce via the
checked-in `compiler/tests/snapshot/run.py` using a fresh results directory. Logs,
expected outcomes and source hashes are saved; binaries/caches/OS fixtures are not.
The cleanup probe retains its original fixture path and requires that disposable
fixture to replay. Native execution is Linux-only. PHP cannot provide the target's
no-follow/nonblocking open semantics; see the owning verified-read contract.

Next optimization review: native compilation remains the dominant command cost.
Adding three compiler files required only a narrow existing-API adapter, with no
native corrective cycles. Keep these observations separate from the harder parser
and semantic stages; they do not establish a whole-compiler migration rate.
