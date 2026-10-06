# Builtin contract: fs_lock_try
Doc Status: normative
Status: experimental

`fs_lock_try(file_lock_handle &$out, string $path, bool $shared = false): result<bool>`

The [file-lock family contract](file_locks.md) defines acquisition, contention,
errors, nontruncating creation, shared read-only open, platform scope, ownership,
registration, compile plan and proof requirements for this builtin.
