# Braced while and do-while in v0.2

Doc Status: normative
Date: 2026-11-04

## Syntax and execution

All three Simple C++ flavors admit:

```text
while (condition) { statements }
do { statements } while (condition);
```

Bodies require braces. The do-while trailing semicolon is required. Loops may nest
and may contain supported conditional statements. `do` and `while` are reserved
keywords in every flavor. Break/continue, numbered transfers, for/foreach, unbraced
bodies, empty-semicolon bodies and alternative syntax remain deferred in this slice.

A while condition executes once before each attempted iteration, including the final
false test. Its body may execute zero times. A do-while executes its body first and
tests once after each normally completed iteration; it therefore executes its body
at least once. Returning or failing in the body does not execute the trailing test.
Returning or failing during a test does not start the body. Expression evaluation
and argument conversion order remain unchanged.

Both forms reuse [condition truthiness](conditional_statements_v0_2.md#php-first-compiler-delivery--2026-11-04):
bool identity, integer/float nonzero (negative zero is false), strings false only for
empty or exactly `"0"`. Direct null, distinct byte and void conditions remain unsupported.
Truthiness does not authorize general conversion to bool.

## Lexical scope and completion

Each body is a child lexical scope, with the same declarations, shadowing and
source-order visibility rules as conditional blocks. Body-local declarations are
not visible outside the body or in either form's condition. In particular, a do-while
condition resolves outer storage, not a same-named body-local shadow. Locals are
created again on each body entry. Existing outer local/reference/global accesses
retain their normal storage semantics.

Every condition and body is checked, even when constant syntax or a guaranteed body
return means it cannot execute. There is no constant reachability/termination proof.
While does not establish guaranteed return because it may execute zero times.
Do-while establishes guaranteed return only when its body guarantees return on every
path. Ordinary function and executable-entry return requirements otherwise remain.

## Preparation boundary

Prepare each body and condition once. Discard incoming value facts before repeating
code, between the prepared test and body, and on loop exit; do-while's body scope is
left before preparing its test. Keep types, declaration bindings and already-prepared
occurrence snapshots. This conservative policy prevents the first iteration's values
from incorrectly proving conversions safe on subsequent iterations. No fixed point,
alias graph, loop unrolling or repeated preparation pass is required.
