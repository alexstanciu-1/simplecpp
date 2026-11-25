# Filesystem / stdio builtins â€” first pass
Doc Status: normative
This document defines the first-pass narrowed contract for the Prism++ / Simple C++ filesystem and stdio builtins.

See also:
- `specs/builtins/filesystem/README.md`
- one-contract-per-builtin files in `specs/builtins/filesystem/`
- `docs/filesystem_builtins.md`

## Module split

- `runtime/include/scpp/php_resource.hpp`
- `runtime/include/scpp/php_stdio.hpp`
- `runtime/include/modules/filesystem/filesystem.hpp` (`scpp::fs`)

Support implementations live in matching `runtime/include/scpp/support/*` files.

The goal is to keep filesystem and stdio out of the generic `php.hpp` surface so these APIs stay isolated and easier to evolve.

## Resource model

`fopen()` returns an `result_or_false<resource_handle_t>` file-stream handle.

The resource wrapper has an explicit `kind`.
Current first-pass kind set:

- `file_stream`

Invalid resource kind, closed-resource use, and similar programmer errors throw runtime errors.
Operational failures now return PHP-shaped `false` at the PHP exposure layer where PHP uses `false` as the sentinel.

## Contract narrowing

This first pass keeps narrowed behavior where documented, but ordinary filesystem and stdio failure sentinels now match PHP more closely.

Key decisions:

- ordinary open/read/write/path failures use PHP-shaped `false` sentinels or plain `bool false` depending on the PHP contract
- `scandir()` returns actual entries only
- `scandir()` sorts entry names ascending
- `scandir()` excludes `.` and `..`
- `scandir()` success payload remains directly iterable at the PHP surface through wrapper delegation
- `realpath()` requires the target path to exist
- `touch()` creates the file if missing
- `file_put_contents()` is overwrite-only in this pass
- directory and path wrappers remain in dedicated filesystem headers, not `php.hpp`

## Implemented functions

### stdio

- `fopen`
- `fseek`
- `ftell`
- `fgets`
- `fread`
- `fwrite`
- `fputs`
- `rewind`
- `fflush`
- `feof`
- `fclose`

### filesystem

- `is_file`
- `is_dir`
- `is_link`
- `file_exists`
- `file_get_contents`
- `file_put_contents`
- `mkdir`
- `scandir`
- `filesize`
- `filemtime`
- `touch`
- `rmdir`
- `unlink`
- `copy`
- `rename`
- `realpath`
- `dirname`
- `basename`

## Result-wrapper consumption note

For first-pass filesystem builtins that return `result_or_false<T>`:
- use `take(...)` when code wants an explicit typed payload local
- direct `foreach` over the wrapper is allowed when `T` is iterable, such as `scandir()`
- false-sentinel branches remain runtime unwrap failures for direct iteration

## Testing note

Runtime smoke coverage lives in `tests/runtime/native/test_php_filesystem.cpp`.
That test file intentionally covers both the stdio and filesystem first-pass contracts.
