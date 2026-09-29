# Retained compiler model
Doc Status: supporting

The compiler has one incremental path. A fresh build starts with empty retained
state and processes all work; later runs select affected work through the same
owners. `Model` holds static roots shared by Compiler instances. Workers are transient.

| Root/owner | Retained data |
| --- | --- |
| `Model::$modules` | Ordered keyed modules; each owns sources keyed by module-relative path |
| `source_record` | File input, current token/parse results, scan revision and pending change state |
| `parsed_file` | Typed file AST, scope owners, collection, completion state and token snapshot |
| `collected_file` | Canonical occurrences and semantic/generation handoffs for that source |
| `language_scope`, `global_scope` | Built-in/runtime definitions and shared source indexes; global parents to language |
| AST/collected definitions | Specialized facts and retained declaration/body work |
| `prepared_files` | Successfully completed file records referencing the retained syntax |
| `cpp_program` | Retained declaration/body fragments and cached text/includes |
| `cpp_files`, `llvm_files` | Published backend artifacts |

`Model::tokens()`, `syntax_files()` and `collected_files()` are snapshots derived
from source records, not parallel retained indexes. `Compiler_Lifecycle` owns root
initialization/reset; Model does not call processing code.

## Source and symbol identity

Modules save their key/name, declared path, resolved path and input position. Any
configuration/order change forces a full rebuild. Files save module-relative paths;
IO boundaries compute full paths. A source record observes its owning module.

The parser reuses matching declaration nodes and their canonical collected identities.
Only applicable name-bearing syntax has an occurrence backlink. Retained names are
independent of token indexes. Duplicate names remain distinct candidates. The
collector registers symbols during parsing; there is no collection pass or second
symbol model. See [analysis](../../04_analyze/README.md) for typed collected roles.

Function signatures and bodies have separate work identities. Fields/parameters
settle with their definition; locals settle with their body. A body is a processing
unit, not a symbol. Replacing body syntax transfers its retained work identity.
Unchanged bodies retain nodes, scopes, occurrences and facts.

## AST and scopes

The property-free `ast_node` base supplies operation contracts; concrete nodes own
typed syntax fields and uint32 spans. There are no payload objects, sibling chains
or inspection parents. [AST layout](ast_layout.md) owns detailed traversal rules.

Parsed files retain file/executable scopes. Functions own signature scopes; structs
own member scopes. Occurrences observe their enclosing scope. Global pools index
the same declarations; built-ins live in the language/runtime scope. Scope methods
encapsulate membership; `Scope_Lookup` performs lexical/publication lookup.

Type definitions live under `compiler/types/`. Built-ins are initialized at startup;
source records retain their canonical definition identity. New runtime-library JSON
intake and broader constructed types remain future work. Reserved-name validation
is deferred; ordinary parent lookup currently applies.

## Structure and processing boundary

Structures may initialize data, enforce local invariants, expose typed access,
clear attached facts, and forward operations to their workers. Workers own semantic
algorithms, scheduling, dependency maintenance and publication. Retained structures
do not keep processing contexts or workers.

Preparation dispatch passes one invocation-local context directly to nodes. Typed
work records dispatch scheduling/rebuild/settlement to `Preparation_Worker`.
`File_Preparation` is the standalone entry adapter to that scheduler. Facts are
attached to syntax, not token-indexed maps. See [preparation](../../04_analyze/prepare/README.md).

Maintenance is node-owned traversal through named owning fields. Workers implement
`enter`, `edge` and `token_index`; edge handling chooses recursion. Inspection
iterators are not used to run preparation, generation or cleanup.

## Retained C++ generation

Successful semantic changes/deletions accumulate in `collected_file.preparation_changes`.
The backend retains fragments keyed by existing work owners, with completion versions,
dirty state, text, includes and record dependencies. It renders selected fragments;
unchanged bodies are not traversed. Assembly consumes the handoff only on success.
Failures retain pending work and withhold completed output; unexpected failures
request a full rebuild.

Assembly preserves the current single-`main.cpp` layout and orders record dependencies.
It reconstructs includes from live fragments. Generated names use role prefixes and
saved source names; temporary numbering is per body. Future namespaces, overloads
and richer shadowing require a naming review. No disk writer or Ninja integration
is implied. Partitioning is tracked in [debt](../planning/incremental_strategy.md).

## Lifecycle and legacy boundary

[Incremental lifecycle](../lifecycle/incremental.md) owns synchronization, appended
tokens, failure and deletion policy. [Ownership](ownership.md) owns reference intent;
[Storage](../storage/STORAGE.md) owns membership semantics.

The [LLVM experiment](../../05_backend/llvm/README.md) retains separate token-indexed
name/template preparation only for regressions. Its maps and native type choices
must not define shared semantics. Future LLVM development must consume shared facts.
