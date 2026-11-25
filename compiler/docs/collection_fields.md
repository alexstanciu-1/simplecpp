# Vector and hash instance fields

Doc Status: supporting

Published vector/hash specializations may be instance fields in any flavor. Omitted
initializers mean an empty collection, including nested inline records and shared record
construction. The existing emitter uses native member `{}` initialization; runtime default
constructors and RAII own storage. Explicit `= []`, populated field defaults, static
collection properties and new element specializations remain deferred.

```php
struct Record {
    public vector<int> $values;
    public hash<int,string> $counts;
}
$a Record;
$a->values[] = 8;
$a->counts["items"] = 1;
$b = $a;
$b->values[0] = 9; // a still contains 8
```

Inline record copying copies collection values into independent container storage. Shared
record owner copying preserves identity. Fields can be copied into locals, passed/returned
by value and replaced by the same canonical collection type. Self-assignment uses existing
runtime assignment. Private access and exact-type conversion rules are unchanged.

Mutation reuses keyed writes, vector append, element conversions and bounds/missing-key
checks. It works through `this`, nested inline records and shared receivers. A shared
receiver is retained using the existing `retained_place` path for indexed writes and now
for append, so a key/RHS call may replace the original owner without destroying the
selected field mid-operation. No raw element reference escapes. Property reference
arguments remain rejected; only named locals/parameters bind writable references.

The only AST type adjustment widens ast_append_statement.receiver from a name to a
name-or-field, including its constructor. No new fields, prepared facts, queues or runtime
ABI changes. Canonical family definitions admit instance storage; declaration preparation
resolves each field once and body preparation reuses existing conversion/type checks.
Preparation never traverses collection elements. Application C++ remains template-free.

Tests: collection_fields.php and collection_fields_native.py cover the six published
specializations in three flavors, empty defaults, nested records, this writes, independent
inline/local copies, whole-field replacement, self-assignment, shared identity, retained
receivers during replacement (including release of the sole original owner), rejected
private writes/reference arguments, empty-owner failures and missing/bounds failures.
Cross-file layout edits, incompatible field type, deletion, retry without edits and
recovery are compared with fresh output and executed. Caller AST reuse, no-op preparation
and lowering purity are checked.

## Verification snapshot

2026-11-14: 108 native fixtures passed. Six focused PHP regression suites passed:
collection references, common value passing, vector/hash operations, enum fields and
reference parameters. Writing and whitespace checks passed.

Median milliseconds across six fresh single-unit fixtures per flavor:

| Flavor | PHP compiler | Native wall | Clang frontend | LLVM backend | Link command | Compiler + native |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| php | 2.49 | 213.50 | 27.68 | 34.84 | 54.50 | 215.80 |
| cpp | 2.52 | 244.18 | 28.38 | 38.87 | 64.00 | 246.60 |
| js | 2.57 | 232.07 | 30.88 | 38.92 | 56.00 | 235.12 |

O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime, fresh application objects,
Clang time traces. PHP compiler measures frontend/preparation/emission, excluding process
startup and fixture setup. Compiler + native is the sum of measured stages, not a separate
CLI wall measurement. Native wall includes direct-Ninja qualification overhead; trace
components do not sum to wall time. These are fixture medians, not controlled comparisons
between flavors. Raw artifacts and logs: `/tmp/scpp-collection-fields-proof/`.
