# Shared route field-access benchmark

Each of 93 synthetic route legs accepts a shared Route, copies its inline Coordinate,
updates it, writes it back, charges a conditional toll and passes a new load to the
next leg. The entry reads the final totals through both the original and a second
owner. The 12 PHP-flavor source files total 51,288 bytes. See [main.phs](main.phs),
[a district](district_01.phs), [the structs](models.phs) and [expected output](expected.json).

With the v0.2 runtime archive built under build/clang, run from this folder:

```sh
python3 -B benchmark.py > run.json
```

The runner emits the app once in a temporary directory. The baseline rewrites the 749
shared-field access sites to direct payload access and removes the generated accessor.
Inline fields, owner lifetimes, call boundaries and build inputs are otherwise identical.
Both variants compile and run after every build. The runner verifies stdout, stderr,
exit status and matching executable .text hashes in each pair.

## Local measurement

Clang 18.1.3, C++23, -O2 -g0, Ninja 1.11.1 with eight jobs, mold 2.40.4, prebuilt
runtime, no PCH or LTO. One warm-up pair, then ten alternating fresh build pairs.
Frontend and LLVM times sum Clang traces across 13 translation units. The linker time
is the Ninja link-edge duration. The PHP compiler measured 84.1 ms once before the pairs.

| Metric | Direct | Overloaded arrow |
| --- | ---: | ---: |
| Generated .cpp bytes | 177,541 | 170,939 |
| Generated .hpp bytes | 12,015 | 12,015 |
| Median native build wall | 582.8 ms | 600.7 ms |
| Wall range | 568.7–636.1 ms | 570.0–655.9 ms |
| Median Clang frontend | 353.1 ms | 369.6 ms |
| Median LLVM backend | 850.8 ms | 897.5 ms |
| Median link | 62.5 ms | 62.0 ms |

The overload added 17.9 ms comparing medians. It was slower in seven of ten
pairs, with a median within-pair difference of 15.1 ms. [Raw samples](results.json)

## Checked shared-owner access

After the empty-owner slice, both benchmark variants perform a null check before
dereferencing. The direct variant expands that check at each shared-access site.
The overloaded variant uses the checked inline operator and calls the same
out-of-line throwing runtime helper on failure. Historical results above used
unchecked access. Each new measurement used one warm-up and ten alternating pairs.

| Median ms | WSL Ninja 8 direct / overload | dev002 Ninja 16 direct / overload |
| --- | ---: | ---: |
| Fresh native wall | 626.2 / 637.2 | 261.9 / 260.6 |
| Clang frontend, summed | 435.9 / 391.4 | 210.8 / 180.2 |
| LLVM backend, summed | 1008.5 / 1053.0 | 512.4 / 515.8 |
| Link | 62.5 / 63.5 | 28.5 / 29.0 |

Every pair produced identical executable text and the expected output.
[WSL measurements](results-empty-wsl.json) and [dev002 measurements](results-empty-dev002.json).
