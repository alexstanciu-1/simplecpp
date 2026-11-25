# Private instance-field checkpoint
Doc Status: planning

Starting revision 5d01e2dc2 plus bounded private instance-field redeclaration admission.
Full snapshot, hashes and tracked diff: `/tmp/compiler-bootstrap-private-fields-01`.
Reproduce with `python3 compiler/my-try/tools/bootstrap_probe.py --results FRESH`.

Conversion: 4.64 s / 195,012 KiB RSS. Input: 269 PHS files, 1,957,870 bytes.
Bootstrap: 210.63 s / 464,404 KiB RSS (453.5 MiB), 442.5 MiB PHP heap.
Initialization: 0.307 s; sync/parse: 4.087 s. The inherited position-field
redeclaration diagnostic disappears; none are added. AST-01 remains first.
No fatal/OOM, emission or native compiler rebuild. No competing builds/tests ran
during measurement. This measures progress to a preparation blocker.

`/tmp/private-fields-portable-01` passes PHP, legacy and both v0.2 modes for independent
base/child values, mutations, a three-level chain and an explicit portable base view.
`/tmp/private-fields-native-01` passes typed struct base/child constructor set inputs
and nine negative/recovery cases in both modes. `/tmp/private-fields-retained-03`
adds ancestor-only visibility edits across files: unchanged descendants reject a
public ancestor collision and recover the original output when private is restored.
The initial cross-file fixture omitted explicit entry selection; the final fixture
selects main.phs, as multi-file compilation requires.
`/tmp/private-fields-inheritance-01` passes all 59 existing inheritance programs.
No field renaming, lookup fallback, numeric policy or new field storage model was added.
