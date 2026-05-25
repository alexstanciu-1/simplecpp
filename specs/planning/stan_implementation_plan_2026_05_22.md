# STAN Implementation Plan
Doc Status: planning

Date: 2026-05-22

Purpose:
- capture the first implementation shape for STAN
- turn the higher-level static-analysis architecture into an executable pipeline
- keep implementation discussion separate from the broader architecture note

This note is planning only.
It does not change current semantics or generator contracts by itself.

Related note:
- [static_analysis_symbol_and_type_awareness_plan_2026_05_22.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/planning/static_analysis_symbol_and_type_awareness_plan_2026_05_22.md)

## Current Framing

STAN is the working name for the static analysis subsystem.

Current intended role:

- advisory semantic analysis first
- shared front-end path with the S2S generator
- no duplicated parser/front-end logic
- per-source cache reuse
- reusable analysis infrastructure for future Simple C++ language frontends

## Phase 1 Goal

Phase 1 should build the first end-to-end pipeline for:

- full analysis run
- incremental analysis run
- shared front-end extraction path
- per-source cache generation

The key design rule is:

- full and incremental runs must share the same semantic extraction code

Incremental mode is an optimization around invalidation and reuse.
It must not become a second semantic implementation.

## Phase 1 Pipeline

Current proposed flow:

1. STAN starts an analysis session
2. STAN reads the root project graph
3. STAN resolves project dependencies, runtime selections, modules, and runtime shallow-source inputs
4. STAN enumerates all participating source files
5. for each source file, STAN calls shared S2S front-end extraction code
6. the shared front-end path runs the pre-tokenizer and other existing front-end steps needed for declaration extraction
7. the shared front-end returns structured extracted facts for that source file
8. STAN builds the project-wide semantic graph from those extracted facts
9. STAN writes per-source cache artifacts

## Step Detail

### 1. Start analysis session

STAN should open one analysis session that fixes the semantic environment for the run.

That environment should include at least:

- root project
- active entrypoint context if relevant
- active frontend language/profile
- active runtime modules
- dependency graph
- analyzer schema/version

This environment becomes part of cache validity.

### 2. Read the root project graph

STAN should not invent a separate project loader if the build/generator path already has one that can be shared cleanly.

Preferred direction:

- one shared project-graph loading path
- STAN consumes it
- S2S generation consumes it

The graph should include:

- root project
- included Prism project dependencies
- declared libraries
- runtime language selections
- runtime module selections
- project-local source roots

### 3. Resolve runtime shallow sources and project environment

STAN should load not only authored project sources, but also analyzer-readable shallow sources or equivalent semantic inputs representing runtime-visible declarations.

Current direction:

- runtime library surfaces should be readable in PHP++ shape or a closely-related derived semantic shape
- STAN should read those surfaces rather than maintain a second hand-written runtime semantic universe

This step should also finalize:

- strict vs legacy profile behavior
- module-gated symbol visibility
- project-config-enforced semantic environment

### 4. Enumerate all participating source files

The source set should include:

- root project source files
- dependency project source files that participate in visibility/composition
- runtime shallow-source files or equivalent analyzer-readable runtime semantic inputs

The file list should be normalized into one consistent source-unit set for the run.

### 5. STAN calls shared S2S front-end extraction code per file

This is a central design point.

Current preferred direction:

- STAN drives orchestration
- shared S2S front-end code performs source extraction work
- STAN consumes the extracted facts

Important nuance:

- STAN should not depend on arbitrary generator internals
- the S2S side should expose a stable extracted-facts interface

So the intended relationship is:

- STAN uses shared front-end extraction
- not STAN scraping random generator state

### 6. Shared front-end path performs pre-tokenizer and declaration extraction

For phase 1, the shared front-end path should reuse the current trusted source-processing chain where practical.

That likely includes:

- pre-tokenizer
- tokenization/parsing steps already relied upon by the S2S path
- extraction of declarations and other file-local semantic inputs

The phase 1 goal is not full semantic resolution here.

The phase 1 goal is:

- one file-local extracted-facts pass shared by both future generation and analysis use

### 7. Shared front-end returns extracted facts

The front-end should return structured extracted facts, not lowering decisions.

Phase 1 extracted facts should likely include:

- source-unit identity
- declared functions
- declared classes/interfaces
- declared methods/properties/constants
- namespace/import information
- local typed declaration forms where recognized
- raw type spellings to be normalized later
- source-local diagnostics from extraction/parsing
- semantic dependency hints discovered at extraction time

Possible early semantic dependency hints:

- `extends`
- `implements`
- imports/use references
- named references that can be recorded before full graph linking

