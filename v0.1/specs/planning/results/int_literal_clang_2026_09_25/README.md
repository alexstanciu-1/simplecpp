# Integer declaration Clang comparison
Doc Status: planning

One-off measurement requested during v0.2 discussion, 2026-09-25. No lowering
policy or compiler implementation changed.

Clang 18.1.3 (Ubuntu), x86_64 Linux under WSL2; C++20, current repository
`scpp/int_t.hpp`, no PCH, serial compiler processes with warm filesystem cache.
The script uses local absolute paths; adjust them when running elsewhere.

Correction to the initial discussion: the current runtime integer is
`scpp::int_t<>`, with an explicit constructor. `int_t<> a = 10;` fails compilation
(see invalid.txt). Compare these valid alternatives instead:

```cpp
int_t<> a{10};
auto a = static_cast<int_t<>>(10);
```

For each size/mode: one warmup per form, then 12 paired repetitions with seeded
random order within each pair. Timings are wall time including process startup
and headers. Each function has at most 100 declarations and returns the sum of
all their native values. This amplifies declaration processing without one giant
scope. Object compilation writes the same output path each time; no linking.

| Declarations | Mode | Direct median ms | Auto/cast median ms | Paired median extra ms for auto/cast | Auto/cast slower pairs |
| --- | --- | ---: | ---: | ---: | ---: |
| 1 | Frontend only | 722.4 | 706.3 | -21.5 | 2/12 |
| 1 | Object -O0 | 724.6 | 732.6 | -4.7 | 5/12 |
| 1 | Object -O2 | 732.0 | 723.9 | -1.8 | 5/12 |
| 10,000 | Frontend only | 1032.8 | 1139.1 | 104.4 | 12/12 |
| 10,000 | Object -O0 | 1405.1 | 1549.8 | 97.7 | 10/12 |
| 10,000 | Object -O2 | 1604.4 | 1686.5 | 66.8 | 12/12 |

Paired medians need not equal differences between independent medians. The -O0
amplified case had large scheduling/timing outliers; see raw samples in results.json.
No consistent advantage across modes was established for one declaration. Direct
initialization was consistently faster for the amplified frontend and -O2 cases.
Optimized assembly for the amplified inputs was identical after excluding the
source filename directive. Runtime speed was not measured.

This does not isolate auto deduction from cast processing or initialization form,
prove a project build speedup, or justify generalizing brace initialization to other
types/values. No CPU isolation was applied. The header cost is included, not
subtracted. Reproduce with `python3 bench.py`; generated sources and assembly go to
`/tmp/scpp-int-literal-bench`. Results are specific to this toolchain and workload.
