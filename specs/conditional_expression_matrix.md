# Conditional selection

Doc Status: normative

## v0.2 full ternary selection

All three flavors accept `condition ? when_true : when_false`. The condition uses
the same type-directed truthiness as `if`. It runs once, followed by exactly one
arm. Failure in the condition skips both arms. Failure in the selected arm propagates.
Preparation checks both arms, including when the condition is a literal. An inferred
selection requires the same canonical value type admitted for a match result,
subject to the published handle joins below. In a declared PHP by-value return,
each arm uses the declared return type and its return conversion. For example,
a payload and a structured diagnostic can each return a `result<int>`. Only the
selected conversion runs. A direct extraction test proves a fresh payload is
initialized on its success edge. See [PHP take outputs](carrier_slice.md#php-take-outputs).

In the common v0.2 grammar, `??` binds more tightly than ternary and `xor` binds
less tightly. Nested ternaries associate to the right. PHP also accepts
`left ?: fallback` at the same precedence. It tests the left value once and
uses that value as the true arm. Only a false test evaluates the fallback.
Both arms follow the full ternary's inferred and declared-return result rules.

## PHP nullsafe access

`findPlayer()?->score` evaluates the optional shared receiver once. Absence
returns an empty optional. Presence reads the declared field and returns its
value in the field type’s optional representation. A stored zero or empty
string stays present. An already-nullable field preserves its own absence or
value, and a shared-handle field preserves object identity. Errors from
evaluating the receiver propagate.

Preparation resolves the field and checks visibility even when the receiver
can be absent. The field must have a supported optional representation.

An owned callable field supplies its own empty state. Reading `$router?->op`
returns an empty callable for an absent router and a copy of the field for a
present router. In `($router?->op)(arg())`, the guard ends before invocation.
The argument runs even when the selected callable is empty, then invocation
raises the ordinary empty-call failure. If the argument replaces the field,
the pending call uses the copy selected before argument evaluation.

For `findPlayer()?->scoreFor(key())`, absence skips the arguments and the call.
Presence retains the selected owner through argument evaluation and invocation.
Arguments bind and execute as for an ordinary instance call, including named
arguments in written order and dispatch through the declared receiver type.
A synchronous method returning by value uses the same optional result rule as
a field. Exceptions from selected arguments and the method propagate.

A nullable enum can guard a public instance method call. For example,
`enum_try_from_value(Kind::class, input())?->score(key())` evaluates the
lookup once. A miss skips `key()` and the method. On a hit, even a case
backed by the empty string is present. The method receives the selected case
by value, and its result follows the same optional-result rule.

PHP nullable enums expose two read-only case properties. `$case?->name`
returns the unqualified case spelling as an optional string. `$case?->value`
returns the backing value in its optional representation. Both evaluate the
receiver once and yield absence on a miss. An empty backing string or integer
zero stays present through `??`. The properties use the same bindings as
`enum_name()` and `enum_value()`.

Consecutive `?->` hops compose from left to right. In
`findTeam()?->captain?->scoreFor(key())`, each selected receiver is evaluated
once. If either owner is absent, the result is absent and `key()` is skipped.
A field or a by-value method can supply the shared owner for the next hop.
Preparation checks every member against its receiver type, even on paths that
can short-circuit.

PHP field reads and synchronous by-value instance calls can mix `->` and
`?->` in any order. Each `?->` evaluates its receiver once. An absent
receiver skips every remaining member and argument in the chain and yields
absence. When the guard succeeds, ordinary hops keep their usual receiver
checks. A later guard tests the owner produced by the preceding run.

For `$team?->captain->club?->coach->score(key())`, an absent team or club
yields absence. An empty captain or coach raises the ordinary empty-owner
failure. All four paths skip `key()`. With every owner present, `key()` and
`score()` each run once, and a score of zero stays present.

A chain may end in consecutive checked reads from typed vectors and hashes,
for example `$team?->groups[groupKey()][playerKey()]`. An absent guarded
receiver skips all keys and yields absence. Each selected key runs once, from
left to right. A missing hash key or out-of-range vector index raises the
ordinary lookup failure before any later key or coalescing fallback runs.
Each lookup returns its element by value. When that element is a collection,
the next lookup sees the selected snapshot, even if its key suspends and
replaces the original outer slot. A stored zero stays present through `??`.

Parentheses end the guard when they enclose the nullsafe field read. In
`($team?->scores)[key()]`, preparation must first form the optional result
of the field read, then check ordinary indexed access on that result. With
the current runtime, `vector<int>` and `hash<int,string>` fields have no
published optional representation. A `vector<string>` field produces a
`nullable<vector<string>>`, which does not support ordinary indexed access.
All three grouped forms therefore reject before execution. Removing those
parentheses places the lookup inside the guard: `$team?->scores[key()]`
skips the key on absence and evaluates it once on hit or miss.

A checked index may also feed a further nullsafe member access, for example
`$team?->players[key()]?->points` or `$team?->players[key()]?->score(arg())`.
An absent team skips the key. A missing slot raises the collection's checked
lookup failure. An existing empty handle yields absence at the second guard.
Only a present player reaches the field or call. The call retains that player
through argument evaluation, including when an argument replaces the slot.

An ordinary member can also follow one or more checked indices, as in
`$team?->groups[$group][$slot]->points` and
`$team?->groups[$group][$slot]->score(arg())`. An absent team skips both keys
and the argument. A missing slot raises its usual lookup failure. If the slot
contains an empty owner, ordinary `->` raises the empty-owner failure before
evaluating the argument. A present player stays alive through the call even
when the argument replaces the slot.

A chain can end by invoking an owned callable from a typed vector or hash slot,
as in `$router?->ops[key()](arg())`. An absent router skips the key and
arguments. A present router performs the checked lookup before evaluating
arguments. Lookup failure propagates. The selected callable stays alive through
invocation even if an argument replaces its slot. Its signature governs argument
binding, and its by-value result uses the ordinary nullsafe result representation.

A standalone call such as `$team?->coach->notify(key());` may end in a
synchronous `void` method. An absent team skips the arguments and invocation.
With a present team, the ordinary receiver check runs before `key()`. An
assignment or another member access requires the call to produce a value.

## v0.2 conditional nullable-local assignment — 2026-11-18

Statement-form `$n ??= fallback;` accepts an existing declared local whose type
is a published runtime nullable carrier. It tests presence once. A present value,
including zero, false or empty string, leaves storage unchanged and skips fallback.
When absent, it evaluates fallback once and applies ordinary assignment conversion
before storing. Both the fallback and its conversion are checked during preparation.
An explicit checked cast stays inside the selected branch.

Null and same-carrier fallbacks retain ordinary assignment behavior; they can leave
the destination empty. There is no new implicit float-to-int conversion. A failed
fallback or conversion performs no final store; any earlier fallback effects remain.
If fallback modifies the same local, successful completion still stores its result.
This statement does not yield a value. PHP also accepts the statement on a writable declared nullable instance field.
It evaluates the receiver once and retains the selected owner through the store or
exception. Rebinding the receiver during fallback leaves the store directed at the
original field. A write to that field by the fallback survives if the fallback throws.
For a PHP local typed hash, `$h[key()] ??= fallback();` saves the key once.
A missing key or an absent payload selects the fallback. A present zero skips it.
The presence probe leaves the hash untouched. On success the ordinary keyed-store
conversion applies and the current hash receives the value at the saved key, even
if the fallback replaced the hash or inserted that key. If fallback or conversion
throws, its earlier effects survive and the conditional store does not run.

For a PHP local typed vector, `$v[index()] ??= fallback();` saves the index once.
The initial bounds check throws before fallback for a negative index or an index
at or beyond the length. An in-range present value (including zero) skips fallback.
For an absent element the converted result replaces the element at the saved index
in the current vector. The store checks bounds again, so clearing or shortening
the vector can cause a write-back failure after fallback effects have occurred.
The operation never extends the vector.

These single-key hash and vector rules also apply to writable PHP instance fields.
For `$holder->items[key()] ??= fallback();`, the statement retains the selected
holder and binds its field before evaluating the key. Rebinding the holder during
the key or fallback still updates the original holder. Replacing its collection
field during fallback makes the replacement collection receive the store at the
saved key. Preparation checks field writability even when the element is present.

For PHP paths through typed vectors and hashes at any depth, the same root selection
applies. Evaluate and save every key once in source order before looking up any
intermediate entry. A missing intermediate entry skips the fallback.
The leaf collection supplies the single-key presence rule. When fallback is needed,
finish evaluating and converting its result before resolving the full path again
from the selected root with the saved keys. A replacement child receives the store. A removed parent or
an invalid vector position raises its usual error and preserves fallback effects.

For a PHP local `mixed` or `dynamic`, `$box[key()] ??= fallback();` evaluates and
normalizes one bracket key before testing the receiver and entry. On a container,
a missing key or stored null selects the fallback. Zero, false and the empty
string skip it. A null `mixed` becomes a container on the successful store. A null
`dynamic` raises `boxed_expected_container` before evaluating the fallback.
The ordinary boxed store uses the current local and the saved key, including
when the fallback replaces the local. A throwing fallback performs no final store.

Parameters, globals, static properties, other indexed places, for-header writes and
application/shared carriers remain outside this contract.

## v0.2 homogeneous scalar wrapper coalescing — 2026-10-20

For canonical `T` equal to `int`, `float`, `bool` or `string`, homogeneous pairs
`nullable<T> ?? nullable<T>`, `result<T> ?? result<T>` and
`result_or_false<T> ?? result_or_false<T>` produce an owned `T`.
Both alternatives are validated during preparation; only the needed fallback
executes. The left is evaluated and captured once. A usable payload, including
`0`, `0.0`, `false` or `""`, selects that payload without evaluating the fallback.
Otherwise the fallback is evaluated once and its usable payload is selected.
If neither branch has a usable payload, throw
`coalesce_selected_branch_has_no_usable_value_domain`; do not return a wrapper,
a default payload or silently discard the selected-branch failure.

The source wrappers remain unchanged and results are independent value copies.
`result_or_bool` remains invalid on either side even when unselected. Cross-family
wrapper pairs, widening, fixed-width payloads, records and ownership joins are not
added by this slice. The earlier nullable-record fallback exclusion below remains
applicable to record payloads.

## v0.2 nullable inline-record selections — 2026-10-07

For an exact inline-record payload `T` (a struct or `value<Class>`), ternary and
Elvis joins of `nullable<T>` with `T` or the same `nullable<T>` preserve
`nullable<T>`. Coalescing `nullable<T> ?? T` produces an owned `T`, extracting the
present payload or selecting the fallback. No implicit move or reference result
is introduced: payloads must be copyable, including when one branch is known to
be unselected. Both branches are validated during preparation; runtime evaluation
is lazy and evaluates the condition/left operand once.

Bare-null branches, nullable coalescing fallbacks, cross-record conversions and
contextual data-literal branches remain outside this bounded slice. This does not
broaden match, Result payloads or direct nonnullable-record joins.

## v0.2 inline-record conditions — 2026-10-07

Ordinary inline structs and `value<Class>` records are always truthy, independently
of field values. A nullable inline record is false when absent and otherwise
delegates to its always-truthy payload. Observation evaluates its operand once but
does not copy or consume it; noncopyable payloads are supported. All condition
consumers share this rule. Explicit boolean casts remain a separate contract.
`isset` observes presence; `empty` now has the distinct
[collection-only contract](count_empty_isset_contract.md#v02-collection-only-emptiness--2026-10-07)
and must not be inferred from negated truthiness or absence.
This document defines the initial centralized runtime matrix for helper-based lowering of:
- `??` via `php::coalesce_eval(...)`
- `?:` / ternary via `php::ternary_eval(...)`

## v0.2 shared-handle joins

Full ternary, shorthand Elvis and coalescing accept two shared-handle alternatives
when their types match or one supplied type can upcast to the other. In the latter
case the result is that supplied base/interface type, independent of branch order
and of the destination receiving the expression. Ordinary classes and explicitly
shared structs use the same rule; a record paired with an implemented interface
produces the interface handle. Preparation observes ancestry dependencies.

For full ternary and Elvis, required and optional nominal object branches use
that same supplied-payload ancestry rule. If either branch is optional, the result
is optional even when the branch supplying the base type is required: `?Derived`
paired with `Base` produces `?Base`, in either branch order. Absence and object
identity are preserved. This forms the nullable application of the selected base;
it does not require a branch already spelled `?Base`. Reference arguments still
require the exact resulting type. This clarification does not change scalar-wrapper
coalescing or decide additional optional-fallback coalescing forms.

No unseen common ancestor/interface is inferred for siblings. Such branches must
first be explicitly normalized to a chosen handle type. Inline
record joins, bare-null branch inference and wrapper-payload widening are not
enabled by this amendment. Match retains its existing result admission.

The condition/left operand is evaluated once. Only the selected ternary branch or
needed fallback executes. Shared Elvis and coalescing both test handle presence;
an empty left selects the fallback, which may itself be empty. The selected handle
retains its object/control-block identity without allocating or copying a payload.
Results are values, not reference aliases. Statically invalid joins reject during
preparation even when the invalid branch is unselected.

Unique handles follow the same supplied-type join rule for full ternary, Elvis and
coalescing. Named unique alternatives require explicit `move(...)`; owned call or
selection results already transfer. A selected upcast must satisfy the existing
virtual-destruction contract. Evaluation consumes only the selected full-ternary
branch. Elvis/coalescing evaluate and consume their left operand once, then consume
the fallback only if that left result is empty. The result is an owned value; moves
leave named source storage empty and preserve payload identity. No payload copy or
implicit unique-interface conversion is introduced.

Weak handles accept full ternary with matching types or an existing permitted
same-family upcast, preserving control-block identity and empty/expired state.
Weak Elvis/coalescing remain rejected: observing usability requires explicit
`lock(...)`. Cross-family ownership joins and unseen common ancestors remain
rejected, and match keeps its separate admission rules.

Other scalar, result, nullable and boxed selections retain their own admission rules.
