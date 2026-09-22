# Builtin contract: fs_is_windows
Doc Status: normative
Status: experimental
Compatibility: narrow target-host fact, not PHP constant compatibility.

`fs_is_windows(): bool`

Returns whether the compiled runtime's native filesystem path separator is a
backslash (Windows path semantics). It uses the target C++ filesystem implementation,
not the PHP generator host or build-machine paths. POSIX targets return false.
There are no filesystem reads, allocation or failure results.

The filesystem owner implements `scpp::fs::is_windows` in `filesystem.cpp` and
exposes it through the existing filesystem module and strict/STAN registry.
The helper does not classify or normalize a path. Such policy remains with the
caller; in particular POSIX backslashes must not be normalized as separators.

Validation: `tests/tools/test_scpp_host_paths.py` (strict build/run and source
policy examples). Both policy branches can be tested on one host; actual native
host-fact evidence applies only to the OS running the test. Windows execution
requires a Windows runner and must not be inferred from POSIX tests.
