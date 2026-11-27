# Nullable and result carriers

Doc Status: normative

Scope: v0.2 runtime/API generation. This is the user-requested follow-on to the
completed numeric/string/vector goal; source compiler integration remains separate.

## Metadata and public surface

The carriers metadata record owns enabled families, default states, explicit boxing,
extraction policy and structured-error field identities. Publication selects concrete
family/payload pairs. Defaults publish nullable, result, result_or_false and
result_or_bool for int64, bool_t, string and vector_string: sixteen concrete classes
in scpp/carriers.hpp, with short names available through short_names.hpp.
Other published integers, float64 and concrete vectors can be requested.
The [boxed-result extension](boxed_results.md) additionally admits result_mixed, result_or_false_mixed and
result_dynamic when their boxed family is published. Other boxed carrier families
remain unadmitted. Bare `null_t` payloads,
nested carriers, errors as payloads, arbitrary user classes and move-only payloads
are not admitted in this slice. Nullable additionally requires published null_t.

No public templates or additional carrier arithmetic matrix are emitted. Existing
payload operators are reused wherever C++ lookup and conversion rules allow it.
Metadata selects implicit_checked_one_layer_value_copy by default; the supported
explicit_checked_one_layer_value_copy policy retains value()/static_cast extraction
while disabling implicit extraction. Preparation rejects unsupported policies and
missing payload/error dependencies before emission.

| Family | Default state | Payload construction | Other state construction/query |
| --- | --- | --- | --- |
| nullable_T | Empty | explicit nullable_T(T) | explicit null_t constructor; reset() empties; has_value() |
| result_T | Successful T{} | explicit result_T(T) | explicit result_T(exception); has_value(), has_error(), error() |
| result_or_false_T | False sentinel | explicit result_or_false_T(T) | from_false(), is_false(); reset() restores false sentinel |
| result_or_bool_T | False sentinel | explicit result_or_bool_T(T) | from_false()/from_true(), is_false()/is_true(); reset() restores false sentinel |

All state queries return bool_t. from_false and default non-result construction
need no allocation; from_true can allocate. Result default success deliberately
preserves the reviewed legacy behavior. Replace a result with an explicitly
constructed result to change its state; there is no direct payload/error assignment
shortcut. Copy/move assignment between the same concrete carrier is provided.
Legacy null-to-false coercions, generic operator->, delegated container members,
interior references and global true/false/error sentinel objects are not imported.

## Checked extraction and ordinary C++ rules

value() returns a payload copy and the conversion operator returns the same copy.
Empty nullable, error result and either boolean sentinel throw std::runtime_error
with carrier_no_value. A boolean false **payload** is a successful payload, distinct
from the false sentinel; a true sentinel is not a successful bool_t payload either.
Use has_value() to test presence and value() to observe payload truth.

```cpp
nullable_int64 maybe{int64{20}};
result_int64 computed{int64{3}};
int64 sum = maybe + computed; // Plain payload result: 23.
int64 next = maybe + 1;       // Native payload arithmetic after extraction.
result_or_bool_bool_t actual_false{bool_t{false}}; // has_value() is true.
auto sentinel = result_or_bool_bool_t::from_false(); // has_value() is false.
```

Implicit extraction now returns a native scalar alias. Ordinary C++ standard
conversions can follow that one user-defined conversion: `if (carrier)` observes
payload truth, and an integer payload can convert to double. This is the explicitly
approved native ABI policy; use `has_value()` to test presence instead. A false
payload is present, while extracting an empty/error/sentinel state still throws.
No implicit cross-carrier conversion is added; explicit reboxing never propagates
empty/error states silently. Same-identity copying preserves the entire state.
The compiler supports [published carrier type resolution](../compiler/docs/carrier_types.md) and
[packed carrier conversions and payload operations](../compiler/docs/carrier_values.md).
The compiler documentation records the bounded source slice and its explicit donor departures.
Because extraction returns a value, it does not supply a mutable payload reference
for ++carrier or carrier += value. Explicitly extract, update and rebox when a
caller intends to replace the stored payload; compiler lowering owns source updates.

