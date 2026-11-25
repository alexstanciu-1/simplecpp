# Discarded indexed increment/decrement

Doc Status: supporting

Published vectors/hashes accept standalone prefix and postfix updates when their element
type's generated supports_update/lower_cpp_update rules admit the operation. Current
published int64 elements are supported; string elements are rejected. No operator matrix,
synthetic +1 expression or extra conversion rule is introduced.

```php
$values[0]++;
--$counts["items"];
factory()->items[key()]++;
```

Targets reuse ast_index_expression with a general expression receiver. Locals, reference
parameters, fields, function-returned owners and temporary collections follow the same
path. Receiver and key are evaluated once. Shared field owners remain alive if the key
expression replaces the caller's owner. The current element is read after key evaluation;
its copied value receives the generated numeric update, then the existing setter stores
it. There is no RHS and no user callback between read and store, so no extra post-RHS key
revalidation is needed. Missing keys/bounds and empty owners fail without insertion.
Private access checks and existing scalar boundary rules remain unchanged.

Value-producing updates, indexed for-header updates, append updates, string byte updates,
boxed collections and unpublished element specializations remain deferred. Results are
discarded, so prefix and postfix have the same externally visible update behavior here.

Structures: ast_index_update extends ast_update_statement and owns one indexed target.
It reuses the existing update operator/postfix fields and adds no prepared facts. The
former compound_collection_type is now indexed_mutation_type because both forms use its
read/replacement contract. Cpp_Emitter::indexed_place owns receiver/key stabilization for
both forms. No runtime ABI, generated type handlers, symbol/watch or queue changes.
Preparation traverses the target once, then calls the canonical generated update admission.

Verification: indexed_updates.php and indexed_updates_native.py cover all three flavors,
all three published integer collection specializations, both prefix/postfix directions,
exact evaluation counts, method/field updates, shared identity, key-side owner replacement,
reference write-back, copy independence, safe min/max boundary steps, invalid targets/types,
private access, missing/negative indexes and empty owners. Cross-file key function edits,
incompatible return type, deletion, retry without edits and recovery are compared with fresh
compilations and executed. Caller AST reuse, no-op preparation, syntax/prepared inspection
and lowering purity are checked.

## Verification snapshot

2026-11-14: 57 indexed-update native fixtures passed; nine focused PHP regressions passed
(indexed compounds, fields, collection references/value passing, ordinary updates,
integer/float/mixed compounds and reference parameters). Generator freshness, style and
whitespace checks passed. Native ordinary-update and indexed-compound regressions also passed.
Rejected empty-index updates use the parser cursor source for diagnostics, before AST attachment.

Median milliseconds across three fresh single-unit fixtures per flavor:

| Flavor | PHP fixture compiler | Native wall | Clang frontend | LLVM backend | Link command | Compiler + native |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| php | 4.15 | 232.15 | 30.02 | 49.77 | 60.00 | 235.94 |
| cpp | 4.47 | 268.60 | 34.26 | 61.54 | 62.00 | 272.61 |
| js | 4.39 | 310.87 | 38.88 | 67.49 | 79.00 | 314.68 |

O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime, fresh app objects with Clang
traces. PHP fixture compiler includes frontend/preparation/emission and inspection/purity
checks, excluding process startup and fixture setup. Compiler + native sums measured stages;
it is not separate CLI wall time. Native wall includes direct-Ninja qualification overhead.
Trace components do not sum to wall time; these are not controlled flavor comparisons.
Raw artifacts: `/tmp/scpp-indexed-updates-proof/`.
