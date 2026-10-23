# Error values and conditional extraction
Doc Status: normative

## Error state

`error` is a nonnullable, copyable structured value. Its state is explicit, not
inferred from message, line or file. Default initialization and the immutable
`error::$no_error` value represent no error. They are false in condition truthiness;
`!$error` is true. Explicit error construction produces a real, truthy error even
when the message is empty. Copying preserves the state and payload.

A failed `result<T>` must contain a real error. Constructing or assigning its failure
state from no-error is invalid and throws; rejected assignment preserves the previous
result. Error inspection must not permit mutating a failure into a no-error state.
The native `scpp::error` sentinel still constructs a genuine failure with empty details;
it is distinct from `scpp::error_t::no_error`.

## Conditional extraction

The primary v0.2 spelling is `take(outputs...) = source`. The helper spelling
`take(outputs..., source)` has the same extraction semantics. This is a dedicated
binding expression, not assignment to the result of an ordinary function call.
It returns `bool`, not the payload, and success does not test payload truthiness.

| Source state | Value output | Alternative output | Boolean result |
| --- | --- | --- | --- |
| Present `nullable<T>` | Copy payload | None | true |
| Empty `nullable<T>` | Unchanged | None | false |
| Value `result_or_false<T>` | Copy payload | None | true |
| False sentinel `result_or_false<T>` | Unchanged | None | false |
| Value `result<T>` | Copy payload | Set `error` to no-error | true |
| Failure `result<T>` | Unchanged | Copy real `error` | false |
| Value `result_or_bool<T>` | Copy payload | Boolean output unchanged | true |
| Boolean sentinel `result_or_bool<T>` | Unchanged | Set boolean output to sentinel | Sentinel value |

`result<T>` and `result_or_bool<T>` require two outputs; nullable and falseable
wrappers require one. Thus a boolean-false payload still returns true, and a
boolean-true sentinel does not assign the value output.

Evaluate the source once, before writing outputs. In the v0.2 implementation, take
owns a source snapshot and copies selected output values; it does not move from the
source or expose a reference into it. Ordinary evaluation/copy exceptions propagate:
take is not catch-all exception handling and does not promise transactional rollback
of source side effects or multiple output writes if a copy throws.

```php
$value int;
$error error;
take($value, $error) = produce(); // produce(): result<int>
if (!$error) {
    use_value($value);
}
```

Reusing `$error` across failures and successes is safe: each successful structured
take clears it. Empty messages do not imply success.

## Current implementation boundaries

