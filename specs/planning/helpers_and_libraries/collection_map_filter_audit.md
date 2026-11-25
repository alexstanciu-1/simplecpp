# Collection map and filter audit

Doc Status: planning

Reviewed active v0.2 at `703d4fda2`, release v0.1 at `d2f63e37e` and my-try at `624ab8a2d`. The six historical free names describe two algorithms. They differ in admitted inputs, callback contracts and output construction. The current default publication rejected all six names in PHP source with `general_api` enabled.

## Disposition by call form

Here T is the visited value, U the map callback's declared result and K the original key type. All callbacks take one value. Keys are used to build the output and are never a second callback argument.

| General name | Release v0.1 | My-try | Proposed v0.2 typed family |
| --- | --- | --- | --- |
| `collection_map` | Sequence, keyed and boxed policies | Vector, typed hash, mixed and dynamic IDs | Dispatch on an admitted vector or typed hash. Return `vector<U>` or `hash<U,K>`. |
| `collection_filter` | Same input families | Same four IDs | Require an exact bool predicate. Return the input's collection type, with a fresh outer value. |
| `sequence_map` | Vector and fixed array | Vector ID | Vector → dense `vector<U>`. Record fixed arrays as deferred. |
| `sequence_filter` | Vector and fixed array | Vector ID | Vector → dense `vector<T>`, including a typed empty result. |
| `keyed_map` | Typed hash, mixed-key hash and table-valued mixed | Hash ID | Typed hash → `hash<U,K>`, preserving actual keys and insertion order. |
| `keyed_filter` | Same keyed inputs | Hash ID | Typed hash → `hash<T,K>`. Keep the original value for each successful predicate. |

Inputs include runtime and admitted application specializations. Resolve the exact output through the existing collection type resolver and check that it can store U. A filter keeps T even when it selects zero elements. The input and output have independent outer storage, with ordinary nested value or shared-handle semantics. Numeric-looking string keys must remain strings on a string-keyed hash. A dense integer-keyed hash is still a hash.

The release's generic boxed map admits results constructible in its box. My-try requires mixed → mixed and dynamic → dynamic. Release keyed adapters admit table-valued mixed; my-try's native policy puts mixed and dynamic into a separate boxed category. Release rejects non-table mixed and null dynamic even on an otherwise empty traversal. Defer boxed carriers, including legacy mixed-key table forms, until their result, key and invalid-input contracts are agreed. Arbitrary iterables, generator inputs, wrapper unwrapping and flavor-native names such as PHP array_map are outside this typed proposal.

Donor evidence is reproducible with `git show d2f63e37e:specs/builtins/collections.md`, the two revisions of `runtime/include/scpp/collections.hpp`, and `git show 624ab8a2d:compiler/my-try/tests/collection_diagnostics.py`. The latter includes named fixtures for captures, source changes during argument evaluation, callback order and empty results. The my-try catalog in `runtime/metadata/modules/core.json` has twelve callable IDs. A catalog row by itself does not prove a runnable source form.

## Probes on the current branch

All executable probes used the PHP-flavor compiler, Clang C++23/libc++ at -O2 and the existing runtime archive. They used source-level stand-ins with explicit signatures, so successful inference of the local holding the return value does not prove generic map result inference. No implementation or timings were introduced.

1. **Map a vector with a named callback.** A stand-in took `const vector<int>&` and `callback<string,int>`, looped with foreach and returned a declared `vector<string>`. Calling it with `label(...)` mapped [1,2] to ["one","two"]. Lowered C++ passed `&label_`, borrowed the input and emitted `out.append(op(value))` inside the iteration_next loop. LLVM IR for the separately callable helper contained an indirect callback invocation. In the same translation unit's optimized entry body, the helper loop was inlined and the known callback call disappeared; iteration_next remained. This observation applies to that build and does not establish a cross-unit performance result.
2. **Filter a sparse typed hash.** Inserted integer keys 19, 2 and 8 with values 1, 2 and 3. The stand-in passed only the value to the predicate and wrote accepted entries under the key supplied by paired traversal. Native execution verified the two original values and printed keys `19;8;`. This distinguishes retained insertion order from sorting or dense reindexing.
3. **Map and filter a fixed array.** The compiler rejected inline `fixed_array<int,3>` because leaf storage requires byte elements. A valid `fixed_array<byte,3>` then failed foreach admission. An indexed loop over that array successfully produced three strings and a zero-length `vector<int>` with an explicit byte-to-int conversion. Trying the donor's exact filter output `vector<byte>` failed as an unavailable specialization in the default publication. Thus indexed map is feasible for selected outputs, but a donor-compatible sequence family requires additional receiver and publication work. Do not widen bytes as an implicit workaround.
4. **Captured callback.** Replacing the named callback parameter with `function<string(int)>` and passing a local closure capturing a string also ran correctly. The stand-in lowered the parameter to a by-value std::function. Existing owned callables can express the behavior; adding an owned-callable conversion to every named callback would change its cost. The source contract already distinguishes borrowed capture-free callbacks and owned callable values.

[Active traversal contract](../../collection_traversal.md) · [Callable contracts](../../compiler_entry_v0_2.md) · [Fixed-array capabilities](../../../compiler/types/fixed_array.php) · [Collection specialization resolution](../../../compiler/types/resolution.php)

## Decision before implementation

**Recommend a synchronous, nonretaining borrow of the input for the duration of the helper.** The release specifies application discipline against mutation, including captured state. My-try demonstrates a callback mutating a captured object's state and separately expects the original vector contents when construction of the callback argument replaces an input element. The latter implies a value snapshot for that case. Active v0.2 foreach borrows a live wrapper, captures the iteration limit and promises no stable visitation when the input changes. Choosing snapshot behavior would be observable and could copy an entire input before traversal.

Proposed rule: evaluate both arguments once under v0.2 call rules, retain the selected input owner through the call, then traverse using the existing foreach contract. Permit ordinary callback effects and propagate exceptions. An exception destroys the partial output before it reaches the caller. An input or alias mutated by a callback inherits foreach's mutation rules. Avoid promising a particular trace when evaluating one argument can replace the other. Preserve the visited value for filter even if the predicate changes its local copy. A valid callback runs once per visited entry and zero times on empty input. Use the current callable invocation failure for an absent owned callable if an entry is visited. This last rule would permit empty input with an absent owned callable and needs an explicit acceptance test.

**Proposed integration.** Publish the six general names through metadata with project visibility as for vector_filled. Preparation should select the receiver policy, resolve the callback's signature and validate a synchronous exact by-value T parameter. Reject reference inputs/returns, defaulted or extra parameters, void map results, non-bool predicates and unavailable output specializations. Named references and explicitly typed lambdas supply U. An untyped map lambda whose return has no settled type needs either a contextual rule or a diagnostic; the typed stand-ins did not prove that inference. Reuse existing borrowed/owned callable representations and the value-only or keyed traversal and insertion bindings. Avoid six independent native algorithms or an unbounded prepublished input × output matrix. Lowering must request application-owned result providers even for a result used only as a temporary.

The first implementation proof should cover named, capture-free and captured callables, empty and throwing cases, type-changing map, sparse ordered filter, wrong adapter/receiver, mutable aliases, and a callback return-type edit with incremental recovery against fresh output. Verify compiled callers, include closure and emitted dispatch before choosing any Stage 2 optimization.
