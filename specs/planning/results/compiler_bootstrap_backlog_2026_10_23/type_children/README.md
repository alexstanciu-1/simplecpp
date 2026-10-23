# Type-declaration child traversal completion
Doc Status: supporting

Parent bb7c196f9 plus source-owned loop spelling change. Production method
 type_declaration_children_iterator::read_next uses an unconditional loop, returns
from its child branch or exhausted switch default, and never falls through.
Completion_Preparation::loop conservatively allows tested-loop exit; Loop_Preparation
already recognizes conditionless for. No new constant analysis is needed: PHP source
now spells the same unconditional loop as for (;;).

The fixture extracts actual protocol/type-declaration/storage child iterator classes,
with minimal owner records. Before: PHP/legacy pass; both v0.2 modes reproduce the
read_next completion error. After: all routes pass base/collection order and empty
traversal (legacy 21.86 s, ordinary 24.50 s, concrete 23.72 s). Existing cursor lifetime
and heterogeneous widening proofs remain separate. No compiler semantic change.

Bootstrap 76.22 s wall / 343628 KiB peak RSS / 314 MiB PHP heap. The read_next
completion diagnostic disappears and no new diagnostic appears. First syntax_node
initialization blocker and other existing blockers remain. No full bootstrap success.
