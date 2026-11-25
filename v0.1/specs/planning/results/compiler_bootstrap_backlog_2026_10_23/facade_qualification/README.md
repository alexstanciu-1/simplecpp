# Mapped facade qualification checkpoint
Doc Status: planning

Starting revision a648ffb61 plus rooted global facade map targets and legacy
source-root call emission. Full source snapshot, hashes and tracked diff:
`/tmp/compiler-bootstrap-facade-root-01`. Reproduce with
`python3 compiler/my-try/tools/bootstrap_probe.py --results FRESH`.

Conversion: 4.60 s / 195,040 KiB RSS. Input: 269 PHS files, 1,956,870 bytes.
Bootstrap: 214.69 s / 464,688 KiB RSS (453.8 MiB), 442.5 MiB PHP heap.
Initialization: 0.305 s; sync/parse: 4.080 s. Fourteen unresolved global facade
function diagnostic texts disappear, with none added. AST-01 remains first.
No fatal/OOM, emission or native compiler rebuild. No concurrent builds/tests ran
while measuring. More calls now resolve, so this is not a same-workload performance
regression comparison or a completed self-bootstrap measurement.

`/tmp/facade-qualification-08` passes PHP, legacy and both v0.2 modes using a
namespaced caller, a same-name namespace shadow and three small global helpers
that call real string operations. Both targets invalidate prior-map outputs and
reuse warm output. This fixture isolates lookup; it does not prove all facade bodies.
`/tmp/facade-string-regression-01` passes the full existing portable string-splitting
suite, including real byte-construction facade calls, binary/UTF-8 cases, cache reuse
and wrong-type/arity rejection. Exact scope is in fixture_summary.json.

Remaining body constraints such as exception identity and nullable Result extraction
stay with OWN/NULL; absence of lookup errors does not close their implementation.
