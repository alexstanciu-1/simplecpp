# Async/task helpers, mysqli, UI and Webview

Doc Status: planning

Scope review at `dbddc8417`. The current [runtime manifest](../../../runtime/module.json) selects none of these as optional modules. Cooperative async and joined parallel work already have language contracts. The proposed dispositions below are for discussion.

## Async/task helpers

| Donor surface | v0.2 disposition |
| --- | --- |
| await, async_wait, async_sleep_ms | Covered by the direct-call and suspension rules. Sleep suspends inside an async function. |
| task_run (184 signatures) | Compare individual workloads with existing joined vector/hash segments and runtime-sized batches. Results currently travel as submission-order integers. Donor callback, boxed collection and result-target signatures are not all equivalent. |
| task_start (184), task_join, task_done, task_cancel, task_status, task_progress | Background batch ownership would be new. A caller can continue before join. A proposal needs to define captured data lifetime, error delivery, timeout, cooperative stop and cleanup of an abandoned batch. Defer to concurrency work. |
| task_run_publish, task_run_publish_unordered, task_synchronize, task_set_status, progress accessors | These concern publishing intermediate results, serializing work and observing an active batch. Defer with the consumer that needs that behavior. |
| Pool size, publication locking and counters | Keep tuning and measurement with the pool implementation. |

The pinned catalog has 406 signatures and 28 spellings, including seven progress accessors. A future acceptance case for background work would keep a worker pending, do observable caller work, request stop, then join and observe the documented result. A sleeping coroutine alone cannot establish that behavior.

[Async contract](../../async_await.md) · [Joined batches](../../compiler_entry_v0_2.md#runtime-sized-parallel-batches) · [Task catalog](../../../v0.1/runtime/metadata/modules/tasks.json) · [Donor batch lifecycle](../../../v0.1/specs/builtins/tasks/first_pass.md)

## mysqli

This is a release PHP receiver API over a MariaDB Connector/C provider. My-try has an isolated database verification harness but no mysql module in the pinned catalog. Preserve the separation between a database backend, the general source API and PHP's natural names. A provider does not settle the general API's names or semantics.

| Receiver | Release inventory | Distinguishing behavior |
| --- | --- | --- |
| mysqli | Connecting constructor, query, prepare, close, set_charset, begin_transaction, commit, rollback. Six fields for connect/current diagnostics, insert ID and affected rows. | A SELECT with zero rows still returns a result object. Successful DML returns true. A query error returns false. The constructor reports connection failure through connection status. |
| mysqli_result | fetch_assoc, fetch_row and num_rows | Packed or string-keyed dynamic rows. End of rows is dynamic null. One row containing a SQL NULL cell is still a row. |
| mysqli_stmt | bind_param, execute, get_result, close. Four fields for diagnostics, insert ID and affected rows. | Binding reads the caller's current values at execute time. Reusing the statement after changing a variable must use the new value. The donor treats b as string binding. |

**Proposed scope.** Take the bounded family as an optional backend, including transactions and prepared statements. Design the general database operations explicitly and expose mysqli receivers for PHP. Keep Connector/C declarations in the archive. Defer additional PHP methods, other database engines and guessed JS/Python aliases. Decide the PHP errno property mapping in metadata (the donor native field is errno_code).

**Prerequisite.** Prove safe parameter and connection lifetimes before admitting bind_param. The donor stores callbacks reading referenced slots and a raw connection owner on the statement. A call-site temporary does not keep either alive for later execute. Decide whether to reject escaping bindings or retain eligible storage. A general execute-with-owned-values operation could be simpler, but would have a different source contract. Also verify a concrete three-state query carrier can carry a result handle and tag true/false distinctly.

**Acceptance.** Use a private MariaDB instance over a Unix socket. Compile from Simple C++ and test failed connect, the three query outcomes, both fetch forms, SQL NULL versus exhaustion, repeated execution after a bound value changes, commit versus rollback, and statement use after closing or releasing the connection.

[Donor contract](../../../v0.1/specs/builtins/db/mysqli.md) · [Binding and owner implementation](../../../v0.1/runtime/include/lang/php/support/php_mysqli.hpp) · [Private server harness](../../../v0.1/my-try/tests/mysql_isolated.py) · [Concrete carriers](../../carrier_slice.md)

## UI

The 12 general calls cover app create/poll/next-event/exit, window create/show/close and event type/window/text/message/URL. The three types are app, window and event. The historical event-to-webview accessor is registered by the Webview module. Polling pumps native events. An empty queue is an ordinary outcome and needs a typed absence contract distinct from failure. Define which operations require the UI thread and how long a delivered event keeps its window alive.

**Proposed scope.** Keep UI independently selectable. If a desktop host is wanted, implement the bounded Linux GTK window/event family first. Defer widget toolkits, menus, layout systems and other platform promises. A useful proof opens and shows a window, observes a close event for that window, drains the queue and exits. Verify that the UI-only caller compiles and links without a Webview SDK or headers.

[UI catalog](../../../v0.1/runtime/metadata/modules/ui.json) · [Native event loops](../../../v0.1/runtime/include/modules/ui/ui.cpp)

## Webview

The 12 catalog calls cover create/close, load HTML/URL/local app, evaluate script, recover the view from a UI event, read a bridge message's ID/command/JSON payload, and reply with success/error. It depends on UI. The private native enqueue helper and platform attachment hooks are outside the source catalog.

**Proposed scope.** If the use case is a local HTML application, take a Linux WebKitGTK provider with the full local load/event/request/reply path. A display-only page needs fewer facilities. Decide separately whether remote navigation is required and which documents may use the native bridge or receive evaluated scripts. Loading a URL need not confer those capabilities. Defer mobile host integration, other desktop backends, custom asset schemes and packaging.

**Acceptance.** Load a local fixture, observe navigation completion, send two uniquely identified requests from the page and verify the correct success and error replies. Exercise missing assets, close with an outstanding request and operations through a closed alias. If remote navigation is approved, verify the agreed bridge boundary across a navigation. The donor already has a headless-display harness for the local flow.

[Webview catalog](../../../v0.1/runtime/metadata/modules/webview.json) · [Source caller with replies](../../../v0.1/my-try/tests/fixtures/ui_webview/enabled.phs.in) · [GTK/WebKitGTK harness](../../../v0.1/my-try/tests/ui_webview_enabled.py)

## Evidence boundary

This audit inspected contracts, catalogs, providers and fixtures. Local tool discovery found libmariadb 3.3.17, mariadbd, mariadb-install-db, Xvfb and dbus-run-session. pkg-config did not resolve the donor's gtk+-3.0 or webkit2gtk-4.1 SDKs on this host. No live database, GUI or concurrency workload was run for this audit.

## Implementation evidence

The local MariaDB fixture exercised transactions, statement reuse after mutating bound slots, SQL null, binary data, floating-point and independent result lifetime. The compiler rejected unproven borrowed slots. On dev002, generated callers ran under Xvfb against GTK 3.24.41 and WebKitGTK 2.52.6. A standalone UI executable delivered its close event and linked independently of WebKit. The combined caller exchanged success and error replies with a local page, switched to a loopback website, and verified that the website rendered without either native command entry point. The [active module contracts](../../database_desktop_modules.md) record the resulting boundary.
