# Restricted runtime public API: first-slice results
Doc Status: supporting

Date: 2026-10-27. Implementation: `2891b2a3e`; approved decisions: `280bdd611`.
See [the boundary and next-dependency inventory](public_api_inventory.md).

## Completed scope

The runtime owns `public/` and exposes narrow `scpp_core_api` / `scpp_tasks_api`
CMake targets. Integers and worker-pool declarations live there; compatibility
headers forward to their definitions. Private include roots are link-only behind
the new targets. Compiler publications consume the selected runtime's public root
directly, rather than copying its integer header into middleware.

Disabled public configuration links core only. The task-owned adapter supplies its
existing diagnostic arguments to general compiled error machinery. Core contains
no task-disabled policy. The legacy template API retains its header-only fallback.
Typed public exception inspection, string/error/textbuilder representation changes
and general specialization-inventory generation are subsequent slices.

## Tests

- Seven focused runtime checks passed in static, shared, ASan/UBSan with leak
  detection, and ThreadSanitizer builds.
- Actual CMake public consumer TUs reject implementation headers with `__has_include`
  guards. Every public header independently preprocesses without templates or
  private runtime headers in both task modes; explicit private-header probes fail.
- Enabled task workload proves four-worker concurrency, worker reuse, ordered
  results, exception propagation/recovery and old/new API interoperability.
- Disabled tests verify exact message/code/component/operation. The new path links
  only core; the compatibility path links no runtime library. A separate inspector
  TU keeps the unchanged private exception type out of the public caller.
- General failure tests verify that thrown diagnostics own copies of borrowed input
  and handle null fields as documented. Integer layout/behavior tests still pass.
- Compiler normal suites: 13/13 on WSL and dev002. WSL ASan/UBSan: 13/13; TSan:
  12/12, with the existing allocation-hook test excluded. Sanitizer publications
  leave normal dependency packages unchanged.
- Publication fixture verifies reuse, failure preservation, recovery and runtime
  mismatch rejection. Middleware no longer copies runtime headers.
- Dependency isolation passed, including changes to the live public integer header.
  Normal compiler builds leave pinned runtime/middleware archives unchanged.
- Relocation passed 13/13 compiler tests plus independent enabled and disabled clients
  using the published runtime API targets. Neither dependency was rebuilt.

## Compiler rebuild costs

Clang 18.1.3, C++23/O2/NDEBUG, Ninja 8 jobs, Mold, no PCH or LTO. Three trials per
case. Only compiler objects and the selected executable are rebuilt; tests are
excluded and dependency archives are fixed and fingerprinted. src/ has ten units;
src/ + support/ has seventeen. No competing build was started by this task during
measurement on the same host.

| Host/checkpoint | src/ runs (s) | Median | src/ + support/ runs (s) | Median | No-op |
| --- | --- | ---: | --- | ---: | ---: |
| WSL before | 0.8077, 0.7366, 0.777 | 0.777 s | 6.208, 6.3073, 6.3938 | 6.3073 s | 0.0099 s |
| WSL after | 0.8026, 0.9987, 0.9012 | 0.9012 s | 8.9045, 7.6578, 7.5453 | 7.6578 s | 0.0105 s |
| dev002 after | 0.3046, 0.3194, 0.2998 | 0.3046 s | 2.6054, 2.612, 2.6148 | 2.612 s | 0.0055 s |

The WSL after runs were slower than this slice's before runs; these measurements do
not establish a whole-compiler build speedup or isolate the cause of that difference.
There is no matched dev002 before measurement for this slice. Heavy support units
still consume legacy APIs; exposing a narrow root does not remove those paths.

Reproduce with `compiler/my-try-cpp/tools/measure_build.py --jobs 8 --trials 3
--output OUTPUT.json`. Evidence: `public-api-before.json`, `public-api-after.json`
and `public-api-dev002.json` under the local compiler build directory.

## Caller-only compile costs

WSL, Clang 18.1.3, C++23/O2, three alternating compile-only trials, after builds
completed. Focused callers receive only `runtime/public`; generic compatibility
callers receive `runtime/include`. These are equivalent configuration calls, not
whole compiler or runtime-implementation build timings.

| Mode | Header | Preprocessed bytes | Runs (s) | Median |
| --- | --- | ---: | --- | ---: |
| Disabled | Public worker pool | 5,848 | 0.0568, 0.0501, 0.0582 | 0.0568 s |
| Enabled | Public worker pool | 5,431 | 0.0518, 0.059, 0.0521 | 0.0521 s |
| Disabled | Generic tasks | 2,934,852 | 3.197, 2.9146, 2.9698 | 2.9698 s |
| Enabled | Generic tasks | 2,965,480 | 2.8706, 2.7444, 2.8272 | 2.8272 s |

The disabled public caller is now template-free: 5,848 preprocessed bytes instead
of the previous checkpoint's 1,258,737, with compile median 0.0568 s versus 1.1338 s.
The enabled caller remains small (0.0521 s versus the previous 0.0543 s). Historical
wall-time comparisons remain sensitive to host load. The structural reduction is
removal of exception implementation headers from disabled public callers.

Reproduce with `tests/runtime/native/measure_worker_pool_headers.py --output OUTPUT`.
Local evidence: `compiler/my-try-cpp/build/public-api-header-cost/results.json`.

## Inline string slice — 2026-10-27

The complete ordinary `scpp::string` API is implemented in runtime core. Other value
APIs remain approved follow-ups; compiler string facades have not migrated. No
compiler-wide speedup or source/support build improvement is claimed here.

The generated target probe reports 32 bytes/alignment 8 on WSL and dev002, with
nonthrowing native move construction, move assignment and destruction. The facade
has exactly the native footprint, containing a live native object rather than a
heap wrapper. These are target measurements, not cross-platform ABI constants.

Allocation-hook results at both O0 and O2 on WSL (O2 also verified on dev002):

| Operation | Allocations |
| --- | ---: |
| Empty/short construction, short copy, equality/vocabulary lookup | 0 |
| Long construction | 1 |
| Long copy | 1 |
| Long move | 0 |
| Long slice into the returned value | 1 |

Long fixtures are 88 bytes. The retained native character allocation remains;
there is no facade allocation. Inline object footprint grows from the compiler's
8-byte pointer facade to 32 bytes, eliminating that facade's separate native object
and allocator metadata once consumers migrate. This is not an RSS measurement.

Validation: 9 focused Release tests; 5 ASan/UBSan tests with leak detection;
3 shared-runtime tests; 3 dev002 tests. All passed. The isolated O0 build compiles
the string, integer and failure implementation owners plus a public-only caller.
Coverage includes independent copies/results, short/long strings, embedded NULs,
UTF-8 and invalid-UTF-8 fallback, clamped slices, bounds errors, self-copy/move/append,
capacity reuse, repeated lifecycle stress and failed construction/assignment/result
allocation cleanup. Public headers remain standalone and template-free in both task
modes. No task scheduling or shared mutable string behavior was introduced.

Compile timings (Clang 18, O2, no PCH/LTO; alternating caller trials, median of 3):

| Measurement | WSL | dev002 |
| --- | ---: | ---: |
| Public string caller | 0.0513 s | 0.0316 s |
| Equivalent native-header caller | 0.8161 s | 0.4577 s |
| New string adapter alone, one compilation | 3.3818 s | 1.8222 s |
| Full runtime-core recompile + archive, one run, Ninja 8 jobs | 15.2012 s | 8.0902 s |
| Runtime-core no-op build | 0.0262 s | 0.0170 s |

The public caller preprocesses to 7,364 bytes versus 1,491,642 for the native header.
The runtime still compiles core through its existing aggregate translation unit;
8 jobs cannot parallelize that single unit. Full-core times are current costs, not
matched before/after regressions. Tests are excluded from timed builds.

