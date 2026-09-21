# Cross-process file locks
Doc Status: normative
Status: experimental
Compatibility: narrow; Linux local-filesystem advisory locks.

## Contract and ownership

The filesystem module owns one dedicated opaque `file_lock_handle` token per
acquisition. Copying a source handle aliases that token; it does not acquire or
duplicate a lock. Calls on one token must be serialized by application discipline.
No source constructor, descriptor access, or user-stream interoperability is exposed.

`fs_lock_try(file_lock_handle &$out, string $path, bool $shared = false): result<bool>`
returns success(true) on acquisition, success(false) on contention, and an error
on invalid state/path, open failure or other OS failure. The output must be empty
or released; unsuccessful acquisition leaves it unchanged. Shared acquisition
opens an existing regular file read-only; exclusive acquisition opens read/write
and creates it if absent (mode 0666 subject to umask). Neither truncates/replaces.
Empty/NUL paths and non-regular files are errors. No parent directories are created.
Both modes are nonblocking. EINTR during open/flock is retried.

`fs_lock_release(file_lock_handle $handle): result<bool>` explicitly unlocks then
closes. Empty/released tokens return success(true). Every alias becomes released.
Unlock failure retains ownership for retry/destruction. After successful unlock,
close invalidates the token even if close reports a late error; the numeric
descriptor is never retried. Errors are reported through `result`, not false.

`fs_lock_transfer(file_lock_handle $handle): result<file_lock_handle>` creates a
new token owning the same descriptor without unlocking/reopening. It invalidates
the old token and all its aliases. Empty/released/inherited inputs are errors.
Allocate the new token before transfer so allocation failure leaves ownership
unchanged. Ordinary allocation exceptions may propagate, as with other runtime
allocations; OS/validation failures use `result`.

Last-token destruction performs nonthrowing cleanup. Explicit release is preferred
where failures must be reported. A forked child's destructor closes its inherited
descriptor without unlocking: Linux flock state is shared across inherited
descriptors. Public release/transfer of a live inherited token fails. The parent
explicitly unlocks even if a pre-exec child still holds a descriptor. Acquisitions
use close-on-exec. There is no cross-process ownership-transfer API.

Release never unlinks the lock file. All participants must use the same stable
file identity and must not unlink/replace it. Shared readers coexist; an exclusive
owner excludes both readers and writers. These are advisory locks, with no
network-filesystem/distributed guarantee. Symlinks follow normal open resolution;
the application owns stable path selection. No upgrades, fairness, or blocking API.
Applications needing a blocking wait retry contention, sleep, and stop on errors.

On non-Linux builds every public operation returns an unsupported-platform error.
The public header remains portable; unsupported calls never report successful locks.

## Reference disposition

| OS/PHP behavior | Simple C++ contract | Disposition |
| --- | --- | --- |
| flock shared/exclusive advisory ownership | Same Linux semantics | kept |
| Generic stream plus flags | Dedicated token, typed operations | modified |
| Contention vs other failure | success(false) vs error | kept |
| Blocking acquisition/upgrade | Application retries; no upgrade | dropped |
| Close/inheritance behavior | Explicit unlock, close-on-exec, owner-PID check | narrowed |

## Exposure and compile plan

Strict names forward through `php_runtime_symbols_strict.json` to
`scpp::fs::lock_try`, `lock_release`, `lock_transfer`. Normalized contracts in
`php_runtime_symbol_contracts_strict.json` own signature metadata. Shallow STAN
generation derives parameter names, reference modes and optional arity from them.
`file_lock_handle` is the source alias of `scpp::fs::file_lock`, held using the
existing class/shared-handle representation. No generator type inference is added.

`runtime/include/modules/filesystem/file_lock.hpp` exposes OS-free declarations;
`file_lock.cpp` owns Linux syscalls and cleanup. Build in `scpp_filesystem` and
the existing monolithic `core/runtime.cpp` composition used by source builds.
Filesystem is a default project module and STAN's function catalog marks it as
required for these calls. The existing monolithic PHS runtime includes filesystem
implementation even in some configurations omitting that module; this slice does
not promise a new PHS module-exclusion gate. There is no legacy registration.
Wider runtime-composition reorganization is a non-goal.

## Required proof

Separate-process tests cover writer/read contention, multiple readers, release,
transfer and old-alias invalidation, read-only shared open, missing/error paths,
nontruncation, stable identity, destructor cleanup, inherited descriptor cleanup,
parent unlock before child exec, and close-on-exec. Native/PHP flock contention
must agree. Strict project build/run proves signatures, result extraction, handles
and reference output. Catalog tests prove module/return/reference metadata.
This target-runtime contract does not claim the separate portable PHP converter
already supports the new operations.
