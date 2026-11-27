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

## PHP single-hop nullsafe access

`findPlayer()?->score` evaluates the optional shared receiver once. Absence
returns an empty optional. Presence reads the declared field and returns its
value in the field type’s optional representation. A stored zero or empty
string stays present. An already-nullable field preserves its own absence or
value, and a shared-handle field preserves object identity. Errors from
evaluating the receiver propagate.

Preparation resolves the field and checks visibility even when the receiver
can be absent. The field must have a supported optional representation.

For `findPlayer()?->scoreFor(key())`, absence skips the arguments and the call.
Presence retains the selected owner through argument evaluation and invocation.
Arguments bind and execute as for an ordinary instance call, including named
arguments in written order and dispatch through the declared receiver type.
A synchronous method returning by value uses the same optional result rule as
a field. Exceptions from selected arguments and the method propagate. Write
targets and longer nullsafe chains remain deferred.

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
This statement does not yield a value. Parameters, globals, properties, indexed
places, for-header writes and application/shared carriers remain outside this slice.

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
