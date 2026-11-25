# Synchronous yield producers

Doc Status: normative

## Bounded v0.2 contract

A free function declaring `Iterator<Value>` or `Iterator<Value,Key>` returns a
function-specific, move-only concrete cursor. The return annotation declares the
producer's outputs; it is not interface-erased storage. The signature alone marks
the producer, including an empty body. PHP/C++/JS share `yield value;` and
`yield key => value;`, with each flavor's normal variable/type syntax.

Calling the producer evaluates/binds ordinary by-value arguments immediately and
captures them using their normal value semantics. Body execution is lazy: the first
advance executes until yield, bare return, implicit completion or exception.
A subsequent advance resumes after the previous yield. No source function silently
yields into its caller. Calling another producer without traversing it creates and
destroys an unstarted cursor.

Outputs use existing generated typed-destination conversions. Keyed producers must
supply explicit keys at every yield; value-only producers must not supply keys.
No automatic keys, key normalization, uniqueness or ordering contract is introduced.
The underlying next contract is value-first, matching Iterator<Value,Key>.

Foreach borrows the cursor and evaluates its receiver once. Breaking leaves it
suspended; another foreach resumes it. Value-only foreach can discard a keyed
producer's key. Each successful advance provides ordinary owned output values.
Completion stays completed. Unhandled exceptions close the cursor and propagate;
a later advance returns false. Failed conversion does not publish the next output.

Captured parameters are released on completion, exception or cursor destruction,
including abandonment before the first advance. A final yield still suspends: cleanup
at normal completion occurs when the consumer advances again and observes completion.
Generated native cursors are move-constructible, not copyable; moved-from cursors are
closed. The initial source slice permits inferred local initialization from a producer
call and direct foreach over a producer result. Explicit transfer of named cursors,
assignment/replacement, cursor parameters and forwarding returns are deferred.
User-authored Iterator structs remain copyable and retain their existing semantics.

## Current implementation boundary

Producer bodies admit local declarations, local storage writes (including existing
compounds/updates), braced `if/elseif/else`, `while`, `do-while`, `for`, `foreach`, yield, echo, ordinary call statements and
bare return. Parameters are by value; existing literal defaults use the ordinary binder.
Nested conditionals use ordinary generated truthiness. Conditions execute when reached;
resuming after a yield does not repeat a condition or reselect its arm.

A local is constructed when its declaration executes, remains alive across suspension,
and is destroyed at lexical scope exit. Completion, an unhandled exception or abandonment
destroys all still-active locals in reverse construction order, then captured parameters.
Unselected arms and unreached declarations do not construct values. Bare return closes
the cursor even inside a conditional arm.

While/do-while/for use their ordinary conditions, updates and lexical scopes.
Suspension resumes inside the current iteration, without repeating initialization or
its already-evaluated condition. Body locals are destroyed at iteration end and
constructed anew when their declarations execute again. Break/continue clean scopes
they exit; for-continue then executes the update. For-header locals survive iterations
and are destroyed when the loop exits. Return and exceptions close the entire cursor.

Foreach inside producers supports vector, hash, mixed, dynamic and custom Iterator
structs, including keyed traversal. Receiver selection and traversal initialization
execute once; receiver lifetime, traversal state and outputs survive each yield.
Named producer locals and captured parameters are borrowed from cursor-owned storage.
Temporary receivers are owned until loop exit. Shared receivers and shared-field
paths retain the selected shared owner. Replacing the original owner does not destroy
the retained receiver. Borrowed inline field paths are re-addressed from their frame;
no reference into a moved-from cursor is retained.

The initial receiver subset consists of these local/parameter, call or construction
roots and field paths. Collection-element/interior roots and global roots are rejected.
Mutation retains the ordinary bounded traversal contract, without snapshots or stable
visitation guarantees. Concurrent mutation remains deferred. Loop exit, return,
exception and abandonment release active traversal resources; continue retains them.
Nested foreach over supported collections/custom iterators is allowed. Traversing
another generated cursor inside a producer, nested cursor locals and
exception-handling regions remain deferred.
Methods, references, yield expressions receiving sent values, yield from, asynchronous
waiting and optimizing suspended-local liveness are separate slices.

Inline-struct outputs remain deferred alongside inline-struct reference parameters.
They need output-storage lifetime handling that does not add an observable default
construction before the first yield. This does not prohibit capturing structs by
value or yielding supported shared handles. Source diagnostics enforce the boundary
before C++ emission.

## Implementation direction

Generate concrete C++ cursor layouts, factories and next bodies, without C++ coroutine
or cursor template machinery. Reuse the metadata-defined Iterator identity and
existing type conversions. Output/capture types belong to the function's signature; source-owned lowering
adds concrete slots for prepared locals and publishes their layout after the join;
resume-state numbering and statement segmentation belong to private lowering.
See [implementation and verification](../compiler/docs/yield.md).
