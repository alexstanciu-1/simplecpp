# Deferred runtime review: prepared facts and native operations
Doc Status: planning

Date: 2026-10-20
Status: non-blocking discussion material; tentatively v0.3, not a release commitment.
No implementation or additional optimization is authorized by this note.

## Current priority

Solidify the existing general `prelower_templates` path. Its measured benefit is
already substantial. Preserve the existing runtime behavior, demand-driven concrete
interfaces, registry bindings and prepared decisions. Prepare only used types and
operations, including nested specializations; do not prepare the entire runtime.
Keep unsupported coverage and any fallback to templates explicit.

The accounting application now passes 998 checks, with all 16 application units
showing zero template declarations in their preprocessed input and zero Clang
class/function instantiations, without PCH. This establishes accounting coverage,
not complete runtime coverage. See the [acceptance evidence](results/prelower_template_free_accounting_2026_10_19/README.md).

The immediate work remains correctness and consolidation of this mechanism:
consistent concrete substitution, truthful metadata and bindings, stable artifact
reuse, and preservation of evaluation order, nested references, lifetimes and
boundary allocation/copy behavior. Focused replay proofs do not establish that the
whole incremental compiler is finished. The future review below is not a prerequisite
for this work and must not expand it into another optimization project.

## What the compilation evidence means

The [native unit comparison](results/prelower_regular_comparison_2026_10_19/README.md)
measured O0 without debug information: all 16 units took 22.769 s sequentially with
ordinary templates and PCH, versus 2.801 s with concrete interfaces and no PCH
(8.13x). With 12 workers, wall time was 5.433 s versus 0.625 s (8.69x).
These are native application-unit compilation measurements. They exclude compiler
lowering and initial middleware preparation; moving template work into reusable
middleware does not eliminate its initial cost. They do not establish a universal
10x penalty for templates or a C-versus-C++ language-mode difference.

External explanations support the mechanism:

