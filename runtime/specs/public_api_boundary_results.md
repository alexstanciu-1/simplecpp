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

## B2b — numeric spaceship and restricted domain members

Nine normalized adapters publish same-signedness integer spaceship and canonical
float/int64 spaceship. Optional per-side metadata type restrictions preserve the
narrower floating admission without a pair matrix. Integer comparison now shares
the native numeric comparison owner and compares directly, never by subtraction.

The 196-case metadata matrix checks exact admission, same-type/family-maximum
selection, int64 results, malformed restrictions and changed metadata without type
name exceptions. Nine template-free units publish and reuse with zero regenerated
implementations. Public behavior tests cover all integer-width boundaries, the full
six-value floating matrix (infinities and signed zeros included), NaN in either/both
positions, structured diagnostics and permitted integer-to-float precision loss.
Successful comparisons allocate zero times.

Five release regression cases passed (**2.50 s**), with the metadata matrix checked
separately. Six ASan/UBSan/leak regression cases passed (**20.43 s**), including the
**9.33 s** metadata matrix. Sealed relocated publication and its public-only linked
consumer pass in both modes. Source evaluation and carrier extraction are separate
responsibilities; these tests do not claim frontend adoption.

Single compile-only observations after checks finished, using
`measure_global_operators.py --spaceship`:

| Case | Seconds | Preprocessed bytes |
| --- | ---: | ---: |
| Public caller | 0.0555 | 13,166 |
| Native caller | 1.3028 | 1,976,890 |
| Private float adapter | 1.1687 | 1,989,621 |

Initial generator-only rebuild: **3.09 s**, peak child RSS **135,072 KiB**.
Subsequent eight-job core/tool/new-test build: release **21.36 s**, **421,708 KiB**;
sanitized **48.66 s**, **632,176 KiB**. These are single observations, not statistical
benchmarks. The existing native hash specialization warning remains unchanged.

## B2c — unary numeric plans and APIs

Eleven public functions expose canonical numeric positive/negative and canonical
integer complement, with family-preserving boxed counterparts. Unary plans have
one operand and an exact borrowed signature; they reuse ordinary function-set
emission and publication rather than introducing artificial binary operands.

Public behavior tests pass for integer boundaries within native arithmetic rules,
floating signed zeros/infinities/NaN, boxed kind rejection, unchanged input values,
and single operand evaluation. Successful operations allocate zero times. Native
signed-overflow limitations are unchanged; minimum-int64 negation is not claimed
safe or newly checked. Five release regressions passed (**3.47 s**), with unary
metadata checked separately; six sanitizer regressions passed (**20.15 s**).

The standalone native cost probe exposed an indirect dependency: boxed unary
operators were available to generated adapters through broader family headers.
The metadata now includes `operators/arithmetic/boxed_numeric.hpp` explicitly,
and the metadata test checks that direct include. Final rebuilt behavior/metadata
checks pass in release and ASan/UBSan/leak mode (final two sanitized cases **2.09 s**).
Sealed relocated reuse passes in both final builds. Legacy boxed error inspection
remains the explicitly recorded G gap.

Final single compile-only observations, `measure_global_operators.py --unary`:

| Case | Seconds | Preprocessed bytes |
| --- | ---: | ---: |
| Public caller | 0.0701 | 21,262 |
| Native caller | 3.3430 | 3,615,280 |
| Private dynamic-negation adapter | 3.5037 | 3,691,112 |

Initial generator rebuild: **3.07 s**, peak child RSS **135,492 KiB**. Initial
core/tool/test rebuilds: release **21.51 s**, **422,036 KiB**; sanitized **48.19 s**,
**632,092 KiB**. The final direct-include rebuilds were not separately timed.
Two incomplete native probe attempts lacked the direct operator header; only the
successful corrected probe is reported above. These are single observations, not
statistical benchmarks. The existing native hash warning is unchanged.

## B2d — logical negation by unary result composition

Six admitted scalar-facing logical-negation plans reuse existing condition APIs and
public boolean `!`. Unary metadata may specify one `${result}` expression marker;
clients save the observation result and apply the expression only after success.
No public header, runtime implementation or core archive changed. The inventory
updates only its metadata identity, and sealed relocated plan resolution reuses the
existing condition API without emitting another adapter.

The plan-driven public consumer passes in release and ASan/UBSan/leak mode: canonical
numeric/bool/error observations, false-like boxed strings, rejected mixed tables,
truthy dynamic objects, operand evaluation once and failure propagation. Successful
operations allocate zero times. Metadata tests cover exact admission, missing or
repeated result markers, binary misuse and an alternative metadata transformation.

Five focused regression cases were checked in both modes. The function-set rename
fixture initially failed because it renamed the recipe but left its new unary
reference dangling. The fixture now renames linked operation metadata too; its
focused rerun passes in release and sanitizer mode. The other four cases passed
unchanged. Sealed publication/relocation passes in both modes, including resolution
and compilation of the logical-not plan from the sealed runtime's own metadata.

Single whole-proof compile **and link** observations: release **0.3601 s**, sanitized
**0.7514 s**. These are not earlier small compile-only caller measurements; other
focused work was running concurrently. Generator rebuilds: release **3.24 s**, peak
child RSS **135,280 KiB**; sanitized **8.75 s**, **265,028 KiB**. No runtime-core
rebuild was needed. Reproduce with `check_logical_not.py --tool <generator>
--core <core.a> [--sanitize]`.

## B2e — mutable unary updates with owned snapshots

Sixteen ordinary function-set variants expose prefix/postfix increment/decrement
for canonical int64/float64 and mixed/dynamic. Unary metadata records `ref` versus
`const_ref` and distinguishes resolving writable storage once from observing an
operand once. Exact C++ signatures require mutable storage and return values, not
references, including native prefix forms whose underlying result is a reference.

Public tests verify signature/return types, aliases, old/new snapshots after later
mutations, one destination resolution, safe signed boundaries, floating infinity/
NaN, and rejected boxed kinds leaving original storage unchanged. Successful paths
allocate zero times. Six release regressions pass (**2.78 s**) with update metadata
checked separately; seven ASan/UBSan/leak regressions pass (**14.14 s**). Relocated
sealed publication and a public-only update consumer pass in both modes.

Single compile-only observations after checks, `measure_global_operators.py --updates`:

| Case | Seconds | Preprocessed bytes |
| --- | ---: | ---: |
| Public caller | 0.0570 | 21,261 |
| Native caller | 3.2473 | 3,615,316 |
| Private dynamic postfix adapter | 3.5488 | 3,691,177 |

Initial generator rebuild: **3.54 s**, peak child RSS **135,908 KiB**. Eight-job
core/tool/new-test builds: release **23.30 s**, **422,624 KiB**; sanitized **53.65 s**,
**635,948 KiB**. These are single observations, not statistical benchmarks. Existing
native hash warnings and signed-overflow limitations remain unchanged.

This covers value updates, not compound RHS ordering or general source lvalue
capture. Carrier updates remain C; property/index owner lifetime remains D/E, and
legacy boxed exception inspection remains G. Compound snapshot/check/write-back is
still explicitly unfinished B2 work.

## B2f — canonical numeric compound plans

Nineteen admitted directional plans reuse existing numeric, bitwise, shift, power
and checked-cast implementations. No new runtime adapter or core rebuild is needed.
The metadata resolver returns ordered capture/snapshot/conversion/evaluation/
calculation/commit steps. Public-only consumers execute those steps, including an
RHS that overwrites the destination: an original 10 plus an RHS returning 3 still
stores 13, even when the RHS first stores 100.

The combined proof covers all nineteen successful plans, destination capture once,
owned results after later mutation, destination failure suppressing RHS, throwing
RHS, zero division, negative/overflowing power and invalid shifts. Failed operations
leave the RHS's store of 100 intact. All successful paths allocate zero. Errors are
observed as thrown failures; this does not close the previously recorded exception
inspection gap. Release and ASan/UBSan with leak detection pass.

Single whole-proof compile **and link** costs: release **0.4312 s**, sanitized
**0.7821 s**. Generator rebuild costs: release **6.98 s**, peak child RSS
**163,528 KiB**; sanitized **20.57 s**, **531,060 KiB**. These are single observations
on WSL, not matched compile-only public/native benchmarks. Generation retains the
same 317 runtime implementation units; only the metadata identity changes in the
publication manifest. Six focused metadata regressions pass in release (4.35 s) and with
ASan/UBSan/leak detection (30.17 s). Sealed relocated publication passes in both modes; it resolves the compound plan using its own tool and
metadata and reuses the existing API definition with zero generated application units.

Reproduce the execution proof with `check_compound_api.py --tool <generator>
--core <core.a> [--sanitize]`. Fixed widths, boxed stored-kind write-back, text,
carriers and general indexed owner lifetime remain outside this completed slice.

## B2g — homogeneous boxed arithmetic

Ten implementations publish addition, subtraction, multiplication, division and
remainder for mixed/mixed and dynamic/dynamic through the existing global operation
identities. Plain arithmetic recipes now use the same function-set owner; their
final generated C++ files are unchanged. Variant-selected private includes avoid
adding boxed dependencies to plain numeric adapters. Normalized duplicate-signature
validation covers function-set and expression-owned arithmetic alike.

Public tests cover all four int/float kind pairings, family-preserving results,
integer-only remainder, null/bool/string/container rejection on both sides, zero
divisors, unchanged inputs and saved-left snapshots. All successful numeric paths
allocate zero. Seven focused release tests pass (4.16 s), as do the same seven with
ASan/UBSan and leak detection (22.12 s). Sealed relocation/import checks pass in both
modes with zero generated application units. Boxed numeric calculation is complete
for homogeneous pairs; this does not establish compound stored-kind validation,
scalar-peer admission, indexed capture or carrier behavior.

Single compile-only observations on WSL after validation, reproduced with
`measure_global_operators.py --boxed-arithmetic`:

| Case | Seconds | Preprocessed bytes |
| --- | ---: | ---: |
| Public dynamic-add caller | 0.0563 | 20,971 |
| Native dynamic-add caller | 3.6060 | 3,615,301 |
| Private dynamic-add adapter | 4.7331 | 3,691,156 |

Eight-job release core/tool/test build: 25.05 s, peak child RSS 422,212 KiB.
A subsequent include-isolation correction required another release build:
22.66 s, 423,704 KiB. Final sanitizer core/tool/test build: 57.40 s,
639,472 KiB. These are single observations, not whole compiler rebuild measurements.

## B2h — boxed compound replacement against the saved kind

Two ordinary function-set recipes return checked replacements for mixed and dynamic.
Ten homogeneous arithmetic compound plans reuse them. Each recipe applies the native
stored-kind check to a private copy of the preserved left value. The live destination
is not passed to the check; the consumer commits only after successful validation.

The public execution proof covers 19 plain plans and ten boxed plans. For every
boxed operator it tests all four original-left/RHS numeric-kind pairings while the
RHS changes the live destination to the opposite numeric kind. This proves both
required directions: an original int rejects a float result despite a live float,
and an original float accepts a float result despite a live int. Nonnumeric RHS
failures retain earlier boolean mutations, results remain independent after later
writes, and successful numeric paths allocate zero times. Mutable write-back
parameters and wrong result signatures reject during metadata resolution.

Release and ASan/UBSan with leak detection pass. Four release metadata regressions
pass in 5.88 s; the sanitized execution/metadata proof passes, followed by three
additional metadata regressions in 12.15 s. Sealed relocated publication passes in
both modes, including resolving the checked write-back API from the sealed metadata.
The existing foreign-exception inspection gap is unchanged; failure propagation is
verified without claiming structured inspection of every native diagnostic.

Whole execution-proof compile and link: release 2.1177 s, sanitized 3.3675 s.
The release observation overlapped the sanitizer runtime build and is not an isolated
caller benchmark. Eight-job core/tool builds: release 31.11 s, peak child RSS
423,824 KiB; sanitized 67.73 s, 639,228 KiB. No new runtime semantic authority or
per-operator write-back implementation was introduced.

Isolated single compile-only observations after validation, reproduced with
`measure_global_operators.py --boxed-compounds`:

| Case | Seconds | Preprocessed bytes |
| --- | ---: | ---: |
| Public dynamic replacement caller | 0.0605 | 21,088 |
| Native dynamic replacement caller | 3.6724 | 3,615,391 |
| Private dynamic replacement adapter | 4.3058 | 3,691,565 |

## B2i — canonical numeric peers reuse boxed implementations

Two metadata operand-conversion recipes use the existing mixed/dynamic public
constructors. Thirty-six admitted binary scalar-peer plans and eighteen boxed
compound plans reuse the existing arithmetic and checked replacement implementations.
Common-operand plans now expose both conversion steps. No extra per-pair adapters,
new runtime implementations or core rebuilds are required; generation retains 329
implementation units and updates only the publication metadata identity.

Public execution proofs cover both binary operand orders, left snapshots before RHS
mutation, compound stored-kind success/rejection, unchanged failure-side effects,
owned results and zero allocations on successful numeric paths. Narrow/unsigned,
bool/string/null and cross-family peers remain rejected, as do float remainder and
plain scalar compound destinations with boxed RHS. Missing boxing recipes, duplicate
sources and repeated/missing expression value markers reject in metadata checks.

Seven release metadata regressions pass (8.28 s). The combined public execution proof
passes under ASan/UBSan with leak detection, followed by six additional metadata
regressions (30.68 s). Sealed relocation/import proofs pass in both modes, resolving
and compiling a scalar-peer operation from the relocated tool and metadata with zero
application implementation units.

