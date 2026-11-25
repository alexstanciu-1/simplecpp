# Filesystem module

Doc Status: normative

Selecting `filesystem` in the root manifest's `dependencies.runtime.modules` enables filesystem calls. PHP source has the PHP names below. C++ source has the general names by default. Setting the root manifest boolean `general_api` to `true` additionally enables general filesystem names in PHP, JS and Python source throughout the project. The default is `false`. The same visibility applies to calls, qualified lookups and function imports. JS and Python have no native filesystem aliases in this publication.

Each path and byte buffer below is a `string`. Each offset, size and timestamp is an `int`. For a general return type `result<T>`, the corresponding PHP return type is `result_or_false<T>`. Check presence with `isset` to distinguish a successful empty string, zero or empty vector from failure. Other return types are identical on both surfaces.

| General operation | PHP spelling | General result and contract |
| --- | --- | --- |
| `fs_get(path)` | `file_get_contents` | `result<string>` containing the exact bytes. |
| `fs_put(path, data)` | `file_put_contents` | `result<int>` containing bytes written after close. Creates or truncates with an existing parent. |
| `fs_copy(source, destination)` | `copy` | `bool`. Copies file contents, overwriting an existing file. |
| `fs_rename(source, destination)` | `rename` | `bool`. Host rename semantics. |
| `fs_remove(path)` | `unlink` | `bool`. General removal accepts an entry including an empty directory. PHP unlink removes a file or link and rejects a directory. Missing entries fail. |
| `fs_mkdir(path)` | `mkdir` | `bool`. Creates one directory level. An existing directory fails. |
| `fs_touch(path)` | `touch` | `bool`. Updates modification time or creates a missing file, preserving existing contents. |
| `fs_rmdir(path)` | `rmdir` | `bool`. Removes an empty directory. Regular files and directory symlinks fail. |
| `fs_exists(path)`, `fs_is_file(path)`, `fs_is_dir(path)` | `file_exists`, `is_file`, `is_dir` | `bool`. Inspect the target, following symlinks. |
| `fs_is_link(path)` | `is_link` | `bool`. Inspects the entry itself, including a dangling symlink. |
| `fs_basename(path)`, `fs_dirname(path)` | `basename`, `dirname` | `string`. Lexical filename or parent under host path rules. On POSIX, basename of `a/` and dirname of `leaf` are empty. |
| `fs_realpath(path)` | `realpath` | `result<string>`. Canonical absolute path of an existing target. |
| `fs_size(path)`, `fs_mtime(path)` | `filesize`, `filemtime` | `result<int>`. File size in bytes and modification time in whole Unix seconds, respectively. |
| `fs_scan(path)` | `scandir` | `result<vector<string>>`. Actual child names in ascending byte order, omitting the synthetic dot entries. Enumeration failure discards the partial listing. |
| `fs_stage(directory)` | — | `result<string>`. Exclusively reserves an empty file on POSIX. The caller owns cleanup. Other targets report an error. |
| `fs_is_windows()` | — | `bool`. Reports Windows path semantics on the compiled target. |
| `fs_read_snapshot(path, expected_mtime, expected_size)` | — | `result<string>`. Linux checked read as specified below. Other targets report an error. |

These signatures give the supported arities. OS path operations reject embedded NUL through their declared failure channel. Lexical filename and parent operations accept the full path string including embedded NUL. Predicates return false on lookup failure.

On Linux a snapshot requires a regular file, a nonnegative representable size and a matching whole-second modification time. The final path component must not be a symlink. The read checks device, inode, size and modification time across the initial path lookup, opened descriptor, and final descriptor and path observations. It reads at most one byte beyond the expected size to detect growth. It closes the descriptor on success and failure. Edits preserving identity, size and whole-second modification time can pass these checks.
