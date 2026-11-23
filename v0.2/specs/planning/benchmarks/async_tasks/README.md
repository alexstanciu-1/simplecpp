# Out-of-line async task methods

The production compiler emits a concrete inline task header per demanded result.
This benchmark uses its three-flavor, 23-result fixture and tests a different placement:
keep the complete promise declaration in the header and collect the recipe's out-of-class
method definitions in one application translation unit. Methods defined inside the promise
class, including return_value, remain inline. No helpers are added to the runtime archive.

The prototype splits the compiler's existing emitted fragments so that the method bodies
under comparison are identical. It covers results declared by runtime headers. Application
records, enums and collections containing them need their complete declarations available
to the implementation unit before this approach can become a general emitter feature.

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
