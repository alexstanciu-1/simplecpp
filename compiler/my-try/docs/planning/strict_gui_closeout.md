# S02 strict-scope reconciliation and V-R08 enabled acceptance
Doc Status: planning

## Agreed goal — 2026-10-22

Keep legacy comment declarations deferred. Replay all six strict-declaration S02
companions with original oracles and default prelowering. Complete V-R08 enabled
Linux UI/WebView acceptance using SDK provisioning, Xvfb and local fixtures.
Harnesses, fixtures, docs and provisioning only; bring compiler/runtime defects
back for discussion. No full matrix. Commit and push each coherent checkpoint.

## S02 — reconciled for the strict milestone

`20261022-s02-prelower-02` on dev002 passes all six companions: two datetime,
two filesystem and two IO cases. The harness checks that the only source changes
are the 25 declaration-comment replacements. All original source and metadata
bytes match pinned baseline `75de94c71e37060c564b6ade19850b7686bd457a`.
All companions already had v0.1 build/run proof; this run refreshes v0.2 evidence.

Publication uses the production compiler API with explicit datetime/filesystem/io
metadata selection. Every application manifest forbids templates. Cold/warm builds
match original exit/stdout/stderr oracles; warm builds compile zero units and do not
link. Each case runs in a private directory and leaves no filesystem resources.
See [the compact results](s02_closeout_results.json), including hashes and provenance.

S02 is a **strict-scope reconciliation**, not implementation of legacy syntax or
unchanged-source compatibility. Legacy spelling stays in the deferred compatibility
queue. Frozen original failures and older migration checkpoints are not rewritten.
The original ledger now has 63/64 dispositions resolved for the strict milestone:
58 implementation repairs, five contract/environment/scope reconciliations, and
partial V-R08. This count does not hide supplementary findings below.

### S02-H01 — project configuration cannot select IO

The first run, `20261022-s02-prelower-01`, rejected all publications before source
preparation because the harness selected `io` in prism.json. The project resolver's
allowed module list in `bin/project_services.php` excludes io; the my-try project
adapter loads the selected metadata modules. Direct compiler API selection supports
IO, as proved by the prior S13 work and the second run here. This is a project-entry
integration gap, separate from legacy declaration syntax. No implementation change
was made, and API publication does not prove project selection works.

Bring a bounded project/metadata selection repair back for discussion; do not add a
second IO metadata path or silently broaden this verification goal.

## V-R08 — acceptance still pending

Dev002 has Xvfb and dbus-run-session, but GTK/WebKitGTK pkg-config inputs are absent.
SDK provisioning is authorized; sudo requires an administrator's interactive action.
WSL was also checked at the user's request: display/Xvfb are available, SDKs are
absent and sudo also needs a password. The user chose provisioning dev002 instead.

Next: native enabled-provider smoke, then default-prelowered source creation,
load/navigation/failure, message/reply acknowledgement, handle snapshots/lifetime,
shutdown and retained/cache recovery. Keep disabled-provider controls, VR08-H01's
general error-condition transport and other platforms explicit. Disabled-provider
success cannot close enabled GUI acceptance. No enabled GUI result is claimed yet.
