# Cost of the signed outer cursor guard

Doc Status: supporting

The unchanged source-transfer scanner is the control. Its signed cursor starts at zero and only increments while strictly below the nonnegative signed length. Each increment therefore stays representable, including on lookahead. No call receives the cursor by reference. This is a local invariant of these emitted loops. The source variant adds only `__builtin_assume(var_i_5 >= 0)` at the outer loop body entry. Classification, checked accessor, source lifetime, token slot construction, reserve and final move are identical.

The second control replaces the original x86-64 test and conditional jump with one equal-length NOP. The harness verifies that the entire linked .text is byte-identical outside those nine bytes. This tests guard versus NOP at fixed addresses. Recompiling with the proven fact also allows instruction selection and layout to change.

## Clang 18, dev002 CPU 7

The warm operator corpus is 8,192 bytes and emits 2,560 tokens. Each of 48 matched rounds per flavor times all three executables with nine batches of 128 scans apiece. Every six rounds use all six orderings. SMT sibling 23 is available to other work. Rates are medians per build. Speedups are medians of within-round throughput ratios, with their middle 50% in parentheses.

| Flavor | Original MiB/s | Fact MiB/s | Fixed-layout MiB/s | Fact / original | Fixed-layout / original | Fixed-layout saving ns/token¹ |
|---|---:|---:|---:|---:|---:|---:|
| cpp | 1162.3 | 1267.7 | 1261.6 | 1.090× (1.081–1.100) | 1.088× (1.074–1.095) | +0.213 |
| php | 1163.4 | 1268.9 | 1264.0 | 1.092× (1.081–1.097) | 1.089× (1.080–1.096) | +0.215 |
| js | 1162.7 | 1264.0 | 1263.8 | 1.089× (1.078–1.095) | 1.086× (1.072–1.097) | +0.208 |

¹ Positive means the control ran faster. The observed spread is descriptive, not a confidence interval. Hardware counters were not collected.

## Verification

All nine timed binaries passed 11 PHP-oracle fixtures and the failure/edit/recovery sequence, including retained results. In each original Clang object, the outer index/length comparison was followed by a sign test and a jump to the failure path before the first byte load. In the source variant the load follows the loop guard directly. In the fixed-layout control the original load address is unchanged. The two lookaheads still use the checked accessor and the original source-level length guards.

Reproduce with `python3 -B signed_cursor.py FIXTURES SLOT_WORK RUNTIME WORK --cpu 7`. [Raw samples, executable hashes and guard evidence](signed_cursor.dev002.json).

## Direct comparison with handwritten C++

On dev002 CPU 7, each flavor ran 48 adjacent pairs with alternating first executable. Each observation averages nine batches of 128 complete scans including result destruction. All six executables matched the PHP oracle on 11 fixtures and passed the edit/failure/recovery checks before timing. Binary hashes matched the previously inspected Clang 18.1.3 nonnegative build and the previously measured flavor-specific handwritten control. All use the previous-count hint on the same 8,192-byte, 2,560-token operator corpus.

| Flavor | Nonnegative MiB/s | Handwritten MiB/s | Median paired throughput | Throughput gap | Middle 50% of ratios | Extra ns/token |
|---|---:|---:|---:|---:|---:|---:|
| cpp | 1282.5 | 1556.1 | 82.6% | 17.4% | 82.2%–83.2% | 0.41 |
| php | 1309.0 | 1616.8 | 80.7% | 19.3% | 80.3%–81.5% | 0.45 |
| js | 1316.3 | 1586.1 | 82.9% | 17.1% | 81.9%–83.4% | 0.40 |

Gap is one minus the median within-pair throughput ratio. Extra time is the median paired elapsed-time difference divided by the token count. The middle 50% describes the spread of observed pairs. CPU pinning permits other work on the SMT sibling.

Reproduce with `python3 -B pinned_operators.py FIXTURES SLOT_WORK --signed-work SIGNED_WORK --cpu 7 --pairs 48`. [Samples and executable hashes](cursor_handwritten.dev002.json).