Reproduce caller/adapter measurements with
`python3 tests/runtime/native/measure_value_api.py --output <results.json>`.
Target-local artifacts are `compiler/my-try-cpp/build/string-api-cost.json` and
`string-core-build.json`; the latter measures removal/rebuild of only core's object,
followed by a no-op. Existing build warning about redundant hash specialization
instantiation is unchanged.

## Error value and retained exception slice — 2026-10-27

The complete approved `error_value` and `exception_info` interfaces are implemented
in runtime core. Compiler facade migration, source borrowing and textbuilder remain
separate slices. Target probes on WSL/dev002 report error_value 80 bytes/alignment 8
and exception_info 8 bytes/alignment 8. Both contain real native objects; neither
adds a heap-allocated facade. Retained handles own the original exception and its
existing diagnostics/trace, without eagerly copying those fields.

Allocation measurements distinguish `operator new` from glibc heap calls. The
explicit diagnostic target `test_public_errors_allocations` interposes malloc,
calloc and realloc through glibc entrypoints; it must run without sanitizers. The
ordinary target uses normal allocation paths under ASan/UBSan. Measurements exclude
construction/throwing of the original exception, whose trace/diagnostics already
have native allocation costs. Counts below agree at O0/O2 on WSL; O2 also agrees on
dev002 (Clang 18/libstdc++/glibc).

| Operation | Observed allocation calls |
| --- | ---: |
| Default/empty error value and empty owned fields | 0 operator-new calls |
| Error make/copy with long message and file | 2 operator-new calls |
| Error move | 0 operator-new calls |
| Error long-message getter | 1 operator-new call |
| Runtime exception capture | 1 glibc heap call; 0 operator-new calls |
| Retained-handle copy/assignment, including rvalue copy | 0 heap calls |
| Long-message exception inspection | 2 glibc heap calls; 1 operator-new call |

The exception_ptr representation is allocation-free as a facade, but rethrowing it
for verification/inspection allocates through the tested C++ exception machinery.
The getter's second allocation owns the returned string. Do not advertise capture
or inspection as allocation-free based on operator-new counts alone. Counts are
implementation observations, not portable guarantees. Avoiding repeated inspection
rethrows would require a later representation/API review; this slice preserves the
approved retained-pointer-only storage and original exception identity.

Validation passed: 10 focused Release tests, 6 ASan/UBSan tests with leak detection,
4 shared-runtime tests, 3 dev002 tests, and isolated O0 allocation/lifecycle checks.
Coverage includes empty-but-present error values, owned field snapshots, copy/move
and self-assignment, first/second-field allocation failure cleanup, retained lifetime
after catch, non-destructive rvalue copying, native exception identity, exact details
and trace entries, rethrow under a different active catch, checked indices, failed
inspection cleanup, foreign exception identity and diagnosed outside-catch capture.
Public headers remain independently template-free in both task configurations.

Build costs (Clang 18/O2, no PCH/LTO; caller medians from three alternating trials):

| Measurement | WSL | dev002 |
| --- | ---: | ---: |
| Public error/inspection caller | 0.0607 s | 0.0228 s |
| Equivalent native-header caller | 1.0956 s | 0.5307 s |
| error_value adapter alone, one compilation | 1.0476 s | 0.5175 s |
| exception_info adapter alone, one compilation | 1.1771 s | 0.6296 s |
| Full runtime-core recompile + archive, one run, Ninja 8 jobs | 16.1507 s | 8.4686 s |
| Runtime-core no-op | 0.0273 s | 0.0162 s |

Public caller preprocessing is 9,505 bytes versus 1,555,107 bytes for native headers.
Core remains one aggregate translation unit. These are current costs, not a matched
before/after experiment or compiler-wide gain; compiler consumers have not migrated.
Tests and middleware builds are excluded from timing.

Reproduce with `python3 tests/runtime/native/measure_value_api.py --facility errors
--core-build compiler/my-try-cpp/build/runtime --output <results.json>`. The script now covers string (default) and errors. Local
and remote final artifacts are `compiler/my-try-cpp/build/errors-api-cost.json`,
including the forced core rebuild and no-op measurements. The existing redundant hash specialization warning remains
unchanged. Glibc allocator instrumentation is an explicit diagnostic executable,
not runtime code or a replacement for sanitizer testing.

Final inspection constructs every owned result inside the handler, rather than
escaping a reference to the caught object. This remains lifetime-safe if the C++
implementation copies on rethrow. Affected sanitizer, O0/O2 allocation, static/shared
and dev002 checks were rerun after this tightening; the table records final timings.

## Composed scan and publication proof (2026-10-27)

Selected path: `fs_scan -> result<vector<string>>`. The generated vector stores
concrete strings directly; the generated Result stores the concrete vector. Both
outer representations are real inline native objects. The native and public scan
entrypoints use the same filesystem scan/sort algorithm, constructing their chosen
stored values directly. A missing rvalue append overload was caught by allocation
parity testing and corrected before publication.

On WSL/Clang 18/libstdc++, vector storage is 40 bytes and Result storage 96 bytes.
The three-entry fixture (including a long filename) observes **42 operator-new calls
for both native and public scan**, excluding path construction. This does not count
all libc allocations or promise a fixed count on other platforms. Owned extraction
and copying retain their native value costs; the boundary adds no wrapper allocation
or second conversion container.

Validation: 12 focused runtime checks, 13 compiler checks, shared scan, ASan/UBSan
with leak detection, reproducible generation, all public headers independently
compiled in both tasks modes, package relocation and failed-publication recovery.
Coverage includes sorted scan results, failures and preserved outputs, invalid
indices, no-error rejection, copy/move, cursor preservation and lifetime beyond
owner destruction. The typed hash check uses concrete string keys with embedded
NULs and independent copies; it is not yet a public hash facade.

The relocated published SDK generated exactly one app-owned `vector<sample::record>`
implementation while reusing the runtime vector/Result. Its main translation unit
received only public roots through the actual package CMake targets. Conflicting ABI,
names, public headers and archives were rejected. Removed demands reject stale output
inventories. The existing compiler middleware naming entrypoint delegates to the
runtime owner and works with relocated custom dependency paths.

Current WSL compile observations (`measure_value_api.py --facility composition`,
Clang 18/O2, three caller trials, no PCH): public scan caller median **0.0808 s**,
native-header caller **6.3176 s**; preprocessing **11,718 vs 2,556,049 bytes**.
Individual vector/Result/scan adapter compilations took 4.6351/4.3547/4.5891 s.
Aggregate core rebuild+archive took 31.4049 s; no-op 0.0373 s. This machine exhibited
substantial variability, so these are current cost observations, not a matched
before/after regression result or compiler-wide speedup claim. No dev002 timing was
performed for this checkpoint. Results are in `compiler/my-try-cpp/build/composition-api-cost.json`.

Reproduce publication reuse after explicit runtime publication with:
`python3 tests/runtime/native/test_publication_composition.py --package compiler/my-try-cpp/dependencies/runtime`.
The shared physical generator currently supports prepared copyable vector/Result
demands. Universal family admission, public hash, boxed families and compiler facade
migration remain subsequent batches; see the comprehensive surface audit.

## Small integer/key batch (2026-10-27)

The configured public scalar family now includes signed/unsigned 8/16/32/64-bit
classes. The existing numeric generator also emits private key adapters for these
exact classes. Hashing/equality delegate to the matching native integer authority;
no public traits or templates are introduced. Scalar size/alignment remains native,
trivial copying is retained, and no allocation-bearing storage is added.

Validation passed: 12 focused runtime checks, 13 compiler checks, scalar and composed
scan ASan/UBSan with leak detection, and relocated publication/application reuse.
The numeric proof includes min/max keys for every width, independent hash copies,
small-width narrowing/sign extension, unsigned wrap and zero-divisor diagnostics.
The scan remains at 42 operator-new calls for both entrypoints.

