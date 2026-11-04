# Direct scalar lowering: generated size and compilation comparison

Doc Status: supporting

Same three sample sources, unchanged preparation, same O2/g0/libc++/mold/Ninja8 configuration, no PCH/LTO for the main comparison. Five warmed PHP passes and three fresh-application native builds per sample. Runtime archive is prebuilt. This is a before/after measurement, not a controlled simultaneous run; ranges and trace limitations matter.

| Sample | C++ bytes before → after | Expansion before → after | Sequencing lambdas before → after | Native median ms before → after |
| --- | ---: | ---: | ---: | ---: |
| small | 14,643 → 8,653 | 5.34× → 3.16× | 59 → 8 | 185.628 → 164.089 |
| medium | 144,811 → 79,985 | 5.22× → 2.88× | 717 → 196 | 515.917 → 348.049 |
| large | 1,430,521 → 782,971 | 5.22× → 2.85× | 7,213 → 2,052 | 2137.976 → 1721.659 |

Counts refer to capture-by-reference sequencing lambdas. Two existing noncapturing string-truthiness lambdas remain in each sample; total lambda counts are recorded in JSON. Large generated size fell 45.3%, sequencing lambdas 71.6%, and the measured native median 19.5%. No runtime execution speedup is claimed.

| Sample | Frontend ms before → after | LLVM backend ms before → after | Optimizer ms before → after | Codegen ms before → after | Link median ms before → after |
| --- | ---: | ---: | ---: | ---: | ---: |
| small | 81.770 → 67.250 | 82.501 → 71.179 | 46.111 → 32.311 | 24.550 → 25.830 | 70.000 → 75.000 |
| medium | 493.837 → 272.185 | 835.261 → 469.977 | 588.052 → 276.995 | 198.222 → 155.496 | 78.000 → 76.000 |
| large | 3380.059 → 1982.337 | 6697.883 → 4677.908 | 5033.599 → 2891.927 | 1497.999 → 1620.095 | 67.000 → 76.000 |

Clang categories come from one separate instrumented build per sample and sum across all TUs. They overlap and are not additive elapsed time. Main native medians exclude instrumentation.

| Sample | Before native min–max ms | After native min–max ms |
| --- | ---: | ---: |
| small | 169.184–197.065 | 158.494–205.579 |
| medium | 470.079–538.421 | 309.270–385.841 |
| large | 2100.136–2436.683 | 1685.357–1723.694 |

## Incremental checks

All no-op/body-edit/signature-error/recovery checks passed. Preparation source files match the pre-change hashes exactly; generated preparation behavior is unchanged. Unchanged ASTs/tokens retain identity and no-op passes read zero sources. Body edits reread one file and prepare its two bodies; recovery after a signature error prepares those bodies and the caller. These timings exclude incremental emission/native compilation, which remains deferred.

| Sample | No-op ms | Body edit ms | Signature rejection ms | Recovery ms |
| --- | ---: | ---: | ---: | ---: |
| small | 0.405 | 0.620 | 0.743 | 0.739 |
| medium | 1.108 | 1.162 | 1.275 | 1.848 |
| large | 10.691 | 11.742 | 11.444 | 16.734 |

## Reused PCH experiment on the large sample

A separate round using the simplified generated code, with three rotated samples per mode. Every application object is rebuilt; the PCH timestamp must remain unchanged. PCH validation is enabled. The entry adapter does not use PCH. Creation is measured separately and excluded from reused-build timings.

| Mode | PCH creation ms | Native median ms | Native min–max ms | Link median ms |
| --- | ---: | ---: | ---: | ---: |
| no-pch | — | 1628.023 | 1551.523–2427.611 | 70.000 |
| minimal-pch | 104.218 | 1640.988 | 1545.253–1762.218 | 73.000 |
| used-headers-pch | 186.458 | 2008.582 | 1586.739–2289.616 | 68.000 |

Minimal PCH includes output/entry headers, matching the existing panel helper. Used-headers PCH additionally includes string/arithmetic headers. Neither demonstrates a clear benefit here; the broader PCH has a worse median with substantial variation. Our public headers are intentionally small and most translation units do not need the string header. This does not rule out benefits for future, header-heavy workloads. PCH cannot remove application-body lambda/optimization work. No production PCH settings were changed.

## Validation and artifacts

44 selected regression checks and 49 focused native executables passed. Proofs include scalar/string RHS mutation, ordered argument conversions, competing failures, suppression of later calls after failure, all three flavors and retained-session incremental changes. Native builds keep -Wall -Wextra -Werror; no warning suppression was added.

Sources match byte-for-byte across benchmark rounds. The implementation stays in C++ lowering and generated backend queries, without new AST facts, preparation passes or statement-prelude lowering. See [implementation](../../compiler/docs/direct_lowering.md).

Tracked measurements: [after results](benchmark-2026-11-04-direct.json), [raw PHP passes](benchmark-2026-11-04-direct-php-raw.json), [comparison and PCH data](benchmark-2026-11-04-direct-comparison.json).

Raw builds, traces and logs: `v0.2/build/capability-benchmarks-2026-11-04-direct-final/` and `v0.2/build/capability-benchmarks-2026-11-04-direct-pch/`. Benchmark sources/harnesses are in this directory. Failed exploratory warning-check outputs remain separate from the final measurement.
