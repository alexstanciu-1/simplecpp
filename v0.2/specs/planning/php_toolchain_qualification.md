# In-process PHP toolchain qualification cache

Doc Status: planning

## Agreed direction

Qualification belongs to build/toolchain orchestration. A warm-cache validity
check must run inside the existing PHP compiler process, with no subprocess,
shell command or helper interpreter launched to validate the cache. Parsing,
semantic preparation and C++ emission do not own this check. Source-only commands
do not require native qualification.

Use PHP's native `stat()` resolution. Retain device, inode, size, modification time
and change time comparisons, but compare times at whole-second precision. Clear
PHP's cached filesystem metadata before checking inputs on each build invocation.
Preserve compiler/flags/environment/working-directory identity, seven-day expiry,
record validation and locking. Unknown or invalid records must take the refresh
path. Python may remain the cold/expired-cache probe implementation initially.

### Accepted debt: timestamp precision

Same-size edits within one second that preserve the other compared fields may be
missed by PHP. The user explicitly accepts this limitation for the PHP bootstrap.
The compiled compiler should restore high-resolution filesystem timestamps.
Do not compensate by spawning a timestamp helper or hashing every input on each
warm build. Python-written nanosecond stamps require explicit conversion to whole
seconds; never compare different units directly.

Qualification observations do not authorize reliance on unspecified C++ operand
order. The source evaluation contract remains unchanged.

## Implemented first host slice

Scope approved: add a bounded PHP native-build entry point. `main.php build`
now runs project compilation/publication, then `Native_Build` in the same process.
Retained callers invoke the host API after successful `Incremental_Project::run()`.
PHP reads the explicit toolchain configuration, checks qualification and writes the
Ninja graph directly. No small helper script runs on the warm path. Python retains
cold fingerprint/probe/cache-publication ownership.

Cache schema 2 binds the complete input snapshot into the context key. Both readers
reject incomplete nonempty indexes and legacy records; Python owns refresh and
publication. This is an integrity check, not a security/authentication boundary.

The saved Ninja graph remains protected; only a validated invocation skips its
qualification edge. Compiler flags, link flags and working directory are shared
between validation and the real native command. Every compile/link edge depends
on `.scpp-native-context`, which contains the verified key. PHP publishes it on a
warm hit; the Python probe publishes it on cold/standalone execution. Content-stable
publication and Ninja `restat` preserve reuse when the key is unchanged, including
weekly expiry refreshes. A changed key invalidates native artifacts. Stable object
paths and depfiles retain no-op reuse and source-local/header-local rebuilds. Source or native failures cannot report an old executable as
a successful result.

Panel migration, PCH orchestration, cross-platform support, automatic dependency
rebuilds and high-resolution native timestamp checks remain deferred. Existing
Python panel and standalone CMake paths retain their behavior. See
[host usage and verification](../../compiler/docs/native_build.md).

## Verification checklist

- [x] Warm validation launches no processes, including compiler identity helpers.
- [x] Actual build compiler, flags, environment and working directory determine
      the checked record; changed inputs and expiry invalidate it.
- [x] PHP reads compatible Python records with explicit timestamp conversion.
- [x] Repeated checks in one PHP process see file changes despite PHP stat caching.
- [x] Missing/malformed records and lock contention cannot bypass qualification.
- [x] Cold refresh failures prevent the native build; successful refresh recovers.
- [x] Exercise the accepted same-second limitation and document it as debt.
- [x] Standalone Ninja/CMake retain their qualification gate.
- [x] Compile and execute a native fixture, then repeat a no-op build; measure
      cache-check time separately from process startup and native build time.

## Review fixes and completed additional verification

- [x] Bind the entire input snapshot, including missing-path markers, to the
      versioned context; reject a substituted nonempty map or dropped dependency
      in both readers and recover legacy entries through the normal probe.
- [x] Rebuild for cold and already-cached search-environment changes and compiler
      replacement at the same selected path; verify changed executable behavior.
- [x] Verify the saved standalone graph invalidates artifacts too, while repeated
      qualification and unchanged weekly refresh preserve native objects/link output.
- [x] Compare 33 admission/context vectors per compiler across Python and PHP,
      including relative linker paths, dash-prefixed path operands and nonempty
      `"0"` environment settings. Run PHP validation with process-launch functions disabled.
- [x] Verify an application-header-only edit rebuilds its consumer and preserves
      unrelated objects. Verify source deletion/restoration through the retained
      PHP host in cpp/php/js on Clang/GCC, including relinking and fresh-output parity.
- [x] Preserve warm builds when Python is deliberately unavailable; no script is
      launched to validate or publish the native context stamp.
- [x] Rerun the Python qualification cache tests on Clang/GCC and the existing
      panel/PCH cache regression against the new shared format.

Deferred work and the accepted PHP timestamp limitation remain unchanged.
