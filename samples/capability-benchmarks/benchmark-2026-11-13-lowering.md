# Paired native compilation after the lowering series

Doc Status: supporting

The audited baseline is `f2221fa6a`. The checkpoint is `022af27bf`, which includes the three instance-receiver slices. The checkpoint reduced median clean native build time on the synthetic call-heavy workload by 25.8% on Clang and 11.1% on GCC. The medium billing project had overlapping ranges and split its clean-build pairs evenly on each compiler. There is no demonstrated general clean-build speedup for that project.

**Next slice:** investigate direct eager integer addition between a plain local and a scalar-returning source call taking only by-value scalar arguments. The billing entry contains 48 instances of `total = total + line(price, quantity)`. All 48 remaining sequencing lambdas in this project occur at these sites. An isolated substitution reduced entry-unit object compilation time by 27.7% on Clang and 28.3% on GCC. Each compiler improved in eight of eight pairs. This percentage applies to this one translation unit. Use prepared storage, passing modes, conversions and the native operation's defined evaluation rules; keep single evaluation and the generated operation's checks. Start with the measured shape and include aliasing and failure controls.

## Workloads and generated shape

| Workload | Source / native units | Application .cpp bytes | Application .hpp bytes | Sequencing lambdas | Named sequencing temporaries |
| --- | --- | ---: | ---: | ---: | ---: |
| Call-heavy | 1 PHP / 2 C++ | 81,987 → 56,364 | 0 → 0 | 384 → 192 | 769 → 384 |
| Medium billing | 15 mixed-flavor / 16 C++ | 108,362 → 97,122 | 8,214 → 8,214 | 99 → 48 | 394 → 194 |

Counts include the generated entry adapter. Header sizes count each published header once. Lambda counts match capture-by-reference immediately invoked lambdas. Temporary counts include declarations of argument snapshots, binary operands and previous compound values. Source locals and parameter declarations are excluded.

- **Call-heavy.** One synthetic PHP-flavor source defines `bump(int &$x)` (increments x and returns 2), `combine(int $a,int $b)` (addition), and `label()` (returns the string xy). It emits 64 separately named functions with the body below and sums `calc_i(i)` for i from 0 to 63. This workload deliberately amplifies call shapes. Its verified output is 3776.
- **Medium.** The checked-in medium capability benchmark uses PHP, C++ and JS flavors, billing arithmetic, string helpers and cross-unit calls. Its input files are identical in both revisions. Output is `invoice: 10469` followed by a newline. The body edit changes the first `return $subtotal;` in `billing/batch_000.phs` to `return $subtotal + 1;`. Fresh emission changed exactly one .cpp and no headers for both revisions. Rebuilding that object and relinking produces `invoice: 10470` followed by a newline. [Medium workload](medium/module.json)

```php
$a = combine($x, bump($x));
$b = combine(bump($x), 3);
$x /= 2;
$s = str_repeat(label(), 2);
return $a + $b + $x + strlen($s);
```

## Measurement conditions

Extract each revision's compiler, generator and runtime sources. Regenerate its own compiler bindings with its metadata, the same runtime publication and the same LP64 layout facts. Emit the projects with `php compiler/main.php project`. All C++ compilations use the checkpoint's public runtime headers and the same prebuilt Clang/libc++ archive (SHA-256 `c9b73cbb48b278f7bc704839d7b107b876e7414ad827d3e99bade528eff09b8c`). Changes merged between revisions are included in the baseline comparison; the two isolated experiments below change only the specified generated expressions.

- AMD Ryzen 5 PRO 4650G, x86-64 Linux. One job, pinned to logical CPU 2. Ubuntu Clang 18.1.3 with libc++ and GCC 13.3.0 with its default C++ headers. Compile flags were `-std=c++23 -O2 -g0 -Wall -Wextra -Werror`. Both compilers' application objects were linked with the same Clang/libc++ driver, mold and runtime archive.
- One warm-up and eight measured rounds. Alternate baseline-first and checkpoint-first, and alternate compiler order. Warm filesystem caches. No PCH, LTO or compiler cache. Clean runs recompile every application translation unit and relink. Edited runs compile just the changed unit and relink the existing objects. Every resulting executable was checked for exact output and exit status 8.
- Syntax-only is compiler-driver wall time with `-fsyntax-only`. Object compilation is serial wall time with `-c`. Native build is object compilation plus the linker invocation. Timings exclude source-language preparation, emission, build-system scheduling and executing the verification binary. The edited measurement is the native rebuild portion; emitter invalidation latency was outside the benchmark.
- Tables show medians and inclusive quartiles. Percentages compare medians; the last column counts same-round wins for the new variant. Linker variability and process startup are visible at this scale. These are warm single-machine observations. [Raw per-round timings](benchmark-2026-11-13-lowering.json)

## Runtime-call audit and candidate selection

The synthetic checkpoint unit has 64 runtime-call wrappers, all for `str_repeat(label(), 2)`. Each binds the string result and integer literal before calling the adapter. The adapter borrows a const string reference and takes its count by value. Passing the string temporary directly preserves its lifetime through this call, and the count literal cannot affect it. The isolated experiment replaced just these 64 wrappers in generated C++ and verified identical output. It removed 7,232 bytes, 64 lambdas and 128 temporaries. Clang's median object time fell 12.2%; GCC's rose 5.2% with overlapping ranges. The candidate appeared zero times in medium. This supports keeping runtime binding simplification on the backlog behind the eager-addition slice. A future runtime slice should test a borrowed string beside a mutating sibling and distinguish const borrows from writable reference bindings. [Runtime-call lowering](../../compiler/05_backend/cpp/emit.php)