Generator-only rebuilds: release 5.70 s, peak child RSS 136,428 KiB; sanitized
12.54 s, 276,624 KiB. Whole expanded execution-proof compile and link: release
3.0994 s, sanitized 7.3265 s. These larger proof timings are not small caller
benchmarks; release overlapped other generation/build work and sanitizer validation
overlap/load can affect the measurements.

Isolated single compile-only scalar-boxing/addition caller measurements after all
validation (`measure_global_operators.py --boxed-peers`): public **0.0589 s**,
**21,006 preprocessed bytes**; native **3.7016 s**, **3,615,338 bytes**. No private
adapter compile is needed for this slice because it introduces no new adapter.

## B2j — bounded scalar text and string compound plans

One primitive string-calculation API combines the existing thirteen text helpers
without treating text as a numeric cast. The plain scalar admission slice selects
121 binary text pairs and eleven string-destination compound forms. A per-operation
conversion owner validates const-reference source/result signatures and selects the
existing context formatter. Compound plans reuse those conversions unchanged.

Public execution proofs cover all eleven scalar conversion forms on both sides,
float context formatting, RHS mutation, independent result snapshots, self
concatenation and embedded NUL/UTF-8 bytes. Injected allocation failures prove that
left snapshot/conversion failures suppress RHS evaluation, while right conversion
and calculation failures retain earlier mutations and skip compound write-back.
Short successful compounds allocate zero times. The tested long string calculation
allocates once through both the existing primitive and the new public entry point;
there is no additional boundary allocation. Identity conversions borrow owned
snapshots instead of copying them again.

Release and ASan/UBSan with leak detection pass. Six release metadata regressions
pass (7.15 s); five additional sanitized metadata regressions pass (31.99 s)
after the sanitized execution proof. Sealed relocated publication passes in both
modes and resolves and compiles the text compound
using the SDK's own metadata/tool with zero new application implementation units.
The generated runtime inventory now contains 330 implementation units. Boxed text
replacement and carriers remain separate incomplete work.

Single whole execution-proof compile **and link** observations: release **0.4750 s**,
sanitized **0.7243 s**. Generator bootstrap build: **4.54 s**, peak child RSS
**137,000 KiB**. Eight-job core/tool builds: release **21.60 s**, **423,676 KiB**;
sanitized **58.79 s**, **639,620 KiB**. These are not whole compiler rebuild costs.
Reproduce the proof with `check_text_compound_api.py --tool <generator>
--core <core.a> [--sanitize]`.

## B2k — boxed text replacement and converted snapshots

Metadata explicitly admits a differing compound write-back source and reuses public
string-to-box constructors. Text plans now cover 143 binary pairs and 25 compound
forms. Numeric saved-kind protocols reject the converted snapshot mode and remain
unchanged. No runtime implementation units were added; the inventory remains 330.

The first allocation test compared a complete compound against boxing alone and
failed (two allocations versus one). Inspection showed two independent issues:
write-back must preserve independent destination/result strings, and the generic
raw snapshot could unnecessarily copy the original boxed string/container. The
baseline now compares equivalent construction plus destination copy, and text plans
save the converted string directly before RHS evaluation, avoiding the raw box copy.

All original boxed kinds are exercised, including null, both bool values, numeric,
string and container kinds. The proofs cover canonical peers, same-family operands,
preserved old aliases, both binary operand orders, embedded bytes and injected
left-conversion/replacement allocation failures. Short boxed compounds perform two
allocations in each family, matching direct construction plus independent destination/
result storage across the tested original kinds. They are not allocation-free.
Short plain-string compounds remain allocation-free; long string calculation still
matches the primitive's single allocation.

Release and ASan/UBSan with leak detection execution proofs pass. Six release metadata
regressions pass (7.19 s), followed by five additional sanitized metadata regressions
(30.62 s). Sealed relocated publication passes in both modes and resolves the converted snapshot
and string-to-box replacement through its own metadata/tool with zero new application
units. The expanded execution-proof compile/link takes 2.9469 s in release and
9.1791 s sanitized; these are whole proofs, not small caller compile measurements.
Generator-only builds: initial release 5.95 s (138,488 KiB peak child RSS), snapshot
refactor 3.86 s (138,624 KiB), final sanitized 9.62 s (280,756 KiB). Runtime core was
not rebuilt. Reproduce using `check_text_compound_api.py --tool <generator>
--core <core.a> [--sanitize]`.

## B2 non-carrier closure audit

The current metadata and inventory resolve all 58 normalized API identities behind
15 special-operation identities to existing public headers and reuse them with zero
new implementation units. The closure table in `runtime_core_completion.md` maps
requirements to proof bodies and preserves deferred/external ownership areas.

Four special-operator executables were relinked against the current release and
sanitizer archives; Ninja performed only four link steps per configuration, with no
source/core compilation. Nine focused checks (four execution tests and five metadata
checks) passed: release 9.68 s; ASan/UBSan with leak detection 51.05 s. Compound and
text execution/publication evidence remains at its immediately preceding slice
checkpoints; the audit did not repeat those compilations without a changed concern.

This closes B2's agreed public-path subset. It does not close carrier forms, indexed
retention, boxed comparison/ordering completion, native diagnostic inspection or
remaining core families. Signed native overflow limitations are visible at G closure;
the successful sanitizer tests do not claim defined behavior for every overflowing
input. The active runtime-core goal remains incomplete.

## C1 checked carrier arithmetic projection

`check_carrier_projection.py --tool <generator> --core <core.a> [--sanitize]`
resolves nullable/Result/plain numeric arithmetic and generates an application-owned
nullable<int64> for public-only execution. Five operations, ordered extraction
failures, copy/reset/take, zero-value presence and owned extraction lifetime pass
in release and ASan/UBSan with leak detection. Measured successful operations use
zero allocations; errors are excluded from that allocation statement. The sealed
application specialization is reused with zero newly generated implementation units.

Compile-only single observations for the proof (Clang, -O1): generated storage
1.0421s release / 1.6409s sanitized; public caller 0.1003s / 0.1306s. These are a
small nullable proof and its storage unit, not a full compiler build or a direct
native/public speedup benchmark. Final incremental generator builds: 4.96s release,
20.23s sanitized; the latter overlapped lightweight release metadata checks. The
runtime-core archive was not rebuilt. All 330 existing runtime generation plans
were unchanged by the new descriptors/policy; publication metadata identity was
refreshed after that equality check, without reprobing unchanged layouts.

Four focused metadata/operator regression tests passed in each configuration:
release 6.36s; ASan/UBSan with leak detection 29.46s. The execution proof above ran
separately. Result execution, sentinel carrier recipes, state comparisons,
conditions/conversions and conditional lifecycle remain pending; neither C nor the
first complete carrier-path review is closed by these results.

## C2 required-scalar carrier casts

The extended `check_carrier_projection.py --tool <generator> --core <core.a>
[--sanitize]` proof passes in release and ASan/UBSan with leak detection. New
execution cases distinguish absent/present-zero bool casts, checked int8 narrowing
with source preservation, and nullable string absence. These successful casts,
along with the retained arithmetic/take/reset cases, allocate zero. Error paths are
excluded from that allocation claim. Application-owned nullable storage still
reuses its sealed publication with zero newly emitted units.

Single compile-only observations: storage 1.0264s release / 1.2560s sanitized;
public caller 0.1094s / 0.1596s. Incremental tool builds took 7.88s / 19.45s, with
lightweight release regressions overlapping the sanitized tool build. No runtime-
core rebuild or new runtime implementation units were required. All 330 generated
runtime plans remained identical; publication API identity was refreshed only after
that comparison. The new shared carrier source is private generator code, not a
consumer dependency.

Required-value conversion plans for Result are covered by metadata tests, not a
Result execution claim. Sentinel recipes, conditions, state comparisons and same-
family reconstruction remain pending. This does not complete the first carrier
review gate.

Five focused regression suites (metadata, global operators, conversions, compounds
and carrier projection) passed: release 7.74s; ASan/UBSan with leak detection 40.64s.
The public-only execution/allocation/lifetime proof ran separately as described above.

## C3 carrier conditions and logical negation

The public-only nullable<int64> proof now includes absence, zero, positive/negative
payload truthiness, logical negation and selected branch execution. It preserves the
explicit bool-cast distinction, prior arithmetic/cast failure/lifetime checks and
sealed application specialization reuse. Release and ASan/UBSan with leak detection
pass; the measured scalar success cases allocate zero. Metadata tests additionally
check scalar variants, Result state composition, nested Result-of-nullable conditions,
aliases, incompatible collection payloads and invalid state/function signatures.

Single compile-only observations: generated nullable storage 1.0151s release /
1.2801s sanitized; public caller 0.1161s / 0.1806s. Initial condition tool rebuild
was 6.26s; the final sanitized tool rebuild was 21.31s and overlapped lightweight
release regressions. Existing runtime generation plans remain identical across all
330 units, so no runtime-core rebuild or new runtime adapters were required.

This is scalar/copy-required public-path evidence. Noncopyable condition observation
and avoiding unnecessary copies of large payloads remain mandatory C follow-up, not
covered by the zero-allocation number. Result/sentinel execution and source formation
validation remain separate from metadata composition. State-aware comparisons and
same-family reconstruction are still needed before the first complete carrier review.

Eight focused regression tests passed: release 9.24s; ASan/UBSan with leak detection
47.08s. The public-only carrier execution/allocation/publication proof ran separately.

## C4 numeric carrier comparisons and ordering

The public proof now generates two application-owned specializations:
nullable<int64> and nullable<float64>. Numeric equality/inequality, canonical strict
identity/inequality, matching absence across payload identities, RHS effects with
absent left, and alias mutation after the left snapshot pass in release and
ASan/UBSan with leak detection. Checked ordering also proves that absent left
suppresses RHS effects. The retained arithmetic/cast/condition/lifetime cases pass;
all measured scalar success operations allocate zero. Sealed publication reuses
both application specializations without newly emitted units.

Compile-only single observations: two storage units combined 2.0817s release /
2.9479s sanitized; the expanded public caller 0.1748s / 0.3579s. These proof sizes
have grown, so they are not a fixed-workload comparison with earlier one-storage
measurements. Final tool rebuilds were 4.74s / 13.22s; sanitized compilation overlapped
lightweight release regressions. Runtime-core was not rebuilt. All 330 existing
runtime generation plans remained identical after the metadata additions.

The scalar result-alias regression also covers carrier plans, ensuring they retain
the registered result identity instead of requiring a hard-coded spelling. Ordinary
Result error state equality is metadata-tested; strict matching error identity
rejects explicitly pending its public path. Literal null/sentinel comparisons,
bool/string/boxed carrier domains and same-family reconstruction remain open. This
slice does not close the first complete carrier review or C as a whole.

Eight focused metadata/identity/ordering regression tests passed after the result-
identity correction: release 10.04s; ASan/UBSan with leak detection 61.12s. Public
execution, allocations, lifetime and publication reuse were checked separately.

## C5 same-family nullable/Result reconstruction

The public proof now generates four application-owned specializations: nullable
and Result of int64 and float64. It verifies widening/round trips, nullable absence,
structured Result error metadata after source mutation, and failed narrowing that
preserves the source and an existing destination. Release and ASan/UBSan with leak
detection pass; sealed publication reuses all four specializations without new units.

Successful measured scalar operations allocate zero. Result error preservation with
the test's non-SSO message allocates twice in both configurations; this is reported
separately, not hidden in a zero-allocation claim. Native Result has an rvalue error
constructor, but the generated public recipe currently exposes only const-reference
error construction. Moving the owned error snapshot into the destination remains
required C lifecycle/allocation work. Public/native storage is equal by layout probes
and generated assertions: each tested nullable is 16 bytes, each Result 96 bytes on
this target. These sizes are target observations, not portable ABI constants.

Single compile-only observations: four storage units combined 4.4031s release /
5.8894s sanitized; public caller 0.2088s / 0.4028s. Tool builds were 2.29s / 6.33s,
with lightweight release regressions overlapping sanitized tool compilation. Runtime
core was not rebuilt; all 330 existing runtime generation plans remained identical.

Eight focused regression tests passed: release 10.10s; ASan/UBSan with leak detection
56.10s. The public execution/allocation/lifetime/publication proof ran separately.
Cross-family and recursive conversion remain rejected. Literal-null comparisons,
strict error identity, sentinel recipes and the remaining lifecycle/observation work
remain open; neither the first complete carrier review nor full C is closed here.

## C6 nullable literal-null presence

Shared metadata now plans strict null presence in either operand order without
payload extraction, payload comparison or wrapper snapshots. The public-only proof
adds application-owned nullable<string> to the four scalar carriers. Present and
absent integers and long strings cover both strict forms/orders; a side-effecting
operand verifies single evaluation. All measured successful cases allocate zero.
Release and ASan/UBSan with leak detection pass, including sealed reuse of all five
application specializations. Result error reconstruction still measures two separate
allocations, retaining the C5 follow-up.

Layout probes/native assertions report nullable<string> at 40 bytes; numeric
nullable types remain 16 bytes and the two Result types remain 96 bytes on this
target. These are target observations. The route adds no stored state or adapter.

Single compile-only observations: five generated storage units total 5.8091s
release / 7.6577s sanitized; public caller 0.2519s / 0.4796s. Tool builds were 5.77s /
14.89s. Lightweight release regressions overlapped part of the sanitized proof;
these are observational costs, not isolated benchmark comparisons. Runtime-core
was not rebuilt: all 330 complete generation plans were equal before/after, allowing
the publication metadata identity to refresh without layout or implementation changes.

Metadata tests cover nested vector payload presence, both operator directions,
duplicate literal rejection, ordinary nullable/null rejection and Result/null
rejection (including Result-of-nullable). No broad native identity overload is used
to extend source admission. Eight focused release regressions passed in 9.68s; the same eight passed under
ASan/UBSan with leak detection in 55.87s.
The carrier review and remaining state/lifecycle contracts remain open.

