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
The [boxed-result extension](boxed_results.md) additionally admits result_mixed and
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
| result_T | Successful T{} | explicit result_T(T) | explicit result_T(error_t); has_value(), has_error(), error() |
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
int64 next = maybe + 1;       // With the optional native numeric bridge.
result_or_bool_bool_t actual_false{bool_t{false}}; // has_value() is true.
auto sentinel = result_or_bool_bool_t::from_false(); // has_value() is false.
```

There is no direct carrier condition conversion. In particular, carrier<bool_t>
requires an explicit value()/state query: C++ does not chain the carrier-to-bool_t
and bool_t-to-native-bool user conversions for if(carrier). Extraction is exactly
one layer and does not itself change numeric type. Explicit target construction can
still compose a payload extraction with a checked target constructor under normal
C++ rules. No implicit cross-carrier conversion is added; explicit reboxing can
extract a successful exact payload and never propagates empty/error states silently.
Same-identity copying preserves the entire state, rather than extracting.
Payload operators such as ! on bool_t can still bind after extraction: !carrier
tests the boolean payload and throws for a missing payload, not for presence.
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

Result failure retains an explicit presence flag, owned string message/file and int64
line. scpp::error_t{} and scpp::error_t::no_error represent no error. Explicit
construction creates a real error even with an empty message:

```cpp
result_int64 failed{scpp::error_t{string{"unable to read"}, int64{7}, string{"app.phs"}}};
if (failed.has_error()) {
    string message = failed.error().get_message();
}
```

Constructing a failed result from no_error throws std::invalid_argument with
result_requires_real_error before publishing a new state. Reading error() from a
successful result throws std::runtime_error with result_no_error. Error inspection
returns value copies, so a caller cannot mutate a failure into no_error through it.
error_t has explicit native-bool condition conversion and has_error(); message/line/
file do not determine presence. The type stays namespaced by default because this
platform's C headers also define a global error_t typedef.

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
integration remain explicit later slices, not implied capabilities.

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
