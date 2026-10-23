# Empty-only provider bootstrap checkpoint
Doc Status: planning

Starting revision 77aca50f8 plus the empty-only provider binding slice. Run
`python3 compiler/my-try/tools/bootstrap_probe.py --results FRESH`.
Full source snapshot, source hashes and tracked overlay are in
`/tmp/compiler-bootstrap-empty-provider-03`.

Conversion: 4.60 seconds, 195,060 KiB peak RSS (190.5 MiB).
Assembled input: 269 PHS files / 1,962,586 bytes.
PHP-hosted bootstrap: 198.90 seconds, 442,724 KiB peak RSS (432.3 MiB),
422.5 MiB peak PHP heap. Initialization completed at 0.300 seconds and sync/parse
at 4.082 seconds. Preparation failed first on AST-01, nonvirtual inherited member
redeclaration. No fatal/OOM, C++ emission or native compiler rebuild.
No diagnostic text was added or removed compared with the empty_expressions checkpoint.
No concurrent build/test workload ran during the measurement. This remains partial-run
performance evidence, not a projection of successful complete bootstrap cost.

`/tmp/empty-provider-03` passes the nine focused factory/admission/recovery tasks;
`/tmp/empty-defaults-regression-03` passes five existing sequence-default tasks.
Both ordinary and template-prelowered generated native applications were executed.