## C7 Result error move construction and assignment

The Result metadata recipe now exposes owned-error move construction and assignment
alongside the existing const-reference overloads. Full generation/probes still
produce 330 units, with unchanged type layouts, registered API identities and source
inventory. Only the published result_vector_string header and its implementation
change; application Results obtain the same methods from the same recipe.

The public proof verifies scalar Result reconstruction preserves its source while
reducing the measured non-SSO error-message allocation count from two to one. The
remaining allocation is the deliberate owned error snapshot. Explicit transfer of
preconstructed owned errors into application and runtime-published Results allocates
zero. Both value-to-error and error-to-error assignments, moved-from error reuse,
retained metadata and rejected no-error construction/assignment are exercised.
Rejected assignment preserves the previous destination error.

Release metadata/conversion/carrier regressions pass (three tests, 4.50s). The
public-only execution/lifetime/allocation/publication proof passes separately.
Single compile-only release observations: five application storage units 6.2376s;
public caller 0.2762s. The necessary runtime-core rebuild and tool relink cost
19.82s release / 46.59s sanitized. Unlike C1–C6 plan-only changes, this slice changes
a generated runtime implementation and cannot reuse the old archive.

ASan/UBSan with leak detection also passes the public proof, including the same
one-allocation reconstruction and zero-allocation owned transfers. Sanitized
compile-only observations: five storage units 9.1128s; public caller 0.6599s.
Both configurations successfully reuse the sealed application specialization
publication. Conditional lifecycle and strict structured-error identity remain
separate required work; no broad family-completion claim follows from this fix.

The same three focused regressions pass under ASan/UBSan with leak detection
in 31.24s. No generator source changes or additional generator builds were needed.

## C8 structured Result error identity

The shared state-comparison plan now admits a function over owned state data in
addition to constant same-state results. Two public error_value identity functions
reuse native presence/message/line/file comparison. Generation/probes publish 332
units (two new function units); no wrapper-pair implementation or storage-layout
change is introduced. Direct error comparison borrows its inputs and allocates zero.

The public proof adds Result<int64>/Result<float64> state comparisons. It exercises
matching errors across payload kinds, each differing message/line/file field,
ordinary equality versus strict identity, strict negation, different carrier states
and RHS mutation of an aliased source after the left snapshot. Direct error tests
also distinguish no_error presence. Metadata tests rename the family/state tag,
retain a synthetic result identity, and reject missing/incorrect state accessors
and non-borrowing comparator signatures. Publication reuse remains covered.

Release execution passes. The measured long-message strict Result comparison
allocates four times: two whole-operand snapshots and two owned error projections.
This is distinct from the allocation-free comparator and scalar success cases.
Removing redundant state projections remains required scoped-observation work;
whole-left snapshot semantics must survive any optimization. C7 reconstruction
continues to allocate once. Observed carrier sizes remain 16/40/96 bytes for the
same tested nullable/Result types on this target.

Single release compile-only observations: five application storage units 5.9467s;
expanded public caller 0.3391s. The initial generator preparation build took 21.77s
and included a core rebuild; final core/tool compilation took 21.22s. Nine focused
release metadata/operator/conversion/identity regressions passed in 11.08s while
the sanitizer build ran. These are observed costs, not isolated benchmark claims.

The public execution/allocation/lifetime/publication proof also passes ASan/UBSan
with leak detection, retaining the same allocation counts. Sanitized compile-only
observations: storage units 7.5520s; caller 0.6901s. Sanitized core/tool build cost
51.64s. Inventory comparison confirms all previous APIs and type layouts unchanged,
with exactly the two new concrete identity functions added.

All nine focused regressions pass under ASan/UBSan with leak detection in 59.26s.
The full carrier review remains open; sentinel families, wider payload domains and
conditional/scoped lifecycle are not closed by this error-identity proof.

## C9 conditional lifecycle foundation

Lifecycle entries now use the existing capability selector and target-check emitter.
The physical proof adds a test-only optional-storage family backed by native nullable,
with move-only and copyable application records. It is intentionally separate from
production carrier planning, whose conditional extraction/capability propagation is
not yet migrated. Public output has concrete/deleted special members, with no public
templates or requires clauses; the target driver still receives native/disabled modes.

Release proves default absence without a default-constructible payload, deleted
copying, move construction/assignment, self-move protection, reset and exactly-once
resource destruction. The measured execution allocates zero. A forbidden copy fails
compilation. A false declared copy capability fails the target probe before changing
an existing header or sealed manifest. Reusing the sealed application specialization
produces no implementation units. Native/public layout probes report 16-byte move-only
optional storage and 8-byte copyable optional storage on this target.

Final release compile-only observations: two storage units 2.0375s; public caller
0.1089s. Initial tool build 6.49s, sanitized tool build 17.44s. Runtime-core and its
published APIs/layouts were unchanged. An earlier release proof was followed by one
repeat to validate the added false-capability/publication-preservation case.

ASan/UBSan with leak detection passes the same lifetime, zero-allocation, rejected
copy, false-capability and publication tests. Sanitized storage compilation is
2.7078s and caller compilation 0.1460s. Four focused release regressions pass in
5.19s; they overlapped the sanitized proof, so these are observational timings.

All four focused regressions also pass under ASan/UBSan with leak detection in
29.22s. Production carrier noncopyable admission remains explicitly pending.

## C10 production nullable and shared capability propagation

The production nullable recipe now uses one capability owner shared with physical
generation, operation planning and imported fact validation. Copy/move facts are
registered physical guarantees and can propagate conditionally through family
arguments. Companion classes do not inherit primary-class guarantees. Nullable
omits copy/extraction/take for noncopyable records while preserving default absence,
reset, destruction, guaranteed moves and no-copy literal-null presence plans.

The revised lifecycle proof uses production nullable, not a test-only family. It
adds a freshly generated vector<int64> and nullable parent, verifies independent
value copies and effective facts, and reuses all four application storage units
from a sealed publication. Forged imported capabilities reject in generation and
planning. False target copyability claims reject without overwriting a publication.
Metadata tests also check nested noncopyable capability propagation, family admission
before presence observation, and companion capability isolation.

Release and ASan/UBSan with leak detection pass. Measured record operations allocate
zero; the separate nested vector copy scenario allocates four times. Target layout
observations: move-only nullable 16 bytes, copyable-record nullable 8 bytes, vector
40 bytes, nullable<vector<int64>> 48 bytes. The existing scalar/error carrier proof
also passes in release, preserving its prior allocation counts (Result error
reconstruction one, strict error comparison four, direct error comparison zero).

Final production-proof compile observations: four storage units 4.5463s release /
6.6867s sanitized; public caller 0.1174s / 0.1537s. The existing carrier proof measured
5.5991s storage / 0.3210s caller in release. Final tool rebuilds were 5.92s release /
17.57s sanitized, after earlier development builds (7.77s and 5.81s release, 20.25s
sanitized). Rebuilds stayed tool-only. Full generation/probes refreshed capability
inventories while all 332 existing generated runtime code units remained byte-equal;
no runtime-core rebuild was needed.

Ten final focused release regressions pass in 10.32s. Production Result/sentinel
lifecycle, general source formation constraints and scoped payload observation are
still incomplete. Physical capability propagation is not a claim that noncopyable
hash/vector operations or all source type arguments are now supported.

All ten final focused regressions also pass under ASan/UBSan with leak detection
in 59.73s. The first complete carrier review remains open.

## C11 Result conditional default/copy/move lifecycle

Result uses the existing capability mechanism for lifecycle and payload APIs;
`default_storage` supplies the independent default-construction guarantee. No
generator source change or build was needed. Full generation/probes refresh the
inventory while all 332 existing runtime code units remain byte-identical, so the
runtime-core archives also remain valid without recompilation.

The public proof now generates eight application units, including non-default,
noncopyable Result payloads and Result-of-nullable. It verifies default success
containing inner absence, success/error transitions, exactly-once destruction,
self-move, copyable take output/error behavior and invalid no-error assignment
preserving a live noncopyable success value. Copy/default misuse fails compilation.
Source Result argument admission is not inferred from these physical fixture records.

Release and ASan/UBSan with leak detection pass, with sealed publication reuse.
Measured record and short-error operations allocate zero; the separate nested vector
copy scenario allocates four times. The four new Result instantiations each measure
96 bytes on this target, matching native layout probes. These are observations,
not portable fixed ABI sizes or a zero-allocation claim for long error strings.

Compile-only totals: eight storage units 8.8421s release / 13.1729s sanitized;
public caller 0.1366s / 0.2306s. Four focused release regressions pass in 4.96s.
Sentinel families, remaining operator domains, source formation constraints and
scoped observations remain open, as does the first complete carrier review.

All four focused regressions also pass under ASan/UBSan with leak detection
in 29.75s.

## C12 — metadata static factory prerequisite

Static class methods now share ordinary signature/dependency/native-result
transport. Metadata rejection checks cover nonboolean flags, const/explicit forms,
constructor/function misuse and receiver substitutions. A renamed family proves
there is no sentinel-family dispatch in the emitter.

The eight-unit lifecycle proof adds an absent factory to application nullable
metadata and calls it with a noncopyable, non-default-constructible payload. Release
and ASan/UBSan with leak detection pass, including zero measured record-operation
allocations, destruction checks, rejected copy/default compilation and sealed reuse.
The separate nested vector copy scenario retains its four allocations.

Single-run compile totals: generator rebuild 5.51s release / 17.59s sanitized;
eight application storage units 9.9336s / 14.8181s; public caller 0.1356s / 0.2577s.
Four focused regressions pass in 5.55s / 31.37s. Regression checks overlapped the
compiled proof, so these are observed costs, not isolated performance comparisons.
An initial release compilation caught and corrected string-prefix concatenation in
the emitter; an initial test run also caught a reused Python fixture variable.
Neither runtime metadata nor generated runtime units changed; no core rebuild was
needed. The actual sentinel recipes remain the next slice.

## C13 — sentinel carrier recipes and state/take proof

Added result_or_false and result_or_bool through the existing metadata lifecycle,
static factory and carrier policies. No generator or native runtime changes were
needed. Regeneration preserves all 332 existing runtime implementation units byte
for byte; only the metadata publication identity changes.

Eight generated application units cover integer and boolean payloads, nested
nullable and noncopyable/non-default records. Release and ASan/UBSan with leak
detection pass. Checks include false payload versus false sentinel, true sentinel
leaving the payload output untouched, preserved alternative output on payload
success, reset/copy/move state transitions, exactly-once record destruction, checked
missing payload failures and sealed application reuse. Measured successful operations
allocate zero; exception construction is outside that interval.

Metadata tests verify both recipes and shared arithmetic/comparison/condition/cast
plans, including sentinel equality tags and the empty-string exceptions versus
required bool/numeric payload extraction. This is not compiled execution of every
operator plan or admission of boolean/string carrier comparisons.

Single-run compilation totals for eight storage units: 10.4189s release / 12.7450s
sanitized; public caller: 0.1495s / 0.1944s. Five focused regressions pass in 6.24s /
37.51s. Runs overlapped, so these are observations rather than isolated comparisons.
The first storage compile caught inconsistent reference/value return deduction in
the bool take snippet; explicitly returning an owned boolean corrected it. No
runtime-core or generator rebuild was needed. Public metadata remains below 200KB.

## C14 — scalar/boxed carrier equality execution

The nonnumeric equality metadata owner now admits matching boolean/string as well
as mixed/dynamic, using value_equal/value_not_equal public helper names. Four new
runtime implementation units bring publication to 336. Existing boxed API identities
and header paths remain, with their helper names migrated in public tests/consumers.
Type metadata provides native boolean operand adaptation; the C++ generator is
unchanged. Numeric equality remains with its promotion owner.

A new public-only eight-specialization proof derives 69 functions from comparison
plans and executes 676 boolean state expectations across all four families and
ordinary equality/inequality/strict identity/nonidentity. It also exercises string
comparison, distinct-kind strict comparison, boxed numeric equality and rejected
boxed kind mismatch. Scalars/short strings allocate zero. Long string equality
allocates four times (two snapshots and two owned extractions); this is recorded
scoped-observation debt rather than a zero-copy claim. Sealed application reuse and
relocated runtime imports pass, including the newly published plain functions.

Release and ASan/UBSan with leak detection pass. Eight focused regressions complete
in 8.26s / 49.72s. The compiled proof's eight storage units take 9.0602s / 13.1212s;
its large generated matrix caller takes 3.1127s / 10.9189s. These are single-run
costs of the exhaustive test caller, not a minimal public-header benchmark. Successful
core rebuild/link takes 21.14s / 44.15s. Runs overlap and are not isolated comparisons.
An initial core compilation exposed missing native boolean operand adaptation; it
was corrected in metadata before the successful builds. No native semantics or
generator source changes were required. Public metadata remains below 200KB.

## C15 — execute sentinel conditions, casts and ordered numeric plans

Expanded the sentinel consumer to render condition/negation, bool/int8/string cast,
same-family float64 reconstruction and add/subtract/multiply/divide/remainder/less/
compare functions from resolved metadata plans. No native implementation, generator
or metadata adjustment was needed. Ten application units include the two new floating
sentinel targets; all runtime/generated publication files remain unchanged.

Release and ASan/UBSan with leak detection pass. Execution verifies required bool
and numeric payloads versus empty-string sentinel exceptions; false/true state
reconstruction; source-preserving failed narrowing; skipped RHS work on failed left
extraction; retained RHS effects on failed right extraction; and saved left payload
survival when RHS work resets the source. Existing take/state/nested/noncopyable
lifecycle and sealed reuse checks remain. Successful operations allocate zero.

