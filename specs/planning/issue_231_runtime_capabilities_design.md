# Issue 231: small runtime capabilities for compiler migration

Doc Status: planning
Status: collection/STAN, managed-process and file-lock runtime slices implemented locally; portable PHP integration and migration proofs remain outstanding.
Revision: runtime alias signature ownership repair after downstream facade integration.
Baseline inspected: `main`, `8cc4d8ff`, tag `v0.1.76`.
Issue: <https://github.com/alexstanciu-1/simplecpp/issues/231>

## File-lock implementation slice

Following user approval to continue, the lock contract is frozen in
`specs/builtins/filesystem/file_locks.md` and its three per-builtin entries.
Those normative documents supersede the proposed lock text below. Implementation
uses a dedicated filesystem token, strict registrations and metadata-derived
shallow signatures. Existing monolithic filesystem composition is retained;
there is no new PHS module-exclusion guarantee.

Proof commands for this slice:

- `ctest --test-dir /tmp/scpp-file-lock-build -R scpp_test_file_locks --output-on-failure`
- `python3 tests/tools/test_scpp_file_locks.py`
- `php tests/tools/test_scpp_strict_runtime_catalog.php`

Native tests exercise independent native/PHP contenders, shared readers,
ownership transfer, alias invalidation, inherited descriptor cleanup, parent
unlock before child exec, close-on-exec, stable identity, nontruncation and
descriptor counts across repeated acquisition/contention/error cycles.
The strict project fixture proves real build/run and rejects a string output slot.
Validation passed on Linux: the CTest lock case, strict project test and runtime
catalog test. A standalone build of the lock source and native test with
`-fsanitize=address,undefined` also passed, including LeakSanitizer outside the
tracing sandbox. That standalone link exercises the OS-free public header and
lock implementation without any language adapter or full runtime library.
Portable PHP framework bindings/converter integration in the adopted compiler
checkout remain outstanding; raw PHP flock interoperability is not that integration.

## Collection implementation slice

The user approved the cross-owner prerequisite repair after the initial probe
showed erased closure signatures and argument-free call-result summaries.
`specs/builtins/collections.md` now freezes the implemented contract and supersedes
the collection proposals below.

- Frontend summaries retain authored callable signatures and function-call
  arguments. Annotation lookup is restored from the owning file when summarizing.
- `StanRuntimeCallResolver` owns bounded metadata-driven instantiation and shared
  collection type policy. Map results, callback validation, nested expressions,
  return/argument boundaries and foreach value/key typing use that information.
  Concrete existing calls keep their fixed signatures. The fixed-return catalog
  reports no fixed type for polymorphic helpers; erased shallow stubs are labeled.
- `foreach.hpp` provides const hash/mixed and dynamic adapters. `collections.hpp`
  implements one map loop and one filter loop with result construction policies.
  No helper-name inference branches or callable conversions were added to S2S.
- Source `dynamic<T,K>` remains unsupported; typed dynamic is proven natively.
  Declare plain dynamic output locals explicitly for subsequent `[]` lowering.
  Callback purity/reference-capture restrictions remain application discipline.
  Boxing follows existing mixed constructors, not a new general conversion system.

Validation commands:

- `php tests/tools/test_scpp_collection_typing.php`
- `php tests/tools/test_scpp_strict_runtime_catalog.php`
- `php tests/tools/test_scpp_stan_strict_discipline.php`
- `php tests/tools/test_scpp_project_module_stan_summary_cache.php`
- `python3 tests/tools/test_scpp_collections.py`
- `ctest --test-dir /tmp/scpp-file-lock-build -R 'scpp_test_(collections|closure|array_semantics|hash_t)$' --output-on-failure`

The strict project proves every supported source carrier, canonical by-value
capture syntax, key distinction/order, dense sequence output, dynamic identity
separation and build-gate rejection. Semantic tests additionally prove stored and
inferred callable locals, nested map/filter, typed keys, nested element types,
return and element boundaries, wrapper rejection and annotation ownership across
file extractions. Native tests cover const iteration, typed dynamic, empty and
invalid inputs, input preservation, ordinary nested sharing, exception
propagation and partial-output shared-owner cleanup.

An ASan/UBSan build of the collection test/template code passed; it links the
existing uninstrumented Debug runtime archive. Leak detection was disabled for
that sandboxed run. This is not a claim that the whole runtime was instrumented.

### Focused timing evidence

Probe: `tests/runtime/native/bench_collections.cpp`, GCC 13.3.0 on Linux, caller
compiled with `-std=c++23 -O3 -DNDEBUG`, linked against the same existing Debug
runtime archive for every path. Median of seven samples, twelve traversals per
sample; 100,000 vector elements or 10,000 hash entries. Measurements include fresh
result allocation and checksum traversal. Stored callables are constructed before
timing. Native template inline callbacks are measured separately from the
source callable-local `std::function` path.

| Operation | Direct loop | Inline callback | Stored callback |
| --- | ---: | ---: | ---: |
| Vector map | 1.15 ns/element | 1.42 | 2.43 |
| Vector filter | 1.23 ns/element | 1.49 | 2.62 |
| Integer-key hash map | 71.88 ns/element | 70.90 | 74.64 |

