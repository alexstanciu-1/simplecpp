# Nullable ownership formation checkpoint
Doc Status: planning

Starting revision c9a44a645 plus registry-driven rejection of redundant nullable
ownership wrappers. Full snapshot, hashes and tracked diff:
`/tmp/compiler-bootstrap-nullable-owner-01`. Reproduce with
`python3 compiler/my-try/tools/bootstrap_probe.py --results FRESH`.

Conversion: 4.99 s / 195,052 KiB RSS; 269 PHS files / 1,961,038 bytes.
Bootstrap: 199.10 s / 470,052 KiB RSS (459.0 MiB), 451.0 MiB PHP heap.
Initialization: 0.301 s; sync/parse: 4.048 s. Diagnostic text set unchanged;
nullable-value boundary remains first. No fatal/OOM, emission or native compiler
rebuild. No competing tests/builds ran during measurement.

`/tmp/nullable-owner-core-02` passes renamed-role registry rejection without
publishing invalid type identities, 30 source-admission cases with repeated
rejection/recovery in each output mode, and native empty shared/moved-from unique/
weak-lock retention controls in both modes. `/tmp/nullable-owner-regression-01`
passes all 18 existing generated programs after adapting explicit ownership examples
to the agreed single spelling. Class normalization controls remain historical until
NULL-01's required/nullable identity and consumer integration is implemented.

The first native control used isset on explicit handles; the existing prelowered
value-probe path rejects that operation. The final control uses admitted boolean
conditions. This is recorded as a NULL/OWN consumer gap, not silently treated as
isset support or repaired in the formation slice. Required class contracts, broader
nullable payload capabilities and portable cross-route migration are still pending.
