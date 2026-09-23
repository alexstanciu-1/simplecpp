# Streaming checked expression evaluation
Doc Status: supporting

`check_bodies/Expression_Order::steps(body, root, start, limit)` now returns an
explicit iterator. Call `next()` until it returns null. Each returned cursor
exposes the completed value ID and call ID; its traversal fields are private-use
state, not a consumer mutation API. Lifetime actions and lowering remain consumers.

The original generator algorithm is retained: left-to-right operands precede their
parent, projected field entries add no evaluated operand, nested calls complete in
strict call order, and a void root is the last call in its segment. Earlier value
IDs bound dependencies; enclosing call IDs bound nested calls. Repeated/cyclic/
out-of-segment calls, missing or mistyped results, invalid argument ranges and
incomplete call segments fail explicitly. Pure shared operands can occur more
than once in the traversal, as in the prototype; calls cannot repeat.

Initialization remains lazy until the first next call. Complete segment validation
happens only when traversal is drained, just as when exhausting the old generator.
Discard the iterator after an exception. Terminal next calls remain null. The
new boundary also rejects negative/reversed/out-of-body call ranges explicitly.

The pending vector is an indexed stack with a logical depth. Slots are reused as
traversal unwinds, avoiding unsupported vector deletion and recursion. Retained
scratch capacity is proportional to maximum expression depth, not event count;
it is released with the iterator. No completed-event list or full graph copy is
owned by the iterator. Completed cursor objects are not subsequently mutated.

The tests cover 24 independent traces/rejections and a 4,096-level conversion
chain. The 24 traces also run through the original generator using its actual row
and Checked_Body classes; the host-only oracle initializes just the fields read
by those queries via reflection, so it does not claim a completed source compiler
pipeline. Synthetic canonical dependencies isolate evaluation order from parsing
and body-checking functionality. All three original consumers—lifetime values,
allocation flow and lowering expressions—remain to adopt the explicit next loop.
