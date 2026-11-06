# Adopted native scalar alias ABI and checked conversions

Doc Status: planning
Date: 2026-11-06
Status: direction adopted; [slices 1 and 2 implemented](../checked_conversions.md).
Wrappers remain published; the coordinated alias cutover is pending.
Compiler numeric checked conversions now use named helpers; string comparison still
extracts the wrapped boolean result until its runtime API migrates.

## Scope and ownership

Replace published integer, floating and boolean wrapper classes with aliases to
their exact target-validated native representations. Keep explicit scpp type names
in runtime, generated runtime and lowered application C++. Prefer C++ aliases over
macros. Metadata owns publication and bindings; target facts own native identity and
layout; compiler preparation owns source semantics. The
[representation catalog](native_representation_catalog.md) lists library-backed
types that remain concrete classes.

The null runtime type/sentinel and the compiler's current null lowering remain
unchanged. String, collection, nullable/result and ownership representations are
not replaced with scalar aliases. No new source types, explicit-cast syntax,
conversion permissions or operator behaviors are activated by this migration.

## Alias ABI

Generate aliases for the selected runtime publication, using the existing module
and demand rules. Supported fixed-width bindings are:

| Published name | Target representation |
| --- | --- |
| scpp::int8 / uint8 | __INT8_TYPE__ / __UINT8_TYPE__ |
| scpp::int16 / uint16 | __INT16_TYPE__ / __UINT16_TYPE__ |
| scpp::int32 / uint32 | __INT32_TYPE__ / __UINT32_TYPE__ |
| scpp::int64 / uint64 | __INT64_TYPE__ / __UINT64_TYPE__ |
| scpp::byte | scpp::uint8 |
| scpp::float64 | double, validated as IEEE binary64 |
| scpp::bool_t | bool |

For example, the generated declarations include `using int16 = __INT16_TYPE__;`
and `using uint16 = __UINT16_TYPE__;` when those types are published. On the current
target they denote short and unsigned short; those spellings are not portable ABI
assumptions. The built-in macro spelling supplies storage, while probe-measured
native identity supports overload deduplication and private consistency assertions.
Width, signedness and size alone cannot distinguish long from long long.

Existing source aliases such as int/int64 retain their canonical compiler identity;
C++ keyword int is not a new namespace alias declaration. Optional numeric runtime
publication does not imply source compiler activation. Float32 and other new formats
remain deferred until their target facts and language contracts are supported.

Aliases introduce no wrapper storage, constructor, extraction or overloaded scalar
arithmetic. They are not strong typedefs: overloads/references cannot distinguish an
alias from its underlying native type. No custom operators may be declared between
two builtin aliases. Compiler type identities remain authoritative for source-level
strict identity, reference matching, overload selection and conversion permissions.

Public headers and their transitive closure remain template-free. Keep aliases and
concrete function declarations public; implementation templates and standard-library
algorithms stay in private compiled runtime code. Exact header placement is an
implementation choice, not a reason to add a parallel scpp::native scalar hierarchy.

## Named checked-conversion boundary

Metadata publishes concrete conversion bindings: canonical source and destination,
symbol/header, parameter/result representations, algorithm and failure behavior.
Generated compiler handlers resolve these at generation time; source preparation
must not interpret metadata or search conversion paths for each expression.

Use source/destination-specific symbols where needed to avoid alias-induced overload
collisions. An illustrative declaration is:

```cpp
scpp::uint8 checked_int64_to_uint8(scpp::int64 value);
```

The final symbol spelling is metadata-owned. This is not a public function template
and not a constructor replacement discovered through C++ overload resolution.
Bind exact source representations. For direct C++ use, generated concrete deleted
overloads should reject unintended native argument conversions before the checked
function runs; a caller's explicit C++ cast remains the caller's responsibility.
Do not infer native type identity from spelling or width when emitting fences.

Preserve existing supported conversion algorithms and exception behavior:

- Integer to integer preserves value, checking signedness/range before conversion.
  Current range failure is std::out_of_range with integer_cast_out_of_range.
- Floating to integer rejects NaN/infinity, truncates toward zero and checks the
  destination range before the native integer cast. Preserve the existing private
  algorithm and its diagnostics, including representability boundaries.
