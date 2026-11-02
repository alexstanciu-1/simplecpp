# Local compiler workbench

Doc Status: supporting

Run inside WSL, from the repository root:

```sh
python3 v0.2/tools/compiler_panel/server.py
```

Open **http://localhost:8765** in the Windows or WSL browser. Stop with Ctrl+C.
The server binds exclusively to `127.0.0.1`; there is deliberately no host/bind
option. `--port 8766` selects another local port. `--php /path/to/php` or
`PHP_EXECUTABLE` selects the existing 64-bit PHP 8.5+ installation.

Prerequisites: Python 3.10+, PHP 8.5+, clang++, libc++, Ninja, mold, util-linux `prlimit`, and an
existing default v0.2 CMake build (native facts, generated runtime headers and
`build/clang/libscpp_runtime.a`). The panel does not rebuild or modify the runtime.
If required, run `cmake --preset default` then `cmake --build --preset default`
from `v0.2` first.

## Reports

The panel accepts up to 24 files (256 KiB total), with an explicit entry and
cpp/php/js flavor per file. Names have no directory components. It calls the real
compiler stages, without evaluating source as PHP or accepting edited native C++.

- Input loading: file, flavor, byte size.
- Tokenization: complete tokens with positions, spelling and lossless hex.
- Parse + local collection: syntax and local IDs/references, including duplicates.
- Global publication: named candidates and original source/token positions.
- Target facts: native signed integer range.
- Preparation: entry and every source function's settled facts and work counters.
- C++ lowering + emission: complete generated translation units.
- Optional native build: exact argument list, compiler output, Ninja graph and executable size. Native builds use Ninja -j4,
  mold and explicit -g0; optimization is selectable (O0/O1/O2, default O2).
- Optional execution: stdout, stderr, exit status and elapsed time. `return 8` is a
  successful execution with status 8; signals/timeouts are failures.

Parsing collects locals in the same traversal. Declaration/body preparation is
one measured orchestration stage, with its current internal phase barriers.
Lowering and text emission currently share the backend traversal. The panel does
not invent separate timings for combined stages. Inspection formatting is outside
stage timers; process time additionally includes startup and report serialization.
PHP memory delta is live allocated memory; the PHP peak is cumulative and includes
retained inspection views. These are inspection runs, not performance benchmarks.

Application requests run in disposable temporary directories; their sources, objects
and executables disappear afterward. Only the optional runtime PCH cache persists.
Download JSON to retain a report, including generated C++. Editor contents are saved
in this browser's localStorage. Reloading an example replaces editor contents.
Failures retain earlier reports and do not run dependent stages.

## Local boundary

The server checks loopback peer addresses, exact localhost Host headers, same-origin
POSTs, a per-process request token, and JSON content type. It serves only three
static assets and two explicit API routes; no repository browsing, CORS, CDN or
external scripts. Do not expose it through a reverse proxy or tunnel.

Only one pipeline runs at a time. Compiler reporting has a 30-second limit, native
build 60 seconds, execution 3 seconds; process groups are terminated on timeout.
Children have a 2 GiB address-space limit, 8 MiB per-output-file limit, and disabled
core dumps. Compiler report overflow becomes a process-stage error; stderr display
is capped at 256 KiB. This is a trusted local development tool, not an OS sandbox
for hostile programs. It uses the launching user's permissions.

Verify with `python3 -B v0.2/tools/compiler_panel/test_panel.py`.

Profile fresh native builds with `python3 -B v0.2/tools/compiler_panel/profile_native.py`.
It compares -O0/-O1/-O2 with explicit -g0, five samples each, separate compile/link
measurements and separate Clang time traces. Results and generated artifacts are
retained in the printed temporary directory. No runtime rebuild or compiler cache.

## Ninja and precompiled headers

The panel uses the same build-graph writer as `profile_ninja.py`. Every source
translation unit gets an object; Ninja schedules at most four jobs and the final
Clang link command explicitly selects mold. Dependency files track included headers.

PCH is optional and off by default. When selected, the runtime integer/output
headers and the public entry header are precompiled with matching optimization/compiler flags.
The entry adapter has no runtime includes and does not use this PCH. The panel
retains runtime PCH entries in `v0.2/build/compiler-panel-pch/` (override with
`--pch-cache /path/to/cache`). Application objects and executables still build fresh.

The cache key includes the resolved compiler's identity, optimization, exact build
flags, umbrella header, runtime include path and relevant include environment.
One Ninja invocation checks the PCH and builds/links the application. Its persistent
cache directory retains the dependency and command logs. Ninja rebuilds
when runtime or system header dependencies change or disappear. A lock protects each entry through the consuming
application build, including across multiple panel servers. A failed PCH build
removes the artifact so it cannot be reused as successful output.

Native reports show BUILT, REUSED or REBUILT, the cache key/path, and combined
Ninja dependency-check/compile/link time. PCH creation, when needed, is included
in that same invocation. Total native time also includes setup, locking, cleanup
and copying the executable back to the disposable run directory. Linux pidfd exit notifications avoid
timeout polling delays in subprocess timing; the WSL kernel must support pidfd. The first run for a configuration
builds its PCH; subsequent unchanged runs reuse it, including after server restart.

To clear the cache, stop panel runs and remove `v0.2/build/compiler-panel-pch/`.
Old compiler/flag variants remain until that manual cleanup; there is no automatic
eviction. Verify dependency invalidation and failed-build recovery with
`python3 -B v0.2/tools/compiler_panel/test_pch_cache.py`.

Run `python3 -B v0.2/tools/compiler_panel/profile_ninja.py` to compare all three
optimization levels, PCH on/off, one/eight source files, five rotated samples.
Fresh builds include PCH construction. The separate full-recompile measurement
retains PCH but removes all object files and the executable. It also verifies
no-op builds, PCH invalidation and identical execution. Raw timings, build graphs,
verbose commands and logs remain in the printed temporary directory.

The **Clang PCH validation** checkbox defaults to on. Turning it off passes
`-Xclang -fno-validate-pch` only to application compilations consuming the PCH.
Ninja dependency checking remains enabled; this does not bypass our cache key or
header-change invalidation. Both modes share the same runtime PCH. Keep validation
on unless a controlled measurement justifies relying solely on cache checks.

`python3 -B v0.2/tools/compiler_panel/profile_pch_validation.py` compares both
modes across O0/O1/O2 with seven rotated samples and saves raw results/build logs.
The application objects and executable are removed from the locked cache directory
after each build; only the PCH and Ninja bookkeeping survive. Fixed object names
avoid retaining a separate output identity for every panel request.

The **64 KiB integer + echo** example generates one source file in the selected
flavor, with distinct `ret_1`, `ret_2`, … variables and increasing values starting
at 8. Each block prints `Result: value` followed by a newline. The source is just
over 65,536 ASCII bytes and ends with `return 0;`.

Panel compiler subprocesses set `XDEBUG_MODE=off`, independent of the parent
environment, so default measurements exclude Xdebug instrumentation. This does
not modify system PHP configuration or enable OPcache/JIT.
