# Project entry contract
Doc Status: supporting

Status: implemented in the PHP prototype. The manifest-selected file body and
named functions now pass through the same signature and body-checking stages.
[Native builds](native_executable.md) now emit LLVM and prepare the hosted entry
adapter. `--output` requests object generation, linking and publication.

## Current policy

- Execute only the file entry selected by the manifest. Filename and discovery
  order have no special meaning.
- Supporting files contribute declarations. Their implicit bodies must be empty;
  executable top-level statements report an unsupported-initialization-order
  diagnostic at the first such statement. Future declaration initializers will
  need an explicit ordering rule before they become supported here.
- The language entry takes no parameters and returns `int`, selected through the
  catalog's qualified `entry_return_type` reference. Its body must return a value;
  bare return and fallthrough are diagnosed by the common checker. No implicit
  zero result is inserted.
- The language result is separate from a native process result. The
  [target-owned startup adapter](native_executable.md#native-entry-contract) calls
  this callable using a probed native width and an explicit status-conversion
  policy. This conversion does not authorize implicit source-language casts.

These are the agreed first-slice rules, not a complete initialization model.

## Ownership and common path

[`Entry_Resolver`](../../src/04_analyze/resolve_types/main_prepare_entry.php) consumes
participating sources, current project symbols and fixed catalog. Discovery
resolves the manifest entry once and records its file ID in `Source_Set`;
`entry_file()` returns that snapshot's shared row without filesystem access.
Source debug JSON exports this identity as `entry_file_id`.
After declaration collection, before call-name resolution, it selects the entry
and validates declaration-only supporting files. Its immutable `entry_contract`
references the existing file-entry symbol and authoritative return definition.
It does not copy the body or create a synthetic AST function declaration.

The signature coordinator selects named functions and that one implicit callable.
Workers obtain a return definition from the source annotation or entry contract;
the same join interns the type/signature and produces `Callable_Signature`.
Every contract has an exact AST and body anchor. The implicit callable's
declaration/annotation IDs are zero because those source nodes do not exist.
Empty supporting-file entries remain symbols, but have no execution contract.

Body selection iterates resolved callable contracts. The existing `Body_Worker`
checks statements, literals, calls and conversions for both origins without an
entry-specific body implementation. Their typed values, statements, dependencies
and join are shared. `Type_Resolution` exposes `entry_symbol_id` in debug JSON;
`signatures` and `bodies` include the selected implicit callable.

An entry-body edit follows ordinary body replacement and retains unaffected
functions. A callee body-only edit can preserve the entry's checked result;
changing the callee signature invalidates it. Manifest/catalog changes select
full work through the common stages. A different selected file withdraws the old
entry's execution contract. Preparation/checking failures preserve all accepted
stage observations together.

## Proof

[`program_entry.php`](../../tests/features/program_entry.php) covers the real
three-file chain, mixed named/implicit workers in reversed order, immutable phase
inputs, invalid signature origins, unchanged reuse, entry/callee edits, missing
or incompatible returns, failure/repair, unsupported supporting statements,
manifest entry replacement in a nested file, retirement, fresh-build equivalence
and exports. CLI simulation verifies the selected entry's signature and body
through two real refreshes. Native process behavior is not claimed by these tests.
