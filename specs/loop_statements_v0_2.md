# Braced loops in v0.2

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
keywords in every flavor. Numbered/labelled transfers, foreach, unbraced
bodies, empty-semicolon bodies and alternative syntax remain deferred in this slice.

Bare `break;` exits the nearest enclosing loop. Bare `continue;` skips the rest of
that loop's body and reaches its next condition evaluation. In a do-while this is
the trailing condition, not an unconditional jump to the body. Break skips that
condition. Both transfers may occur inside nested conditional arms. They require
an enclosing loop within the same function; even unreachable outside-loop uses
diagnose. This slice introduces no switch, labelled or numbered transfer semantics.

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
Break and continue do not count as returns. Nested loops consume their own transfers;
a possible break/continue path in a do-while prevents it from proving guaranteed
return. The compiler continues rejecting statements after unconditional return,
and also rejects statements after unconditional break/continue or an exhaustive
conditional whose every arm transfers control. No constant-condition pruning is added.

## Preparation boundary

Prepare each body and condition once. Discard incoming value facts before repeating
code, between the prepared test and body, and on loop exit; do-while's body scope is
left before preparing its test. Keep types, declaration bindings and already-prepared
occurrence snapshots. This conservative policy prevents the first iteration's values
from incorrectly proving conversions safe on subsequent iterations. No fixed point,
alias graph, loop unrolling or repeated preparation pass is required.

## Bounded for slice — 2026-11-04

All flavors admit `for (initializer; condition; update) { statements }` with exactly
one required declaration, assignment or discarded-value update initializer, one required condition expression,
and one required assignment or discarded-value update clause. The initializer uses ordinary flavor syntax:

```text
cpp: for (int i = 0; i < 10; i = i + 1) { echo i; }
php: for ($i int = 0; $i < 10; $i = $i + 1) { echo $i; }
js:  for (let i: int = 0; i < 10; i = i + 1) { echo i; }
```

Existing inferred declarations (`auto`, PHP first write, JS `let`) and writes to
existing locals are permitted in the initializer. An explicit declaration shadows
outer storage. A bare PHP initializer write selects existing visible storage first;
otherwise it declares a loop-local binding. Updates require existing local storage
and cannot introduce declarations. All clauses use existing type/conversion rules;
the loop is not restricted to integer storage. Conditions use generated truthiness.

Initialization runs once. The condition runs before every attempted iteration. On
normal body completion or `continue`, the update runs once before the next test.
Break, return or failure skip the update. A false first condition skips both body
and update. Each reached clause retains its existing expression evaluation contract.

The header owns a lexical scope visible to the condition, update and body. A braced
body owns a child scope and may shadow header locals; its locals are not visible in
the header or after the loop. Header declarations do not escape the loop. Writes
to pre-existing outer locals remain visible after it.

Prepare initializer, condition, body and update once, in that order. Clear value
knowledge before repeating regions and on exit, retaining types and bindings.
Check the update even when a constant condition or body transfer prevents its
execution. Completion conservatively permits zero iterations and does not prove
a value function returns merely because the for body returns.

Omitted clauses, comma-separated clauses,
standalone call clauses and unbraced bodies remain deferred. Calls inside otherwise
supported expressions are allowed. This slice adds no assignment expressions or
general expression statements.


## Compound for headers — 2026-11-05

Initializer and update assignments may use the same `+=`, `-=`, `*=`, `/=`, `%=`
int64 mechanism as standalone compound statements. For example:

```text
cpp: for (int i = 0; i < 10; i += 1) { echo i; }
php: for ($i int = 0; $i < 10; $i += 1) { echo $i; }
js:  for (let i: int = 0; i < 10; i += 1) { echo i; }
```

A compound initializer requires existing visible storage; it never declares a
loop-local binding, including in PHP. It executes once before the first condition.
Both clauses preserve the destination's old value before evaluating their RHS
once and commit only after successful computation. Earlier RHS effects remain
on failure. The ordinary compound type, overflow and error contracts are unchanged.

A compound update runs after each normal body completion or nearest-loop continue,
including when its RHS mutates the same destination through a reference parameter.
Break, return, body failure and a false initial condition suppress the update.
Nested loops retain their own transfer targets and header scopes. Body-local
shadowing cannot change the header's resolved destination.

No source-level comma clauses, omitted clauses, assignment-expression
values or new types are introduced. Preparation still visits each clause once;
this is a reuse of assignment semantics, not a separate preparation operation.


Prefix/postfix int64 increment/decrement is also admitted in both header positions
under the [discarded-value update contract](compiler_scalars_v0_2.md#discarded-value-int64-updates--2026-11-05).
It requires existing storage and shares ordinary loop sequencing; neither position
exposes an expression result. Increment/decrement in the condition remains deferred.