The compiler can lower source operations to checked value() calls in temporaries,
then call ordinary payload operators, enforcing source sequencing and language flavor.
prepared.json records family/payload mapping, state defaults, boxing/extraction policy,
checked failure, value result category, lifecycle and explicit-lowering responsibility.
It does not claim source admission, take binding-expression integration or null-safe
operator lowering. Runtime native int variables remain indistinguishable from literals.

## Structured errors

Result failure retains a shared exception handle. Its payload owns the message, file and int64 line.
An empty handle represents no error. Explicit
construction creates a real error even with an empty message:

```cpp
result_int64 failed{scpp::exception{string{"unable to read"}, int64{7}, string{"app.phs"}}};
if (failed.has_error()) {
    string message = failed.error().get_message();
}
```

Constructing a failed result from no_error throws std::invalid_argument with
result_requires_real_error before publishing a new state. Reading error() from a
successful result throws std::runtime_error with result_no_error. Error inspection
returns another owner of the same diagnostic. Reassigning the owner leaves the carrier intact.
For two results of the same specialization, `same_error_identity(other)` is true
when both contain errors with matching identity fields. The runtime borrows both
stored errors and compares string fields as byte views. The public `error()`
accessor continues to return an owned value.

The native type is `scpp::exception`. Existing native callers can use the compatibility
alias `scpp::error_t`. Both spellings refer to one type. PHP exposes `Exception`.
Loose result equality accepts any two failures. Strict result equality compares
the catalog fields message, file and line, including across different objects.

## Ownership, reuse and limitations

Reviewed runtime/include/scpp/nullable.hpp, result_core.hpp, result.hpp,
result_or_false.hpp, result_or_bool.hpp and error_t.hpp, plus
specs/error_values_and_take.md. Reuse their optional/result state distinction,
checked access, result defaults, real-error requirement and one-layer typed boundary.
Do not import old wrapper lifting, shared-object conversions, references, delegated
iteration, runtime error module or source take machinery.

Private carrier_semantics.hpp adapts the state/checking responsibility using standard
variant storage. The generator supplies concrete types and selects allowed state
constructors; one shared handwritten primitive owns checked extraction/error access.
Carriers reuse the existing private-pointer lifecycle emission. Copies preserve state
and own independent values; checked extraction always copies, including from rvalues.
Moves transfer ownership and reset the source to its family's default state (a moved-
from result is therefore successful T{}). Self-copy/self-move work. Copy assignment
builds a replacement before mutation. No extraction consumes or resets the source.

Default wrappers use no allocation; materialized payload/error/true-sentinel storage
adds an allocation and indirection. This favors a simple template-free boundary over
inline storage; no allocation/performance equivalence to std::optional is claimed.
Standard allocation exceptions propagate. Null/nested carrier payloads, move extraction,
state-propagating arithmetic, conditional extraction transactions and compiler source
value operations remain explicit later slices, not implied capabilities.

## Validation and cleanup

carrier_consumer checks arithmetic composition, empty/error/sentinel failures,
boolean payload distinction, independent string/vector copies, moves, invalid-error
replacement preservation and platform-header composition. carrier_preparation checks
policies, payload/dependency rejection, duplicates and deterministic ordering.
carrier_publication builds a minimal nullable_int32-only API with no string/error
surface and explicit-only extraction. Public closure checks include carriers.hpp.
No generated files enter Git. No old implementation files or modules are imported;
no obsolete files need archival in this slice.

Validation: all 24 tests pass in Release and in a fresh standalone Clang/libc++
UBSan build with recovery disabled. The ownership/error-state consumer passes
Clang/libstdc++ ASan+UBSan; leak detection remains disabled for the previously
recorded environment limitation. No libc++ ASan or leak-sanitizer success is claimed.

## Shared type/export integration

