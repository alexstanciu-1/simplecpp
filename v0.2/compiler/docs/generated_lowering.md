# Metadata-generated native lowering

Doc Status: supporting

Current representation: [native scalar aliases](../../specs/native_scalar_aliases.md) supersedes earlier scalar wrapper spellings and extraction adapters.

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

## Placing optimization work

The development build needs one semantic preparation that can diagnose the source
and immediately emit correct C++. A smaller native loop can justify substantial
analysis for a hot function. Running that analysis on every edited function
charges the developer even for bodies where the optimization never applies.
Evaluate the cost of *discovering* a safe transformation separately from the
cost of spelling it in C++ and the benefit in the executable.

| Where the work belongs | When to use it | Why |
|---|---|---|
| Ordinary source, provider or lowering | The source requests the operation, or prepared types, conversions and provider capabilities already determine the direct form. | Every build can benefit without a new search. The provider owns storage, checks and failure behavior. |
| Bounded foreground proof | A cheap candidate check narrows the work to relevant bodies or statements. Existing body-local facts or occurrence indexes suffice. | Useful code is available on the first build. Measure the cost on bodies with and without candidates before widening the proof. |
| Optional body analysis | The optimization needs broader reasoning about uses, aliases, effects, control flow or ranges. | An editor can build immediately with ordinary lowering and spend extra analysis time selectively on bodies likely to benefit. |

Correctness checks and diagnostics belong to ordinary compilation. Every optional
transformation must preserve the source evaluation contract, including observable
failures, ownership and destruction. For example, a record with one final consumer
can still be unsafe to construct early in a vector. An intervening call may
grow that vector or throw before the source append would have allocated.
Keep the ordinary append whenever the proof is incomplete.

### Proposed ownership and reuse

For the first optional pass, request optimization explicitly for one successfully
prepared function body. The job reads that body's settled facts and the contracts
it needs, and privately produces a small lowering plan. Keep optional decisions
off shared syntax nodes. This keeps ordinary bodies light and lets a future
native worker analyze one body while other workers read the same generation.

Use the existing incremental dependency model. Reuse the plan across unrelated
edits. An edit to the initializer or a change to a provider capability the proof
used invalidates it. Publish a completed plan only if its input generation and
dependencies are still current. Retain inputs until their readers finish. Each
build fixes its plan selection before emission so worker timing cannot change
C++ halfway through a build. A newly available plan can be selected on a later
requested build, which then re-emits the affected unit as needed.

Start with in-memory reuse to measure the benefit. A resident compiler could run
the same job between requests, giving foreground edits priority. A one-shot
compiler exits when its build finishes. Reuse across separate invocations would
require a persistent cache with compatible compiler, provider and target facts.
Idle scheduling and disk persistence are future decisions. Neither is required
to try an on-demand pass.

For the first experiment, measure foreground preparation without a plan (including
candidate misses), the separate proof cost, and later emission with the cached
plan. Compare emitted C++, native compile time and execution on the same workload.
Verify source behavior on optimized and fallback paths, reuse after an unrelated
edit, invalidation after a relevant edit, and retirement of stale results during
edit and recovery. Compare fresh and incremental output with the same plan
selection. These measurements guide which proofs merit foreground time.
[Tokenizer findings and first experiment](../../specs/planning/v03_bootstrap_implementation_plan.md#optimization-costs-for-the-six-tokenizer-findings)

### Requesting direct-slot construction

Use `project` or `build` with `--optimize-function=scan` to request analysis
of that prepared free function. Repeat the flag for additional functions, using
qualified source names for namespaced functions. A resident project accepts the
same selection as a list in `run()`. The selection applies to one invocation.

The application-vector provider publishes the optional construction helper.
Only source units with eligible sites include its private header. Removing the
last site retires it. Body identity and the file's preparation revision govern
in-memory plan reuse. Ordinary builds skip discovery and use the existing append.

## Implemented first slice

`emit-compiler` reuses the API generator's scalar registry and validated target
facts. It emits `compiler/generated/types/int64.php`, the complete
canonical `type_int64` definition, plus null/bool/byte definitions and their direct source-name registry. `Cpp_Emitter` dispatches through resolved type
objects; see [type ownership](types.md). Its int64 storage spelling is `__INT64_TYPE__`, the same target-native
representation used by the runtime generator, without a wrapper or header include.
For example:

```cpp
[[maybe_unused]] __INT64_TYPE__ local_0 = 8LL;
scpp::echo_int(local_0);
return static_cast<int>(local_0);
```

Generated lowering accepts settled literal AST facts, never unchecked source literal text. It preserves
normalized decimal spelling and handles the signed minimum without an overflowing
positive literal. References retain their existing local binding identities.

Echo and checked process-exit conversion still use the minimal runtime public APIs.
Proven entry literals use plain decimal spelling. Other proven exit expressions use a native cast. Runtime wrapper APIs remain available
for existing native consumers; this slice does not remove them or add arithmetic,
new source cast syntax, operators, profiles or additional incremental machinery.
The [core scalar extension](types.md) adds metadata-driven declaration conversions,
using native storage and named public conversion functions for unknown-value range
checking. Binding planning reuses runtime recipes; preparation remains unchanged.

## Generation and verification

From `v0.2/`:

```sh
cmake --preset default
cmake --build --preset default --target scpp_runtime
php tools/api_generator/generate.php emit-compiler runtime/metadata/runtime.json \
  build/clang/runtime-publication.json \
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
