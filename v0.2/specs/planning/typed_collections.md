# Typed collections before iteration

Doc Status: planning

Baseline: v0.2/develop-php at `e40949352`

The [feature catalog](non_object_feature_catalog.md) tracks the collection work as CT-01 through CT-04. Start with CT-01 on the six default published specializations: vectors of int or string and all four combinations of int/string for `hash<Value,Key>`. Typed empty locals, vector append, hash keyed writes and copied indexed reads are the starting point. The [vector](../../compiler/docs/vector_operations.md) and [hash](../../compiler/docs/hash_operations.md) compiler contracts describe these existing operations.

## CT-01 source operations

Examples use cpp-flavor local names. PHP uses its usual `$` variable prefix. The same operation and result contracts should apply across cpp, php and js. Count, empty and vector indexed replacement are implemented. The remaining rows are proposed source bindings.

| Proposed source spelling | Published runtime operation | Source behavior |
| --- | --- | --- |
| `count(c)` | `size()` | Return the logical length as source int for either collection. |
| `empty(c)` | `empty()` | Return bool. Only zero length is empty, irrespective of the values stored. |
| `v[i] = x;` | vector `set(i, x)` | Replace an existing index. Use the existing int index rule and typed element conversion. An out-of-bounds index fails. |
| `isset(h[k])` | hash `has(k)` | A presence probe for the current non-nullable int/string values. A missing key yields false without insertion. Revisit null sensitivity when adding nullable payloads. |
| `hash_find(h, k)` | hash `find(k)` | Proposed helper returning the published `nullable<Value>`. Absence yields an empty carrier. Presence yields an owned copy, including zero or an empty string. Existing `h[k]` still throws on a miss. |
| `unset(h[k]);` | hash `remove(k)` | Remove the key if present. The source statement discards the native removal flag. |
| `h[] = x;` | int-key hash `append(x)` | Append a converted value under the next integer key. The source statement discards the assigned key. String-key hashes reject append. |
| `hash_clear(h);` | hash `clear()` | Proposed mutating helper. The hash becomes empty and reusable. The next int-key append uses zero. |
| `vector_clear(v);` | vector clear, subject to the capacity contract below | Destroy the elements, retain capacity and allow reuse. |

The strict vector helper spec requires capacity retention. The current v0.2 vector `clear()` deletes its storage. Before enabling this source binding, provide a runtime operation that satisfies the source contract and verify it with a capacity or allocation probe. The other operations can proceed independently. [Strict vector helpers](../../../specs/builtins/vector_helpers.md) · [Current implementation](../../tools/api_generator/templates/vector_methods_definition.cpp.tpl)

For hash append, choose zero when there is no live nonnegative key, otherwise one more than the largest live nonnegative key. Removing the largest allows that key to be reused. Overflow fails before mutation. All keyed operations preserve the declared key type and complete string bytes. [Hash contract](../hash_slice.md) · [Array semantics](../../../specs/array_semantics.md) · [Count, empty and isset](../../../specs/count_empty_isset_contract.md)

## Compiler and runtime ownership

Use the validated runtime specialization and member plans to generate operation bindings on concrete collection types. Source preparation checks the receiver, exact key or index type, writable local for mutations, typed destination conversion and result type. Lower through the existing collection syntax nodes where applicable. In particular, keyed probes and removal must access the receiver and key directly: evaluating the throwing indexed read first would give the wrong behavior on absence. Preserve single evaluation. Existing hash writes retain key-before-RHS ordering. Vector replacement uses native argument ordering. Record provider requirements through the generated include queries. Coordinate edits to shared keyed syntax with the boxed workspace.

## CT-01 verification checklist

- [x] Exercise count and empty on all six default specializations across cpp/php/js. Check empty and populated cardinality, a stored zero or empty string, vector growth and hash replacement at constant size.
- [x] Verify vector replacement at the first and last valid indices. Reject negative and past-end indices, including index zero on an empty vector. Check single evaluation and destination contents after bounds or conversion failure.
- [ ] Compare hash probe, find and indexed read on the same missing key. Check count after each operation. Verify present zero/empty-string values, copied find payloads, embedded NUL keys and distinct numeric-looking string keys.
- [ ] Verify removal of present and absent keys, strict key types, writable receivers and conversion failures. Check integer append on empty, negative-only and gapped keys, after removal of the maximum and after clear. Exercise overflow with the prior membership and values intact.
- [ ] Verify vector clear's capacity contract before admitting the binding. Exercise repeated clear and reuse for both families.
- [ ] Check generated bindings and per-unit headers against the selected publication. Reject unavailable specializations and unsupported receiver/key combinations during preparation. Compare incremental edits, failure and recovery with fresh emission and native execution.

