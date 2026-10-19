# Concrete C++ specialization
Doc Status: supporting

The opt-in `prelower_templates` path uses the existing AST, prepared types and
operation decisions. It does not rewrite syntax or add a semantic pass. The ordinary
C++ path remains the default. This is the first bounded integer/vector slice, not
whole-accounting coverage or a general interpreter for arbitrary C++ templates.

The next design slice is [capability-driven generalization](../../../../specs/planning/runtime_template_capability_matrix_2026_10_19.md)
through the existing registry and bindings, using original runtime templates behind
concrete application interfaces. The handwritten recipes described below are the
current measured implementation, not a mandate to add more per-family implementations.

## Generic runtime ABI integration checkpoint

The engine now exposes `request_opaque(canonical_type_use)` and
`request_observation(prepared_call)`. `CPP_Opaque_Representation` consumes existing
read-only type/capability queries and C++ type/callable bindings. Its storage and
lifecycle generation has no family branches. The existing integer/vector recipe
selection lives in `CPP_Concrete_Recipes`, outside engine bookkeeping.

This is an API-level integration slice, exercised from real settled preparation.
The ordinary expression emitter and `--prelower-templates` still use the measured
first-slice recipes; they do **not** automatically select opaque storage yet.
No new AST, semantic registry or prepared-fact cache was added.

Opaque owners contain the original runtime object in aligned bytes. A grouped
`layout_probe` artifact asks the native toolchain for size/alignment and physical
lifecycle/noexcept traits. Source copy permission still comes from existing
capabilities; native traits do not grant source operations. Shared/native objects
are not heap-boxed merely to cross the boundary. A construction callback supports
owned results directly in destination storage; constructors propagate exceptions.

The first operation mechanism accepts one prepared readonly observation and an
owned storable result. Its symbol/header comes from the selected callable binding.
Borrow transport requires a prepared observation/reference decision and rejects
conversion or copy boundaries. No operation is selected by names such as `count`,
`vector` or `parcel`. Runtime-only nested arguments are admitted; generated source
type dependencies reject until their layout bootstrap is designed.

The [native builder](native_cpp_build.md) owns resolution and caching. Preparation
and emission never invoke Clang. Published source remains immutable; cached layout
headers are forced into generated units at native compilation. Requests deduplicate
inside the existing engine, and generated names distinguish semantic type identity
from coincident native spelling without embedding invocation-local IDs.

Remaining work: select this protocol through ordinary expression emission and replay
its operation requests in cached fragments; map prepared nested places and reference
returns; extend beyond observation calls and prove source-record layout dependencies.
The integration fixture's nested projection helpers are test code, not production
index lowering. See the [integration evidence](../../../../specs/planning/results/prelower_layout_integration_2026_10_19/README.md).

## Invocation

```bash
php compiler/my-try/main.php --s2s /absolute/source --output /absolute/output --prelower-templates
php compiler/my-try/tools/build_cpp.php /absolute/output /absolute/cache --no-pch -O0
```

The API owns `Compiler::$cpp_lowering`, a `cpp_lowering_options` record. Set
`$compiler->cpp_lowering->prelower_templates = true` before `exec_cpp()` or `cpp()`.
Changing this setting invalidates retained emission fragments, not semantic work.
Optimization, PCH and native debug remain independent native-builder settings.
Use `--no-pch` for the concrete comparison; native debug is off by default.

## Ownership

`CPP_Specializations` accepts canonical types, deduplicates representations and
synchronously generates their declarations and implementations. It uses registered
integer width/signedness and vector argument identities. There is no `vector<int>`
implementation branch: the same vector recipe receives the element's C++ binding.
`Capability_Facts` reads completed declaration capabilities and registered recursive
rules without scheduling preparation or caching another copy of semantic facts.

