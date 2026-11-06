# Generated concrete vectors

Doc Status: normative

Current representation: [native scalar aliases](native_scalar_aliases.md) supersedes earlier scalar wrapper spellings and extraction adapters.

Scope: runtime and physical API preparation inside v0.2. Source type resolution,
evaluation order and contextual conversions remain compiler-owned.

## Representation and publication

The vector metadata record binds the source family to private std::vector storage,
a published signed count type (currently int64), value-copy reads and explicit set
mutation. It admits registered integer, floating, boolean, null and string element
families. Publication requests concrete specializations; the default emits
vector_int64 and vector_string in scpp/vectors.hpp. There is no public vector<T>
template or standard container alias. The compiler now resolves source vector<T> and
element aliases to a requested concrete identity through the shared export plan;
[source vector operations remain deferred](../compiler/docs/vector_types.md).
The API generator does not resolve source symbols. Nested vectors, arbitrary user classes and carriers are not admitted.

Each class owns a private incomplete pointer to a generated std::vector<element>
storage record. Default and moved-from values are empty without allocation.
Materializing storage adds an allocation and indirection beyond std::vector's own
buffer. No inline-layout or equivalent performance claim is made. String and vector
generation share lifecycle emission. Public headers and their closure are template-free.

Preparation validates count/boolean publication, element families, native construction,
equality dependencies and explicit conversions before emission. Private generated
assertions verify element copy construction and nonthrowing move construction/assignment.
No old-tree include or link dependency is introduced.

## Concrete exports and provider ownership

Runtime metadata `vector.provider` owns the vector declaration group's placement
(default `core.vectors`, `scpp/vectors.hpp`). The optional legacy publication header
must agree; it cannot override metadata. Specializations remain requested only in
`publication.vectors.specializations`.

After validating those requests, prepareVectors publishes each canonical identity
through the shared type-export index: vector_int64 maps to scpp::vector_int64 and
its provider. The vectors declaration group's type index contains exactly those
selected identities. No duplicate specialization catalog is added to metadata.
These are publication-resolved exports. Compiler generation consumes the validated
runtime plan to register selected specializations; the metadata-only catalog does
not invent them.

`vector_publication` records the provider and dependencies derived from count,
boolean and selected element identities. Finalization validates the effective graph,
including cycles and unpublished dependencies, before any output is written. The
renderer supplies a declaration fragment; the shared assembler emits its header
and dependencies. Int-only vectors need no string or optional numeric include.
Checked vector-cast helpers remain implementation dependencies, not public includes.
Existing vector names, signatures, operations and failure behavior are unchanged.

CMake obtains the vector header from the common provider-output inventory. Header
relocation, provider sharing, removal and re-addition use existing publication cleanup.
Unrequested specializations have no concrete exports. Existing aggregate consumers
retain the derived vectors_header path; their declaration owners are not migrated
by the provider-only slice. The subsequent compiler slice consumes these exports
for type resolution, as described in [compiler vector types](../compiler/docs/vector_types.md).

The vector_provider_exports test covers export/provider identity, relocation and
sharing with string, strict inputs, bounds/copy/NUL behavior, minimal dependencies,
no-STL public closure, rejected-publication preservation and removal/re-addition.
It also verifies generated compiler bindings follow placement and specialization retirement;
unaffected scalar definitions remain byte-identical.

## Members and ownership

| Member | Contract |
| --- | --- |
| size() | Return count type, checked for representability |
| empty() | Return bool_t |
| append(element value) | Append an owned value; move the argument into storage |
| at(count index) const | Check bounds and return a value copy |
| set(count index, element value) | Check bounds and move the value into the existing slot |
| clear() | Destroy elements and release storage |

Negative and beyond-end indices throw std::out_of_range before unsigned conversion.
at exposes no reference, pointer, iterator or interior lifetime protocol. Modifying
its returned string cannot change the vector. Copies own independent elements. Moves
leave an empty reusable source; self-copy/self-move are supported. Copy assignment
prepares a replacement before changing the target. Failed argument conversions and
bounds checks leave contents unchanged. Allocation failure in append leaves logical
contents unchanged; an empty private owner may remain allocated. Allocation exceptions
propagate. Move assignment of an admitted element cannot throw.

## Native convenience and operators

