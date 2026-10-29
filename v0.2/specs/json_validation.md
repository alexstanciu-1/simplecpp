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
traversal, typed JSON extraction/encoding or source compiler registration is claimed.
The reader/writer still recurse. Raw input strings preserve donor byte acceptance;
Unicode escapes are validated separately. Persistent empty object identity remains
outside the hash-derived container model.