These are local focused comparisons, not whole-compiler speed claims or portable
performance thresholds. Hash result construction dominates this probe; stored
callable overhead is visible for the inexpensive vector operation. The helpers
introduce no additional type erasure. The benchmark is an opt-in CMake target,
`bench_collections`, and does not impose timing assertions on tests.

Portable PHP collection/process/lock counterparts, converter integration,
migration fixtures, commits and publication remain separate outstanding work.

## Managed-process implementation slice

`specs/builtins/process.md` freezes the implemented runtime contract and supersedes
section 4's proposals. The implementation is an optional `process` module in
project-local and shared module composition, and `scpp_process` in native CMake.

Native owner: `runtime/include/modules/process/process.cpp`, with an OS-free
public header. Private unlinked regular files hold all three streams. Fork/exec
uses a close-on-exec errno-reporting pipe; parent/child signal setup is explicit,
and the child performs no C++ allocation or destruction. Poll uses monotonic
elapsed time and waitid with WNOWAIT/WNOHANG. The group is signalled while the
leader is still waitable, including a final signal after an earlier stop/timeout,
then the leader is reaped. Lost wait ownership disables further PID signalling.

Public names are `process_start`, `process_poll`, `process_result`, `process_stop`
and `process_close`. Output fields are `stdout_text`, `stderr_text`, `exit_code`,
`signal`, `timed_out`, `stopped`; the text suffix avoids C stdio macro collisions.
Normalized metadata generates the strict signatures and output properties.

Concrete scope refinements: absolute executable/cwd paths; default host SIGCHLD
with no competing child reaper; default child signal dispositions and empty mask;
signalable children remaining in their group. Completion means the leader is
reaped and remaining group members have been sent SIGKILL, not that arbitrary
grandchildren are reaped. First result collection snapshots each file's size and
caches those bytes; it cannot promise quiescence for descendants stuck inside
uninterruptible OS operations. Poll deadlines remain caller-driven.

Proof commands:

- `cmake -S runtime -B /tmp/scpp-file-lock-build -DSCPP_WITH_PROCESS=ON`
- `cmake --build /tmp/scpp-file-lock-build --target test_process -j4`
- `ctest --test-dir /tmp/scpp-file-lock-build -R scpp_test_process --output-on-failure`
- `python3 tests/tools/test_scpp_process.py`
- `php tests/tools/test_scpp_strict_runtime_catalog.php`

Native proofs cover binary stdin, separate output, nonzero/127 status, explicit
exec/setup failures, invalid paths/arguments/timeouts, nonblocking polling,
timeout versus stop, large output before reading stdin, cwd and literal argv,
descendants after normal leader exit and timeout/stop/close/destruction,
available exit status taking precedence over a late poll, alias invalidation, independent cached
snapshots, inherited handles, closed host stdio, destructor/repeated cleanup,
SIGCHLD rejection, child SIGPIPE reset, descriptor counts and direct-child reaping.
Strict PHS proves build/run, typed handle/output exposure, bad handle rejection,
and actual link exclusion with the process module disabled (using rebuilt runtime
artifacts in both configurations).

ASan/UBSan passed with the new process source and native test instrumented, linked
against the existing uninstrumented Debug core archive; leak detection was
disabled for the sandboxed run.

Test target remains the uncommitted working tree based on `8cc4d8ff`, not a new
released compiler target. The adopted portability framework was inspected again:
the current host PHP is 8.5.7 with pcntl_waitid available, but framework wrappers/mappings have not been
changed in this runtime slice. PHP framework counterparts and the affected
portability/migration proofs still prevent closing #231.

## Constrained adapter follow-up

The downstream update identified a gap in the earlier completion audit: the
explicit portable operation policy below was documented but its native adapters
were absent from candidate `394164c0`. This follow-up implements `sequence_map`,
`sequence_filter`, `keyed_map` and `keyed_filter` on the same runtime line.

Native result policies identify sequence/keyed/dynamic carriers. Constrained
entry points delegate to the existing algorithms. Normalized call contracts add
`accepted_carrier_families`; STAN checks those before reusing callback and result
typing. Sequence rejects every hash, including dense integer-keyed hashes. Keyed
accepts hashes and table-valued mixed and rejects sequences and dynamic handles.
Generic helper behavior and dynamic scope are retained.

Integration tests also exposed two existing mismatches, repaired in their owners:

- Native callback checking now accepts the existing `const T&` lowering of source
  value parameters, in addition to exact `T`. The callback receives the same
  copied entry value; source references and native mutable references stay rejected.
- STAN indexed-expression typing reuses the carrier model instead of parsing hash
  parameters backwards or splitting nested generic arguments at the wrong comma.

Validation for the follow-up:

- `ctest --test-dir /tmp/scpp-file-lock-build -R 'scpp_test_(collection_adapters|collections)$' --output-on-failure`
- `python3 tests/tools/test_scpp_collections.py`
- `php tests/tools/test_scpp_collection_typing.php`
- `php tests/tools/test_scpp_strict_runtime_catalog.php`
- `php tests/tools/test_scpp_stan_strict_discipline.php`

