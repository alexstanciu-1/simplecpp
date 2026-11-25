# Semantic diagnostics and the next compiler review
Doc Status: planning

Started: 2026-10-18. The [bounded inventory](diagnostic_inventory_2026_10_18/README.md)
contains 33 case groups. After user decisions and authorization to continue, 31 were
reviewed; self-value constructors and CH15-SOURCE-DETECTION-01 remain deferred.
The [execution report](../../../../specs/planning/results/compiler_diagnostic_review_2026_10_18_followup/README.md)
records eight deduplicated issues and proposed repair categories. No compiler fixes
or repair goal have been started. Discuss categories and separately authorize repairs.

## Compiler objective and authority

Develop `compiler/my-try/` as a semantic compiler for the supported Simple C++
language. It must determine source-language validity through its own frontend
and shared semantic analysis, with clear diagnostics at the relevant source
location. C++ remains a backend target; Clang compiles and validates emitted C++,
but must not substitute for source-language semantic checking.

Static analysis (STAN) should consume the same semantic model and facts so its
understanding of types, operations, ownership and lifetimes stays consistent with
compilation. This is a direction, not a claim that all analysis is implemented or
permission to resume the parked legacy STAN path.

The existing [v0.2 diagnostic policy](../../../../specs/v0_2_preparation_diagnostics.md)
owns early rejection semantics. Syntax errors belong to the frontend; statically
invalid resolved types and operations belong to shared preparation, before emission.
Value-dependent failures retain runtime guards. This review does not introduce
constant evaluation, flow narrowing, reachability analysis, or language extensions.
Top-level language contracts remain authoritative over test outcomes.

A source-language error first discovered by Clang is a validation gap. Clang
rejection of a valid source program may instead be a lowering, runtime binding,
or build configuration defect. Diagnose the owner before proposing a fix; do not
make valid source illegal merely to avoid a backend failure. Unsupported features,
invalid programs, runtime failures and internal compiler faults are distinct outcomes.

## Workflow and decision boundaries

1. Agree this inventory plan: bounded language families, evidence fields, expected
   outcomes, execution method and completion criteria. Do not begin an open-ended
   search for every possible compiler error.
2. Start a separate, explicitly requested goal to construct the case inventory.
   Read current contracts, catalog coverage and focused tests; use old reports as
   candidate seeds, not current findings. Deliver the inventory and unresolved
   expectation questions. Creating cases does not authorize running or fixing them.
3. Review the inventory's scope and ambiguous expectations, then explicitly start
   its execution. Pin the compiler revision and relevant local changes, runtime
   metadata, toolchain and configuration. Execute bounded batches, preserve results,
   minimize reproducible failures and deduplicate by the actual semantic owner.
   Do not silently modify the compiler during this review.
4. Catalog verified issues using the discussion categories below. Keep impact,
   confidence and dependencies separate from implementation complexity. Record
   blocked, inconclusive and already-resolved candidates too; they are not failures.
5. Start an explicitly requested repair goal for the agreed simple-fix set. Each
   fix needs a truthful owner and focused proof. Reclassify and stop that item if
   implementation reveals a design choice; continue independent authorized items.
6. Discuss the light-discussion items and record decisions, boundaries and proof
   requirements. Implement the agreed batch through a separately requested goal.
7. Discuss normal-discussion and red-flag items individually in chat. New structures,
   protocol changes or cross-owner restructuring require a concrete design decision
   before implementation. Do not hide them inside an easier batch.

Inventory-goal completion means all agreed families have explicit cases or justified
deferrals, expected outcomes have authority, and unresolved questions are visible.
Review completion means every frozen case has a result or attributed blocker, and
every finding has evidence and a proposed category. Neither means zero bugs or full
language completeness. New discoveries enter a follow-up queue rather than silently
expanding the current batch.

## Inventory shape

Use stable case IDs and group by source-language rule and semantic owner. Candidate
families include declaration/name resolution, type formation and constraints,
operators/conversions/mutation, calls/returns, containers, inheritance, and supported
ownership/reference/lifetime contracts. Add failure recovery and stale-output checks
where state retention matters. Select the exact families during inventory setup.

Each case records:

- ID, family, owning contract/section, supported-language boundary and candidate owner.
- Minimal authored source, project/module configuration and a nearby valid control.
- Expected phase and outcome: accept, source rejection, documented unsupported
  feature, or runtime check. Mark uncertain expectations for discussion before scoring.
