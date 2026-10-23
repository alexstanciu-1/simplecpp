# Constructor promotion bootstrap checkpoint
Doc Status: planning

Starting revision d630e2d0e plus the converter's v0.2 set mapping and switch_label
PHP promotion. Run `python3 compiler/my-try/tools/bootstrap_probe.py --results FRESH`.
Full snapshot/hashes and tracked overlay: `/tmp/compiler-bootstrap-promotion-01`.

Conversion: 4.66 s / 194,972 KiB RSS (190.4 MiB).
Input: 269 PHS files / 1,956,338 bytes.
PHP-hosted bootstrap: 190.83 s / 440,544 KiB RSS (430.2 MiB), 420.5 MiB PHP heap.
Initialization completed at 0.297 s and sync/parse at 4.031 s. Preparation still
fails first on AST-01 nonvirtual inherited redeclaration. No fatal/OOM or emission.
Seven property-requires-set diagnostic texts disappear; none are added. This is
aggregate text evidence, not proof that every affected owner or bootstrap succeeds.
No concurrent build/test workload ran during measurement; no native compiler rebuild.

`/tmp/constructor-promotion-06` proves root-class enum/default/private promotion,
ordinary parameters, argument/body ordering and failure effects in PHP/current legacy
native and both v0.2 C++ modes. Actual switch_label construction is checked in PHP.
`/tmp/promotion-empty-defaults-01` passes existing cross-route container-default checks.

Promotion plus explicit parent construction remains open: the legacy expansion
hides the first base call behind assignments. `/tmp/constructor-promotion-02` records
that native failure; the user decision on source adaptation versus a no-child-
observation convention is pending. No parent-call reorder is implemented.
The exception facade's unrelated shared-object identity helper also remains blocked;
checked arithmetic supplies the bounded constructor failure proof instead.
