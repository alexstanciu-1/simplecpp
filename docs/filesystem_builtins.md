# Filesystem builtins — first pass

This page summarizes the first-pass Prism++ / Simple C++ filesystem and stdio builtin surface.

## Header split

Filesystem and stdio support are intentionally kept out of the generic `php.cpp` / `php.hpp` area.
Use the dedicated headers instead:

- `runtime/include/scpp/php_resource.hpp`
- `runtime/include/scpp/php_stdio.hpp`
- `runtime/include/scpp/php_filesystem.hpp`

## First-pass contract shape

- resource wrappers carry an explicit `kind`
- ordinary operational failures return `null`
- wrong resource kind, closed-resource use, and similar programmer errors throw
- `scandir()` returns actual entries only, sorted ascending
- `scandir()` excludes `.` and `..`
- `realpath()` requires an existing path
- `touch()` creates the file if it is missing
- `file_put_contents()` is overwrite-only in this pass

## Implemented stdio functions

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

## Implemented filesystem functions

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

## More detailed contracts

For one-file-per-builtin contracts, see `specs/builtins/filesystem/`.