The final sanitizer run also includes explicit zero-divisor checks for division
and remainder, added after the release run started; these two checks were not part
of that release execution. Final metadata-only checks pass. Compile-only totals:
ten storage units 11.1188s release / 14.8055s sanitized; caller 0.3103s / 0.6070s.
No runtime-core or generator recompilation was required. Full source admission,
compound/update/lazy protocols and scoped observation remain separate C work.

## C17 — bounded carrier text operand conversion

Concatenation now has a generic metadata-selected carrier operand conversion policy.
It reuses the state conversion planner shared with explicit casts while retaining
concat_text as the present-payload formatter. All 336 runtime generated units remain
byte-identical; the publication identity is refreshed and no core rebuild is needed.

A six-specialization public proof passes in release and ASan/UBSan with leak
detection. It covers four boolean carrier families, true payload versus true sentinel,
float text parity with the plain formatter, embedded NUL/non-ASCII bytes, cross-carrier
text, left failure suppressing RHS work and right failure preserving the destination.
Measured short success paths and the selected float example allocate zero. Sealed
application reuse passes. Metadata checks enforce the bounded payload/peer domain
and reject unsupported nested, boxed, fixed-width and uint64 extensions.

Compile-only proof totals: storage 6.8769s release / 9.5664s sanitized; public caller
0.1618s / 0.2714s. Initial generator builds take 6.03s / 18.91s. A final review added
an early rejection for missing carrier state descriptions, matching other state-aware
owners; its focused negative check requires no generated-runtime recompilation.
Carrier compound text write-back remains pending. These single-run timings overlap
other checks and are not isolated comparative benchmarks.

Six focused release regressions pass in 10.81s before the final guard; the final
release text metadata check also passes. In the overlapping sanitizer regression
run, five existing checks pass but the newly added negative text check reaches the
old executable before the final link and fails. The final text check is rerun after
that build completes; this timing overlap is not counted as a passing final run.
The final guard-only generator rebuild costs 6.23s release / 15.85s sanitized.
The final sanitizer text check passes in 15.14s after the rebuilt executable is
available. Together with the five passing focused regressions and the compiled
sanitized consumer, this verifies the completed slice; no failing check remains.

## C18 — carrier compound text destinations

Extended the shared ordered compound owner with explicit carrier payload-target
admission and existing payload reconstruction for write-back. Both carrier string
destinations and plain string with carrier RHS reuse binary text conversions. Names
propagate through the common resolver. No runtime implementation units changed and
no runtime-core rebuild was needed; publication identity was refreshed.

The nine-unit public text proof passes in release and ASan/UBSan with leak detection.
It includes the four string carriers, absence/false/true-to-payload replacement,
returned destination snapshots, RHS alias mutation after left conversion, one-time
member selection, left-error suppression of RHS work, failed RHS conversion and a
thrown RHS effect after destination mutation. These are public-library consumer
proofs; general frontend owner retention and indexed destinations are not claimed.
Short-value successful paths allocate zero, and sealed application reuse passes.

Compile-only totals: storage 11.5412s release / 14.4576s sanitized; public caller
0.4876s / 0.7673s. Release generator builds took 6.40s initially and 5.88s after a
local rule-selection correction preserving existing plain-operand diagnostics;
sanitized generator build took 20.11s. Six focused regressions pass in 12.21s /
71.10s. An initial negative metadata test encountered the earlier snapshot-mode
validator; it was adjusted to remove the setting and directly exercise the new
required-snapshot rule. Timings are single runs with overlapping checks.

## C19 — canonical carrier unary values and updates

Added per-operation checked carrier unary policies for canonical plus/minus,
integer complement and integer/float prefix/postfix increments/decrements. Updates
reuse the scalar mutable-reference implementation on an owned local payload and
the payload-construction helper shared with compound write-back. Prefix/postfix
results are independent updated/old carrier snapshots. No public/native runtime
operators were duplicated; all 336 existing generated units are unchanged.

The eight-unit public proof executes 52 successful value/snapshot cases and 65
unavailable-state cases. It checks preserved source values, returned snapshot
independence and Result error message/line/file after rejected updates. Successful
in-range operations allocate zero. Noncanonical, boxed, nested and wrong-kind
payloads remain rejected. These tests do not establish new integer overflow semantics.

Initial test failures were in fixture/diagnostic assumptions: float64 is a published
family rather than a metadata leaf; new policy validation precedes the old scalar
signature check; and the complement expected-value expression used an invalid
function-style cast of a platform two-word type macro. Fixtures now read published
type bindings, isolate the intended signature validator, and use expected value -6.
No production change was required by those failures.

Release public proof passes, with storage compilation 9.6012s and caller 0.8255s;
sealed reuse passes. Five unchanged release regressions passed initially, and both
corrected focused checks pass on rerun in 1.95s. All seven sanitizer metadata/regression
checks pass in 59.97s. Generator builds take 6.41s release / 18.55s sanitized. No
runtime-core rebuild is needed. Single-run timings overlap other validation work.
The compiled ASan/UBSan proof with leak detection also passes, including sealed reuse;
its eight storage units take 12.0278s and its caller takes 1.4830s to compile.

## C20 — canonical arithmetic carrier compounds (2026-10-27)

The metadata opts the five canonical arithmetic compounds into checked payload
projection. Existing ordinary directional compound rules remain the admission owner;
no carrier-pair runtime overloads or implementation units are added. All generated
file hashes remain unchanged across the 336 runtime implementation units; only the
publication metadata fingerprint changes.

`check_carrier_compound.py` resolves 156 plans across four families and plain peers.
Its public-only consumer executes 348 successful cases and 464 failure cases, covering
saved-left ordering, RHS alias mutation, returned snapshot independence, absent/
error/sentinel extraction, zero divisors and prior RHS effects surviving failure.
The successful measured region allocates zero times. The eight application storage
units also seal and reuse with no regenerated source. This is not frontend or indexed
owner-retention validation, and does not claim an extreme-integer overflow contract.

Five focused release regressions pass in 3.69s; the corresponding sanitizer
regressions pass in 25.73s. Initial negative fixtures were corrected to isolate their
intended validation owners (ordinary compound versus carrier policy; nonempty domain
versus payload admission). No failed behavioral proof was waived.

The timed final generator rebuild takes 6.50s release and 19.51s sanitized; an earlier
release build preceded the diagnostic/fixture cleanup. Runtime-core was not rebuilt.
The compiled release proof passes: eight storage units 9.7756s, combined caller
13.0225s. These are single-run wall times, with other validation active, not an
isolated benchmark or a per-expression compilation cost. The caller deliberately
contains the full success/failure matrix.

The compiled ASan/UBSan proof with leak detection also passes, including publication
reuse: storage compile 12.6209s and combined caller compile 39.8109s. Future test
extensions should keep the executed semantics while avoiding redundant expansion of
this already large caller matrix.

## C21 — metadata-only canonical carrier bitwise/shifts (2026-10-27)

The existing projection/compound protocols admit the five canonical integer bitwise
and shift operations through metadata alone. Ninja reports no generator work in both
release and sanitizer configurations. Regeneration preserves every generated-file
hash across the 336 runtime implementation units; core is not rebuilt.

The reused compound proof's `--special` mode checks 80 cross-family binary plans,
60 executable compound plans, 180 success cases and 232 failure cases. It covers all
four carrier families, plain peers, independent result snapshots, saved-left/RHS
aliasing, unavailable payloads, prior RHS effects and checked counts -1,64,65 versus
valid endpoints 0,63. Noncanonical, boxed and nested payloads reject. No binary power
permission is inferred from the separately admitted carrier compound contract.

Five focused release regressions pass in 7.44s and their ASan/UBSan counterparts in
43.66s. The compiled release public proof passes with zero allocations in successful
operations and sealed application reuse: four storage units compile in 6.0050s and
the combined caller in 3.5903s. These are single-run wall times during concurrent
validation, not isolated comparative benchmarks.
The compiled ASan/UBSan proof with leak detection also passes, including sealed
reuse: four storage units 6.2220s, combined caller 7.8840s.

## C22 — carrier compound power with separate binary admission (2026-10-27)

A shared projection worker now serves independently admitted binary and compound
plans. The compound metadata admits canonical carrier `**=` without adding carrier
`**` to binary admission. The ordinary power backend and payload reconstruction
remain unchanged; there is no operation-name exception in the generator.

The power mode of the existing proof covers 12 compound plans, 72 success cases and
92 failure cases across all four families and plain peers. It checks zero exponent,
negative base, successful signed boundaries, negative exponents, overflow, missing
payloads, RHS effects on failure, snapshot independence and zero allocations in the
successful region. Binary carrier power still rejects. A renamed metadata operation
reuses the declared `power_int64` backend (the initial fixture incorrectly expected
a newly named backend; that expectation was corrected).

Seven existing focused release regressions pass in 3.97s; the power metadata proof
also passes. Eight sanitizer regressions pass in 31.03s. Both compiled power proofs
pass with ASan/UBSan/leak detection on the sanitized run and sealed application reuse.
Single-run storage/caller compile times: release 7.7568s / 1.3465s; sanitized
6.5094s / 1.6755s. Other validation was active, so these are not isolated benchmarks.

Generator rebuild: release 6.57s, sanitized 18.88s. All generated file hashes across
336 runtime implementation units remain unchanged; core was not rebuilt. The
existing public special-integer test passes, including exact power error codes,
component and operation inspection. An optional native `test_pow` invocation was
not run because that executable was not built in this configuration; its dry-run
would also rebuild a filesystem unit. The public proof covers this slice without
that unrelated build, and the missing executable is not counted as a passing test.

## C23 — ordinary and carrier string relational bindings (2026-10-27)

Four reusable string ordering bindings use the existing ordered-pair function-set
mechanism. Numeric promotion remains in its existing branches; string/string selects
byte ordering. No generator changes, carrier-pair overload product, string arithmetic,
heterogeneous string comparison or string spaceship are added. Publication now has
340 implementation units; existing generated files are unchanged and twelve new
files provide the four sources plus public/compatibility headers. Core's explicit
implementation list includes the new bindings.

`check_carrier_order.py` validates 100 plain/carrier resolver combinations and rejected
payload widening. Its public consumer executes 256 success cases and 80 missing-state
cases, including embedded NUL bytes, empty strings, all four relational operations,
plain peers and aliased RHS mutation after left extraction. Short successful cases
allocate zero times; no zero-allocation claim is made for copying long payloads.
The four application carrier units seal and reuse without regeneration.

Eight focused release regressions pass in 4.05s; eight sanitizer regressions pass in
28.17s. Release compiled proof passes, storage 5.6289s and caller 1.5043s. Generator
code was unchanged. After wiring the new files into the explicit core implementation
list, core builds take 25.02s release and 57.69s sanitized. These are single-run wall
times with concurrent validation, not isolated benchmarks. Both builds retain the
existing ineffective hash explicit-instantiation warning; no new warning was added.
The compiled ASan/UBSan proof with leak detection also passes, including sealed
reuse: four storage units 5.9458s, combined caller 3.8547s.

## C25 — exact scalar lazy coalescing (2026-10-27)

The new selection planner validates both branch types and exact join forms before
emitting usable-value selection stages. It supports canonical scalar plain/plain,
nullable/result/result_or_false-to-plain, and homogeneous carrier pairs. A renamed
carrier recipe proves admission is metadata-owned. Result-or-bool on either side,
widening, cross-family and non-admitted nested payloads reject independently of
whether fallback would execute. Broader boxed/record/ownership joins remain open.

A single concrete `coalesce_require_value(boolean)` API delegates to the factored
native selected-domain error check. Existing native coalescing delegates to that
same check. The public path does not expose callback templates or duplicate error
construction in consumers. Publication adds one implementation unit (341 total).

Six focused release regressions pass in 3.10s. The compiled public proof passes 60
success cases and 36 failure cases: successful false/zero/empty payloads skip even
throwing fallbacks, absent/error/false selects fallback once, both unavailable report
the exact error code/component/operator, and returned snapshots survive source edits.
Successful short/scalar paths allocate zero times. Twelve application units seal and
reuse without source regeneration. Release storage compile 13.4904s; caller 0.6997s.

The first generator compile exposed a mixed `auto` declaration error, corrected by
separate declarations before any tests passed. Initial failed build 5.53s, corrected
release build 1.66s; sanitized generator build 17.86s. After publication, release
core rebuild/relink 19.60s and sanitized 43.53s. Existing hash-instantiation warning
remains unchanged. Timings are single-run wall times with other validation active,
not isolated benchmarks. No logical-operator policy change is part of this slice.
Six sanitizer regressions pass in 20.40s. The compiled ASan/UBSan proof with leak
detection and sealed reuse also passes: storage 18.0382s, caller 1.5333s.

## C26 — exact scalar/carrier ternary and Elvis joins (2026-10-27)

The shared selection owner now separates full ternary from Elvis evaluation and
preserves exact carrier branch identity. The condition owner handles truthiness;
existing carrier recipes handle payload construction. No logical-operator policy is
inferred. Both alternative types and physical copy requirements validate before
execution; a renamed family proves there is no generator type-name exception.

`check_carrier_conditional.py` proves exact scalar and all four scalar-carrier joins,
rejects widening/cross-family/non-admitted payloads, and exercises 204 successful
value/lazy cases plus 84 failure/state-preservation cases. Cases cover skipped throwing
branches, falsey payloads selecting Elvis fallback, true sentinels skipping it, full
ternary returning an unavailable state without extracting, and independent returned
snapshots. The successful scalar/short-string region allocates zero times. Sixteen
application specializations seal and reuse without regenerated sources.