WSL/Clang 18/O2, three alternating caller trials: concrete uint16 addition caller
median **0.0585 s**, equivalent native-operator-header caller **4.7350 s**; preprocessing
**11,327 vs 3,303,631 bytes**. Standalone integer adapter compilation: **5.2920 s**.
These are current local costs, not a matched compiler-wide speedup measurement.
Reproduce with `measure_value_api.py --facility integers`; local artifact:
`compiler/my-try-cpp/build/small-integer-api-cost.json`. Public/generated integer files
remain 11,452/30,464 bytes. Full operator closure and value-argument demand publication
remain separate batches.

## Metadata-driven C++ generator (2026-10-27)

The selected scan/vector/Result path is now emitted by `scpp_api_generation`, a
reentrant C++ library with a thin CLI. Public API metadata owns operation signatures,
native bindings, lifecycle, companion cursor and adaptation snippets. The Python
driver performs target probing, staging and publication validation only. Existing
compiler naming entrypoints delegate to the same C++ naming implementation.

A metadata-only proof renames the vector family to `sequence` and changes an operation
snippet without modifying the generator. It also checks malformed schemas, duplicate
class identities, missing dependencies, invalid lifecycle/transport and unknown
placeholders. Target probes validate metadata-specified payload capabilities and
combine companion layouts into one compile per dependent unit (two for this runtime
closure, one for the app-owned vector). No extra compile is needed for each method.

The generated scan retains **42 operator-new calls on both public/native paths**.
Vector/Result storage remains **40/96 bytes**, and cursor storage **128 bytes**, on the
tested Clang 18/libstdc++ target. Existing copy, mutation, error, lifetime and typed-hash
proofs pass. ASan/UBSan with leak detection covers the generated scan, metadata
success/rejection paths and concurrent calls into the reusable library. The normal
focused runtime suite has 8 checks; compiler tests have 13. Naming parity and
reproducible generation pass. Relocated publication proves a public-only client can
both consume generated app storage and call the C++ generation library directly;
runtime specializations are reused, with incompatible publications rejected.

Build observations on WSL/Clang 18/O2/Ninja 8 jobs:

- Grouped changed runtime/core/filesystem/tool/test build: **22.725 s**. This is an
  observed incremental build, not a forced clean build; sanitizer work ran separately
  in parallel, so it is not an isolated before/after comparison.
- Ninja's actual core compilation in that group: **22.342 s**; scan adapter **3.354 s**.
- Observed generator object compilations: JSON bridge **5.638 s**, naming **1.887 s**,
  generation worker **5.387 s**. These are individual observed compilations, not costs
  to sum as a parallel clean-build estimate.
- One public scan caller compilation: **0.1294 s**, versus **3.0257 s** with native
  headers. Preprocessing: **18,229 vs 2,556,049 bytes**. One trial was deliberately
  used, and separate adapter recompiles skipped, to limit validation compile cost.
- C++ planning CLI median: **0.00426 s**, ten trials including process startup,
  excluding target compiler probes.

Artifacts: `compiler/my-try-cpp/build/metadata-build-cost.json`,
`metadata-api-caller-cost.json`, and `metadata-plan-cost.json`. No dev002 or new
shared-runtime rebuild was performed for this slice. No compiler-wide speedup or
universal family coverage is claimed.

Publication source identity now excludes Python bytecode caches: generating a cache
must not invalidate a package or trigger a false "sources changed during build"
failure. The publication fixture verifies that cache changes preserve archive reuse.

## Typed hash, nullable and capability conditions (2026-10-27)

Typed hash public recipes now cover string/integer keys, owned reads, writes,
non-inserting nullable lookup and preserved iteration. Conditional operation
selection is metadata-driven; the generic generator does not recognize hash or
integer family names. Required capability claims are verified alongside layout
probes. A canonical type substitution resolves signature dependencies such as
`nullable<vector<string>>`. Concrete integers now use a private hash-only append
policy, sharing native next-key/overflow behavior without broad numeric trait opt-in.

The compiled fixture generates eight units (four hashes, three nullable types and
one fixture-owned vector). It exercises nested vectors, an application-owned record,
copy isolation, absent lookup without insertion, checked nullable extraction,
mutation-preserved cursors and cursors escaping the owner's destruction. A sealed
application publication is reused with **zero new implementation units**. This also
covers structural generated names ending in `_`, which must not become authored
aliases during import. Public consumer include paths exclude implementation headers.

Allocation measurements count `operator new` calls, not every allocator mechanism:

- Matched native/public hash workload: **32 / 32 calls**.
- Empty hash and empty nullable construction: **0 calls**.
- Missing lookup with a short key: **0 calls**.
- Target storage: hash **168 bytes**, nullable string **40 bytes**, hash cursor
  **120 bytes**. No wrapper heap allocation or wrapper ownership indirection is added.

Release and ASan/UBSan with leak detection pass. The integer append proof checks
zero and overflow for all eight concrete signed/unsigned widths plus native `int_t`;
the public proof additionally checks negative keys and gaps. Sanitizer execution
requires running outside a ptrace sandbox for LeakSanitizer. Metadata success and
rejection paths also pass with the sanitized generator.

WSL, Clang 18, Ninja 8 jobs; single trials to limit validation compilation:

| Measurement | Seconds |
| --- | ---: |
| Generate eight units, including target layout/check probes | 14.279 |
| Compile/link the complete Release fixture | 9.102 |
| Public hash lookup caller compilation, O2 | 0.049 |
| Equivalent native-header caller compilation, O2 | 1.905 |
| Reuse sealed application specializations without compilation | 0.143 |
| Instrumented fixture generation/probes | 13.145 |
| Instrumented fixture compile/link | 16.588 |

The matched caller preprocessing sizes are **16,917 vs 2,262,389 bytes**. Generation
and build timings are fixture costs, not compiler-wide rebuild measurements; no
new dev002 comparison was performed. Local artifacts are
`compiler/my-try-cpp/build/typed-hash-cost.json` and `typed-hash-sanitizer-cost.json`.

Reproduce with `python3 tests/runtime/native/test_typed_hash_composition.py --core
compiler/my-try-cpp/build/runtime/libscpp_runtime_core.a`. For sanitizers, use the
sanitized core path, add `--sanitize`, set `ASAN_OPTIONS=detect_leaks=1` and
`UBSAN_OPTIONS=halt_on_error=1`, and select the sanitized generator through
`SCPP_API_GENERATOR`. The fixture publishes only in a temporary directory and removes
it on exit; runtime publication demands remain unchanged. Shared identity keys,
shared-value reference operations, mixed and dynamic hashes remain later slices.

## First mixed value and scalar publication (2026-10-27)

`mixed` and `float64` now use zero-parameter metadata recipes. The existing null
sentinel is exposed through a template-free public header with a compatibility
forwarder. The runtime demand includes mixed and its floating dependency. Native
inline storage, exact scalar extraction, checked read/write helpers, lazy removal,
null reset and binding-aware traversal remain the behavior owners. The only generator
change fixes importing canonical zero-parameter identities without registering aliases
that shadow their recipes. No type-name-specific generation branch was added.

The compiled public-only test covers scalar kinds (including signed zero, infinity
and NaN), exact extraction failures, owned strings, null bootstrap, missing reads,
invalid scalar receivers, nested copy isolation, empty versus null iteration,
mutation, reset, move and destruction during traversal. Self-aliased writes snapshot
before mutation; an injected allocation failure verifies that a failed snapshot
leaves the receiving value unchanged. The mixed cursor's finished `next()` is a no-op
because preserving an already-consumed last entry can immediately exhaust traversal;
finished key/value reads still reject.

Matched native/public `operator new` counts (inputs prepared before measurement):

| Workload | Native | Public |
| --- | ---: | ---: |
| Null/bool/int/float construction, scalar copy and reset | 0 | 0 |
| Owned string boxing, copy and extraction | 5 | 5 |
| Integer-keyed table construction, writes, copy and read | 5 | 5 |
| Same table with traversal surviving binding reset | 8 | 8 |

