# Compiler entry and update order
Doc Status: supporting

Start: ../main.php -> Compiler_Session::compile() in compile.php.
Bootstrap: ../../bootstrap.php loads declarations; it does not compile.
Each named step below runs init(); run(); finalize(); result(), in that order.
Siblings are coordinator calls, not calls between stages. See [entry files](steps.md).

```text
Compiler_Session::compile()                    compile.php
  -> Project_Lock::acquire() [unless borrowed] lock.php
  -> [action] prepare inputs                  ../01_prepare_inputs/
    -> [step] Manifest_Reader; Input_Selection::select()
    -> [step] Language_Types
    -> Runtime_Import::open() [if configured; selected package reads and input-set join]
    -> Family_Adapter::validate_exposures() [fixed source-family declarations]
    -> [step] Source_Discovery
  -> Instantiation_Policy::load() [fixed preparation budget]
  -> Type_Cache::requires_rebuild() [cache query]
  -> [if valid published inputs/target and no native family service] finish() [reuse]
  -> [otherwise: frontend; repeat on unsupported increment]
    -> Phases::run_tokenization(); run_parsing()
    -> [step] Declaration_Collector; Entry_Resolver
    -> Phases::run_symbols() [fixed symbols + loaded catalog]
    -> [step] Symbol_Comparer; Input_Selection::supports_increment()
  -> [action] complete analysis               ../04_analyze/
    -> [action] capture ordinary packages in Family_Preparation; release early leases if native preparation is configured
    -> [action] create one Layout_Coordinator with previous layouts and the fixed rebuild flag
    -> Type_Cache::prepare(); [step] Type_Resolver [record join -> ready layout batches -> instance/native preparation -> concrete signatures/locals]
    -> Runtime_Import::reserve() [ordinary + demanded packages; verify accepted snapshots, hold final leases]
    -> Phases::run_bodies(); run_lifetimes()
  -> [action] prepare/generate backend output ../05_generate_code/
    -> [step] LLVM_Backend; Phases::run_lowering()
    -> [step] Native_Entry; Phases::run_emission()
  -> finish()
    -> [if default output requested] Native_Paths::default_output()
    -> [if output path] Native_Paths::destination(); [step] Native_Builder; publish()
    -> [otherwise] retain observed snapshot
  -> Runtime_Input_Lease::release() [if acquired; also on failure]
  -> Project_Lock::release() [if acquired here; also on failure]
```

phases.php runs explicit owning-step lifecycles; inputs.php owns the update gate.
run_tokenization() finishes Source_Reader before Tokenizer; run_emission() finishes
LLVM_Emitter before Module_Assembler. Other helpers execute one phase each.
step.php owns lifecycle interfaces, result/store markers and status enum.
join.php owns the separate instance batch-acceptance interface implemented by all
process joins; it does not add a second phase lifecycle.
state.php holds phase-fixed control; result.php holds request outputs.
Compiler_Snapshot in compile.php retains coherent observed/published results.
Native publication is in ../06_build_output/build_native/; failed preparation
preserves accepted snapshots. cleanup() is deferred and is not in this flow.

Ownership: [type_model](../04_analyze/type_model/calls.md) supplies shared contracts;
[prepare_backend](../05_generate_code/prepare_backend/calls.md) owns target/layout/ABI
preparation and its retained tools. `lower/` consumes their accepted output.

Within Type_Resolver, Concrete_Preparation::init() runs the
[Template_Checker lifecycle](../04_analyze/check_templates/calls.md) before concrete
work. Accepted definition checks are retained in `Instance_Set`; the session does
not need a second parallel permission registry.

`Input_Snapshot::runtime` retains configured ordinary inputs. The backend runtime
set additionally contains current demanded specialization packages; these do not
become source declarations on the next update. The session supplies a per-update
`Family_Preparation` coordinator to type resolution, with prior type/package
associations for validated reuse. A configured native service disables the whole-
pipeline warm shortcut so native freshness is checked through the normal stages.
Final read leases exclude replacement until `finish()` completes; failures release
all acquired leases and preserve accepted compiler snapshots. Prepared runtime cache
packages may already have been published when a later compiler stage rejects source.

`data/native_project.php` defines explicit project key, source root and native output
root configuration for the selected source export boundary. Paths locate artifacts;
they are not portable type identities. Compiler_Session routes source-dependent demands through the
[integration gates](../../docs/details/source_family_integration_plan.md). Explicit
preparation can also publish project modules separately, with unresolved source obligations.

## Source-dependent native families

`Compiler_Session` accepts an optional `native_project`. After early runtime leases
are released, it constructs the shared `Layout_Coordinator` and
`Source_Export_Coordinator`, supplying retained exports from prior family results.
`Type_Resolver` passes both services to concrete preparation. Accepted source/native
packages are re-reserved with explicit project bindings before body checking and
held through the common final link. Backend preparation and module assembly establish
the complete source-export closure; source and native types share existing checking,
borrowing, owned-result and cleanup paths.

Custom exports keep that phase order: early preparation establishes declarations and
ABIs. After `run_bodies()` and `run_lifetimes()`, `LLVM_Backend` receives the fixed
current analysis sets. `Project_Exports` selects verification and accepts its join
before source emission. Current analyses are never written into native package contracts.
