# Namespace identity and body ownership prerequisite
Doc Status: planning

Recorded 2026-10-06 after the approved namespace/import discussion.
Language rules are agreed in [the v0.2 contract](../../../../specs/namespaces_v0_2.md).
Status: coordinated migration implemented within the agreed bounded scope.
Canonical identity/backend, executable-work links and source-body inventory consumers
are implemented below. Named namespace parsing/reconciliation and scope-retention
integration are implemented with bare-name and dependency-observed qualified lookup.
Qualified-reference parsing is integrated for supported calls, named types and
registered constant references. Body-local plain/function/constant imports, aliases
and homogeneous groups are integrated. Scalar source constants are integrated and
remaining cards are reconciled in [Chapter 08's conclusion](../catalog/08_project_symbols.md#positive-pass-conclusion--2026-10-06).
The checkpoints below retain their historical next-step/proof boundaries.

## Why this is more than adding namespace parsing

The existing implementation has truthful flat-program owners, but several of those
owners assume the whole file has one global declaration environment and executable
body. Extending only parsing/scope lookup would leave independently wrong identities
and lifecycle behavior in other owners:

- `03_parse/parser.php`: file parsing reconciles one root declaration inventory and
  one function-body identity; collection publishes file declarations to one global
  scope through `compiler/scope_publication.php`.
- `03_parse/scopes/lookup.php`: `visible()` substitutes a published scope for its
  local scope. That cannot also represent a body-local import environment without
  explicitly distinguishing namespace publication from lexical lookup.
- `compiler/types/source.php`: source type registration is supplied the declaration's
  short name. Namespace identity must be available at this boundary without replacing
  declaration identity or creating alias-derived types.
- `04_analyze/prepare/worker.php`, `status.php` and `program.php`: executable work,
  dirty-state consumption and entry admission address `root->body` directly.
- `05_backend/cpp/callable_bindings.php`, `types.php`, `generate.php` and `artifacts.php`:
  callable/type headers, source references, record-cycle keys and forward declarations
  use short names. `A\Box` and `B\Box` must not share these keys. Simply passing a
  backslash-containing path to `source_name()` also does not produce valid C++.
- `03_parse/collect/retirement.php` and coordinator lifecycle: namespace contributions
  and their published memberships must retire without removing another file's
  declarations or losing observers during failed-parse recovery.

## Recommended coordinated migration

1. Model owns a canonical namespace registry, including the global namespace.
   Namespace identities own published declaration scopes. Namespace parent identity
   expresses path structure, not implicit lexical fallback. Language scope remains
   distinct from global user declarations.
2. Parsed files own source namespace bodies and their local declaration/import
   environments. Each body observes its namespace identity and owns its syntax;
   source occurrences are not shared merely because their namespace names match.
   Preserve the shared statement-body abstraction instead of inventing another
   statement container. Global source uses an implicit global body through the same
   path as explicit namespace bodies.
3. Collected definitions retain their local spelling and namespace identity. Expose
   canonical source identity through one typed query; aliases never change it.
   Source type registration and namespace publication consume that identity.
4. Parsing/collection owns declaration registration and reconciliation. Namespace
   interning/publication follows the existing synchronized shared-write protocol;
   workers must not concurrently mutate an unprotected registry.
5. Shared lookup distinguishes body-local bindings, namespace members and registered
   language names. Analysis observes qualified lookup dependencies, including misses,
   so namespace/target additions, moves and deletions invalidate dependent work.
6. Analysis owns executable-body work and selected-entry admission for the body
   model. Coordinator retirement/reset handles every body and namespace contribution
   through the existing failure/retry ordering. No stage invokes a later worker.
7. C++ naming/artifact workers consume canonical identities. Preserve existing global
   names/artifact paths where possible; namespace-qualified symbols must have unique
   deterministic headers, definitions and forward declarations across output units.
   This is backend-owned encoding, not C++ spelling stored in semantic records.

Deliver a vertical slice: global behavior through the repaired path, semicolon/braced
namespace bodies, cross-file namespaced functions/structs and rooted/current-relative
lookup. Keep imports for the following slice; their agreed body-local ownership must
not be precluded by this migration. Do not retain a second flat execution pipeline.

## Risks and validation cost

The main risks are invalid retention after namespace edits, accidental global-user
fallback, short-name collisions, duplicate publication, lost invalidation subscriptions
and stale backend fragments. This needs more than a parser fixture:

- Existing global project/body/function/type behavior remains valid.
- Same short function/struct names in different namespaces coexist and compile;
  true duplicate canonical declarations reject before C++ emission.
- Different files contribute to one namespace; rooted and relative references select
  the expected definition; prohibited global/parent fallback rejects.
- Namespace/body addition, movement, removal, failed parsing and repair match a clean
  compilation; unrelated identities and output bytes stay stable where applicable.
- One entry body is selected; cross-body execution merging rejects.
- Generated headers stand alone, namespaced units compile separately, and ordinary
  body edits preserve runtime PCH and unrelated object reuse.

Use focused PHP and generated-program proofs plus conversion where portable source
changes require it. Rebuilding the compiler itself remains explicitly requested work.
No cache optimization, import execution, class/static-member expansion, LLVM work,
runtime redesign or native toolchain-generalization is part of this prerequisite.

The smallest reasonable choices are the coordinated migration above, or a model-only
checkpoint with namespaces still disabled. The latter must not be presented as
working qualified lookup. A parser-only/string-prefix shortcut is not recommended.

## Canonical identity/backend checkpoint — 2026-10-06

`Namespace_Registry` now owns canonical identities and their published scopes. The
global scope is obtained through that same registry; both full compilation and syntax
resets replace it. Scope backlinks are weak; namespace scopes parent to language
scope, never another namespace's user-symbol scope. Registration requires the existing
parser publication reservation when invoked from parallel workers. No new parse-worker
registration calls are enabled yet; production initialization currently registers global.

Collected definitions expose `qualified_name()` independently of local `name` lookup.
Source type registration, C++ callable/type headers, source symbols, record-dependency
keys and forward declarations now use qualified identity. Publication still registers
the short name in its target namespace's pool. Backend separator encoding is reversible
and does not collide with literal `Uq`; existing global names/output paths are unchanged.

`tests/namespace_identity.py` explicitly supplies distinct namespace scopes to the
existing parser API, proves `A\Box`/`B\Box` and `A\answer`/`B\answer` coexist, and
compiles their separate generated units without PCH. It also checks interning, invalid
paths, language visibility without parent/global leakage, and reset identity.
This is model/backend evidence, **not** support for authored `namespace` declarations,
imports or qualified-reference syntax. That distinction is retained in its summary.

Next: source namespace bodies and their retained work/retirement path, then parser
declarations and dependency-observed qualified lookup. Continue under the existing
approval; no further semantic decision has been requested by this checkpoint.

Evidence on `738a1e7d` plus this checkpoint overlay:

- `/tmp/my-try-namespace-identity-20261006-5/summary.json`: model checks, separate-unit
  generated execution without PCH, and scope-model conversion.
- `/tmp/my-try-namespace-global-project-20261006-1/summary.json`: five retained PHP
  suites, 26 generated executions with/without PCH, 30 standalone-header checks and
  three transitive dependency checks; global artifact behavior remains intact.

Initial fixture corrections fixed a trailing-backslash PHP string and used the
standalone parser's explicit publication adapter instead of calling its task-only
shared publication path outside a work callback. No language/runtime behavior was
relaxed. The native compiler itself was not rebuilt. The portable-PHP skill informed
weak namespace backlinks and explicit typed storage receivers in the new registry.

## Executable-work ownership checkpoint — 2026-10-06

`body_work` now explicitly observes its executable `function_body_node` through a
weak link. Source-file provenance no longer implicitly selects the file root's body
for change queries, source-body preparation or C++ fragment rendering. Function and
source bodies share the same backend statement-rendering path; function signatures
still provide return context. Parser body replacement rebinds the observer while
transferring the existing work identity, without touching analysis scheduling state.

This removes a prerequisite assumption, not the entire single-body file model.
File selection, retirement enumeration and entry admission still await the source
namespace-body inventory. Namespace syntax and qualified lookup remain disabled;
the next step remains the approved body/parser/lifecycle migration, then lookup.

Evidence on `5aa37d61` plus this checkpoint overlay:

- `/tmp/my-try-body-work-20261006-1/commands.json`: seven focused PHP suites pass,
  including independent change flags for two body records sharing one source,
  replacement/repeated parsing, failed-parse repair and join-ordered retirement.
- `/tmp/my-try-body-work-project-20261006-1/summary.json`: five project PHP suites,
  26 generated executions with/without PCH, 30 standalone headers and three
  transitive dependency checks pass.
- `/tmp/my-try-body-work-conversion-20261006-1/commands.json`: parser and preparation
  directory conversion pass. Whole C++ backend conversion encounters the preexisting
  `structures.php:42` readonly enum-field restriction; the field is unchanged from
  the base revision and is not repaired in this ownership slice.
- `/tmp/my-try-body-work-emitter-conversion-20261006-1/commands.json`: the changed
  emitter file converts independently. This is not whole-backend or native compiler
  validation. The compiler itself was not rebuilt.

The portable-PHP skill informed the weak body observer and typed query boundary.

## Source-body inventory checkpoint — 2026-10-06

Files now own `Storage<namespace_body_node>`. Each occurrence owns its declarations
and executable body and observes its declaration/import scope. Global parsing
constructs an implicit occurrence through this model; the old flat file fields are
removed rather than retained as a parallel representation. Single-body test/adaptor
clients use an explicitly guarded query. Parked LLVM is adapted only to that bounded
query; no namespace semantics have been added to LLVM.

Preparation, reset, retirement and C++ traverse all active source bodies. Analysis
selects one executable body and rejects multiple nonempty bodies; a declaration-only
file selects its first empty body. The prepared selection is invalidated before
retry/reset and upon removed-body retirement. C++ consumes that fact and does not
decide which body to execute. Removed bodies have a separate owning retirement
inventory; analysis unlinks work before frontend disposal releases it. An empty
retirement inventory retains identity across no-op preparation, preserving syntax
purity. Token cleanup waits for outstanding body retirement. Reappearing deleted
sources create fresh syntax within their retained source/collection identity.

This completes the inventory-consumer prerequisite, **not** authored namespace
syntax. The next parser slice must select/reconcile bodies by namespace identity
and source occurrence, preserve scope owners through failed parsing and semantic
retirement, publish declarations into the appropriate namespace under synchronization,
and retain the implicit-global path. Qualified-reference parsing and observed lookup
follow. Do not describe the manually assembled multi-body proof as parser support.

Evidence on `b178859c` plus this checkpoint overlay:

- `tests/namespace_bodies.py`: eight focused PHP suites, two generated executions
  without PCH and parser/preparation conversion. Final evidence:
  `/tmp/my-try-namespace-bodies-final-20261006-2/summary.json`.
- `/tmp/my-try-namespace-body-project-20261006-1/summary.json`: five project suites,
  26 generated executions with/without PCH, 30 standalone headers and three actual
  transitive dependency checks pass.
- The changed-consumer sweep initially caught deleted-source revival and no-op
  retirement-container replacement; both were repaired. Rechecks in
  `/tmp/my-try-namespace-body-repairs-20261006-1/commands.json` pass multi-body,
  incremental preparation, S2S syntax-purity and parked LLVM suites. The first sweep
  also omitted the required output-directory arguments for S2S/LLVM; the recheck
  supplies them. No root-level output files were created by the failed attempt.
- Interpolation and logical diagnostic expectations fail identically on an isolated
  `b178859c` baseline; `/tmp/my-try-namespace-body-baseline-20261006-1/commands.json`
  records expected-failure reproductions, not passing behavioral suites.
- Local edits in `tests/dependency_cleanup.php` retain their failing shared-lookup
  expectation and are not incorporated into this change. Its committed fixture,
  with only the body-access migration, passes in the changed-consumer sweep.
  `structure_access.php` now initializes the language model before parsing types;
  its previous missing initialization was exposed by its focused check.

The portable-PHP skill informed weak scope/entry links and explicit typed body
inventories. No native compiler rebuild or full-backend conversion is claimed;
the previously recorded readonly enum-field conversion limitation remains separate.

## Authored namespace-body checkpoint — 2026-10-06

The tokenizer admits namespace separators. The parser accepts semicolon and braced
named namespaces (including multi-segment declaration paths), reopened occurrences
and braced global bodies. One source-body parser handles those and implicit global
source. Nested namespace declarations remain outside this slice. Body reconciliation
uses canonical path plus occurrence order; a namespace move creates a new declaration
environment and retires the old contributions instead of relabeling definitions.

Each selected occurrence configures the collector's source/publication scopes.
Production namespace interning and publication hold the existing parser task lock;
the standalone parser remains a sequential adapter. Standalone publication now visits
each body and selects its namespace destination. Bare-name lookup sees same-namespace
cross-file declarations and registered language names, without parent/global user
fallback. **Qualified reference syntax and imports are still not implemented.**

Before an update, prior lexical scopes enter `collected_file.retired_scopes`. They
remain strong owners while old collected records and partial syntax can observe them,
including across failed joins. Analysis retires work first; frontend disposal removes
old memberships before releasing those scopes. The syntax-purity proof excludes this
explicit retirement bookkeeping, not active syntax or scope contents. Scope-retention
cost and cleanup observability belong to the user's non-blocking
[CH15-CLEANUP-PERF-01](../catalog/15_blocking_debt.md#ch15-cleanup-perf-01--compiler-cleanup-performance-and-ide-debug-endpoint).

Proof on `84f80a53` plus this checkpoint overlay:

- `tests/namespaces.py` covers both namespace spellings, reopening, cross-file typed
  declarations, duplicate rejection, no fallback, no-op/body edits, namespace moves,
  failed partial parsing/repair, return to implicit global source and standalone scope
  retention. Generated fixtures compile as separate units without PCH. Tokenizer and
  parser source convert; this does not prove native concurrency or compiler execution.
  Evidence: `/tmp/my-try-namespaces-final-20261006-3/summary.json` (five PHP suites,
  nine generated programs). Final scope-lifetime and empty-body rechecks, including
  parser conversion: `/tmp/my-try-namespaces-final-check-20261006-2/commands.json`.
- `/tmp/my-try-namespaces-project-20261006-1/summary.json`: existing project checks,
  26 generated executions with/without PCH, 30 standalone headers and three transitive
  dependency checks pass.
- `/tmp/my-try-namespaces-regression-20261006-1/commands.json`: S2S syntax-purity,
  parked global LLVM, namespace identity/body, collection and observed-lookup suites
  pass. LLVM now explicitly rejects named/multiple namespace bodies rather than
  accidentally applying its flat-name behavior; its semantics are not expanded.

Initial checks caught a missing backslash token and duplicate temporary scope retention;
both were corrected. A duplicate-name fixture now checks the existing duplicate
diagnostic rather than an assumed ambiguity message. No semantic rule was relaxed.
The portable-PHP skill guided nullable boundaries, weak observations and typed scope
inventories. The native compiler was not rebuilt. Next: qualified-reference syntax
and dependency-observed path lookup; then the agreed body-local import families.

## Qualified lookup model checkpoint — 2026-10-06

`Source_Name_Path` shares source-root/current-namespace path interpretation across
plain and observed lookup. Functions, types and constants keep independent exact
member pools. Qualified queries do not search parent namespaces, alternate tails or
language pools; bare-name language availability is unchanged. This checkpoint targets
qualified source members, not new qualified runtime aliases or member syntax.

Plain lookup is read-only. Analysis may intern an empty namespace after the parse
join and subscribe to its requested member using the existing observation records.
Later declarations populate the same scope. The existing worker detects additions,
moves, deletions and ambiguous membership without a second invalidation graph.
These identities live until syntax reset; no new cleanup policy is introduced.
Preparation is currently sequential; parallelization must synchronize this interning.

The old `generators/php/src/Generator/NameRegistry.php` can search global candidates
and ancestor-anchored tails. That historical lowering behavior is deliberately not
copied: the v0.2 contract selects only the rooted or current-relative namespace.

Proof on `eea4b237` plus this overlay:
`/tmp/my-try-qualified-lookup-20261006-3/commands.json` records four passing PHP suites
(`qualified_lookup`, `observed_lookup`, `namespaces`, `incremental_preparation`).
The new fixture supplies a qualified name directly to the retained model and exercises
ordinary preparation/retry/cleanup, including absent-namespace addition, target moves,
duplicates and consumer removal. An initial cleanup assertion incorrectly expected
the whole pool to lose subscriptions; the corrected proof checks removal of the
specific consumer in both directions, preserving declaration consumers.

The portable-PHP skill informed explicit scope ownership and nullable guards. No
conversion, native compiler rebuild or generated-program execution is claimed for
this model slice. **Qualified-reference source parsing remains next**, followed by
body-local imports. The shared namespace runner includes the new model fixture.

## Qualified source-reference checkpoint — 2026-10-06

One parser name scanner now serves consumption and lookahead for rooted and
current-relative paths. Existing call, constant-reference and named-type nodes retain
the complete spelling and token span. Local/field/parameter/return types, template
type arguments, cast lookahead and construction use the same type path. Nullable
declaration lookahead now reuses that scanner instead of maintaining a second type
grammar. Parsing does not resolve the path or mutate the namespace registry for a
reference; collection and analysis keep their existing ownership.

The qualified lookup recovery fixture now starts from authored source, rather than
manually changing a collected name. Source constant declarations, imports, qualified
runtime aliases, new member-call features and template-call execution remain outside
this slice. Existing type/wrapper/constructor admission still applies after parsing.
There are no C++ emitter changes: lowering consumes the already resolved canonical
definition identities and artifact dependencies.

Evidence on `12c6a802` plus this overlay:
`/tmp/my-try-qualified-parser-20261006-4/summary.json` records six PHP suites,
16 generated-program executions without PCH, and tokenizer/parser conversion.
Rooted/global/current-relative calls, rooted/relative struct locals, qualified
signature/field types, construction and registry-supplied constants are exercised.
Syntax-only nullable/template payload checks do not claim broader wrapper admission.
The initial construction fixture tried shared-handle field access, outside existing
member-access support; the corrected fixture isolates qualified construction.
The compiler itself was not rebuilt. The portable-PHP skill guided explicit types
and the shared scan/consume structure. Next: the agreed body-local import families.

## Body-local imports checkpoint — 2026-10-06

The agreed plain-symbol import rule is recorded in the authoritative namespace spec.
One normalized import node carries the absolute target, alias and kind restriction;
homogeneous groups yield individual bindings. Namespace bodies own them and local
scopes index them without publication. Parser reconciliation preserves unchanged
binding identity, inspection/cleanup traverses imports, and analysis checks every
import including unused ones before processing declarations. Imports apply throughout
their body. Same-target repetition is idempotent; conflicting targets and namespace
declarations reject per symbol kind. Other files/reopened bodies receive no bindings.

Consumers observe local `import_name` membership plus existing qualified target pools.
No additional work-record family or cached target graph was needed. The validation
pass deliberately revisits all imports on each preparation invocation; caching that
pass is not part of this first implementation. A persistent `imports_changed` handoff
blocks generation from saved facts after import-only edits and is acknowledged only
after successful preparation. C++ uses original identities and headers; no aliases
are re-exported or emitted as forwarding functions. Parked LLVM explicitly rejects imports.

Evidence on `544e87a0` plus this overlay:

- `/tmp/my-try-imports-20261006-3/summary.json`: seven PHP suites, 24 generated
  executions without PCH, tokenizer/parser conversion.
- `/tmp/my-try-imports-extra-20261006-1/commands.json`: import signature retargeting
  and cross-file isolation, observed lookup, namespace-body/collection regression,
  S2S syntax purity and preparation-source conversion pass.
- `/tmp/my-try-imports-final-20261006-1/commands.json`: final import suite also
  verifies alias removal and repair against the prior clean output.

Initial fixtures used two effectful calls in arithmetic, outside existing operand
admission; explicit temporaries isolate import behavior. Conversion caught a missing
constructor-side object-hash annotation, fixed following the portable-PHP skill.
The purity snapshot excludes the new source-change handoff just as it excludes
`syntax_changed`, not import syntax or indexes. Native compiler execution was not run.
Source constant declarations, namespace aliases, mixed-kind groups, non-group comma
import lists, qualified runtime aliases and later class/static-member features remain
outside this slice. Next: reconcile remaining Chapter 08 cards and debt.
