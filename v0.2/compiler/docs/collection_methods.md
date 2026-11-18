# Generated collection methods

Doc Status: supporting

Vector and hash expose `clear()`, `remove(key)` and `has(key)`. PHP and C++ use `->`;
JS uses `.`. This is a Simple C++ API, not a promise of PHP/JS standard-library compatibility.
There is no new `list_clear`. Hash key presence also has the free spelling
`hash_contains(collection, key)` described below.

```text
// PHP
$items vector<int> = [];
$items[] = 8;
$items->clear();
factory()->items->clear();

// C++
vector<int> items = [];
items->clear();

// JS
let items:vector<int> = [];
items.clear();
```

Clear returns void, removes all elements, and is harmless on an empty collection.
Vectors destroy their elements and retain capacity. Hash capacity is unspecified;
the existing runtime releases its storage. Both can be populated again afterward.
Collection copies remain independent; shared owners of the containing struct observe
the same mutation. A by-value collection parameter clears its copy; a reference
parameter clears caller storage. Private fields remain inaccessible to foreign callers.
Empty shared receivers throw through the existing checked owner access.

## Owners and retained state

The API generator consumes each published specialization's validated method plan and
provider export. It registers a canonical `runtime_function` for clear, with the
collection as operand zero and `receiver_method = true`. Existing reference masks
distinguish mutable from const receivers. This flag describes the
native member binding; it is not a new intrinsic or an additional per-call
prepared record. Canonical argument/result types come from the validated method plan.

Existing `ast_call_expression` stores the receiver and arguments. Preparation resolves
the generated binding by flavor, canonical type and name; checks arity and syntax;
and reuses receiver preparation for field visibility. Mutable named receivers use the existing
mutable-reference validation/invalidation; read-only receivers do not invalidate facts. Expression temporaries are permitted:
clearing a temporary collection has no effect on another collection's copy.
Ordinary reference arguments still require exact-type named locals/parameters.

The backend emits `(receiver).clear()` directly. The receiver is evaluated once;
C++ full-expression lifetime keeps temporary inline/shared owners alive through the call.
Clear adds no snapshot or lambda. Neither method adds an AST class, observer index or per-expression fact.
Existing watched field/function contracts invalidate the containing body after edits.
No metadata is interpreted during application preparation.

## Verification (2026-11-14)

`tests/collection_clear.php` covers six specializations × three flavors, copies,
reference/value parameters, shared and inline fields, expression receivers, private
fields, argument/result errors, receiver type edits, deletion, repeated failure without
edits and recovery. Successful incremental output equals fresh output; unchanged caller
ASTs are reused and no-op runs prepare zero bodies. Inspection/emission purity is checked.
`tests/collection_clear_native.py` executes 54 fixtures, including checked empty-owner
failures and 18 successful incremental checkpoints. `tests/collection_clear_storage.cpp`
uses public APIs and allocation accounting to prove retained vector capacity, element
release and balanced allocation lifetimes. Collection fields/references, indexed
compounds/updates, ordinary reference parameters and enum methods are regression checks.

Measured medians across the six main fixtures per flavor (one build per fixture):

| Flavor | PHP prepare/emit/inspection (ms) | Native wall (ms) | Clang frontend (ms) | LLVM backend (ms) | Optimizer (ms) | Codegen (ms) | Link command (ms) |
|---|---:|---:|---:|---:|---:|---:|---:|
| PHP | 2.50 | 209.66 | 26.53 | 29.17 | 12.85 | 15.01 | 57.50 |
| C++ | 2.66 | 212.85 | 26.53 | 28.77 | 12.69 | 14.79 | 56.00 |
| JS | 2.92 | 209.94 | 26.31 | 28.39 | 12.64 | 14.40 | 56.50 |

O2/g0, libc++, mold, Ninja8, no PCH/LTO; rebuilt runtime archive reused, fresh
instrumented application objects. Direct Ninja includes qualification-check process
overhead. LLVM optimizer/codegen are nested components, not additional wall time.
Combined measured PHP + native work medians are 212.17/215.49/213.29 ms (PHP/C++/JS);
this sum excludes fixture setup and executable launch. Native ranges were 202–231,
202–1246 and 200–240 ms respectively; the C++ group contained one large wall-time outlier.
These are bounded correctness fixtures, not a controlled before/after speed comparison.
Raw artifacts: `/tmp/scpp-collection-clear-proof/` (temporary).

## Removal

`remove(index)` shifts a vector suffix left; `remove(key)` preserves other hash keys.
Both return bool and return false without mutation when absent, including negative or
past-end vector indexes. Index/key admission remains exact; aliases share canonical
identity. C++ overload input fences remain strict. No new conversion policy is added.

The existing runtime-call binder prepares each argument once and checks it against the
generated canonical parameter type. Emission uses the existing exact-argument lowering
handler so integer literal suffixes agree with the target's native alias. Simple calls
remain `(receiver).remove(key)`. If key evaluation can replace a shared receiver owner,
`retained_place()` keeps that allocation alive and binds the selected collection before
evaluating the argument. This temporary preserves lifetime, not an additional operand
ordering policy. Empty owners throw before key evaluation. An inline temporary lasts
through the native member-call full expression.

No new retained fields were added for removal. `runtime_function.receiver_method`
now describes zero/argument-bearing methods instead of just zero-argument members.
Preparation continues using the existing call path; it does not scan unrelated bodies.

