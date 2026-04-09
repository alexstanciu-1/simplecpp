# Filesystem / stdio builtin contracts

This folder contains one contract file per filesystem or stdio builtin in the first-pass Prism++ filesystem surface.

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
