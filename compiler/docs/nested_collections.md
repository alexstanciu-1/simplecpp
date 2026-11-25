# Recursive concrete collection values

Doc Status: supporting

Vector/hash values can recursively contain supported vector/hash types. All three
flavors use the existing type-reference tree and typed empty `[]` construction:

```php
$rows vector<vector<vector<int>>> = [];
$tables hash<hash<hash<int,string>,int>,string> = [];
$groups vector<hash<vector<Point>,string>> = [];
```

This is recursive specialization, not a fixed-depth feature. Hash argument order
remains `hash<Value,Key>`. Int/int64, string and [shared-owner keys](hash_keys.md)
use the common hashing/equality contract. Other key families remain separate. Other unpublished scalar or
carrier payload specializations are not activated by this change.

## Ownership and preparation

The existing record-owned specialization cache is now the shared
`collection_specializations` trait, also used by vector/hash identities. An element
owns canonical outer specializations keyed by family and key type; each outer type
retains its existing non-owning element/key links. No process-global application-type
cache retains retired declarations. Runtime-only nested types can share their immutable
canonical identities across sessions, just like existing runtime specializations.

`Type_Resolution` resolves arguments recursively and queries the element's collection
capability. Generated registry methods still select the vector/hash recipe and permitted
key types. There is no new scalar matrix or metadata scan in preparation. Canonical
alias spellings reuse the same identity; cached lookups allocate no new type definition.

The common `concrete_value_storage` trait exposes the existing copy/noexcept move and
destruction contract to the template-to-concrete adapter. The adapter delegates instead
of switching on record/vector/hash types. Recipe-generated native assertions still check
these requirements. This does not automatically make every storage-capable type a key.

Record layout preparation follows nested collection arguments to their nominal records
without retaining a flattened dependency list. Existing symbol watches track the written
leaf types. Self-referential collection fields remain diagnosed, including through multiple
collection layers; this is distinct from finite nested specialization.

No AST fields, occurrence facts, preparation passes or cleanup owners were added. Vector
and hash canonical types gain the same small specialization index already owned by records.

## Lowering and copies

Private concrete demand recursively requests dependencies before their users, deduplicates
them, and emits explicit child-header includes. Field publication follows the same dependency
order. Existing vector/hash recipes generate concrete classes; application C++ introduces
no template instantiations for nested collections.

All existing by-value calls, returns, empty fields, traversal and mutations reuse their
usual handlers. Indexed reads return values. To modify an extracted nested value and update
its parent, explicitly write it back:

```php
$row = $rows[0];
$inner = $row[0];
$inner[0] = 9;
$row[0] = $inner;
$rows[0] = $row;
```

This slice does not introduce reference traversal, autovivification or new semantics for
chained indexed write targets. Ordinary value copies stay independent; shared objects
inside copied values retain their existing shared identity.

## Verification

```sh
XDEBUG_MODE=off python3 -B compiler/tests/nested_collections_native.py --output /tmp/nested-proof --sanitize
```

The native fixture covers three collection layers: vector/vector/vector, hash/hash/hash,
vector/hash/vector and hash/vector/hash, each with integer and application-record leaves.
It verifies copied values, explicit nested mutation/write-back, field storage, cross-file
parameters/returns, clear and value traversal. Each of 12 revisions per flavor is compared
with a fresh compilation, including record edits/deletion, retry without edits, recovery,
recursive-field rejection and nested return-type mismatch/recovery. Successful emitted
artifacts are byte-identical to fresh output and execute with expected results. No-op
preparation is zero bodies. The source-syntax tests verify canonical alias identities.

Optional ASan/UBSan instrumentation covers generated nested application storage and leak
checking; all three flavors passed. The linked prebuilt runtime is not itself
instrumented in this proof. LeakSanitizer required execution outside the sandbox
because its ptrace restriction prevents leak checking.

Existing vector/hash type/operation tests, collection field/reference/has tests and the
all-flavor collection bootstrap native proof pass. The hash operation suite's stale
integer-key append rejection now checks unsupported string-key append instead.

Initial-build observations from `/tmp/scpp-nested-final` (ms, one sample per flavor):

| Flavor | PHP compiler | Native wall | Clang frontend | LLVM backend | Link | PHP + native |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| PHP | 38.22 | 2024.60 | 847.29 | 2403.30 | 78 | 2062.82 |
| C++ | 36.61 | 1867.63 | 801.59 | 2143.52 | 64 | 1904.24 |
| JS | 36.95 | 2035.11 | 753.01 | 2560.59 | 75 | 2072.06 |

O2/g0, Ninja8, mold, no PCH/LTO, prebuilt runtime, direct Ninja qualification.
Frontend/backend totals sum parallel translation-unit work and are not additive to native
wall. PHP + native is staged work, not test-harness wall time. The fixture deliberately
instantiates many concrete storage types; these are not comparisons with a small scalar
program. No-op PHP runs measured 6.08–7.30 ms; leaf-record edits 17.85–20.23 ms.
