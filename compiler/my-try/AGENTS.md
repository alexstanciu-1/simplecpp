# my-try operating rules
Doc Status: supporting

These rules apply to the entire `compiler/my-try/` subtree. Preserve unrelated IDE
changes and reread touched files before editing. PHP remains authored source; use
the portable-PHP skill and existing converter/framework for adaptation.

## Starting or resuming work

After the repository's required reading, read the [documentation index](docs/README.md)
and follow its map to the guide owning the requested change. For feature work, inspect
the [catalog](docs/catalog/README.md) chapter's progress and agree the next unfinished
slice. For native work, read the [portability status](docs/portability/conversion_review.md)
and distinguish its tested revision from the current checkout. Consult archives only
when historical context is needed; their former plans are not current instructions.

## Scope and workflow

- Default development is frontend + C++ S2S. LLVM is parked; change its semantics
  only on explicit request. New semantics extend shared preparation and attached
  facts. LLVM must first be adapted to that model if development resumes.
- Catalog work targets strict PHS first. Review the corresponding legacy S2S
  implementation, tests and edge cases before each slice. Legacy examples are
  evidence, not semantic authority. Follow the [catalog workflow](docs/catalog/README.md#per-example-workflow).
- Reject statically invalid type/structure combinations during shared preparation,
  even where v0.1 needed runtime rejection. Preserve guards for valid operations
  with value-dependent failures; see [v0.2 diagnostic policy](../../specs/v0_2_preparation_diagnostics.md).
- Implement the agreed ownership area, review the code, run relevant focused tests,
  then commit. Style/lint runs, broad regressions, conversion and native compilation
  are on demand. In particular, do not run `tools/native_validate.py` automatically.
  PHP checks do not prove native portability.
- Bypass legacy v0.1 STAN for this compiler work (`--no-stan` on requested native
  runs). Its repair catalog is parked; resume STAN work only on explicit request.
- Active development is on `v0.2/develop`. The former local
  `feature/scpp-native-portability-fixes` branch was fast-forwarded into it and deleted
  on 2026-10-03. Do not recreate or switch to that retired branch for ongoing work;
  its previous branch-specific push instruction no longer applies.
- An explicitly requested partial refactor may temporarily break later stages;
  discuss cross-owner repairs instead of silently introducing compatibility code.

- Catalog development currently uses a positive-pass approach: make the agreed
  bounded functionality work through sound structures, ownership and reusable paths.
  Keep focused behavioral/recovery proofs; record peripheral hardening and deferred
  extensions as debt rather than expanding the slice to exhaust them.
- Group related catalog cards around a shared contract/owner before implementing;
  keep deferred prerequisites and each group's proof boundary explicit. Chapter
  closeout should update the catalog handoff and documentation index together.
- Discuss new retained structures and semantic/ownership changes before implementing
  them. Once a batch is agreed, carry analogous cases through without requesting
  confirmation for each one. Stop for a newly discovered cross-owner redesign.
- Use the [shared test runner](docs/testing.md) for focused checks as well as broad
  runs: 12 continuously refilled task slots, PHP-FPM with OPcache by default and CLI
  fallback on unavailability. Keep dependent steps within each fixture ordered;
  supplementary probes also use the pool. A generated-C++ run does not prove the
  compiler itself was rebuilt natively.

## Model and processing

Read [MODEL](docs/architecture/MODEL.md) before changing retained data and
[ownership](docs/architecture/ownership.md) before changing links or lifetime.

- Stage workers must not depend on later-stage processing. They publish their own
  facts/change descriptions; the coordinator sequences consumers. Shared structures
  may cross stage boundaries, but storing another stage's facts is not permission
  to run its algorithms or mutate its scheduling state. C++ spelling/bindings belong
  to the C++ backend, not shared semantic identities.
- Parser-invoked collection is part of `03_parse/collect/`, not a separate analysis
  pass. Composition/lifecycle orchestration and neutral structural traversal are
  explicit cross-stage roles, not precedents for forward dependencies in workers.
  Preparation and C++ dispatch/traversal belong to their stages; do not add AST
  processing or universal visitor hooks. Work records expose data and typed queries,
  not processing adapters. Remaining incremental cross-owner mutations follow
  the agreed [stage-owned processing plan](docs/planning/stage_owned_processing.md).
  Neutral maintenance remains available. The agreed lifecycle/consumption repair
  proceeds in bounded slices; new retained protocols still require discussion.

- Model's static fields own shared roots; retained records never retain workers.
  Dependency-aware lexical traversal belongs to analysis `Observed_Lookup`; plain
  `Scope_Lookup` must not accept preparation contexts or register subscriptions.
  `Compiler_Lifecycle` owns resets. Module configuration changes reset compilation;
  identical configuration preserves retained data.
- Structures may initialize data, preserve local representation invariants, expose
  typed access/query helpers and clear their facts. Workers own stage dispatch,
  traversal, algorithms, scheduling and publication policy, including work-record
  queueing, rebuilding, settlement and retirement dispatch. Neutral structural
  maintenance and lazy inspection are retained.
- Specialized AST node families own named fields; the shared abstract statement
  body owns its statements and common traversal. Do not recreate node/payload registries,
  generic child lists or inspection parents. `children()` is lazy inspection;
  compiler operations use typed fields through stage-owned handlers. Every operation
  has one traversal owner.
- Keep stable identity and collection positions. Do not sort syntax membership or
  turn non-owning indexes into new semantic owners. Document index maintenance.
- Keep semantic type identity, value-storage representation and operation capability
  separate. Normalize redundant modifiers through registry facts, not source names.
  Source-derived capabilities belong to completed declaration facts; semantic queries
  require completion and register dependencies. Do not duplicate them in registry caches.
- Required fields stay nonnullable and must be populated before read/publication.
  Absence is explicit `?T`; weak-reference intent does not imply nullable.

## Authoring

Follow [code_style.md](docs/code_style.md). Use `namespace scpp\compiler;`, lowercase
`snake_case` data types and `Capitalized_Snake_Case` workers. Qualify external
exceptions/constants; prefer short same-namespace types in portability annotations.

Continuation lines in a split statement or declaration need at least one extra tab
beyond its first line, including parameter lists and multiline conditions.

Keep data in its owning process's structures files and algorithms in named workers.
Use explicit PHP types; add supported adjacent annotations for container element/key
intent or native widths. Do not invent unsupported annotation syntax. Stabilize
host results after checking their truthful failure/union forms.

Use `Storage<T>` for numeric shared-record lists, `Keyed_Storage<T>` for unique
string keys and `Key_Storage_List<T>` for duplicate keys. Scalar/sparse value
containers remain typed vectors/hashes. Bind nested collection receivers to typed
locals where required by the converter. See [Storage](docs/storage/STORAGE.md).
Ownership tags document intent; only supported bindings implement native behavior.

## Performance and documentation

Prioritize reduced native recompilation through modular output, stable names and
precise dependencies. Current output remains one `main.cpp`; partitioning is debt.
Do not benchmark every spelling or promise devirtualization/layout gains without
measurement. Preserve semantics, lifetime and diagnostics during optimization.

Keep current guidance compact. Update the owning guide and unresolved debt when
behavior changes; archive completed proposals/checkpoints rather than appending
contradictory “current” sections. Proof counts belong to dated evidence, not global
instructions. The [documentation index](docs/README.md) identifies the active owners.
