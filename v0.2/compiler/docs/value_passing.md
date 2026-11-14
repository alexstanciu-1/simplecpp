# Common by-value parameters and returns

Doc Status: supporting

A by-value boundary uses the destination canonical type's existing conversion handler.
Functions, methods and constructors share the argument binder; returns use the same typed
destination conversion context. Identity means no conversion, not absence of native copy
construction. Published vector/hash families inherit the common identity rule instead of
checking the expression's AST parent. This applies to every existing specialization.

No AST fields, retained preparation records, per-call capability tables or runtime ABI
changes are introduced. Preparation compares canonical identities and retains its existing
packed conversion codes. C++ lowering and runtime RAII implement copy, move and destruction.
Metadata's trivial/nontrivial classification remains a lowering safety fact, not copyability.

Value semantics depend on the family: vectors/hashes own independent container storage;
strings preserve value semantics; structs copy members; shared/dynamic copies preserve
identity; weak copies do not extend payload lifetime; carriers copy state and payload.
This does not imply recursively cloning shared elements.

Writable vector/hash parameters now use the existing exact-type named-local/parameter
reference binder. See [collection references](collection_references.md). Existing scalar/owner
reference behavior is unchanged. Fields/globals, default
arguments, unavailable runtime specializations, unique/borrow and new collection literals
are outside this change. Empty brackets still require an explicitly typed local initializer.

`tests/value_passing.php` and `tests/value_passing_native.py` exercise all six published
vector/hash specializations in all three flavors: functions, methods, constructors,
returned locals, returned temporaries, multi-argument calls, independent mutation and
assignment. Cross-file edits, incompatible return types, deletion, retry without edits and
recovery are compared with fresh emitted bundles; native fixtures verify their output.
Lowering purity, unchanged caller AST reuse and zero preparation on no-op runs are checked.

## Verification snapshot

2026-11-14: all 72 native fixtures passed. Nine focused PHP suites passed (vector
types/operations/copies, hash types/operations, boxed scalars, enum methods, carrier
types/values); the compiler writing checker passed. No runtime regeneration was required.

Fresh single-unit fixtures, medians across the six collection specializations (ms):

| Flavor | PHP frontend/prepare/emission | Native wall | Clang frontend | LLVM backend | Link command |
| --- | ---: | ---: | ---: | ---: | ---: |
| PHP | 2.096 | 270.014 | 30.830 | 35.093 | 69.0 |
| C++ | 2.440 | 231.934 | 29.694 | 30.512 | 63.5 |
| JS | 1.907 | 216.749 | 29.481 | 29.196 | 57.0 |

O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime, fresh application objects
with Clang time traces. Native wall includes the direct Ninja build path and its
qualification overhead; trace components do not sum to wall time. These are one-run
fixture medians, not a controlled performance comparison between flavors.
Raw sources, emitted artifacts, logs and traces: `/tmp/scpp-value-passing-proof/`.