Five release regressions pass in 3.45s; five sanitizer regressions pass in 25.29s.
Both compiled public proofs pass, including ASan/UBSan and leak detection. Release
storage/caller compilation: 19.1941s / 3.4662s; sanitized: 24.6989s / 8.7881s.
These are single-run wall times with other validation active, not isolated benchmarks.

Initial release generator build 5.66s; review corrected capability access to use the
resolved record's capabilities list before tests, with a 2.03s rebuild. Sanitized
generator build 16.75s. All 341 runtime implementation units are unchanged and core
was not rebuilt. General boxed/record/ownership joins and scoped observation remain
open; these bounded proofs do not claim frontend adoption or general selection closure.

## C27 — constant observations of noncopyable records (2026-10-27)

Explicit constant leaf facts produce condition plans that evaluate the source once
but do not read/copy it. Carrier state dispatch can select that constant without an
owned payload extractor. The new public proof uses the production nullable recipe
with deleted payload/carrier copies, checks zero observation moves/allocations,
100 repeated observations, empty/present/reset states, exactly-once destruction and
source evaluation/exception propagation. Metadata checks reject conflicting callable
owners, nonboolean facts and wrapper constants; nested state-dependent noncopyable
observation still rejects. A renamed carrier keeps the mechanism metadata-driven.

Five focused release regressions pass in 3.52s; five sanitizer regressions pass in
22.59s. Both compiled public proofs pass, including ASan/UBSan/leak detection and
sealed storage reuse. One storage unit compiles in 1.2509s release / 1.1444s sanitized;
caller compiles in 0.1525s / 0.1524s. These are single-run concurrent-validation wall
times. Generator builds take 1.58s release and 3.10s sanitized. No runtime units or
publication metadata change, and core is not rebuilt.

Prepared observation facts remain explicit planner inputs. The storage reuse proof
does not claim those facts are inferred from a native artifact or that general source
formation is settled. Long-string/error extraction copies and state-dependent scoped
observations remain debt; no general borrowed interior-reference API is introduced.

## E1 — move-only vector storage (2026-10-27)

`check_vector_lifecycle.py` exercises production vector metadata with a move-only
application record and an ordinary integer vector. Metadata assertions cover omitted
copy methods/cursor/layout probes, retained ordinary APIs, conditional primary-class
rejection, invalid capability parameters and references to omitted companions.

Release and ASan/UBSan with leak detection pass: three records destroyed exactly
once; append/remove/container move/clear allocate zero times after reserve; container
moves do not move payloads. Ordinary copying is independent, and a cursor survives
clear with its saved value. Public-only headers pass the private-template guard;
sealing and importing the application generates no duplicate sources.

Single-run compile costs for both generated storage units combined / public caller:
release **2.8258s / 0.1149s**, sanitizer **3.7510s / 0.1400s**. These are focused
fixture costs, not complete compiler rebuild times. Existing runtime generated
implementation files were unchanged; only publication metadata identity changed.
No runtime-core rebuild was needed. Generator builds used Ninja eight jobs.

The first regression assertion incorrectly expected cursor snapshotting on `set()`;
current native indexed assignment changes the live cursor value. The final proof
uses structural clear preservation. E2 subsequently confirmed the indexed-write
behavior matches `specs/array_semantics.md`; that audit item is closed. No native iteration behavior was changed to satisfy this storage slice.

## E2 — conditional typed-hash storage (2026-10-27)

`check_hash_lifecycle.py` uses production metadata with move-only application records,
a second record with an empty default state, integer keys and string keys. It checks
copy/cursor/access omission, integer-only append and default-dependent removal.
The compiled fixture generates four hashes plus nullable<int64> for owned find.

Release and ASan/UBSan with leak detection pass. Six owned payloads are destroyed
exactly once across replacement, removal and clear. Whole-container transfer performs
no payload moves; replacement, remove, transfer and clear allocate zero times after
setup. Initial insertion/index allocation is outside that zero-allocation region.
Ordinary copied hash values remain independent, and a cursor preserves entries over
clear. Public-only headers pass the guard and sealed application reuse emits no
new sources. Four focused metadata regressions pass in 3.37s.

Single-run storage compilation (five units combined) / public caller:
release **10.9975s / 0.1308s**; ASan/UBSan **18.8289s / 0.1770s**. These focused
fixture timings are not full rebuild costs. No generated runtime implementation
changed and no core rebuild was required. The generator itself needed no changes.

Removal for non-default-constructible key/value storage remains a native tombstone
limitation, explicitly gated and retained in E backlog; source eligibility and
application-specific key binding completion are not claimed by these native proofs.

## E3 — finished-cursor advancement (2026-10-27)

Vector/hash metadata now uses the same finished `next()` no-op as mixed/dynamic.
Only the published vector_string implementation changed; signatures/layouts did
not change. Generated application vector/hash cursors use the same recipe change.
No native iterator or preservation algorithm was modified.

Expanded lifecycle proofs cover empty cursors, ordinary exhaustion, preservation
following consumption of the final value, repeated advancement with zero allocation,
and rejected finished key/value reads. The runtime-owned vector_string path is
also called. Public-only compilation and sealed application reuse remain checked.
The complete metadata shape/default/hash/boxed generation script passes.

One release and one sanitizer core rebuild were required: **21.11s / 45.40s**.
These builds overlapped, so the measurements are observed wall times rather than
isolated comparative benchmarks. Both used Ninja eight jobs. The existing native
mixed-hash explicit-instantiation warning remains unrelated to this change.

Release focused compile costs (storage / public caller): vector two units
**2.8193s / 0.1233s**; hash five units **10.9426s / 0.1405s**.
ASan/UBSan with leak detection passes for both expanded proofs. Sanitizer storage /
public caller costs: vector **3.9181s / 0.1699s**, hash **18.1807s / 0.2034s**.

## C28 — scoped carrier conditions (2026-10-27)

`check_scoped_conditions.py` generates four string carriers and nullable<error_value>.
It verifies the exact internal const-reference assertion, scoped plan, dependency
API publication, malformed observation descriptor rejection, renamed-family reuse
and explicit nested-owned fallback. No primitive condition table was duplicated.

Release and ASan/UBSan with leak detection pass: 100 observations per carrier/state
allocate zero times after setup for long strings and retained error values; empty
and "0" strings remain false; source evaluation occurs once and source exceptions
propagate; bypassing the plan and calling the payload method on absence rejects.
Public-only compilation and sealed application reuse pass. These are runtime physical
proofs, not new source formation permissions. Nested no-copy observation is not proved.

Single-run compile costs, five storage units / public caller:
release **6.8091s / 0.1143s**; sanitizer **7.3907s / 0.1755s**.
The compiled sentinel regression passes, including state/take/nesting and zero
success-path allocation checks: storage **11.0566s**, caller **0.3186s**.

The selected runtime-owned carrier is result<vector<string>> and has no callable
payload condition, so no runtime implementation unit changes. Its generated header
has a whitespace-only change from omitted-method emission. The sanitizer generator
build consequently also rebuilt core through the header dependency; no runtime
semantic/core performance improvement is claimed from this slice.


## C29 — nested scoped conditions (2026-10-27)

The expanded scoped proof covers eight generated units: four string carriers,
nullable<error_value>, and the three Result families over nullable<string>. Repeated
observations preserve outer failure, successful inner absence, present long-string
payloads and boolean sentinel states without observation allocations.

The constant-condition proof adds Result<nullable<move-only record>> to its existing
nullable record. Its fixture SDK explicitly supplies the record's constant condition
fact and updates/seals that fixture metadata identity. This is a physical generation
proof, not a production frontend fact provider. The outer type is statically
noncopyable; repeated observation performs no moves/allocations, and failure assignment
destroys the owned record exactly once. Public-only consumers and sealed reuse pass.

Release single-run storage/public-caller costs: two record units **2.9755s / 0.1456s**;
eight scoped units **10.6669s / 0.1247s**. These execution measurements preceded the
final opt-in publication policy; that policy selects the same generated observation
bodies. Final-policy metadata and sanitizer proofs are recorded below. No runtime
implementation source changed; generator/core rebuilds were affected by the previous
omitted-method whitespace header and its cleanup in this slice. No overall build-speed
claim is inferred from these focused timings.

Final-policy validation: six focused metadata tests pass in **8.76s**. ASan/UBSan
with leak detection passes both compiled proofs and the sealed old-policy rejection.
Sanitizer storage/public-caller costs: record **2.4146s / 0.1632s**, scoped nested
**12.0633s / 0.1929s**. No runtime-source or frontend-admission change is implied.

## C30 — exact boxed conditional joins (2026-10-27)

`check_carrier_conditional.py --boxed` uses the existing execution-plan consumer
with a separate mixed/dynamic domain: **102 value/lazy, 42 failure/state-preservation
and 20 boxed-kind cases**. All four carriers and exact branch directions are covered.
Boxed null stays a successful payload when selected by full ternary, but Elvis selects
a fallback. Mixed containers reject conditions before evaluating the fallback;
dynamic containers, including empty containers, select the saved left. Mutating an
extracted mixed table preserves the original value; extracted dynamic tables retain
shared identity.

Release and ASan/UBSan with leak detection pass, as do public-only compilation and
sealed reuse. Scalar-valued boxed success cases allocate zero times in the measured
region. Container construction/mutation and throwing/error cases are outside that
zero-allocation assertion; this is not a claim that copying arbitrary boxed payloads
never allocates. The ordinary scalar metadata mode also passes (204 value/lazy and
84 failure/state cases).

Single-run compile costs for eight generated units / generated public caller:
release **9.4955s / 2.0366s**; sanitizer **12.2972s / 5.9202s**. This caller contains
the full generated case matrix and is much larger than the earlier simple callers.
No generator code changed and no runtime-core rebuild was needed. Coalescing remains
outside this slice because its boxed-null usability rules differ.

## C31 — boxed coalescing observations (2026-10-27)

The boxed mode of `check_carrier_coalesce.py` executes **30 success, 6 failure and
6 container cases**, plus a native dynamic coalesce comparison. It covers exact
mixed/dynamic pairs and nullable/Result/false-sentinel-left fallbacks, successful
boxed-null payloads, absent/error/sentinel fallback, valid null-box fallback results,
throwing unselected/selected RHS paths, value independence and dynamic identity.
Static negative cases retain result_or_bool rejection on either side and reject
unsupported join forms and malformed observation metadata.

Release and ASan/UBSan with leak detection pass; public-only compilation and sealed
application reuse pass. Scalar-valued boxed success cases allocate zero times after
setup. Container setup/mutation and throwing cases are outside that allocation
assertion. The native fixture proves direct dynamic null now evaluates fallback and
that selecting a null fallback remains valid. This repairs a native mismatch with
`conditional_expression_matrix.md`; no new source rule was introduced.

Six generated units / public caller compile costs, single runs:
release **6.9799s / 0.2843s**; sanitizer **9.2657s / 0.5063s**.
The separate native comparison fixture compiled in **2.7827s / 2.7758s** respectively.
Both configurations rebuilt core for the shared native helper change. The existing
mixed-hash instantiation warning remains. Three focused metadata regressions passed
in **5.76s**; scalar coalescing also retains its 60 success/36 failure planning cases.

## E4 — boxed count publication (2026-10-27)

The existing metadata function-set emitter now publishes focused `count(mixed)`
and `count(dynamic)` headers/implementations. No new generator branch or native
behavior was needed. The functions return source `int64` and delegate validation
to native count helpers; this does not change existing vector/hash API signatures.

`test_public_observations` passes in release and ASan/UBSan with leak detection.
It checks empty and populated tables (null entries count), all scalar/null/string
rejections, mixed copy independence and dynamic shared identity. Repeated successful
count/observation calls allocate zero times after setup; rejection allocations are
not constrained. Both new headers independently pass public-only guarded compilation.
Two metadata regressions pass in 1.67s, including 19 observation/reset bindings,
unsupported demand rejection and zero-unit imported-definition reuse. This is
metadata reuse evidence, not a new sealed-package relocation test.

Compile-only observation caller, single isolated run after builds completed:
**0.0537s public / 2.8843s native** at `clang++ -std=c++23 -O2`. The caller exercises
presence, emptiness and count; this measures header/translation-unit cost, not
runtime throughput or a full compiler rebuild. A preliminary run overlapped core
builds and is not used for the comparison. Release and sanitizer core builds each
ran once because the aggregate gained two implementation units; their elapsed times
were not benchmarked. The existing mixed-hash explicit-instantiation warning remains.

## E5 — strict vector helper publication (2026-10-27)

The existing vector recipe now emits reserve/capacity/resize/filled/clear/
clear_keep_capacity and both compact signatures. The lifecycle proof generates
three application vector types (move-only, integer, throwing-copy record), plus
exercises runtime-published vector_string filled construction. It proves:

- negative reserve/resize/compact/filled requests throw `value_error`; existing
  size, contents and capacity survive the checked failure;
- filled construction copies the requested value, zero count works, and a failure
  during copying destroys all partially constructed elements;
- resize retains the prefix, both clear helpers retain capacity, and compact does
  not truncate when its requested capacity is below size;
- reserve/clear/capacity work with move-only storage; fill operations are omitted;
- reserved scalar resize/clear operations and move-only operations allocate zero
  times after setup; this is not a claim that filled construction allocates nothing;
- public-only caller compilation, ASan/UBSan/leak detection and sealed application
  specialization reuse pass.

Standalone compilation exposed a pre-existing include-order dependency in the
broad PHP support header (`value_copy` used an undeclared ownership helper).
The unchanged vector provider definitions were extracted into a focused native
header; an exact-text comparison verifies no provider behavior changed. PHP support
includes the new header. This does not repair or expose unrelated value_copy APIs.