These are scoped allocation counts, not all allocator traffic. Existing native string
and table allocations remain; this slice does not convert deep copies to COW.
Measured inline storage is **40 bytes mixed**, **8 bytes float64**, and **184 bytes
mixed cursor**, with compile-time size/alignment checks against the native types.

Release and ASan/UBSan with leak detection pass, including the allocation-failure
case. Metadata tests also pass with the sanitized generator. Leak detection runs
outside the ptrace sandbox. A relocated sealed publication compiles an application
`vector<mixed>` with owned extraction, reuses runtime mixed/float definitions, and
subsequently reuses the sealed application specialization with zero generated units.

WSL, Clang 18, O2; one serial caller/adapter trial to limit compilation:

| Compile | Seconds |
| --- | ---: |
| Public mixed integer-extraction caller | 0.0525 |
| Equivalent native-header caller | 1.2491 |
| Standalone generated float64 adapter | 0.9350 |
| Standalone generated mixed adapter | 3.0543 |

Caller preprocessing is **16,588 vs 1,575,118 bytes**. The observed grouped normal
core/test/tool rebuild was **23.34 s** and the instrumented group **46.62 s**, Ninja
8 jobs. Those groups overlapped and are not isolated before/after performance trials.
The existing bundled core still dominates a rebuild when its generated adapters
change; this is not a compiler-wide build-speed claim. No dev002 run was performed.

Reproduction:

- Build `test_public_mixed` and `scpp_api_generator` in the runtime configuration.
- Run `test_public_mixed` normally and in the sanitizer configuration with
  `ASAN_OPTIONS=detect_leaks=1 UBSAN_OPTIONS=halt_on_error=1` outside ptrace.
- Run `tests/runtime/native/test_boxed_composition.py --core
  compiler/my-try-cpp/build/runtime/libscpp_runtime_core.a` for relocation/reuse.
- Run `tests/runtime/native/measure_value_api.py --facility mixed --trials 1
  --output compiler/my-try-cpp/build/mixed-api-cost.json` for caller and adapter costs.

Floating arithmetic, mixed arithmetic/conversions, shared/weak ownership exposure,
dynamic crossing, and recursive public mixed/nullable signatures remain later work.

## First dynamic and explicit scalar conversion publication (2026-10-27)

The runtime publishes `dynamic` through metadata alone, using native `dynamic_t<>`
storage and the existing checked helpers. Its cursor returns dynamic values and
mixed integer/string keys. Explicit `to_dynamic` / `to_mixed` use native cast rules,
including container rejection. No implicit truthiness, family conversion or operator
surface was added; no C++ generator modification was required.

Public-only tests cover shared alias/nested mutation, reset retaining other aliases,
null-write rejection, absent reads, scalar kinds and exact extraction, cursor mutation,
replacement/move/destruction, and explicit scalar conversion round trips (including
integer extrema, signed zero, infinity, NaN and strings). Failed container conversion
and injected allocation failure leave destination bindings unchanged. Self-reference
and self-append retain identity; tests explicitly remove those edges before exit.
Cycle reclamation remains debt, not a property inferred from passing leak tests.

Release and ASan/UBSan with leak detection pass. Metadata planning/import tests also
pass under sanitizers. Allocation counts below are matched `operator new` calls, with
input strings prepared before counting; they do not count every allocator mechanism.

| Workload | Native | Public |
| --- | ---: | ---: |
| Scalars, copying/reset and numeric cross-family round trip | 0 | 0 |
| String boxing, copy and extraction | 5 | 5 |
| Integer-keyed table writes, alias copy and read | 3 | 3 |
| Same table with traversal surviving binding reset | 6 | 6 |
| String mixed → dynamic → mixed round trip | 6 | 6 |

Copying an existing container with no active traversal allocates **0** times.
Probed inline sizes are **40 bytes dynamic** and **184 bytes cursor**, matching the
native representations. Allocation-bearing native payloads remain; no wrapper heap
allocation is introduced.

WSL/Clang 18/O2 observations, one caller/adapter trial:

- Public integer-extraction caller: **0.0541 s**, **19,269** preprocessed bytes.
- Native-header equivalent: **1.0457 s**, **1,713,237** preprocessed bytes.
- Standalone generated dynamic adapter: **3.5409 s**.

The fixture publication proof ran concurrently with part of the measurement; these
are local observations, not isolated benchmark ratios or compiler-wide rebuild
measurements. No dev002 run was performed. Local artifact:
`compiler/my-try-cpp/build/dynamic-api-cost.json`.

Reproduce with the `test_public_dynamic` runtime target, normally and with the sanitized
core (`ASAN_OPTIONS=detect_leaks=1 UBSAN_OPTIONS=halt_on_error=1` outside ptrace).
`test_boxed_composition.py --family dynamic --core
compiler/my-try-cpp/build/runtime/libscpp_runtime_core.a` validates a relocated sealed
runtime, an application `vector<dynamic>` with alias-preserving extraction, and reuse
of that application's sealed specialization with zero regenerated units. The same
fixture defaults to mixed to retain the value-copy counterpart.
Run `measure_value_api.py --facility dynamic --trials 1 --output <file>` for caller and
standalone adapter compilation. Broader casts, truthiness and operators are the next
design discussion, not part of this completed slice.

The native regression test retained pre-iteration assumptions (24-byte storage and
unconditionally nonthrowing moves). Its expectations now include the existing cursor
binding handle and potentially allocating preservation. An injected failure during
public move construction verifies that source storage and its active cursor remain
usable when preservation cannot allocate. This slice does not change native layout
or move behavior to satisfy those tests.

The same native test now selects its numeric-string key expectation from the existing
`SCPP_LANGUAGE_TARGET_PHP` setting; this compiler runtime build has that option off.
No key normalization behavior was changed.

## First global operator metadata slice — 2026-10-27

The existing C++ API library resolves domain admission and normalized operand/result
types from `global_operators` metadata. This slice publishes nine normalized numeric
addition functions and four boxed equality/inequality functions, rather than all
numeric operand pairs. The existing manifest/emitter now records API ownership and
reuses imported normalized definitions. Source frontend integration and other operator
groups remain pending; this is not a compiler-wide promotion migration.

Validation on WSL, Clang, eight build jobs:

- Six focused release tests pass (1.22 s total): global operators, dynamic native
  behavior, public dynamic lifetime/allocation behavior, reusable API generation,
  metadata generation and global operator metadata checks.
- The same six pass under ASan/UBSan with leak detection (4.14 s). LeakSanitizer
  required execution outside the sandbox's ptrace environment. No sanitizer errors
  were reported. Instrumented core and tooling were built, not merely the callers.
- The public operator test covers 81 scalar pairs in both boxed families and both
  equality operators: 42 incompatible non-null pairs throw. Null/present comparisons
  return false; numeric int/float equality remains admitted. NaN, embedded NUL bytes,
  inequality and normalized signed/unsigned/floating additions are checked.
- A 100-iteration successful-operation loop (addition and boxed string comparisons)
  reports **zero allocations**. This excludes constructing inputs and exception
  paths; no allocation-free failure guarantee is claimed.
- Metadata-only tests cover signed/unsigned rejection, null/bool/string arithmetic
  rejection, same-type retention, family-maximum selection, metadata changes affecting
  resolution, ambiguous-rule/signature rejection, deduplicated demands and matching/
  conflicting API imports. Existing template-default and composition checks pass.
- A relocated sealed-runtime publication test passes normally and with ASan/UBSan/
  leak detection. Re-requesting normalized operators generates zero implementation
  units; a public-only caller links and runs against the relocated archive.
- Both generated equality adapters compile independently, proving their native
  transport headers no longer depend on inclusion order in the core bundle.

