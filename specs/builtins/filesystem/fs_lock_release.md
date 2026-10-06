# Builtin contract: fs_lock_release
Doc Status: normative
Status: experimental

`fs_lock_release(file_lock_handle $handle): result<bool>`

The [file-lock family contract](file_locks.md) defines explicit unlock, idempotent
cleanup, alias invalidation, partial failures, inheritance, platform scope,
registration, compile plan and proof requirements for this builtin.
