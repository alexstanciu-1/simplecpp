# TASK-02 retained lookup synchronization
Doc Status: planning

Source fix: Symbol_Collector snapshots class/enum association lookups under the same
batch lock as source registration. Local reconciliation runs after unlock. Standalone
lookup remains direct. source_type_snapshot is a transient by-value-captured result
object, not retained state; this avoids unsupported converter reference captures.

Actual PHP parser tests preserve class/enum identities during independent new type
registrations and parse failure/retry; standalone parsing succeeds without a task
context. Existing parse_collection, collected_roles and collected_query_dispatch pass.
The first collected_query_dispatch invocation omitted its output argument; corrected
setup passes. No production change for that fixture setup issue.

Native evidence extracts the actual helper and snapshot declaration. Test scope uses
a shared native hash with retained record/enum entries and concurrent insertion.
A fixture-only barrier inserted at the generated work method entry requires all four
callbacks to be resident before loops begin. Source output proves 4000 iterations,
2002 table entries and retained identity. Callback failure followed by successful
lookup proves synchronized exception/context cleanup. /tmp/retained-lookup-tsan-01
passes uninstrumented and ThreadSanitizer application builds. The task runtime library
is not itself instrumented; generated lookup/hash/read/write/callback code is.
/tmp/retained-lookup-tsan-negative-01 removes only the reader synchronization in that
generated fixture and exits 66 with a hash read/write race. The final harness includes
this negative control under --sanitize. This is not a whole-compiler native build.

Both v0.2 modes currently reject shared callback captures (routes_summary.json).
TASK-02 is partially complete and must rerun after TASK-01 admission is implemented.
Do not treat reproducing that diagnostic as successful native execution.

Initial -03/-04 native fixtures are NOT concurrency evidence: legacy emission dropped
the for loop inside the closure. Moving work into a typed method and adding registry
size/work assertions corrected the proof. Converted PHS retained the loop, identifying
a legacy generator defect rather than a converter failure. It is separately backlogged.
Earlier fixture conversion rejected a C++ keyword local and unsupported string cast;
corrected fixture uses descriptive names and integer registry keys.

Bootstrap: 73.71 s wall / 341916 KiB RSS (333.9 MiB) / 312 MiB PHP allocation.
First blocker remains required file-field initialization; no aggregate diagnostics
added/removed. No fatal/OOM, emission or native compiler rebuild. Early-stop measurements
are not the full compilation cost. Source snapshot hashes accompany this checkpoint.
