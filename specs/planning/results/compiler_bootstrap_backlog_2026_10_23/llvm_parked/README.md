# Park LLVM outside the native C++ bootstrap
Doc Status: planning

2026-10-25. User explicitly authorized parking the LLVM prototype when it interferes
with the compiler milestone. Baseline 603b89f6c plus this slice.

Move 05_backend/llvm to prototypes/llvm unchanged in its lowering algorithms.
LLVM_Prototype now owns prototype output/reset/emission. Existing Compiler LLVM
entrypoints remain host-only @scpp-no-export wrappers, resetting output before
frontend work. Shared Model/lifecycle no longer own prototype output. Host tests
and reports use the prototype's output list. Explicit prototype runs/reset own its
state; generic C++ resets no longer clear it. boot.php still loads it for host tests.

The active native driver drops historical LLVM proof branches. The historical copy
is parked beside the backend. native_validate.py now requires --types-only; native
LLVM validation is parked. No automatic native_validate run was performed.

bootstrap_probe.py snapshots active compiler stages, excluding prototypes, and uses
a dedicated C++ bootstrap driver without test-proof sources. request.txt supplies
an input directory; output.txt supplies the publication directory. Uses existing
metadata, compiler entry selection, prelowering, and output publication. Successful
hosted bootstrap output is also published for the future native build step.

Validation:
- Six host suites pass: pipeline, incremental_smoke (LLVM), source_constants,
  namespaces, incremental_preparation and lifecycle_handoff.
- The actual new driver, executed by PHP, publishes prelowered C++ for arithmetic
  and shared-identity programs; Native_CPP_Build compiles them and executions return
  the expected 42 and 9. This is driver/program proof, not a native compiler rebuild.
- Initial snapshot has 255 PHS files and no LLVM_Generator/LLVM_Preparation/
  llvm_module/exec_llvm or S2S_Proof declarations. Final snapshot checked separately.

Initial run (driver still returned encoded artifacts): 191.79 s / 610500 KiB RSS /
578.5 MiB heap; 19 diagnostics remain. Driver changed to full output publication,
then was proved with native programs. Final bootstrap metrics recorded below.
No source-language semantics or LLVM lowering algorithms were redesigned.
Native compiler and second-generation proof remain incomplete.

Final run: 171.58 seconds / 610192 KiB peak RSS (595.89 MiB) / 578.5 MiB
PHP heap. 255 PHS files; exclusion.json verifies no prototype/proof-class hits.
19 distinct preparation messages remain, first projected method receiver lifetime.
This completes prototype parking, not the active compiler bootstrap goal.
