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
