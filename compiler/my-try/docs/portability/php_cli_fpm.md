# PHP CLI versus FPM measurement
Doc Status: supporting

Measured 2026-10-02 on the local development machine, with compiler sources at
`8b07db7e45fe969cd06e9aeaf007c9a9ef2bc444`. This compares PHP execution strategies;
it does not switch the test runners to FPM or change compiler semantics.

## Result

FPM is substantially faster for the measured PHP workloads. Median wall time across
three warm trials of all **341 source fixtures**, generating C++ without compiling it:

| Execution mode | 1 job | 12 jobs |
| --- | ---: | ---: |
| Current CLI (Xdebug develop mode, CLI OPcache off) | 23.967 s | 4.193 s |
| CLI, Xdebug disabled | 21.025 s | 3.379 s |
| FPM, Xdebug disabled, OPcache off | 6.961 s | 1.175 s |
| FPM, Xdebug disabled, warm OPcache | 2.029 s | 0.618 s |

At 12 jobs, warm FPM is **6.8 times faster** than the current CLI configuration and
**5.5 times faster** than CLI with Xdebug disabled. All fixture output hashes match
across every mode and request. The three 12-job fixture trials range from 4.074–4.221
seconds for current CLI and 0.613–0.621 seconds for warm FPM.

Additional workloads, also median wall time over three trials:

| Execution mode | 48 no-op requests, 1 job | 48 operator-suite runs, 1 job | 48 operator-suite runs, 12 jobs |
| --- | ---: | ---: | ---: |
| Current CLI | 2.107 s | 11.178 s | 1.899 s |
| CLI, Xdebug disabled | 2.143 s | 4.747 s | 0.856 s |
| FPM, OPcache off | 0.196 s | 2.603 s | 0.515 s |
| FPM, warm OPcache | 0.195 s | 1.640 s | 0.286 s |

The no-op median request latency was 43.31 ms for current CLI versus 3.89 ms for
warm FPM. Turning off Xdebug matters more for the operator suite than for no-op
startup. FPM without OPcache isolates worker reuse from opcode-cache effects.

## Method and boundaries

- CLI and FPM both use PHP **8.5.7**, the same CLI `php.ini` and extension scan
  directory. The inherent SAPI extension difference is CLI `pcntl` versus FPM
  `cgi-fcgi`; other loaded extensions match. JIT is disabled.
- Current CLI retains its configured Xdebug develop mode; the other three modes
  set `XDEBUG_MODE=off`. CLI OPcache remains disabled, matching the existing runner.
- A private FPM pool uses `pm=static`, 12 workers and a mode-0600 Unix socket under
  the result directory. No existing FPM service was changed. Both private pools
  stopped cleanly; their sockets were removed.
- Requests use `cgi-fcgi`, including client subprocess startup and response capture.
  CLI timing also includes process startup and output capture. No HTTP server,
  network round trip or direct in-process FastCGI client is involved.
- Fixture requests mirror the native harness's PHP work: bootstrap, `S2S_Proof`,
  source compilation, then repeated preparation/emission. The operator workload
  runs the actual `tests/operators.php` assertions in a fresh PHP request.
- Every mode first sweeps each workload to verify results and warm caches. This
  first-touch sweep is excluded from the reported warm trials. Request order is
  shuffled reproducibly per trial; modes are measured in fixed order. FPM pool
  socket availability took 82–348 ms in these two starts, excluded from steady-state
  measurements. These are not cold-filesystem or controlled whole-build benchmarks.
- OPcache timestamp validation stays enabled; file update protection is set to zero
  so the freshly generated benchmark entry can be cached immediately. The warm
  FPM pool reports 102 cached scripts at completion. Source PHS files remain input
  data and are compiled on each request; no compiler AST is retained across requests.
- Scheduling uses the same reusable runner with continuous slot refill. The test
  includes 1- and 12-job phases, each with three repetitions. It verifies response
  hashes and successful operator assertions, not merely response timing.

Configuration references: PHP's [OPcache settings](https://www.php.net/manual/en/opcache.configuration.php)
and [FPM pool settings](https://www.php.net/manual/en/install.fpm.configuration.php).

## Implications for my-try

An optional FPM execution path is worthwhile for repeated PHP compilation requests
and PHP-heavy checks. Disabling Xdebug for ordinary non-debug CLI checks is a smaller
possible improvement that does not require a daemon. Neither change has been applied
by this measurement task.

This does **not** imply a 6.8-fold speedup for the complete native validation. Its
previous run took 259.9 seconds and included native compiler construction and hundreds
of C++ compilations. The isolated PHP fixture phase here falls from about 4.2 seconds
to 0.6 seconds; integration would need a whole-run measurement under the same load.

Keep CLI available for CLI-specific argument/stream/exit behavior and as a fallback.
Before enabling FPM for general tests, define request input, output/error envelopes,
per-request temporary-directory isolation, timeout/cancellation behavior and worker
lifecycle. The successful operator suite is one representative PHP suite, not proof
that all 43 suites are HTTP/FastCGI-compatible. A persistent CLI worker or CLI disk
opcode cache was not benchmarked.

## Reproduction and evidence

The [benchmark tool](../../tools/benchmark_php.py) starts only its own private pools,
uses the shared 12-job runner, retains each trial and stops the pools in `finally`.
It needs installed matching CLI/FPM binaries plus `cgi-fcgi`; override its executable
and INI arguments when using another PHP version. Unix socket creation must be allowed.

```bash
mkdir /tmp/FRESH_FIXTURES
php compiler/my-try/tests/s2s.php /tmp/FRESH_FIXTURES
python3 compiler/my-try/tools/benchmark_php.py \
  --programs /tmp/FRESH_FIXTURES/programs.json \
  --results /tmp/FRESH_BENCHMARK --jobs 12 --rounds 3 --repeat 48
```

Measured evidence: `/tmp/my-try-cli-fpm-20261002-run2/summary.json`,
`measurements.json`, `environment.json`, generated entry/fixtures and private FPM
configurations/logs. The controller log is
`/tmp/my-try-cli-fpm-20261002-run2.log`. These temporary artifacts may disappear;
this report retains the results and the tool reproduces the experiment. An initial
sandboxed attempt was stopped after Unix-socket creation was denied; its incomplete
CLI samples were discarded from this comparison.
