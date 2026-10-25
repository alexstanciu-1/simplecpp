# Production native-build concurrency and coordinator costs
Doc Status: planning

Performance goal remains active. This report is a checkpoint; the sequential
comparison and line-parser candidate measurements are still running.

## Ownership and reuse

`Native_CPP_Build` now uses a bounded process pool for independent object actions.
The native owner reuses managed spawn/poll/close, dependency discovery, token
validation, action identity, and output staging. A request advances through scan,
validation and compilation; free slots refill without batch barriers. The
coordinator owns all cache publication, ordered diagnostics and link membership.
Layout probes, optional PCH prerequisites and final linking remain ordered.
The API and CLI accept a positive job count; the default remains one.

Explicit portable `\\scpp\\Process_Handle` type boundaries map to the existing
`process_handle` binding. Clang token markers use the existing byte-search
operation, with an executable-PHP facade. Required string locals extract nullable
owner/status reads before marker comparisons. No nullable or process semantics
changed. Existing UTF-8-oriented `q_strpos` was inappropriate for the byte protocol
and expensive in native execution; its general behavior is unchanged.

## Runtime failure discovered by the real pool

The actual native builder failed around 256 completed regular objects. A 513-unit
fixture reproduced this independently of the compiler workload. Hash bucket width
was selected from bucket capacity, although buckets contain physical entry positions.
Repeated insertion/removal keeps live capacity small while physical positions grow.
The runtime now selects and widens position storage from the physical range. Rehash
already includes the appended entry, so insertion must not index it a second time.
Reusing a deleted bucket also updates its tombstone count.

`test_hash_churn.cpp` keeps only 16 live entries through 66,000 inserts/removals,
checking typed/dynamic lookups, order and removal across both width boundaries.
It fails before the repair and passes afterward. Existing hash, packed-storage and
concurrent-iteration tests pass. An initial standalone existing-hash harness link
omitted its JSON provider; rerunning with that provider passes. No new sanitizer
claim is made. The native 513-unit build and executable now pass in 3.11 seconds.
Runtime fix commit: `b1b85f8a5`.

## Completed dev002 measurements

O2 native compiler, fixed published compiler workload, 32 logical CPUs, production
cached builder with real dependency scans and strict token checks. Instrumented
Clang proxy and 100 ms process-tree sampler are included in wall time. These are
not external object-only replay timings.

| Candidate | Jobs | Cold seconds | Warm seconds | Maximum Clang overlap |
| --- | ---: | ---: | ---: | ---: |
| Process pool + hash repair | 32 | 201.99 | 155.88 | 28 (cold) |

Cold summed process-tree peak RSS: 3,629,187,072 bytes. This sums resident pages
across processes and can double-count shared mappings; it is not unique physical
memory. `/usr/bin/time`'s separate maximum RSS is not the simultaneous process-tree
peak. Cold total user/system CPU: 1006.25 / 245.36 seconds.

Cold actions: 763 dependency scans, 763 object compilations (including 27 layout
objects), 473 template checks, 28 links. Summed command wall times are 53.34,
778.49, 158.37 and 1.31 seconds respectively; overlapping durations do not sum to
build elapsed time. Maximum object command: 9.33 seconds for runtime
`core/runtime.cpp`. Maximum template check: 1.15 seconds; dependency scan: 0.18.

Largest generated CPP: 179,939 bytes (`specializations/layout_part_17.cpp`). Largest
HPP: 58,472 bytes (`Parser_Run`). No generated CPP/HPP exceeds 200,000 bytes.

## Next measured slice

The dependency parser previously copied the entire Make rule into value arguments
for each byte access and length query. It now splits once into physical lines and
scans each line, caching its length. The same parser state crosses escaped newline
continuations and stops at the first unescaped rule ending. Byte splitting preserves
non-UTF-8 path bytes. No Make syntax extension, compiler lowering change, dependency
cache relaxation or runtime string change is involved. A single unusually long
physical line can still be expensive; this slice targets actual Clang output.

PHP and legacy-native proofs cover escapes, dollars, split paths, trailing
continuation, malformed input, non-UTF-8 bytes and a 1,024-line rule. Both v0.2
output modes compile and execute that same fixture. A 10,000-case differential
check matches previous results and diagnostic messages exactly.

## Other validation and evidence

- PHP-hosted pool: jobs 1/4 demonstrate maximum real overlap 1/4; generated program
  exits 12. Warm reuse produces no new object/link actions. Failure leaves pending
  status, closes children/stages, and retry succeeds.
- Dependency invalidation and strict provider-header rejection/recovery pass;
  ordinary PCH remains supported. Two stale invalidation expectations (JSON provider
  membership and unchanged object reuse) were corrected and pass against the old
  builder as well as the candidate.
- Portable process-handle boundaries convert for v0.1/v0.2; PHP and legacy native
  execute the process/byte-search proof. The full rebuilt v0.2 compiler executes
  the production pool, not merely a PHP-hosted driver.

Recipes capture the isolated `/home/alex-ai/compiler-self-o2-20261025` server
workspace. Runtime-changing candidate 07 rebuilt all objects; candidate 08 may reuse
objects only with identical generated headers and sources, since runtime is unchanged.
