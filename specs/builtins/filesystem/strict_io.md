# Strict IO handles
Doc Status: normative

The strict `io` module exposes shared `resource_handle` values backed by the
existing file-stream resource provider. Copying a handle shares its stream,
cursor and closed state. Closing any alias closes that stream; the final owning
handle also releases an open stream on destruction, including exceptional unwind.
Handles do not expose resource fields or public source constructors.

| Function | Return |
|---|---|
| `io_open(string path, string mode)` | `result_or_false<resource_handle>` |
| `io_seek(resource_handle handle, int offset [, int whence])` | `?int` |
| `io_tell(resource_handle handle)` | `result_or_false<int>` |
| `io_read_line(resource_handle handle [, int length])` | `result_or_false<string>` |
| `io_read(resource_handle handle, int length)` | `result_or_false<string>` |
| `io_write(resource_handle handle, string data)` | `result_or_false<int>` |
| `io_rewind(resource_handle handle)` | `bool` |
| `io_flush(resource_handle handle)` | `bool` |
| `io_eof(resource_handle handle)` | `bool` |
| `io_close(resource_handle handle)` | `bool` |

Open failure returns the false sentinel. Invalid modes, wrong resource kinds,
empty handles and closed-handle operations follow the provider's runtime-error
contract. Seek defaults to `SEEK_SET`, returns zero on success, and null on an
operational seek failure. Explicit whence uses the target platform's stdio values.

Line reads preserve bytes, including embedded NUL. They stop after newline or
EOF. An explicit positive length is a buffer width: at most `length - 1` bytes
are returned. Width one returns a present empty string without advancing;
nonpositive lengths reject. EOF before any byte returns false, while a partial
final line remains present. Byte reads accept zero length and return a present
empty string; negative lengths reject. EOF is observed through the stream's
stdio state rather than predicted from its position.

Calls retain the evaluated handle while subsequent arguments are evaluated.
Replacing a source handle in a later argument does not retarget the earlier
handle snapshot. Wrapper state, required extraction and `take` behavior are
unchanged by concrete prelowering.

`runtime/metadata/modules/io.json` owns strict source exposure; implementations
remain in `runtime/include/core/resource.hpp` and `runtime/include/core/stdio.hpp`.
Raw descriptors, asynchronous IO and new resource kinds are outside this surface.