- Template instantiation performs semantic work, including substitution, lookup,
  overload resolution and constructing specialized declarations/bodies. Even tiny
  traits can accumulate substantial cost when instantiated at scale; Richard Smith's
  LLVM talk illustrates compiler time and memory per trait instantiation.
  [LLVM 2024 presentation](https://llvm.org/devmtg/2024-10/slides/techtalk/Smith-Generic-implementation-strategies-in-Carbon-and-Clang.pdf)
- Instantiating large function templates can revisit extensive type-independent
  bodies. An LLVM developer's deliberately exaggerated experiment fell from roughly
  35 s to 5 s after moving that work into a non-template function. This illustrates
  repeated work, not a prediction for accounting.
  [LLVM discussion and experiment](https://discourse.llvm.org/t/rfc-identifying-wasteful-template-function-bodies/53869)
- Separate translation units can repeat instantiation and code generation; linker
  deduplication happens after that compilation cost. GCC documents this common
  implementation model. This does not mean a compiler repeats every specialization
  at every call within one translation unit.
  [Template instantiation model](https://gcc.gnu.org/onlinedocs/gcc/Template-Instantiation.html)
- PCH reuses serialized compiler state and loads it lazily. It does not automatically
  supply all later application-specific specializations and their generated code.
  Therefore the explanation is not simply that loading PCH copies a whole runtime.
  [Clang PCH internals](https://clang.llvm.org/docs/PCHInternals.html)
- Historical LibreOffice profiles found template work dominating even with PCH.
  Subsequent Clang/PCH improvements substantially reduced costs, so these findings
  do not mean templates were never optimized or PCH is useless. Our comparison is
  against the measured configuration, not every possible C++/PCH optimization.
  [2019 profile](https://llunak.blogspot.com/2019/05/why-precompiled-headers-do-not-improve.html),
  [2021 improvements](https://llunak.blogspot.com/2021/04/clang-precompiled-headers-and-improving.html)

Our interpretation: small concrete declarations and compiled implementations avoid
exposing and processing extensive template machinery in each application unit.
The measurements support that boundary. They do not yet measure the benefit of
removing runtime scalar or string wrappers.

The [PHP-hosted edit-cycle experiment](results/accounting_edit_cycle_2026_10_19/README.md)
is diagnostic evidence only. The PHP implementation is not the intended compiler
performance comparison, and incremental compilation is unfinished. Its full-cycle
numbers must not drive optimization priorities now.

## Future review: who owns each responsibility?

The following are candidates for discussion, not approved representation changes.
Source semantics remain authoritative. A semantic type need not retain its current
C++ wrapper merely because that wrapper exists today.

| Area | Existing preparation should decide or publish | Possible emitted operation | Support that remains dynamic |
|---|---|---|---|
| Numeric types, including `int_t` | Width, signedness, capabilities, conversion and operator decisions; any proven range facts | Native scalar storage/arithmetic where semantics match; a concrete checked helper otherwise | Value-dependent overflow, division, shift and conversion checks required by the language |
| Traits, constraints and overload machinery | Admission, chosen operation, argument/result types and lifecycle requirements | Direct call or expression using the selected binding | The selected algorithm, where it actually does runtime work |
| `string_t` | Semantic string type, representation contract, conversion and operation selection | Concrete interface or direct helper; review whether the current wrapper is needed | Owned storage, allocation, lifetime, byte/UTF behavior, comparison, formatting and required checks |
| `vector`, `hash` and nested types | Element/key capabilities, layouts, lifecycle and selected operation bindings | Demand-driven concrete methods or helpers | Storage, growth, hashing/equality, destruction, iteration behavior and required bounds checks |
| `shared`, `unique`, `value` and source objects | Ownership/storage contracts, allowed copy/move operations, field layouts and lifecycle bindings | Concrete ownership/storage interfaces; native access where equivalent | Reference counts where applicable, allocation, destruction and value-dependent access checks |
| Conversions and guards | Static validity and proofs already available to preparation | Native conversion when equivalent; selected helper otherwise | Checks depending on values unavailable at compile time |
| I/O, filesystem, JSON, date/time and other services | Callable selection, argument/result contracts and static validation | Direct calls to compiled implementations | External effects, parsing/formatting algorithms and other dynamic behavior |

In particular, replacing `int_t` with a native integer must not introduce C++
undefined behavior or change checked arithmetic. Replacing the app-facing
`string_t` representation does not remove string ownership or make `char*` an
equivalent implementation. Removing an overload from generated input does not
remove the operation's required runtime semantics.

A “prepared function” can be a demand-driven concrete helper in the existing
middleware. It executes dynamic work through a binding already selected by
preparation. Prefer that when it preserves behavior and reuse; do not introduce
another semantic decision engine or duplicate runtime algorithms in the backend.

## Constraints and future discussion sequence

1. Inventory the relevant runtime metadata and existing compiler facts before
   proposing additions. Reuse the existing type/capability registry, `cpp_types`,
   prepared operation/conversion decisions, backend bindings and layout cache.
2. For each responsibility, distinguish semantic identity, physical representation
   and dynamic behavior. Enrich its existing owner only where facts are missing.
   Do not add a parallel registry, rewritten AST or family-specific engine branches.
3. Discuss a bounded candidate and its semantic obligations before implementation.
   Retain original runtime behavior, evaluation order, nested reference identity and
   zero extra boundary allocations/copies. Existing optimized-build differences in
   earlier evidence remain explicit; O0 proofs are not universal optimization proofs.
4. If future work is authorized, establish behavior and lifecycle parity before
   measuring whether the candidate improves compilation or execution. Do not assume
   wrapper removal is beneficial solely from the template-free benchmark.

No further O2 benchmarking, ThinLTO work, linker integration, PHP-host optimization
or broad runtime redesign is part of the current slice. ThinLTO and linker options
remain non-blocking future topics. This note preserves the discussion for the next
review; it does not schedule implementation for tomorrow.
