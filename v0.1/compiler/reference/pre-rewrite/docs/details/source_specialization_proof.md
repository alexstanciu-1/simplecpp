# Isolated source-type / native-family proof
Doc Status: supporting

Status: isolated preparation experiment, 2026-09-19. No compiler phase, source
syntax, production family lookup, or compiler runtime-adapter capability is added.
The [design discussion](clang_lifecycle_composition.md#type-ownership-and-imported-combinations)
and [performance requirement](performance_watchlist.md#focused-execution-performance-proof-for-imported-combinations)
own the purpose of this proof.

## What is exercised

Two fixture descriptions stand in for future resolved compiler output:

- `point`: two signed 32-bit fields.
- `sample`: an unsigned byte, signed 64-bit field and signed 32-bit field.

Preparation generates their native declarations and instantiates the actual
`scpp::vector_t<T>` for each. An independent LLVM fixture uses only accepted
metadata for storage size/alignment, field offsets, symbols and ABI signatures.
It does not include or reproduce native container internals. The same generator
handles both field arrangements, including padding in the second record.

The LLVM caller constructs the vector, appends copies, reads length, obtains record
copies through caller storage and destroys the vector. Checks prove that modifying
the original record or a returned copy does not modify stored elements. Additional
growth/read workloads verify all fields against an independent arithmetic oracle.
An out-of-bounds read terminates at the bridge with a diagnostic. This does not
prove recoverable exceptions or failure cleanup.

## Owners and contracts

| File / owner | Role |
|---|---|
| [Specialization_Request](../../src-runtime-preparation/requests.php) | Pure export from a fixed request/catalog to native declarations, ordinary preparation definitions and operation bindings. Scalar fields and type-only family arguments are the supported vocabulary. |
| [Fixture catalog](../../src-runtime-preparation/tests/specializations/catalog.json) | Selects native family/header, scalar mappings, template parameters, lifecycle bindings and function adapters. No generator branch selects vector by name. |
| [Provider helpers](../../src-runtime-preparation/include/scpp_provider/sequence.hpp) | Implement sequence append-copy, length and bounds-checked read-copy. Native element references do not escape. |
| [Runtime_Preparation](../../src-runtime-preparation/prepare.php) | Existing measured metadata, bitcode generation, integrity checks and stable package publication. The whole package remains one replacement unit. |
| [Prepared_Request](../../src-runtime-preparation/request_adapter.php) | Separate isolated consumer adapter. Holds a package read lock, verifies artifact integrity/context and rejoins measured types/operations against the exact expected definitions. It is not a second compiler implementation. |
| [LLVM fixture](../../src-runtime-preparation/tests/specializations/consumer.py) | Generates actual LLVM IR from accepted facts, plus equivalent direct C++ baselines using the exported native definitions. |
| [Proof runner](../../src-runtime-preparation/tests/specializations/run.py) | Owns the workspace, native builds, execution checks, timings, allocation instrumentation and reuse/invalidation experiments. |

Two reusable adaptations were added within preparation: call-scoped mutable object
parameters, and owned `value_record` results through caller storage. They extend the
existing free-function adaptation; no native by-value aggregate ABI is assumed.
Named aliases of native class specializations can be measured as opaque values.
An opaque alias cannot claim the complete-public-field record contract.

The source-export representation uses the prototype's plain integer storage,
zero-construction and trivial value-copy/no-cleanup contract. It is a canonical
native definition for this experiment, not a claim of nominal interoperability
with an independently generated S2S class or with `scpp::int_t` wrapper members.
No custom source operations or nontrivial field behavior is being reproduced.

## Identities, inputs and reuse

The request supplies an exact caller-owned `scope`, source IDs and specialization
IDs. Their scope/lifetime is one project export; a future compiler must retain its
mapping across updates. Preparation includes the full scope/provider identity in
the generated provider namespace using reversible readable encoding. It does not
allocate IDs by hashing content. Duplicate IDs, duplicate concrete family demands,
unknown types, arity mismatches and missing demanded lifecycle operations fail.

Family type parameters are substituted by exact symbolic references, such as
`$self` and `$element`. C++ names, header paths and the resulting operation shapes
pass existing preparation validation. This is not arbitrary C++ source templating.
Nested source-field dependency scheduling and general constant template arguments
remain unsupported.

Generated source definitions are project-specific inputs. Provider runtime headers
remain external and unchanged. Existing dependency/fingerprint logic includes the
generated definitions, selected headers, tool code and compilation context.

The proof checks unchanged-package reuse, a real LLVM caller-body edit with package
reuse, and a source-field change that rebuilds the package with unchanged logical
operation identities. It then recompiles and executes consumers using the new
layout. Both specializations share this package, so invalidation rebuilds both;
per-specialization scheduling/caching is not claimed. The fixture owns its input
workspace and holds the package read lock during consumption/linking. Production
compiler demand collection, selected tasks and joins remain future integration.

## Reproduce and inspect

```sh
python3 src-runtime-preparation/tests/specializations/run.py
```

Use `--workspace PATH` for a configurable output directory and `--repetitions N`
for at least three timing repetitions. Default outputs stay under the ignored
`generated-runtime/proofs/source-specializations/` directory. Requires
the configured Clang 18 toolchain, `ld.lld-18`, PHP and Python on Linux.

`initial-accepted.json` preserves the initial contracts; `report.json` contains
measurements, configuration and checks. Per-mode directories retain their caller,
native declarations, provider source, modules, link logs, assembly and available
optimized LTO IR. Caller-edit and record-edit builds have distinct directories.
The stable `output/package/` contains the final changed-record package at completion.

The public preparation regression and affected compiler record fixtures are run
separately. Only their expected rejection diagnostics change: mutable declarations
over a const-only native function, and record-return declarations over an integer
function, now fail native signature validation rather than the old vocabulary gate.
The compiler continues to reject unsupported consumption contracts.

## Performance method and limits

Compare identical successful-path operations and inputs using the same runtime,
target, Clang, standard and optimization settings. The fixture builds provider and
caller at matched `-O0` or `-O1`; full-LTO and ThinLTO cases also explicitly select
link optimization level 1. It does not substitute the package's existing `-O2` LTO
variants into an `-O1` comparison.

The read-heavy workload constructs 257 records and repeatedly reads length/copies;
the lifecycle-heavy workload repeatedly constructs, appends, reads and destroys a
small vector. Runtime arguments, changing values and checked checksums keep work
observable. A noinline outer benchmark function is used on both paths. Builds use
four parallel jobs; measurements are serial, warmed, and alternate execution order.
Report medians and all samples. Small differences near parity are not a speedup claim.
The raw LLVM fixture and Clang's C++ frontend produce different IR shapes and
optimization information. These timings measure the complete tested integration
path, not an isolated instruction-level cost attributable solely to ABI calls.

Separate instrumented executions count native allocations, releases and bytes;
their timings are not performance evidence. The selected source records have
trivial copying and no refcount operations. Generated code and remaining bridge
calls expose extra work, but this proof does not count nontrivial copy constructors
or smart-pointer reference-count changes. Those families require their own proof.

## Results

See the accompanying measured results below. The user accepted the measured O1
overhead for the fast development compiler. This settles the performance review
for this bounded proof; the experiment itself does not implement compiler integration.

Verified with Clang 18.1.3 on x86-64 Linux/WSL2, using the configured Simple C++
runtime checkout. Five samples per path/workload after warm-up; ratios below are
bridge median divided by direct-C++ median (greater than 1 means slower).
[Full measurements and configuration](source_specialization_measurements.json)
retain all samples. This is one-machine evidence, not a universal overhead bound.

| Record | Mode | Read-heavy ratio | Small-vector lifecycle ratio |
|---|---|---:|---:|
| points | O0 | 1.589× | 1.204× |
| points | O1 | 0.995× | 1.242× |
| points | O1-full | 1.003× | 1.307× |
| points | O1-thin | 0.997× | 1.198× |
| samples | O0 | 1.186× | 1.208× |
| samples | O1 | 1.060× | 1.299× |
| samples | O1-full | 1.002× | 1.304× |
| samples | O1-thin | 0.998× | 1.271× |

The optimized read-heavy cases were near parity in most measurements; one ordinary
O1 sample-record case was about 6% slower. Small-vector lifecycle work was about
20–31% slower across O1 configurations. O0 also showed overhead. These results do
not justify a negligible-overhead claim. The user considers this O1 cost acceptable
for development builds; no overhead-reduction experiment is required before integration.

Allocation instrumentation matched exactly: both paths made 20 allocations and
20 releases, totaling 16,368 bytes for the point record and 49,104 for the wider
record in the instrumented workload. No additional wrapper allocation appeared.

Saved IR retained 15 direct bridge call sites across the check/run/bounds fixtures
at ordinary O0/O1 and full LTO at link level 1. ThinLTO reduced those totals to 4
and 5 for the two records; append remained out of line. This is static call-site
inspection, not a count of executed calls or proof that residual calls alone explain
all overhead. The outer benchmark remains noinline on both sides. No bridge was
forced always-inline and no optimization-specific success path was introduced.

Validation: the isolated run passed both record shapes in all four configurations,
copy independence and checksum/bounds checks, the three reuse/invalidation cases,
allocation parity, and seven request/acceptance rejection cases. The existing
standalone preparation test passed 161 checks; provider-record and runtime-record-
borrowing integration fixtures also passed. A full compiler suite was not rerun
because compiler implementation was unchanged.

## Accepted development-build tradeoff

The canonical exported definition plus prepared native specialization is feasible
for the bounded plain-record case. On 2026-09-19 the user accepted its measured
20–31% O1 lifecycle overhead: the objective is a fast compiler for development.
Keep the general preparation/adapter path; reducing this measured gap is not an
integration prerequisite. Future investigation of bridge call granularity or
optimization visibility is optional, not required work for this proof.

This decision does not establish an overhead bound for other workloads or settle
ownership-wrapper costs. Subsequent investigation established the
[ownership boundary](clang_lifecycle_composition.md#ownership-decision): runtime
implementation uses Clang preparation; complete source operations belong to our
compiler. The [source-operation adapter proof](source_operation_adapter_proof.md)
tests how native templates reach those operations without duplicating them.
