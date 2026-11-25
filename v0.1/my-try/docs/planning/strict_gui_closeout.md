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
At the S02 checkpoint the original ledger reached 63/64 strict-milestone dispositions:
58 implementation repairs, five contract/environment/scope reconciliations, and
then-partial V-R08. The final count below includes enabled acceptance; supplementary
findings remain explicit.

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

## V-R08 — enabled Linux acceptance complete

The user provisioned dev002 with GTK 3.24.41 and WebKitGTK 2.52.6. Enabled native
provider execution passes under isolated Xvfb/D-Bus. No WebKit sandbox disabling
was needed. Local app/file/HTML fixtures avoid external websites. WSL was checked
but was not used for builds or execution.

| Gate | Result / evidence directory under `/home/alex-ai/curl/` |
| --- | --- |
| Native provider | `20261022-vr08-enabled-native-01`: existing desktop smoke builds and receives real ready/navigation/message events; runtime reply succeeds. |
| Source execution | `20261022-vr08-enabled-source-02`: four programs pass cold/warm with default prelowering, enabled provider definitions and forbidden application templates. |
| Integration controls | `20261022-vr08-enabled-final-01`: four source programs pass again; the existing four disabled-provider controls and both contract suites pass with GUI SDK discovery deliberately masked; provider source/definition/library cache regressions pass. |
| Retained acceptance | `20261022-vr08-retained-final`: edited/reverted/fresh publications all build/run cold/warm. Invalid source clears output readiness; recovery and fresh artifacts agree. |
| SDK selection | `20261022-vr08-sdk-selection-02`: one project/publication/cache switches enabled → disabled → restored, reports on/off/on from actual app creation, then reuses each warm build without recompilation or linking. |

The enabled source cases cover HTML, local app-folder and file-URL loading; ready,
navigation-start/finish and load-failure events; message ID/command/payload extraction;
success/error replies with JavaScript acknowledgement; and evaluation acknowledged
back through the bridge. Event getters and all 24 API names remain exercised.
The HTML case clears the caller's view handle during the later HTML argument but
successfully loads through its earlier snapshot. Copies survive clearing original
app/window handles. Missing-app/closed-view failures preserve output destinations.
Closing views/windows, repeated cleanup, finally cleanup and app exit complete;
polling after exit returns false.

Warm builds compile zero units without linking. Retained source edits use one
compiler; fresh project metadata initialization uses a separate process, preserving
immutable startup metadata. The initial retained fixture attempted reconfiguration
in the same process and was corrected without compiler changes. The initial SDK
selection fixture omitted the structured Result error output; its corrected three-
argument take passes. Earlier source-output failures came from D-Bus session chatter;
application stdout/stderr are now separate files, with session logs retained.

All 17 focused input hashes match local/dev002. The [results](strict_gui_closeout_results.json)
and [archive](strict_gui_evidence/README.md) retain passing evidence and intermediate
failures. No compiler/runtime implementation fix, full matrix, native compiler
rebuild, pixel-comparison suite or non-Linux platform sweep was performed.

### VR08-H02 — concrete runtime-handle null comparison

`20261022-vr08-enabled-source-01/html` prepares successfully, then C++ compilation
fails for this valid source condition:

```php
if ($view !== null) { return 20; }
```

The emitted condition calls `local_view.has_value()` even though default-prelowered
concrete webview storage has no such method. This is a compiler lowering defect,
separate from the 24 provider APIs. Keep it open for discussion of the existing
handle/null-comparison and concrete operation owners; no repair is authorized here.
The snapshot acceptance instead uses `webview_load_html` on the cleared handle and
checks the error result, proving absence without claiming null-comparison support.

The previously recorded VR08-H01 general error-condition transport gap was not
reverified here; the subsequent [error-condition goal](error_conditions.md) repairs it. Other platforms, broader UI features, and
legacy comment syntax remain outside this closeout.

## Current milestone disposition

**64/64 original strict-milestone dispositions resolved**: 59 implemented/repaired
and five contract/environment/scope reconciliations. S02 is explicitly a scope
exclusion, not legacy syntax implementation. V-R08 closes within the agreed Linux
provider scope. The historical S02-H01 and VR08-H02 findings above are subsequently
[repaired and verified](project_io_handle_presence.md) on dev002 with default
prelowering. The frozen results retain the original failure evidence. VR08-H01 is also [subsequently repaired](error_conditions.md); 100% of this original ledger does not mean a gap-free compiler.
