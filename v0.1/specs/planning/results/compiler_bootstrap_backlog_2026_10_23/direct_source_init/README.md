# Direct source initialization checkpoint
Doc Status: supporting

Reuse: accepted PHP constructor promotion already emits v0.2 set: inputs. Adapted
source_record's owned file and 33 AST cursor constructors to that existing protocol.
Cursor parameter names are preserved. source_record's snapshot parameter becomes file
so promotion initializes the existing public field; its sole repository call is
positional. No explicit parent-constructor ordering is changed. Nullable staged
fields, Storage_Cursor construction and converter-owned INIT-02 remain separate work.

/tmp/direct-source-init-02 passes AST behavior, AST invariants and lifecycle handoff.
/tmp/direct-source-init-portable-05 extracts production source_record, child_iterator_i,
children_iterator and pair_children_iterator, with small dependency owners. PHP,
legacy native, ordinary and prelowered v0.2 preserve file identity by mutation,
source-order traversal and independent cursors. Native times: legacy 21.47 s,
ordinary 23.33 s, prelowered 20.59 s. This is a bounded production-component proof,
not execution of every cursor or a native compiler rebuild.

Fixture setup corrections: removed a stale copied harness task; assembled the required
exception framework; scoped v0.2 fixture dependencies to the two exception classes it
uses. The full exception framework still fails on scpp_portability_same_exception's
object-identity comparison. The saved owner probe identifies that independent consumer
blocker. It was not a cursor failure and is not resolved by this source slice. Initial
full framework loading also included unrelated string/collection functions. The fixture
does not claim full framework compatibility. Legacy builds use the assembled framework.

Bootstrap /tmp/compiler-bootstrap-direct-source-init-01: 78.39 s wall time /
340,820 KiB RSS (332.8 MiB) / 310 MiB PHP heap. Preparation still fails first on a
file field in another owner. The first-field diagnostic disappears; no diagnostic
text is added. Repeated file/source diagnostics are aggregated across owners, so
this does not measure how many declarations were repaired. No fatal/OOM, emission or
native compiler rebuild; required-field source migration and other backlog work remain.
