# my-try startup review
Doc Status: planning

Reviewed 2026-10-26 to identify the first functional startup work for my-try-cpp.
This is a trace of the current PHP implementation plus proposed rewrite boundaries;
the [initial startup slice](startup_slice.md) now implements a bounded catalog
model, with semantic type/callable installation still separate.

## Existing boot sequence

1. [`main.php`](../../my-try/main.php) includes
   [`boot.php`](../../my-try/boot.php) before parsing CLI options. Boot loads portable
   host helpers, record/storage definitions and stage workers. Most of this file
   is PHP composition, not runtime initialization to translate into C++ calls.
2. The end of boot calls `Runtime_Catalog_Loader::load` for `core`, `json`,
   `strings`, `datetime`, and `filesystem`, using `runtime/metadata/modules`.
   [`Runtime_Catalog_Loader`](../../my-try/compiler/runtime_catalog/load.php)
   recursively loads dependencies, checks names and cycles, decodes each module
   once per loader invocation, and returns dependency-ordered typed metadata.
3. [`Compiler_Lifecycle::configure_runtime`](../../my-try/compiler/lifecycle.php)
   validates metadata against privately created language/runtime type and callable
   catalogs, constants, members, initialization/indexing/iteration contracts and
   backend bindings. Only then does it retain the metadata in `Model`.
   Reconfiguration after syntax initialization is rejected.
4. `main.php` parses its invocation and constructs `Compiler`. Its constructor
   selects the job budget (currently 12) and C++ lowering options (prelowering by
   default). It does not itself initialize the model or start a persistent pool.
5. Direct source mode calls `Compiler::init(paths)` -> `init_modules(inputs)`.
   [`Module_Loader::configuration`](../../my-try/01_prepare_inputs/module.php)
   resolves/validates module directories and explicit source selections privately.
   The first initialization resets the model; module reconciliation decides whether
   configuration changed. Rebuilding modules resets compilation roots and discovers
   sources. Failed discovery leaves readiness false and permits retry.
6. [`Compiler_Lifecycle::reset_compilation`](../../my-try/compiler/lifecycle.php)
   installs semantic type roots, runtime definitions/callables/constants/member and
   protocol catalogs, independent C++ bindings, language scope and builtins, then
   namespace/global roots and output state. Metadata is reused across semantic
   resets; these resets do not reread catalog files.
7. After optional entry selection, `Compiler::exec_cpp()` performs source sync /
   tokenization / parsing and collection, then `prepare(true)`, then C++ emission
   and optional publication. Preparation remains a separate phase. Native Clang
   compilation is a separate consumer, not an automatic part of this entry path.

Project mode additionally loads the manifest through the host `Project_Compilation`:
it selects configured runtime modules (plus core/strings), configures their catalogs,
initializes modules/entry, and validates project exports after preparation. The
default catalogs from boot have already been loaded/validated before that selection.
The browser/debug path and LLVM prototype reset are host/prototype behavior, not
requirements for the new C++ compiler startup.

## Lessons to apply before translating startup

- Distinguish runtime libraries used to implement the compiler from runtime metadata
  describing facilities available to source programs. The new `middleware/types.json`
  is a concrete implementation naming registry, not the source-language type catalog.
- Parse the requested invocation before costly catalog IO/validation. Select the
  intended runtime modules once, including project-mode selection, before loading.
- Keep metadata loading, semantic validation/installation, and backend bindings as
  explicit owners. Do not move them into a universal middleware initialization call.
- Existing startup performs private type/catalog setup during configuration, then
  installs catalogs during model reset. First module initialization can reset roots
  again for the configuration change. Measure these passes separately and establish
  why each is needed before deciding whether to reuse their results. This trace is
  not a timing attribution or authorization to remove validation.
- Establish job configuration before stage execution. Actual worker scheduling and
  publication boundaries belong to stage/coordinator owners; no concurrency claim
  follows merely from storing a jobs count. Measure meaningful parallel work when
  source discovery/frontends are introduced.
- Treat same-configuration reuse and changed-configuration invalidation as explicit
  contracts to test in the rewrite, not performance inherited from old incrementality.

## Proposed first functional startup scope

Start with explicit invocation/configuration and startup catalog loading/validation,
followed by model installation. Keep error reporting and timing boundaries visible
between these steps. Source discovery is the next consumer, before tokenization.
The desired first proof is a successful startup from selected catalog inputs plus
a failed startup that publishes no partial ready state, with catalog IO and setup
timed separately. This needs concrete filesystem/JSON interfaces and typed metadata
records; a class named Compiler that only calls the smoke test would not establish
that proof.

## Agreed model ownership — 2026-10-26

Use one process-global static Model. The user does not require simultaneous compiler
models or independent compilation sessions. Incremental work updates existing
retained records and their status/revision flags; old/new versions needed during
an update do not imply two complete model graphs. Do not introduce whole-model
double buffering or per-session model ownership.

Where an update needs both previous and candidate data, retain only the versions
needed by its owning process until publication/retirement is safe. Exact version,
failure-recovery and publication protocols remain to be designed and proved; this
decision alone does not establish a universal two-version concurrency algorithm.
One static model still requires explicit mutation ownership and synchronization
for concurrent workers. Lifecycle owns initialization, reset and retirement.

The subsequent [startup implementation](startup_slice.md) creates this static
catalog model. It distinguishes unresolved metadata readiness from semantic/compiler
readiness and records the exact validation and concurrency boundaries.