Single-run compile measurements (`-std=c++23 -O2`, no timing repetitions):

| Case | Seconds | Preprocessed bytes |
| --- | ---: | ---: |
| Public addition + boxed equality caller | 0.0732 | 17,837 |
| Equivalent native-header caller | 3.3736 | 3,602,497 |
| Standalone mixed equality adapter | 3.5305 | 3,673,232 |
| Standalone dynamic equality adapter | 3.4203 | 3,676,089 |

The focused release rebuild, including the core bundle, took **20.41 s**, peak
reported child RSS **406,204 KiB**. The instrumented focused build took **58.56 s**,
peak **603,232 KiB**, including stale module prerequisites. These are touched-target
build costs, not clean compiler builds or aggregate process-memory measurements.
No dev002 measurement was requested for this slice. Timings are single observations,
not stable speedup claims. Local raw measurements: `compiler/my-try-cpp/build/global-operator-cost.json`.

Reproduce caller/adapter costs with
`python3 tests/runtime/native/measure_global_operators.py`. Focused CTest selection:
`scpp_(test_global_operators|test_dynamic_value|test_public_dynamic|test_api_generation|check_global_operators|test_api_metadata)$`.
For instrumentation, use the sanitizer runtime build with
`ASAN_OPTIONS=detect_leaks=1 UBSAN_OPTIONS=halt_on_error=1`.

Known unrelated fixture limitation: `test_mixed_t_dynamic.cpp` still contains old
`dynamic_`, `dynamic_box` and `dynamic_v` usages, so that whole legacy target does not
compile against the separated-family runtime. Its null/empty-string expectation was
updated, but its broader migration was not folded into this slice. The active dynamic
fixture and public pair matrix prove the changed equality behavior for both families.
The previously tracked REL-DEBT-04 bool/int comparison conflict is resolved in the
shared helper; this does not claim the entire legacy fixture is repaired.

Reproduce the publication proof with
`python3 tests/runtime/native/test_global_operator_publication.py --core <runtime-build>/libscpp_runtime_core.a --tool <runtime-build>/scpp_api_generator`;
add `--sanitize` with the instrumented build and sanitizer environment above.

## Core completion goal A1 — ordinary numeric operations (2026-10-27)

The active completion goal's first slice adds ordinary subtraction, multiplication,
division, integer remainder and six numeric comparisons to the global metadata
publication path. Addition now delegates to the same native arithmetic owner rather
than reproducing its expression. There are 98 normalized numeric implementations
(9 types for 10 operations, 8 integer types for remainder), not original-operand-pair
implementations. The generator has no numeric type-name branches; metadata describes
operation-specific native operands separately from general API transport.

Five focused tests pass in release (1.08 s) and ASan/UBSan with leak detection (6.20 s).
The combined numeric fixture exercises all nine types, zero-divisor error codes,
negative integer truncation/remainder, unsigned wrap, NaN comparisons, infinity and
selected widening. The successful-operation loop reports **zero allocations**; setup
and error paths are excluded. Existing signed-overflow limitations are unchanged;
this is not a new checked-overflow policy or a claim that undefined native inputs
are safe. Both integer and floating division adapters compile independently.

The relocated sealed-publication proof additionally reuses division and comparison
APIs with zero emitted implementation units and runs a linked public-only caller,
normally and with sanitizers. Metadata checks cover result types, common promotion,
signed/unsigned rejection, invalid scalar admission and integer-only remainder.

Single-observation compile costs (`measure_global_operators.py --numeric`):

| Case | Seconds | Preprocessed bytes |
| --- | ---: | ---: |
| Public integer division + floating comparison caller | 0.0588 | 13,128 |
| Native equivalent caller | 3.4424 | 3,550,635 |
| Independent integer division adapter | 3.5499 | 3,562,439 |
| Independent floating division adapter | 3.3310 | 3,563,212 |

The eight-job focused release rebuild took **22.03 s**, reported peak child RSS
**408,548 KiB**; the sanitizer rebuild took **44.37 s**, **608,076 KiB**. These are
changed-core/tool/test build observations, not clean compiler builds. Costs were
sampled once to limit compilation; do not interpret them as stable speedup ratios.
Raw caller observations: `compiler/my-try-cpp/build/core-a1-cost.json`.

Reproduction targets: `test_numeric_operators`, `test_global_operators`,
`test_api_generation`, `scpp_api_generator`; run their CTest cases plus
`scpp_test_api_metadata` and `scpp_check_global_operators`. Publication proof uses
`test_global_operator_publication.py` with the matching core archive and tool;
add `--sanitize` and the sanitizer environment for the instrumented variant.

The revised semantic promotion contract records the frontend's remaining wider-operand
behavior explicitly. No compiler preparation/lowering migration, new floating widths,
boxed arithmetic, wrapper lifting or checked-cast API is claimed by A1. A2 is next.


## A2a — checked scalar conversion prerequisite (2026-10-27)

The shared native cast owner now validates floating-to-integer conversions before
performing an integral conversion. Finite values truncate toward zero; the
truncated value must be within the target range. Exact exclusive power-of-two
bounds cover signed and unsigned 64-bit limits without rounding the maximum up.
NaN, infinity and out-of-range values throw `integer_cast_out_of_range`.

Decimal parsing uses `from_chars`, preserving optional leading plus and full-string
consumption, rejecting hexadecimal, whitespace, non-finite and out-of-range
spellings. This follows config.json's `strict_full_string_finite_decimal_parse`
policy. Successful parsing no longer creates an allocated lowercase copy.

`test_checked_scalar_casts` covers narrow signed/unsigned boundaries, signed and
unsigned 64-bit limits, explicit native-template and wrapped overloads, malformed
and embedded-NUL strings, and output preservation on parse failure. A loop of 100
successful numeric casts and long decimal parses records **zero allocations**.
The new test plus numeric and boxed operator regressions pass in release (0.02 s)
and under ASan/UBSan with leak detection (0.08 s).

Eight-job rebuild observations: the initial release build took 20.36 s and built
the changed core but failed on a test-only `float_t` name ambiguity. Qualifying
the test type and completing the build took another 3.02 s (188,104 KiB peak
child RSS). These are separate build attempts, not a clean-build timing. The
sanitizer rebuild took **50.93 s**, peak child RSS **608,032 KiB**. Existing public
headers/adapters did not change; no new public-caller timing is claimed.

Reproduce with native CMake targets `test_checked_scalar_casts`,
`test_numeric_operators`, `test_global_operators`, then their matching CTest cases.
A2 public conversion generation remains pending, including full-range unsigned
string parsing; no wrapper conversion coverage is claimed here.

### A2a follow-up — full unsigned integer parsing

The native string cast now parses unsigned nonnegative literals in uint64 storage,
then checks the requested target width. This accepts UINT64_MAX without routing
through signed int64. Negative literals retain signed parsing: `-0` succeeds and
representable negative values throw `integer_cast_out_of_range` for unsigned targets.
The existing whole-string decimal grammar and error distinction remain: malformed
or wider-than-family literals produce `invalid_cast_int_literal`; values parsed
successfully but outside a narrower target produce `integer_cast_out_of_range`.
Leading plus remains rejected for integers, as before.

The focused scalar test covers signed minima/maxima, unsigned maximum and the
signed/unsigned boundary, narrow overflow, negative unsigned inputs, malformed
literals and embedded NUL. Parsing preconstructed UINT64_MAX text in the allocation
loop adds **zero allocations**. All three focused cast/numeric/boxed test cases pass
in release (0.02 s) and ASan/UBSan/leak mode (0.08 s).

One eight-job build per configuration: release **20.14 s**, peak child RSS
**407,128 KiB**; sanitizer **43.81 s**, **608,116 KiB**. These are changed-core/test
rebuilds, not public-caller or clean compiler timings. Public conversion generation
and publication remain pending; this closes the native unsigned parsing gap above.

