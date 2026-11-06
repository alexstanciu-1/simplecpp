# Compact compiler development catalog

Doc Status: planning

Updated: 2026-11-06, after string equality.
Scope: the active `v0.2/compiler/` PHP compiler and its generated C++ programs.
Reference: [my-try catalog](../../../compiler/my-try/docs/catalog/README.md).

This is a feature checklist, not a copy of donor completion claims or structures.
Mined structures must be discussed before adoption. Current normative specs and
agreed v0.2 decisions take precedence over donor syntax, policy and architecture.
Runtime implementation alone does not mean the compiler supports a feature.
Statuses below reflect reviewed implementation/docs and existing proofs, not a new
catalog-wide test run. No whole chapter is claimed complete.

Next scalar work follows the [agreed family/representation and conversion plan](scalar_families_and_conversions.md),
with grouped implementation slices rather than individual operators. Byte/uint8 canonical identity is implemented; broader behavior remains pending.

## Compact inventory

**Partial** means a bounded compiler slice exists; **pending** means the source
feature still needs implementation. Numbers retain the donor chapter grouping,
not a mandatory dependency order.

| # | Area / donor reference | Active v0.2 position | Main remaining work |
| --- | --- | --- | --- |
| 01 | [Literals and locals](../../../compiler/my-try/docs/catalog/01_literals_locals.md) | Partial: int/int64, null, bool, byte, float/float64, strings; typed/inferred locals, copies, plain reassignment and int64/float64 compounds | Broader constant initializers, chained assignment; expression-dependent examples |
| 02 | [Expressions](../../../compiler/my-try/docs/catalog/02_expressions.md) | Partial: literal/name leaves, typed-initialization conversions, signed floating literals, grouping, same-type scalar and string equality comparisons, mixed int64/float64 ordering, equality and arithmetic, int64 and float64 arithmetic and generated-truthiness `!`/`&&`/`||`; explicit scalar casts, int64/float64 compounds (including float destination/int64 RHS), discarded-value int64/float64 updates and int64/float64 unary signs | Bitwise operators, string ordering/null comparisons, eager xor, broader casts/compound writes, value-producing increments, string operations |
| 03 | [Control flow](../../../compiler/my-try/docs/catalog/03_control_flow.md) | Partial: metadata-generated bool/int64/float64/string truthiness, braced if/elseif/else and while/do-while/for with bare break/continue, lexical body scopes and structural return completion | Unbraced bodies, numbered/labelled transfers, omitted/comma for clauses and foreach, ternary expressions, null/byte conditions and precise flow facts |
| 04 | [Functions](../../../compiler/my-try/docs/catalog/04_functions.md) | Partial: typed value parameters/returns for current types, nested/value calls, cross-file signatures, trailing literal defaults, mutable reference parameters and ordered argument conversion | General-expression defaults, terminating recursion proofs, variadics, read-only references/reference defaults, named arguments and signature inference; overloads remain unimplemented |
| 05 | [Dynamic boundaries](../../../compiler/my-try/docs/catalog/05_dynamic_boundaries.md) | Partial: null value and bounded metadata-driven scalar conversions and explicit casts | Nullable/Result, mixed/dynamic, broader casts, probes/coalescing and text conversions |
| 06 | [Containers](../../../compiler/my-try/docs/catalog/06_containers.md) | Pending in compiler | vector/hash registration and construction, reads/writes, append, key rules, iteration; mutation policy before mutation during iteration |
| 07 | [References and lifetime](../../../compiler/my-try/docs/catalog/07_references_lifetime.md) | Partial: mutable whole-storage reference parameters and forwarding; string value copies | Local aliases, read-only references, reference defaults/returns, lifetime rules, shared/unique/weak, transfers and cleanup |
| 08 | [Projects and symbols](../../../compiler/my-try/docs/catalog/08_project_symbols.md) | Partial: modules/loading, multifile entry, namespaces/use, explicit scalar globals, stable symbols, frontend reuse and preparation watches | Broader namespace/import cases, constant expressions, global writes/string globals, dependency artifact/version validation and broader incremental behavior |
| 09 | [Value types and enums](../../../compiler/my-try/docs/catalog/09_value_types.md) | Pending source-defined types | Enums, value semantics and enum operations; record storage with 10 |
| 10 | [Objects](../../../compiler/my-try/docs/catalog/10_objects.md) | Pending | Classes/structs, fields, construction, methods, static members, access and destruction |
| 11 | [Inheritance](../../../compiler/my-try/docs/catalog/11_inheritance.md) | Pending | Base/interface/trait relationships, member indexes, overrides, dispatch and access; apply the agreed declaration preparation plan |
| 12 | [Closures](../../../compiler/my-try/docs/catalog/12_closures.md) | Pending | Callable types/values, invocation, value captures, reference captures and escape/lifetime rules |
| 13 | [Errors and cleanup](../../../compiler/my-try/docs/catalog/13_errors.md) | Pending source exception statements | throw/try/catch/finally, typed error contracts and cleanup across exits |
| 14 | [Output and build integration](../../../compiler/my-try/docs/catalog/14_output_runtime.md) | Partial: int/string echo, public runtime APIs, C++ emission, Ninja/mold/PCH panel and timings | Broader output, source/runtime API binding, artifact reuse/grouping and diagnostic improvements as needed |
| 15 | [Debt review](../../../compiler/my-try/docs/catalog/15_review.md) / [details](../../../compiler/my-try/docs/catalog/15_blocking_debt.md) | Ongoing review, not an imported implementation queue | Reconcile applicable debt by owner; do not import donor-only regressions, cache machinery, native-portability work or completion gates |

