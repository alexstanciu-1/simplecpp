# Published vector type resolution

Doc Status: supporting

## Scope and ownership

All three flavors parse generic type arguments into `ast_type_reference` using
`first_child` and `next_sibling`. Type resolution admits published `vector<T>` specializations. The subsequent
[first value-operation slice](vector_operations.md) adds typed empty construction,
append and indexed reads. Same-specialization local copying and assignment are also supported.
General methods, casts, parameter passing, returns and fields remain deferred.
An unused vector-typed parameter can also be emitted for export/header proofs.

The generator CLI receives metadata, runtime publication and native facts. It prepares
the runtime plan before generating compiler types. `compilerVectorTypes()` consumes
validated concrete exports and their providers, emitting `types/vectors.php` and
registry specialization cases. CMake tracks the publication and generator PHP files.
The compiler never reads runtime JSON while preparing an application.

The default publication provides `vector<int64>` and `vector<string>`.
`vector<int>` resolves to the same canonical singleton as `vector<int64>`.
A supported scalar element alone does not create a vector export: for example,
`vector<byte>` is unavailable in that default publication. Activating optional scalar widths does not create additional vector exports.

## Structures and processing

- `ast_type_reference::spelling()` derives written composite names without a cached copy.
- `Type_Resolution` owns declaration type lookup, arity checks and recursive argument resolution.
- `vector_type_definition` retains one non-owning canonical `element_type` reference.
- Generated concrete definitions supply C++ names and headers from runtime exports.
- `type_registry::generic_arity()` and `specialize()` expose generated family registration.

Existing AST declaration facts retain resolved type references. No additional prepared
AST, watcher or queue is introduced. Resolution visits each argument once; singleton
lookup uses canonical element identity. Simple scalar names keep direct registry lookup.
Unknown types, malformed arguments and unpublished specializations produce source diagnostics.
Generic cast syntax and vector conversions remain outside this slice.

Incremental declaration contracts include the full written type spelling, so changing
only an element type invalidates observing bodies. Existing publication, preparation,
cleanup and emission boundaries remain unchanged.

## Split and join

A published runtime call returning a concrete vector can initialize an inferred or
explicitly typed local of the same specialization. Split returns an owning
`vector<string>`; join accepts that exact specialization. The generated
flavor adapters include the vector export's provider through a dedicated
`string_vectors.hpp` header. Ordinary string calls retain their existing includes.

## Initial type-resolution verification (2026-11-06)

The 77 selected compiler CTests pass. Focused tests cover all three flavors, aliases,
linked nested syntax, arity errors, unpublished specializations, element-only signature
changes, retry without edits, deletion/re-addition and recovery against fresh compilation.
Unchanged ASTs/tokens and stable symbol entries retain their identities. A synthetic
observer verifies signature invalidation without implying vector call/value support.

Provider tests cover relocated/shared headers, specialization retirement/re-addition
and generated binding selection. The existing provider integration proof also passes.
Native fixtures compile and run declarations containing unused vector parameters.

| Flavor | Native wall ms | Clang frontend ms | LLVM backend ms | Link command ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 152.36 | 23.12 | 21.99 | 74 |
| php | 140.73 | 17.96 | 16.16 | 78 |
| js | 131.19 | 20.18 | 18.25 | 64 |

O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime and fresh instrumented
application objects. Single verification samples; trace work and parallel wall time
are different measures. These figures are not a speedup comparison.
Results: `/tmp/scpp-vector-types-native/results.json`.
