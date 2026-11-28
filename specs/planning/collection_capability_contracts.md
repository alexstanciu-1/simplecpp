# Reusable collection capabilities and PHP array helpers

Doc Status: planning

Review baseline `cc4a00c7f`. This is a source and contract inventory. Native observations below come from the linked existing fixtures and PHP helper replay. The earlier C0–C7 work established the built-in collection surface. This proposal addresses how a consumer discovers the operations it needs and how another provider could fulfill them. [Prior completion plan](collection_capability_completion.md) · [PHP helper replay](helpers_and_libraries/php_array_string_inventory.md) · [Type knowledge debt](compiler_type_knowledge_debt.md)

## Existing building blocks

| Operation | Current contract and providers | What to reuse or clarify |
|---|---|---|
| Count and emptiness | `counted_collection_type` answers each cardinality query separately. Published and application vector/hash, boxed providers, fixed arrays and segments have cardinality support. Strings have their own generated cardinality behavior. | Keep per-operation admission, result and failure rules. Fixed arrays already know their extent and evaluate the receiver. The count rejection still names only vector/hash. |
| Traverse values or entries | `iterable_type` and the traversal bindings provide key/value types and copying behavior. Vectors use positions, typed hashes traverse live entries in insertion order, boxes classify keys at runtime. `key_iterable_type` can advance without copying the value. | Expose which observations a helper needs. Keep key-only fast paths optional. Specify ordering, single evaluation, retention, mutation during traversal and how long a selected entry remains valid. |
| Observe membership, presence or lookup | `membership_type`, `key_presence_type`, `key_lookup_type` and `shared_value_lookup_type` answer different questions. Python membership on a mapping tests keys. A missing key and a present null can be distinguished by key presence. | PHP value search needs element equality even on mappings. A single undifferentiated “contains” promise would select the wrong operation. |
| Read an index | `index_read_type` and `readonly_index_lookup_type` cover checked reads and the stronger repeatable lookup guarantee. Fixed arrays also offer checked indexing. `scoped_byte_read_type` adds a specialized byte-view binding. | Record key domain, value result, bounds/miss behavior and lifetime. Keep byte access as a provider specialization. |
| Store, update or append | `index_write_type`, `indexed_mutation_type`, `nested_index_storage_type` and `appendable_collection_type` distinguish keyed store, read/replace, saved-key traversal and append. Integer-key hashes can append. Fixed extent and string-key hashes illustrate why indexed write alone is insufficient. | Preserve missing-key rules, revalidation after user code, and copy-on-write versus shared identity. `writable_iteration_type` is a separate synchronous scoped write contract. |
| Build a result | `indexed_collection_type`, `sequence_initializer_type` and `keyed_initializer_type` cover empty, ordered and keyed construction. Concrete recipes bind component types. `native_sequence_type` and `destination_append_type` support specialized native construction/traversal. | Construction primitives exist. The helper still selects the output family and changed value type by hand. A helper needs an answer for “can I build this output with these keys and values?” |
| Publish receiver methods | Built-ins expose `has`, `remove` and `clear` across the four main families. The compiler generator walks vector/hash publication plans to generate method registrations. | Describe the source operation, receiver mutability, parameter/result contracts and native binding once. Distinguish public methods from compiler-internal traversal hooks. |
| Application-defined traversal | Metadata-defined `Iterator<T>` and `Iterator<T,K>` check user struct methods. A generator can also produce a single-pass iterator. | This is a precedent for explicit conformance. Traversal alone grants no count, restart, indexed access, output constructor or mutation capability. |

Evidence owners: [compiler capability interfaces](../../compiler/types/definition.php), [fixed arrays](../../compiler/types/fixed_array.php), [segments](../../compiler/types/segment.php), [generated registration and cardinality](../../tools/api_generator/php/render_compiler.php), [traversal and mutation rules](../collection_traversal.md), [count and emptiness](../count_empty_isset_contract.md), [application iterator binding](../../compiler/docs/iterator.md).

## Worked consumers

