# Common hash-key contract and shared owners

Doc Status: supporting

The [v0.2 key contract](../../specs/array_semantics.md#shared-owner-hash-keys)
owns source semantics. All three flavors support shared-owner hash keys:

```php
$table hash<int,shared<Point>> = [];
$p = new Point();
$q = $p;
$table[$p] = 7;
echo $table[$q]; // 7: same allocation

$empty shared<Point>;
$table[$empty] = 9; // one distinct empty-owner key
```

Keys are strong values. Copies of the same owner replace/find one entry; separate
allocations remain distinct even with identical fields. Field mutations do not affect
identity. Removing a key, clearing/destroying a table, or replacing an owned table
releases its stored owners. Copying a table retains independent entries with the same
shared object identities. A retained key prevents allocation-address reuse while stored.

Class/base/interface views compare and hash the control pointer, not the adjusted view
pointer. Key expressions must have the exact declared key type; perform an existing
checked/implicit typed-destination conversion into that type before using the key.
An empty required class value remains invalid under the existing class contract; explicit
nullable owners and shared struct owners can supply the empty key. Weak observers,
inline records, collections and additional scalar key families are not newly hashable.

## One generated path

- Runtime metadata `hash.key_contracts` declares the existing int64/string hash and
  equality bodies, with nonthrowing=true. The validated plan retains these contracts.
- `ConcreteHashKey` is the generator-facing capability, alongside `ConcreteType`.
  `RuntimeConcreteType` supplies metadata bodies; `cpp_concrete_type` delegates to the
  compiler type's lowering methods. Recipes do not inspect ASTs or switch on key families.
- The hash recipe declares its key parameter. The concrete engine obtains hash/equality
  expressions from that type, rejects unsupported providers or caller overrides, and
  preserves its existing native storage assertions. Native declarations remain template-free.
- Generated int64/string definitions expose the same capability. Shared owners provide
  `hash_pointer(owner.ptr)` and control-pointer equality. The runtime helper mixes a
  uintptr_t conversion without dereferencing the allocation.
- One generated application hash handles arbitrary approved key/value pairs. The existing
  integer append variant remains separate because append is not a general key capability.
  Existing published runtime hashes, strict input fences and their optional lookup remain.

For example, the compiler's shared-key lowering is:

```php
public function lower_cpp_hash_key(string $key): ?string
{
    return '::scpp::detail::hash_pointer((' . $key . ').ptr)';
}
```

No new source function, operator, cast, AST node or per-access preparation fact is added.
Value admission uses the existing concrete storage contract, independent of hashing.
This includes existing records, strings, integers and nested collections. This slice does
not activate every optional scalar/carrier specialization or application `hash_find` carriers.

## Canonicalization and incremental ownership

Published specializations retain the direct registry fast path. Other hashes are cached
by their canonical key and value identities. A lazy WeakMap on the key type uses value
identity, with weak references to resulting specializations. It therefore neither aliases
same-named replacement generations nor pins their ASTs. Live AST/type users own the
specialization. Native intent is a non-owning canonical pair index, not a requirement to
reproduce PHP WeakMap internals.

Preparation resolves and checks types, then reuses existing read/write/call paths. No
runtime metadata parsing, new traversal pass, per-element analysis or dependency index
was added. Existing signature watches include key/value references; keys changing or
being deleted invalidate the usual callers. Canonicalization remains behind a method
for future synchronization at the existing native phase gates.

## Verification

```sh
XDEBUG_MODE=off php compiler/tests/hash_key_contract.php
XDEBUG_MODE=off python3 -B compiler/tests/shared_hash_keys_native.py --output /tmp/shared-key-proof --sanitize
```

All three flavors pass identity/empty-key lookup, object mutation, copying, growth,
removal holes, key/value traversal, scope destruction and exact-key rejection. Weak
observers prove that the table retains a key and that the last table/owner release
expires it. Cross-file signatures and base/interface conversion paths are covered.
Integer/string/record/nested-vector values share the recipe. Thirty incremental revisions
compare diagnostics or emitted artifacts with fresh compilation: key deletion/re-addition,
failed retry without edits, value-layout replacement while the key survives, and signature
mismatch/recovery. Native executables verify successful output.

The PHP retirement proof keeps the key alive while replacing/dropping value declarations,
including an int singleton key. Old declarations and hashes are collected; live pairs
remain canonical. Nested-collection regression: 36 checkpoints across all three flavors.

ASan/UBSan and leak checks passed for generated shared-key storage in all flavors. The
runtime archive is prebuilt and uninstrumented. LeakSanitizer runs outside the sandbox's
ptrace restriction. Runtime hash consumer/storage/freshness/header tests and the general
concrete recipe tests passed; the latter cover Clang and GCC and allocation/copy failures.
Generator checks reject missing, incomplete and throwing metadata contracts, unsupported
key providers and attempts to override the type-owned key operations.

Deferred: weak keys, source-defined hash/equality methods, new key coercions, cycle
collection and cross-thread ownership. Strong keys can participate in existing shared
cycles; no cycle collector is introduced. Self-referential concrete fields remain deferred.

## Recorded timings — 2026-11-19

Separate sequential initial builds from `/tmp/scpp-shared-hash-verified`, one observation
per flavor, milliseconds:

| Flavor | PHP compiler | Native wall | Clang frontend | LLVM backend | Link | End-to-end |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| PHP | 15.60 | 768.27 | 223.14 | 534.79 | 69 | 875.79 |
| C++ | 16.46 | 724.01 | 222.57 | 509.77 | 60 | 837.05 |
| JS | 16.27 | 781.25 | 210.61 | 543.92 | 71 | 887.04 |

O2/g0, Ninja8, mold, no PCH/LTO, prebuilt runtime; direct Ninja qualification.
Clang frontend/backend values sum parallel translation-unit work, not additive wall
components. End-to-end includes starting a fresh compiler service, compilation/emission,
build setup, native build and checking the executable. Raw sequential measurements are
in `timings-sequential.json`; incremental rows and native traces remain alongside it.