- Integer to floating retains permitted rounding; it is not an exactness guarantee.
- Boolean to numeric yields 0/1. Numeric to boolean tests nonzero, including NaN as
  true and both signed zeros as false, when the conversion context permits it.
- Same-type identity and proven representable conversions need not call runtime
  helpers. Do not add a call to every widening operation merely for uniformity.
- String conversion already published by the runtime keeps its existing parsing,
  formatting and failure contracts through named bindings. This does not activate
  deferred source-level text conversions or introduce a general to_string feature.

Conversions take an input value and return a result; they do not mutate the caller's
destination. Checked assignment/write-back stores only after successful conversion.
Do not introduce saturation, wrapping, sentinel results or unchecked fallbacks.
Noexcept follows actual failure behavior, not merely the native storage category.

Availability of a helper does not authorize its source-language use. Typed
destinations, operator promotion, ordinary assignment and compound write-back keep
their separate metadata permissions. Existing named arithmetic helpers remain the
boundary for division/shift/power checks. Raw handwritten C++ casts/arithmetic obey
C++ semantics; callers needing Simple C++ behavior must use the named operations.

## Initialization, collections and migration constraints

Wrapper default constructors currently establish zero/false. Native aliases do not
zero-initialize an uninitialized local. Generated declarations and runtime fields
must explicitly initialize where the existing contract requires it. Preserve reset,
moved-from, nullable/result and container lifecycle states when replacing wrappers.

Vector input filtering remains strict. Exact element/count representations and
explicitly selected adapters are admitted; other native inputs retain generated
deleted overload fences. Alias-equivalent types share one signature. Vector casts
must use named checked conversion bindings before appending/storing; partial failure
discards the temporary and preserves source/destination. No public template is needed.
Null behavior is unchanged. Native bool element storage and its std::vector<bool>
proxy implications need their own bounded review before migration.

This is a coordinated ABI break, not a source-compatible typedef substitution.
Rebuild generated runtime implementations, archives and consumers together. Retire
obsolete scalar constructors/operators/native bridges rather than keep two permanent
scalar APIs. Temporary coexistence while introducing helpers is a migration stage,
not the final design. Do not add compatibility wrappers or module-version machinery.

## Proposed implementation slices for discussion

1. Add named concrete conversion functions while wrappers still exist. Use native
   representations in their ABI, metadata-selected bindings, exact input guards and
   focused boundary proofs. Reuse existing private algorithms. Prove equivalence to
   existing constructors and retain compiler generated-code behavior at this stage.
2. Switch generated compiler conversion bindings and runtime result extraction to
   the named functions/native returns as appropriate. Preserve preparation rules,
   diagnostics and independent operation admission. This compiler integration is
   required before wrapper retirement, not collection syntax implementation.
3. Retire wrappers in one coordinated scalar ABI cutover: generate aliases, migrate
   dependent vectors/carriers/hash/boxed/JSON/datetime surfaces and private adapters,
   audit initialization and retire obsolete overloads. This is the larger cross-owner
   slice; confirm its inventory and verification cost before implementing it. If
   necessary, prepare helper migrations per family first, without exposing mixed ABI.
4. Consolidate publication fixtures, remove temporary adapters and resume source
   vector<T> integration. Optional widths stay governed by existing activation rules.

Required proof includes alias identity and target consistency, accepted/rejected
direct C++ inputs, integer extrema/signedness, floating boundaries/nonfinite values,
unchanged source conversion admission, failure atomicity, defaults/reset states,
collection casts, and template-free public include closures. Compiler-binding changes
require all-flavor incremental recovery against fresh compilation. Benchmark generated
size and Clang frontend/LLVM/linker times with matching build settings; aliases alone
do not establish a measured speedup.

## Current worktree boundary

The earlier bounded vector migration is paused and saved outside the active source
changes as a local patch (`/tmp/scpp-paused-vector-arwtc0f6/prototype.patch`). Its
wrapped API baseline was restored for slice 1, so named conversion publication is
additive and does not change collection consumers. Probe-measured native identities
were retained as a prerequisite for exact conversion-input fences. Reconcile the
saved vector prototype with the alias cutover before resuming it; it is not a
completed migration or part of the checked-conversion implementation.
