# Stream I/O → Process → curl

Doc Status: planning

Audited on `codex/helpers-and-libs` at `feb2b471e`. One proposed Stage 1 goal has three independently selectable modules. Their common requirement is a typed handle whose copies refer to one native state. Each module owns its close, failure and cleanup rules.

| Checkpoint | Donor module catalog | Adjacent release surface |
| --- | --- | --- |
| Stream I/O | 10 names, 12 signatures, one resource type | 11 PHP names including the write alias fputs. Archived JSS has 10 io.* mappings. |
| Process | 5 names, 5 signatures, two types, six output fields | Five separate core CLI/environment/shell helpers. |
| curl | 9 names, 13 signatures, two types, nine response fields | 12 option constants, seven info selectors, and a PHP compatibility layer with different results. |

With general_api enabled, fresh v0.2 PHP source projects rejected io_open, process_poll and curl_init as unknown functions. Selecting the corresponding module instead reported unknown submodule for each. An independent registry lookup resolved fs_get as a positive control. The compiler's host PHP process and filesystem functions do not constitute application bindings.

## 1. Stream I/O

| General name | Proposed PHP spelling | Proposed source result |
| --- | --- | --- |
| io_open(path, mode) | fopen | result_or_false<file_stream> |
| io_tell(stream), io_write(stream, bytes) | ftell, fwrite and fputs | result_or_false<int> |
| io_read(stream, length), io_read_line(stream[, length]) | fread, fgets | result_or_false<string> |
| io_seek(stream, offset[, whence]) | fseek | nullable<int>, present zero on success |
| io_rewind, io_flush, io_eof, io_close | rewind, fflush, feof, fclose | bool |

Use an opaque file_stream owner with a shared cursor. A copied handle sees close through any alias. The donor uses a polymorphic resource base with one published kind. A concrete handle suffices here. Bind operations to the extracted handle. Borrow strings and handles in C++, pass lengths and offsets by value, and perform validation and stdio inside the archive. Route both write spellings to the same target.

The archived fread/fwrite/fclose pages describe nullable returns, while the shipped PHP wrappers and neutral catalog use falseable byte/count results and plain close status. The table proposes the shipped contracts. Keep the donor's r/w/a families with binary and update variants, reject embedded NUL in OS paths as v0.2 filesystem does, and preserve NUL in data. Keep width one for fgets as a successful empty read without cursor movement. EOF before a line gives false. A byte read at EOF gives a successful empty string. Closing a closed alias is an invalid-state operation. Seek clears EOF on success. Validate the whence and offset before native conversion. Publish usable seek origins for the general surface and the PHP SEEK_SET, SEEK_CUR and SEEK_END spellings. The archived path/mode/access flags describe the implementation and have no catalog field bindings. File locks already have a separate inventory item with transfer semantics.

A temporary Clang donor executable wrote four bytes including NUL, rewound, read through an alias, checked width one and offset zero, compared line EOF with byte EOF, and observed rejection through the closed alias. All checks passed. Reuse this sequence from v0.2 source, adding invalid mode, missing path, partial last line and failed seek.

[Stream catalog](../../../v0.1/runtime/metadata/modules/io.json) · [Native provider](../../../v0.1/runtime/include/core/stdio.hpp) · [PHP wrappers](../../../v0.1/runtime/include/lang/php/support/php_stdio.hpp) · [Older fread contract](../../../v0.1/specs/builtins/filesystem/fread.md) · [Filesystem contract](../../filesystem_module.md)

## 2. Managed Process

Publish process_start(executable, args, input, timeout_ms[, cwd]) returning result<process_handle>. Poll, stop and close return result<bool>. Process_result returns result<process_output>. Bind the five names on the general surface. Start borrows strings and a vector<string>. Its native call never needs an application stream handle. The Linux donor uses private seekable capture files for binary stdin, stdout and stderr.

Preserve the distinction between a pending successful false, a completed nonzero exit, and an error starting a child. A missing executable and a child that exits 127 must produce different results. Preserve literal argv, absolute executable and optional absolute cwd, monotonic poll-driven timeouts and exclusive wait ownership. Stop leaves output collectable. Close is idempotent and can block to reap. Copies alias one process state. Each collection produces an independent snapshot with stdout_text, stderr_text, exit_code, signal, timed_out and stopped. Recommend a v0.2 value record for the snapshot. Retain explicit stop versus timeout and the donor's Linux platform boundary.

A temporary Clang caller launched /bin/cat, round-tripped embedded NUL, polled to completion, closed the handle, checked rejection through the alias and read the retained output. It passed. Implementation acceptance should also cover separate stderr, exit 127 versus exec failure, timeout versus stop, and cleanup before reusing a process ID. The donor has fixtures for the wider lifecycle.

The core donor catalog separately publishes getenv, shell_exec, cli_argc, cli_argv and cli_args. The latter two return equivalent mixed argument-list snapshots including argv[0]. Current v0.2 generates int main() with no argument transport. Recommend tracking these five as a separate core checkpoint. Adding them here would also change startup or introduce shell-command semantics. The compiler's own subprocess calls do not supply either source contract.

