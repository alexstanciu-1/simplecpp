# v0.2 direction
Doc Status: planning

The active feature roadmap is [docs/catalog](catalog/README.md), including its full
examples, rule cards, progress and decisions. This entry retains the agreed direction;
the [original handoff](archive/handoff_catalog_v02.md) is historical.

## Current chapter handoff — 2026-10-16

[Chapter 13 is finalized within its implemented bounds](catalog/13_errors.md#bounded-closeout-and-native-gate--2026-10-16).
Its gate passed PHS conversion and native compiler compilation, 68 feature-program
parity/execution proofs, 52 rejection/recovery comparisons and four native edit/repair
proofs. NOTE-047 is reconciled; uniform source-location diagnostics and the other
[explicit extensions](catalog/15_blocking_debt.md#chapter-13-deferred-work) remain deferred.
This is focused chapter evidence, not a full cumulative native sweep.

[Chapter 14 is closed within its implemented bounds](catalog/14_output_runtime.md#bounded-closeout-and-native-gate--2026-10-16).
The requested compiler PHS conversion/native build and focused native artifact,
publication, runtime-linkage and incremental gate pass. Legal source shadowing and
visible warnings remain intentional. [Deferred extensions](catalog/15_blocking_debt.md#chapter-14-closeout-deferrals)
keep their owners. Next: prioritize Chapter 15 and agree its first bounded slice.

The [Chapter 12 closeout](catalog/12_closures.md#bounded-closeout-and-current-handoff--2026-10-16)
retains its separate evidence limits; this CH13 gate does not retroactively certify
complete CH12 native coverage. LLVM remains parked.

## Agreed v0.2 scope

Progress the frontend and C++ S2S first. General validation/STAN follows in a second
pass. LLVM is parked for regressions and must consume shared semantic facts before
its development resumes. Templates await review after ordinary functions/structs.

## Agreed multi-language direction

PHS/PHP++ is the reference surface for the full canonical model. Other frontends
normalize toward its concepts while preserving their own source behavior. Implement
strict PHS first and accept easy legacy forms sharing that model. Do not invent a
separate universal AST abstraction or infer support from a legacy example alone.

## Agreed type and scope direction

Language-defined types are hard-coded; runtime/library definitions may later come
from JSON. Keep current scalar metadata compact and enum-based. Global scope parents
to LANGUAGE+RUNTIME through the same encapsulated scope API. Reserved-name enforcement
is deferred to validation, not established as unrestricted shadowing semantics.

## Current retained model and execution

Specialized AST nodes own typed fields, saved names and applicable facts. Collection
runs during parsing; resolution/preparation follows the join. Incremental work has
separate declaration (signature/record/constant) and body owners; bodies are not
symbols. C++ caches fragments and assembles narrow declaration headers, per-source
implementation units and `entry.cpp`. See [model](architecture/MODEL.md),
[lifecycle](lifecycle/incremental.md) and [remaining debt](planning/incremental_strategy.md).

## Validation and proof boundaries

Use [AGENTS](../AGENTS.md) for the focused-test/publication policy and
[portability status](portability/conversion_review.md) for proof limits. Historical
native passes do not certify the current compiler. Feature evidence belongs in the
catalog or dated result records, not repeated global checkpoint summaries.
