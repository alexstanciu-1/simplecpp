# PHP native-build host

Doc Status: supporting

The PHP host owns the `build` command: source compilation and C++ bundle publication,
qualification cache lookup, Ninja graph publication and native invocation happen in
one PHP process. Preparation and lowering remain independent of native compilation.
The existing `project` command still emits C++ only.

```sh
XDEBUG_MODE=off php compiler/main.php build \
  /path/to/app /path/to/facts.json /path/to/generated /path/to/native /path/to/toolchain.json
```

The explicit toolchain file is a JSON object, for example:

```json
{
  "cxx": "clang++",
  "flags": ["-std=c++23", "-O2", "-g0", "-stdlib=libc++", "-Wall", "-Wextra", "-Werror", "-I", "/path/to/runtime/generated/public"],
  "link_flags": [],
  "jobs": 4
}
```

`cxx` and `flags` are required. Optional fields are `link_flags`, `jobs` (1..1024),
`ninja` (default `ninja`), `python` (default `python3`) and `cache` (default
`qualification` inside the native directory). Executable names are resolved through
PATH in PHP, without invoking `which` or compiler version commands. Relative flag
operands, executable paths and cache paths are interpreted from the native directory.
Unknown configuration fields reject. This first host targets the existing Linux
Clang/GCC toolchain; it does not provide platform/toolchain autodetection or PCH
orchestration. Explicit PCH options reject. Existing panel/PCH launchers are unchanged.

Input paths support spaces, Unicode, dollar signs and colons. Quote, backslash and
pipe characters in native graph input paths reject in this slice; the current
Ninja/Make depfile grammar does not reliably round-trip those spellings.

The bundle manifest supplies generated translation units, external native sources and
prebuilt dependency link inputs. This command does not rebuild dependencies. Use
native facts and prebuilt runtime artifacts compatible with the chosen target,
compiler and standard library. The default runtime manifest selects the existing
Clang/libc++ archive; it does not automatically substitute a GCC archive.

A retained PHP caller uses the same host without starting another PHP process:

```php
require_once 'compiler/boot.php';
require_once 'compiler/host/native_build.php';

$project = new \scpp\compiler\Incremental_Project($app, $generated, $target);
$config = \scpp\compiler\Native_Build::configuration($toolchain_file);
$report = $project->run(); // Do not build if source preparation/publication throws.
$native = \scpp\compiler\Native_Build::run($generated, $native_directory, $config);
// Reuse $project; call run() and Native_Build::run() after subsequent source edits.
```

## Cache and invocation ownership

`Qualification_Cache` reads the existing Python qualification index and record in
PHP. Cache schema 2 binds the entire tracked-input snapshot into the content-addressed
context via `inputs_sha256`. Removing even one dependency or substituting a nonempty
map makes both Python and PHP readers miss. Older schema records also miss and are
refreshed by the Python owner; they are not silently accepted with weaker validation.
This is cache integrity checking, not authentication against a writer that can rewrite
the cache. Request/context JSON hashing, option admission, recognized environment
and working-directory identity, seven-day expiry and trace checks remain shared
protocol rules. The PHP option scan determines admission and relative-path dependence
together; parity vectors cover both readers. File validation
uses stat metadata, not compiler binary hashes. It clears PHP's stat/realpath caches
on every input check so a retained process observes changes. Warm validation launches
no processes: no Python/PHP helper, `stat`, `which`, compiler driver or shell.

**Accepted bootstrap debt:** Python-written nanosecond timestamps are explicitly
floored to seconds for comparison with PHP `stat()`. Same-size changes within one
second can escape detection if other compared fields remain equal. Restore high-
resolution timestamps in the compiled compiler; do not launch a helper to work
around this accepted limitation. See the [planning note](../../specs/planning/php_toolchain_qualification.md).

`.scpp-native-context` contains the verified context key and is a normal implicit
input of every compile and link edge. A context change (including search environment,
compiler replacement or tracked input identity) therefore rebuilds affected native
artifacts instead of merely requalifying and reusing stale objects. Application
headers still use ordinary depfiles, so an application-header-only edit rebuilds
its consumers without invalidating unrelated objects.

A valid hit publishes that stamp directly in PHP, only if its content changed, and
creates an invocation-only graph whose stamp-producing qualification edge is phony.
The saved `build.ninja` retains the real probe gate. On its cold/standalone path the
Python qualifier writes the same stamp via `--context-stamp`; Ninja's `restat` rule
preserves reuse if qualification refreshes without changing the context. This also
covers weekly expiry: it does not by itself force native rebuilding. The temporary
graph is removed after invocation. Stable object names and depfiles preserve no-op
reuse and surviving objects across source membership changes.

The PHP native host holds a shared bundle lock from manifest read through Ninja
exit. The bundle writer uses the same persistent lock inode exclusively for publication
and retirement. A separate build-directory lock serializes graph publication and
invocation. Standalone bundle consumers can hold a shared lock on `.scpp-bundle.lock`
around manifest and artifact reads. The
qualification-record lock is nonblocking on the warm path; contention falls back
to the protected graph. This stamp integration applies to the PHP host's saved
graph; the existing panel/PCH graph orchestration remains outside this slice.

On a miss, expiry or unsupported flag, Ninja runs the existing Python qualifier,
which owns fingerprinting, probing and atomic cache refresh. Unknown options may
require full fingerprinting even when a resulting qualification record is reusable.
Python is not a warm-cache launcher. Native compiler and Ninja processes still run
when their work is needed. Qualification traces never alter source evaluation-order
semantics.

The report contains the native exit code, captured output, qualification path,
lookup duration and native invocation duration. A nonzero native status produces
no successful executable field; the CLI returns failure. Source failures stop
before native invocation. This host does not run the resulting application.

## Verification

```sh
XDEBUG_MODE=off python3 -B compiler/tests/native_build.py
XDEBUG_MODE=off python3 -B compiler/tools/check_style.py
```

The proof covers cpp/php/js on Clang/GCC, with a matching GCC runtime fixture:

- Cold build, executable output, warm no-op object/executable reuse and graph stability.
- Retained PHP cache checks with process-launch functions disabled; warm native
  invocation with Python deliberately unavailable.
- Python/PHP record interoperability, Unicode/space/dollar paths, relative cwd,
  compiler/flag/link-flag/environment identity, expiry and future timestamps.
- Incomplete nonempty indexes, single-dependency removal, legacy-schema recovery,
  malformed records, contention, stat-cache clearing, changed/deleted/created inputs,
  and the explicitly accepted nanosecond-only difference.
- 33 cross-reader admission vectors per native compiler, including relative linker
  and dash-prefixed input paths, unknown options and nonempty `"0"` environment values.
- Cold and already-cached `CPATH` transitions, replacement of the selected compiler
  at the same path, and standalone context invalidation; unchanged refreshes retain
  objects and the executable.
- Header-only rebuild isolation, source deletion/restoration, relinking on membership
  changes, and retained/fresh generated-output and executable comparison in all flavors.
- Failed qualification/native/source compilation followed by recovery, with no stale
  successful executable result; standalone Ninja still runs its qualification gate.

The test reports the median of 31 warm cache checks separately from native builds.
These local checks are not a dev002 end-to-end performance comparison and do not
establish total compilation speedup.
