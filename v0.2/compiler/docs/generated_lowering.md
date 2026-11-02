# Metadata-generated native lowering

Doc Status: supporting

## Agreed direction

The runtime API generator also generates executable PHP traits for the C++ backend.
Metadata is the shared source of type representations, conversion contracts and
operator policies. Generate specialized dispatch code rather than interpreting the
metadata on every expression or maintaining parallel handwritten type matrices.

Preparation owns source admission, resolved language types, overload selection and
conversion decisions. These remain AST-owned facts. Lowering consumes them without
resolving names, mutating facts or discovering new source-language errors. Shared
rules needed by preparation and lowering should come from the same metadata.
Language identity remains distinct from storage: native representation does not
make byte an arithmetic integer or collapse distinct source types.

Generated lowering can emit native expressions, explicit conversion/check code, or
calls through the runtime public API. Casting is intended primarily for generated
lowering; operations may remain runtime helpers. Choose and measure these per
operation rather than forcing one policy everywhere. Value-dependent failures must
retain their specified checks. Future handlers needing temporaries/statements must
preserve evaluation order and evaluate each operand once. No generic expression
result framework is introduced until an implemented operation needs it.

The goal is simpler emitted C++ and reduced header/overload work for Clang. Removing
wrappers is not itself proof of faster executables: optimized native_value accessors
may already disappear. Measure build time and runtime behavior where relevant.

## Implemented first slice

`emit-compiler` reuses the API generator's scalar registry and validated target
facts. It emits `compiler/generated/cpp/native_values.php`, a PHP trait used by
`Cpp_Emitter`. Its int64 storage spelling is `__INT64_TYPE__`, the same target-native
representation used by the runtime generator, without a wrapper or header include.
For example:

```cpp
[[maybe_unused]] __INT64_TYPE__ local_0 = 8LL;
scpp::echo_int(local_0);
return static_cast<int>(local_0);
```

The trait accepts checked int64 facts, never source literal text. It preserves
normalized decimal spelling and handles the signed minimum without an overflowing
positive literal. References retain their existing local binding identities.

Echo and checked process-exit conversion still use the minimal runtime public APIs.
Proven exit conversions remain native casts. Runtime wrapper APIs remain available
for existing native consumers; this slice does not remove them or add arithmetic,
new source casts, additional compiler types, profiles or incremental compilation.

## Generation and verification

From `v0.2/`:

```sh
cmake --preset default
cmake --build --preset default --target scpp_runtime
php tools/api_generator/generate.php emit-compiler runtime/metadata/runtime.json \
  build/clang/native-layout/facts.json build/clang/native-layout/context.json \
  compiler/generated --check
```

CMake's `scpp_compiler_publication` checks output on every build and regenerates
only when stale, preserving unchanged timestamps. It also runs when building the
runtime target used to set up the panel. Generate before running the PHP compiler
in a fresh checkout. Runtime-only exports do not require the compiler publication.
Generated PHP is not checked in and is never edited as the source of truth.

The dedicated compiler output has its own versioned inventory. Regeneration clears
all `.php` and `.json` files recursively there, including retired artifacts; other
extensions are preserved. Runtime publication retains its existing extension list
and inventory contract. `--check` performs no writes.

Proofs cover generator metadata admission, freshness/cleanup, scalar boundary
spelling, all-flavor executable status, integer output, cross-file calls, checked
exit boundaries, deterministic output and AST purity. No C++ wrapper or
`native_value()` should occur in emitted integer code.

## Runtime retirement inventory

See the [native scalar runtime dependency inventory](../../specs/planning/native_scalar_runtime_inventory.md)
for remaining wrappers, their callers and the proposed first removal slice. Native
compiler lowering currently avoids wrappers; it does not yet suppress their runtime
publication. Cross-family API migrations require an explicitly agreed scope.
