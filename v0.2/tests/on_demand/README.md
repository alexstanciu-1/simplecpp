# On-demand regression matrices

Run these matrices on dev002 from the v0.2 directory. The regular build uses
the compact tests by default. To run one full matrix against the same build:

```sh
cmake --preset default -DSCPP_ON_DEMAND_TESTS=ON
ctest --preset default -L on_demand -R '^compiler_virtual_methods_native_full$'
cmake --preset default -DSCPP_ON_DEMAND_TESTS=OFF
```

List the available matrices with `ctest --preset default -N -L on_demand` while
the option is enabled. An unfiltered CTest run in that configuration includes
the exhaustive matrices.

| Default check | Contracts exercised | Additional on-demand coverage |
| --- | --- | --- |
| Python bridge | Typed PHP call from Python, paired hash traversal, comparison-chain evaluation count and short-circuiting, provider signature rejection and recovery | Other peer frontends, Python expression and statement families, edit sequences |
| Method dispatch | Overridden method through a concrete receiver, abstract base and interface, default arguments, fixed ancestor call and missing implementation | Class hierarchies, multiple receiver types, adapter ownership and native rebuild scope |
| Record ownership | Constructor, method mutation, independent record fields, shared class identity and weak expiry | Field types, nested construction, adapters, destructor and allocator probes |
| Static access | Shared inherited storage, lexical and late-bound calls, protected access rejection and repair | Redeclarations, sibling classes, qualifiers and checked updates |
| Callbacks | Typed arrow, value and reference captures, invocation and rejection of a captured closure at a typed callback boundary | Signature combinations, lexical scopes and lifetimes |
| Boxed containers | Keyed literals, mixed and dynamic copy semantics, indexed updates, field access, presence, removal, clear and a missing-key exception | Scalar kinds, keys, ownership boundaries, operators and runtime failure cases |
| List and keyed initializer providers | Regenerate publications, type-check consumers against the generated headers, reject invalid metadata and check restoration | Compile and execute consumers with Clang and GCC across provider configurations and frontends |
| Shared upcasts | Base/child identity, mutation visibility, retention and weak expiry, nullable conversion, rejected downcast and repair | Additional hierarchy and constructor edits, ownership and lifetime probes |
| Last-use transfer | Native scanner output and generated string/vector moves before, during and after a later read | Alias, byte-view, parameter-mode and provider edits across frontends |
| Arithmetic argument order | Permitted argument traces, ordered reference aliasing and final conversion failure | Other operators, call forms, ownership and failure positions across frontends |
| Expression-qualified static calls | Qualifier evaluation and lifetime, empty typed qualifier, declared-type selection after a provider edit and invalid qualifier diagnostics | Static fields and constants, named arguments, checked updates and failure ordering |
| Interface access | Sibling-interface cast, writes through a shared view, nullable membership and incompatible cast failure | Adapter publication, recovery, weak ownership and native layout checks |
| Hash key presence | Runtime and application hash hits and misses, borrowed receiver lifetime during key evaluation, direct presence lowering, shadowing and edit recovery | Other frontends, both native compilers, exceptional operands and native rebuild scope |
| Called static methods | Emitted declarations and definitions for two sibling classes, independent retirement, rejection of an invalid demanded body and recovery | Recursive demand, forwarding, late selection, factory calls and compiler object lifetime |
| Object access | Same native ownership and RTTI probe for null, identity, dynamic casts, single evaluation and last-owner release | Adapter retirement and republication, type and layout edits, other frontends and compilers |
| Native build | Real cold build and execution, warm cache and object reuse, stale qualification rejection, failed compilation and recovery | Cross-toolchain parity, artifact invalidation, additional cache corruption and project edits |
| AST parent lifetime | Same native retained-root and weak-parent probe, no-op preparation, failed source deletion and repair | Layout and body edits, native object reuse, other frontends and compilers |
