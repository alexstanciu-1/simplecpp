# Empty hashes and keyed operations

Doc Status: supporting

The first source-value slice supports explicitly typed empty locals, keyed writes and
value-copy reads for the four published `hash<Value,Key>` combinations of int64/string.
All three flavors use `[]` and `value[key]`. For example, the cpp flavor accepts:

```cpp
hash<int,string> counts = [];
counts["items"] = 8;
echo counts["items"];
```

Keys must have the exact canonical declared type. No implicit bool, float, byte, null
or text coercion is added. String keys preserve all bytes, including embedded NUL;
`"01"` and `"1"` remain different keys. Written values are typed destinations and use
existing generated checked conversions, including checked carrier extraction. Reads
return copies and throw the existing out-of-range error for missing keys; they never
insert. Replacing a key keeps the runtime's existing storage behavior.

Source `count(h)` and `empty(h)` observe the number of live keys through the
published `size()` and `empty()` members. Replacing an existing key preserves its
count. Presence of a zero or empty-string value still makes the hash nonempty.

## Owners and structures

Runtime metadata declares empty construction, strict keys, copied reads and the set/at
method contracts. Publication validates these once. The compiler generator selects the
exported C++ identity/header and emits specialized construction, key admission, value
conversion and lowering handlers. Preparation performs identity checks and calls the
existing scalar/carrier conversion path; it does not interpret metadata at compile time.
Application C++ continues to use concrete template-free public runtime APIs.

The former `ast_empty_vector_expression` is now `ast_empty_collection_expression`, shared
by vector and hash explicitly typed local initializers. Collection nodes live in
`03_parse/structures/collections.php`. Indexed reads reuse `ast_index_expression`.
New `ast_index_assignment` owns receiver, key and value children and retains only the
packed approved value-conversion code as a prepared fact. Collection, preparation reset,
inspection and emission visit those children through their existing traversal owners.
Compiler inventory version is 24. No symbol, observer, work-queue or cleanup structures change.

Lowering emits a small statement block: snapshot the key, evaluate/convert the RHS,
then call the generated set binding. This preserves key-before-RHS order and prevents
failed conversion from inserting or replacing an entry. The receiver is a simple local
name. This does not expand the deferred general expression-sequencing work.

## Boundaries and verification

Nonempty literals, inferred empty collections, hash append, indexed compound/update
operations, vector keyed writes, hash copying/arguments/returns, traversal and nested
containers remain deferred. Runtime storage algorithms and public ABI are unchanged.

`tests/hash_operations.php` checks all four pairs, strict keys, converted values, NUL
preservation, numeric-string distinction, copied reads, missing keys, conversion failures,
carrier extraction and lowering purity. Native fixtures also observe failed-conversion
atomicity using test-only C++ exception handling; source try/catch remains unsupported.
Incremental tests change producer signatures and local value types, repeat failures without
edits, delete/re-add producers and compare successful output with fresh compilation.
Unchanged consumer ASTs and library declarations are retained, and no-op facts are reused.
`tests/hash_operations_native.py` executes successful checkpoints and failure observations
in all three flavors and records Clang frontend, LLVM backend and linker timings.

## Recorded results

All 87 selected compiler/hash/provider checks pass (the hash freshness fixture was
regenerated after its metadata plan changed). All 39 native operation/recovery checkpoints
pass, and PHP style checks cover 167 files. Generator corpus verification retains
130 accepted/247 rejected runtime cases, 19/537 scalar cases and 17/37 CLI cases.
Native artifact multisets are unchanged; only metadata input/plan baselines were updated.

Representative main-fixture timings, milliseconds:

| Flavor | Native wall | Clang frontend | LLVM backend | Link command |
| --- | ---: | ---: | ---: | ---: |
| cpp | 214.12 | 34.00 | 36.87 | 85 |
| php | 161.41 | 26.45 | 23.31 | 68 |
| js | 158.10 | 25.80 | 24.81 | 63 |

O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime, fresh instrumented application
objects. These are single observations, not flavor performance comparisons. Some other
validation processes were active during this run. Trace totals are distinct from wall
time. PHP fixture generation including incremental/fresh checks took 136.27 ms.
Raw results: `/tmp/scpp-hash-operations-native-final/results.json`.
