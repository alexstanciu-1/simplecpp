# Builtin contract: fs_lock_empty
Doc Status: normative
Status: experimental

`fs_lock_empty(): file_lock_handle`

Returns a fresh present token with no acquired resource. No filesystem operation is
performed. See the [file-lock family contract](file_locks.md) for aliasing, acquisition,
release, transfer, allocation failure and platform behavior. This factory does not
make an uninitialized required handle a valid argument.
