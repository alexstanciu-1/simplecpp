# Completed file frontend
Doc Status: supporting

File_Frontend retains its token/syntax references, ordered definition IDs and entry
body ID. Structural validation now checks vector bounds explicitly before looking
up entry, root and definition nodes. It preserves the existing invalid-frontend,
invalid-definition-index and incomplete-definition-index errors. It still validates
root/index consistency, not every descendant's grammar or semantics.

Debug JSON is emitted directly from the existing schema, with Source_Json quoting
and byte slicing for spelling-bearing nodes. Syntax_Kinds owns enum-name export next
to the vocabulary, following the selected target's local enum-name requirement.
Output ordering and optional text fields remain unchanged.

Construction now requires the exact token buffer and syntax tree. The parser and
its two storage-test construction sites bind them directly, then assemble the same
file/index metadata as before. This avoids uninitialized-field reads rejected by
STAN. Default empty construction is intentionally removed; no compatibility shim
or converter extension is introduced. The constructor binds inputs, not complete
structural validity; validate() still owns the latter.

The PHP/native harness covers valid root/entry/definition structure, shared identities,
exact JSON with multibyte spelling, out-of-range entry/root/definition IDs, wrong file
identity, incomplete definition chains and recovery after rejected validation. Retained
parser fixtures continue to exercise real producer output and broader host behavior.

Evidence: `specs/planning/compiler_migration/results/frontend-record-01/summary.json`.
PHP/native validation passes on `2f0d667f38a35ff02ef77e813f409189cba2d032`, as do
all seventeen retained compiler fixtures. Twenty-five production files are ready.
Frontend storage, segmented acceptance and the full parser still need migration.