## A2b — normalized numeric-target public conversions (2026-10-27)

The C++ metadata generator now publishes 36 checked adapters: four normalized
sources (int64, uint64, float64, string) to nine numeric targets. Source integer
widths normalize losslessly within signedness; destination casts delegate to the
shared checked native owner. The ordinary operation emitter, generated API
inventory and sealed import records are reused. No source/target type-name dispatch
is embedded in the generator.

`test_public_conversions` compiles against public headers only and exercises every
adapter, unsigned maximum parsing, signedness/range errors, non-finite rejection,
strict-decimal rejection and accepted unsigned-to-floating precision loss. Its
100-iteration success loop records **zero allocations**. Native cast boundary tests
and prior numeric/boxed operator tests remain green. `check_conversions.py` checks
normalization/admission, malformed normalization, duplicate requests, 36 generated
units, template-free headers and zero-unit import reuse/conflict rejection.

Seven focused release tests pass in **1.68 s**; the same tests under ASan/UBSan with
leak detection pass in **10.57 s**. The relocated sealed-package proof now requests
`cast_uint64_from_string`, generates zero adapters, and runs a public-only caller
against the moved archive, in release and sanitizer configurations.

Single-observation compile costs (`measure_global_operators.py --conversions`):

| Case | Seconds | Preprocessed bytes |
| --- | ---: | ---: |
| Public string parse + floating-to-int8 caller | 0.0520 | 15,516 |
| Native equivalent caller | 3.1226 | 2,976,498 |
| Independent string-to-uint64 adapter | 3.1021 | 2,992,006 |

The eight-job changed-core/test/tool release build took **20.65 s**, peak child RSS
**410,376 KiB**; sanitizer build **47.47 s**, **613,176 KiB**. A later registry
validation correction rebuilt only the generator object/archive/executable in each
configuration; those unmeasured tool-only updates are not included in these timings.
These are not clean compiler build times or stable speedup ratios. Raw caller
observations are in `compiler/my-try-cpp/build/core-conversions-cost.json`.

Boolean, string-result and boxed conversion publication remain in A2; wrappers
remain in C. Full compiler frontend adoption is excluded from this goal.

## A2c — boolean, owned text and boxed scalar conversions (2026-10-27)

Publication now contains 57 conversion adapters. The additional 21 use the same
registry, emitter and ownership inventory. The sole generator extension forwards
optional target `transport` to the existing operation emitter. String results use
native owned transport, and boxed sources use ordinary native parameter borrowing.

`test_scalar_conversion_surface` exercises all new adapter signatures, strict boolean
literal parsing, boolean/null string exceptions, unsigned maximum formatting, scalar
boxed dispatch, rejected null/container scalar casts, non-finite integer rejection,
and owned results surviving temporary boxed source destruction. A repeated successful
numeric/boolean loop records **zero allocations**; copying long string text records
**one allocation** for its owned result. The existing numeric conversion/native tests
and metadata tests pass. Renaming fixtures retain unrelated types referenced by the
conversion registry while proving that arbitrary family names still work.

Six focused CTest cases pass in release (**2.33 s**) and ASan/UBSan/leak mode
(**12.09 s**). The relocated sealed-runtime proof additionally reuses
`cast_string_from_mixed` and `cast_boolean_from_dynamic` without generating adapters,
and runs the public caller against the relocated archive in both configurations.

Single-observation compile costs (`measure_global_operators.py --boxed-conversions`):

| Case | Seconds | Preprocessed bytes |
| --- | ---: | ---: |
| Public boxed string + boolean caller | 0.0704 | 21,289 |
| Native equivalent caller | 3.0137 | 2,976,452 |
| Independent boxed-to-string adapter | 3.3130 | 3,209,800 |

Eight-job changed-core/tool/test builds: release **22.08 s**, peak child RSS
**411,500 KiB**; sanitizer **50.70 s**, **614,532 KiB**. The release test was rebuilt
once afterward to add container rejection coverage. An earlier tooling build failed
because new core includes were added before generation; the tool was relinked against
the existing core archive, then generated the missing sources. Those unmeasured
bootstrap/test-only steps are not part of the successful build timings. Future demand
updates should build the tool and generate first, then add core source inclusions.
Raw measurements: `compiler/my-try-cpp/build/core-boxed-conversions-cost.json`.

Fixed-width boolean/boxed destinations beyond canonical int64 remain rejected pending
source-contract reconciliation; cast-to-boxed inventory review remains before A2 can
be closed. These results do not claim wrapper or full runtime-core completion.

## A2d — origin-preserving fixed-width boxed casts and closure audit

The user confirmed that boxed integers remain native int64 and boxed floats remain
float64 through dispatch, then use the requested target's ordinary checked rule.
The native owner now admits fixed-width integer destinations from both boxed
families; boolean conversion yields zero/one uniformly. Dynamic required casts share
the same destination admission and delegate to that cast owner. Publication adds
21 adapters (78 total), without generator changes or new boxed storage kinds.

`test_boxed_integer_casts` exercises all added signatures, verifies parity with
native-origin public casts, preserves input values and checks negative-to-unsigned,
narrow overflow, NaN and uint64 overflow failures. A boxed float at 2^63 casts to
uint64 successfully; a boxed UINT64_MAX string parses successfully. These cases
would fail if dispatch first narrowed everything to int64. Repeated successful
numeric/boolean/string-payload casts record **zero allocations**.

The new coverage audit records all 17 canonical scalar cast pairs in config, five
configured boxing pairs, dynamic boxing and both existing explicit family functions.
The full scalar/deferred ownership inventory is
`specs/planning/runtime_core_conversion_audit.md`. Existing boxing and family
conversion APIs are reused rather than duplicated in the cast registry.

Eight focused cases pass in release (**2.37 s**) and ASan/UBSan/leak mode
(**15.12 s**). Relocated sealed publication reuses `cast_uint64_from_mixed` with
zero generated adapters and runs in both modes. This completes scalar A2;
carrier/ownership/container conversion work remains routed to C/D/E.

Single observations (`measure_global_operators.py --boxed-integers`):

| Case | Seconds | Preprocessed bytes |
| --- | ---: | ---: |
| Public boxed uint64/int8 caller | 0.0540 | 21,300 |
| Native equivalent caller | 3.0008 | 2,977,177 |
| Independent boxed-to-uint64 adapter | 3.3584 | 3,209,222 |

Eight-job release rebuild: **25.06 s**, peak child RSS **413,764 KiB**; sanitizer
rebuild **54.76 s**, **620,172 KiB**. One build per configuration; generation used
the already-built tool before adding core source includes. Raw caller observations:
`compiler/my-try-cpp/build/core-boxed-integer-cost.json`. These timings do not claim
clean compiler costs or a stable speedup ratio.

## B1a — shared condition-observation recipe (2026-10-27)

A generic metadata function set expands one ordinary function recipe into 14
condition APIs. It uses the existing native helper, operation emitter and ownership
inventory; no condition type-name dispatch was added to the generator. Public
headers are template-free. Publication also rejects generated header/source path
collisions before returning a plan, preventing distinct APIs from overwriting files.

`test_public_conditions` covers all 14 signatures, numeric zeros/NaN/infinity,
string false-like values versus strict bool casts, boxed null/scalars, mixed table
rejection, dynamic object truthiness and error presence. Repeated long-string,
boxed, numeric and error observations record **zero allocations**. Function-set
checks cover arbitrary set/function names, one-pass substitutions, unknown/recursive
markers, collisions and zero-unit publication reuse. Existing metadata/conversion
checks remain green.

Seven focused release cases pass in **2.61 s**; ASan/UBSan with leak detection passes
in **17.09 s**. The relocated sealed-runtime proof imports string/dynamic condition
APIs without emitting adapters and executes them against the relocated archive in
release and sanitizer configurations.

Single observations (`measure_global_operators.py --conditions`):

