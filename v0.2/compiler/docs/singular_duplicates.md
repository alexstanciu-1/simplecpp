# Singular relationships and erroneous duplicates

Doc Status: supporting

If a property or per-key slot has zero or one element in valid code, and multiple
elements are necessarily erroneous, store one nullable reference. Preserve extra
invalid elements through a typed `duplicate_element` chain. Do not allocate a
candidate list for the normal zero/one case.

One declaring scope owns each chain. Other indexes reference the same nodes and
never maintain competing chains on that link. Resolve null as absent, one node as
unique, and a node with a successor as ambiguous. Do not silently select the head
of an ambiguous chain. Preserve every invalid declaration and its diagnostic.

This is a representation rule, not a new semantic rule. It does not turn overloads,
imports, or multiple observations into errors. Retain collections where multiple
elements are valid. Existing serialized report formats may materialize lists at
the inspection boundary; they are not the compiler's retained representation.

## Current implementation

- `symbol_entry.head` is one nullable declaration reference.
- `scope.declarations` maps each key to one head reference.
- `Scope_Collection.pending` is a flat object-identity inventory of all declarations,
  including many valid distinct names. There is no list per name. Temporary head,
  tail and comparison indexes hold one reference per key.
- Body scopes publish local-variable chains. The compilation scope publishes public
  function chains; file scopes only publish reference indexes.
- `ast_file_node.declaration_nodes` retains all syntax in order, including duplicate
  declarations. It is a valid multi-element syntax inventory, not a candidate list.
- Only successful publication rebuilds live chains, after old readers finish.
  Failure leaves old links intact. Previous sessions retain syntax, not historical
  duplicate chains. Stable entries survive removal and reactivation.
- Generic change status compares the complete old/new chain identity, including a
  changed tail with the same head. Dependency comparison captures transient scalar
  signature/ambiguity facts before links change; no declaration lists are copied.

## Similar-case review

This is a targeted source review, not a claim to have proved every collection in
all historical subsystems. The following are follow-up candidates, not authorized
cross-subsystem refactors:

| Location | Assessment |
| --- | --- |
| `compiler/my-try/03_parse/scopes/structures.php`: `variables`, `types`, `constants` backed by `Key_Storage_List` | Same-name uniqueness pools are candidates. Review aliases and live/tombstone ownership before migrating. |
| Same donor: `functions`, `methods`, `runtime_functions`, `imports` | Conditional candidates only. Overloads or multiple imports can be legitimate; establish semantic uniqueness first. |
| `compiler/my-try/04_analyze/prepare/data/structures.php`: `preparation_lookup.candidates` | Mixed lookup roles. Unique property/type/constant resolution could use the pattern; callable overload candidate sets cannot be converted mechanically. |
| `generators/php/src/Stan/StanDiagnosticCollector.php`: `collectDuplicateDiagnostics()` per-key buckets | A direct error-only multiplicity example. Its records/report format use arrays rather than AST identities, so an owner-level change is needed before using intrusive links. |
| `generators/php/src/Stan/StanDependencyResolver.php`: `buildResolutionLookup()` | Review exact qualified keys separately. Short-name search buckets can legitimately contain unrelated scoped declarations and need a collection. |
| `v0.2/tools/compiler_panel/report.php` | Updated to traverse the live chain. Lists are materialized only to preserve the inspection JSON shape. |

Current compiler collections intentionally retained: syntax statements/declarations,
function lists across a file, reference occurrences, diagnostic lists, source/module
inventories, dependency/observer sets and backend output units. Multiple entries in
these collections are valid. Existing singular fields such as a declaration's type
or a call's symbol already use one nullable reference.

Apply the rule in reviews and future development. Do not alter copied historical
samples/generated output to enforce it. Do not replace a general collection utility
with a duplicate chain when that utility also serves valid multi-element relations.
