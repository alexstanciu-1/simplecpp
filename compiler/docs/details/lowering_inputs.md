# Lowering input boundary
Doc Status: supporting

Status: implemented and consumed by [instruction lowering](lowering.md) in the
PHP prototype. [Native builds](native_executable.md) now consume this boundary
through LLVM emission and optional executable publication. [LLVM backend preparation](backend_preparation.md) now
supplies verified target facts and actual bindings through this boundary.

## Resolved language contracts

`Checked_Body::signature_for(symbol_id)` and `definition_for(type_id)` return
the existing shared signature payload and named definition. Missing references
fail explicitly. Dependency maps still belong to body checking and its reuse
validation; consumers need not know their storage. Lifetime analysis now uses
the definition accessor. No second signature or type dataset is created.

## Separate backend snapshot

[`Backend_Context`](../../src/05_generate_code/prepare_backend/data/context.php) is immutable and shared
by all lowering inputs. It holds an optional `backend_configuration` and an
index of provider-prepared `callable_binding` records. It never chooses a target,
linkage or calling convention from source names, host PHP or the language `int`.

Configuration contains the backend implementation/configuration key, target
triple, data layout, CPU, features, ABI-provider key and runtime-provider key.
The keys must identify exact configurations/revisions supplied by their owners.
Recording these facts does not verify toolchain availability or supported options;
the [LLVM provider](backend_preparation.md) now verifies the configured target
and required primitive calls before supplying these records.

A callable binding supplies a link name, linkage and calling convention, tied to
its exact configuration and shared language signature/return definition. Direct scalar parameter contracts retain ordered type IDs and shared definitions.
Foreign and aggregate passing remain unsupported. The context rejects duplicate
symbol/link names and bindings from another configuration. Access also rejects
stale language contracts, including references from another type lineage.
Constructing descriptors does not advertise executable backend support.

An empty context remains representable and fails callable readiness checks.
Normal compilation automatically prepares a complete context.
`Native_Entry` separately prepares the module startup plan using the probed
hosted-C ABI. It shares the language-entry binding from this context.

## Pipeline, workers and reuse

`Compiler_Session::compile(manifest_path)` automatically invokes the LLVM provider.
The temporary context-injection argument is removed: configuration comes from
`tools/backend.json` or the constructor's `backend_toolchain_path`. The coordinator
canonicalizes prepared contexts and accepts them with the complete candidate.
Failures preserve previous semantic results and backend context. There is no
CLI target-selection flag; the configuration file owns that request.

`Compile_Result::lowering_input_for(symbol_id)` returns a small immutable
`lowering_input` referencing the exact analyzed body and backend context.
`require_callables()` checks the owner's contract and each reachable call,
including void calls; calls after the reachable prefix are excluded. These are
preparation checks, not execution or native-entry readiness checks.

Each `Lowered_Body` retains this input dependency. Work selection is
`update.full_rebuild || !previous_input.is_current(analysis, backend)` through
one common path. Both identities must match. Equal context contents canonicalize
to the retained object; configuration, binding or language-contract changes
produce a new context. A backend-only change invalidates lowering reuse while
allowing independent source/type/body/lifetime results to remain shared. The
[lowering phase](../../src/05_generate_code/lower/main_lower.php) selects independent workers
and joins complete current results, excluding removed owners.

`--debug=json` exports `backend`: configuration, prepared link/calling-convention
bindings, and a reference to the separate module-level entry contract. `lowered` exports
the resulting values, instructions and blocks. Inspection reports
`completed: false` and `stopped_before: "build_native"`; successful `--output`
builds report completion and their artifact through the native result.

[`lowering_inputs.php`](../../tests/05_generate_code/lower/lowering_inputs.php) proves shared
access, missing/stale contract rejection, all configuration dependencies,
configuration-bound bindings, deterministic canonicalization, body-only reuse,
independent backend invalidation, reachable void calls, rollback/repair, cross-lineage
rejection and exports. Real provider preparation and its verification are covered separately by the
[backend preparation proof](backend_preparation.md).