## Cardinality slice verification

`count(c)` returns source int and `empty(c)` returns source bool. The generator
validates the published zero-argument read-only members and their result identities
before emitting each specialization’s handlers. Preparation records the selected
observation on the existing call expression. Lowering evaluates the receiver once
and emits its member call using the existing per-unit header collector.

Ordinary function declarations retain precedence. The compiler watches the resolved
symbol before binding a builtin, so adding or removing a declaration invalidates the
retained caller. Direct helper spellings work inside namespaces. A plain or
function import reserves its local spelling for declaration lookup. An imported
function aliased as `count` wins, and a plain import that resolves only to a
constant cannot supply a callable alias. PHP’s keyword spelling of `empty` is accepted
in call position.

- [x] Verify both return identities, emitted member names, and provider headers in
  generated specialization tests, including provider relocation and retirement.
- [x] Execute native cardinality fixtures for all three flavors. Count an empty
  container, append or insert a zero/empty string, and distinguish vector growth
  from replacement of a live hash key.
- [x] Reject wrong arity and scalar/string receivers. Exercise ordinary declared
  calls, namespace-local use, repeated preparation failure, deletion and recovery
  with the retained caller; compare recovered emission with fresh compilation.
- [x] Reject both `use count as n; n(v)` and `use LIMIT as count; count(v)` when
  the imported target is only a constant. Execute a declared function imported
  as `count` across cpp/php/js.
- [x] Relocate the vector and hash providers in the same publication. In each
  flavor, lower separate source files that call both helpers. Check their direct
  includes with the original headers absent, then compile, link and execute the
  emitted application against that runtime.

The native build and both provider-export regressions passed. The hash operations
native harness executed 51 fixtures, including the all-specialization cardinality,
namespace, declared-function, imported-function and incremental cases. The compiler
style check passed for 169 files. The earlier full-suite run and separate
socket-enabled frontend-service run passed 195 of 197 tests. The two failures,
`checked_conversion_generation` and `numeric_contract`, also reproduce on a
clean export of baseline `e40949352`: their standalone test setup calls
`prepareBoxed()` without loading `boxedScalarPlan()`.

## Indexed vector replacement

Published vector types now implement the keyed-store contract used by
`ast_index_assignment`. Reads and writes share the exact count-type index check.
The generator binds the published mutable `set(count, element)` signature.
Preparation selects the element conversion. Lowering emits a direct call to `set`, which checks bounds. See the
[native lowering plan and inventory](native_cpp_lowering.md) for exact argument policy.

The cpp/php/js native fixtures replace the first and last elements of int and
string vectors, including an embedded NUL string copied from another slot.
Effectful operands verify single evaluation. Their relative order is deferred. Test-only C++ catchers inspect the
original count and elements after empty, negative, past-end and overflowing
conversion cases. When both conversion and bounds would fail, conversion fails
first. Incremental signature and element-type edits exercise rejected
conversions, retries and recovery against fresh output. The relocated-provider
application also replaces and reads a vector slot. All 12 focused CTests passed,
including the native vector fixtures and the hash and boxed regression suites.
The native build and compiler style check passed.

## Sequencing and ownership of later work

1. **CT-02** covers same-type initialization and assignment, by-value parameters and returns, then whole-collection reference parameters. Prove independent copies and caller-visible reference mutation.
2. **CT-03** starts with flat, explicitly typed populated literals. Add nesting and further published specializations once payload copy, conversion and publication requirements are defined.
3. **CT-04** is a separate foreach discussion after the other collection work. Traversal order and mutation during traversal belong to that contract. CT-01 verification observes values and membership by explicit index or key.

Mixed/dynamic compiler and container work continues in `simple_cpp_02`. Hash probe, find, removal and append, and both clear operations remain deferred.
