# Compiler working rules

Doc Status: supporting

These rules apply to the entire `v0.2/compiler/` subtree. After the repository's
required reading, read [README.md](README.md), [code_style.md](docs/code_style.md)
the [AST ownership decisions](docs/ast_model.md), and the contract owning the change. The [frontend contract](../specs/compiler_frontends.md)
records the current language-flavor and parser boundaries.

## Current direction

- Work in small end-to-end slices. Agree each slice's scope and verification
  checklist with the user before implementation, then complete the agreed checks
  and review before moving to the next slice.
- Run the PHP compiler sequentially on 64-bit PHP 8.5+, using ordinary `php`.
  ZTS and `parallel` are not requirements. Read [execution guidance](docs/execution.md).
  PHP is the bootstrap implementation. The agreed native ownership model includes
  private lowering; concrete shared lowering needs are reviewed individually.
  Do not restore PHP worker runtimes, transport adapters,
  phase-receipt serialization or fact export/import solely for interpreter isolation.
- Keep stage ownership and sequencing explicit. Source syntax and semantic facts
  remain ordinary in-process objects. The coordinator completes collection before
  preparation and completes preparation before lowering/publication. Sequential
  execution does not prove native thread safety.
- Before changing frontend, preparation, lowering or orchestration, read the
  [thread-safety plan through lowering](../specs/planning/two_traversal_preparation.md).
  It records exclusive source/body ownership, early declaration notifications into
  a coordinator-owned pending index (direct calls in PHP, synchronized delivery in
  native execution), publication only after frontend completion,
  relationship resolution before contract preparation, owner-private reference
  indexes, immutable external reads, phase joins and incremental reader lifetimes.
  It is an agreed design, not proof of current implementation safety or authorization
  to implement a scheduler, AST-fact refactor or native synchronization machinery.
- Author the compiler in PHP 8.5. The later Simple C++ port will be manual or
  assisted; do not impose the donor's PHP-to-PHP++ converter restrictions here.
- Simple C++ is one language with cpp/php/js syntax flavors. There is one PHP
  flavor. PRJ-selected composed profiles will control policy through common
  analysis; this does not claim profile loading or enforcement already exists.
- Mine `compiler/my-try/` and `compiler/my-try-cpp/` for reviewed concepts and
  useful proofs. Their implementations are evidence, not semantic authority.
  Do not copy their historical work queues, branch instructions or infrastructure.
- Preserve unrelated work. Keep changes within the agreed ownership area;
  discuss a newly discovered cross-owner redesign rather than silently adding
  compatibility machinery. Do not create empty stages for future functionality.

## Data and processing ownership

Keep one `ast_file_node` per frontend result. Prepared facts belong to their owning
AST node, with a nearby doc comment stating readiness and null meaning. Do not grow
a second prepared AST or restore parsed/collected file wrappers. Remaining prepared
records are migration work, not a reason to introduce more identity wrappers.

Every class, interface, trait and enum needs a role doc comment. Include a short
code example in that comment when it clarifies the represented syntax, prepared
facts or usage; keep examples within the documented support boundary. See
[coding style](docs/code_style.md#php-types-and-declarations).

For all PHP compiler work, maintain the adjacent native-intent comments specified
in [coding style](docs/code_style.md#native-size-and-collection-intent): integer
properties, parameters and returns need reviewed widths/ranges; arrays need
element/key or shape descriptions; integer enums declare backing intent once.
Annotate constants where representation matters. These are documentation, not
converter directives or PHP storage claims. Preserve runtime checks and signed
rejection paths; do not invent widths or annotate ordinary locals mechanically.

- Keep retained data in its owning stage's structures files and processing in
  named workers. Data types own their representation: initialization, local
  invariant checks, accessors and small typed queries/updates belong there.
  Do not force data-only records or one companion worker per data type.
- Workers own scanning, parsing, collection, analysis, traversal, scheduling and
  publication decisions. Records must not retain workers or gain stage policy
  merely because they store the affected fields.
- Stage workers must not invoke later-stage processing. The coordinator sequences
  stages; shared records may carry facts without owning the producers' algorithms.
  Every compiler operation has one traversal owner.
- Flavor parsers retain syntax and intent in one common model. Common analysis
  owns name/type resolution, profile enforcement and source-language diagnostics.
  Backends consume settled facts; C++ spelling belongs to the C++ backend, and
  Clang must not become the source-language semantic validator.
- Reject statically invalid combinations in shared analysis as that stage is
  implemented. Retain runtime checks for valid operations with value-dependent
  failures. Successful parsing alone is not semantic validity.
- Use specialized syntax node families with named, typed fields. Do not replace
  them with generic payload registries or child arrays for processing convenience.
  Keep semantic type identity, storage representation and operation capability
  distinct. Required fields are nonnullable and populated before use/publication;
  genuine absence is explicit. Non-owning intent alone does not imply nullability.
- Prefix all AST subclasses with `ast_`. Names and optional written type names
  live directly on their owning declarations. Resolved type definitions live in
  protected declaration fields with checked accessors. Calls link to function
  declarations; symbol indexes do not introduce identity wrappers. Introduce a
  specialized preparation record only for additional semantic data, not a repeated
  identity/type. Declaration traits share data/local invariants, not analysis policy.
- AST nodes inherit token-based `[start, end)` positions and one syntax-parent
  link from `ast_node`. The file root owns tokens/source; recover byte positions
  from tokens for diagnostics and literal decoding. Do not reintroduce per-node
  source pointers or span/range wrappers. Semantic references are not parents.
- Preserve source bytes, spans, stable identities and syntax order. Indexes are
  lookup aids, not new semantic owners; document their maintenance when introduced.
- Token kinds have explicit integer IDs in 1..127, with zero reserved. All keywords
  share one kind; consumers read their source spelling. Do not infer a packed PHP
  representation from the eventual native layout.

The donor's incremental lifecycle, observed lookup, storage subscriptions and
backend change-consumption protocols are not implemented requirements of this
slice. Introduce such mechanisms only with their owning stage and a concrete need.

## Validation and documentation

Use the focused commands in [README.md](README.md) for affected behavior. PHP
checks do not prove native portability. Do not automatically run donor conversion,
native validation, legacy STAN, LLVM work or the donor's test pool. No local style
checker has been imported; review presentation rules directly.

Formatting-only work preserves behavior, APIs, ownership, literal contents and
statement order. Keep current guidance and unresolved debt with their owners;
do not append contradictory status sections or present historical proof counts
as current requirements. Performance claims require relevant measurements.

These rules consolidate the applicable parts of the donor
[PHP operating rules](../../compiler/my-try/AGENTS.md) and
[C++ operating rules](../../compiler/my-try-cpp/AGENTS.md). Current user decisions
and repository semantic authorities take precedence over donor conventions.
