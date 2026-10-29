# Concrete ownership and inline values

Doc Status: normative
Scope: native runtime/API generation for registered concrete records. No source
language admission or compiler adaptation is implemented by this slice.

## Owners and publication

`runtime/metadata/runtime.json` defines the supported ownership policy.
`tools/api_generator/ownership.cpp` prepares requested concrete specializations,
records native handle layout and explicit qualification conversions, and emits the
concrete lifecycle/API. Inline value capabilities remain native-payload-dependent. It is part of the
existing generator, not another CLI or lowering pipeline.
`runtime/src/ownership/ownership_semantics.hpp` privately owns placement lifetime
operations and checked payload access. Actual shared/weak/unique ownership uses
standard-library smart pointers; there is no new reference-count algorithm.

An optional publication block selects external, consumer-owned concrete records:

```json
"ownership": {
  "header": "scpp/ownership.hpp",
  "records": [{
    "name": "record_probe",
    "header": "scpp/ownership_records.hpp",
    "constructors": [["int64"]],
    "families": ["shared", "weak", "unique", "value"],
    "const_families": ["shared", "weak", "unique"]
  }]
}
```

This produces `value_record_probe`, mutable-pointee `shared_record_probe`,
`weak_record_probe`, `unique_record_probe`, and their `*_const_record_probe` variants.
The record must be a complete class in namespace scpp with a template-free public
header, a nonthrowing destructor and the requested constructors. The compiler
checks those C++ requirements. Record bindings are explicit trusted build inputs,
not inferred source types; source ownership formation/role checks remain compiler
responsibility. Names/headers, duplicate requests, scalar constructor dependencies
and weak-to-shared publication dependencies are checked during preparation.
Constructor parameters are published scalar values only in this bounded slice;
record/handle parameters, generic constructors and inheritance conversions are absent.
Weak must be requested alongside shared for the same payload and const qualification.
Either family list can be omitted; the combined request must be nonempty. Value-only,
const-only, unique-only and shared-only requests are supported. Const families admit
shared/weak/unique only. Requests are sorted for deterministic output.

The test publication at `tests/ownership/publication.json` uses four small handwritten
records: copyable, immovable, over-aligned move-only and throwing-move payloads. These are consumer proofs, not built-in
runtime records. Generated artifacts are under `runtime/generated/ownership-proof/`
and ignored by Git. Default runtime and compiler publications are unchanged.
This alternative publication is linked separately, never with another independently
generated definition of the same scpp scalar types. Request one unified publication
for a combined executable. Ownership source is `src/ownership.cpp`; consumers must
compile it along with the scalar source and provide record definitions.

## Public API and states

Shared and unique publish default-empty construction, destruction, move, reset,
swap, `has_value() -> bool_t`, explicit native-bool condition and checked `*` / `->`.
Shared additionally copies ownership without copying the record. Unique copy is
deleted. Payload creation uses a concrete static factory such as:

```cpp
auto owner = scpp::shared_record_probe::make(scpp::int64{7});
scpp::weak_record_probe observer{owner};
auto retained = observer.lock();
if (retained) {
    retained->value = scpp::int64{9};
}
```

Weak publishes default-empty construction, explicit construction from matching
shared, copy/move/reset/swap and `lock() -> shared_record_probe`. Locking an expired
or empty weak returns empty shared and is noexcept. No weak condition, direct
access, implicit shared conversion, expiration-based null comparison, use-count
observer or implicit payload extraction is exposed.

Empty shared/unique access throws `std::runtime_error("ownership_empty_access")`.
References/pointers obtained via checked access borrow the payload; they do not
extend lifetime. No standalone raw get/release/adopt/reset-pointer or native
smart-pointer accessor is exposed. Null-sentinel constructors/assignment, inheritance
conversions and ownership identity operators are not published yet; default construction and reset provide the empty state.

Moves empty their source; self-copy and self-move preserve ownership. Last strong
release destroys the payload exactly once. Weak owners retain only the control
block; coallocated storage can remain reserved after payload destruction until
last weak release. Noncopyable records may be shared and weakly observed.
Failed payload construction frees its storage. Factory evaluation completes before
assignment, so failed replacement preserves the existing owner.

Distinct handles may be used across threads under standard smart-pointer ownership
guarantees. Concurrent mutation of the same handle or mutable payload needs external
synchronization. A const handle does not make its payload const. Weak lock performs
the ownership acquisition; no preceding check substitutes for it. No cycle collector
or atomic mutation of a handle object is provided.

## Inline values and const pointees

Metadata enables inline values with a separate policy, so handle-empty and weak-lock
rules never apply to them:

```json
"value": {
  "binding": "inline_record",
  "construction": "explicit",
  "special_members": "payload",
  "access": "borrow"
},
"const_pointee": "explicit_add_const"
```

`value_record_probe` contains a direct `record_probe` member. Requested constructors
are explicit; `constructors: [[]]` requests default value initialization only if the
payload supports it. No default constructor is invented for other publications.
The wrapper declares no copy/move/destructor: C++ derives those special members and
their exception specifications from the payload. Immovable records remain immovable;
throwing moves propagate normally. A move does not create an empty value state.
Payload copy semantics apply recursively according to each member's own semantics;
this does not promise recursive deep cloning of referenced objects. `*` and `->`
provide mutable or const borrows according to the value object's constness, without
implicit payload conversion, reset or a nullable state. Size and alignment are
statically checked against the payload, including over-aligned records.

