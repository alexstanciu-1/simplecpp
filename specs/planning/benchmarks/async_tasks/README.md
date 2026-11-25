# Out-of-line async task methods

The production compiler defaults to a concrete inline task header per demanded result. Applications can select `"async_task_methods": "grouped"` in `module.json`. The production path uses the recipe fragments directly; this directory retains the original placement experiment.
This benchmark uses its three-flavor, 23-result fixture and tests a different placement:
keep the complete promise declaration in the header and collect the recipe's out-of-class
method definitions in one application translation unit. Methods defined inside the promise
class, including return_value, remain inline. No helpers are added to the runtime archive.

The prototype splits the compiler's existing emitted fragments so that the method bodies
under comparison are identical. The 23-result fixture uses runtime headers. The application-payload proof below supplies
complete types using the existing record and collection headers.

Run with the v0.2 Clang runtime built:

    XDEBUG_MODE=off python3 -B profile.py
    XDEBUG_MODE=off python3 -B run.py /tmp/scpp-task-methods-new

The second command requires a new directory and retains all generated sources, traces,
Ninja logs and raw samples. It measures five fresh application builds per configuration
after warm-up, alternating order, at -O2/g0 with Ninja, mold and the warm selective
coroutine PCH. Each executable must match the expected output and exit code.
The incremental probe verifies that removing one of two same-result producers reuses
the methods object, removing the last retires it from the link, and restoration matches
fresh compiler output. The separate profile check regenerates both profiles in isolation
and checks Clang dependencies for the entry adapter, caller, worker and unrelated unit.

WSL medians in milliseconds, inline → out of line:

| Flavor | Parent frontend | Parent LLVM | Methods unit frontend / LLVM | Link | Native wall |
| --- | ---: | ---: | ---: | ---: | ---: |
| PHP | 227 → 176 | 924 → 469 | 209 / 519 | 64 → 56 | 1450 → 1087 |
| C++ | 200 → 166 | 1001 → 418 | 197 / 476 | 64 → 59 | 1510 → 1039 |
| JS | 213 → 137 | 823 → 416 | 185 / 485 | 58 → 58 | 1360 → 947 |

The grouped methods compile concurrently with the producers. A change to the set of
demanded result types invalidates that grouped object. These runs measure build latency;
they do not measure runtime await throughput or the cost on a one-task application.


## Application-defined payloads

Run `XDEBUG_MODE=off python3 -B application.py /tmp/scpp-task-application-new` with a new output directory.
The fixture passes each distinct resolved async result to the concrete header publisher. For a record it returns the
published record header. For a hash it returns the self-contained collection header, which includes the element
declaration before the concrete specialization. The outlined methods unit consumes those paths directly.
The fixture checks the incremental publisher's headers and files against a fresh session. Inline builds use the original bundle.

Two cross-file producers per result share one definition of each outlined method. The incremental sequence demands
both tasks, adds a field to Point, deletes and restores Point, keeps each task alone, removes both, then restores both.
Ninja must recompile the unchanged methods source on the layout edit. Each successful generation is compared with
fresh artifacts and both executables run. The proof checks linked symbols and dependencies as well as hash copy independence.

Baseline from the original fixture-supplied headers: warm selective PCH, Clang 18, -O2/g0, Ninja -j4 and mold. Five timed fresh builds per row and variant after warm-up.
Milliseconds, inline → outlined. The methods column shows the extra unit's frontend / LLVM medians.
PHP compiler time is a single incremental snapshot shared by both native variants.

| Flavor | Result | PHP compiler | Parent frontend | Parent LLVM | Methods FE / LLVM | Link | Native wall | Wall range inline / outlined |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: | --- |
| PHP | point | 4.2 | 38 → 30 | 34 → 20 | 30 / 23 | 53 → 54 | 332 → 300 | 284–366 / 273–392 |
| PHP | hash | 5.2 | 51 → 52 | 56 → 39 | 64 / 27 | 51 → 54 | 390 → 397 | 371–480 / 372–573 |
| CPP | point | 3.5 | 33 → 31 | 34 → 18 | 33 / 21 | 52 → 51 | 289 → 276 | 280–352 / 264–374 |
| CPP | hash | 5.1 | 56 → 62 | 55 → 44 | 52 / 21 | 57 → 56 | 400 → 407 | 369–482 / 362–450 |
| JS | point | 3.8 | 56 → 42 | 58 → 24 | 43 / 24 | 57 → 64 | 414 → 419 | 317–520 / 315–685 |
| JS | hash | 5.3 | 66 → 70 | 63 → 50 | 68 / 26 | 57 → 54 | 459 → 494 | 362–520 / 418–576 |

Outlining reduced parent LLVM time in every row. The wall-time ranges overlap in each pair; the single-result fixtures provide no consistent wall-time advantage. The raw samples include child and blocking-caller traces.