## Start with 01: literals and locals

Checked items mean the bounded concept is supported, not every original donor
spelling. Most donor examples use inferred declarations; our typed equivalents do
not prove those original examples. See [types](../../compiler/docs/types.md),
[strings](../../compiler/docs/strings.md) and [assignments](../../compiler/docs/assignments.md).

- [x] Integer, boolean and floating literals in typed locals (`LIT-INT-001`, `LIT-BOOL-001/002`, `LIT-FLOAT-001`).
- [x] Single/double quoted and empty strings, bounded escapes (`LIT-STR-001/002/003`).
- [x] Typed local initialization (`TYPE-VAR-001`), plus null and distinct byte in our current type model.
- [x] Reads, copies, sequential copy chains and reassignment of existing locals (`VAR-ASSIGN-001`, `VAR-REASSIGN-001`, typed forms of `VAR-CHAIN-002/003`).
- [x] Reject forward local reads and cpp/js writes before declaration; preserve source-order value facts (`VAR-ORDER-001`, bounded local isolation from `NOTE-033`).
- [x] **01-A: Inferred local declarations.** PHP first-write declaration, cpp `auto`, js `let`; reject null inference, preserve byte reference types, no widening. [Rules and proofs](../../compiler/docs/inference.md).
- [x] **01-B: Named constants.** Typed literal-only declarations, exact cross-file lookup/imports and value-sensitive invalidation; see [the bounded slice](../../compiler/docs/constants.md). Runtime named constants and general constant expressions remain deferred.
- [x] **01-C: Grouping and int64 arithmetic.** Binary `+ - * / %`, derived initialization and plain self-updates; see [arithmetic](../../compiler/docs/arithmetic.md). Logical `!`/`&&`/`||` now reuse generated truthiness; see [logical expressions](../../compiler/docs/logical.md).
- [ ] **01-D: Chained assignment.** Discuss result type, conversion boundaries and single evaluation before enabling assignment expressions (`VAR-CHAIN-001`).
- [ ] **01-E: Identifier/context checks.** Audit flavor-specific identifier examples; parameter-dependent examples await chapter 04 (`IDENT-VAR-001`, `NOTE-021`).
- [ ] Reconcile remaining chapter-01 notes and explicitly defer legacy annotation spelling (`TYPE-VAR-005`); do not silently adopt it.

Typed function delivery: [model, contract and proofs](../../compiler/docs/functions_slice.md).

Explicit scalar casts now have a bounded [implementation and proof](../../compiler/docs/casts.md).

Int64 compound statements now use the [shared assignment/operator path](../../compiler/docs/compounds.md).

Compound for headers now reuse the [same assignment lowering](../../compiler/docs/for.md#compound-header-extension--2026-11-05).

Discarded-value int64/float64 updates now share [storage-write ownership](../../compiler/docs/updates.md).

Int64 unary `+`/`-` now use [metadata-generated handlers](../../compiler/docs/unary.md), with one-visit operand preparation.

Possible next discussion: prefix/postfix update values, with old/new snapshots and evaluation-order debt reviewed first. This remains a proposal. Chained assignment can stay separate;
do not require all chapter-01 carryovers before starting bounded expressions.

## Working rhythm

For each selected item: discuss the smallest useful source example and semantics,
review donor ideas and current owning specs, agree structure changes, implement an
end-to-end slice, then update this checklist with the proof/debt link. Keep the AST
as fact owner, metadata-generated type behavior and source-order correctness.
Include incremental edit/reset checks when facts or symbols change. Native proofs
report Clang frontend, LLVM and linker work with build/cache settings. Chapter 14
is continuous, not postponed to the end; safety prerequisites from 07 apply when
needed. LLVM, old STAN and transplanting the donor's architecture are not authorized
by this catalog.

Float64 arithmetic slice: same-type `+ - * /` and unary signs are covered by
[the implementation and proof](../../compiler/docs/float_arithmetic.md).
[Mixed int64/float64 arithmetic](../../compiler/docs/mixed_arithmetic.md) now reuses
the promotion path; float remainder remains deferred.
Same-type [float compound statements](../../compiler/docs/float_compounds.md) now reuse
the shared assignment mechanism, including for headers.

[Float64 prefix/postfix update statements](../../compiler/docs/float_updates.md) now
reuse the same generated update mechanism, including for headers; expression results remain deferred.

[Conversion contexts and mixed numeric ordering](../../compiler/docs/conversion_contexts.md)
now reuse generated conversion behavior; compounds retain same-type admission.
