# Compiler development catalog
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

Implementation home: `compiler/my-try`.

## Strict mode first

The active development sequence targets strict-mode PHS. Use source types such as
`$x string = "test";` and `$items vector<int> = [];`, not legacy comment annotations.
Follow the [strict quick-learn](../../../../specs/simple_cpp_php_strict_quick_learn.md)
and strict library/profile contracts when preparing each working example.

Progress-table examples are working examples and may adapt imported spelling.
The imported cards and source ledger preserve the originals for provenance, not
as instructions to implement legacy syntax first. Legacy annotation-placement and
conflicting-annotation rules are `deferred-legacy` in separate table sections.
For less straightforward ownership/reference forms, define the strict example when
discussing the feature rather than guessing a replacement contract. Deferring the
old spelling does not itself defer the underlying language concept.

This is a prioritization and authoring decision, not blanket rejection of legacy
inputs or a claim that every imported example has passed a strict-mode audit.
Validate library names and other inherited source assumptions per selected example.

Use one example at a time: discuss its behavior and C++ solution, split it if it
combines several concepts, agree the general rule, implement it, and record proof.
Start with [integer literals and local variables](01_literals_locals.md#lit-int-001),
not classes. Existing compiler capabilities can be verified and recorded as we reach
them; there is no requirement to implement them again.

## Canonical language model

Agreed 2026-09-25: PHS/PHP++ is the reference surface for the full canonical
Simple C++ model. Other language frontends shape their ASTs toward that model
and may expose subsets. The common path is source language -> canonical
PHS-shaped AST -> shared preparation -> C++ emission.

The AST represents language concepts rather than PHP token spelling. Frontends
preserve their intended source behavior when mapping to those concepts. A feature
that cannot be expressed faithfully needs discussion of a canonical-model extension
or remains unsupported. No separate universal language abstraction is planned.

Build this path through strict PHS first, accepting straightforward legacy forms
that share the model. Additional frontends are later work. Generation comes first;
comprehensive validation and semantic invalidation follow in a second pass. See the
[handoff decision](../handoff_catalog_v02.md#agreed-multi-language-direction).

## Development order

The order introduces small source-to-output slices before composite language features.
A minimal program entry, runtime linkage and diagnostic path support the first slice;
the final output chapter is consulted from the start, not postponed wholesale.
Basic function work can proceed alongside control flow. Later rows in a chapter may
need another chapter first (for example, container-valued function arguments).
Discuss and split those rows rather than expanding a small slice silently.

| Stage | Focus | Rule IDs | Prose sections |
| --- | --- | ---: | ---: |
| 01 | [Literals and local variables](01_literals_locals.md) | 20 | 5 |
| 02 | [Expressions and scalar operations](02_expressions.md) | 54 | 6 |
| 03 | [Conditions and control flow](03_control_flow.md) | 12 | 1 |
| 04 | [Functions, parameters and calls](04_functions.md) | 21 | 3 |
| 05 | [Null, mixed, wrappers and conversions](05_dynamic_boundaries.md) | 15 | 8 |
| 06 | [Arrays, vectors, hashes and iteration](06_containers.md) | 28 | 5 |
| 07 | [References, ownership and lifetime](07_references_lifetime.md) | 18 | 4 |
| 08 | [Files, namespaces and symbol resolution](08_project_symbols.md) | 31 | 5 |
| 09 | [Value types and enums](09_value_types.md) | 7 | 0 |
| 10 | [Classes, properties, construction and methods](10_objects.md) | 78 | 2 |
| 11 | [Inheritance, interfaces and dispatch](11_inheritance.md) | 22 | 0 |
| 12 | [Closures and callable values](12_closures.md) | 13 | 0 |
| 13 | [Exceptions and cleanup](13_errors.md) | 3 | 1 |
| 14 | [C++ artifacts, runtime integration and diagnostics](14_output_runtime.md) | 8 | 24 |

## Per-example workflow

1. Choose an entry and read its source example, old C++ result, preconditions and notes.
2. Discuss source semantics and the desired v0.2 C++ solution. Consult current owning
   specs when the imported material conflicts or describes a v1 workaround.
3. Split combined cases using child IDs such as `FUNC-DECL-002.a`; retain the parent
   ID and its source links. Name dependencies and the slice's non-goal.
4. Record the agreed source example, expected result/diagnostic, semantic owner,
   facts needed by the emitter, and target C++ before implementation. Include the
   compilation-cost review below when agreeing the target form.
5. Implement the reusable concept in `my-try`; track frontend, C++ S2S and LLVM
   independently. LLVM delivery stays deferred unless the user explicitly requests it;
   record PHP/native compiler execution and generated-program evidence separately.
6. Update the chapter's progress row with a durable test/result link and any blocker.
   A rejected construct is complete only when its agreed diagnostic is proved.

Each card has a **v0.2 decision / target C++** area to expand during discussion.
Do not overwrite the imported source versions when recording our decisions.

## Design C++ for efficient native compilation

Native edit-to-build latency is the current primary performance priority, ahead
of STAN speed and expanding resolved-AST feature coverage. Preserve correctness.
Address the largest demonstrated costs first:

1. Modular output with narrow type and callable headers.
2. Stable implementation units and names, avoiding unrelated artifact changes.
3. Precise dependencies so only genuinely affected objects rebuild.
4. Publish only changed final content, preserving unchanged bytes and timestamps.
5. Investigate remaining compilation/link costs using measurements.

The [latency handoff](../../../../specs/planning/s2s_next_generator_technical_handoff.md)
provides the architectural baseline. Its main demonstrated gain comes from reducing
recompilation scope. Assess performance changes by representative edit-to-build time,
rebuilt objects and invalidation reasons; separate frontend/generation, native
compilation and linking when measuring.

Do not benchmark every example or block development on small C++ spelling choices.
The comparison between `int_t a = 10;`, `int_t a = static_cast<int_t>(10);` and
`auto a = static_cast<int_t>(10);` illustrates a possible later investigation. It is
not current optimization work or a prerequisite for agreeing the literal example.
Choose a straightforward semantically correct form; record a small cost question as
deferred if useful. Shorter code or less deduction does not establish a speedup.

When profiling identifies a worthwhile candidate, record its alternatives, semantic
constraints, expected benefit and comparative evidence. Test before adopting a
performance change under comparable toolchain, flags, headers/PCH, concurrency and
cache conditions; repeat enough to distinguish noise. Preserve types, conversions,
evaluation order, checks, copies, aliasing, lifetimes and diagnostics, and assess
runtime-performance tradeoffs. Do not infer a whole-build gain from an isolated
microbenchmark. Measurement effort should follow the size of the actual bottleneck.

## Progress meanings

- Status: `pending-discussion`, `discussing`, `agreed`, `split`, `deferred`,
  `deferred-legacy`.
- PHP input example: strict working example where adapted, otherwise an imported
  example awaiting review; differing source examples may be retained together.
  Prose-only entries show `example pending` until we define one.
- Frontend: parsing, symbol/type resolution and the semantic checks/facts needed
  by the selected feature. This column does not require native code generation,
  complete STAN coverage, or a complete direct compiler backend.
- C++ S2S: lowering the frontend's facts into the agreed C++ form and proving its
  behavior through the native C++ toolchain.
- LLVM: the optional direct LLVM path for this example, tracked independently of
  C++ S2S. Default to `deferred`; activate only on an explicit user request.
  This is a scope decision, not a claim that existing LLVM support is absent.
- Frontend, C++ S2S and LLVM states: `unverified`, `missing`, `in-progress`, `proved`,
  `blocked`, `deferred`, `not-applicable` (explain the last three).
- Proof / blocker: test path, evidence link, child IDs, or a concrete missing dependency.

Frontend and C++ S2S cells begin **unverified**, including rules marked supported
in an old catalog. LLVM defaults to **deferred**. This avoids claiming either missing or completed my-try behavior
without checking it. Status and implementation evidence are independent.

For the v0.2 S2S goal, the shared frontend must provide the facts needed by C++
generation; it need not continue through direct LLVM lowering. A selected v0.2
example can be complete with Frontend and C++ S2S proved and LLVM explicitly
deferred. Deferring LLVM does not defer the frontend semantics needed by C++ S2S.
Existing LLVM capabilities can be verified and recorded as we reach them, and
selected new examples can target both backends. Do not require LLVM parity for
every catalog row or resume the broader direct-compiler roadmap implicitly.

## Consolidation and authority

This catalog consolidates these four sources:

- [Rule catalog](../../../../generators/php/specs/catalog.md)
- [Rebuilt rule catalog](../../../../generators/php/specs/rules_catalog.md)
- [Detailed general rules](../../../../generators/php/specs/rules.md)
- [Original example discussion workflow](../../../../generators/php/specs/catalog_rebuild_workflow.md)

Identical rows under one ID are combined with all origins retained. Differing rows
under one ID remain imported versions in the same card. IDs marked covered by
another ID stay searchable; source aliasing does not prove implementation.
The short `NS-GLOBAL-EXPLICIT-001` row has no invented example. Non-table prose is
retained as NOTE entries, including overlaps, caveats and historical material.
Source line numbers record the intake location and may drift as originals change.

The [source merge ledger](source_merge.json) records exact imported cells, prose,
source fingerprints and destination chapters. It is provenance, not a second
progress tracker. Edit chapter progress and decisions directly; do not regenerate
over reviewed work. The original catalogs are unchanged.

This is a planning/work-tracking catalog. Current root specifications retain semantic
authority. In particular, old “type-blind generator” rules are v1 architecture input,
not requirements to keep the new compiler type-blind. Old snippets may be abbreviated,
legacy syntax, or conflicting; they are not automatically compilable acceptance tests.

The separate `specs/php/catalog.md` and `canonical_examples.md` describe authoring
practice rather than paired lowering cases; they remain supporting references.
This inventory is not a complete v0.2 language roadmap: source records, templates,
new mixed-property behavior and incremental publication may need new examples when
we reach them. Add those deliberately rather than treating absence as rejection.

Intake totals: **402 source rows → 330 distinct rule IDs**, with **13 IDs retaining differing versions** and **64 prose sections**.
