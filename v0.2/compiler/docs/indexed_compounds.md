# Indexed vector/hash compounds

Doc Status: supporting

Existing published vector/hash specializations accept compound assignment using their
element type's existing generated operator rules. PHP string concatenation uses `.=`;
C++/JS use `+=`. Integer arithmetic, bitwise/shift and power compounds retain their
existing admission and failure rules. No extra operator/conversion matrix is introduced.

```php
$values[0] += 2;
$counts["items"] += 1;
factory()->labels[key()] .= "!";
```

The indexed target owns a general receiver expression and key. This includes a field
selected from a function-returned owner, nested struct fields, ordinary local/parameter
collections and temporary returned collections (whose mutation is discarded). Private
field access still follows ordinary expression resolution. String byte indexing and boxed
container compounds and nested indexed mutation paths remain outside
this slice; published nested collection specializations are not available.

Follow the existing-element contract in ../../../specs/array_semantics.md: evaluate the
receiver/key once, retain any shared field owner, read the old element by value, compute
with the RHS using existing compound rules, validate that the key still exists, then write.
Missing/bounds failures happen before RHS evaluation. RHS replacement leaving a valid key
receives the computed result; replacement with an empty collection fails without insertion.
Earlier RHS effects remain on failure. No element pointer survives RHS execution, so vector
growth and hash storage replacement are safe. A separate final read currently validates
existence using the existing generated read binding, avoiding a new runtime ABI.

Structures: ast_index_compound extends the existing compound statement and owns one
ast_index_expression target. Its receiver remains ast_expression. Existing compound
conversion/execution/write-back facts are reused. indexed_mutation_type exposes a
prepared-value replacement binding; vector/hash generator templates use the same setter
binding as ordinary assignment. Shared compound_result emission handles local, property
and indexed operation conversions. No symbol/observer/queue or runtime structures change.
Preparation visits receiver, key and RHS once; only canonical type checks and the existing
generated element handler run. No collection contents or metadata are scanned.

Tests indexed_compounds.php and indexed_compounds_native.py cover all three flavors and
six published specializations: integer compound groups, byte-preserving string concat,
function receiver/key evaluation counts, reference parameters, owner replacement including
the sole owner, RHS vector growth/hash replacement, missing/empty-owner/checked failures,
RHS key removal, private access, invalid operands and fresh/incremental equivalence.
Incremental layout changes, incompatible fields, deletion, retry and recovery preserve
unchanged caller ASTs and no-op preparation. Syntax/prepared inspection and lowering purity
are exercised as well. Source evaluation rules are unchanged.

## Verification snapshot

2026-11-14: 144 indexed native fixtures passed; nine focused PHP regressions passed:
ordinary/integer/float/mixed compounds, collection fields/references/value passing, and
vector/hash operations. Native integer and float compound regression suites also passed. Compiler generation
freshness, style and whitespace checks passed.

Median milliseconds across six fresh single-unit fixtures per flavor:

| Flavor | PHP compiler | Native wall | Clang frontend | LLVM backend | Link command | Compiler + native |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| php | 3.02 | 229.13 | 29.51 | 47.82 | 54.50 | 231.88 |
| cpp | 2.76 | 235.60 | 30.81 | 49.99 | 57.50 | 238.41 |
| js | 3.10 | 231.40 | 29.13 | 48.84 | 56.00 | 234.06 |

O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime, fresh app objects with Clang
traces. PHP compiler measures frontend/preparation/emission. Compiler + native sums those
measured stages, excluding PHP startup and fixture setup; it is not separate CLI wall time.
Native wall includes direct-Ninja qualification overhead. Trace components do not sum to
wall time. Fixture medians are not controlled flavor-performance comparisons. Raw sources,
outputs, logs and traces: `/tmp/scpp-indexed-compounds-proof/`.

Discarded-result indexed increment/decrement reuses the target path; see [indexed updates](indexed_updates.md).
