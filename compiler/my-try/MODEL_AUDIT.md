# Model data and storage separation
Doc Status: historical

Superseded by the [current model](MODEL.md) and [collection API](helpers/STORAGE.md).
Views, parallel AST registries and inline-row lifetime machinery are not current
requirements. The text below records earlier exploration only; references to removed
helpers/tests and “current” behavior describe that historical checkpoint.

The approved cleanup is implemented. See [MODEL.md](MODEL.md) for the current
ownership, segmentation and index-maintenance contract.

## Resolved worker links

- `Model::$tokens`, `file::$tokens` and `collected_file::$source` share
  `token_list` records, never Tokenizer workers.
- `Model::$syntax_files` replaces `Model::$parsers` and owns `parsed_file`
  records. Each shares its collection with `Model::$collected_files`.
- Parser consumes `token_list`; its cursor and Symbol_Collector stay private
  processing state. Published records have no links back to these workers.
- `Syntax_Nodes::category()` owns the former derived AST record query.

## Nested record storage

Numeric Storage owns module files, file tokens, collection entries, LLVM
functions and LLVM function parameters/blocks. Each parsed_file also owns all its
AST nodes, local scopes and separate stores of concrete specialization payloads. AST arguments,
type arguments, parameters, children, array elements and fields are read-only
Storage_View memberships into that file's node store. Single-record links remain
references to registered records. See docs/ast_storage.md.
Record constructors initialize these containers; they perform no processing.

## Deliberately deferred

Scalar lists and indexes retain typed arrays: collection position work lists,
scope name pools and template maps, LLVM strings. Scope pools may contain lists
of references to declarations owned by collection entries; they are indexes.
Storage currently accepts objects only. Sparse-key maps and scalar containers
need their own contract before migration; do not box scalars to force a fit.

Preparation records and worker registries remain transient outside model roots.
No native conversion, weakref policy, rollback, persistence format or incremental
invalidation is implemented by this cleanup.

## Proof

`tests/model.php` checks shared identity, nested Storage, a cycle-aware walk that
permits only data records/enums/Storage/Storage_View, failed-stage publication and
reset. tests/ast.php proves per-file ownership, unique registration and child views.
`tests/run.py` also covers PHP lint, storage behavior, LLVM rejection/purity cases,
19 baseline native fixtures, 28 call fixtures and the sample's exit value 9.


## Ownership hardening

Local scopes have a uniform parsed_file.scopes owner. Parser construction checks
kind/payload compatibility. Model restart methods invalidate downstream roots and
source token backlinks before rebuilding; they do not provide transaction rollback.
Token lists own the authoritative captured source text for their spans. Preparation
references are annotated, and emitted parameter records are independent copies.
The transient prepared-type registry remains shared across prepared files; its
native allocation boundary is deliberately not selected by PHP ownership comments.
