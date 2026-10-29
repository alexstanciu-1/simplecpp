# JSON module validation

Doc Status: supporting
Date: 2026-10-29
Contract: [json_module.md](json_module.md).

## Evidence

- Full libc++ Release suite: **52/52** passed. Final focused JSON rerun: **3/3**.
- Focused libc++ UBSan: **3/3** passed (consumer, freshness, publication).
- The same consumer passed Clang ASan+UBSan with libstdc++. Leak detection was
  disabled; this is not a leak-sanitizer proof. Cycle tests explicitly remove
  back edges before teardown, consistent with the existing shared ownership model.
- A standalone exported client compiled and ran against copied public headers and
  the JSON, generated core, boxed-storage and packed-storage static libraries.
  Its include path contained no private headers or old runtime tree.
- Existing frozen artifact expectations remain unchanged. Adding the optional
  metadata section updates input fingerprints only; unused JSON policy emits no
  JSON files or prepared module. Full scalar/family/parity checks pass.
- `git diff --check` passed.

## Behavioral coverage

`tests/json/consumer.cpp` keeps checks active in Release and covers both families:
null/false success, nested values, full-document consumption, malformed tokens,
signed int64 boundaries and double fallback, numeric overflow/underflow, decoded
Unicode pairs and embedded NUL, invalid surrogate sequences, duplicate escaped
names and byte offsets, sibling names, donor key normalization at INT32_MAX and
UINT32_MAX, wider string names, packed/object output and insertion order, control
escaping, donor raw-byte output, nonfinite floats and recovery.

Ownership proofs distinguish mixed COW from dynamic aliases. Writer proofs cover
repeated aliases, direct/indirect cycles, repair and retry. Allocation injection
covers decoder/writer calls and floating formatting: allocation failures propagate,
never become checked syntax errors or successful partial numeric output. A custom
C++ decimal locale verifies locale-independent floating output.

`tests/json/publication.py` checks each independently requested operation and family,
compiles both its generated definitions and public consumer, preprocesses the public
include closure for templates/private std dependencies, rejects unrequested typed
encoding and invalid dependencies/requests/header collisions, and verifies stable
output under reordered requests. The module's separate CMake target proves that
core can be built without a JSON dependency.

## Limits and next boundary

No performance claim, exhaustive fuzzing, hostile nesting/resource budget, public
traversal, typed JSON extraction/container encoding or source compiler registration is claimed.
The reader/writer still recurse. Raw input strings preserve donor byte acceptance;
Unicode escapes are validated separately. Persistent empty object identity remains
outside the hash-derived container model.

## Production profile follow-up

`python3 -B v0.2/tests/json/build_profiles.py` exports only v0.2 sources to a
fresh temporary tree, excluding compiler/Cargo/build artifacts, and builds with
`BUILD_TESTING=OFF`. The completed sequence is JSON only, JSON plus datetime,
datetime only, core only, and JSON re-enabled in the same build directory.
Every profile passed its core/module examples, target and prepared-publication
checks, core-symbol separation and unchanged-build object timestamp checks.
This covers all four option combinations and returning to a previous output inventory.

The default build also passed after extracting profile composition into
`cmake/RuntimePublication.cmake`. The JSON/datetime focused Release suite passed
11/11. Runtime algorithms and generated API policies are unchanged in this follow-up;
no additional sanitizer claim is needed for the CMake composition change.

## Direct scalar encoding follow-up

The full Release suite passed 52/52 after adding int64, float64, bool_t and string
encode requests. Focused UBSan passed 3/3; the updated consumer also passed
ASan+UBSan with libstdc++ and leak detection disabled. Source-only production
profile checks passed again for all four combinations and JSON re-enablement.
Frozen output expectations remain unchanged; only metadata input fingerprints moved.

Scalar coverage checks exact integer extremes, both booleans, finite double boundary
values/negative zero, checked nonfinite failures, embedded NUL/control/quote escaping,
empty strings, literal string contents ("null" stays a JSON string), allocation
failure/retry and classic-locale formatting. Boxed and typed numeric results agree.

Each scalar operation is additionally prepared without any public boxed family.
Its public include closure is template-free, its generated runtime and module
sources compile, and unavailable encode/decode overloads, native C++ arguments,
null_t and absent dependencies reject. These tests prove direct typed transport;
no throughput or allocation-count improvement is claimed. The production example
also calls direct int64 encoding.

## Nullable scalar encoding follow-up

The four nullable scalar encoders use the existing presence query and one explicit
payload-copy extraction. The consumer verifies empty/reset states, present zero,
false, empty text, embedded NUL, and a present nonfinite float. Checked encoding
failure preserves the nullable; reset and retry succeed. Allocation injection while
copying/encoding a long nullable string preserves its source payload on every failure.

Publication proofs independently request each nullable encoder without boxed APIs
or public scalar encode overloads. Both implicit and explicit carrier extraction
policies compile; missing and mismatched carrier dependencies reject. There is no
new public template, nullable storage implementation or implicit boxing path.

The focused UBSan suite passed 3/3 and the consumer passed ASan+UBSan with libstdc++
(leak detection disabled). All source-only production profiles passed again,
including disabling both modules and re-enabling JSON. The production profile adds
nullable_float64 to the base core's existing nullable scalar requests; its example
now exercises empty nullable encoding. Frozen output expectations are unchanged.
The full Release regression suite passed 52/52 after this extension.
