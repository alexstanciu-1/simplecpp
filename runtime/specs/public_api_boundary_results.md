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
