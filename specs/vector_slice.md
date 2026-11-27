# Generated concrete vectors

Doc Status: normative

Current representation: [native scalar aliases](native_scalar_aliases.md) supersedes earlier scalar wrapper spellings and extraction adapters.

Scope: runtime and physical API preparation inside v0.2. Source type resolution,
evaluation order and contextual conversions remain compiler-owned.

## Nested collection writes

In the PHP, C++ and JavaScript flavors, a writable local or instance field can be indexed through
any sequence of typed vectors and hashes, as in `groups[team][i][name] = value;`.
Evaluate and save each key once in source order, then evaluate and convert the
value. Follow the saved keys from the current root. Each intermediate hash key
must exist and each vector index must be in bounds. A final hash store inserts
or replaces the entry. A lookup failure prevents the store and preserves earlier
operand effects. Intermediate collections are accessed in place.

For an instance field, select the receiver once before evaluating any keys.
A shared receiver stays alive through write-back, including when an operand
rebinds the variable that originally held it. If an operand replaces the field
on that same receiver, follow the saved keys into its current collection. Inline
struct copies retain their independent storage. Field visibility and read-only
checks apply to nested writes, updates and append.

In PHP, keyed stores, appends, compound assignments and standalone prefix or
postfix updates can select their field receiver from a typed vector
of shared class handles, as in `$owners[i()]->values[j()] = rhs();` and
`$owners[i()]->values[] = rhs();`. Parentheses around
`$owners[i()]` have the same meaning. Evaluate the owner index once and retain
the selected handle before evaluating the field key. If the key or RHS replaces the owner entry,
the original object receives the write. Its destructor can observe the stored
value even when the vector held its last handle. Field access checks and the
destination collection's key and value rules apply. Inline struct elements still
require a separate write-back rule.

For a compound such as `groups[team][name] += rhs()`, read the existing leaf
before evaluating the RHS. Compute from that saved value, then check the entire
path again at write-back. An initial lookup failure skips the RHS. If the RHS
removes and reinserts an entry, write-back follows the saved key into its current
entry. If any entry is still missing, write-back fails and the RHS effects remain
visible. Standalone prefix and postfix increments and decrements likewise require
an existing leaf. Keys retain the receiver's usual exact type requirements.

For nested append, as in `groups[team][] = value;`, evaluate and save the path
keys in source order, then evaluate and convert the value. Resolve the saved path
in current storage and append to the selected vector or integer-keyed hash. If
evaluation replaced the collection, the replacement receives the append. A missing
parent raises its normal lookup error. A hash selects its next key from the live
entries at append time under the [hash append rules](hash_slice.md#append-and-ordering).

## Representation and publication

The vector metadata record binds the source family to private concrete buffer storage,
a published signed count type (currently int64), value-copy reads and explicit set
mutation. It admits registered integer, floating, boolean, null and string element
families. Publication requests concrete specializations; the default emits
vector_int64, vector_string, vector_float64 and vector_bool_t in scpp/vectors.hpp. There is no public vector<T>
template or standard container alias. The compiler now resolves source vector<T> and
element aliases to a requested concrete identity through the shared export plan;
[typed empty construction, append and indexed reads](../compiler/docs/vector_operations.md),
[collection methods](../compiler/docs/collection_methods.md) and the construction and capacity operations below are supported.
The API generator does not resolve source symbols. Application-owned element types use the separate [application vector specialization rules](../compiler/docs/application_vectors.md).

Each class holds a pointer to a private generated storage record owning the element buffer. Default and moved-from values are empty without allocation.
Materializing storage adds an allocation and indirection for the record in addition to its element buffer. No inline-layout or equivalent performance claim is made. String and vector
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
| has(count index) const | Return bool_t indicating index presence, without copying an element or allocating |
| append(element value) | Append an owned value; move the argument into storage |
| at(count index) const | Check bounds and return a value copy |
| set(count index, element value) | Check bounds and move the value into the existing slot |
| clear() | Destroy elements and retain capacity |
| remove(count index) | Remove the indexed element and shift the suffix left; return bool_t indicating removal |

For at/set, negative and beyond-end indices throw std::out_of_range before unsigned conversion.
For has/remove, every negative or beyond-end index returns false without mutation or allocation.
Successful removal preserves the order of remaining elements and retains capacity.
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

## Native traversal

Concrete vectors expose the [common traversal protocol](collection_traversal.md).
It borrows the live wrapper and copies one element at a time; no cursor allocation,
collection snapshot or per-wrapper state is added. The [compiler foreach slice](../compiler/docs/foreach.md) consumes these bindings.

## Vector construction and capacity

The default runtime publication selects `vectors.capacity_helpers`. A reduced
publication can omit the group. It depends on checked reserve. Published concrete
vectors and compiler-generated application vectors use the same element-lifetime
recipe. Source access uses the normal member separator for the flavor.

| Operation | Contract |
| --- | --- |
| `v.capacity() → int` | Borrow the receiver and return its capacity in elements. Empty default and moved-from vectors report zero. |
| `v.reserve(n) → void` | Preserve size and contents and ensure capacity at least n. Smaller requests retain capacity. |
| `v.clear() → void` | Destroy the live elements and retain capacity. This covers both historical clear helper names. |
| `v.resize(n, fill) → void` | Preserve the retained prefix and destroy removed elements. When growing, copy the evaluated fill into each new slot. A throwing copy destroys the partially constructed suffix and preserves the original contents and size. A successful buffer expansion may remain observable. |
| `v.compact() → void` | Request compact storage for the current size, preserving contents and order. Exact final capacity is unspecified. |
| `v.compact(n) → void` | Preserve all elements, with capacity at least the larger of n and size. A request above current capacity can grow storage. |
| `vector_filled(n, fill) → vector<T>` | Infer T from the fill's source type and return n copies in an owning vector. Use the same published or application specialization as an explicit vector type. |

Counts are signed source integers. A negative count or explicit capacity raises
`ValueError` before provider mutation. Unrepresentable allocations use the
checked reserve length error. Fill arguments are evaluated once, including on
zero counts and shrinking resize. Element copying follows the type's usual
semantics. Struct values copy independently and copied class handles share the
same object. A vector indexed read produces a value, so an indexed fill from
the receiver stays valid during growth.

The free factory belongs to the general surface. C++ admits it by default.
PHP, JS and Python require `"general_api": true` in the root manifest.
Receiver methods follow the collection surface independently of the flag.
PHP and C++ source use the arrow separator and JS and Python use the dot.
