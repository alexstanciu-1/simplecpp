# Present empty lock token factory
Doc Status: planning

User approved implementation, with the keep/remove decision reserved for the end of
the goal. Reuses file_lock private default construction and shared allocation; try
and transfer now use that same factory. No class-nullability or out-parameter exception.
Ordinary filesystem metadata and legacy symbol/contract registration expose the factory.
The PHP carrier already constructs a real object; the native portable facade now agrees.

/tmp/lock-empty-output-01 passes the complete 11-leaf output suite on legacy and both
v0.2 modes, with its lock slot explicitly initialized. /tmp/lock-empty-portable-02
passes PHP and legacy/ordinary/concrete native facade lifecycle tests: fresh identity,
release/release, failed try preservation, acquisition, contention, transfer, released
aliases, shared readers and released-transfer failure. Initial fixture assembly omitted
--os, corrected without production changes. Legacy builds use --no-stan.

/tmp/lock-empty-runtime-01 builds current core/runtime.cpp and runs the existing native
process suite extended for factory presence, failure preservation and old empty aliases.
Native/PHP contention, inheritance, close-on-exec, cleanup and leak controls pass.
/tmp/lock-empty-catalog-02 passes metadata catalog assertions after generation. The first
catalog attempt read the stale generated source; generation now precedes catalog reads.
Regeneration also restores already-registered JSON reader declarations in that artifact.
This does not resume legacy static analysis or prove a native compiler rebuild.

Bootstrap: 74.35 s wall time / 341280 KiB RSS (333.3 MiB) / 312 MiB PHP peak.
First blocker remains required file-field initialization. The unassigned out diagnostic
is removed; none added. No fatal/OOM or emission. These are early-stop measurements.
Reservation direct field initialization remains a separate source/converter obligation.
