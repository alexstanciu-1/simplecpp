# v0.2 direction
Doc Status: planning

The active feature roadmap is [docs/catalog](catalog/README.md), including its full
examples, rule cards, progress and decisions. This entry retains the agreed direction;
the [original handoff](archive/handoff_catalog_v02.md) is historical.

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
separate signature/record and body owners; bodies are not symbols. C++ caches fragments
and assembles one `main.cpp`. See [model](architecture/MODEL.md),
[lifecycle](lifecycle/incremental.md) and [remaining debt](planning/incremental_strategy.md).

## Validation and proof boundaries

Use [AGENTS](../AGENTS.md) for the focused-test/publication policy and
[portability status](portability/conversion_review.md) for proof limits. Historical
native passes do not certify the current compiler. Feature evidence belongs in the
catalog or dated result records, not repeated global checkpoint summaries.
