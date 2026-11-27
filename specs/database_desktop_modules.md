# mysqli, UI and Webview

Doc Status: normative

A project selects each provider through its runtime module dependencies. Webview also selects UI. PHP projects can use mysqli receiver syntax when mysqli is selected. General database and desktop names follow the project general-API visibility rules. Generated bindings call the native target directly. Native SDK types stay in the compiled providers.

## mysqli

A PHP caller constructs a connection with `new mysqli(host, user, password, database, port, socket)`. Trailing arguments are optional. Construction preserves connection diagnostics even on failure. General callers use the checked `db_connect` result. Connection and statement status fields are read-only.

| Receiver | Operations | Status |
| --- | --- | --- |
| Connection | query, prepare, set_charset, begin_transaction, commit, rollback, close | connect_errno, connect_error, errno (also errno_code), error, insert_id, affected_rows |
| Statement | bind_param, execute, get_result, close | errno (also errno_code), error, insert_id, affected_rows |
| Result | fetch_row, fetch_assoc | num_rows |

A query returns a result handle for rows, true for successful execution without rows, or false on failure. Prepare and statement result retrieval return a handle or false. A fetched row is packed or keyed according to the fetch operation. SQL NULL occupies its column in that row. End of input returns a null dynamic value. Materialized result handles retain their rows across later executions or a connection close. Closing a connection invalidates its statements, including aliases.

PHP `bind_param(types, slots...)` retains named caller slots and reads their current values on each execute. The compiler must prove a fresh, confined local statement and stable parameter storage throughout use. The first implementation accepts ordinary synchronous frames and named integer, float, string or mixed slots. A guard observes the prepare result. A checked extraction into an exact statement local gives access to the same owner, with or without a guard. Both handles must stay confined. Publishing either handle, before or after binding, fails preparation. Property arguments, temporaries and shorter-lived slots cannot be retained. The general `db_execute(stmt, types, values)` consumes a packed parameter list for one execution. The type codes are i, d, s and b. The [publication](../runtime/metadata/mysqli_publication.json) gives each general spelling and its typed signature.

## UI

The GTK provider owns an application event queue. Window operations and event pumping run on the UI thread. Poll pumps pending native work without blocking. Next-event removes the oldest queued event and returns an empty nullable when the queue is empty. Closing a window is visible to its aliases. A delivered window event keeps its window handle alive. Exit stops polling and clears queued events. A UI-only project requires GTK 3. [UI publication](../runtime/metadata/ui_publication.json)

## Webview

A window hosts one view. An explicitly supplied HTML document or local application folder is app-owned content. Local app loading starts at index.html and serves assets beneath the chosen folder. The page can call `window.scpp.invoke(command, payload)`. The host receives a webview_message event with a request ID, command and JSON payload. It can reply once with a JSON value or an error. Page replacement, navigation and close invalidate outstanding requests. Evaluating a script is available only for app-owned content.

Loading an HTTP or HTTPS URL creates a fresh browsing context without native command handlers. Ordinary website navigation stays within the website context. A privileged page confines navigation and scripts to app-owned content. The Linux provider requires WebKitGTK 4.1 (2.40 or later) and JSON-GLib. [Webview publication](../runtime/metadata/webview_publication.json)