| PHP consumer | Vector | Typed hash | Mixed / dynamic | Simple C++ fixed array |
|---|---|---|---|---|
| Count, one argument | Published count | Published count | Table count observed in both | Implemented from logical extent |
| Single-input map with callback | Dense vector of callback results | Same key type, mapped value type | Same boxed family and original keys | Outside current transform admission |
| Filter with value callback | Integer-key hash preserving surviving original positions | Same key/value types and surviving keys | Same boxed family and surviving keys | Outside current transform admission |
| Filter without callback | Admitted for supported scalar condition | Same element requirement | Rejected in current slice | Outside current transform admission |
| Value search with `in_array` | Exact comparable scalar | Searches values with exact scalar equality | Outside current typed slice | Outside current membership helper admission |
| In-place key-preserving sort with `uasort` | Positional keys cannot represent arbitrary reordered key associations | Plausible provider, needs ordering contract and binding | Plausible for table kinds, with separate ownership rules | Positional keys have the same obstacle as vector |

The first five rows describe current behavior. The sort row is a design exercise. Published specializations and applicable concrete recipes still govern what can be instantiated. Fixed arrays here refer to the existing inline byte-array model, including nested dimensions. Future array types would supply their own contracts. [Transform preparation and value-search admission](../../compiler/04_analyze/prepare/prepare.php) · [Regression driver](../../compiler/tests/collection_transforms.py) · [PHP helper replay](helpers_and_libraries/php_array_string_inventory.md)

Three observations determine the migration order:

