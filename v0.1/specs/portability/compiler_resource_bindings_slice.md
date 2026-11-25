# Checked ownership operand binding
Doc Status: supporting

Resource_Bindings connects accepted checked values/arguments to static resource
locations. Zero-based semantic parameter positions are converted explicitly to the
checked body's one-based argument queries. Existing local borrows are required for
call operands. Copy/source binding retains only statically named field projections;
index and element selection reject with the preserved ownership-contract diagnostic.
Summary binding follows explicit parameter membership, while runtime metadata
selects owner/destination positions independently of map iteration or source names.

The prototype private trait's body-dependent lookup now has an explicit checked-body
owner, separate from bound contract application. Failure retains node/reason and
projects the exact frontend path/start/length into Annotation_Diagnostic. No name
resolution or inference is added to the converter. Allocation/resource type validity
remains the already checked signature/contract owner's responsibility.

Nine PHP/native cases use real parsing, resolution, signature/local preparation and
body checking: root and nested field paths, reordered arguments, summary/runtime
position mapping, literal rejection and indexed-owner rejection. Expected locations
and reasons are explicit; failure spans are checked against the actual syntax node.
First native build passes. One PHP fixture correction replaced a second source
function's unprepared generic storage signature with proved storage-provider calls.
That complete concrete-preparation coordinator remains a separate dependency; the
fixture does not claim to implement it. Complete allocation traversal, fixed-point
flow and ownership acceptance remain unfinished.

Run `python3 compiler/tests/resource_bindings/run.py --results FRESH --target-checkout TARGET`.
Evidence: `specs/planning/compiler_migration/results/resource-bindings-01`.
