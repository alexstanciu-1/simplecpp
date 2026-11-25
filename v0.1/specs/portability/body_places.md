# Checked storage locations
Doc Status: supporting

`check_bodies/Place` owns a fixed root-to-leaf projection path for one positive
local storage ID. `Place_Projection` distinguishes field ordinals (zero allowed),
fixed-array index values and dynamic-storage element values (positive operand
IDs required). Type IDs are positive and the call boundary is nonnegative.
Checked integer tags replace the original enum; invalid tags are rejected at the
constructor boundary. These records do not grant access/lifetime permission.

The constructor copies vector membership and keeps exact immutable projection
objects. `size()`/`at()` replace public array access; append/replacement in the
caller's input vector cannot alter the published path. `allocation_backed()` is
true exactly when a dynamic element projection occurs, not merely for an index.
`call_end` retains the assignment target's evaluation boundary for later consumers.

The prototype's `indices()` generator becomes `next_index(start)`, which returns
a projection position or -1. Consumers begin at zero, fetch the returned projection
with `at(position)`, then resume at `position + 1`. This visits index/element
projections in original order without a second retained operand list, a closure,
or generator machinery. Position and expression-value ID remain distinct.
Invalid cursor/index inputs throw; a cursor at the end returns -1.

Constructor callers pass an explicit empty typed vector for a bare local. This
avoids an unsupported container default while retaining the same representation.
Remaining place syntax cursors, expression checking, lifetime consumers and lowering
must adopt these accessors when migrated; they are not included in this slice.
Projection objects are candidates for a later compact value-layout optimization.

`compiler/tests/body_places` exhausts projection-kind sequences up to length three
and tests invalid operands/types/call boundaries/local roots: 47 PHP/native cases
with retained-prototype agreement, plus per-case identity, membership isolation and
access-bound checks. No native allocation-size or destructor claim follows from
these semantic proofs.