Three storage units / caller compile, single runs: release **5.0525s / 0.1663s**
(overlapped a core rebuild; informational only), sanitizer **6.5690s / 0.2222s**.
Both core configurations were rebuilt after the header extraction. The existing
mixed-hash instantiation warning remains. Metadata/lifecycle checks pass separately;
these metadata checks are not substitutes for the compiled execution proof.

## Metadata section packaging — 2026-10-27

Split the near-200KB metadata entry point into root (13,224 bytes), families
(82,836), function sets (50,444) and global operators (39,038). The driver assembles
one metadata object for the unchanged C++ generator. An exact comparison against
the pre-split resolved object passes; its canonical API identity is unchanged.
Regeneration of 343 implementation units changes no generated source, public header
or specialization manifest.

All 18 affected metadata-reader checks pass in **22.24s**. The new section-loader
CTest passes in **0.09s**, covering flat compatibility, disjoint ownership, invalid
paths/shapes, missing files, symlink escape rejection, identity compatibility and
SDK integrity tracking. The relocated sealed operator publication test passes with
zero generated units and a public-only linked caller.

No runtime implementation or public declaration changed. There was no runtime/core
rebuild or new allocation/sanitizer benchmark; existing native proofs remain evidence
for the identical generated code. The relocation test compiled one public consumer.
This consolidation adds no semantic coverage to the completion inventory.

## E5 nested/defaulted composition follow-up — 2026-10-27

`test_defaults_composition.py` now accepts current `--core`/`--tool` artifacts and
creates a disposable sealed SDK. Its initial run caught a copied-recipe factory
collision: sequence_string and vector_string both declared vector_filled with the
same arguments and different return types. The fixture's distinct families now name
that factory `${name}_filled` using the existing substitution mechanism. No runtime
or generator behavior changed.

The expanded public-only caller proves nested filled copies survive input/extracted
copy mutation, both defaulted-family factories coexist with runtime vector helpers,
repeated count/empty/capacity observations and zero-count construction allocate
nothing after setup, and escaped inner/outer/defaulted cursors keep their values
alive. Defaulted/explicit requests reuse the same sealed specialization with no new
implementation units.

Release and ASan/UBSan with leak detection pass. Focused Ninja builds (three generated
storage units, one public caller and links, eight jobs) took **2.1772s release** and
**3.5729s sanitizer**, single runs. These are small fixture builds, not full compiler
or runtime rebuild timings. Existing runtime archives were reused. The first failed
release build is excluded from these successful-run times.

## G1 — core wall-clock helpers (2026-10-27)

Ordinary function metadata publishes the two microtime overloads. The native
provider moved unchanged into `scpp/detail/microtime.hpp`; an exact-text comparison
against its previous PHP string-adapter definitions passed. This isolates the core
helper without changing source aliases or migrating the datetime/strings modules.

The guarded public-only test passes in release and ASan/UBSan with leak detection.
It checks positive numeric epoch samples, bool-false logic_error, eight-digit
fractional text shape and independent owned text lifetime. It deliberately makes
no monotonicity assertion. One hundred numeric calls allocate zero times; a string
call allocated twice on this implementation. String allocations are observed, not
promised as a cross-platform bound. Each public header independently compiles.

The relocated sealed publication test imports both functions with zero generated
units and links/runs a public-only caller. Two metadata checks pass in **1.43s**.
Single isolated compile-only caller costs at clang++ C++23/O2:
**0.0538s public / 1.6303s native**. These callers exercise both overloads; they are
not runtime throughput or full build measurements. Release and sanitizer core
archives were each rebuilt once for the two new implementation units. The existing
mixed-hash explicit-instantiation warning remains.

## G2 — core CLI argument readers (2026-10-27)

Ordinary function metadata publishes cli_argc, cli_argv and cli_args with concrete
int64/mixed results. The native definitions moved unchanged into a focused private
header; exact-text comparison against the prior process adapter passed. The startup
setter remains private, and existing startup-before-execution ownership is unchanged.

Release and ASan/UBSan with leak detection pass. A separate native fixture initializes
context; the public-only caller observes it across translation units. The test covers
the initial empty context, program name, empty/spaced/long arguments, mutation of one
snapshot without affecting another or argv, source-byte changes affecting only later
queries, and retained snapshots after source lifetime end/context reset.

One hundred argc queries allocate zero times. For the four-argument fixture, native
and public snapshot creation both allocate **13 times**: the boundary adds no wrapper
allocation. This is measured parity, not a fixed allocation promise for all arguments.
All three public headers compile independently. Relocated sealed publication imports
all readers with zero generated units and runs a public-only linked caller. Metadata
checks pass in **1.41s**.

Single isolated clang++ C++23/O2 compile-only caller costs:
**0.0536s public / 2.3717s native**. Core archives were rebuilt once per configuration.
The private fixture initially lacked its C++23 feature setting; fixing its test target
required only fixture compilation/linking, not another core compilation. Existing
mixed-hash instantiation warnings remain. No concurrent context mutation contract was
added, so no new TSan claim is made for the startup setter.

## G3 — environment/shell Result composition (2026-10-27)

The runtime publishes result_or_false<string> plus getenv/shell_exec through ordinary
metadata and the existing inline Result transport. Native compatibility calls and
public calls share payload-factory providers; string bytes move directly into their
respective owned representations. No new generator branch or public callback exists.
Factories are borrowed for the synchronous call, so factory copy/move cannot throw
between opening a pipe and establishing its cleanup owner.

Release and ASan/UBSan with leak detection pass. Focused tests cover missing versus
present-empty environment values, independent long-value ownership after environment
replacement, embedded-NUL name rejection, shell bytes including NUL, successful empty
stdout despite nonzero exit status, and retained Result values. The shared capture
owner is tested with a null pipe (factory not invoked), a throwing payload factory
(pipe closed once), and a noncopyable/nonmovable factory. Null-pipe behavior is injected
at that private owner; the test does not rely on nondeterministic OS pipe exhaustion.
Shell command tests ran on Linux; no new cross-platform shell syntax claim is made.

Missing getenv results allocate zero times after input setup. Long successful values
allocate **1 native / 1 public** in this fixture. Public headers independently compile.
The final relocated sealed SDK reuses both APIs and the runtime-owned Result type with
zero generated units. Three metadata checks passed in **2.52s**. Five affected compiled
carrier regressions (scoped conditions, text ordering, text operations, coalesce and
ternary/Elvis) pass, including sealed reuse; their fixtures now honor the published
Result name instead of requesting an application duplicate.

Single isolated clang++ C++23/O2 caller compile:
**0.0528s public / 1.9529s native**. Core builds ran for initial publication and again
for the factory-reference lifetime fix; the native compatibility adapter's missing
brace was also caught and repaired by its standalone fixture. The existing mixed-hash
instantiation warning remains. Earlier carrier semantic decision gates are unchanged.

## G4 — process-memory observations (2026-10-27)

Ordinary metadata publishes current/peak process-memory queries with zero arguments
or a boolean compatibility argument. Concrete C++ names are process_memory_usage and
process_peak_memory_usage; source catalog names are unchanged. The different public
names avoid conflicting with native scpp zero-argument functions by return type alone.
Both paths call the same extracted private byte-observation provider; exact-text
comparison verifies that provider logic is unchanged.

Release and ASan/UBSan with leak detection pass. Tests exercise all overloads and
both bool values, permitting nonnegative observations including zero rather than
requiring a platform measurement or stable exact deltas. Actual unsupported-platform
fallback execution is not claimed by the Linux test. After warmup, native/public
allocation counts match: **3/3 per query in release**, **2/2 under sanitizers** for
both current and peak samples. These are observed implementation counts, not fixed
cross-platform guarantees or allocator-accounting semantics.

All four focused public headers compile independently. The relocated sealed SDK
imports the four functions with zero generated units and links/runs a public-only
caller. Two metadata checks pass in **1.39s**. Single isolated clang++ C++23/O2
compile-only caller costs: **0.0519s public / 0.8476s native**. The native comparison
also benefits from removing the broad PHP common header. Core was rebuilt once per
configuration; the existing mixed-hash instantiation warning remains.

## G5 — generic constant declarations and debug flags (2026-10-27)

The C++ generator now emits constant sets through the ordinary API output/export
path. The runtime publishes 19 concrete constexpr int64 debug flags in scpp::debug.
The focused check matches every value against the core catalog, exercises unrelated
boolean/int32 constants, rejects empty sets/duplicate members/bad names/empty
expressions/conflicting API identities, and verifies imported-record equality plus
zero-unit reuse. Native and concrete debug headers also compile together.

The release constant-set test passes in **0.34s**; three existing metadata/function
regressions pass in **1.73s**. The same new generation and error cases pass through
the ASan/UBSan generator with leak detection. The relocated sealed SDK imports
`debug_flags` with zero generated units and compiles/runs a public-only consumer.

A standalone public-only proof compiles all 19 flags plus two unrelated constants
using static_assert, compiles their declaration-check units, links and runs without
any runtime archive in **0.2452s** (single combined compile/link/run setup; timing
covers the compiler invocation, execution follows separately). Inline constexpr
initialization introduces no runtime boundary allocation or dynamic initialization.
No native runtime behavior changed. An initial aggregate include preceded generation
and failed; it was removed because constant sets need no archive definition. Tool
builds then completed normally; the existing mixed-hash warning remains. Debug-call
bindings and source-context forwarding are not claimed complete by this slice.

## G6 — scalar debug bindings (2026-10-27)

Two ordinary function-set recipes publish the eight registered scalar dbg signatures
as scpp::debug::dbg_at. All calls forward explicit source file/int32 line context;
labelled calls receive explicit flags. The existing formatter and native default
flags remain the authorities. No generator code or native formatting code changed.

Release and ASan/UBSan with leak detection pass. A public-only caller and native
fixture compare output byte-for-byte over **20 cases**: four scalar types, value-only
form and four labelled option combinations. Coverage includes source location, native
scalar type names, embedded NUL/newline escaping, default/explicit/compact flags and
multiple depth flags reported by the formatter rather than escaping as an exception.
After warmup, native/public allocation counts match in every case; no zero-allocation
claim is made for debug formatting or the test's stream capture.

All eight public headers compile independently. The relocated sealed SDK imports all
eight bindings and executes an integer debug call with zero regenerated units. Three
metadata/function/constant regressions pass in **1.82s**. Single isolated clang++
C++23/O2 caller compile costs: **0.0544s public / 1.9888s native**, for an integer
value-only and labelled-string call. Core archives were rebuilt once per configuration;
the existing mixed-hash instantiation warning remains. This closes the registered
scalar debug calls and G5 flags, not unregistered native object inspection or frontend
source-context lowering.

## G7: exact-mixed predicates and empty hash factory (2026-10-28)

The existing function-set mechanism publishes three exact-mixed predicates in
`scpp::predicates`; a root `scpp::is_bool` function collides with the native operator
concept and was caught by the initial release build. No generator branch or native
concept rename was introduced. Hash gains its registered static empty factory via
the existing static-method recipe.

Release evidence: public-only observations, 22-operation metadata admission/reuse
and metadata packaging tests pass (combined 0.73s). Repeated predicates cover null,
false, zero, integral float, negative zero, infinity, NaN, numeric text and owned
table, with zero allocations. Hash tests cover zero-allocation empty factories for
ordinary and move-only payloads, existing mutation/lifetime behavior and sealed
application-specialization reuse. Relocated runtime reuse emits no implementation
units and links a public-only predicate caller. Three headers compile independently.

Single isolated compile-only observation caller: 0.0564s public, 2.9348s native.
Application hash storage compilation was 10.3712s, public caller 0.1395s; these are
fixture timings, not a full compiler rebuild or a claimed optimization speedup.

Limits: weak/shared table states are not constructible through this bounded public
fixture and are not newly claimed as covered. The predicate delegates to unchanged
kind observers; source evaluation/eligibility remains compiler-owned. Empty hash
construction is not collection-initializer coverage.

ASan/UBSan with leak detection: public observations and metadata checks pass
(3.14s combined); generated application hash lifetime/factory tests and sealed
reuse pass. Sanitized fixture storage compilation: 18.3667s; caller: 0.2163s.
Core was rebuilt once successfully per configuration, after an initial release
compile diagnosed the namespace collision. No concurrency behavior changed.

## E6: runtime-checked mixed keys on boxed reads (2026-10-28)

Both public boxed families now accept mixed keys through their existing checked-read
provider. Release public mixed/dynamic regression tests, metadata packaging and the
new index-read proof pass. The focused test covers invalid null/bool/float/table
keys, including null receivers; valid integer/string keys; missing/null traversal;
scalar receiver rejection; no insertion; mixed value versus dynamic identity; and
result lifetime beyond source reset. Warmed repeated reads allocate zero times.

An initial test assumed PHP numeric-string normalization; the configured core uses
`SCPP_LANGUAGE_TARGET_PHP=OFF`. The test now selects its expectation from that build
option without exposing private headers to the caller. PHP-enabled execution is not
claimed by this run. Cold string-key interning can allocate and is excluded from the
warmed lookup measurement; it remains existing storage debt.

Relocated sealed-runtime reuse emits no new implementation units and links both new
read signatures. Single isolated compile-only two-family read caller: 0.0607s public
versus 1.8697s native. The core implementation compiled once for release; fixes to
the test's count API and profile expectation required only small caller rebuilds.

ASan/UBSan/LSan: the new public read test and existing mixed/dynamic regression
tests pass (three tests, 0.06s). Both public headers compile independently; generic
metadata generation/admission tests pass (1.20s). The sanitizer core compiled once.
No shared-state mutation or new synchronization was introduced.