| Case | Seconds | Preprocessed bytes |
| --- | ---: | ---: |
| Public string/dynamic condition caller | 0.0563 | 21,339 |
| Native equivalent caller | 3.3187 | 3,503,815 |
| Independent dynamic condition adapter | 3.4786 | 3,579,623 |

Eight-job changed-core/test build: release **21.42 s**, peak child RSS **414,344 KiB**;
sanitizer **49.00 s**, **623,064 KiB**. The release generator alone was rebuilt after
adding output-path collision validation; that unmeasured tool-only update is excluded
from the release core/test timing. Raw caller observations are in
`compiler/my-try-cpp/build/core-conditions-cost.json`.

B1 strict identity, context-specific text/concat, eager xor and remaining observation
protocols are still pending. Carrier and ownership/callable conditions retain their
C/D/F owners. Lazy source evaluation stays compiler-owned; this slice observes an
already evaluated value and does not publish templated conditional evaluators.

## B1b — bounded plain-scalar concatenation text (2026-10-27)

The existing function-set mechanism publishes 11 `concat_text` overloads from one
recipe. It delegates to the native scalar text helper and returns an owned public
string using existing native transport. No generator code changed. The bounded
plain domain follows `expression_evaluation.md`; uint64, boxed and carrier variants
are not implicitly added. Existing public string `+`/`+=` primitives handle the
converted operands, so no pair matrix is generated.

`test_public_text` proves all signatures, integer extremes within the domain,
float text versus explicit-cast formatting, boolean/null rules, embedded-NUL byte
preservation, primitive concat/append and owned lifetime beyond temporary inputs.
The repeated small integer/bool/null/string loop records **zero allocations**;
a long string conversion records **one allocation** for the owned result. The
metadata check verifies the exact bounded domain, absent unsupported API variants,
existing native result transport and zero-unit import reuse.

Nine focused cases pass in release (**2.64 s**) and ASan/UBSan/leak mode
(**17.37 s**). The relocated sealed-runtime proof imports `concat_text_float64`,
generates no adapter and runs against the relocated archive in both configurations.

Single observations (`measure_global_operators.py --text`):

| Case | Seconds | Preprocessed bytes |
| --- | ---: | ---: |
| Public float text + primitive concat caller | 0.0589 | 15,175 |
| Native equivalent caller | 4.4934 | 3,667,786 |
| Independent float text adapter | 4.5420 | 3,601,749 |

The first native benchmark probe omitted the generated string-operator header and
failed; the corrected probe includes the same operator owner as the public string
implementation. Only the successful complete observation is tabulated. Raw results:
`compiler/my-try-cpp/build/core-text-cost.json`. These are single observations, not
stable ratios. Eight-job changed-core/test builds took **33.79 s** release
(**416,188 KiB** peak child RSS) and **52.09 s** sanitized (**624,744 KiB**).

Carrier text remains C; B1 strict identity, eager xor and remaining observations
remain open. Public APIs accept evaluated values; source ordering, captured
compound destinations and write-back are compiler-owned and not newly implemented
by this slice.

## B1c — same-type strict identity (2026-10-27)

The existing function-set emitter publishes 14 adapters: identity and its negation
for canonical int64, float64, boolean, string, null, mixed and dynamic. Native
identity remains the semantic owner; numeric promotion is not involved. This is
not a complete heterogeneous identity resolver or a new fixed-width source admission.

`test_public_identity` checks all signatures, both 81-pair boxed scalar matrices,
ordinary numeric equality versus strict int/float distinction, NaN, signed zero,
infinity and embedded-NUL bytes. It also checks mixed table contents and dynamic
shared-object identity. Scalar/string/dynamic-object loops measure **zero allocations**.
A separate one-entry mixed-table comparison with a string key measures **two
allocations**, caused by native iterator key materialization. That existing cost is
recorded as container-batch debt; no allocation-free table claim is made.

Nine focused tests pass in release (**3.21 s**) and under ASan/UBSan/leak detection
(**15.49 s**). Metadata checks prove same-type signatures and ordinary publication
reuse. The relocated sealed-runtime proof imports mixed/float identity with zero
emitted adapters and executes against the relocated archive in both modes.

Single observations (`measure_global_operators.py --identity`):

| Case | Seconds | Preprocessed bytes |
| --- | ---: | ---: |
| Public canonical-float + mixed identity caller | 0.0546 | 18,551 |
| Native equivalent caller | 4.1089 | 3,630,205 |
| Independent mixed identity adapter | 4.8440 | 3,703,110 |

The initial eight-job release build compiled the changed core but failed on a
vexing-parse initializer in the test: **27.77 s**, peak child RSS **418,656 KiB**.
Correcting that test and finishing linking took **0.49 s**, **105,012 KiB**. The
sanitizer build took **51.39 s**, **625,800 KiB**. Both test executables were rebuilt
once more after separating the discovered native table allocation cost from scalar
allocation assertions; those unmeasured test-only builds are excluded above.
Raw caller observations: `compiler/my-try-cpp/build/core-identity-cost.json`.

Heterogeneous scalar planning, boxed/plain pairs, eager xor and remaining observation
protocols keep B1 open. Carrier state-preserving identity remains C; publishing these
same-type paths does not endorse the legacy sentinel-as-payload carrier behavior.

## B1d — heterogeneous plain-scalar identity planning (2026-10-27)

The global operator metadata resolves all 50 identity/negation combinations among
canonical int64, float64, boolean, string and null. Same types refer to existing
function-set APIs. Distinct types return a constant only after ordered eager
operand evaluation; no common numeric type or pair adapter is generated. Domain-set
selectors, exact operand admission and result types are metadata-defined. Function
set signatures and overlapping domain rules are validated, and bulk generation
reuses the validated registry and expanded function descriptors.

`check_identity_plans.py` verifies all scalar plans, same-family boxed call reuse,
rejected unsupported input domains, metadata-selected constants/result types,
conflicting rules and malformed backend signatures. It also proves zero-unit API
reuse. With `--core`, it generates a small public-only consumer and native equivalent:
all scalar results, left-before-right effects, left failure suppressing RHS, right
failure preserving earlier effects, and **zero successful allocations** pass in both
release and ASan/UBSan/leak mode. This is a consumer proof, not frontend integration.

Eight metadata regression cases pass in release (**3.12 s**) and sanitizer mode
(**21.98 s**). The final result-type generalization was rechecked with the focused
identity-plan test in both modes. Relocated sealed publication still reuses the
existing adapters. Runtime sources, public headers and core archive are unchanged;
the publication inventory updates only its metadata identity.

Focused 50-case proof program compile **and link** observations:

| Mode | Public consumer | Native equivalent |
| --- | ---: | ---: |
| Release | 0.2755 s | 3.7116 s |
| ASan/UBSan | 0.8149 s | 4.4307 s |

These whole-program timings are not comparable to earlier compile-only small caller
probes. The measured sanitized generator-only rebuild took **7.67 s**, peak child
RSS **234,008 KiB**; release tooling and the final result-type validation refinement
were not separately timed. No runtime-core rebuild was needed. Reproduce with
`check_identity_plans.py --tool <generator> --core <core.a> [--sanitize]`.

B1 boxed/plain identity, eager xor and remaining observations are still pending;
carrier state-preserving identity remains C. Constant plans never authorize dropping
operand effects or bypassing source exceptions.

## B1e — eager canonical logical xor

The global operator metadata selects one boolean implementation for bool/bool and
all four canonical int/float pairings. Existing conversion metadata supplies each
operand's independent checked boolean conversion. No generator change or operand-pair
implementation matrix was needed. Carrier xor is deliberately deferred to C.

