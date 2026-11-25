# Exclusive filesystem staging
Doc Status: normative

`fs_stage(string $directory) -> result<string>` reserves a new empty regular file in
an existing directory. Success returns the path of that file, relative or absolute
according to the directory argument. The operation creates the file exclusively
and gives the caller ownership of that reservation. Another reservation in the same
directory cannot take a path that still exists.

On POSIX hosts the file has owner-only permissions and the reservation closes its
descriptor before returning. An empty path, a NUL byte in the path, an unavailable
host implementation, or an operational reservation failure returns a structured error.
A failure while finalizing the reservation attempts to remove any file the call created.

The caller owns writing, promotion, and removal after a successful reservation.
For an atomic replacement, reserve the stage in the destination directory, write
it with `fs_put`, and rename it to the destination after the write succeeds. A
publisher that stages several artifacts owns the commit point that makes the new
bundle visible.

In v0.2, enable the filesystem runtime publication and select the application
dependency `filesystem` to call this operation.
