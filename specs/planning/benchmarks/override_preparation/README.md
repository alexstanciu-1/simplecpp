# Override body edits and preparation time

Doc Status: planning

Measured on dev002 at `3e8c2f91b`, with PHP 8.5.12 NTS, Xdebug off, on a Ryzen
9 5950X pinned to logical CPU 6. The compiler ran sequentially after the test
suite had finished. A temporary copy timed the production
`Incremental_Preparation::prepare` call with `hrtime(true)`. Source discovery,
parsing, lowering, file writes and native compilation fall outside the interval.

Every case kept the number of files, entry and override body size fixed. A single
source declared N functions of the form `readN(Child $n) { return $n->value(); }`
with an explicit int return type. The control took Base instead. The entry simply
returned 8. The compiler prepares declared bodies in this fixture even though the
entry does not invoke them. Each fresh session alternated 2 and 3 as the override
return literal, with an unchanged request after each edit. The first three pairs
warmed the session. Each median below uses the next 31 pairs. Case order used a
fixed shuffle. A fresh-process startup cost is outside the measurements.

| Callers | Concrete edit, ms | Concrete unchanged, ms | Base-typed edit, ms | Base-typed unchanged, ms |
|---:|---:|---:|---:|---:|
| 0 | 0.056 | 0.015 | 0.056 | 0.015 |
| 1 | 0.093 | 0.016 | 0.056 | 0.016 |
| 8 | 0.249 | 0.021 | 0.061 | 0.021 |
| 32 | 0.745 | 0.034 | 0.075 | 0.034 |
| 128 | 2.727 | 0.087 | 0.128 | 0.086 |
| 512 | 11.541 | 0.318 | 0.343 | 0.294 |

Every concrete edit prepared N callers plus the override. Every base-typed edit
prepared only the override. Every unchanged request prepared zero bodies. At
32–512 callers the median edit-time difference between receiver types was about
20–22 microseconds per concrete caller. Larger caller bodies or different
source-file distributions need their own measurement. The unchanged measurements
also show some project-size-dependent preparation-stage work with zero body visits.

From a configured checkout on dev002, run
`XDEBUG_MODE=off taskset -c 6 python3 -B specs/planning/benchmarks/override_preparation/measure.py`.
The [probe](measure.py) writes the [sample CSV format](dev002.csv) to stdout.