```cpp
scpp::value_record_probe item{scpp::int64{7}};
auto copy = item;                     // Copies the record.
copy->value = scpp::int64{8};
auto owner = scpp::shared_record_probe::make(scpp::int64{7});
scpp::shared_const_record_probe view{owner};
scpp::weak_const_record_probe observer{view};
auto retained = observer.lock();     // shared_const_record_probe
```

Const-pointee owners use private `std::shared_ptr<const Record>` / unique/weak
equivalents, with checked access returning const references/pointers. They can also
be created directly through `make`. A const handle to a mutable record still exposes
a mutable record, following C++. A const-pointee handle grants read-only access; it
does not prevent other mutable aliases from modifying the object or provide thread
synchronization.

Where both endpoints are published, explicit constructors add pointee constness:

| Target | Mutable source | Effect |
| --- | --- | --- |
| shared_const | shared const reference / rvalue | Retain identity / transfer and empty source |
| unique_const | unique rvalue | Transfer and empty source |
| weak_const | weak const reference / rvalue | Retain observation / transfer and empty source |
| weak_const | shared const reference | Observe same control block |

Matching weak-from-shared construction also exists for const pointees. Const removal,
cross-record conversions and unique-to-shared transfer are absent. Qualification
constructors are noexcept, explicit and allocation-free; no cross-type assignment
is added. Preparation records these concrete conversion signatures and effects in
`prepared.json`; emission consumes the same prepared conversion list. Value special
members are described as native-payload-dependent rather than guessed by the tool.
Source value normalization and compiler ownership admission remain compiler work.

## Representation and allocation contract

Each concrete handle contains an aligned byte array; its private native smart pointer
is explicitly constructed inside it and destroyed exactly once. Copies/moves invoke
native constructors/assignments, never memcpy. Access uses properly laundered typed
pointers. The generator measures native-library smart-pointer sizes/alignment, and
every emitted specialization statically verifies its own actual native layout.
A deliberately mismatched layout is a compile-fail proof.

This is native-toolchain-specific generation, not a sealed portable ABI. Regenerate
and rebuild all consumers when changing the target/toolchain. Layout assertions
check size/alignment compatibility; they do not make mixed-toolchain binaries safe.
The public transitive include closure contains no templates or standard-library
implementation types. Private headers may use both.

Supported-toolchain measurement with nonallocating probe records:

| Operation | Allocation calls |
| --- | ---: |
| Inline value construction/copy/move/destruction | 0 wrapper allocations |
| Explicit add-const handle conversions | 0 |
| Default empty handles | 0 |
| Shared/weak copies and handle moves | 0 |
| Weak derivation and successful/expired lock | 0 |
| Reset/swap | 0 |
| Shared creation via make_shared | 1 |
| Unique creation via make_unique | 1 |

On the tested 64-bit Clang/libc++ target: shared and weak handles are 16 bytes,
unique handles are 8 bytes. These are measured bindings, not universal sizes or a
standard-mandated allocation count. Payload-internal allocations are separate.
Global allocation probes target these ordinary-aligned records; they do not measure
arbitrary over-aligned/custom-allocator payloads. Constructor-failure accounting
uses a nonallocating fixture exception to isolate ownership storage from linked
exception-library allocation/free implementations.

## Reuse and deliberate limits

Reviewed legacy shared_p/unique_p/weak_p/memory headers supply the semantic pattern:
standard ownership, checked access, explicit allocation factories and weak locking.
Focused lifetime tests adapt the shared-last-owner, weak lock, move/reset and
noncopyable invariants. No legacy module/header is a build dependency.
Generated concrete lifecycle functions replace publicly templated wrappers and the
compiler-style per-handle pimpl allocation. Narrow private helpers centralize native
object lifetime operations.

Source value normalization, inheritance/shared-self,
unique-to-shared transfer, result/container composition,
null-source lowering and compiler snapshot adaptation remain separate work. The
current public carrier extraction rules do not apply to these handles.

## Validation record

All 34 CTest checks pass in Release and UBSan configurations under Ninja/eight
jobs/Clang/libc++/mold. Ownership proofs cover metadata rejection and deterministic
emission, default and reduced family preparation, template-free transitive headers,
compile-time layout mismatch rejection, forbidden API operations, noncopyable
payloads, copy/move/self-assignment, checked empty access, factory allocation
failure, constructor failure and preservation of an existing destination. Inline
value tests additionally prove independent copies, immovable and move-only payloads,
over-alignment and throwing move construction/assignment. Const-pointee tests prove
identity preservation, moved-from emptiness, read-only access, qualification-only
conversion, const weak locking, balanced allocation/free counts and no payload copy.
Compile-fail probes reject const writes, const removal and immovable value copies.

The consumer measures allocations with nonallocating records and includes 64
lock-versus-final-release rounds, checking payload validity and exactly-once
destruction. This is focused concurrent coverage, not a ThreadSanitizer or exhaustive
concurrency proof. Focused Clang/libstdc++ ASan+UBSan also passes with leak detection
disabled for the sandbox environment; no LeakSanitizer success is claimed.