### 8. STAN builds the project-wide semantic graph

After all file-local extracted facts are collected, STAN should build the shared semantic graph.

Phase 1 graph-building work likely includes:

- source-unit indexing
- declaration indexing
- duplicate declaration detection
- source-to-source dependency edges
- symbol ownership mapping
- profile/module-aware runtime symbol visibility attachment

This is where STAN begins turning file-local facts into project-wide semantic structure.

### 9. STAN writes per-source cache artifacts

After successful extraction and graph participation, STAN should write cache artifacts per source unit.

Current preferred first format:

- PHP-native cache data using `var_export()`

That is a good v1 fit because:

- it matches current repository habits
- it is easy to inspect
- it is easy to version
- it keeps the first implementation simple

## Full And Incremental Sharing Rule

Both run modes should share:

- project graph loading
- source enumeration
- shared front-end extraction
- extracted-facts shape
- semantic graph build logic

Incremental mode should add only:

- cache loading
- dirtiness detection
- dependency-based invalidation
- selective recomputation

This rule should be kept strict.

## Cache Model For Phase 1

Each source file should have its own cache artifact.

Initial cache contents may include:

- extracted declarations
- source-local imports/use data
- recognized local typed declarations
- semantic dependency edges discovered for that source
- extraction diagnostics
- environment key fields relevant for validity

## Editor Roadmap

Goal:

- turn STAN from a good CLI batch analyzer into the semantic engine for editor diagnostics and navigation
- support a future VS Code extension through a stable service boundary
- keep the LSP/editor layer thin by putting semantic truth inside STAN itself

The editor roadmap should be implemented in three layers:

- STAN core service
- LSP bridge
- VS Code extension

### Roadmap Principles

- STAN remains the semantic source of truth
- the LSP layer translates protocol concepts, not semantic meaning
- the VS Code extension handles UX and workspace integration, not analysis
- unsaved editor buffers must be analyzable without writing files to disk
- true incremental invalidation is required before editor integration is considered healthy

## STAN Core Service

Purpose:

- expose STAN as a long-lived incremental semantic service instead of only a CLI command

### Core Service Phase 1

Goal:

- refactor current STAN run orchestration into a reusable session/service object

Work:

- define `StanWorkspaceSession`
- move project graph loading, runtime/profile preparation, file cache loading, and semantic pass orchestration behind that session
- allow one workspace to stay warm across many requests
- separate CLI printing from semantic result production

Deliverables:

- `StanWorkspaceSession`
- service-safe result DTOs instead of CLI-shaped arrays only
- no behavior change required yet for `scpp stan`

### Core Service Phase 2

Goal:

- support true document lifecycle operations

Work:

- add APIs for:
  - open document
  - update document text
  - close document
  - analyze document
  - analyze affected graph
- maintain in-memory overlays for unsaved buffers
- distinguish on-disk source state from in-memory editor state
- make dependency invalidation operate from the changed source unit outward

Deliverables:

- overlay-aware source provider
- in-memory per-document cache entries
- affected-files recomputation instead of whole-workspace invalidation

### Core Service Phase 3

Goal:

- expose editor-grade semantic queries

Work:

- add query APIs for:
  - diagnostics by file
  - document symbols
  - workspace symbols
  - definition lookup
  - hover info
  - references
- ensure returned results include stable symbol identity and exact source spans

Deliverables:

- `StanSemanticQueryService`
- declaration/reference location model with line and column spans
- stable diagnostic/category ids

### Core Service Phase 4

Goal:

- make service performance and correctness observable

Work:

- add timing breakdowns for:
  - project graph load
  - file extraction
  - dependency invalidation
  - semantic passes
  - individual query categories
- add cache hit/miss counters
- add service-mode integration tests using unsaved-buffer overlays

Deliverables:

- timing hooks
- incremental correctness tests
- performance baselines for editor scenarios

## LSP Bridge

Purpose:

- translate between VS Code/editor LSP traffic and STAN service operations

### LSP Phase 1

Goal:

- diagnostics-only language server

Work:

- implement LSP server process around `StanWorkspaceSession`
- support:
  - `initialize`
  - `initialized`
  - `shutdown`
  - `textDocument/didOpen`
  - `textDocument/didChange`
  - `textDocument/didClose`
  - `textDocument/didSave`
- publish diagnostics from STAN after document updates

Deliverables:

- `stan-lsp` executable
- diagnostics publishing for open documents
- workspace config bootstrap from `prism.json`

### LSP Phase 2

Goal:

- semantic navigation features

Work:

