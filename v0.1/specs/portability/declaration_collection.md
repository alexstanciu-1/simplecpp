# Source declaration collection and symbol records
Doc Status: supporting

The first rewritten `04_analyze` component consumes a valid, immutable
`parse\Frontend_Set` and a previous source `Symbol_Store`. It extracts file-local
facts and returns a `Symbol_Refresh` with a candidate store and collection changes.
It does not resolve references, compare definitions/bodies, or import runtime providers.

`File_Collector` emits the unnamed entry, top-level functions, constants and structs,
then each struct's methods. Templates retain the outer declaration and parameter
list; methods retain their wrapper, owner declaration and const-receiver flag.
Fields remain syntax owned by their struct, not project symbols. Facts are compact
value records containing syntax IDs and tags. Names are sliced from source bytes
when indexing, rather than copied into every temporary fact.

`Symbol_Record` binds a stable positive ID, owner, name and fact to the exact
frontend. `Symbol_Store` separates dense positions from stable IDs and indexes names,
paths and owners. Paths are supplied snapshot identities, consistent with the
rewritten parser; vector positions are never persistent file IDs. Source names are
currently global, exact and case-sensitive. Function/template-function and
struct/template-struct counterparts conflict; different categories may share a name.
No namespace field or external payload is fabricated for unmigrated capabilities.

The sequential coordinator builds a fresh candidate in current source/declaration
order. Exact retained frontends skip extraction and share immutable records. Changed
or freshly rebound frontends produce new records, matching prior IDs by name, kind
and semantic owner. Thus named declarations may move files without changing identity;
file entries are matched by path. Removed IDs are never reclaimed. The allocation
watermark permits `MAX_SYMBOL_ID + 1` as exhausted state and rejects further allocation.
Published stores, records and their input snapshots must be treated as read-only;
producer methods are not a public mutation protocol for accepted snapshots.

Collection changes contain a stable ID and added/removed/uncompared status; the
refresh owns both stores from which the corresponding sides are obtained. Exact
retained records need no row. Re-extraction is **uncompared**, even when bytes match.
No body-change or children-change conclusion is claimed before semantic comparison.

Duplicate diagnostics identify the second name's byte span and the first declaration's
path/start. Unsupported declaration wrappers report their span. Failed collection
returns an empty candidate with the previous watermark and no changes; it does not
mutate the baseline. Malformed frontend/store inputs raise framework exceptions.

The prototype collector currently rejects standalone constexpr/consteval wrappers,
although the parser accepts them. This rejection is retained and tested, pending an
explicit semantic review. Template-wrapped evaluated functions remain supported.

## Evidence and boundary

`compiler/tests/collect_symbols/run.py` proves 97 independent expected PHP/native
outcomes and nine additional PHP serialization assertions. It adapts collection,
identity, update and duplicate guarantees from the retained symbol_collection.php
and semantic_storage.php tests. It does not run their complete compiler-session harness.
See [saved evidence](../planning/compiler_migration/results/declaration-collection-01/README.md).

No converter, PHP framework or target change was required. This source-only synchronous
entry does not yet expose asynchronous collection plans/segmented joins. Runtime/family
provider symbols, comparison, exports, persistent caches and session publication remain
unmigrated. Preserve those prototype responsibilities when their actual dependencies
are adopted. `src-runtime-preparation` remains PHP unchanged.