`collection_remove.php` and its native harness cover all six specializations and all
three flavors, first/middle/last/missing removal, vector shifting, hash NUL keys, copies,
by-value/by-reference parameters, expression receivers, last-owner replacement, empty
owners, private access, type/arity errors and incremental failures/recovery against fresh
compilation. The storage proof checks int64 extreme indexes, strict public native inputs,
no allocation on missing removal, vector buffer reuse and balanced element destruction.

Removal verification (2026-11-14): 54 native fixtures passed. Provider relocation and
publication tests cover the new bindings; the provider negative fixture now uses an
actually unpublished node rather than numeric.scalars, which is published by default.

| Flavor | PHP prepare/emit/inspection (ms) | Native wall (ms) | Clang frontend (ms) | LLVM backend (ms) | Optimizer (ms) | Codegen (ms) | Link command (ms) |
|---|---:|---:|---:|---:|---:|---:|---:|
| PHP | 5.53 | 237.12 | 29.73 | 53.75 | 22.70 | 29.45 | 56.50 |
| C++ | 5.25 | 237.04 | 29.74 | 51.58 | 21.99 | 27.99 | 56.00 |
| JS | 5.09 | 237.00 | 29.85 | 53.48 | 22.24 | 28.65 | 57.00 |

Same build settings as above; medians across six main fixtures per flavor, not a
before/after comparison with the smaller clear fixtures. Native ranges: PHP 224–259 ms,
C++ 226–247 ms, JS 216–246 ms. Raw artifacts: `/tmp/scpp-collection-remove-final/`.

## Presence queries

`has(index)` checks vector bounds and `has(key)` probes the hash. Both return bool;
absence is false, including negative and extreme vector indexes. Stored zero or an
empty string still counts as present. No element copy or collection allocation occurs.

The former `mutable_receiver_method` flag is now `receiver_method`. Existing mutable
and const reference masks describe operand zero; no extra field or per-call fact is
needed. Generator validation checks the published method's argument/result types and
mutability. The existing preparation loop checks exact argument types and skips mutable
binding/invalidation for has. Its argument may still call another function that mutates
storage, so the existing lowering lifetime protection applies equally to read-only calls.

`collection_has.php` and its native harness test all six specializations in all three
flavors, zero/empty-string payloads, NUL keys, extreme indexes, repeated probes, fields,
expression receivers, shared owner replacement, empty-owner ordering, type/arity/private
access diagnostics, and incremental edits/deletion/recovery against fresh compilation.
The native storage proof calls const receivers and counts allocations/releases to check
that empty probes do not allocate and populated probes do not copy heap-backed values.

Presence verification (2026-11-14): all 54 native fixtures and focused regressions passed.
Medians across the six main fixtures per flavor, with the same O2/g0, Ninja8, mold,
no-PCH/LTO, prebuilt-runtime and fresh-object settings used above:

| Flavor | PHP prepare/emit/inspection (ms) | Native wall (ms) | Clang frontend (ms) | LLVM backend (ms) | Optimizer (ms) | Codegen (ms) | Link command (ms) |
|---|---:|---:|---:|---:|---:|---:|---:|
| PHP | 4.68 | 236.63 | 30.40 | 50.58 | 21.76 | 26.35 | 57.00 |
| C++ | 4.53 | 235.24 | 29.33 | 48.81 | 21.52 | 25.81 | 55.50 |
| JS | 4.64 | 234.96 | 29.97 | 51.05 | 21.60 | 26.77 | 55.50 |

Combined measured PHP/native work medians: 241.44/240.20/239.68 ms; this excludes fixture
setup and executable launch. LLVM subcomponents overlap their parent totals. Raw results
and generated programs: `/tmp/scpp-collection-has-proof/` (temporary).

## Shared hash key-presence capability

`key_presence_type` exposes an immutable `runtime_function` for `hash_contains`.
The hash generator validates the published `has(const Key&) -> bool_t` signature,
const receiver and provider, then constructs one binding per canonical runtime or
application hash type. Its references to canonical types are non-owning in the native
model. Neither AST fields nor per-call signature objects are added.

Preparation selects this capability after ordinary function/import resolution. It
passes the already-prepared receiver to the existing runtime-call checker, which
validates exact key types. Lowering uses the common native-member path: operand zero
is the receiver whether source syntax is a method or a free call. Key arguments reuse
exact-argument lowering; existing shared-owner retention protects field receivers.

The immutable runtime signature classes now live in `types/runtime.php`, separate from
AST declarations, so standalone generator/provider consumers can construct bindings.
No new runtime API or nullable application carrier is introduced. Existing member
syntax retains its current admission; this slice adds the free helper for both hash
families, not general application collection methods.

`hash_contains.php` checks canonical binding reuse, const modes, preparation visits,
emission purity and reset/reprepare equivalence. `hash_contains_native.py` covers
runtime/application hashes and both key types, missing/present zero/empty values,
NUL/numeric-looking keys, field and temporary receivers, owner replacement and expiry,
empty-owner/key failures, native-inserted empty shared payloads, function precedence,
imports, and incremental body/layout/deletion/error recovery in all three flavors on
Clang/GCC. The hash provider fixture also checks the binding's relocated header and
publication retirement/restoration. These are correctness proofs, not performance claims.