For the eager-addition comparison, only the 48 matched expressions in the emitted medium entry unit were replaced, preserving the existing function calls, arguments and addition. Compile the original and changed entry units alone, alternating order for eight warmed pairs after a warm-up. Relink each with identical siblings per compiler and verify the output. Both experiments operated on disposable emitted C++.

## Native timing

| Compiler | Workload | Stage | Baseline ms [Q1–Q3] | Checkpoint ms [Q1–Q3] | Median change | Faster pairs |
| --- | --- | --- | --- | --- | --- | --- |
| clang | calls | Syntax only | 211.2 [186.1–236.9] | 185.8 [152.7–213.6] | -12.0% | 7/8 |
| clang | calls | Object compilation | 685.3 [581.1–814.8] | 488.6 [406.0–682.6] | -28.7% | 6/8 |
| clang | calls | Link | 66.0 [61.0–68.6] | 59.1 [54.4–81.8] | -10.4% | 5/8 |
| clang | calls | Compile + link | 750.2 [643.6–884.7] | 556.9 [457.4–750.8] | -25.8% | 7/8 |
| clang | medium | Syntax only | 821.1 [769.0–1105.7] | 861.7 [750.0–1057.7] | +4.9% | 5/8 |
| clang | medium | Object compilation | 1317.3 [1289.0–1756.7] | 1405.8 [1258.4–1709.2] | +6.7% | 4/8 |
| clang | medium | Link | 61.3 [58.6–66.4] | 77.5 [62.9–91.5] | +26.4% | 1/8 |
| clang | medium | Compile + link | 1380.2 [1348.1–1819.4] | 1485.0 [1325.5–1802.9] | +7.6% | 4/8 |
| clang | edited | Object compilation | 86.2 [78.0–103.9] | 80.1 [74.0–97.4] | -7.0% | 6/8 |
| clang | edited | Link | 61.8 [57.7–73.2] | 63.9 [56.6–69.5] | +3.5% | 5/8 |
| clang | edited | Compile + link | 147.9 [135.3–179.9] | 140.4 [135.9–167.0] | -5.1% | 6/8 |
| gcc | calls | Syntax only | 100.4 [91.7–120.5] | 86.7 [73.9–97.1] | -13.6% | 8/8 |
| gcc | calls | Object compilation | 640.5 [574.1–713.6] | 556.5 [508.5–673.3] | -13.1% | 6/8 |
| gcc | calls | Link | 60.4 [54.5–74.2] | 59.4 [56.4–67.9] | -1.6% | 5/8 |
| gcc | calls | Compile + link | 698.0 [643.7–771.9] | 620.7 [566.0–731.3] | -11.1% | 6/8 |
| gcc | medium | Syntax only | 357.2 [342.9–366.8] | 347.6 [323.5–400.8] | -2.7% | 5/8 |
| gcc | medium | Object compilation | 1258.6 [1068.5–1475.6] | 1169.1 [1005.6–1348.6] | -7.1% | 4/8 |
| gcc | medium | Link | 60.9 [58.0–67.7] | 76.6 [72.9–86.5] | +25.8% | 2/8 |
| gcc | medium | Compile + link | 1319.8 [1128.8–1549.0] | 1235.5 [1083.5–1430.7] | -6.4% | 4/8 |
| gcc | edited | Object compilation | 76.0 [71.6–86.5] | 62.5 [57.6–80.6] | -17.8% | 7/8 |
| gcc | edited | Link | 61.2 [55.9–67.9] | 60.3 [56.0–63.9] | -1.5% | 5/8 |
| gcc | edited | Compile + link | 137.0 [127.7–154.4] | 122.8 [112.3–146.4] | -10.4% | 7/8 |

## Isolated generated-C++ experiments

| Compiler | Change | Stage | Checkpoint ms [Q1–Q3] | Experiment ms [Q1–Q3] | Median change | Faster pairs |
| --- | --- | --- | --- | --- | --- | --- |
| clang | 64 runtime wrappers | syntax | 185.8 [152.7–213.6] | 161.1 [144.7–189.5] | -13.3% | 6/8 |
| clang | 64 runtime wrappers | object | 488.6 [406.0–682.6] | 429.2 [399.7–494.4] | -12.2% | 6/8 |
| clang | 48 eager additions, entry TU only | syntax | 58.7 [54.1–62.1] | 53.3 [51.3–57.1] | -9.2% | 7/8 |
| clang | 48 eager additions, entry TU only | object | 96.5 [90.1–100.9] | 69.8 [67.6–74.8] | -27.7% | 8/8 |
| gcc | 64 runtime wrappers | syntax | 86.7 [73.9–97.1] | 94.9 [81.5–100.0] | +9.5% | 4/8 |
| gcc | 64 runtime wrappers | object | 556.5 [508.5–673.3] | 585.3 [558.4–624.3] | +5.2% | 3/8 |
| gcc | 48 eager additions, entry TU only | syntax | 25.4 [24.9–25.6] | 20.2 [20.0–20.6] | -20.4% | 8/8 |
| gcc | 48 eager additions, entry TU only | object | 73.7 [70.6–82.4] | 52.9 [52.0–53.7] | -28.3% | 8/8 |

## Clang stage breakdown

| Workload | Revision | Frontend ms | LLVM backend ms | Optimizer ms | CodeGenPasses ms |
| --- | --- | --- | --- | --- | --- |
| calls | old | 124.9 | 318.4 | 198.1 | 116.3 |
| calls | new | 86.0 | 242.5 | 136.4 | 103.1 |
| medium | old | 171.4 | 384.2 | 236.6 | 124.9 |
| medium | new | 154.8 | 365.7 | 218.0 | 124.5 |

The Clang breakdown uses a separate instrumented build per workload and revision with `-ftime-trace`. The columns sum events across the translation units; backend, optimizer and code generation overlap. The uninstrumented timing table is the basis for the speed comparisons.
