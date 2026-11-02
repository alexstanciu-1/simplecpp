# Common modules and initial incremental boundary

Doc Status: planning
Date: 2026-11-02

This records the agreed direction, initial structures and implementation checkpoint.
It does not extend language semantics or authorize a broad runtime rewrite.

## Agreed direction

Application and runtime modules use one module model and one orchestration path:
selection, dependency resolution, input loading, declaration publication,
preparation, lowering and build participation. A runtime module is a module in the
application's selected graph, not a separate compiler-wide dependency system.
Different source/metadata readers may feed that common path. Modules need not have
identical capabilities or identical physical input formats.

Each module owns its direct dependencies. Projects and optional compiler defaults
or profiles select initial modules; the coordinator computes their dependency
closure. The application does not reconstruct runtime modules' internal dependency
lists. Do not maintain parallel runtime and application dependency resolvers.

Retain semantic decisions on declarations/AST nodes; module symbol indexes only
reference those declarations. Native bindings and generated lowering traits may be
supplied by runtime modules without making source modules imitate native providers.
Introduce concrete metadata/build capability records when their first consumer is
implemented, not a generic optional-property registry now.

Dependency selection and symbol visibility are separate. Direct-dependency-only
visibility, with deliberate re-export if later supported, is a recommendation from
the discussion, not an implemented or fully agreed language rule. Do not implicitly
promise that every transitive dependency's declarations are globally visible.

## Initial full-rebuild rule

For now, any detected module change of any kind causes a full rebuild of the
selected program. Apply this equally to application and runtime modules. This
includes additions/removals, dependency or configuration changes, source membership
or content changes, metadata/binding changes and changed module-owned native inputs.
A changed profile/default selection also requires rebuilding the selected program.

This is the conservative initial interpretation: it does not exempt ordinary source
edits. Source-only selective rebuilding can be agreed as a later refinement; the
initial module plan must not quietly claim that optimization is implemented.

Invalidate retained frontend/preparation/lowering results and application native
objects/link outputs for that rebuild. Rebuild affected module-provided native
artifacts; do not claim that merely relinking stale native artifacts is a full
rebuild. Independent system toolchains are not rebuilt. Detailed cache policy and
change detection belong to the implementation discussion.

A no-change run may eventually reuse completed work. Native worker ownership and
publication barriers remain as recorded in two_traversal_preparation.md; PHP stays
single-threaded. A failed update must not mix old and new graph/declaration state.
Start with full invalidation rather than symbol-level watch subscriptions.

## Agreed initial retained structures

The agreed three records introduce ownership without implementing all module
capabilities:

| Record | Initial fields | Role |
| --- | --- | --- |
| compilation_session | modules: array<string,module>; entry: module | Own the selected module graph and identify the executable entry. |
| module | name: string; root: string; dependencies: array<string,module>; sources: array<string,source_unit>; entry: ?source_unit | Own module-relative source membership and direct dependency references. Root is the resolved module directory. |
| source_unit | module: module; path: string; flavor: flavor; ast: ?ast_file_node | Stable source identity, with an owner backlink and the current completed frontend result. Path is module-relative. |

`modules` and `sources` are owning indexes. Dependencies, the entry and the
source-unit owner backlink are non-owning references in the eventual native model.
No separate module input/result class is proposed. Load configuration privately,
resolve dependency names to module references and publish a complete graph.

The file root already owns tokens and their source snapshot. Do not duplicate
source bytes, tokens or prepared facts on source_unit. A null AST means no completed
frontend result for the current compilation, not an empty source file. Detailed
failure/change states are intentionally left for the next incremental slice.

source_unit replaces source_input; compilation_session replaces the former flat
program_sources retained wrapper. source_file remains an immutable loaded snapshot.
In-memory panel input needs a loader path into the same unit model; do not force
all callers through disk IO or invent a second module model for the panel.

Do not add a runtime/app kind discriminator solely to branch orchestration. A
module can eventually supply source and metadata/native implementations together.
The initial structures deliberately omit unimplemented export caches, capability
registries, version solvers, build scheduling and fine-grained dirty flags.

## Donor findings

- compiler/my-try/01_prepare_inputs/structures.php: module owns stable source_record
  membership; source identity is module-relative and survives snapshot replacement.
