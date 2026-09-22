# Source entry preparation
Doc Status: supporting

`resolve_types\Entry_Preparation::prepare(frontends, collection)` selects the implicit
callable of the manifest entry and enforces the prototype's supporting-file execution
policy. It consumes only fixed snapshots: no filesystem lookup, parsing, symbol
allocation, type inference or execution occurs in this phase.

Successful preparation returns an `Entry_Selection` whose `symbol()` is the exact
record in the supplied current symbol store. It owns that store and a stable symbol
ID, preserving the record's exact frontend/syntax origin. The manifest choice is the
frontend set's entry position, not the first file, a filename convention, or a named
function. Reordering files with the corresponding entry index preserves selection;
changing the manifest choice selects the other existing file-entry symbol.

Every supporting file must have an empty entry block. Functions, methods, structs,
templates and constants are declarations and remain allowed. An empty block statement
or an apparently dead conditional still counts as top-level executable syntax; this
phase does not optimize or evaluate it. The first offending statement in current
file order supplies the error path and byte span. The selected file may have an empty
body or arbitrary parsed statements; later phases own their semantic validity.

Failed policy validation returns a diagnostic selection with no usable symbol.
Invalid, missing, extra or stale frontend/symbol membership raises LogicException
before policy diagnostics. All accepted inputs and earlier selections remain
unchanged. Published results and their retained snapshots are read-only by convention.

## Important dependency: this is not the complete typed entry contract

The prototype's Entry_Resolver also binds the **exact catalog-selected integer
named_type_definition**, independently of native exit width/ABI. That definition carries
lifetime, integer-family and operation capabilities; it is not just a name or bit width.
The current rewrite has not adopted Type_Catalog, named definitions, or their provider
loader. `Entry_Selection` intentionally makes no return-type claim and cannot replace
the full contract for signature/body analysis. No placeholder definition, default native
int, fabricated catalog or stripped semantic payload was added.

Next is the type-model/catalog dependency needed for return-type binding. Preserve
shared definition identity, validation of integer entry type, and independence from
backend ABI when completing the contract. This splits the original logic at its actual
source-policy/type-definition boundary rather than reproducing a Step lifecycle shell.
The generic session lifecycle remains unimplemented.

## Proof

44 independent expected PHP/native outcomes cover selection, manifest switches,
reordering, empty entry/support files, allowed declarations, unsupported supporting
statements, UTF-8-prefixed byte diagnostics, repair, retained identity and invalid/stale
snapshots. Nine host serialization assertions cover purity. Cases adapt the relevant
source-policy guarantees from retained `tests/features/program_entry.php`; complete
signature/body/lifetime/provider/native-startup cases remain outside this component.

See [timing and evidence](../planning/compiler_migration/results/entry-preparation-01/README.md).
The converter/framework/target and `src-runtime-preparation` were unchanged.