Coverage includes type-changing maps, empty/all/none results, dense sequences,
key/order retention, carrier rejection, read-only string/container callbacks,
nested calls and typed argument/return/element boundaries. The PHP framework,
converter bindings and downstream parity/migration proofs remain owned by v0.2;
this follow-up supplies the previously missing constrained native surface.

## Runtime alias signature follow-up

Downstream [reported successful converted collection and cumulative compiler
proofs on `08c8206a`](https://github.com/alexstanciu-1/simplecpp/issues/231#issuecomment-5764637852)
and selected that candidate for portability work. The same integration exposed
conflicting generated class forward declarations for `file_lock_handle`,
`process_handle` and `process_output` in facade signatures.

A standalone multi-file strict fixture reproduced all three alias conflicts.
The fix moves the existing atomic runtime-declaration ownership list from the
header emitter into `TypeMapper` and registers the three aliases there. Header
emission consults that owner after traversing container/wrapper arguments. This
classification does not change shared-handle representation or ordinary user
class forward declarations, and never matches qualified user names by basename.

Validation for this follow-up:

- `php tests/tools/test_scpp_runtime_type_declarations.php`: all three aliases,
  leading backslashes, shared and by-reference representation, result/vector
  signatures, retained user-class forwards and qualified-name discrimination.
- `python3 tests/tools/test_scpp_runtime_alias_signatures.py`: real multi-file strict
  build/run for parameters, returns, by-reference lock output, methods and fields;
  lock contention/reacquisition, process output and shared-handle invalidation.
- `python3 tests/tools/test_scpp_file_locks.py` and
  `python3 tests/tools/test_scpp_process.py`: existing local-slot regressions.
- `php tests/tools/test_scpp_strict_runtime_catalog.php`: existing runtime exports.

Downstream PHP backend tests are reported as passing, but process/lock native
facade parity was blocked by these declarations. This fix supplies another native
candidate; it does not claim that downstream parity or migration proofs are done.
No additional process/lock behavior, constructor or module scope is introduced.

## 1. Original design scope (implementation updates above take precedence)

| Capability | Proposed first contract | Deliberately excluded |
| --- | --- | --- |
| Map/filter | Shared foreach protocol over vectors, fixed arrays, hashes, table-valued mixed and dynamic; typed synchronous callbacks | Arbitrary user iterators, key callbacks, callback storage, general purity analysis |
| Processes | Linux command execution with explicit argv, supplied stdin, captured stdout/stderr, polling, timeout, owned group cleanup | Live streaming, interactive sessions, scheduler, detached jobs, Windows implementation |
| Locks | Linux local-filesystem advisory locks, nonblocking exclusive/shared acquisition, explicit release and transfer | Lock upgrades, distributed/network-filesystem guarantees, raw descriptors |

The user accepted continuing the shared-foreach design, including hash, mixed
and dynamic inputs. Runtime names and contracts are now frozen in the normative
family documents linked above. PHP backend requirements remain proposals.
Linux-first, file-backed capture, caller-driven deadlines, and PHP 8.4+ with
PCNTL/POSIX are the recommended first backend scope.
The runtime collection slice above is implemented following explicit user approval.
Broad prototype callback rewrites remain outside this batch slice.

Non-goal: general PHP compatibility, converter type inference, compiler algorithm
changes, a new ownership type system, or a broad runtime/compiler refactor.

## 2. Baseline owners and reusable pieces (before these slices)

- `runtime/include/scpp/vector_t.hpp`: typed sequence storage, reserve and append.
- `generators/php/specs/rules.md`: typed callable locals, lambda lowering, value
  and reference captures. Typed callable storage can use `std::function`.
- `runtime/include/lang/php/php_process.hpp`: `shell_exec` uses `popen`, reads
  stdout synchronously, and closes through RAII. It does not expose child status,
  separate stderr, polling, timeout, or process-group ownership.
- `runtime/include/core/resource.hpp` and `core/stdio.hpp`: existing file stream
  handles and IO. A shared resource handle already aliases one closeable state.
- `runtime/include/modules/filesystem/`: filesystem operation owner. Current
  strict filesystem API has no cross-process locking contract.
- `runtime/include/modules/datetime/`: reuse monotonic clock facilities.
- `runtime/include/modules/curl/curl.hpp`: precedent for runtime-owned opaque
  handle types and `result<...>` operations exposed through strict metadata.
- `bin/project_services.php`: PHP host tooling already uses `proc_open` and
  `flock`. Those calls are not compiled-program runtime capabilities. The STAN
  worker opens a lock without truncation and unlocks explicitly, but its failure
  handling does not establish the requested contention-versus-error API.

Caller sources inspected outside this checkout:

- Reference prototype: `/home/alexv/__AI/scpp_compiler_3/prototype`.
- Current adopted compiler: `/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler`.
  Its README declares this the development home; the original is reference only.
  Its portability target is also `v0.1.76` / `8cc4d8ff`.
- `tool_process/process.php`: already uses three temporary files, a `setsid`
  launcher, monotonic deadlines, cached exit status and explicit group cleanup.
  This file matches the reference prototype. Reuse that operating model; improve
  launch-error distinction, status/cleanup ordering and typed exposure.
- `src/compile/lock.php`: nonblocking exclusive acquisition, `ce` open mode,
  persistent sibling lock path, explicit unlock, owner/borrower discipline.
- `src-runtime-preparation/reservation.php`: exclusive package lock transferred
  once to the staged candidate. Actual transfer is required, not speculative.
- `src/01_prepare_inputs/load_runtime/package_adapter.php`: nonblocking shared
  reader. `src-runtime-preparation/request_adapter.php`: blocking shared reader.
  Shared mode is necessary; preserve the latter's waiting behavior in a framework
  retry loop over the nonblocking primitive.

The existing Tool_Process API inherits cwd/environment and receives timeout
seconds (default 10; runtime preparation uses 60). Framework conversion must
make the proposed milliseconds explicit. Its exception policy belongs to the
compiler adapter; the runtime should retain nonzero status and stderr as data.

## 3. Map and filter

Proposed strict names: `collection_map(input, callback)` and
`collection_filter(input, predicate)`. Names remain reviewable. One family should
cover supported foreach carriers rather than separate vector/hash algorithms.

There is already a runtime iteration protocol in `runtime/include/scpp/foreach.hpp`:
`foreach_range(container)` returns a range of entries exposing `key()` and
`value_copy()` (plus `value_ref()` for mutable iteration). Generated foreach
statements use this path. It is an adapter/template protocol, not a source-level
`Iterable` interface or a universal promise for every C++ range.

Current gaps: const vector/fixed-array adapters exist, but const hash/mixed
adapters and a direct dynamic-handle adapter do not. Dynamic is a shared hash
handle (`scpp/dynamic_t.hpp`), not a separate sequence representation. Complete
the read-only adapter path locally in this owner; avoid const_cast or converting
typed inputs to mixed. Audit source foreach/STAN handling of dynamic alongside
the runtime adapter rather than assuming that an overload proves frontend support.

Iteration tells us how to read; a small separate output policy must tell us how
to build a fresh result. Recommended output contract:

| Input | Map output | Filter output | Keys |
| --- | --- | --- | --- |
| `vector<T>` | `vector<U>` | `vector<T>` | Dense indexes |
| `fixed_array<T,N>` | `vector<U>` | `vector<T>` | Dense indexes; no new fixed-size transform contract |
| Typed `hash<T,K>` | `hash<U,K>` | `hash<T,K>` | Preserve keys and iteration order |
| Default heterogeneous `hash<mixed>` | `hash<mixed>` | `hash<mixed>` | Preserve integer/string keys |
| Table-valued `mixed` | Fresh table-valued `mixed` | Fresh table-valued `mixed` | Preserve integer/string keys |
| Default `dynamic` | Fresh `dynamic` | Fresh `dynamic` | Preserve integer/string keys; new outer identity |

`T` is the exposed iteration value type; `U` is the callback's declared result
type. For dynamic/mixed/default heterogeneous hashes, input values are `mixed`;
use an explicitly `mixed` callback parameter and stabilize inside the callback.
Mapping those carriers boxes callback results into mixed values. This preserves
heterogeneous key support without inventing unsupported `hash<U,mixed>` storage.
For any already-supported typed dynamic form, follow its underlying typed hash
policy and prove frontend representation before exposing that specialization.

Non-table mixed and empty/null dynamic handles should produce a clear runtime
type/state failure, not silently behave as empty collections. Empty *tables* are
valid. Existing foreach on non-table mixed returns an empty range; validation is
therefore explicit at the collection-helper boundary, without changing foreach.
Likewise, unwrap `result`/nullable carriers before calling these helpers even
though some foreach adapters currently skip failed wrappers. Do not hide errors
under an indiscriminate "anything that loops" acceptance rule.

- One supported collection, one callback argument, one call per element in
  input iteration order. Keys are not passed to the callback in the first scope.
- Map emits exactly one result per element. Filter retains original key/value
  pairs for keyed carriers, and compacts retained values for sequences.
- Empty input returns the corresponding empty result without callback invocation.
- Callback parameter and return types are explicit; filter requires `bool`.
- Input element and callback parameter types must match; no implicit `mixed`
  bridge is introduced to rescue mismatches. The declared callback result type
  must be storable in the selected output; boxed outputs require a boxable type.
- Initially use inline typed closures/arrow functions or typed callable locals.
  No dynamic names, variadic callbacks, or by-reference callback parameters.
- Capture-free callbacks and explicit by-value captures are permitted. Captured
  state must be treated as read-only, including reachable shared objects.
- Callback code must not mutate the traversed collection through any alias, mutate
  its elements/shared objects, or perform externally visible side effects.
  It may allocate and mutate a fresh result object that it owns.
- These mutation/purity restrictions are application discipline, not a promise
  of static enforcement. Reject structurally explicit reference captures at the
  portability boundary when possible; no whole-program purity checker is added.
- Ordinary element copy semantics apply. Class handles remain shared handles;
  filtering does not deep-copy objects or nested dynamic identities. A fresh
  outer dynamic result does not promise a deep clone. Exceptions propagate
  immediately; no partial output is returned and temporary output is destroyed normally.

Proposed PHS call shape, not an executable example of an available builtin:

```php
$items vector<int> = [1, 2, 3];
$doubled vector<int> = collection_map($items, fn(int $x): int => $x * 2);
$selected vector<int> = collection_filter($items, fn(int $x): bool => $x > 1);
```

Reference disposition: single-input transformation, keyed-table keys and order
are kept; sequence filtering is modified to dense output; multiple inputs, null
callbacks, dynamic callable resolution, and callback mutation are dropped.
Do not register these as `array_map` or `array_filter`.

Owner: `foreach.hpp` owns read-only input adaptation; lightweight helpers under
`runtime/include/scpp/` own transformation and result construction, in a native
`scpp::collections` family. Use one map traversal and one filter traversal with
small carrier-specific result builders. Those builders own append versus keyed
insert, boxing and outer identity. No helper-name/type switch in the generator.
Pass input by const reference, reserve where supported, and invoke the callback
directly. Do not add a second `std::function` conversion in the helper. Existing
callable-local lowering may still impose that cost.

STAN must bind `T` from the input and `U` from an explicitly typed callback using
the existing runtime-signature owner. Native C++ may deduce template arguments;
the structural portability converter must preserve authored types rather than
infer them. First proof: an `int -> string` map has `vector<string>` in STAN and
native code, and incompatible callback/assignment types fail appropriately.
Also prove typed-key hash outputs and deliberate mixed/dynamic boxing; generic
result construction must not accidentally normalize or discard keys.
If that requires a broad semantic frontend redesign, report the ownership gap
instead of adding helper-name branches or returning `mixed`.

Issue #199 remains open. Its failing example places `use` after the return type;
the current rules show `use` before the return annotation. Test the chosen
canonical syntax independently; the open issue does not prove all captures fail.

### Portable PHP: explicit operation policy

Keep the two generic strict helpers above. For portable PHP, encode the output
policy in a thin framework entrypoint, as existing `take_nullable`/`take_false`
spellings already encode wrapper intent. Proposed entrypoints:

| Portable name pair | Accepted native carrier | PHP output policy |
| --- | --- | --- |
| `sequence_map`, `sequence_filter` | vector/fixed array | Append into a fresh list |
| `keyed_map`, `keyed_filter` | hash/table-valued mixed | Assign retained original keys into a fresh array |
| `dynamic_map`, `dynamic_filter` | dynamic handle | Fresh outer framework object with keyed entries |

Each name maps through `function_map.php` to a constrained thin native adapter
over the same collection algorithms. Preserve that constraint in native code;
do not erase every alias to an unconstrained generic call. Passing a native hash
to a sequence entrypoint must fail, even when its integer keys happen to be dense.
PHP sequence entrypoints require a list; keyed entrypoints accept an array even
when its keys are dense. `array_is_list` may validate a *chosen sequence contract*,
but must never choose between sequence and keyed semantics.

Illustrative portable PHP, proposed rather than currently convertible:

```php
$items /** vector<int> */ = [1, 2, 3];
$selected /** vector<int> */ = sequence_filter(
    $items, static fn(int $x): bool => $x > 1
);
```

An input `[0 => 10, 1 => 20, 2 => 30]` filtered for values above 10 yields
`[0 => 20, 1 => 30]` through sequence_filter and `[1 => 20, 2 => 30]` through
keyed_filter. Both algorithms use the same entry traversal; only insertion differs.

This does not require PHP wrapper objects for all existing vectors/hashes or
receiver-type inference in the converter. Dynamic does need an explicit shared
object representation in the PHP framework; plain PHP arrays cannot prove its
identity semantics. Keep that representation local to the portability runtime,
with an owned entry array and fresh result object. Its access/alias parity is a
required representation proof before porting dynamic callers, not permission to
silently change compiler data structures. Generic strict dynamic support remains
in scope independently of that converter milestone.

The six portable names are small bindings, not six runtime algorithm owners.
General callback conversion, hash annotations, mixed stabilization and dynamic
representation are not all supported in today's converter; add bounded syntax
and representation proofs as separate integration work. Do not claim these
examples run merely because the target runtime eventually supports the helpers.

Caller review found both keyed filters (for example
`resolve_types/data/result.php::body_signatures`) and explicit reindexing with
`array_values(array_filter(...))` in runtime record preparation. Adapt those
intentions to keyed and sequence operations respectively. Dynamic string callback
names and first-class method references also occur; replacing them with explicitly
typed closures is application adaptation when each slice is authorized, not a
reason to add general PHP callable resolution.

## 4. Managed processes

Proposed API:

```text
process_start(string executable, vector<string> args, string stdin,
              int timeout_ms, string cwd = "") -> result<process_handle>
process_poll(process_handle handle) -> result<bool>
process_result(process_handle handle) -> result<process_output>
process_stop(process_handle handle) -> result<bool>
process_close(process_handle handle) -> result<bool>
```

`process_handle` is opaque. `process_output` is a typed result snapshot with:

```text
stdout: string        stderr: string
exit_code: int       signal: int
timed_out: bool      stopped: bool
```

Normal exit uses its exit code and signal 0. Signal termination uses exit code
-1 and the signal number. Nonzero program exit is a successful operation with a
nonzero status, not a runtime API error. Timeout and explicit stop remain
distinct. The first terminal cause wins; a later close does not relabel it.

### Launch and capture

- Require an explicit executable path initially; resolve tool paths in the
  framework. No shell interpretation or PATH-search policy in the first slice.
  `args` excludes argv[0]; empty arguments are valid. Reject NUL in paths/argv,
  an empty executable, and negative timeout. Stdin/output are binary strings.
- Empty cwd inherits the parent's directory; otherwise require an absolute
  directory. Inherit environment initially. Environment overrides are a caller
  review item, not silently declared unnecessary.
- Timeout 0 means disabled. Positive timeout starts after successful launch.
  Launch and file setup are synchronous and not covered by execution timeout.
- Proposed simplest backend: private temporary regular files for all three
  standard streams. Fully write and rewind stdin before launch. Child stdout
  and stderr go to separate files. This avoids producer/consumer pipe deadlocks
  without threads or a pipe event loop.
- File-backed capture changes descriptor behavior: it is seekable and is not a
  terminal or pipe. Only batch tools that accept this are in the first scope.
- Temporary descriptors are close-on-exec except the intentional child standard
  streams. Prefer anonymous/unlinked temporary files; never use a predictable
  shared filename. Setup failures unwind all already-created resources.
- Launch/setup/read failures return structured errors. Do not confuse exec
  failure with a program that legitimately exits 127: the launch mechanism must
  provide an exec-failure distinction. Prove this on the chosen Linux backend.
- Capturing costs disk/tmpfs IO and final output memory. This slice has no live
  output API or total output quota. Very large output is supported within host
  resources, not advertised as unbounded. Child write failure, such as disk
  exhaustion, may be visible only through the child's behavior; do not promise
  the parent can detect every incomplete child write.

### Polling and lifetime

- States: running -> stopping -> complete -> closed; internal failures retain
  enough ownership to clean up. Cache terminal child status exactly once.
- Poll returns false while work/cleanup remains, true when completion is ready,
  or an error for API/OS failure. It must not wait for the child or read the
  complete capture files. Repeated completed polls return true.
- Check an available exit status before applying the deadline. If still running
  at an expired deadline, request forced group termination and enter stopping.
- Timeout is cooperative: callers must poll regularly. A 10 ms polling cadence
  is a proposed framework default, not a real-time guarantee. No background
  worker enforces the deadline while the caller does unrelated work.
- `process_result` requires complete state, reads/caches the captured output,
  and returns an independent snapshot. It may perform blocking file IO.
  Before completion or after close it returns an invalid-state error.
- `process_stop` requests immediate forced group termination without waiting;
  callers continue polling. Repeated stop succeeds harmlessly. A stopped process
  still has a collectable result. Graceful TERM/grace-period policy is deferred.
- `process_close` force-stops any unfinished group, reaps the direct child and
  releases resources. It can block during reaping; repeated close succeeds.
  Destruction is a nonthrowing fallback with the same resource responsibilities.
  Do not promise a hard cleanup deadline for an uninterruptible OS process.
- One designated application owner calls lifecycle functions. Ordinary handle
  copies alias one state and do not create another child owner. Close invalidates
  all aliases. Concurrent calls on the same handle are outside the first contract.

### Group ownership is the critical backend proof

Create a fresh group before executing the target. Descendants must remain in
that group; daemonization or moving to another group/session is excluded.
When the leader exits, remaining group members are also cleaned up: spawning
background work is not a supported way to let work outlive this handle.

Never retain a bare reusable PID/PGID and signal it after ownership has been
lost. Native candidate: observe exit without reaping, perform the required
group action while the leader remains waitable, then reap. Establish the exact
ordering and output-snapshot semantics with descendant tests before freezing
completion behavior. `waitid(..., WNOWAIT)` provides a relevant Linux primitive.
Reaping the direct child does not mean this API can reap arbitrary grandchildren.

`posix_spawn` with process-group/file actions is a candidate, not a frozen
backend choice. Check cwd support and exec-failure reporting. If fork/exec is
needed, keep the child-side path safe in a multithreaded host; do not run C++
allocation/destruction there or introduce a broad runtime fork framework.

Reference disposition: supplied input, separate output and status are kept;
capture and lifetime become typed and group-owned; arbitrary PHP resources,
shell command strings, interactive pipes and detached jobs are dropped.

Owner: new optional `runtime/include/modules/process/` module, `scpp::process`.
Keep OS implementation in a compiled source file and OS headers out of public
headers. Reuse clock/error/ownership facilities, but do not stretch generic
`resource_handle` into a process lifecycle. Existing `shell_exec` remains its
current small contract; replacing it is not necessary for this feature.

## 5. Cross-process locks

Proposed API:

```text
fs_lock_try(file_lock_handle &out, string path, bool shared = false) -> result<bool>
fs_lock_release(file_lock_handle handle) -> result<bool>
fs_lock_transfer(file_lock_handle handle) -> result<file_lock_handle>
```

- `result<bool>` separates three outcomes: success(true) acquired;
  success(false) contended; error failed to open/lock. `take` extracts the bool;
  its own success return alone does not mean the lock was acquired.
- `out` must initially be empty or released. On contention/error it is unchanged.
  Reject an already-owning output rather than silently releasing its lock.
- Exclusive acquisition opens read/write and creates the file if absent, without
  truncating or replacing it. Shared acquisition opens an existing file read-only
  and does not create it. This preserves package readers' ability to consume a
  read-only prepared package. Missing shared-lock files are IO errors.
  Release never unlinks. Parent directory must exist. Application code must not
  unlink or replace the lock file while any participant may be using it.
- Shared mode allows readers; exclusive mode excludes readers and writers.
  Both are nonblocking. Shared callers have been confirmed in the adopted
  compiler. Its blocking reader uses a framework loop: retry only contention,
  sleep between attempts, fail immediately on IO errors, and continue until
  acquired. Do not invent a timeout for that previously blocking caller. This
  promises eventual retries, not fairness or a bounded acquisition latency.
- Use Linux `flock` on a dedicated descriptor, with close-on-exec. Retry EINTR;
  only the defined would-block error becomes contention. These are advisory
  locks: every participant must follow the same protocol.
- Release performs explicit unlock then close. Empty/released handles succeed
  harmlessly. Operational failures are errors, not contention. The concrete
  implementation must define partial-cleanup state and avoid blindly retrying
  a descriptor close after its numeric descriptor may have been reused.
- Handle copies alias the same token. Explicit release invalidates all aliases.
  Last-token destruction performs nonthrowing fallback cleanup.
- Transfer allocates a new token first, transfers the held descriptor without
  unlocking/reopening it, then invalidates the old token and all its aliases.
  Failure leaves the old owner intact. This gives `Package_Reservation` a real
  ownership handoff without introducing move-only source types. Only transfer
  within one process is included; arbitrary process-to-process transfer is not.
- A forked child must not explicitly unlock the parent's inherited lock token:
  that could release the parent's lock. In a child, cleanup closes only the
  inherited descriptor. Track acquiring PID for this distinction. The parent
  explicitly unlocks even if a pre-exec child still holds an inherited descriptor.
  Public manipulation/transfer of an inherited token is rejected.
- Filesystem identity, contention and inheritance must be tested across separate
  processes, including native/PHP contention on the same lock file.

Reference disposition: advisory shared/exclusive OS locking is kept; blocking
and generic stream flags are narrowed to typed nonblocking acquisition; raw
resource interoperability, upgrades and distributed guarantees are dropped.

Owner: filesystem module, `scpp::fs`, with a dedicated lock source/header and
opaque state. It shares no lifecycle with a user-closeable `io_open` stream.

## 6. Strict metadata, framework and build plan

Use `php_runtime_symbols_strict.json` for names/targets and
`php_runtime_symbol_contracts_strict.json` for normalized contracts; derive the
STAN-visible surface through the existing runtime shallow-source generation.
Inspect the current incomplete/blocked metadata policy rather than treating a
new registry row as proof that every compiler consumer accepts the function.
New handle/result names must use the existing type-mapping and runtime-class
catalog owners. Do not hand-edit generated STAN files as the primary fix.

Collection helpers should be core-backed templates. Locks build with filesystem;
processes require an explicit `process` module. Unsupported platform selection
must fail clearly rather than silently omit cleanup semantics. No new optional
OS implementation should be dragged into every translation unit.

The PHP framework should expose matching lowercase `scpp` operations, typed
documentation and explicit converter mappings. Use the explicit portable
collection entrypoints from section 3; their authored names select output policy.
The current framework/converter owner is
`/home/alexv/__AI/simple_cpp/simple_cpp_01/tools/php_portability/`, with
`runtime/bootstrap.php` and `function_map.php`; use that existing mapping owner.
No changes to that separate checkout were made during this design review.
Use PHP OS facilities for lock/process backends. `proc_open` supports argv and
file descriptors, but its documented process-group option is Windows-only.
Linux group ownership and safe reaping cannot be assumed from that API alone.
The prototype already supplies a setsid launcher. Reuse its isolation intent,
but replace the status/ownership path rather than copying its lifecycle blindly.

### PHP process backend recommendation

Require PHP CLI 8.4+ with PCNTL and POSIX for this optional framework feature.
`pcntl_waitid` supports `WNOHANG | WNOWAIT`, allowing the same observation-before-
reaping discipline as native Linux. Missing prerequisites should fail explicitly;
do not add an older-PHP fallback with weaker ownership guarantees.

Use a small PHP framework launch shim, invoked with `proc_open` and file-backed
stdio. The shim establishes a fresh session/group, reports its PID and group
readiness, then execs the target. It reports setup/exec failures through a private
status channel separate from program stdout/stderr. The parent obtains identity
from this handshake, not an initial `proc_get_status` call that could reap a
fast-exiting child. Treat handshake failure as launch failure and unwind resources.

Protocol proof requirements before freezing backend implementation:

- Process identity/group readiness are published before target exec. An early
  stop cannot miss group establishment or launch a target after cancellation.
- A successful exec and a failed exec are distinguishable; a legitimate target
  exit 127 is not a launch error. Define framing/EOF and partial-write handling.
- The target cannot inherit the private control descriptor or modify launch
  status. Use close-on-exec and a bounded local protocol; no shell quoting.
- Poll with `pcntl_waitid(P_PID, owned_pid, ..., WEXITED | WNOHANG | WNOWAIT)`.
  No other handler or framework code may reap the owned PID or ignore SIGCHLD.
- Stop/cleanup performs its group action while the leader is still owned and
  waitable, then exactly one owner reaps it. Define interaction with `proc_close`
  explicitly; do not make both PCNTL and proc resource destruction status owners.
- A PHP shim means one additional PHP interpreter startup, replacing the setsid
  launcher. Measure this for short compiler tools before adopting it on hot paths.

This is a concrete preferred route, not a proved launch implementation. If PHP
stream primitives cannot honestly provide the control descriptor contract, stop
and compare a tiny native launcher with changing the PHP backend requirement.
Do not silently add a helper binary, FFI or a custom extension. A native launcher
would add installation/versioning work and cross-backend protocol tests.

No converter inference, broad callback rewrites, or new `finally` lowering belongs
to these runtime changes. Framework exceptions/result adapters and handle output
parameters need their own PHP/native parity checks; current scalar-only wrapper
fixtures do not prove the new handle/result contracts.

## 7. Completion audit and remaining gates

The native runtime, strict exposure and STAN slices are implemented and validated
as recorded above. The user lifted the collection deferral and approved its
cross-owner prerequisite repair. The normative family contracts now own semantics;
the earlier proposals in this document are design history.

The final Simple C++ audit adds synchronized descendant checks for timeout, stop,
close and destruction, and proves that an available exit status wins over a late
poll. Collection tests additionally exercise all/none filtering and mapping empty
results across vector, fixed array, typed hash, mixed-key hash, mixed and dynamic
carriers (including native typed dynamic). Per-builtin reference entries link to
the family contracts and are registered as metadata authority sources.

Audit validation passed on the uncommitted working tree based on `8cc4d8ff`:

- `cmake --build /tmp/scpp-file-lock-build --target test_process test_collections -j 2`
- `ctest --test-dir /tmp/scpp-file-lock-build -R 'scpp_test_(process|collections|file_locks)$' --output-on-failure`
- `php tests/tools/test_scpp_strict_runtime_catalog.php`
- `php tests/tools/test_scpp_collection_typing.php`

Issue-wide gates still outstanding:

1. Implement and prove the matching portable PHP framework/converter surface in
   the adopted compiler checkout. The process launch protocol remains unproved.
   On local PHP 8.5.7, PCNTL/POSIX were available and `pcntl_waitid` with WNOWAIT
   observed exit 7 repeatedly before exactly one reap. That proves only the wait
   primitive, not launch error reporting, descriptor inheritance or group cleanup.
2. Run matching PHP/native behavior cases, including keyed hashes with dense
   integer keys, wrapper/handle ownership and process lifecycle. Rerun the actual
   affected compiler portability proofs; runtime fixtures do not replace these.
   Measure representative compiler callback payloads and any PHP launcher overhead
   before drawing migration performance conclusions.
3. Commit/select an immutable target revision and record it in those migration
   proofs before switching the compiler away from v0.1.76. The local working tree
   is not a released target. Publication has not been performed.

The original audit missed the native constrained adapters; the follow-up recorded
above closes that gap within the accepted Linux batch-tool scope. PHP framework parity and compiler migration remain
separate work and still prevent closing #231.

Stop and report a blocker if PHP parity needs a native launcher/extension or a
broad ownership refactor; do not silently expand the accepted runtime slice.

## 8. Reference material

- [Linux flock contract](https://man7.org/linux/man-pages/man2/flock.2.html):
  locks follow open file descriptions; explicit unlock and final close differ
  when descriptors are duplicated or inherited.
- [Linux wait contract](https://man7.org/linux/man-pages/man2/waitpid.2.html):
  nonblocking observation and leaving a child waitable with `WNOWAIT`.
- [Linux posix_spawn](https://man7.org/linux/man-pages/man3/posix_spawn.3.html):
  group attributes, file actions and launch failure details.
- [PHP proc_open](https://www.php.net/manual/en/function.proc-open.php):
  argv/file-backed standard-stream support and platform-specific options.
- [PHP proc_get_status](https://www.php.net/manual/en/function.proc-get-status.php):
  status reporting and version-dependent exit-status caching.
- [PHP pcntl_waitid](https://www.php.net/pcntl-waitid): PHP 8.4+ support for
  observing a child without reaping, including nonblocking flags.

These OS/API references support backend analysis. Runtime implementation evidence
is recorded above; the proposed PHP framework backend is not yet proved.
