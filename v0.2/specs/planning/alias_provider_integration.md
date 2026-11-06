# Scalar alias and runtime provider integration handoff

Doc Status: planning
Date: 2026-11-06
Status: conflicts resolved; quick generator checks passed; full integration verification pending

The user requested pushing a logically aligned merge now, leaving integration
repairs/verification to the other worker. This is not a fully validated release.

## Parents and resolution

Local alias migration: 697cdc4b3 (including named conversion commits 1bd34b47e,
96f1a4974). Remote provider/export work: bb09d12bc and 40695e0f4.

- Preserve movable string/output declaration groups and generated private output
  provider include. CMake tracks their metadata-selected paths.
- Preserve native aliases, strict library input filtering, named checked conversions,
  approved carrier extraction behavior and retirement of native_bridge/wrapper code.
- Remove compiler.scalar_headers. Scalar compiler names/headers resolve through
  type_exports/submodule providers. renderCompiler shares one resolved provider plan
  with compilerProviderMetadata and compilerTypePlan; direct planner tests can resolve
  it on demand. Checked conversion source casts use the resolved type spelling.
- Keep scalar_bool with type_export bool_t and null extraction; do not restore boxed
  scalar conversion bindings. Preserve string_storage's semantic export binding.
- Keep both emitter changes: collect global prototype requirements before emitting
  includes, and collect each expression result type's public headers.

## Checks performed on combined code

- PHP syntax checks for conflicted renderer/planner/compiler tests passed.
- tools/api_generator/tests/compiler.php passed against current target facts/context.
- emit-runtime passed with current runtime metadata/default publication; temporary
  output: /tmp/scpp-alias-provider-merge/runtime.
- git diff --check passed.

Earlier alias-only native/runtime test results do not certify this merged tree.
No combined native build or full incremental recovery suite has been run.

## Remaining work for the receiving worker

1. Adapt tools/api_generator/tests/submodules.py's wrapper-shaped spelling assertions:
   it expects scpp::uint8{ / scpp::byte{ and checked-constructor spelling changes.
   Aliases now occur in declarations/casts; checked conversion function names retain
   canonical identities. Preserve relocation, strict ownership, no-op, cached rebuild
   and all-flavor proofs rather than weakening them.
2. Run provider proofs against actual aliases, isolated public headers, runtime builds
   and all-flavor incremental recovery against fresh compilation. Check checked helper
   header dependencies when scalar provider headers are relocated.
3. Regenerate/review all three generator_parity snapshots after the combined behavior
   passes. They intentionally retain the last verified LOCAL alias snapshots and are
   stale for added submodule plans, provider headers and changed input fingerprints.
   Neither parent's snapshots can certify this combination. Re-run publication
   admission/diagnostics checks; do not blindly accept changed results.
4. Reconcile remaining historical wrapper wording in provider planning/tests/docs;
   current native alias contracts govern representation and extraction.
5. Capture Clang frontend, LLVM and linker timings for the combined application proof.
   Restart retained PHP compiler processes after regenerating compiler definitions.

No source vector/hash functionality or new source conversion permissions are added.
