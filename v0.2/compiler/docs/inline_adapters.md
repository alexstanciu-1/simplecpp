# Concrete inline adapters

Doc Status: supporting

The C++ backend emits ordinary concrete inline functions for conversions actually
used. It does not publish a C++ template or parse arbitrary template bodies.
The first pattern is a shared class derived-to-base view conversion.

Preparation admits the conversion and records its existing conversion code.
The target type's `cpp_conversion_adapter()` hook supplies a request during the
normal lowering walk. `Cpp_Shared_View` owns the application-owner-specific body;
`Cpp_Adapters` owns signature identity, deduplication and rendering. Runtime
control-block mechanics remain in `scpp/owner_access.hpp`. No ancestry lookup,
extra prepared expression facts or demand-discovery AST walk is added.

A request describes a named pattern, concrete parameter/result types, parameter
modes, body, explicit runtime headers and `noexcept`. Modes distinguish value,
const reference, writable reference and move-reference signatures. They do not
admit source borrows or unique conversions. Future patterns must supply their own
validated semantics and lifetime/ownership proof before using this vocabulary.

For example, with names abbreviated for readability:

```cpp
inline owner_Node scpp_adapter_HASH(owner_Leaf source) noexcept {
    owner_Node result;
    result.view = source.view;
    result.ptr = source.ptr;
    source.ptr = nullptr;
    source.view = nullptr;
    return result;
}
```

Native pointer conversion adjusts the view while the control pointer preserves
allocation identity. Copying an lvalue into the parameter retains ownership;
a temporary can transfer ownership. Clearing the parameter prevents its destructor
from releasing the transferred hold. The runtime presence check follows this
adapter when the prepared conversion requires a nonempty result.

Identity includes the pattern, native signature/modes, exception contract and
configured root namespace. Required/optional source identities share native
storage and therefore share an adapter. The remaining presence conversion belongs
to the occurrence and is excluded from that identity. Conflicting definitions for
the same identity are internal errors. Names and paths use deterministic SHA-256
identities, following the existing generated-unit convention.

Each source emitter privately accumulates demand. Parameter/result dependencies
feed the existing declaration planner. Complete application types and owner
methods precede the generated adapter includes; executable definitions follow.
These guarded fragments are internal, dependency-ordered includes, **not standalone
public headers**. Their runtime provider includes are explicit. Single-file
emission embeds the same fragments after complete declarations.

Bundle assembly merges immutable text after source lowering. The output snapshot
retains each source's demanded paths, allowing unchanged sources to reuse their
adapters without retaining AST pointers. Only the union of current source demand
is published; existing manifest cleanup removes obsolete fragments. The disk
manifest format is unchanged. This preserves private lowering ownership without
adding a shared mutable adapter registry or changing preparation scheduling.

Verification:

- `XDEBUG_MODE=off python3 -B v0.2/compiler/tests/inline_adapters_native.py`
  checks all flavors on Clang/GCC: signature deduplication, presence differences,
  demand removal/restoration, retained/fresh output, failure recovery, no-op reuse,
  namespace edits and standalone constructor-argument conversion. Persistent Ninja
  builds also verify exact object rebuild sets, no-op object/executable reuse,
  adapter-header depfiles, successful source deletion through the final consumer,
  fragment retirement and restoration. Unchanged output timestamps are preserved;
  every deletion/restoration revision is compared with fresh emission.
- `XDEBUG_MODE=off python3 -B v0.2/compiler/tests/shared_upcasts_native.py --memcheck`
  covers shared identity, mutation, null boundaries, concrete cleanup, weak lifetime
  and ancestry edits with native memory checks.

Unique conversions, borrows, downcasts, interfaces, virtuals and additional
patterns remain deferred. This implementation makes no timing improvement claim.