`check_xor_api.py --tool <generator> --core <core.a> [--sanitize]` checks admission,
conversion selection, template-free publication and zero-unit reuse. Its compiled
public-only and native-equivalent consumers pass 85 truth-table cases (including
negative zero, infinity and NaN), ordered conversion and eager RHS evaluation.
Injected failures at each consumer evaluation/conversion stage suppress subsequent
stages. Successful operations allocate zero times. These are consumer proofs, not
frontend integration. Both release and ASan/UBSan with leak detection pass; relocated
sealed publication also passes in both modes.

Single observations for the complete proof program, compile **and link**:

| Mode | Public consumer | Native equivalent |
| --- | ---: | ---: |
| Release | 0.7266 s | 3.0839 s |
| ASan/UBSan | 2.7370 s | 5.5601 s |

These are not the earlier small compile-only probes; other focused checks were
running concurrently, so do not interpret their ratio as a stable benchmark.
The core/tool rebuild took 27.95 s in release (419,456 KiB peak child RSS) and
45.88 s sanitized (627,540 KiB). Nine focused metadata regressions passed in release
in 3.53 s and sanitized in 23.39 s. The existing native hash specialization warning
remains unchanged.

## B1f — primitive presence, emptiness and reset

Seventeen ordinary metadata-generated adapters complete float/null/boxed presence,
boxed collection emptiness and numeric/boolean/string cleanup. Existing type-owned
operations are reused rather than emitted again. The generator implementation is
unchanged. Public signatures contain no templates; reset borrows mutable storage.

`test_public_observations` passes in release and ASan/UBSan with leak detection:
false and zero remain present, boxed null is absent, "0" is nonempty, empty boxed
strings/tables are empty, and a table containing null is nonempty. Unsupported boxed
kinds throw `invalid_empty_operand`. Reset preserves native aliases, and cleaning a
dynamic binding leaves a copied binding's shared container intact. Successful
observation/reset paths allocate **zero** times, with setup and error paths excluded.

`check_observation_api.py` verifies bounded metadata admission, template-free headers,
17 implementation units and zero-unit API reuse; it prevents duplicate generation
of existing type-owned helpers. Six focused regression cases pass in release
(**3.82 s**) and sanitizer mode (**15.90 s**). Sealed relocated publication and a
public-only linked consumer pass in both modes. This is runtime API evidence, not
frontend adoption or proof of keyed/carrier behavior.

One compile-only observation of a two-function presence/emptiness caller, measured
after builds and sanitizer checks finished: public **0.0938 s**, native **3.4737 s**.
Reproduce with `tests/runtime/native/measure_observation_api.py`. The Ninja eight-job
core/tool/new-test rebuild took **21.91 s**, peak child RSS **417,300 KiB**, in release;
sanitized **52.18 s**, **626,132 KiB**. These are single observations, not statistical
benchmarks. The existing native hash specialization warning is unchanged.

## B1g — borrowed boxed/plain strict identity

The generic ordered-pair plan retains heterogeneous types instead of coercing both
to a common box. Twenty concrete implementations cover both identity operators,
two boxed families and five canonical scalar kinds; reversed operands reuse them
only after ordered eager evaluation. No runtime semantic changes were required.

Release and ASan/UBSan/leak checks pass:
- 40 metadata plans, exact signature validation, unsupported-width/cross-family
  rejection, template-free generated headers and zero-unit reuse;
- public behavior matrix covering null, bool, integer versus float, signed zero,
  NaN, long strings, embedded NUL bytes and containers versus plain scalars;
- zero allocations during comparisons (setup excluded), including long strings;
- a plan-driven compiled consumer for both operand directions, ordered effects and
  injected left/right failure suppression;
- sealed relocated publication and public-only linked reuse.

Seven focused regression tests pass in release (**4.14 s**) and sanitized
(**14.77 s**). The compiled plan consumer is additional runtime-boundary evidence,
not proof of frontend migration. Reproduce with `check_identity_pairs.py <tool>
<core.a> [--sanitize]` and `test_public_identity_pairs`.

Single compile-only observations after other checks completed, using
`measure_global_operators.py --identity-pairs`:

| Case | Seconds | Preprocessed bytes |
| --- | ---: | ---: |
| Public caller | 0.0538 | 18,213 |
| Native caller | 3.2591 | 3,630,108 |
| Private boxed/string adapter | 3.4274 | 3,704,547 |

Initial release generator rebuild: **3.51 s**. The subsequent eight-job core/tool/test
build (including final direction validation) took **38.40 s**, peak child RSS
**418,188 KiB**. Sanitized build: **65.67 s**, **627,284 KiB**. These are single
observations; the existing native hash specialization warning is unchanged.

## B1h — boxed text conversion and B1 checkpoint

Two existing `concat_text` recipe variants publish mixed/dynamic text conversion;
no generator code changed. Native scalar formatting and the family-specific
`Array`/`Object` labels remain unchanged. The existing public string owns the result.

The extended public text test verifies boxed null/bool/integer/float/string/container
kinds, embedded NUL bytes, lifetime after temporary destruction and allocation
counts. Small selected conversions allocate zero times; each long boxed-string
conversion allocates exactly once for the owned result, with no extra heap wrapper.
Five focused tests pass in release (**1.93 s**) and ASan/UBSan/leak mode (**9.78 s**).
Sealed relocated publication reuses both adapters with zero new implementation units
and passes a public-only linked consumer in both modes.

Single compile-only observations (`measure_global_operators.py --boxed-text`),
measured after builds and tests finished:

| Case | Seconds | Preprocessed bytes |
| --- | ---: | ---: |
| Public caller | 0.0633 | 20,925 |
| Native caller | 4.2393 | 3,585,423 |
| Private dynamic-text adapter | 4.1874 | 3,662,563 |

Eight-job core/tool/text-test rebuild: release **26.46 s**, peak child RSS
**419,432 KiB**; sanitized **58.02 s**, **627,472 KiB**. These are single observations,
not statistical speedup claims; the existing native hash warning remains unchanged.

The completion plan now distinguishes current batch status from chronological
checkpoints. B1's agreed scalar-facing scope is complete; B2 and carrier C remain
open, as do the later ownership/container/callback/helper batches.

## B2a — binary bitwise, checked shifts and power

Sixteen ordinary recipe variants publish canonical integer and homogeneous boxed
bitwise/shift operations plus canonical checked power. The metadata admission matrix
checks 486 cases: only canonical int64, homogeneous mixed and homogeneous dynamic
binary integer operations are admitted, and power remains int64-only. Native return
transport preserves boxed families; no generator code or arithmetic algorithms changed.

The public behavior test covers bit patterns, signed boundaries, shift counts
-1/0/62/63/64/max, invalid boxed kinds on either side, power zero/negative/overflow
cases and a repeated-multiplication oracle over 231 small powers. Successful paths
allocate zero times. Release focused regressions and admission checks pass. Five
sanitized tests pass in **34.07 s**, including the **26.00 s** admission matrix.
ASan/UBSan/leak checks and relocated sealed publication reuse pass.

One initial release test failed because it attempted structured inspection of a
native `ValueError`; `capture_runtime_exception()` intentionally rethrows that type.
The corrected harness verifies its preserved standard-exception message. Only the
test target was rebuilt in each mode. Structured power errors retain their code,
component and operator metadata. Template-free inspection of ValueError and legacy
boxed errors is explicitly unfinished G work, not counted as covered by catch-all
exception delivery.

Single compile-only observations after checks finished, from
`measure_global_operators.py --special-integers`:

| Case | Seconds | Preprocessed bytes |
| --- | ---: | ---: |
| Public power/boxed-shift caller | 0.0529 | 18,494 |
| Native caller | 3.3984 | 3,610,747 |
| Private mixed-shift adapter | 3.4359 | 3,681,798 |

Initial eight-job core/tool/test rebuild: release **22.64 s**, peak child RSS
**419,712 KiB**; sanitized **46.58 s**, **632,092 KiB**. The later test-only harness
rebuilds were not separately timed. These are single observations, not statistical
speedup claims. The existing native hash specialization warning remains unchanged.