- Expected diagnostic reason and source file/location; require useful attribution
  rather than exact prose unless wording is itself contractual.
- Required proof: frontend/preparation, emitted-C++ compile/link, execution, or
  incremental recovery. Select only the layers needed for that case.
- Provenance: originating report/test, revision and source hashes; status and evidence
  paths. On execution, record actual phase, diagnostic location, command and exit code.

Pair negative cases with valid controls to detect over-rejection. Test meaningful
siblings such as prefix/postfix increment and decrement when the existing contract
supports them; do not assume similar spelling implies identical implementation.
Check that failures publish no executable result or stale successful output where
the tested interface promises that guarantee.

## Evidence and focused execution

Follow the [shared runner](../testing.md) and local operating rules: the existing
12-slot task pool, ordered steps within each fixture, PHP-FPM with documented CLI
fallback. Bounded batches limit scope and output, not safeguard visibility.
Use fresh evidence directories and record the tested source state. A PHP-hosted
compiler emitting C++ is not a native compiler rebuild. Compile-only, link and run
results must remain separately labeled. Clang is a downstream cross-check, not the
authority for expected source-language meaning.

Retain minimal sources, case manifests, reproduction commands, hashes and relevant
diagnostics/results. Preserve generated text when needed to establish a lowering
failure. Keep binaries, caches and unrelated logs out of permanent evidence.
Use repository-relative references and durable evidence paths rather than depending
on a temporary snapshot. Summarize each batch by case IDs, outcomes and actionable
findings; avoid dumping whole generated programs or broad diagnostic streams.

Follow [focused tool use and Daybreak handling](../../../../AGENTS.md#focused-tool-use-and-daybreak-blocks).
The previous review encountered blocks according to the user; their cause is not
established. Precise context and limited data reduce unnecessary exposure but do
not guarantee that a block will not recur. Record the blocked action and supplied
reason, distinguish an execution block from a compiler failure, and continue
unaffected work. Never disguise, encode, split or reroute a blocked action to evade
a safeguard. If no authorized alternative exists, leave that case blocked with
the required next action stated explicitly.

## Issue catalog and repair categories

Each issue records the rule, reproducer/control IDs, expected versus actual behavior,
source attribution quality, evidence, suspected owner/root cause, impact, confidence,
dependencies, proposed repair boundary and proof required. Multiple failing cases
may belong to one issue. A speculative explanation is not a confirmed root cause.

| Category | Decision rule | Next action |
| --- | --- | --- |
| Simple fix | Contract and owner are clear; an established path can be completed without new structures, protocols, or semantic choices. A small local refactor is allowed if it clarifies that owner. | Include in the agreed simple-fix goal with focused regression/control proof. |
| Light discussion | A bounded implementation choice or small local behavior/flow adjustment needs agreement; no new retained structures or ownership protocol. | Discuss the choice, record the decision, then batch implementation in a goal. |
| Normal discussion | New or changed structures, retained facts, ownership links, or processing flows require design work. | Discuss one item at a time and agree a bounded implementation slice. |
| Red flag | Existing ownership/model cannot honestly represent the requirement; significant restructuring or coordinated cross-owner repair is needed. | Stop dependent repair work; explain affected areas, risks, validation cost and smallest viable options before seeking approval. |

Severity is independent of category: a simple fix can address a serious defect.
Missing `--` beside working `++` is only a simple fix after checking source semantics,
mutability, evaluation order and the reusable implementation path. Unclear semantics
or missing proof move an item out of the automatic repair set. Repair completion
requires the reproducer and valid control to pass at the relevant layer, diagnostic
attribution to be checked, and catalog status/evidence to be updated.

## Historical starting points

- [Preparation/emission boundary audit](preparation_emission_audit.md): earlier
  ownership findings and their recorded resolutions.
- [October 18 diagnostic archive](../../../../specs/planning/results/compiler_diagnostic_review_2026_10_18/README.md):
  candidate reproducers and historical evidence; revalidate against the new baseline.
- [Current catalog](../catalog/README.md): supported scope and deferred work.

Non-goals: repairing the legacy generator/STAN, reviving LLVM, changing runtime
semantics, broad fuzzing, performance benchmarking, or modifying deferred work from
other tasks. Any necessary scope expansion must be made explicit before proceeding.