Per-specialization publication selects native_index and native_value. Defaults admit
native int indices, native int values for vector_int64 and const char* values for
vector_string:

```cpp
vector_string names;
names.append("Ada");
string first = names.at(0);
names.set(0, "Grace");
vector_int64 numbers;
numbers.append(20);
numbers.set(0, int64{3});
```

These concrete members use the validated conversion recipes before calling core
members. Exact deleted overloads prevent other native numeric types from narrowing
into int signatures. They add declarations, not runtime algorithms. C++ cannot
distinguish an int literal from an int variable. Member adapters belong to the class publication. The former native bridge is retired. A tested
adapters-disabled publication requires explicit count and element values. Compiler
lowering must not depend on optional plain-C++ conveniences.

Requested == and != compare lengths and corresponding elements using published
element equality, returning bool_t. The private predicate explicitly converts that
result; no implicit bool conversion is needed. NaN retains IEEE non-reflexive equality.
Missing element equality rejects publication. There is no vector arithmetic, ordering,
truthiness, implicit container conversion or native initializer-list API.

## Explicit casts and physical plans

casts_from names other published specializations. Each request requires a published
explicit element conversion. It emits an explicit constructor usable with static_cast,
converting elements in order into a temporary target. Failure destroys the partial
target and preserves the source; assignment from a failed cast leaves the destination
intact. Identity uses copy construction. Defaults request no cross-element casts.

prepared.json records concrete source/runtime mappings, storage/lifecycle facts,
value-copy reads, mutable set behavior, concrete member argument/result forms, native
adaptations, equality dependencies and elementwise cast source/target facts. These
are physical plans, not source admission, sequencing or my-try-cpp integration.

## Reviewed reuse and proof

Reviewed runtime/include/scpp/vector_t.hpp bounds/append paths, as recorded in the
string slice: retain standard owned storage and negative-index checking; omit
iterator snapshots, reference-returning reads, wrapper lifting and the legacy error
subsystem. The shared handwritten sequence_index primitive owns bounds checks.

vector_preparation checks dependency rejection and deterministic publication.
vector_consumer checks both defaults, copy/move/self operations, string reallocation,
value-read isolation, native-overload rejection, bounds and conversion failures.
vector_publication compiles/runs an adapters-disabled publication with int32, int64,
string, float64, bool_t and null_t elements and requested casts, including partial
conversion failure and unchanged source/destination.

Cleanup: no retired files or archives. vectors.hpp and runtime.cpp are locally
generated and Git-ignored. No handwritten public specialization or second generator
is introduced.

Validation: all twenty release tests pass and also pass in a fresh standalone
Clang/libc++ UBSan build with no generated inputs or original-tree dependencies.
Default vector ownership and the alternate cross-element cast consumer both pass
ASan+UBSan under Clang/libstdc++. As in the earlier string proof, LSan is disabled
for the known environment limitation; no leak-sanitizer result is claimed.

## Initial provider-only integration verification (2026-11-06)

The runtime build and 55 runtime CTests pass. The focused vector-provider proof
passes default, relocated and shared-string placement, failure preservation,
removal/re-addition and unchanged compiler PHP. The existing provider proof passes
all-flavor incremental recovery and CMake demand removal/restoration. A subsequent
runtime build performs freshness checks without native compilation or linking.

Reviewed publication captures retain 130 accepted/247 rejected runtime cases,
19 accepted/537 rejected scalar cases and 17 accepted/37 rejected CLI cases.
Runtime snapshot changes are limited to prepared plans and vector header includes;
scalar-only C++ artifacts remain identical. The custom-header fixture now changes
the owning vector provider together with its legacy header assertion.

Representative native proof timings (milliseconds):

| Placement | Compile driver | Clang frontend | LLVM backend | Link driver |
| --- | ---: | ---: | ---: | ---: |
| Default | 64.27 | 13.75 | 9.67 | 54.38 |
| Relocated | 70.65 | 14.93 | 10.42 | 55.32 |
| Shared with string | 113.21 | 21.89 | 16.93 | 97.07 |

Clang C++23/libc++, O2/g0, mold, no PCH/LTO, one application translation unit,
separately compiled runtime object reused at link. These are single verification
samples taken while other native work was running, not comparative performance
claims. Runtime compilation is excluded from the application figures. Raw proof
and trace files are retained in `/tmp/scpp-vector-provider-proof/`.
