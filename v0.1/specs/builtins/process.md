# Managed child processes

Doc Status: normative

The optional `process` module provides Linux batch-tool execution with explicit
arguments, binary input and separate captured output. Enable it in
`prism.json` under `runtime.modules`. Native CMake enables `scpp_process` with
`SCPP_WITH_PROCESS=ON`; public declarations are in `scpp/process.hpp`.

## API

| Strict PHS operation | Return type |
| --- | --- |
| `process_start(string executable, vector<string> args, string input, int timeout_ms, string cwd = "")` | `result<process_handle>` |
| `process_poll(process_handle handle)` | `result<bool>` |
| `process_result(process_handle handle)` | `result<process_output>` |
| `process_stop(process_handle handle)` | `result<bool>` |
| `process_close(process_handle handle)` | `result<bool>` |

Native operations are `scpp::process::{start,poll,collect,stop,close}`. Handles and
outputs use ordinary shared class handles in PHS. `process_output` has public
fields `stdout_text`, `stderr_text` (string), `exit_code`, `signal` (int),
`timed_out`, `stopped` (bool). Text field names avoid C stdio macro collisions.
Each result call returns an independent snapshot; changing it does not change
future result calls. Snapshots already returned survive handle close.

`process_handle` and `process_output` are public runtime aliases, not user class
declarations. They are valid at ordinary function/method parameter and return
boundaries and in typed fields, as well as local variables. Generated headers
use the runtime-provided declarations and must not forward-declare them as classes.

## Launch and streams

Require an absolute executable path, absolute nonempty cwd, and nonnegative
timeout. Empty cwd inherits the working directory; timeout zero disables the
deadline. Paths and arguments cannot contain NUL. Args exclude argv[0] and may
contain empty strings or shell metacharacters, which are passed literally. No
shell interpretation or PATH search is performed. Environment is inherited;
concurrent environment mutation during launch is outside this contract.

Before launch, three private mode-0600 temporary files under `/tmp` are opened
with close-on-exec and immediately unlinked. Stdin is fully written and rewound.
Only intentional standard-stream duplicates survive exec. Capture files are
regular seekable files, not pipes or terminals; this avoids pipe-capacity
deadlocks for batch tools but does not supply interactive/streaming IO.
Input and output preserve binary bytes. There is no output quota; host storage
and memory limits apply. Child write failures may be visible only in child status
or its output, not as an error detected by the parent.

The child creates its process group before exec. A close-on-exec reporting pipe
separates explicit exec/setup failure from a legitimate exit code 127. The child
path uses async-signal-safe operations and does not allocate or unwind C++.
Signals are blocked around fork, restored in the parent, and set to default
(disposition and empty mask) before executing the child program.
Launch/setup is synchronous and outside the execution timeout. Setup and OS
failures return errors; allocation failures retain normal C++ exception behavior
with acquired resources owned by RAII. Non-Linux operations report unsupported.

## Polling, timeout and ownership

One designated application owner controls each handle. Copies alias one state.
Concurrent lifecycle calls are unsupported. The host must retain the default
SIGCHLD disposition, must not enable SA_NOCLDWAIT, and must not reap these children
through another handler/thread/library. Start rejects nondefault SIGCHLD state.

Poll returns false while the child or direct-child cleanup remains unfinished,
true on completion, and errors for invalid state or OS failure. It does not block
waiting for exit or read the capture files. Available child exit status is
observed before checking the monotonic deadline. Positive timeout is measured
from successful launch; expiry requests SIGKILL for the owned group. Enforcement
is caller-driven: poll regularly. There is no background timer or hard deadline
while the caller does other work.

Stop requests immediate group SIGKILL without waiting and leaves output
collectable after completion. It observes available exit/deadline state first.
Repeated stop on an open handle succeeds. First observed cause wins: timeout and
explicit stop are distinct; normal nonzero exits remain successful API results.
Normal exit records its exit code and signal zero. Signal termination records
exit code -1 and its signal number.

At completion, observe the leader with `waitid(WNOWAIT)`; signal the group while
the leader is still owned and waitable, then reap it. Repeat the group signal at
leader exit even if stop/timeout signalled earlier. Never signal a stored PID/PGID
after reaping. ECHILD marks wait ownership lost and prohibits further signalling.
Group members must remain signalable by their parent and must not daemonize or
change groups/sessions. Background work may
not intentionally outlive the handle.

The API reaps the direct child, not arbitrary grandchildren. Completion means
SIGKILL has been sent to remaining group members and the leader has been reaped;
it does not promise every descendant is already reaped or that an uninterruptible
kernel operation has finished. Result collection snapshots each file's size at
its first read and caches those bytes. It does not wait for grandchildren to
close descriptors. This makes capture behavior explicit for descendants whose
termination is delayed by the OS.

Result requires completion and an open handle. It may block on file reads.
Before completion or after close it reports an invalid-state error. Close kills
unfinished work, reaps the direct child, releases capture files/cache and
invalidates all aliases. It is idempotent, including an empty handle, and may
block in waitpid; no hard cleanup deadline is promised. Destruction is a
nonthrowing fallback. A forked copy is not another child owner: public lifecycle
operations reject it, while its destructor only closes inherited descriptors.

General schedulers, detached jobs, streaming, graceful TERM policy, Windows,
resource compatibility and PHP portability-framework bindings are outside this
runtime slice. Existing `shell_exec` remains unchanged.

## Builtin reference entries

- [process_start](process/process_start.md)
- [process_poll](process/process_poll.md)
- [process_result](process/process_result.md)
- [process_stop](process/process_stop.md)
- [process_close](process/process_close.md)
