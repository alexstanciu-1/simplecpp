# Typed construction facts — 2026-10-25
Doc Status: planning

Approved source representation reuses the static-member fact protocol. The syntax
node owns nullable record/empty-only/sequence slots, with exactly one present after
successful publication. Generic queries upcast the same object; typed queries reject
missing/wrong alternatives. Setters and cleanup clear previous alternatives. No tag,
duplicate generic fact, worker retention or new virtual method is introduced.

Data_Construction_Preparation publishes fully built facts at each concrete producer;
Expression_Preparation keeps its existing shared epilogue. CPP_Data_Construction and
CPP_Default_Constructors consume typed accessors, without changing selection/order.
The latter retains its required-preparation guard before querying alternatives.

The extracted production representation passes PHP, v0.1 and both v0.2 native modes
for identity, generic epilogue mutation, replacement, checked wrong-alternative reads,
cleanup and reattachment of a retained handle. Integrated native record/sequence/
empty-only fixtures pass both v0.2 modes under execution and outlined-default policies:
source entry order 1234, record positions 2/1, shared item identity and independent
Storage defaults. Outlined Defaults construction is present in published C++.

Host data_construction, data_preparation, property_defaults and incremental_preparation
pass. data_records fails at its historical recursive required-field example (missing
input b); restored parent-revision production files reproduce the same diagnostic at
the same fixture. This is not introduced by typed storage; no unrelated semantic repair.

Native compiler and second-generation acceptance remain outstanding.

Measured bootstrap: 1:42.11 / 761252 KiB RSS / 722 MiB PHP heap. Semantic
preparation completes at 87.40 s and project validation at 87.92 s. Both approved
blockers clear. Emission then rejects: Concrete layout dependencies contain a
generated header cycle. No generated layout/header change made in this slice.
The JSON report's fatal field contains the last PHP warning (missing output-status
file lstat during publication setup); the caught blocking exception is the layout
cycle, as recorded in failure and stderr. No successful C++ bundle/native compiler.
