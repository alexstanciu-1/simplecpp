# Builtin contract: fs_lock_transfer
Doc Status: normative
Status: experimental

`fs_lock_transfer(file_lock_handle $handle): result<file_lock_handle>`

The [file-lock family contract](file_locks.md) defines uninterrupted ownership
transfer, old-token invalidation, failure preservation, platform scope,
registration, compile plan and proof requirements for this builtin.