- implement:
  - `textDocument/documentSymbol`
  - `workspace/symbol`
  - `textDocument/definition`
  - `textDocument/hover`
- map STAN symbol ids and source spans into LSP ranges and locations

Deliverables:

- first editor navigation support
- hover/type display from STAN semantic facts

### LSP Phase 3

Goal:

- richer editor assistance

Work:

- implement:
  - `textDocument/references`
  - `textDocument/signatureHelp`
  - completion hooks where STAN facts are already strong enough
- optionally add `codeAction` for a small set of high-confidence diagnostics later

Deliverables:

- references
- signature help
- scoped completion groundwork

### LSP Phase 4

Goal:

- harden the protocol layer for real editor use

Work:

- debounce diagnostics intelligently
- avoid full re-publish storms on unrelated files
- add cancellation support for slow requests
- add logging/tracing switches for debugging the extension

Deliverables:

- stable interactive behavior under frequent edits
- traceable service logs

## VS Code Extension

Purpose:

- provide a polished editor experience on top of the STAN LSP bridge

### Extension Phase 1

Goal:

- working diagnostics extension for Simple C++ / PHP++ files

Work:

- define language activation for relevant file types
- launch `stan-lsp`
- detect workspace root/project root
- surface diagnostics in Problems and inline editor decorations
- expose a simple status item showing STAN readiness / busy state

Deliverables:

- first installable extension
- live diagnostics for open files

### Extension Phase 2

Goal:

- navigation and semantic inspection UX

Work:

- enable go-to-definition
- enable hover/type info
- enable outline/document symbols
- add command palette actions such as:
  - restart STAN
  - show STAN trace output
  - reanalyze workspace

Deliverables:

- usable navigation workflow
- basic operator controls

### Extension Phase 3

Goal:

- workspace polish and project awareness

Work:

- handle multi-root workspaces
- surface profile/config errors clearly
- expose strict vs legacy project context in the UI
- allow extension settings for:
  - diagnostics debounce
  - trace level
  - STAN executable path

Deliverables:

- workspace-ready extension behavior
- operator-facing configuration

### Extension Phase 4

Goal:

- quality and release readiness

Work:

- add extension integration tests
- verify behavior with unsaved buffers, large projects, and project dependency graphs
- document installation and troubleshooting
- measure perceived latency for:
  - first diagnostics
  - post-edit diagnostics
  - definition/hover

Deliverables:

- extension test coverage
- release checklist
- user documentation

## Recommended Build Order

1. STAN core service phase 1
2. STAN core service phase 2
3. LSP phase 1
4. VS Code extension phase 1
5. STAN core service phase 3
6. LSP phase 2
7. VS Code extension phase 2
8. performance hardening across all three layers

## Minimum Viable Editor Milestone

The first milestone that is worth dogfooding internally should be:

- persistent STAN workspace session
- unsaved-buffer overlays
- selective invalidation for changed files
- diagnostics publishing in VS Code
- line/column diagnostic ranges

Without those pieces, STAN may still be useful in the CLI, but it will not yet feel like a healthy editor analyzer.

Potential format shape:

```php
return [
	"schema_version" => 1,
	"source_path" => "...",
	"source_hash" => "...",
	"profile" => "strict",
	"modules" => ["json", "filesystem"],
	"dependencies" => [
		["kind" => "extends", "target" => "..."],
	],
	"decls" => [
		// structured exported facts
	],
	"diagnostics" => [
		// extraction-phase diagnostics
	],
];
```

## Prioritized Checklist

### STAN Advisory Phase

The advisory phase should prioritize diagnostics that are:

- clear to explain in source terms
- likely to turn into confusing generated-C++ compile failures later
- useful even before strict enforcement exists

Recommended order:

1. Declaration and symbol surface completeness

- cross-file symbol indexing
- duplicate declaration detection
- unresolved class/function/method/property/static symbol diagnostics
- runtime/profile/module visibility diagnostics

2. Single-type discipline for locals and properties

- local type morph warnings on straight-line reassignment
- local type morph warnings on `if` / `elseif` / `else` merges
- property assignment type warnings against declared property type
- preserve separate warnings for `mixed_t` / `dynamic_t` domain misuse later

3. Chained expression and receiver validation

- property access validity
- method call validity
- null/scalar receiver diagnostics
- chained expression type propagation through locals
- ambiguity diagnostics when merged candidates disagree on later member type

4. Function and method signature checking

- argument count checks
- argument type vs parameter type checks
- return expression type vs declared return type
- missing method return type usage diagnostics where STAN depends on it

5. Inheritance and class-shape checks