The experimental v0.2 compiler admits the assignment spelling and normalizes both
spellings to one AST node. Outputs currently reference existing local variables or
parameters of the exact selected branch types; the two outputs must name distinct
declarations (aliasing between reference parameters is not analyzed). Payloads are
supported for scalars, exact `mixed`/`dynamic`, explicit callables and typed vector/hash Result payloads.
Nullable and all three Result families also admit exact enum payloads;
nullable take otherwise retains its scalar domain. Enum payloads use the same
state/output rules above, including true-sentinel take without enum write-back. See [enum wrapper boundaries](compact_layout_types.md#321-v02-nullable-and-result-enum-payloads).
Wrapped null/false are successful payloads, not wrapper failure.
Likewise, an absent callable is a successful payload: `take` returns true, replaces
the callable output with absence and clears the structured error output. Presence
and invocation are separate checks on that extracted callable. See
[Result-wrapped callables](language/closures.md#result-wrapped-callables).
Copied mixed containers retain value semantics; copied dynamic containers share
identity. Inline declarations and bare
tuple assignment `($value, $error) = source` are deferred. Nested eager operator
operands retain the compiler's existing sequencing restrictions.

The `take` spelling is intrinsic in this parser. User-function shadowing of this
intrinsic is not supported; broader reserved-name validation remains deferred.
The older PHP generator retains its documented helper spelling and function lookup
rules; this change does not give that parser assignment-form support.

The v0.2 compiler exposes default/copyable `error`, its registered readonly no-error
constant, direct conditions and logical negation. Core runtime metadata additionally
exposes `error::make(string message, int line = 0, string file = "") -> error` and
`get_message() -> string`, `get_line() -> int`, `get_file() -> string` instance methods.
The message is required; even `error::make("")` creates a real error. Details are
supplied values, not automatically captured source locations. Getters return source
values, not references into the error. They currently use the ordinary mutable,
stable whole-storage inline receiver rules; temporary/projected/read-only receiver
support is not added by this slice. Default/no-error getters return empty message/file
and zero line, without creating an error.

At ordinary value boundaries, canonical `error` converts to the failure state of
`result<T>` for already-admitted payload types. Shared conversion preparation selects
that operation; the runtime checks presence. This supports initialization, assignment,
by-value arguments and returns. It does not grant error conversion into
`result_or_false<T>` or `result_or_bool<T>`, nor admit `result<error>`.
`take` retains the state and output rules above, including clearing old errors on
success. No-error failure construction/assignment throws under the existing contract.

The factory is a registered static callable, not a runtime-constructor syntax rule.
`new error(...)`, broader error operators, the bare runtime error sentinel,
`result<error>` disambiguation and throw/catch/finally remain outside this slice.
Runtime condition truthiness is the shared authority; this contract does not
introduce an implicit or explicit boolean cast from error.

## Typed-container Result boundaries — v0.2

The shared type-capability model owns exact payload admission through the `result_payload`
capability: registered providers declare it, while completed source enum facts
publish it for all three Result families. This is not implied by
storage, copying or truthiness support.
Registered template copyability can depend on selected type parameters; Result
operations use these effective capabilities, not container-name special cases.
Registering transport support does not grant a condition contract or bypass
operation-specific copy checks. Runtime type registrations remain separate from
the current function-module JSON catalogs.

All three Result families admit exact `vector<T>` and `hash<T,K>` payloads for
construction, same-wrapper copying, checked required extraction and existing-output
`take`. The payload must first have its own concrete type; this does not introduce
implicit literal-to-container-to-Result conversion. Element/key types remain exact.
Copy operations require recursively copyable contents, including nested containers
and completed inline-record declarations. Containers holding unique/noncopyable
values do not become copyable merely because a Result wraps them.

The copies retain normal container semantics; extracting or taking a container does
not alias the wrapper's container storage. Contained shared/dynamic values retain
their own sharing semantics. An empty container is a successful value: `take`
succeeds, structured-error output clears, and `isset` observes the wrapper state.
Failure preserves the existing value output. Required extraction of a non-value
state throws under the existing Result contract.

Full ternary joins preserve an exact wrapper identity against the same wrapper or
exact payload. Result-left `??` accepts an exact container fallback and evaluates it
only on a non-value state; successful empty containers do not select the fallback.
The existing prohibition of `result_or_bool` in `??` remains.

Value admission is distinct from truthiness: typed vector/hash payloads have no
condition contract, so a Result containing one also rejects in conditions and Elvis
during preparation. `empty` still does not unwrap any Result automatically. Extract
first and then inspect the container. No new reference extraction, implicit family
conversion, nullable-container support or direct-container selection rule is added.

## Required-local success paths — v0.2, 2026-10-23

Output binding is a write, not a read of its prior value. For admitted nullable,
falseable and structured-error payloads, a direct true `take` condition establishes
assignment of a required local output. False leaves the value output unchanged.
Logical negation swaps these facts; lazy `&&`/`||` route them to the executing operand.
If/loop tests and conditional-expression branches use the resulting path facts.

```php
$out json_node;
$failure error;
if (!take($out, $failure, json_node_member($parent, $name))) {
    throw Exception::make("missing member");
}
consume($out);
```

A true `result_or_bool` sentinel is not payload success and establishes no assignment
of an initially uninitialized value output. Already initialized outputs keep their
previous value on non-payload paths, as specified above. Saved boolean variables,
arbitrary boolean comparisons and ordinary function calls do not carry extraction
correlations. No new out-parameter or nullable-payload admission protocol is implied.
