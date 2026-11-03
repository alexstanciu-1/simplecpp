# App-local frontend service

Doc Status: supporting

```sh
XDEBUG_MODE=off php v0.2/compiler/main.php frontend v0.2/samples/my-try-simple
# Edit any selected source and repeat the command above.
XDEBUG_MODE=off php v0.2/compiler/main.php frontend v0.2/samples/my-try-simple status
XDEBUG_MODE=off php v0.2/compiler/main.php frontend v0.2/samples/my-try-simple stop
```

This Linux/WSL host uses ordinary PHP, Unix sockets and `/usr/bin/setsid`.
The argument is an application directory containing its module manifest. Its
canonical directory identifies the service. A new service performs a full frontend;
subsequent requests call the same incremental frontend with the retained session.
No preparation, lowering, native build or watcher is introduced.

## Ownership and lifecycle

`host/frontend_service.php` owns the client transport and sequential service loop.
`host/frontend_daemon.php` is its detached process entry point. The existing
`Project_Compiler::frontend()` continues to own compilation orchestration; there
is no second change-detection or parsing implementation in the service.

State lives in the ignored application-local `.scpp/` directory:

- `compiler.sock`: Unix socket, removed on normal stop or idle expiry.
- `compiler.lock`: lifetime lock; its inode stays after exit to prevent split locks.
- `compiler.log`: detached startup diagnostics, retained after exit.

The client tries to connect and starts a detached process if needed. A lifetime
`flock` guarantees one owner when clients launch concurrently. Losing starters
exit; clients wait up to five seconds for the winning service. A stale socket may
only be removed after acquiring the lifetime lock. Non-socket occupants and
symlink service paths are rejected. Newly created service directories use mode
0700 and the socket uses 0600. No TCP listener is opened. Relative socket paths
allow long canonical application paths without exceeding Unix socket path limits.

The service remains independent of the CLI client and processes requests one at a
time. One hour after the last completed request (including status), it exits.
Compilation time does not consume the idle budget. Incomplete requests have a
five-second read timeout. Status/stop do not start an absent service. The client
waits up to one hour for a compilation response. A crashed service loses its
in-memory cache; the next invocation starts fresh and recovers the stale socket.

JSON reports include PID, successful generation number, full/incremental mode,
frontend elapsed milliseconds, source/symbol counts, and source change/reuse
counts. The reported time covers `Project_Compiler::frontend()` only; process
startup, IPC and retired-session garbage collection are outside that timer.
Unchanged roots/tokens are checked for exact object identity before reporting reuse.
A failed frontend request returns a diagnostic without advancing the generation
or replacing the last successful publication; subsequent requests can retry.

There are no external AST readers in this sequential host. After reporting a
successful generation, it detaches the previous-session link and removed-source
records, then collects unreachable cycles. It retains the current AST/index plus stable symbol tombstones.
The general frontend API's previous-session behavior is unchanged; this host's
retirement policy is not a native concurrent lifetime solution.

Compiler PHP/generated-code edits require an explicit `stop` and restart; hot code
reload, automatic version negotiation and cross-checkout service sharing are not
part of this slice. Filesystem change detection retains the existing mtime+size
limitation. The normal executable `project` command remains separate.

## Proof

```sh
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/frontend_service.py
```

The test copies all 257 `my-try-simple` source paths to a disposable application
and invokes the real CLI in distinct processes. It verifies concurrent startup
converges on one PID, full then unchanged runs, one edited file with 256 reused
roots, failed-candidate preservation and retry, separate-app isolation, explicit
stop/restart, stale socket recovery after a crash, and idle timeout/reset. The
production default is checked as 3600 seconds; the same server loop is exercised
with a one-second deadline to avoid a one-hour test. All test services are stopped.
The original sample is unchanged. A sandbox must permit local Unix sockets.

Observed local run: PID 601138 performed the first 257-file frontend in 24.94 ms,
one-file update in 4.38 ms (256 reused), and unchanged invocation in 4.30 ms
(257 reused). These are single test observations, not benchmark medians.

No compilation-session, source-unit, token or AST structure changes were needed.

Stable symbol entries now survive successful frontend updates. See
[stable-symbol lifetimes](stable_symbols.md); this service still does not run
incremental preparation or lowering.
