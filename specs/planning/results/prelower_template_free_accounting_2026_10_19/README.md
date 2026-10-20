# Template-free accounting application
Doc Status: supporting

Accounting passes **998 checks, zero failures** with all 16 application C++ units
and their transitive headers free of templates. The runtime and middleware still
compile the original runtime templates. No accounting source workarounds were
added for this slice.

## Implementation

Existing `cpp_types` bindings supply scalar and byte literal interfaces, including
explicit concrete exposure for native bool/float/string classes. The byte interface
constructs directly in existing inline storage and borrows the original bytes;
runtime hash key operations consume that interface without a temporary string.
An alternate metadata-selected byte adapter is tested. Existing prepared result
types select boolean results, rather than hard-coded native bool wrappers.

Prepared moves emit typed rvalue casts without `<utility>`. The compiled entry
callback retains CLI initialization and exception reporting behind middleware;
initializer buffers use `<stddef.h>`. Source handles reuse the preceding
[source-handle slice](../prelower_source_handles_2026_10_19/README.md).
No new AST, semantic registry, template-family dispatch or runtime algorithm was
introduced. Literal interface dispatch is by physical contract, not source names.

## Acceptance evidence

- All 16 actual app object commands were replayed without PCH through preprocessing
  and `-ftime-trace -ftime-trace-granularity=0`. Every unit has **zero `template <`
  declarations and zero `InstantiateClass`/`InstantiateFunction` events**.
  No runtime implementation header occurs in any app's preprocessed closure.
  See [closure.json](closure.json); total preprocessed app input is 2,576,024 bytes.
- O0/no native debug, accounting 998/0 after the clean build and all six timed
  app rebuilds. Warm native build: **0 compiled, 22 reused, 0 linked, 0 PCH built**.
- Direct/concrete output, indexing, compound operations, initializer failure prefixes,
  evaluation order, nested references and ownership checks match. Both the source
  owner and nested representation boundary report seven zero counters.
- Empty/short/embedded-NUL/long string literals, copying, physical moving and destruction
  match byte-for-byte and allocation-for-allocation. Long construction plus its
  requested copy uses two allocations, 108 bytes, two frees in both modes. Borrowing
  and hashing use zero allocations/frees in all four cases. Native and concrete
  hashes match the previous native `std::hash<std::string>` result. Tracked payload
  counters do not claim to count primitive/string internal member moves.
- An ordinary/prepared entry failure produces identical diagnostics, `before` output,
  suppressed later output and exit status 1 through the compiled callback.
- Demand retirement/restoration, option changes and fragment replay pass through
  the existing fixtures. All 21 malformed metadata cases reject without replacing
  the installed runtime configuration.

Counter evidence: [bytes](byte-counters.json), [indexing](indexing-counters.json),
[compound](compound-counters.json). PHP ran through the shared runner's CLI fallback
because the sandbox disallows its FPM socket. Generated native applications were
built; the compiler itself was not rebuilt natively.

## Isolated O0 timings

Three alternating serial/parallel rounds on the same WSL2 Ryzen 5 PRO 4650G
(6 cores/12 logical CPUs), Clang 18.1.3. Runtime, middleware and layout artifacts
reused. Every app object rebuilt; no PCH, debug information or O2. Reported totals
are compilation plus linking, excluding frontend preparation, dependency scanning,
publication and execution. No other proof jobs ran during these measurements.

| App rebuild | Compile wall median | Link median | Compile + link median |
|---|---:|---:|---:|
| Sequential | 2.784 s | 0.496 s | 3.277 s |
| Parallel, at most 12 Clang processes | 0.619 s | 0.510 s | 1.129 s |

The slowest unit by median is now `src/checks.phs`: **0.310 s sequential** and
**0.563 s parallel**. Parallel contention changes individual unit times. See
[timings](timings.json) and [unit timings](unit-timings.json).

The earlier partially prepared baseline with runtime PCH measured 7.447 s serial
and 1.653 s parallel compilation. These results are about 2.7x faster for app
compilation. They compare the successive backend states, not just an isolated
single-header change, and do not establish a universal C/C++ performance ratio.

A cold build still prepares three layout batches and compiles four runtime objects,
two middleware objects and sixteen app objects (22 linked objects). Those costs
are excluded from the reused-middleware timings above. The capture build overlapped
proof work, so its cold timing is not a controlled performance result. Middleware
still has templates and its first build is substantial; no whole-cold-build speedup
is claimed. Future ThinLTO remains non-blocking and untested here.

## Reproduction

Use the general API configuration `prelower_templates=true` and
`concrete_type_substitution=true`, with `Native_CPP_Build` configured at O0,
`native_debug=false`, `pch=false`. The broad runtime PCH is not template-free.

Run the O0 proof suite:

```sh
python3 compiler/my-try/tests/prelower_header_boundaries.py --results FRESH_DIRECTORY
```

Record native compiler invocations as JSONL objects with `phase: "object_compile"`
and an `arguments` array, then audit exactly those commands:

```sh
python3 compiler/my-try/tests/prelower_template_closure.py \
  --commands RECORDED_JSONL --published PUBLISHED_ACCOUNTING --results FRESH_DIRECTORY
```

Raw local evidence is under `/tmp/accounting-template-free`,
`/tmp/accounting-concrete-handles`, `/tmp/prelower-template-free-final`,
`/tmp/prelower-final-checks` and `/tmp/prelower-entry-check`.
The final focused O0 run passes output (30.47 s), indexing (33.08 s), and compound
(45.44 s), concurrently scheduled. These are proof-suite durations, not isolated
compile benchmarks. String movement is tested at the physical C++ boundary;
source `move` remains unique-owner-only.

This establishes the accounting workload boundary, not complete runtime coverage.
Unused wrappers, language exceptions and other unrequested runtime operations still
need their own metadata/interface proofs. Historical handwritten recipes remain a
separate comparison mode; the accepted general path does not add to those recipes.
