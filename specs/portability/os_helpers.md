# File-lock and managed-process portability candidates
Doc Status: planning

## Status

Both PHP backends and native facades are implemented. The Linux PHP lifecycle
suite passes, and the prepared matching PHP/native facade proof now passes on
`2f0d667f38a35ff02ef77e813f409189cba2d032`. The earlier signature-alias blocker on
`08c8206a` is resolved by the [v0.1 update](https://github.com/alexstanciu-1/simplecpp/issues/231#issuecomment-5765056335).
This is still an unreleased branch candidate.

No compiler component depending on these operations has yet been migrated; the
ready set is tracked in `compiler/portability.json`. The focused facade proof covers the behavior
listed below, not unrestricted OS/API equivalence or every cross-backend failure
path. The PHP-specific limits below remain relevant. #231 has not been closed.

## PHP-facing contract

All functions are under lowercase `scpp` and use the uniform imports. They are
thin throwing facades over native result-returning operations, not PHP resource
compatibility. Operational failures throw the supported RuntimeException family;
messages are backend-specific, not a stable cross-backend error-code protocol.

| Operation | Behavior |
| --- | --- |
| `lock_empty()` | Empty output slot/token |
| `lock_try($out, $path, $shared)` | Replace output on acquisition; true acquired, false contention, exceptions for errors |
| `lock_release($handle)` | Explicit unlock/close, alias invalidation, idempotent |
| `lock_transfer($handle)` | New owner token; old token and aliases become released |
| `process_spawn($executable, $args, $input, $timeout_ms, $cwd)` | Absolute executable, explicit vector of arguments, binary input; return owned handle |
| `process_poll($handle)` | False pending, true complete; caller drives timeout enforcement |
| `process_output($handle)` | Independent snapshot, only after completion and before close |
| `process_stop($handle)` | Request group kill; output remains collectable |
| `process_close($handle)` | Kill/reap unfinished child and release streams; idempotent |

Arguments are explicit, including shared=false, timeout=0 and cwd="" where wanted.
Handles are factory-returned locals in this slice. Generic resource types and
portable named handle fields/signatures are not added. Process outputs expose
stdout_text, stderr_text, exit_code, signal, timed_out and stopped, matching native.
Returned snapshots survive close and do not mutate the cached output.

Native facade sources are separate assembly artifacts. Install them only for a
candidate proof with `install_native_runtime.php OUTPUT --os`, and enable
`filesystem` and `process` project modules. Ordinary assembly does not add these
modules or native sources. Reinstall using the same explicit selection; edited
or unowned artifacts are refused, and omitting --os will not delete owned files.
The converter only adds fixed call mappings and zero-argument factory arity;
it does not resolve OS handles or infer ownership.

## File-lock backend

A private resource plus acquiring PID owns the lock. PHP aliases share that token.
Transfer allocates the replacement first, moves the resource without reopening
or unlocking, then empties the old token. Release explicitly unlocks before close;
destruction in a forked child only closes the inherited descriptor. Public
release/transfer on inherited live tokens throws.

Shared opens read-only; exclusive opens without truncation and may create the
file. Modes request close-on-exec. No release path unlinks/replaces the lock file.
A nonblocking flock distinguishes contention from other failure. Stable local
regular-file identity remains an application requirement. PHP fopen has no
O_NONBLOCK-open option: the backend rejects known special files before open and
checks the resulting descriptor. It does not promise safety against concurrent
replacement of a stable path by a FIFO. PHP does not expose flock/open errno
reliably enough to reproduce the native EINTR retry policy; interruption may be
reported as an operational error. Neither point should be hidden as full OS parity.

## Process backend and launch protocol

Require Linux PHP CLI 8.4+ with PCNTL/POSIX, default SIGCHLD disposition and no
competing reaper of these children. The launcher is a PHP process, not a native
helper/extension. Its extra interpreter startup has not been benchmarked, so no
hot-path performance claim is made.

1. Allocate private mode-0600 capture/input files, unlink immediately, fully write
   and rewind stdin. Launch metadata and two named channels live in a random
   mode-0700 directory. Serialization preserves argument bytes without JSON UTF-8
   coercion; NUL remains prohibited for argv/paths.
2. The launcher opens the control stream close-on-exec, establishes its session/
   group, sets cwd and resets signal dispositions/mask. It reports READY with PID
   and waits for acknowledgment on a separate close-on-exec channel.
3. The parent obtains proc status exactly once while target exec is gated. It
   validates readiness against that PID/group and acknowledges. It never uses
   proc_get_status to poll the target. Temporary channel endpoints prevent an
   early false EOF and blocking opens if the launcher exits.
4. Exec closes the control stream. Explicit setup/exec failure reports an error
   there, distinct from a legitimate target exit 127. The launcher is required
   through PHP -r so its own script descriptor does not survive target exec.
5. Poll uses waitid(WNOWAIT|WNOHANG), checks available exit before the monotonic
   deadline, signals the group while the leader remains waitable, then calls
   proc_close as the sole reaper. Stop/timeout retain distinct first causes.
6. Close kills/reaps unfinished work, clears caches and invalidates aliases.
   Destruction is nonthrowing. Forked copies cannot operate the parent's token.

Private framework streams/channels do not survive exec except intentional stdio
copies. Unrelated preexisting host descriptors (including PHP CLI's entry script
stream) remain a host inheritance concern; this is not a close-all-descriptors API.
Launch is synchronous outside execution timeout. Output is file-backed, not
interactive, and has no quota. At first collection, snapshot file sizes and cache
those bytes. Group kill/reap does not promise arbitrary grandchild reaping or
termination of tasks stuck in uninterruptible kernel operations. Descendants must
remain in the owned group. No background timer, scheduler or Windows backend.

## Proofs and remaining gate

`php tests/portability/os_php.php` exercises separate-process lock contention,
shared readers, transfer/aliases, inherited-token restrictions, parent unlock
while a child holds an inherited descriptor, missing/special files, stable file
identity, nontruncation and destruction. Process cases cover binary/large IO,
literal argv/cwd, exec failures versus exit 127, snapshots/aliases, pending/closed
errors, timeout/stop/late polls, owned descriptor inheritance, descendant cleanup
for normal exit/timeout/stop/close/destruction, forked tokens, SIGCHLD rejection
and repeated-cycle descriptor stability. The fork socket test requires permissions
not available in the default sandbox; it passed outside that restriction.

`python3 tests/portability/os_native.py --results FRESH --target-checkout TARGET`
is the matching facade proof (also selectable as validate.py --native os).
It now passes on `2f0d667f`. Historically, native builds failed because headers declared
`class file_lock_handle`, `class process_handle` and `class process_output` even
though the runtime owns those names as aliases. A three-function PHS reproducer
fails independently of our converter. Neither generated output nor target sources
were patched.

Historical blocked evidence is retained under
`specs/planning/compiler_migration/results/os-candidates-01/`. The successful
facade and cumulative compiler evidence for the repaired target is under
`specs/planning/compiler_migration/results/os-parity-01/`. The OS runner accepts
`--candidate-revision FULL_COMMIT` to test a clean immutable target before changing
the selected pin. Extend cross-backend and negative ownership cases when adopting
real compiler callers; focused success does not close every #231 integration gate.
