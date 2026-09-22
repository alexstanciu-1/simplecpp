# Output building order
Doc Status: supporting

1. build_native/utilities/native_paths.php - validate the destination.
2. build_native/main_build_native.php - init selects missing/stale objects.
3. build_native/main_build_native.php - run compiles, joins and links/reuses a candidate.
4. build_native/main_build_native.php - finalize exposes the unpublished candidate.
5. compile/ - publish the candidate and adopt retained session snapshots.

This group runs only when an output path is requested. Native_Builder uses the
configured LLVM_Toolchain service; object jobs may overlap within its bound.
Compiler_Session owns final publication. The compiler does not run the program.

Call maps: [native building](build_native/calls.md),
[coordinator/publication](../compile/calls.md).