- compiler/my-try/01_prepare_inputs/module.php: validates ownership and paths before
  publication; complete discovery is required before treating unseen files as deleted.
- compiler/my-try/compiler/runtime_catalog/structures.php and load.php: separate
  runtime_module_spec owns dependencies and catalog/native bindings; loader reads
  dependencies once, detects cycles and publishes dependency-first.
- compiler/my-try/05_backend/cpp/runtime_build.php: aggregates requirements from the
  selected runtime graph. The common dependency graph should supply that information
  for all modules in the new design.

Reuse stable ownership, complete publication and dependency traversal. Do not copy
the donor's separate runtime/source module systems, global mutable registries or
subscription/retry machinery. Its mtime/size checks are evidence, not a guarantee
that all content changes are detected.

## Initial implementation scope

The record names/fields are agreed; change detection remains a subsequent slice.
The first proof should load a small application/dependency graph,
reject missing/duplicate identities and cycles, retain module/source ownership,
and use the existing source-to-executable pipeline. Runtime modules must enter the
same graph; catalog capability ingestion can remain a later bounded slice.

Incremental proof should distinguish unchanged runs from any changed module,
exercise full invalidation including failed updates, and verify output equivalence
to a clean build. No package manager, native thread pool, class decomposition or
function regrouping is part of this foundation.

## Implementation checkpoint — 2026-11-02

The agreed records are implemented in compiler/01_prepare_inputs/modules.php.
Program_Loader builds the selected dependency closure through one path. Disk input
validates roots/containment and unique physical ownership; in-memory input supplies
snapshots to the same frontend without filesystem reads. Dependency cycles,
missing modules and invalid entries reject before
source processing. Empty modules participate normally; this is not runtime catalog
ingestion. Explicit PHP inputs remain available; the module.json adapter is described below.

Program_Frontend traverses source units, attaches completed roots, publishes symbols
only after complete collection, and prepares through the existing pipeline. Failed
runs clear all unit AST references. Reusing a session currently rebuilds every unit;
older prepared handles reject emission once that session's roots are replaced.
The panel uses the same inventory, source loading and AST ownership while preserving
its explicit timing stages. Prepared program handles retain the session lifetime.

Single-module source keys and emitted filenames remain unchanged. Multiple modules
use module-name/module-relative-path keys; module names cannot contain path separators.
The existing flat global declaration scope remains in effect: module visibility,
exports, runtime metadata ingestion, profiles, and change detection/reuse
are deferred. Module dependencies select membership, not a new access-control rule.

Tests cover shared dependency identity, source owner backlinks, colliding relative
filenames across modules, deterministic output, graph rejection, full rebuild,
failure cleanup and stale results. Existing CLI and in-memory APIs now adapt to the
common graph; standalone single-source AST tools remain available.

## Manifest implementation checkpoint

See [module manifests](../../compiler/docs/modules.md) for the operational format.
Optional name/type/entry and named dependencies now feed the same loader. Discovery
or explicit selection produces module.sources.json inspection output. Runtime
selection is implicit in the manifest command; defaults and explicit submodule
requests merge idempotently. Duplicate dependency requests are no longer errors.
Activation does not imply a catalog parent depends on the activated child.

Modules additionally retain their type and optional canonical manifest input path.
Native sources share module/source ownership but have no AST; native paths are
published separately for the native build. Runtime core, JSON, boxed storage and
datetime manifests describe existing sources. This does not ingest runtime API
metadata, generate optional publications, or add semantic bindings. There is no
config field or profile machinery. Existing explicit inventory/panel callers keep
their behavior. Full rescanning and rebuilding remain the initial policy.

## Whole-project change detection checkpoint

The project command compares mtime and size before preparation; content hashing
was replaced at the user's request. Any detected change, including application
source changes, triggers full compilation. Unchanged inputs may reuse the bundle.
Success metadata remains in the existing bundle manifest and is updated only on
publication. Failure leaves changes pending against the previous baseline.

source_unit now carries mtime, size and the general change_status enum
(none/added/removed/changed). Removed units are retained for the change pass without
entering active compilation. There is no separate tracked-file model. Header and
manifest checks use plain metadata maps. No AST change, partial reuse, or native
linker orchestration is introduced. Same-size edits preserving second-resolution
mtime can be missed; --force explicitly requests a rebuild. See the module guide
for the tracked-input boundary and exclusions.
