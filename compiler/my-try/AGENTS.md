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
- Implement the agreed ownership area, review the code, run relevant focused tests,
  then commit. Style/lint runs, broad regressions, conversion and native compilation
  are on demand. In particular, do not run `tools/native_validate.py` automatically.
  PHP checks do not prove native portability.
- Bypass legacy v0.1 STAN for this compiler work (`--no-stan` on requested native
  runs). Its repair catalog is parked; resume STAN work only on explicit request.
- On `feature/scpp-native-portability-fixes`, push completed commits to the existing
  remote branch and provide a GitHub commit link. This is standing authorization.
- An explicitly requested partial refactor may temporarily break later stages;
  discuss cross-owner repairs instead of silently introducing compatibility code.

## Model and processing

Read [MODEL](docs/architecture/MODEL.md) before changing retained data and
[ownership](docs/architecture/ownership.md) before changing links or lifetime.

- Model's static fields own shared roots; retained records never retain workers.
  `Compiler_Lifecycle` owns resets. Module configuration changes reset compilation;
  identical configuration preserves retained data.
- Structures may initialize data, preserve local representation invariants, expose
  typed access/query helpers, clear their facts, and dispatch/traverse typed
  operations. Workers retain algorithms, scheduling and publication policy.
- Concrete AST nodes own named fields. Do not recreate node/payload registries,
  generic child lists or inspection parents. `children()` is lazy inspection;
  compiler operations use typed fields/hooks. Every operation has one traversal owner.
- Keep stable identity and collection positions. Do not sort syntax membership or
  turn non-owning indexes into new semantic owners. Document index maintenance.
- Required fields stay nonnullable and must be populated before read/publication.
  Absence is explicit `?T`; weak-reference intent does not imply nullable.

## Authoring

Follow [code_style.md](docs/code_style.md). Use `namespace scpp\compiler;`, lowercase
`snake_case` data types and `Capitalized_Snake_Case` workers. Qualify external
exceptions/constants; prefer short same-namespace types in portability annotations.

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
