# Builtin contract: fs_read_snapshot
Doc Status: normative
Status: experimental
Compatibility: narrow checked source read; Linux backend in this revision.

`fs_read_snapshot(string $path, int $expected_mtime, int $expected_size): result<string>`

Returns binary file contents only after the following checks succeed. Expected
mtime is Unix seconds, with whole-second precision; expected size is byte length.
Empty content is success with an empty string, never failure.

1. Fresh `lstat` of the named path must identify a regular file with the expected
   mtime and size. The final path component must not be a symlink.
2. Open read-only with close-on-exec, no-follow and nonblocking flags. Fresh `fstat`
   must identify a regular file with the same expected mtime/size and the same
   device/inode as the first observation. Nonblocking open prevents a raced-in FIFO
   from hanging the caller; only verified regular handles are read.
3. Read at most `expected_size + 1` bytes, in bounded chunks. Partial reads and EINTR
   are handled; the extra byte detects growth without consuming an unbounded stream.
4. Fresh post-read `fstat` and `lstat` must both match the original identity,
   regular-file kind and expected mtime/size. Returned length must equal expected
   size. Replacement/removal of the name behind an open handle is a failure.
5. Close the descriptor on every success, error and exception path. No partial
   string is returned on failure.

Empty/NUL-containing paths, negative sizes and sizes whose extra byte cannot fit
native string/size limits return errors. Filesystem/open/read/stat/version failures
return `error_t` through `result`; allocation failures keep ordinary exception
behavior with descriptor cleanup. Error messages identify the failing operation
or version check; host errno message text is not a stable programmatic taxonomy.

This is **not an atomic snapshot**. Same-inode edits with the same size and
whole-second mtime can pass. Mutations after the final observations are also not
excluded. No stronger timestamp, locking, hashing or durability guarantee is added.
Intermediate directory symlinks follow host path resolution; final-component
symlinks are rejected. No public metadata or fabricated cross-platform identity
is exposed. Other target platforms return an explicit unsupported error until an
equivalent native backend is implemented.

Ownership: `runtime/include/modules/filesystem/snapshot.cpp`; private algorithm
and Linux observations under `modules/filesystem/detail/`. Public declaration in
`filesystem.hpp`. Compile in `scpp_filesystem` and the existing monolithic runtime
composition, as with file locks. Strict registration/normalized metadata own the
STAN signature. This does not expand existing filesystem module-exclusion policy.

Validation: `tests/runtime/native/test_snapshot.cpp` exercises actual Linux files,
deterministic mutation/failure injection through private backend operations,
bounded consumption and descriptor cleanup. The test seam is not a public runtime
hook. `tests/tools/test_scpp_snapshots.py` proves strict build/run, STAN argument diagnostics and native type rejection.
PHP framework parity and compiler migration remain downstream work.
