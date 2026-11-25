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

## Advisory file locks

The four general operations use an opaque `file_lock_handle`. Copies alias the same token. Callers serialize operations on a shared token. Linux supports nonblocking advisory locks on regular files. Participants must use a stable file identity and keep the lock file in place. Release leaves the file and its contents intact.

| General signature | Effect |
| --- | --- |
| `fs_lock_empty(): file_lock_handle` | Creates a fresh empty token on every platform. |
| `fs_lock_try(file_lock_handle &$out, string path, bool shared = false): result<bool>` | Takes a writable named local or parameter. Acquisition returns true and replaces only that slot. Earlier aliases of its previous token remain empty or released. Contention returns a successful false. A held output, invalid path, open failure or other OS failure gives a checked error. Both contention and error preserve the output. |
| `fs_lock_release(file_lock_handle h): result<bool>` | On Linux, succeeds with true for an empty or released token. Unlocking a held token releases it for all aliases. Unlock failure keeps ownership for retry. If close reports an error after unlock, the token is already released. |
| `fs_lock_transfer(file_lock_handle h): result<file_lock_handle>` | Moves the descriptor into a fresh token, preserving the lock continuously. The old token and all its aliases become released. Transfer from an empty or released token fails. Failure allocating the destination preserves the acquisition. |

Shared mode opens an existing file read-only and permits other shared readers. Exclusive mode opens read/write and creates a missing file subject to umask. Contending for a lock through a second independent open follows the same rules within or across processes. An empty path, embedded NUL, directory, FIFO or missing parent gives a checked error. Symlinks follow normal open resolution. There is no blocking wait or upgrade operation.

The final owning token performs nonthrowing cleanup. The descriptor is close-on-exec. After fork, explicit release or transfer of a live inherited token fails in the child. The child's final reference closes only its descriptor. Explicit parent release unlocks even while a child retains a descriptor. On other platforms try, release and transfer return checked unsupported errors.
