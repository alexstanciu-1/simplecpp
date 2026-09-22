# Build native call map
Doc Status: supporting

Caller: Compiler_Session::finish(), only for native output requests. utilities/native_paths.php
chooses default names from the prepared target when requested, then validates destinations
against recursive roots and selected input files. compile_objects.php owns Native_Compiler; join.php
accepts compiled/retained objects before linking. workspace.php owns private staging;
result.php owns candidates and artifacts. finalize() exposes the candidate only.
Compiler_Session::publish() separately calls Native_Candidate::publish(); failures/abandoned
candidates clean newly owned files and preserve accepted artifacts.

The following are ordered lifecycle calls, not calls between siblings.

```text
Native_Builder                                  main_build_native.php
  init() -> [action] validate retained paths; select objects
  run() -> build_candidate() -> Native_Compiler::compile_batch(); Native_Join::join(); LLVM_Toolchain::link_objects()
  finalize() -> [action] complete unpublished Native_Candidate
  result() => Native_Candidate
```

status() and supports_run() are always available. Result/store access requires
finished status; invalid timing throws without changing the state.

LLVM_Toolchain::link_objects() accepts Backend_Context's optional Runtime_Input_Set,
verifies compiler/target compatibility and links one ordinary module per package
with their common C++ driver. The session holds all package reader leases until
publication and releases them on every exit.

The retained tool service lives in [prepare_backend/tools/toolchain.php](../../05_generate_code/prepare_backend/tools/toolchain.php); native building consumes its
configuration and object/link contracts without owning target preparation.