- parent/interface exists
- inherited member lookup validity
- duplicate or invalid redeclaration checks
- required interface method presence

6. Control-flow checks with strong compile impact

- missing return on non-void functions
- maybe-uninitialized local usage
- obvious unreachable invalid paths where detection is simple and reliable

7. Runtime and project integration checks

- active module/profile symbol availability
- dependency-project symbol visibility
- runtime shallow declaration drift checks

### Pre-Compile Blocking Phase Later

The later blocking phase should start only after advisory diagnostics are stable enough that users trust them.

Recommended blocker rollout order:

1. Structural blockers

- parse/extraction failure
- duplicate declarations
- unresolved required symbols
- invalid parent/interface references

2. Type-shape blockers

- local type morphs
- property assignment type violations
- return type mismatches
- argument-to-parameter mismatches

3. Member access blockers

- missing property access
- missing method calls
- null/scalar receiver misuse
- invalid static-vs-instance access

4. Control-flow blockers

- missing required return
- maybe-uninitialized local usage where STAN confidence is high

5. Runtime/profile blockers

- use of symbols unavailable in the active profile
- use of symbols unavailable in selected runtime modules

### Blocking Gate Rule

When STAN eventually becomes part of a pre-compile gate, the initial blocking set should stay intentionally small.

Preferred rollout rule:

- advisory-first for broad categories
- block only the highest-confidence diagnostics first
- expand blocking only after repeated real-project feedback confirms low false-positive risk

### Practical Priority Rule

When choosing the next feature to implement, prefer diagnostics that:

- replace hard-to-read C++ compiler failures with direct source-language messages
- use already-available declaration facts before requiring deeper inference
- improve both STAN advisory value and future pre-compile usefulness with the same implementation

This shape is illustrative only.

## Dependency Memory In Phase 1

Per-source dependency memory is a core requirement for safe incremental analysis.

Current first-class dependency categories should start with:

- `extends`
- `implements`
- namespace/import dependencies
- referenced source-level declarations when resolvable
- runtime/profile/module semantic dependencies when they materially affect visibility

Important rule:

- a source cache should remember which other source units affect its semantic interpretation

This is necessary so incremental invalidation can be precise rather than coarse.

## Duplicate Declaration Simplification

Simple C++ benefits from a strong simplifying rule:

- same-scope same-name declarations are errors
- they are not overload sets to be ranked later

Phase 1 should lean on that aggressively.

That means STAN should treat duplicate declarations as:

- structural semantic errors

not as:

- candidates for overload-resolution machinery

This should simplify:

- symbol indexing
- call resolution
- duplicate diagnostics
- cache invalidation

## Dynamic Carrier Simplification In Phase 1

Current simplifying rule from discussion:

- `mixed_t` nested content stays in the dynamic-carrier world
- `dynamic_t` nested content stays in the dynamic-carrier world

Phase 1 should use that to avoid fake precision.

That means:

- dynamic recursion is tracked as dynamic-domain recursion
- typed narrowing should happen only at real typed boundaries
- `unknown` should not silently collapse into `mixed`

## Unknown In Phase 1

`unknown` may still exist internally in phase 1.

But current direction is:

- `unknown` is a temporary unresolved state
- it should trigger an advisory warning when it survives to user-visible analysis output
- it should not become a comfortable steady-state result for ordinary type-aware flows

## Recommended First Return Contract From Shared Front-End

The most important implementation boundary in phase 1 is the file-local extracted-facts contract returned from shared S2S front-end code.

That contract should likely contain:

- source identity
- declaration list
- namespace/import list
- raw declared type forms
- dependency hints
- extraction diagnostics

That contract should likely avoid:

- generated C++ details
- lowering decisions
- runtime helper routing choices
- whole-program speculative conclusions

## Open Questions For This Implementation Note

1. What exact PHP function/class boundary should shared front-end extraction use first?
2. Where should the stable extracted-facts contract live in the repo?
3. Should runtime shallow sources be checked in, generated ahead of time, or generated on demand?
4. Which facts are cacheable immediately, and which should remain rebuilt until the graph layer stabilizes?
5. What is the first minimal dependency-edge set beyond `extends`?
6. Should STAN phase 1 write one cache file per source unit, or shard by project as a wrapper over per-source entries?

## Short Takeaway

Part 1 sounds strong.

The healthiest implementation shape is:

- STAN owns orchestration
- shared S2S front-end code owns file-local extraction
- STAN owns cross-file graph building and per-source cache writing

That gives us one front-end path, one semantic extraction shape, and one place to build incremental invalidation without duplicating generator logic.
