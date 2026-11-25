# Nullable class-handle representation foundation
Doc Status: planning

Starting revision bb7ad71c3 plus an explicit nullable-handle interpretation in
Type_Queries and native/concrete representation/provider transport integration.
Full snapshot, hashes and diff: `/tmp/compiler-bootstrap-nullable-representation-01`.
Reproduce with `python3 compiler/my-try/tools/bootstrap_probe.py --results FRESH`.

Conversion: 4.69 s / 194,968 KiB RSS; 269 PHS files / 1,962,398 bytes.
Bootstrap: 200.18 s / 470,180 KiB RSS (459.2 MiB), 451.0 MiB PHP heap.
Initialization: 0.304 s; sync/parse: 4.076 s. Diagnostic text set unchanged;
nullable-value boundary remains first. No fatal/OOM, emission or native compiler
rebuild. No competing tests/builds ran during measurement.

`/tmp/nullable-handle-representation-03` passes O0/O2/ASan+UBSan and verifies the
application token stream contains no templates. Registry-formed nullable<Node>
retains distinct semantic/exact-reference/nested/concrete identities while using the
same native handle storage and pointer payload as Node. Signature comparisons detect
parameter/return changes; a renamed nullable definition uses the same role query.
Scalar optional storage remains wrapped. Generated concrete handles retain owner
identity and absence through copies and provider vector round trips. The test uses
the compiler-generated empty Node payload header and separate native provider code.
Clearing a required handle in this C++ storage proof tests representation only; it
is not source permission to assign null to a required object.

The initial representation proof passed. A later signature test used constructor
arguments on a constructor-less model record; the corrected fixture assigns typed
fields explicitly. No production fix was needed for that test setup error.
`/tmp/nullable-handle-runtime-regression-01` passes four preparation suites and all
seven native handle programs (identity, Result, containers, members, absent receiver,
constants and properties).

This is internal representation support, not source NULL-01 completion. Source
normalization remains until value conversions, references, receivers/overrides and
initialization can preserve/enforce requiredness coherently. End-to-end retained
source edits and PHP/legacy required-boundary parity remain future consumer proofs.
