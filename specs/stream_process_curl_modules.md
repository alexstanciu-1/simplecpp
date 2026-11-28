# Stream I/O, managed Process and curl

Doc Status: normative

Projects select `io`, `process` and `curl` individually under `dependencies.runtime.modules`.
Build the matching archives with `SCPP_ENABLE_IO`, `SCPP_ENABLE_PROCESS` and
`SCPP_ENABLE_CURL`. PHP publishes the named flavor operations below.
C++ uses the general surface. Other flavors can use it with the project
`general_api` opt-in. The compiler binds each spelling to its native operation.
Copying a handle shares its state. Releasing the final handle releases its native resources.

## Stream I/O

| General | PHP | Source result |
| --- | --- | --- |
| io_open(path, mode) | fopen | result_or_false<file_stream> |
| io_tell(stream) | ftell | result_or_false<int> |
| io_write(stream, bytes) | fwrite, fputs | result_or_false<int> |
| | fputcsv(stream, fields[, separator, enclosure, escape, eol]) | result_or_false<int> |
| io_read(stream, length) | fread | result_or_false<string> |
| io_read_line(stream[, width]) | fgets | result_or_false<string> |
| io_seek(stream, offset[, origin]) | fseek | nullable<int> |
| io_rewind, io_flush, io_eof, io_close | rewind, fflush, feof, fclose | bool |

Strings carry bytes, including NUL. Open accepts r/w/a and their binary and update
variants. An invalid mode raises ValueError. A path containing NUL or an OS open
failure returns false. Reads, writes, seeks and close use the extracted handle.
All aliases share the cursor and observe close. A closed handle, incompatible
read/write access, negative byte count or nonpositive line width raises ValueError.
A line read includes its newline when encountered and consumes at most width
minus one bytes. Width one succeeds with an empty string. EOF before a line gives
false. A byte read at EOF succeeds with an empty string. Seek defaults to SEEK_SET.
SEEK_SET, SEEK_CUR and SEEK_END are available as source constants. Success clears
EOF and returns present zero. Invalid origin, offset outside native range or a failed
seek returns null. A write reports the count actually written.

### PHP CSV

`str_getcsv(record[, separator, enclosure, escape])` is a core string helper. It
returns one native `vector<string>` per input record. A completely blank record
returns one empty string. The parser also preserves quoted empty fields, empty
columns, doubled enclosures, embedded line breaks and NUL bytes.

`fputcsv` accepts a writable file stream and a `vector<string>`. It writes fields
in order and returns the byte count including the ending (a newline by default).
Both helpers use single-byte separators and enclosures, defaulting to comma
and double quote. The default escape is backslash. An empty escape disables it.
CSV scanning uses byte comparisons and ASCII whitespace. For round trips of
fields containing backslashes and quotes, pass an empty escape to both helpers.

## Managed Process

The general surface publishes process_start(executable, args, input, timeout_ms[, cwd])
returning result<process_handle>. Executable, input and cwd are strings, args is a
vector<string> and timeout_ms is an integer. Linux launches the absolute executable
directly using literal arguments. An optional nonempty cwd must be absolute.
Executable paths, cwd and arguments reject embedded NUL. Standard input and both
captured outputs support binary data. Launch requires the default SIGCHLD disposition
and exclusive ownership of child waits. Unsupported hosts return a checked error.

process_poll, process_stop and process_close return result<bool>. A pending poll
succeeds with false. A zero timeout disables timing out. Poll observes elapsed
monotonic time and initiates termination when the timeout expires. Explicit stop
preserves collectability. Close reaps as needed, releases capture storage and
succeeds on repeated close. Child exit status, including 127, is observable
separately from an error starting the executable.

process_result requires an open, completed process and returns result<process_output>.
Its read-only fields are stdout_text, stderr_text, exit_code, signal, timed_out and
stopped. A signal exit has exit_code −1. A snapshot retains its contents after
close and after releasing the handle.

## curl

The general surface publishes curl_request_init([url]), curl_request_setopt(handle,
option, value), curl_request_exec(handle), curl_request_getinfo(handle, selector),
curl_request_errno, curl_request_error, curl_request_reset, curl_request_close and
curl_request_strerror. Init returns result<curl_handle>. Setopt, reset and close
return result<bool>. Exec returns result<curl_response>. Getinfo returns result<mixed>.
Errno returns int. Error and strerror return string. PHP uses the matching curl_*
spellings. Its init returns a falseable handle, exec a falseable body string, and
setopt/reset/close return bool. PHP getinfo returns a boxed scalar, a table when the
selector is omitted, or boxed false on error.

| Option | Accepted value |
| --- | --- |
| CURLOPT_URL, CURLOPT_CUSTOMREQUEST, CURLOPT_USERAGENT | Text string without NUL |
| CURLOPT_POSTFIELDS | Binary string including empty |
| CURLOPT_HTTPHEADER | vector<string> of header lines without NUL |
| CURLOPT_TIMEOUT, CURLOPT_CONNECTTIMEOUT | Nonnegative integer seconds |
| CURLOPT_RETURNTRANSFER, CURLOPT_POST, CURLOPT_FOLLOWLOCATION, CURLOPT_SSL_VERIFYPEER | Bool or integer |
| CURLOPT_SSL_VERIFYHOST | Integer 0 or 2 |

Boolean option values use the integer overload's zero/one interpretation. PHP
also accepts mixed and inspects its kind, including packed strings for headers.
RETURNTRANSFER retains the body-returning behavior in this surface. Empty
successful responses carry an empty body. HTTP error status codes still produce
a response on successful transport. Redirect following defaults to off and TLS
verification defaults to on.

Response snapshots have read-only fields status_code, headers, body, effective_url,
content_type, total_time_ms, header_size, request_size and redirect_count.
Headers are nonempty lines with trailing CR/LF removed. The seven selectors are
CURLINFO_RESPONSE_CODE, CURLINFO_EFFECTIVE_URL, CURLINFO_CONTENT_TYPE,
CURLINFO_TOTAL_TIME_MS, CURLINFO_HEADER_SIZE, CURLINFO_REQUEST_SIZE and
CURLINFO_REDIRECT_COUNT. CURLINFO_HTTP_CODE is an alias of CURLINFO_RESPONSE_CODE. The PHP table uses the corresponding lowercase keys and
also includes http_code. Source constants have module-local identities. Their
translation and the libcurl headers belong to the native provider. Reset clears
options, diagnostics and cached response. Close invalidates aliases. Returned
snapshots survive both operations.

## PHP process observations and shell output

Selecting the process module exposes `memory_get_usage([bool]): int` and
`memory_get_peak_usage([bool]): int`. They report the current process RSS and
peak RSS in bytes on Linux. Either value of the optional flag selects the same
process metric. An unavailable measurement returns zero.

`shell_exec(string): result_or_false<string>` runs a command through the Linux
host shell and captures stdout byte for byte. Stderr is inherited. An empty
command or an embedded NUL raises an Exception before execution. Failure to
open, read or close the pipe returns false. A successfully collected stream
can be empty, and a nonzero child exit status preserves the captured output.