## E7: staged mixed-key direct writes (2026-10-28)

The ordinary function emitter publishes a key-only validator, and both boxed family
recipes add mixed-key set overloads through existing native storage validation and
self-assignment handling. No generator branches, new source builtins or mutable
interior-reference API were added. Public caller staging remains explicit.

Six release checks pass (1.33s): metadata, packaging, mixed/dynamic regression and
public indexed read/write proofs. The write fixture checks invalid key suppression
of RHS effects, failed RHS without storage creation, valid-key receiver failure
after RHS effects, saved key independence, post-RHS local root replacement, same-
family ownership and mixed self-assignment. Repeated numeric replacement and key
validation allocate nothing. This is not a cold string-write allocation claim or
proof that the frontend emits the demonstrated staging.

Relocated sealed runtime reuse emits zero implementation units and links/runs both
new set signatures and the validator. All three changed public headers compile
independently. Single isolated compile-only staged-write caller: 0.0548s public /
2.1175s native. Core compiled once for release.

Validation: four boxed read/write and lifetime tests pass under ASan/UBSan with
leak detection (0.08s). Six release runtime/metadata checks pass (1.33s), independent
public header compilation and relocated sealed reuse pass. Repeated numeric writes
and key validation allocate zero times. Single-run public/native caller compilation:
0.0548s / 2.1175s. Core was rebuilt once per configuration. Broader nested/access
protocols and the carrier review gate remain open.

## E8: one-step boxed keyed probes and removal (2026-10-28)

Five metadata-generated support functions expose native readiness, leaf probing and
invalid-empty failure without interior pointers. Both boxed recipes add mixed-key
removal via the existing checked removal path. No source helper registration or
generator branch is introduced.

Seven focused release checks pass (1.38s), including existing boxed read/write
regressions and metadata/publication packaging. The new fixture distinguishes missing
from stored null for removal, false from null for isset, supported empty/long strings
from invalid empty leaves, and invalid probe keys from throwing removal keys. It
checks skipped first-key effects and fresh lookup after root replacement, COW versus
shared identity, and idempotent missing removal. Repeated probes (including long text)
and missing removal allocate nothing; cold string-key insertion/interning is not
measured by this fixture.

Five support headers compile independently. Relocated sealed runtime reuse emits
zero units and links both probe families and removal methods. Single isolated
compile-only probe caller: 0.0564s public versus 2.1155s native. Core rebuilt once
for release. This is a single-step runtime API proof, not nested prefix resolution
or source-frontend staging coverage; weak-table public construction remains absent.

Validation: seven release runtime/metadata tests pass (1.38s); five boxed access and
lifetime tests pass with ASan/UBSan and leak detection (0.11s). Independent headers
and sealed relocation/reuse pass. Single-run probe caller compilation: 0.0564s
public / 2.1155s native. One core rebuild per configuration. The overall completion
goal and carrier review gate remain open.

## E9: shared owning initializer backing (2026-10-28)

One new metadata recipe realizes the existing native initializer_buffer with public
element storage. It deletes default/copy/move, exposes construction/append and
complete-input count/owned at, and never publishes view(). Four demanded inputs
(string, int64, mixed, dynamic) use the same recipe. The runtime publication gains
the recipe identity but no permanent buffer demand or implementation unit.

Release and ASan/UBSan/LSan tests pass for inline zero-allocation small/empty inputs,
large heap-backed input, incomplete reads, negative/out-of-range reads, append past
capacity, failed long-string copy followed by successful retry, partial construction
unwind, owned values surviving buffer destruction and mixed COW/dynamic identity.
Application-owned sealed specialization reuse emits no new sources. The public-only
caller has no private runtime headers. Metadata checks reject move-only input and
verify the four nonmovable/noncopyable representations and absence of public views.

Focused timings: four storage units compiled in parallel (maximum eight workers)
in 1.0313s release / 1.0806s sanitized; public caller 0.1409s / 0.1721s. These are
fixture compile costs, not a public/native caller comparison. Three metadata checks
pass in 1.49s. No runtime-core rebuild was needed in either configuration.

The prior metadata experiment proved a signature/layout cycle for a mixed member
factory taking its own buffer and successful resolution of a separate function
binding. Placeholder bodies were used: no factory behavior is credited. A separate
syntax-only trait proof confirms native immutable initializer_entry is copy-
constructible but not copy-assignable. Current copy_storage/lifecycle bundles both;
keyed entry transport therefore needs an explicit local capability/lifecycle design,
not a false copy_storage declaration. Factory execution, keyed duplicates and source
view non-escape remain unfinished.

## E10: private positional factory adaptation (2026-10-28)

The initialization owner now shares populate_list_adapted across vector, typed hash,
mixed and dynamic factories. Existing from_list operations delegate with identity
adaptation. The view copies an adapter's result directly by value; borrowing a native
payload from a public representation therefore avoids an extra public-value copy.
No borrowed view/callback is published in the template-free public surface.

Release and ASan/UBSan/LSan tests pass across the four factories, including ordered
input, valid empty results, public boxed representation transport, thrown adaptation after a populated prefix and destruction of
tracked payloads. Long-string allocation parity is native 3 / adapted 3 in both
configurations. Earlier entry evaluation/conversion still belongs to the compiler;
adaptation must not become a late source checked cast.

Final focused executable build: 4.19s release / 8.35s sanitized, including link
(and sanitizer CMake regeneration). The release fixture first needed correct native
vector-index spelling and the existing public-key policy include; those were fixture
errors, not new runtime key semantics. No runtime-core rebuild occurred. Public
headers/metadata/publication are unchanged, so this slice does not claim new factory
publication or reuse coverage. Concrete factory bindings and keyed entry transport
remain next work.

## E11: concrete positional factories (2026-10-28)

Four ordinary family recipes expose static from_list scopes over E9 buffers and
the E10 native adapter. Mixed/dynamic factories and buffers are runtime-owned;
typed vector and integer-keyed hash factories are generated on application demand.
The scopes cannot be instantiated/copied/moved, so no per-call factory wrapper
object is allocated. Ordinary class/static-method generation suffices.

The public-only application fixture passes release and ASan/UBSan/LSan: all four
empty/nonempty constructions, ordered typed entries, independent parent containers,
nested mixed value/dynamic identity behavior, retained results beyond input lifetime,
incomplete-input rejection and injected partial string-copy failure. Empty typed
outputs allocate zero times; the long-string vector case allocates 3 times, matching
the native E10 proof. String-key hash list helpers reject; their helper default of
int64 does not alter the source hash default.

Runtime imports and application sealed reuse produce no duplicate implementation
units. All four factory headers compile independently. Four metadata checks pass
(1.61s); the older owning-buffer fixture also passes sanitized compilation/lifetime
and reuse with mixed/dynamic buffers imported from runtime. Its emitted-unit assertion
now subtracts imported identities instead of requiring four local definitions.

Parallel generated application compilation (maximum eight workers): 3.1937s release
/ 4.5106s sanitized; public caller: 0.1488s / 0.2064s. These are fixture timings, not
a native/public caller comparison. Core rebuilt once per configuration. Keyed
factory publication, immutable entry lifecycle, duplicate validation and semantic
non-escape/source integration remain unproven by this positional slice.

## E12: immutable initializer entries (2026-10-28)

A generic capability implication graph and independent copy/move assignment lifecycle
overrides allow native immutable initializer_entry records to retain their actual
representation. The buffer now requires copy construction rather than assignment.
The application-owned entry recipe exposes owned key/value reads, construction and
destruction, but no default construction or assignment. Existing lifecycle shorthand
and explicitly exported capability inventories remain unchanged.

Release and ASan/UBSan with leak detection pass the public-only entry/buffer proof:
immutable traits, string ownership, duplicate/order preservation, escaped owned reads,
failed-copy recovery and partial-input cleanup. Scalar entries and small buffers
allocate zero times. Four headers compile independently, and sealed application
reuse emits no duplicate implementations. Graph checks cover unknown/duplicate edges,
cycles, transitive and diamond paths; lifecycle checks cover independent and
conditional assignments. Assignment-only shape checks are emission evidence, not
a claim that the immutable native record is assignable.

Parallel generated storage compilation: 1.2039s release / 1.0997s sanitized; public
caller: 0.1506s / 0.1814s (single runs, maximum eight workers). Existing conditional,
vector, hash and sentinel lifecycle metadata regressions pass. Only the generator
needed rebuilding; existing runtime-core implementation output was unchanged.
Keyed factories and duplicate rejection remain next; source admission and non-escape
semantics are not inferred from physical storage capabilities.

## E13: private keyed adaptation (2026-10-28)

Typed hash, mixed and dynamic keyed factories share adapted immutable-entry population.
Existing native entry signatures delegate through identity adaptation; receiver key
normalization and presence-based duplicate checking remain authoritative. Public
boxed storage is transported without exposing a borrowed source view. Tests cover
typed/native compatibility, mixed/dynamic entry adaptation, null-payload duplicates,
and destruction after a partial-result adapter failure. No public keyed factory is
published by this slice; source admission, full key-policy matrices and sealed public
reuse remain separate proof obligations.

Validation: focused release and ASan/UBSan/LSan runs pass. Existing positional
long-string allocation parity remains 3 native / 3 adapted; this is not a keyed
allocation measurement. The final release build took 5.53s with core compilation
already completed during the initial attempt; sanitized build took 46.37s including
core recompilation. These are build observations, not comparable isolated caller
costs. The initial compile caught an accidental shared-wrapper edit, corrected before
both successful test runs. Public keyed compile/allocation costs remain next.

## E14: public keyed factories (2026-10-28)

Ordinary metadata recipes publish mixed/dynamic keyed factories and their required
immutable entry/buffer compositions. Typed string/integer hash factories are generated
and reused by applications. Public-only tests cover empty results, incomplete input,
duplicate rejection including null/false payloads, invalid boxed key kinds, owned
results surviving input lifetime, mixed value/dynamic identity, and independent dynamic
parents. Long-string typed hash allocation parity is 9 native / 9 public; each measured
allocation failure preserves the existing destination. Empty typed factories allocate
zero times. Factory headers compile independently and sealed reuse emits no new units.

Key-policy execution here is explicitly non-PHP: integer and numeric-string keys stay
distinct. The runner verifies the core CMake configuration. PHP-mode normalization,
source factory dispatch and source non-escape admission are not claimed. The synthetic
entry-assignment shape test omits imports that contradict its deliberately altered
recipe; production import validation is unchanged.

Validation: release and ASan/UBSan/LSan public-only runtime tests pass, as do independent
factory-header compilation and sealed runtime/application reuse with no duplicate
implementation units. Six focused metadata checks pass (the entry shape regression
was rerun after import isolation). Parallel application storage compilation took
4.6994s release / 6.0052s sanitized; public caller 0.1825s / 0.2841s. These are single-run
fixture timings. Runtime-core rebuilt once per configuration; generator code did not
change. The runtime now publishes 383 implementation units; this is not a coverage
percentage.

## E15: keyed factory target-policy proof (2026-10-28)

The keyed fixture now reads SCPP_LANGUAGE_TARGET_PHP from the supplied core's CMake
cache and uses matching application/probe/reference flags. For PHP mode it generates
a temporary runtime SDK publication through the ordinary metadata tool before sealing;
it does not change the ABI field by hand. Generated implementation bytes must match
the files compiled into the supplied core. The first attempt with the non-PHP
publication was correctly rejected as an incompatible dependency target ABI.

The public proof checks numeric-string/integer duplicate rejection in PHP mode,
distinct keys in non-PHP mode, leading-zero string preservation in both, typed entry
iteration order and incomplete boxed input rejection. Dedicated PHP-enabled release
and sanitizer builds live under compiler/my-try-cpp/build/php-key-policy; the normal
compiler dependency configuration remains unchanged. Invoke check_keyed_initializers.py
with --tool and --core for the matching build, adding --sanitize for its sanitized
core. The runner rejects missing or unrecognized key-policy configuration.

Validation: non-PHP release and PHP release/ASan/UBSan/LSan public execution and sealed
reuse pass. Allocation parity remains 9 native / 9 public, with allocation-failure
cleanup checked. PHP generated application compilation took 4.8618s release / 6.3038s
sanitized; caller 0.2121s / 0.3584s. Non-PHP release rerun: 4.8334s / 0.2159s. These
single-run fixture timings exclude runtime generation and core builds. Each new PHP
core configuration built once; no runtime implementation or generator changes were
needed. Existing carrier logical-policy and prepared-fact ownership decisions remain
pending; this proof does not advance ownership/callback implementation past that gate.

## E16: private saved-key prefix resolution (2026-10-28)

The existing keyed-path owner resolves homogeneous boxed prefixes from the current
bound root using already-evaluated keys. Boolean readiness and final probe helpers
keep interior pointers call-local. The native fixture checks root replacement,
prefix removal between stages, no later saved-key access after failure, null versus
false payload presence, empty errors and zero-length path rejection. Internal key
adapters are representation access, not source callbacks. No new public pointer,
cursor, container or factory family is introduced. Heterogeneous paths, field-owner
retention, mutation commits and frontend source effects remain outside this proof.

Validation: focused release and ASan/UBSan with leak detection pass. Numeric saved-key
observations allocate zero times after setup. Build including runtime-core dependency
recompilation: 23.30s release / 47.64s sanitized, one build per configuration. These
are not isolated public caller measurements; publication and its compile/reuse proof
remain the next slice. No generator changes or source semantic changes were needed.

## E17: public saved-key path observations (2026-10-28)

