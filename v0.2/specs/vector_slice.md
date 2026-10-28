# Generated concrete vectors

Doc Status: normative

Scope: runtime and physical API preparation inside v0.2. Source type resolution,
evaluation order and contextual conversions remain compiler-owned.

## Representation and publication

The vector metadata record binds the source family to private std::vector storage,
a published signed count type (currently int64), value-copy reads and explicit set
mutation. It admits registered integer, floating, boolean, null and string element
families. Publication requests concrete specializations; the default emits
vector_int64 and vector_string in scpp/vectors.hpp. There is no public vector<T>
template or standard container alias. The compiler resolves source vector<T> and
aliases to a requested concrete identity; the API generator does not resolve source
symbols. Nested vectors, arbitrary user classes and carriers are not admitted.

Each class owns a private incomplete pointer to a generated std::vector<element>
storage record. Default and moved-from values are empty without allocation.
Materializing storage adds an allocation and indirection beyond std::vector's own
buffer. No inline-layout or equivalent performance claim is made. String and vector
generation share lifecycle emission. Public headers and their closure are template-free.

Preparation validates count/boolean publication, element families, native construction,
equality dependencies and explicit conversions before emission. Private generated
assertions verify element copy construction and nonthrowing move construction/assignment.
No old-tree include or link dependency is introduced.

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

These concrete members explicitly construct runtime arguments before calling core
members. Exact deleted overloads prevent other native numeric types from narrowing
into int signatures. They add declarations, not runtime algorithms. C++ cannot
distinguish an int literal from an int variable. Member adapters belong to the class
publication, independent of the optional numeric native_bridge.hpp include. A tested
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
