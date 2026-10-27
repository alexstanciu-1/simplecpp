# Catalog validation allocation baseline
Doc Status: supporting

Measured locally on 2026-10-26, Clang O2, using the five default catalogs in
`runtime/metadata/modules`. This is startup metadata validation, not semantic
preparation, source compilation, concurrency or incremental processing.

## Reproduce

From `compiler/my-try-cpp/`:

```sh
python3 tools/build.py configure
ninja -C build/compiler -j8 catalog-validation-profile
build/compiler/catalog-validation-profile ../../runtime/metadata/modules
```

The profiling target is excluded from normal builds and CTest. Its allocation
hooks live only in the profiling executable; production binaries are unchanged.
No runtime/middleware rebuild is needed.

Each catalog is read and parsed before its measurement window. Validation uses
one shared identity inventory, retained across core, json, strings, datetime and
filesystem. Reporting occurs after snapshots. Module JSON documents are not
retained in the inventory by this probe; this isolates `validate_catalog`, rather
than measuring the full loader. Repeated trials use fresh processes.

## Results

Three baseline trials produced identical allocation/byte counts:

| Catalog | Allocation requests | Requested bytes | Peak extra live bytes | Retained bytes |
| --- | ---: | ---: | ---: | ---: |
| core | 12,029 | 413,222 | 13,335 | 6,033 |
| json | 5,315 | 183,995 | 7,933 | 3,333 |
| strings | 11,794 | 420,410 | 19,140 | 5,983 |
| datetime | 1,354 | 47,074 | 2,401 | 228 |
| filesystem | 5,466 | 191,101 | 9,396 | 1,935 |
| Total (peak is not additive) | 35,958 | 1,255,802 | — | 17,512 |

Sum of instrumented validation times: 3.017–3.362 ms across the three trials.
These timings include instrumentation overhead; they are not production timings.
A subsequent run with allocation-hook calibration produced identical counts.

Separate 1,000-iteration probes, excluding initial JSON parsing and key creation:

| Operation | Allocations per call | Requested bytes per call |
| --- | ---: | ---: |
| `object.member("name")` | 4 | 96 |
| `object.member(existing_string_key)` | 3 | 64 |
| Construct the 82-byte parameter vocabulary string | 2 | 115 |

These probes retain zero bytes after each loop. Existing keys remove one
allocation in this example; they do not remove the allocations from node access.
The probe counts include the runtime implementation behind the middleware calls,
not just the facade itself. There is no stack attribution in this instrument.

## Interpretation and next work

Allocation churn is confirmed: roughly 1.26 MB requested during validation, with
only 17.5 KB retained in identity inventory. This does not mean 1.26 MB of peak RAM.

First investigate the JSON view/member boundary and repeated vocabulary-string
construction. In `validate_names`, even the empty-vocabulary check constructs a
string inside the loop. In `matches_vocabulary`, the fixed vocabulary is rebuilt
for each call. Prefer reusable runtime/middleware operations over compiler-side
native string workarounds. Measure the same baseline after each focused change.

Identity registration uses linear searches and therefore has quadratic growth
for repeated insertions. This measurement does not establish that those searches
dominate current execution time; a scaling experiment is needed before choosing
a replacement container. No production optimization was made in this baseline.

## Measurement limits

Counters intercept ordinary C++ `new`/`new[]` and matching deletes, including sized
deletes. They do not intercept direct malloc, aligned allocation, allocator caches,
OS mappings or stack storage. Requested/live byte counts exclude instrumentation
headers, allocator metadata and alignment padding. They are not RSS measurements.
Hooks are deliberately single-threaded for this serial probe, not MT-safe tooling.
Allocation failure traps in the profiling binary. A calibration checks one known
allocation and release before application measurements.

## Follow-up cleanup — 2026-10-26

The middleware literal `json_value::member` overload now calls the runtime
without constructing a middleware string. `string::is_one_of` scans a borrowed
space-separated vocabulary without allocating; compiler validation no longer
constructs vocabulary strings or constructs a string to test an empty vocabulary.

Validation now attaches field names and zero-based row indexes on failure.
Diagnostic strings are constructed in failure paths, not maintained in allocated
context objects during successful traversal. Constants, properties, build
settings, non-function bindings and optional-section traversal have named helpers.
This preserves the existing bounded schema; it does not add semantic resolution.

Three fresh-process trials after this cleanup produced identical allocation counts:

| Measurement | Before | After |
| --- | ---: | ---: |
| Validation allocation requests | 35,958 | 25,375 |
| Cumulative requested bytes | 1,255,802 | 701,680 |
| Retained identity bytes | 17,512 | 17,512 |
| Instrumented validation time, three trials | 3.017–3.362 ms | 1.615–1.887 ms |
| Allocations per literal member read | 4 | 3 |
| Allocations per existing-key member read | 3 | 3 |
| Allocations per vocabulary match | unmeasured separately | 0 |

That is 29.4% fewer requests and 44.1% fewer requested bytes. Timing trials are
local observations with instrumentation, not a stable performance guarantee.
The old vocabulary-construction probe remains as a reference. The added matching
probe also asserts zero allocations over 1,000 calls. Boundary tests exercise
exact tokens, nonmatching substrings, spacing, empty vocabularies and embedded NUL
keys. Startup fixtures check row/field context for nested parameters, type arguments,
constants, properties, build settings and non-function bindings.

JSON member reads still allocate for the owning facade/runtime node path. This
slice does not change document ownership or replace runtime storage. The retained
baseline makes a subsequent representation change measurable. Linear duplicate
searches also remain; no new collection family was introduced.