Ordinary function sets publish mixed/dynamic prefix readiness and complete probes,
with runtime-owned vector<mixed> keys. No generator change was required. The public
fixture stages reached source effects and root binding, removes prefixes, replaces
a dynamic child through an alias, skips absent-root keys, and retains owned saved
keys. Repeated numeric-key observations of long-string leaves allocate zero times
with setup excluded. Null/false/empty behavior and invalid-key/receiver errors are
checked. The public boundary returns only booleans; interior references stay native
and call-local. Typed heterogeneous paths, field retention and nested mutation remain
separate work, and this manually staged proof is not frontend adoption.

Validation: release and ASan/UBSan/LSan public execution and relocated sealed reuse
pass, with zero new implementation units on import. Four public headers compile
independently; six metadata regressions pass (2.33s). Single-run nested-probe caller
compilation: 0.0559s public / 2.2876s native; direct-probe control 0.0558s / 2.1471s.
Core rebuilt once per configuration. The runtime publishes 388 implementation units;
that count is not a completion percentage. Existing carrier decisions remain pending.

## E18: public homogeneous boxed removal (2026-10-28)

The existing keyed owner rechecks the complete saved path without mutation before
COW descent and erasure. Ordinary metadata publishes mixed/dynamic overloads using
the existing vector<mixed>. Public-only tests establish no-op allocation behavior
with shared storage, null-valued slot removal, mixed copy independence, dynamic alias
visibility, remaining key identity and empty-parent retention. Invalid runtime keys
and existing scalar intermediates throw; missing/null intermediates return. Staged
root mutation is observed on the final call. Field-owner retention, heterogeneous
typed paths and frontend evaluation are not claimed by this proof. Allocation failure
during actual mutation retains the existing non-transactional contract.

Validation: release and ASan/UBSan/LSan removal and existing nested-probe tests pass;
relocated sealed publication reuses both APIs with zero new units. Both removal
headers compile independently. Single-run caller cost: 0.0541s public / 2.5304s native.
Core rebuilt once per configuration. Missing paths, missing leaves and null roots
allocate zero times in the focused proof. Runtime inventory: 390 implementation
units, not a coverage percentage. Carrier decisions and ownership/callback review
remain pending.

## E19: public homogeneous boxed store/append (2026-10-28)

A common index-write-path helper validates the complete current path, then commits
using existing native owners. Metadata publishes store and final append for mixed
and dynamic over saved vector<mixed> keys. The public-only proof covers three levels,
mixed bootstrap, dynamic existing-child requirements, validation without partial
creation, COW versus shared identity, next-key append, staged key/RHS effects and
root-self aliasing. Mixed self RHS retains its pre-commit value; dynamic keeps identity,
and the test removes its intentional self edge before leak checking. Allocation/copy
failure during mutation remains non-transactional. Typed heterogeneous paths, field
retention, compound writeback and full source lowering remain open.

Validation: release and ASan/UBSan/LSan nested store/append, probe and removal tests
pass (three runtime checks per configuration). Relocated sealed import emits no new
units and links all four new APIs. Four headers compile independently. Repeated
numeric replacement allocates zero times after setup. Single-run four-operation
caller compilation: 0.0699s public / 2.6039s native. Core rebuilt once per configuration;
no generator changes were needed. Runtime inventory is 394 implementation units,
not a completion percentage.

## E20: owned existing-leaf snapshots and checked writeback (2026-10-28)

The native existing-index owner supplies saved-key adapters; ordinary metadata
publishes read/store for mixed and dynamic. Read returns an owned value. Writeback
rechecks every segment without mutation and shares the ordinary write's root-self
snapshot commit helper. Tests stage old-leaf arithmetic across RHS mutation, reject
deleted/wrong-kind destinations without recreation, preserve mixed copies/dynamic
identity, retain long-string snapshots after root reset, distinguish existing null
from missing, and exercise root-self RHS and invalid paths. This is the runtime
transport for compound lowering, not a new compound operator policy or proof of
frontend/field-owner/heterogeneous-path integration.

Validation: release and ASan/UBSan/LSan existing-only and ordinary-write regression
tests pass; relocated sealed reuse links all new APIs without new implementation
units. Four headers compile independently. Numeric read/writeback allocation count
is zero after setup. Single-run existing-only caller compile: 0.0570s public /
2.5540s native; ordinary-write control: 0.0640s / 2.5909s. Core rebuilt once per
configuration. Runtime inventory: 398 implementation units, not a coverage ratio.

## E21: eliminate temporary boxed keys during strict identity (2026-10-28)

Packed-table const entry views now compare validated canonical stored key codes.
The process-wide string pool guarantees a stable tagged identity for complete byte
strings; strict identity still compares entries in order and recursively compares
values. No key bytes/reference are exposed to public callers. Existing key extraction
shares the checked physical-key accessor, including packed bounds validation.

The public proof now requires zero allocations for short, long, embedded-NUL and
nested string-key comparisons, different key bytes/order, and packed versus associative
storage with tombstones. Earlier B1c's measured two allocations for one short-string
key are eliminated. Scalar/boxed pair, NaN and dynamic identity behavior remain tested.
Key normalization/ranges, pool concurrency and cycle behavior are unchanged.

Validation: release and ASan/UBSan/LSan strict-identity and heterogeneous-pair tests
pass, including zero allocations for the table-key cases. Sealed relocated publication
continues to reuse the unchanged APIs with no new implementation units. Single-run
boxed identity caller compile: 0.0566s public / 2.9023s native. Core rebuilt once per
configuration. No metadata generation or public ABI change was required.

## G8: focused native error definitions (2026-10-28)

ValueError/TypeError definitions moved to a private focused core header; the old
string-helper owner retains a compatibility include. Checked shift and process-string
helpers use the focused header directly. Native names, inheritance, construction and
messages are unchanged, and the public runtime_error-only capture contract is intact.
This is dependency/ownership preparation for G inspection, not foreign-exception API
publication or source Exception support.

Validation: four focused release and ASan/UBSan/LSan checks pass (native error types,
public structured errors, special integers and process strings). A final cross-library
shift throw is caught as the same native ValueError in release and sanitizer runs.
Existing error/process allocation checks remain green; no new capture wrapper is
allocated or published. Single-run error-caller compile: 0.4435s focused header /
1.2257s former aggregate include. Dependency inspection confirms shift validation
includes neither core/string_support.hpp nor scpp/int_t.hpp. Core rebuilt once per
configuration; the final cross-library proof rebuilt only its small test caller.

## B3 — same-family boxed ordering (2026-10-28)

The global ordering group now publishes `<`, `<=`, `>` and `>=` for
mixed/mixed and dynamic/dynamic through the ordinary operator recipes. A private
relational adapter keeps existing scalar comparisons and delegates boxed operands
to native numeric-only ordering, preserving the original diagnostic operator.
No generator type-name branches or operand-pair expansion were added.

Non-goals: cross-family boxes, boxed/plain operands, carrier boxed payloads and
spaceship admission are unchanged. This closes the same-family ordering gap only;
it does not complete all remaining boxed or carrier contracts.

Evidence: `check_boxed_ordering_api.py` verifies eight generated APIs, published
reuse and rejected operand/carrier domains. `test_public_boxed_ordering.cpp`
passes in release and ASan/UBSan with leak detection: all int/float combinations,
canonical int64/float64 rounding, signed zero, infinity, NaN, rejection of stored
null/bool/string/table, unchanged operands and zero allocations on successful
numeric comparisons. Existing numeric and boxed arithmetic regressions also pass
in both configurations. Eight standalone public headers compile independently.
The sealed relocated publication test links and executes all eight APIs in release
and sanitizers with zero application-generated units.

Single-run caller compile cost (`measure_boxed_ordering_api.py`, clang++ C++23 -O2):
public **0.0543s**, native **3.1721s**. These are caller translation-unit timings,
not full runtime rebuild times. Core compiled once per configuration; a test-only
vexing-parse correction required a caller retry, not another core compilation.

## C32 — guarded canonical logical negation (2026-10-28)

The user confirmed existing guarded bool/numeric logical semantics and compiler-owned
source eligibility/formation/boundary validation with prepared facts consumed by the
API generator. No shared semantic-model extraction is authorized or implemented.

Logical negation now combines metadata-selected `carrier_unary` payload domains
(boolean/int64/float64) with the existing condition owner. Matching carrier payloads
use checked one-layer extraction and the ordinary scalar result expression; absent,
error and sentinel states throw. Present false/zero produces true. Direct condition
observation remains separate. The shared unary consumer test now honors calculation
result expressions, rather than assuming every operation is a bare function call.
No new runtime adapter or public template is needed; the publication manifest is
regenerated. Non-numeric/nested negation admission and lazy and/or are still review
work, not implicitly approved by preserving their pre-existing implementation.

Validation: metadata projection/unary/sentinel checks pass. The compiled unary proof
passes release and ASan/UBSan with leak detection: 64 plans, 76 successful value/
snapshot cases and 80 unavailable-state cases across all four families; successful
operations allocate zero and failure states are preserved. Both builds prove sealed
application specialization reuse. Broader projection and sentinel execution/reuse
fixtures pass release, keeping direct conditions, bool casts and logical negation
distinct. The projection fixture's newly throwing case was moved outside its
success-only allocation interval after that assertion correctly detected exception
allocation; the zero-allocation assertion was not weakened.

Single-run application storage/caller compile measurements: unary release
16.5505s/1.1221s; unary sanitizer 21.2525s/2.2131s; sentinel release
12.4949s/0.3023s; projection release 5.2790s/0.3122s. Some runs overlapped and these
are workload timings, not controlled before/after comparisons. Runtime archives
were reused without recompilation. Generator-only rebuilds covered the policy
change and a final duplicate-projection cleanup; metadata checks passed afterward.

Remaining C work: lazy logical execution plans, explicit non-numeric admission,
prepared-demand validation and broader admitted selection joins before carrier
sign-off and ownership/callback expansion.

## C33 — lazy canonical logical plans (2026-10-28)

The existing selection owner now resolves `logical_and`/`logical_or` through
metadata-defined operand groups and a short-circuit boolean. Both operand types
validate before a plan is returned. Each executed operand uses the ordinary
checked carrier projection followed by its scalar condition call. The RHS source,
projection and observation occur only after the left condition requires them.
The result is plain boolean; source evaluation remains compiler-owned. No eager
binary adapter, public callback template or family-specific generator switch was
introduced. Direct conditions retain their separate semantics.

The policy admits canonical bool/bool and numeric/numeric pairs, including int64/
float64, across plain values and all four carriers. It rejects bool/numeric mixing.
Boxed, string, fixed-width and nested-carrier logical admission remain explicit
review work; this slice is not a claim that all condition types support logical ops.

Evidence: `check_carrier_logical.py` checks all 450 operation/type pair requests
(250 admitted, 200 rejected), invalid RHS types and malformed metadata. Its public
execution fixture derives 36 consumers from plans, covering 132 success cases and
54 failure/skip groups: truth tables, mixed numeric and cross-carrier/plain pairs,
skipped throwing RHS effects, skipped unavailable RHS, required RHS failure and
left failure before RHS. Successful paths allocate zero. Release and ASan/UBSan
with leak detection pass; both prove sealed application specialization reuse with
no regenerated units. Four existing carrier/boxed coalescing and conditional
metadata regressions pass in 11.78s; these are metadata-only regression checks.

Single-run application storage/caller compile: release 13.9585s/1.1164s; sanitizer
19.3550s/2.3235s. Runs overlapped, so these are workload observations rather than
controlled comparative measurements. The runtime archives were reused; only the
C++ API generator and application proof units were compiled. Runtime publication
metadata was regenerated through the existing tool.

Next: audit non-numeric logical admission and implement prepared-demand consumption
under the accepted compiler-owned source-validation boundary. Carrier sign-off,
ownership and callback expansion remain incomplete.

## C34 — non-numeric audit and boxed logical paths (2026-10-28)

Read-only inspection of the existing semantic owners confirms boxed AND/OR admit
the same boxed family or plain bool/int/float peers. Cross-boxed families and
boxed/carrier combinations reject. Evidence: `Boxed_Operators::decide_logical` and
`Guarded_Operators::try_decide`; runtime condition semantics remain owned by
`operators/conditional/condition_truthiness.hpp`, under `dynamic_types.md` and the
conditional matrix. Historical compiler execution reports were not rerun here.

The shared selection metadata now has optional `plain_operand_groups`, used only
when both projections are identity. This expresses the boxed groups without
accidentally granting boxed/carrier combinations or adding generator type-name
exceptions. Existing canonical checked carrier groups are unchanged. Public
consumers reuse the existing condition functions; no runtime adapter or archive
recompile is required.

`check_boxed_logical.py` derives 28 public-only consumers from returned plans.
Release and ASan/UBSan with leak detection pass: boxed truth tables across null,
bool, int, float and string; all plain peer directions; skipped/reached invalid
mixed tables; and dynamic object truth (including an empty constructed object).
Successful paths, including long strings, allocate zero. Caller compile single
runs: 0.2195s release / 0.3635s sanitizer; these include the fixture and are not
public/native comparisons. Canonical logical metadata regression passes, with
added cross-boxed and boxed/carrier rejection checks. Publication metadata is
regenerated; compiled consumer checks use public headers and the existing archive.

The audit also distinguishes unary domains: nullable-string negation is an existing
condition route; guarded Result/string negation is unresolved in the old compiler's
string slice. Ordinary unary preparation admits explicit condition-negation roles
(records, ownership, callable, nullable, boxed and error), rather than every type
with a condition helper. The physical generator's broad fallback must not be used
as evidence of source admission. Prepared semantic facts must carry that operation
permission; narrowing or broadening source semantics is not inferred here.

Next work remains the prepared-demand consumer and its source/physical distinction,
then the remaining selection/ownership/callback review. Non-numeric logical source
admission is not marked universally complete by this boxed slice.
