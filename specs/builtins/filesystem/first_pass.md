# Filesystem / stdio builtins — first pass

This document defines the first-pass narrowed contract for the Prism++ / Simple C++ filesystem and stdio builtins.

See also:
- `specs/builtins/filesystem/README.md`
- one-contract-per-builtin files in `specs/builtins/filesystem/`
- `docs/filesystem_builtins.md`

## Module split

- `runtime/include/scpp/php_resource.hpp`
- `runtime/include/scpp/php_stdio.hpp`
- `runtime/include/scpp/php_filesystem.hpp`

Support implementations live in matching `runtime/include/scpp/support/*` files.

The goal is to keep filesystem and stdio out of the generic `php.hpp` surface so these APIs stay isolated and easier to evolve.

## Resource model

`fopen()` returns a nullable resource handle.

The resource wrapper has an explicit `kind`.
Current first-pass kind set:

- `file_stream`

Invalid resource kind, closed-resource use, and similar programmer errors throw runtime errors.
Operational failures return `null` where PHP would traditionally return `false`.

## Contract narrowing

This first pass intentionally does **not** aim for 1:1 PHP parity.

Key decisions:

- ordinary open/read/write/path failures return `null`
- `scandir()` returns actual entries only
- `scandir()` sorts entry names ascending
- `scandir()` excludes `.` and `..`
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

## Testing note

Runtime smoke coverage lives in `runtime/tests/test_php_filesystem.cpp`.
That test file intentionally covers both the stdio and filesystem first-pass contracts.
