# Source snapshot owner and indexes
Doc Status: supporting

`read_sources\Source_Set` is now in the portability-ready set, together with its
`Source_Json` string-escaping helper: sixteen production files in total. This
adapts the existing owner, without adding compiler functionality or filesystem,
worker, process or lock support.

## Representation and ownership

Folder/file membership uses explicit vectors; logical-ID-to-row and path-to-ID
indexes use typed hashes. IDs remain distinct from row offsets. Index rebuilding
validates candidate membership before adopting all three indexes together.
Deleted rows remain addressable by ID but absent from live path/folder indexes.

Acknowledgment constructs a new owner and explicitly copies its membership,
indexes, entry and next-ID state. Only pending rows receive new records with
pending work cleared. Other rows, folders and immutable buffers remain shared.
The new removal list is empty. Consumers must respect snapshot ownership; shared
records do not become deeply immutable. New record fields must be included in
this explicit copy and its proofs. PHP `clone` is not enabled in the converter.

## Debug JSON

The existing `to_json()` schema and field order are retained using explicit
schema serialization in Source_Set. Source_Json quotes valid UTF-8 using PHP's
default JSON escaping (including surrogate pairs), and malformed UTF-8 still
produces the existing wrapped export error. Null versus empty folder selections
remain distinct. This is a dense-list, fixed-schema debug export, not a general
JSON implementation, reflection facility or persisted compiler representation.
Code-point indexing may be quadratic for long strings; this non-hot exporter has
no new performance guarantee.

## Bounded converter addition

Literal class `implements` lists pass through, including qualified names and
multiple interfaces. The converter performs no interface resolution or conformance
checking. Source_Set's two marker interfaces are proved by the cumulative native
build. Inheritance and broader polymorphic behavior are not added by this slice.

The pinned v0.1.76 target exposed two shapes to avoid here:

- Returning only inside try/catch triggered a missing-return STAN diagnostic.
  A result local with a return after the handler preserves the exception flow.
- Passing `$this` to a class-typed static helper produced a raw-pointer versus
  shared-reference C++ mismatch. Schema serialization stays on its owning object;
  only scalar quoting/classification is delegated to Source_Json.

These are recorded observations, not claims that the target lacks these language
features generally. No generated C++ was patched and STAN remains enabled.

## Evidence

`tests/portability/source_set_oracle.php` compares thirty snapshots against the
frozen pre-adaptation owner in `tests/portability/reference/source_set_before.php`.
That file is a test oracle, excluded from conversion and compiler loading, not a
second development home. Comparisons cover exact JSON, acknowledgment, previous
snapshot preservation, buffer/row identity and membership. String cases include
all ASCII bytes, Unicode/supplementary characters and malformed UTF-8.

The cumulative PHP/native harness additionally proves lookup errors, tombstones,
entry selection, duplicate rejection without partial index adoption, repeated
acknowledgment, exact populated/empty JSON exports and escaping. The validation
driver runs the oracle on its fast path. Check-command regressions cover literal
implements emission and rejection of unsupported inheritance/malformed syntax.

[Recorded validation](../planning/compiler_migration/results/source-set-01/summary.json)
includes the clean pinned target, source hashes, native output and all sixteen
retained compiler fixtures. Earlier failed native diagnostics are retained there.
The source reader/coordinator and whole compiler are not yet portable.
