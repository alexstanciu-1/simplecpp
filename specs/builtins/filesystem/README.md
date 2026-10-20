# Filesystem / stdio builtin contracts
Doc Status: normative
This folder contains one contract file per filesystem or stdio builtin in the first-pass Prism++ filesystem surface.

The current strict prefixed surface is specified in [Strict IO handles](strict_io.md).

## Current first-pass coverage

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

See also: `specs/builtins/filesystem/first_pass.md`.

## Checked source reads

Managed advisory locking is defined in [file_locks.md](file_locks.md), with
[fs_lock_try](fs_lock_try.md), [fs_lock_release](fs_lock_release.md) and
[fs_lock_transfer](fs_lock_transfer.md). These typed handles retain stable file
identity across release and belong to the `filesystem` module.

- [fs_read_snapshot](fs_read_snapshot.md) provides checked bounded source reads.