The dedicated `cpp_specialization_ref` carries one uint32 entry index. Its portable
value-record declaration has no name/header collections or worker link. The engine
alone initializes it; consumers treat it as immutable. It is invocation-local and
must not be persisted. Native compiler rebuilding/layout measurement is outside this
slice; PHP execution and successful conversion are separate evidence.

Retained fragments store canonical specialization requests. Every assembly replays
all live requests into a fresh engine, including requests from reused fragments.
Engine entries and processing workers are not retained in Model. Support emission
orders an output-only index by stable name, so discovery order does not change its
bytes. Source membership and entry IDs remain untouched.

Names live in `scpp::internal`, such as `int16`, `uint64`, `vector_int16` and
`vector_vector_int64`. Other element bindings use a collision-free encoding of their
C++ spelling. No source-name short-name lookup determines specialization identity.
Representation freshness is ordinary generated content: existing publication and
native object hashing own invalidation. There is no second object cache.

## Current coverage

- Concrete signed/unsigned 8-, 16-, 32- and 64-bit integer wrappers, derived from the
  registry; ordinary `int` shares its registered representation. Integer literals,
  prepared value conversions, checked integer-to-integer casts, canonical integer
  arithmetic/comparisons, local mutations and conditions consume these wrappers.
  Existing frontend admission restrictions still apply, including restrictions on
  arithmetic involving noncanonical widths. This does not expand source semantics.
- One vector recipe for copyable elements with available representations: default
  and list initialization, count, append, checked reads/direct writes, value iteration,
  copy/move/destruction, clear and reserve. Proofs cover all integer widths, bool and
  nested vectors, and source records with equal short names in different namespaces. Native aliasing/growth probes also exercise element snapshots.
- Prepared operation/argument evaluation order is retained. Production bounds,
  zero-divisor and integer-cast error messages, codes and details are preserved through
  a separately compiled runtime bridge.

`base.hpp/base.cpp` provide the CLI/error bridge. Per-representation headers expose
concrete declarations; `containers.cpp` groups support definitions. Integer wrapper
methods are small inline concrete methods. Clang receives ordinary C++ translation
units, not aliases to `int_t<Rep>` or explicit instantiations of `vector_t<T>`.

This does **not** make every preprocessed input template-free. Existing `bool_t`
includes runtime detail machinery; CLI/error support and runtime providers retain
standard-library/runtime templates. Ordinary nontemplate semantic types retain their
existing bindings, which may themselves contain C++ templates (for example callable
or ownership bindings). Review these exposures before extending coverage.

Other applied template families, noncopyable vector elements, runtime calls without
an adapter, integer power, noninteger checked casts, nested indexed writes, indexed
compound assignment and keyed probes/removal are outside this slice. Covered dispatch
sites reject unsupported adapters during emission. This is not a claim that all
existing compiler features can yet be used under the flag; use the default path for
uncovered applications. Full accounting needs further families and operation adapters.

[Dated proof and measurements](../../../../specs/planning/results/prelower_first_slice_2026_10_19/README.md) record the first slice.

## Validation and remaining work

```bash
python3 compiler/my-try/tests/prelower_templates.py --results /absolute/fresh-results
```

The shared runner checks same-instance option switching, no-op fragment reuse,
request removal, failure/repair, discovery-order stability, default incremental
regression, matched Clang O0/O2 execution, stable runtime errors, integer representation
limits, vector copy/move/aliasing, ASan/UBSan, and native cold/warm/body-edit reuse.
Its timing report separates application, support and linking; lowering is excluded.
No PCH is used in either comparison variant. Sanitizer leak detection is disabled.

Continue with the [decision](../../../../specs/planning/cpp_template_prelowering_decision_2026_10_19.md)
and [plan](../../../../specs/planning/cpp_template_prelowering_plan_2026_10_19.md), especially
the early runtime-template inventory/discussion. General family recipes do not imply
that every family shares one implementation. Selective-method closure, background
work, persistent engine retention and runtime throughput benchmarking remain deferred.