Metadata family records explicitly retain default_state and states. `carriers.provider`
owns placement; requested specializations and exception enter the shared export index.
Provider assembly derives dependencies from actual payloads. A legacy publication header
is accepted only when it agrees with provider placement. No ABI or lifetime algorithm
changes accompany this integration. See [compiler carrier types](../compiler/docs/carrier_types.md)
for supported payloads, incremental proofs and the boundary before source value operations.

### PHP take outputs

PHP reserves `take` for result families. For `?T`, test `$value !== null` for presence
or `$value === null` for absence. A present zero, false, or empty string passes the
presence test.

`take($value) = expression` evaluates the result expression once and yields a
bool. Success means the result contains a payload, including zero or false.
On success it assigns the exact payload type to `$value`. On failure an existing
value survives and a fresh output stays uninitialized. A fresh output requires
no default instance of its payload type.

| Result family | Additional outputs | Failure |
| --- | --- | --- |
| `result_or_false<T>`, `result_or_bool<T>` | `take($value, $ok)` | Each boolean sentinel sets `$ok` to false. |
| `result<T>` | `take($value, $error)` or `take($value, $error, $ok)` | Sets the diagnostic and clears the optional status. Success clears the diagnostic. |

The multi-output forms may omit `take`, as in `($value, $ok) = call();`.
All outputs must be distinct writable variables of the exact output types.
The error supports a condition and `get_message()`, `get_file()`, and `get_line()`.

In PHP, a value return to a declared `result<T>` accepts a present `Exception`
or shared descendant, including the diagnostic from `take`. The result retains
the same object, dynamic class, message and construction site. Throwing the
extracted object reaches ordinary typed handlers. The specialization
`result<Exception>` is reserved because an exception could occupy either arm. Returning a
cleared diagnostic raises `result_requires_real_error`. This conversion is
specific to a result return. Other typed destinations and ordinary arguments
retain their existing admission rules.

A direct success test admits a read of a fresh payload on that path. A failure
branch that returns or throws leaves only the initialized path at the join.
An empty diagnostic from the same structured extraction also proves success.
For example, after `($value,$error) = read(); if ($error) { return $error; }`,
the remaining path can read `$value`. Overwriting a status slot, taking a
mutable alias, or passing it by mutable reference discards its evidence.
Once initialized, the payload remains usable independently of that flag.
An unconditional assignment also initializes the payload. This slice admits
extraction as a standalone statement, an assignment or declaration initializer,
a direct `if` or ternary test (including negation), a return expression, or the
direct positive condition of a PHP `while`. Both `while (take($row) = next_row())`
and `while (($row,$ok) = next_row())` evaluate the producer once per test.
The body may read the exact payload, including zero and empty strings.
A miss preserves the payload slot and writes any status outputs.
`continue` repeats the test and `break` exits with the current outputs.
After the loop, status outputs and previously initialized payloads can be read.
A fresh payload gains no post-loop initialization proof. Other loop headers
and generators remain deferred. Across loops and exception regions, preparation
conservatively keeps payloads already proven initialized on entry.

A ternary admits a fresh payload read on the success edge. On failure, it may
read a payload slot that was already initialized. Only the selected arm runs.
In a declared PHP by-value return, each arm uses the ordinary return conversion.
Parentheses place the extraction in the condition and keep the ternary outside
the input expression:

```php
return (($text, $error) = fs_get($path)) ? strlen($text) : $error;
```

For a PHP falseable runtime handle such as the return of `mysqli::prepare`,
a boolean guard tests payload presence.

Member access through `->` on a nullable or result carrier resolves fields and
methods on its declared payload type. The receiver is evaluated once. Access
checks for a present payload and borrows it with the receiver's readonly or
writable access. Absence, failure and either boolean sentinel raise an error
before the selected member is used. A prior condition is optional.

Borrowing preserves the identity of the selected payload. When a method
argument or the RHS of a compound field update replaces the carrier, the
operation finishes on the originally selected payload. A plain field store
evaluates its RHS before selecting the destination. These rules also apply
when the receiver comes from a call or a nested field.
