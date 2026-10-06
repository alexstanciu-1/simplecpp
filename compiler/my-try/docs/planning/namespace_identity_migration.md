# Namespace identity and body ownership prerequisite
Doc Status: planning

Recorded 2026-10-06 after the approved namespace/import discussion.
Language rules are agreed in [the v0.2 contract](../../../../specs/namespaces_v0_2.md).
Status: coordinated migration approved by the user; implementation in progress.
Canonical identity/backend checkpoint implemented below. Namespace body syntax,
qualified-path lookup and their lifecycle integration remain unfinished.

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
