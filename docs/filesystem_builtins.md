# Filesystem builtins â€” first pass
Doc Status: supporting
This page summarizes the first-pass Prism++ / Simple C++ filesystem and stdio builtin surface.

Filesystem is now runtime-owned and lives under `namespace scpp::fs`. The PHP layer keeps only a thin wrapper in `namespace scpp::php`. PHP resource/stdio behavior remains PHP-owned for now.

## Strict cross-process file locks (Linux)

Use `fs_lock_try`, `fs_lock_release`, and `fs_lock_transfer` with a dedicated
`file_lock_handle`. Acquisition returns `result<bool>`: successful extraction
means the operation completed, while the extracted bool says whether it acquired
the lock. False means contention; an error means an invalid call or OS failure.

```php
$lock file_lock_handle;
$err error;
$acquired bool = false;
if (!take($acquired, $err, fs_lock_try($lock, "project.lock"))) {
	echo $err->get_message(), "\n";
} elseif (!$acquired) {
	echo "project is busy\n";
} else {
	// Perform the protected work here; release on every exit path.
	$released bool = false;
	if (!take($released, $err, fs_lock_release($lock))) {
		echo $err->get_message(), "\n";
	}
}
```

Pass `true` as the third acquisition argument for a shared reader; that opens an
existing file read-only. Exclusive acquisition creates a missing file without
truncating it. Release never deletes the lock file. Every participant must use
the same stable file and must not replace or unlink it.

Handle copies alias one token. `fs_lock_transfer` returns a new owner without
releasing the OS lock; the old token and its aliases become released. Use it for
a handoff where the old owner may subsequently clean up. Explicit cleanup is
idempotent; last-handle destruction is a nonthrowing fallback.

See the [full file-lock contract](../specs/builtins/filesystem/file_locks.md) for
inheritance, error handling and platform limits. This is a strict runtime API;
portable PHP framework/converter bindings are separate integration work.

## Header split

Filesystem and stdio support are intentionally kept out of the generic `php.cpp` / `php.hpp` area.
Use the dedicated headers instead:

- runtime module header: `runtime/include/modules/filesystem/filesystem.hpp`
- PHP wrapper header: `runtime/include/lang/php/php_filesystem.hpp`
- PHP-owned resource/stdio headers remain under `runtime/include/lang/php/`

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
