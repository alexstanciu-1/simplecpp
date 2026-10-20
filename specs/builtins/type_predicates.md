# Exact-mixed type predicates in v0.2
Doc Status: normative

The bounded strict core surface exposes `is_bool(mixed) -> bool`,
`is_int(mixed) -> bool` and `is_float(mixed) -> bool`. Each call has exactly one
required, non-retaining observation input. The input must have exact `mixed`
storage type; this surface does not implicitly box other source types.

The result reports the stored mixed kind without coercion or extraction:

| Stored kind | is_bool | is_int | is_float |
| --- | --- | --- | --- |
| bool | true | false | false |
| int | false | true | false |
| float | false | false | true |
| null, string, owned/shared/weak table | false | false | false |

False is still a bool; zero is still an int. Integral-valued floats, either signed
zero, infinities and NaN are float values. Numeric-looking strings remain strings.
Table predicates inspect the carrier kind without dereferencing or locking the
table; expired weak table state still returns false for all three predicates.

Each input expression evaluates exactly once. The predicates do not mutate or
consume input storage, detach tables, retain a borrow or return a borrowed value.
Locals, readonly inputs, temporaries and properties may be observed under the
existing [observation contract](../runtime_module_metadata.md#non-retaining-observation).
A temporary property owner remains alive through the call. Errors from evaluating
the source expression propagate normally; the kind test itself performs no
value-dependent conversion that could fail.

The returned bool is a value snapshot. It neither changes the input's static type
nor introduces flow-sensitive type narrowing, reference adaptation or conditional
extraction. Existing explicit typed value boundaries retain their own contracts.

Plain scalar overloads, fixed widths, dynamic, wrappers, additional predicates
and flow-sensitive narrowing remain separate capabilities. This bounded admission
is not a claim that those wider source forms were intentionally removed from the
compatibility milestone. The runtime's generic C++ overloads do not independently
authorize source overloads.
