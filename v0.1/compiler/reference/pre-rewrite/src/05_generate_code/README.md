# Code generation order
Doc Status: supporting

1. prepare_backend/main_prepare_backend.php - verify target facts and prepare selected layouts and ABI bindings.
2. lower/main_lower.php - lower selected analyzed bodies into explicit execution plans.
3. lower/main_native_entry.php - prepare the native entry adaptation.
4. emit_llvm/main_emit_llvm.php - emit selected function and composed lifecycle IR; join their results.
5. emit_llvm/main_assemble_modules.php - assemble selected file modules and join the program.

The coordinator calls these final operations in order. The same layout owner also
accepts ready record batches during type resolution; final preparation reuses those
exact facts and completes any remaining layout roots. Preparation publishes the fixed
Backend_Context before body lowering. Its retained toolchain service also serves
native object/link work; its tools do not become body-worker dependencies.
The independent `tool_process/` service is shared with runtime preparation.

Call maps: [preparation](prepare_backend/calls.md), [lowering](lower/calls.md),
[emission](emit_llvm/calls.md). Overall: [compiler](../compile/calls.md).

The selected source export boundary in `prepare_backend/source_export_preparation.php`
consumes accepted layouts and resolution-owned portable identities. It prepares
six-role lifecycle/import contracts with private workers and a join. It is currently
called explicitly with accepted compiler outputs by the project preparation adapter;
compiler family routing and export emission/linking remain the final integration gate.

Source-dependent native demands also use `Source_Export_Coordinator` at the ready
concrete frontier. Final backend preparation fixes required source-link entries and
adds their complete operations to common lifecycle emission. Entry-module assembly
emits stable forwarding exports; its join validates closure before native linking.
