# Measured native compilation results
Doc Status: planning

Median seconds across three serial repetitions. Every executable passed all 998
accounting checks. See README.md for scope and exclusions.

| Optimization | Representation | Application compile | New support compile | Link | Total native |
|---|---|---:|---:|---:|---:|
| -O0 | original | 41.844 | 0.000 | 0.418 | 42.262 |
| -O0 | eager | 21.655 | 0.000 | 0.398 | 22.053 |
| -O0 | concrete | 2.677 | 0.682 | 0.162 | 3.518 |
| -O2 | original | 65.031 | 0.000 | 0.245 | 65.270 |
| -O2 | eager | 45.201 | 0.000 | 0.239 | 45.439 |
| -O2 | concrete | 4.578 | 2.392 | 0.155 | 7.098 |

PCH creation (excluded from the table):

- -O0: original 4.163 s, eager 5.199 s.
- -O2: original 4.205 s, eager 5.275 s.

Concrete mode does not use a PCH or the original runtime shared library. Its total
includes compilation of all its support code. The existing original runtime library
is reused for both original modes. Totals are paired medians and need not equal
the sum of independently reported median components.

At -O0, concrete application compilation is 15.63x faster than ordinary PCH and 8.09x faster than eager PCH. Including new support and linking, the ratios are 12.01x and 6.27x.

At -O2, concrete application compilation is 14.20x faster than ordinary PCH and 9.87x faster than eager PCH. Including new support and linking, the ratios are 9.20x and 6.40x.

These results do not establish that C++ templates cannot be optimized: enabling
-fpch-instantiate-templates substantially improves the original baseline. They show
that explicit concrete representations still reduce native compilation work for this
application, even after that improvement. Other PCH/codegen/explicit-instantiation
configurations remain unmeasured. Representation/library changes mean the ratios
cannot be attributed solely to template syntax.

Validation passed for the unchanged generated input and final converted-file hashes,
unchanged original runtime binary, 18 benchmark executions, original/concrete
container/string probes, full concrete ASan/UBSan, zero outstanding shared owners,
and zero template declarations across all 18 preprocessed native inputs. LeakSanitizer
was disabled; no runtime performance or full production-runtime equivalence claim
is made.