1. **Count is the control.** Ordinary preparation already asks the receiver for its cardinality result. Generated providers bind size and emptiness operations. The immediate debt is the concrete-family diagnostic and the division between semantic promises and native emitter methods.
2. **Search and transforms expose the remaining family switches.** In `collection_membership`, the visitor explicitly admits vector/hash before checking the element and equality. In `collection_transform`, it switches among vector, hash and box, constrains the callback, then chooses vector/hash results explicitly. These are distinct decisions. Filtering positions 0 and 2 must preserve key 2 even when only that entry survives. Mixed results retain value semantics; dynamic children can retain shared identity. Replacing the family switch with a single iterable check loses those guarantees. [Preparation](../../compiler/04_analyze/prepare/prepare.php) · [PHP array contracts](../collection_traversal.md#php-array-helpers)
3. **Sorting needs a cross-operation guarantee.** Starting from keys a, b, c with values 20, 10, 10, sorting ascending must visit b, c, a. Every key must still retrieve its original value; equal values keep their previous relative order. PHP mutates the supplied array, compares two values using the sign of the callback result and returns true. PHP also specifies cursor reset. Whether Simple C++ exposes an analogous cursor is a surface decision. Today's unmodified typed hash traverses in insertion order, so approving a reordered hash requires defining subsequent lookup, replacement, insertion, erase/reinsert and traversal. [PHP uasort](https://www.php.net/manual/en/function.uasort.php) · [Current traversal contract](../collection_traversal.md)

## Proposed contract boundary

A reusable definition names an operation, its associated types (key, element, output as needed), receiver access, argument and result rules, observable effects, failures and lifetime. A provider binds that definition to its implementation. A binding may use a method, operator, constant extent, or generated primitive. Public visibility is a separate choice. A helper publishes its requirements, callback and result policy, applicable flavor and allowed forms of runtime checking. Generate direct dispatch as today. Preparation resolves and validates once; lowering consumes the selected facts. [Generated lowering direction](../../compiler/docs/generated_lowering.md)

Groups should compose smaller definitions and state any additional laws. For instance, a **key-preserving transform** needs entry traversal and an output provider that accepts the observed keys and selected result element type. A **key-preserving reorder** additionally promises that changing traversal order preserves key associations on the same writable receiver. A provider could supply a whole reorder operation without exposing a mutable iterator or a raw slot. These are proposed contract descriptions; names, metadata schema and source syntax remain open.

For each consumer/provider pair record one of three outcomes: statically supported, supported with an operation-specific runtime kind check, or unsupported. In particular, a boxed type may support a PHP helper for tables while a scalar instance fails. Each binding must preserve the helper's existing failure and alias behavior. A runtime check for one operation grants no permission to another.

## Shared runtime helper interface

Use the same capability definitions for compiler admission and runtime helper implementation. Author each helper algorithm once against the operations it consumes. Each collection provider binds its storage-specific primitives once, for reuse by every eligible helper. Generated concrete entry points may forward to or specialize the common algorithm. The contract concerns one maintained implementation of the behavior; the choice of native calling convention and number of compiled instantiations can follow the existing publication and performance requirements.

| Helper | Common implementation | Provider responsibility |
|---|---|---|
| `count` | Evaluate the receiver once and return its cardinality. A direct call or a known extent can implement the entire operation. | Supply the count primitive and any required runtime kind check. |
| `in_array` | Traverse values, apply the prepared equality operation and stop at the first match. Vector and hash callers use the same search algorithm. | Supply value traversal with the promised lifetime. The element contract supplies equality. |
| `array_map` / `array_filter` | Apply the callback and publish selected entries through the prepared result builder. Each helper owns its callback and key-preservation rules once. | Supply traversal and output construction, including sparse keys and the applicable copy or sharing behavior. |

**Pilot acceptance test.** Bind cardinality and value traversal for a third test provider with comparable scalar elements. Run the existing count and search algorithms on it. The only provider-specific additions should be capability bindings, publication and conformance tests. Review both the runtime sources and generator templates to verify that adding the provider required no new helper body, family switch or handwritten helper overload. Apply the same test to a future user-defined provider.

## Count and value-search pilot

The pilot uses the existing cardinality query and a borrowed value cursor. Generated collection bindings use their published limit and next-value operations. Fixed arrays bind the cursor to their logical extent. A single runtime `any_value` loop consumes either cursor and the prepared element equality. Boxed cursors remain subject to the helper’s exact scalar equality rule. [Third-provider regression](../../compiler/tests/collection_value_scan.py) · [Runtime algorithm](../../tools/api_generator/templates/collections/value_scan.cpp.tpl)

## PHP key-preserving filter

PHP `array_filter` uses the source's associated result builder and one runtime entry loop.
The native regression covers sparse positional results from vectors and fixed byte arrays,
typed hash keys, mixed value isolation, dynamic child identity, and a callback replacing
the owner of an indexed row. [Regression](../../compiler/tests/collection_subset.py) · [Runtime loop](../../tools/api_generator/templates/collections/filter_entries.cpp.tpl)

## Delivery and review gates

| Stage | Proposed work | Acceptance evidence |
|---|---|---|
| 1. Cardinality and publication | Trace one built-in provider from metadata and publication through preparation to native emission, plus the handwritten fixed-extent provider. Define the smallest shared contract and capability-based diagnostic. | Existing accepted and rejected inputs retain behavior. A test provider can supply cardinality without adding a branch in the helper. Generated bindings remain direct. |
| 2. Value search | Express the existing scalar `in_array` slice in terms of value traversal, associated element type and published equality. Keep its present optional strict-flag contract. | Hash with a needle present only as a key returns false. Check once-only evaluation and early exit, unsupported equality and currently excluded boxes/arrays. A fixed array is an opt-in extension after traversal and element-equality contracts are satisfied. |
| 3. Map and filter | Define result construction and key/ownership rules. Migrate single-input map and both existing filter forms. Separate callback requirements from provider capabilities. | Exercise sparse integer keys, string keys, empty input, changed result element type, missing output specialization, callback failure, mixed copy isolation and dynamic child identity. Compare fresh and resident compiles after a binding or signature edit. |
| 4. Sort design, then separate approval | Specify key-preserving reorder and which providers can honor it. Decide stable tie handling, writable destinations, comparator mutation/re-entry, exception effects and alias visibility before publishing a helper. | Walk the b,c,a example through typed hash, mixed alias and dynamic alias. Specify what an observer sees after a throwing comparator. Show why a positional vector/array rejects. Reuse the same requirement for a second candidate such as `asort` before finalizing the group. |
| 5. Later user-defined providers | Build on explicit Iterator-style conformance. Bind user methods to the same source-level contracts, with signature, associated-type and dependency checking. Define conformance fixtures for behavioral guarantees the compiler cannot prove. | A custom counted collection works with unchanged `count`; an iterator-only type still rejects count; a custom keyed provider participates only in the groups it fulfills. Verify invalidation after removing and restoring a required operation. |

**Review requested.** Start with count and typed value search as behavior-preserving pilots. Review output construction before migrating transforms. Treat key-preserving reorder as a separate decision about observable storage behavior. The later extension point is successful if publishing a conforming type requires a provider binding and tests, with no edits to the generic consumers.