[Managed process contract](../../../v0.1/specs/builtins/process.md) · [Process catalog](../../../v0.1/runtime/metadata/modules/process.json) · [Native lifecycle fixture](../../../v0.1/tests/runtime/native/test_process.cpp) · [Core catalog](../../../v0.1/runtime/metadata/modules/core.json) · [v0.2 entry emission](../../../compiler/05_backend/cpp/program.php)

## 3. curl

The strict neutral catalog has init with zero or one URL, setopt with four value types (string, int, bool, vector<string>), exec, getinfo(handle, selector), errno, error, reset, close and strerror. The typed contracts return result<curl_handle>, result<bool>, result<curl_response> and result<mixed> where fallible. The diagnostic getters and strerror return plain values. Response fields are status_code, headers (vector<string>), body, effective_url, content_type, total_time_ms, header_size, request_size and redirect_count.

The release PHP adapter returns a falseable handle from init, bool from setopt/reset/close, and a falseable body string from exec. Getinfo accepts one or two arguments and returns mixed (info table, selected scalar, or boxed false). It also accepts a mixed option value for setopt. Keep that dynamic inspection in the PHP adapter. Preserve the release's bounded RETURNTRANSFER behavior of returning the body. Both typed and PHP callers should be able to distinguish an empty successful transfer from failure, and a retained response must remain usable after reset/close. Recommend a value snapshot with read-only response fields so callers cannot mutate cached getinfo state.

**Naming decision.** Both donors use curl_exec(handle). In v0.2 PHP, flavor lookup precedes the additive general lookup. A program enabling the general surface would still select the PHP body-returning operation. Expected-return-type overload resolution would make the same expression mean different things. Recommend leaving PHP curl_* intact and giving the checked general family a distinct prefix, for example curl_request_init, curl_request_setopt and curl_request_exec throughout. Both bind directly to scpp::curl operations. Publish the donor option and selector identities for general callers too. The present compiler's runtime integer constant lookup sites are PHP-specific. Exercise a C++ general caller with a symbolic option, and a PHP opt-in caller using both exec forms in one project.

Keep libcurl and its option translation private. The donor uses local option IDs 1–12 and info IDs 101–107. Its module links curl and its selector results require boxed storage. Discover the toolchain's library at configure time and publish the selected link artifact to the native manifest. Validate overloads on argument three, retain byte lengths for POST and response bodies, and return native callback failures without unwinding through libcurl. Use an offline file URL for binary transfer and a loopback HTTP fixture for response codes, redirects where enabled, header lines and binary POST.

The installed pkg-config libcurl reported 8.5.0. The archived Clang curl fixture passed using a local file URL, binary content, info queries, reset and closed-handle errors. Linking the donor provider and libcurl alone left mixed-value symbols unresolved. Adding the donor mixed implementation resolved them. The loopback HTTP fixture was inspected and remains an implementation acceptance test.

[curl catalog and IDs](../../../v0.1/runtime/metadata/modules/curl.json) · [Strict and PHP contracts](../../../v0.1/specs/builtins/curl/first_pass.md) · [PHP adapter](../../../v0.1/runtime/include/lang/php/php_curl.hpp) · [HTTP fixture](../../../v0.1/my-try/tests/curl_runtime.cpp) · [Current source lookup](../../../compiler/types/availability.php)

## Integration and exit criteria

Reuse module-gated metadata, opaque value handles, published fields, concrete result carriers and narrow generated headers from the JSON and regex work. Add only the concrete handle/result pairs each family uses. Keep cleanup in private native implementations. The three donor catalogs depend on core individually. Enabling Stream I/O should not pull in child-process code or libcurl. Keep JS and Python natural spellings for their dedicated flavor reviews, recording the archived JSS io.* mappings.

For each checkpoint, build an executable from its emitted link manifest, exercise the distinguishing states above, disable and restore the module in a resident compiler, and compare recovery with a fresh build. Compile a minimal caller and each selected public header on its own. Check their dependencies for foreign flavor wrappers, stdio implementation details, process internals and curl/curl.h. Finish with a single mixed-flavor project selecting all three modules. Performance measurements belong to Stage 2.

## Stage 1 verification

The [published v0.2 contracts](../../stream_process_curl_modules.md) cover all three checkpoints.
Local Clang builds exercised each module independently and a PHP/C++ application using all three.
The executable linked from the emitted manifest. Stream bytes written through PHP passed through
curl and a managed child, then a C++-flavor caller checked the captured bytes.

The curl HTTP fixture used only loopback and verified redirect count, a 404 response,
request headers, binary POST and response header lines. A separate file transfer covered
success with an empty body. The module tests remove and restore manifest selections in a
resident compiler and compare the recovered C++ with a fresh compilation. Clang also checked
every published module header independently. JSON and curl passed together with their shared
checked mixed result. The original build profile passed publication gates and generator parity
after restoration. Execution and compile-time optimization measurements remain Stage 2 work.
