# Vector construction and capacity audit

Doc Status: planning

Reviewed active v0.2 at `9f69d75c3`, release v0.1 at `d2f63e37e` and my-try at `624ab8a2d`. Seven historical names represent eight call forms. The two clear names have identical semantics. Three forms are already covered by two v0.2 members. The remaining five forms need native operations and source publication.

Stage 1 is implemented in the [vector contract](../../vector_slice.md#vector-construction-and-capacity). The table records the intake decisions.

## Contracts and proposed disposition

In the table, `v` is a vector, `n` is a signed source integer passed by value and `x` is an exact copyable element. Member spellings below use PHP syntax. Native targets without an existing binding are proposals.

| Donor form | Proposed source and native target | Passing and result | Distinguishing proof for implementation |
| --- | --- | --- | --- |
| `vector_capacity(v)` | Add `v->capacity()` → `v.capacity()` | Borrow const receiver, return int. | Default and moved-from values report zero. Observe a reserved empty vector through a const reference without allocating. |
| `vector_reserve(v,n)` | Existing `v->reserve(n)` → `v.reserve(n)` | Mutable receiver, void. | Reserve 16, append 3, reserve 2. Capacity stays at the previous value and size stays 3. |
| `vector_clear(v)` | Existing `v->clear()` → `v.clear()` | Mutable receiver, void. | Destroy owned elements, retain observed capacity and reuse the buffer. |
| `vector_clear_keep_capacity(v)` | Same existing `clear()` member | Same receiver and result. | Same postcondition as the preceding row. A second method would add a spelling for identical behavior. |
| `vector_resize(v,n,x)` | Add `v->resize(n,x)` → `v.resize(n,x)` | Mutable receiver, scalar fill by value or owning element by nonretaining const reference, void. | Grow from an indexed fill through reallocation. Shrink without overwriting the prefix. Evaluate the fill once even when shrinking. |
| `vector_compact(v)` | Add `v->compact()` → `v.compact()` | Mutable receiver, void. | Preserve size, order and values, including owned strings. Donors permit a nonbinding shrink request. Exact capacity is unspecified. |
| `vector_compact(v,n)` | Add `v->compact(n)` → `v.compact(n)` | Mutable receiver, void. | Test requests below size, between size and capacity, and above capacity. Preserve every element, with final capacity at least max(size,n). A large request may grow the buffer. |
| `vector_filled(n,x)` | Propose general free factory `vector_filled(n,x)` → concrete vector's generated static `filled(n,x)` | Count by value, same fill policy as resize, owning vector returned by value. Infer the vector from the fill. | Zero and positive counts, exact result element type, independently mutable string values, and existing shared identity for class elements. |

Both donors reject negative counts and explicit capacities. Recommend the same v0.2 ValueError channel as reserve, before provider mutation. An out-of-range allocation size follows reserve's length error. Fill copyability applies even when the count is zero or shrinking. The donor guarantee for negative input does not specify rollback on every allocation or element-copy failure. In v0.2 the number of live elements must always reflect successful constructions. For resize growth, recommend destroying the newly constructed suffix if a fill copy throws; decide separately whether a successfully enlarged capacity may remain observable.

## Evidence by layer

* Release provider `runtime/include/lang/php/support/php_value.hpp` and its `vector_t.hpp` at the release revision implement both clear forms with the same clear member. Unary compact calls shrink_to_fit. Binary compact reserves at least max(size,n) in replacement storage and moves every element. The signature stub's default zero conceals this overload distinction. My-try's strict helper spec, provider and compiler fixtures confirm both forms and add copyability, readonly observation, alias and error cases. See the [archived strict contract](../../../v0.1/specs/builtins/vector_helpers.md) and [donor signature index](intake.tsv).
* Current source lookup was exercised with a PHP vector of strings and general_api enabled. All eight donor calls were rejected as unknown free functions. Clear and reserve members compiled. Capacity, resize and both compact member calls were rejected as unknown methods. The current default generated registry contains clear and reserve bindings for int and string vectors in PHP, C++ and JS. [Collection method admission](../../../compiler/docs/collection_methods.md)
* Runtime metadata already selects checked reserve. The concrete storage recipe has count and capacity, raw allocation, element destruction and nonthrowing relocation. Clear destroys the live elements and keeps the buffer. Reserve only grows. That makes capacity a small observer, but compact needs a relocation path that can also shrink. Blank vector wrappers can have no storage. The recipe serves both published runtime vectors and application-generated vectors of eligible struct/class elements. [Storage recipe](../../../tools/api_generator/templates/collections/vector_storage.cpp.tpl) · [Application vectors](../../../compiler/docs/application_vectors.md)
* The compiler generator currently selects a short list of collection members and result types. Extend the prepared operation/signature metadata for a count result, element-typed fill and arity-aware compact resolution. A receiverless factory additionally needs to resolve its concrete result from the fill, including application-owned types. Reuse concrete vector type resolution and publication. A table of global overloads for just the runtime int/string types would leave application vectors out. [Binding generator](../../../tools/api_generator/php/render_compiler_vectors.php) · [Vector publication](../../vector_slice.md)

## Resize alias probe

Two scratch executables ran on Linux using the current runtime archive and Clang C++23/libc++ at -O2. Neither adds a resize binding. Both start with one three-byte string containing an embedded NUL and grow to 128 using existing reserve and append.

1. A PHP-flavor stand-in `grow(vector<string> &$items, int $n, string $fill)` was called as `grow($v,128,$v[0])`. Generated C++ evaluated `v.at(0L)` into a local string and moved it to the by-value parameter. Replacing slot zero afterward left the checked first and last appended values intact.
2. A native stand-in accepted `const scpp::string& fill` and was called directly as `grow(v,128,v.at(0))`. Allocation hooks confined to reserve observed allocation and deallocation. After replacing slot zero, all 127 appended elements still matched the original bytes.

The property that makes the second call safe is the public indexed read. It returns an owning value whose temporary lives through the call. A resize member can borrow that value while it grows the vector. This case needs no resize-specific staging rule. It says nothing about references into private buffers or expressions where another argument changes the fill before entry. Use the active evaluation contract for those cases. The donor's blanket left-to-right rule is historical. [Indexed read contract](../../../compiler/docs/vector_operations.md) · [Evaluation](../../expression_evaluation.md)

## Decision for the family goal

Recommend keeping receiver operations as collection methods, following clear and reserve. Make the new free factory general by default in C++ and available through project opt-in elsewhere. Natural flavor factory spellings can follow in the flavor reviews. Include runtime and admitted application specializations, observing element value/handle semantics. Keep unsupported element families under their existing type rules.

Acceptance should include a source caller for each overload, negative/zero counts, bounds after shrink, alias growth, copy failures and moved-from/empty behavior, plus application vector type edits and recovery versus fresh output. Inspect the emitted member/factory and its declaration provider. Benchmarks and LLVM comparisons belong to Stage 2.
