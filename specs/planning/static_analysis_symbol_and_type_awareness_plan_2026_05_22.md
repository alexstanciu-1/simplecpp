# Static Analysis Symbol And Type Awareness Plan
Doc Status: planning

Date: 2026-05-22

Purpose:
- open the planning track for adding symbol awareness and data type awareness to the Prism++ toolchain
- frame the first milestone as a static analysis tool
- keep the current S2S generator responsibility boundary intact unless a later approved design intentionally changes it
- document the main architecture options before implementation starts

This note is planning only.
It does not change current semantics or the current generator contract.

## Background

Current repository guidance is explicit that the PHP S2S generator is:

- deterministic
- structurally driven
- intentionally type-blind except for narrow local lowering checks
- not a semantic compiler

That boundary is currently useful and should not be discarded casually.

At the same time, multiple current and recent problem areas show that the project now needs a stronger semantic understanding layer for tasks such as:

- missing symbol diagnostics
- typed call-site validation
- local variable flow understanding
- container element/value reasoning
- property/method/member lookup
- earlier detection of invalid typed-boundary assumptions
- better user-facing diagnostics than downstream C++ errors alone

So the immediate question is not whether semantic awareness is useful.
It is where that awareness should live, how strong it should be, and how it should relate to the generator.

## First Goal

The first goal is a static analysis tool.

Initial intent:

- analyze source before or alongside generation
- build symbol awareness and type awareness deliberately
- produce diagnostics and inspection output
- begin in advisory mode first
- avoid making the S2S generator itself become the primary semantic compiler

Current preferred direction:

- the static analysis tool should be a distinct subsystem
- it should share common front-end and semantic interfaces with the S2S generator
- it should not duplicate parser, source-model, symbol-model, or type-model code
- it should not be tightly entangled with generator lowering logic

This keeps the architecture cleaner while still allowing the analyzer and generator to call each other directly through common code-level interfaces where that is the right design.

## Non-Goals For The First Phase

- replacing the C++ compiler as the final semantic authority
- turning the generator into a full semantic compiler
- changing top-level language semantics through this planning note
- promising full PHP semantic compatibility
- solving every generator weakness before the analyzer exists
- building a PHP-only analyzer architecture that cannot be reused by future language frontends

## Advisory v1 Position

Version 1 of the analyzer should be advisory.

That means:

- it may run as an explicit inspection/diagnostics pass
- it may report findings without blocking generation by default
- it should build trust through stable output before it becomes part of required generation behavior

Early success for v1 is:

- useful symbol and type diagnostics
- reliable project/dependency visibility
- reusable analysis APIs
- stable enough output that later generator integration can be narrow and deliberate

Notably, advisory does not mean isolated.

The analyzer should still be designed from the start as a real subsystem that shares core interfaces with the generator rather than as a throwaway external checker.

## Problem Statement

Today, the generator can lower many source forms, but it lacks durable knowledge about:

- what symbol a call resolves to
- whether a member access refers to a declared property or method
- what type a local currently has after assignments and control-flow joins
- what type a function or method returns
- whether a typed boundary is valid because the destination is explicit, or invalid because the source flow is incompatible
- whether a missing-name error should be caught before C++ emission

That gap affects both:

- diagnostics quality
- generator design choices around narrow local type-aware lowering

The risk is that if semantic awareness is added ad hoc inside the generator, we may accidentally invalidate the repo's current design rule that lowering remains deterministic and not semantically over-ambitious.

## Architecture Direction

### Preferred shape

Introduce a dedicated static analysis subsystem that can:

- index declarations
- build scopes and symbol tables
- resolve names
- infer or propagate supported type facts
- expose diagnostics and queryable analysis artifacts

The generator should remain a separate consumer of source structure and should interact with the analyzer through shared internal interfaces rather than through duplicated parallel logic.

Later, the generator may optionally consume selected analyzer outputs through a narrow interface, but it should not depend on the analyzer for basic deterministic emission unless that dependency is explicitly approved as a project-level design change.

### Why this direction currently looks healthiest

- preserves the current generator responsibility boundary
- allows richer diagnostics without forcing lowering to become speculative
- makes analysis results testable independently of code generation
- supports future tools beyond generation, such as:
  - `scpp analyze`
  - symbol inspection
  - go-to-definition style tooling
  - typed contract audits
  - dead code / unused symbol warnings
- fits the runtime/language layering direction, where reusable subsystems should not be owned by one language frontend

## Build And Runtime Context

The analyzer design should match the current project/build composition model rather than assuming a single-file or single-language world.

Relevant current build/runtime facts:

- `scpp build` resolves a root `prism.json`
- Prism project dependencies are loaded recursively through `dependencies`
- all participating project trees are scanned for source files
- generated output and cache state are per-project under `.prism/`
- runtime composition is layered and explicit
- runtime languages and runtime modules are selected from `prism.json`
- reusable runtime modules live below language adapters and are intended to support multiple languages

This matters because the analyzer eventually needs to understand at least:

- root-project symbols
- dependency-project exported or visible symbols
- profile-specific builtin/runtime symbol surfaces
- selected language/runtime-module availability for the current build

The analyzer should therefore be designed as a project-graph-aware service, not only as a single-file pass.

Additional planning constraints:

- per-source caching should align with the existing PHP-oriented cache model so unchanged source files can reuse prior analysis results
- runtime library surfaces should be readable through PHP++-shape metadata/artifacts rather than re-described manually in a second semantic system
- project configuration should be able to enforce strict profile behavior, and analysis visibility/rules should follow that active profile

## Implementation Shape Emerging From Discussion

The implementation direction is becoming clearer.

Current preferred execution model:

1. a full pass
2. an incremental pass

The full pass builds a clean semantic snapshot from all participating source units in the project graph.

The incremental pass reuses cached per-source facts where valid and recomputes only the source units affected by:

- direct source edits
- dependency-visible declaration changes
- runtime/profile/module environment changes
- analyzer schema/version changes

This is currently the preferred shape because it matches:

- per-source cache reuse
- project-graph-aware analysis
- advisory-first rollout

## Dynamic Carrier Simplification

Another simplifying rule from the current discussion:

- `mixed_t` can only contain nested `mixed_t` / `dynamic_t` carrier content
- `dynamic_t` can only contain nested `mixed_t` / `dynamic_t` carrier content

This should make analysis easier because dynamic-carrier recursion stays inside the dynamic-carrier world instead of implying arbitrary hidden native typed payload graphs.

### Analyzer implication

This suggests:

- the analyzer can treat `mixed` and dynamic-carrier shapes as explicit dynamic domains
- nested dynamic-carrier traversal does not need to pretend it recovered precise native declared types unless a real typed boundary exists
- dynamic recursion can be modeled with a smaller set of stable carrier rules

### Boundary implication

This also reinforces a previous rule:

- `unknown` should not collapse into `mixed`

because `mixed` and `dynamic` already describe a real dynamic carrier domain with its own constraints.

## Full Pass vs Incremental Pass

### Full pass

The full pass should:

- scan all source units in the participating project graph
- build declaration indexes
- build dependency relationships between source units
- build or refresh symbol/type facts
- produce a complete semantic snapshot for the current environment

The full pass is the authoritative rebuild path when cache reuse is unavailable or invalid.

### Incremental pass

The incremental pass should:

- load cached per-source semantic state where valid
- detect which source units are dirty
- detect which source units are affected transitively by dependency changes
- recompute only the invalidated portion of the semantic graph
- merge reused and recomputed source facts into one project-level semantic snapshot

Important rule:

- incremental analysis must converge to the same semantic snapshot as a full pass for the same inputs

If that invariant is violated, the incremental model is wrong.

## Source Dependency Tracking

When a source unit is analyzed, the analyzer should remember which other source units materially affect its semantic result.

This dependency list is a core part of safe incremental invalidation.

Initial examples include:

- class `extends`
- interface `implements`
- trait or equivalent composition forms if/when supported
- imported namespace/use targets
- direct function or constant references resolved to other source units
- property/method/member references resolved to declarations owned elsewhere
- dependency-project exported declarations used by the current source
- runtime/profile/module symbol-surface dependencies when relevant

The first explicitly identified dependency class is:

- `extends`

That is a strong starting point because parent declarations can affect:

- member visibility
- method/property lookup
- inherited type facts
- override legality if/when that area is expanded

This dependency recording should be explicit rather than inferred later from broad cache invalidation.

In other words:

- each source unit should know which source units it depends on semantically
- incremental invalidation should follow those stored dependency edges

## Simplicity Rule: No Overload Sets In One Namespace/Class

Simple C++ already benefits from a simplifying language rule:

- no function overloading by same-name declaration sets
- no duplicate declaration definitions in the same namespace/class scope

This is a strong advantage for the analyzer.

It means name resolution can usually aim for:

- zero matches
- one match
- invalid duplicate declaration

rather than:

- large overload sets requiring complex ranking/disambiguation

### Why this matters

This reduces complexity in:

- symbol table design
- duplicate declaration diagnostics
- call resolution
- incremental invalidation
- future cross-language reuse of the semantic core

### Analyzer implication

The analyzer should treat duplicate declarations in the same semantic scope as structural errors, not as candidate overload groups.

That means:

- duplicate function names in the same namespace are an error
- duplicate methods in the same class are an error
- duplicate properties/constants in the same owning scope are an error where current language rules forbid them

This keeps the semantic model cleaner and aligns with the non-messy-codebase goal.

## Main Design Options

### Option A. Keep all new awareness inside the generator

Shape:

- extend generator passes with symbol tables and type tracking
- use the same subsystem for diagnostics and lowering decisions

Advantages:

- one implementation surface
- direct access to lowering context
- potentially faster short-term delivery for specific generation fixes

Risks:

- conflicts with the current generator boundary
- encourages semantic policy to spread across lowering code
- makes diagnostics harder to separate from emission concerns
- makes future non-generator tooling less clean

Current assessment:

- not preferred as the primary architecture

### Option B. Separate analyzer with shared front-end infrastructure

Shape:

- share parser/AST/source model
- share semantic data-model interfaces where those facts are common
- build a dedicated semantic-analysis layer beside the generator
- optionally let the generator consume selected analyzer facts later through a constrained API

Advantages:

- matches the first-goal direction
- avoids code duplication
- keeps ownership boundaries clearer
- enables tooling and diagnostics independent of C++ emission
- lets us evolve analysis strength in stages

Risks:

- requires careful definition of what is shared versus duplicated
- may initially duplicate a small amount of local logic already embedded in generator helpers

Current assessment:

- preferred

### Option C. Fully separate analyzer with no shared code

Shape:

- analyzer reimplements front-end logic independently from the generator

Advantages:

- maximum decoupling
- strongest guard against accidental generator entanglement

Risks:

- high duplication cost
- parser/AST drift risk
- slower iteration

Current assessment:

- probably too strict as an initial rule
- conflicts with the no-duplication goal
- better to avoid sharing lowering logic than to forbid shared front-end infrastructure

## Working Recommendation

Use Option B:

- separate analyzer subsystem
- shared parser/AST/source-location infrastructure required where practical
- shared semantic interfaces required where facts are common
- no duplication of parser, symbol, or type-model code by default
- no direct sharing of lowering-oriented generator logic by default
- generator consumption of analyzer results should be opt-in and narrow at first, then expanded only after advisory stability is proven

In short:

- share front-end facts when useful
- share semantic interfaces intentionally
- do not share semantic policy through lowering shortcuts
- do not make the analyzer a hidden generator pass
- do not build two separate semantic worlds inside one repository

## Shared Interface Requirement

The analyzer and the S2S generator should be able to call each other directly in code through common internal interfaces.

That implies a reusable core contract for concepts such as:

- source units
- project graph
- declarations
- scopes
- symbols
- resolved symbol targets
- type identities
- type facts
- diagnostics
- language-profile/runtime-surface visibility

These interfaces should be designed so they are not PHP-only.

The PHP frontend will be the first consumer, but the subsystem should be shaped as Simple C++ analysis infrastructure that future language frontends can also implement or consume.

This does not require full language-agnostic semantics from day one.

It does require that:

- shared abstractions live above one specific frontend
- reusable project/runtime/dependency modeling is not buried in PHP-only generator code
- future language support is a first-class design constraint now, not a retrofit later

## Proposed Interface Shape

This section proposes the first shared internal interface shape for discussion.

The intent is:

- stable conceptual boundaries
- no forced commitment yet to exact class/file names
- enough precision that analyzer and generator work can begin toward the same target

### Design posture

The shared contract should be query-oriented, not mutation-heavy.

Preferred style:

- immutable or snapshot-style project/semantic views after construction
- stable ids for cross-linking facts
- language adapters provide facts into the common model
- analyzer produces resolved facts
- generator consumes read-only resolved facts when it chooses to opt in

This reduces hidden coupling and makes caching/testing easier.

## Proposed Project Graph API

The project graph API should model build-time composition, not only source-file adjacency.

### Core entities

- `WorkspaceGraph`
- `ProjectNode`
- `SourceUnit`
- `DependencyEdge`
- `RuntimeSelection`
- `LanguageSelection`
- `ModuleSelection`

### Minimal responsibilities

`WorkspaceGraph` should answer:

- what the root project is
- which projects participate in the current build/analyze invocation
- dependency order
- cycle diagnostics
- active language/profile/module selections per project
- source units belonging to each project

`ProjectNode` should answer:

- project root path
- owning `prism.json`
- entrypoint
- declared dependencies
- declared libraries
- runtime language selections
- runtime module selections
- cache/build/generated directories

`SourceUnit` should answer:

- absolute path
- owning project
- frontend language kind
- parsed AST handle
- source hash / cache identity
- export/visibility metadata when applicable
- per-file cache location(s) for reusable analysis state when enabled

`DependencyEdge` should answer:

- source project
- target project
- dependency kind
- visibility/export policy if the build model defines one

### Example query shape

Illustrative query style:

```text
WorkspaceGraph::rootProject() -> ProjectNodeId
WorkspaceGraph::projectsInDependencyOrder() -> list<ProjectNodeId>
WorkspaceGraph::projectSources(ProjectNodeId) -> list<SourceUnitId>
WorkspaceGraph::runtimeSelection(ProjectNodeId) -> RuntimeSelection
WorkspaceGraph::dependencyEdges(ProjectNodeId) -> list<DependencyEdgeId>
```

### Important design rule

The project graph should be shared by analyzer and generator.

We should not have:

- one dependency loader for build/generation
- a second unrelated dependency loader for analysis

That would create immediate duplication and drift.

### Project-config-aware analysis mode

The project graph should also expose the active analysis-relevant language profile.

For current PHP++ work, that includes at least:

- whether PHP is active for the project
- whether the PHP profile is `legacy` or `strict`
- which runtime modules are active

This matters because profile selection should influence:

- builtin/runtime symbol visibility
- strict-only or legacy-only diagnostics
- whether strict-mode enforcement or advisory warnings are active for the current analysis run

## Proposed Symbol Table API

The symbol layer should separate:

- declaration indexing
- symbol identity
- resolution results
- scoped lookup views

### Core entities

- `SymbolId`
- `DeclId`
- `ScopeId`
- `SymbolTable`
- `ScopeGraph`
- `ResolutionResult`
- `SymbolVisibility`

### Symbol kinds

At minimum:

- namespace
- function
- class
- interface
- method
- property
- constant
- parameter
- local
- builtin/runtime symbol

### Minimal responsibilities

`SymbolTable` should answer:

- what declaration owns a symbol
- what kind of symbol it is
- which scope introduces it
- which project/source unit owns it
- whether it is source-defined, dependency-defined, or runtime-surface-defined

`ScopeGraph` should answer:

- parent scope
- child scopes
- visible declarations from a scope
- namespace/import context for a scope

`ResolutionResult` should answer:

- resolved target symbol when unique
- unresolved state
- ambiguous state
- not-visible state
- profile/module gated state when the symbol exists but is unavailable in the active environment
- cache provenance when the result was reused from persisted analysis state

Under the current no-overload-set direction, ambiguity should be relatively rare and should usually mean one of:

- invalid duplicate declaration state
- conflicting visibility/import state
- an analyzer model gap that still needs refinement

### Example query shape

Illustrative query style:

```text
SymbolTable::lookupInScope(ScopeId, NameRef, LookupKind) -> list<SymbolId>
SymbolTable::resolveCall(ScopeId, CallSyntaxId) -> ResolutionResult
SymbolTable::resolveMember(TypeFact, MemberName, MemberKind) -> ResolutionResult
SymbolTable::symbolDecl(SymbolId) -> DeclId
SymbolTable::symbolKind(SymbolId) -> SymbolKind
```

### Important design rule

Resolution results should preserve uncertainty explicitly.

Do not collapse:

- unresolved
- ambiguous
- unknown receiver type
- symbol exists but inactive under current runtime/language/module selection

into one generic failure bucket.

The generator and diagnostics layer will need those distinctions.

### Runtime-surface source of truth

Builtin/runtime-visible symbols should not be maintained as an unrelated duplicate semantic universe inside the analyzer.

Preferred direction:

- runtime/library surfaces should be exposed in PHP++-shape artifacts or metadata
- the analyzer should read those artifacts as symbol/type inputs
- generator and analyzer should consume the same runtime-surface description wherever practical

That allows the analyzer to reason about runtime symbols without reinventing them by hand in a separate analyzer-only registry.

## Proposed Type Identity API

The type API should distinguish declared identity from flow facts.

That means separating:

- what a type is
- how it was written
- what we currently know about an expression/local at a program point

### Core entities

- `TypeId`
- `TypeForm`
- `TypeOrigin`
- `TypeFact`
- `TypeEnvironment`
- `TypeQuery`

### Type identity categories

At minimum:

- scalar primitives
- null
- mixed
- unknown
- named declared object/interface type
- wrapper/ownership families
- container families
- callable signature type when explicit
- runtime/builtin special families when needed
- dynamic carrier families

### Minimal responsibilities

`TypeId` should represent canonical type identity.

Examples:

- `int`
- `string`
- `shared<A>`
- `value<Point>`
- `nullable<string>`
- `vector<int>`
- `hash<string, int>` when/if represented explicitly

`TypeForm` should preserve authored structure where needed.

Examples:

- source spelled `?string`
- normalized identity `nullable<string>`

`TypeFact` should represent what is known at a specific program point.

Examples:

- exact declared type
- narrowed non-null form
- known mixed
- unknown
- union-like fact set only if later approved

`unknown` here means:

- the analyzer does not yet have enough proven information

It should not mean:

- dynamically typed by design
- an acceptable final semantic clarity target for ordinary type-aware code

### Runtime-library type sourcing

The long-term preferred direction is for runtime library declarations to exist in analyzer-readable PHP++ shapes, or in a closely-related shared semantic artifact derived from those shapes.

Goal:

- the tool reads runtime-exposed declarations from one maintained source
- runtime callable/type shapes do not need to be reinvented separately in analyzer code
- future languages may still map the same shared runtime authority through their own frontend adapters

### Example query shape

Illustrative query style:

```text
TypeEnvironment::declaredType(DeclId) -> TypeId | none
TypeEnvironment::expressionType(ExprId, ProgramPointId) -> TypeFact
TypeEnvironment::receiverType(MemberAccessSyntaxId) -> TypeFact
TypeEnvironment::normalizeType(TypeSyntaxId) -> TypeId
TypeEnvironment::isAssignable(TypeFact source, TypeId destination) -> AssignabilityResult
```

### Important design rules

1. `unknown` is not `mixed`
2. declared type identity is not the same as flow fact
3. normalization must preserve wrapper distinctions
4. runtime-visible availability and type identity are separate concerns
5. dynamic-carrier recursion should stay modeled as dynamic-carrier recursion unless a real typed boundary proves otherwise
6. `unknown` is a warning-worthy unresolved type-awareness outcome, not a desired steady-state result

For example:

- `strlen` may be visible or not depending on profile/runtime surface
- but `string` remains `string` regardless of symbol-surface selection

## Proposed Generator/Analyzer Boundary

The generator should not ask the analyzer to "generate for me."

The analyzer should not ask the generator to "decide semantics for me."

The crossing boundary should be narrow and fact-oriented.

### Preferred call direction in advisory v1

1. shared project/front-end layer builds or loads:
   - project graph
   - parsed source units
   - source metadata

2. analyzer builds:
   - symbol table
   - scope graph
   - type environment
   - diagnostics

3. generator may optionally query:
   - resolved symbol facts
   - declared type facts
   - selected expression/local type facts where the analyzer marks them stable enough

4. generator continues to own final lowering choices

5. when semantic facts are unchanged and valid, both analyzer and generator should be able to reuse per-file cached state rather than recomputing all file-local analysis every run

### Generator-facing query surface

The generator-facing surface should probably be a small read-only facade rather than the entire analyzer internals.

Illustrative shape:

- `SemanticSnapshot`
- `SemanticQueryService`
- `GeneratorSemanticView`

Minimal query families:

- resolve declaration for syntax node
- resolve callable target for call syntax
- resolve member target for member syntax
- get declared type for declaration
- get stable type fact for expression/local
- get active runtime/profile symbol availability
- get diagnostics attached to a syntax/source span
- get cache validity/provenance for a source unit or semantic fact set

### Example crossing pattern

Illustrative flow:

```text
generator -> SemanticQueryService::resolvedCall(CallSyntaxId)
generator -> SemanticQueryService::declaredType(DeclId)
generator -> SemanticQueryService::stableExpressionType(ExprId)
generator -> SemanticQueryService::runtimeSymbolAvailability(NameRef, ActiveProfile)
```

### Important design rule

The generator should be able to ask:

- "do you know this?"

and receive:

- exact fact
- unknown
- ambiguous
- not-applicable

It should not be forced into:

- "analysis must fully succeed or generation stops"

while v1 is advisory.

### Cached semantic snapshot model

Each source file should be able to persist its own reusable analysis cache when its inputs have not changed.

Preferred first shape:

- one cache record per source unit
- keyed by source identity plus analysis-relevant environment inputs
- loadable independently
- mergeable into one project-level semantic snapshot

Likely cache inputs:

- source path
- source content hash or stable size+mtime+guard hash policy
- owning project identity
- active language frontend/profile
- active runtime module selection
- dependency-export visibility inputs relevant to that source
- analyzer version/schema version

Likely cache payloads:

- declaration index facts
- local symbol table facts
- local type facts where persistable
- outbound references/imports
- semantic dependency edges to other source units
- diagnostics

Important rule:

- cache reuse must be invalidated not only by source-file edits, but also by changes in profile, runtime-module availability, dependency-visible declarations, or analyzer schema version

That includes changes to remembered semantic dependencies such as parent classes or other declaration providers used by the source unit.

This is especially important if strict-mode enforcement changes the visible symbol/type world for the same source text.

## Proposed Language Adapter Split

To keep the subsystem reusable for future languages, split the shared model from frontend-specific providers.

### Shared core owns

- project graph
- source-unit ids
- symbol ids
- type ids
- scope graph interfaces
- diagnostic envelopes
- semantic query/result shapes

### PHP frontend adapter owns

- PHP/PHS AST-to-declaration extraction
- PHP namespace/use interpretation
- PHP-specific typed comment extraction rules
- PHP-specific source constructs that produce symbol/type facts
- mapping active PHP profile registries into runtime-surface symbols
- PHP-oriented per-source cache serialization/deserialization if PHP remains the host implementation layer for v1

### Future frontend adapters would own

- their syntax parsing/binding rules
- their language-specific declaration extraction
- their language-specific type spelling normalization
- their visibility/import rules

This gives us one shared semantic backbone with per-language fact providers.

## Suggested First Concrete Boundary

If we want the smallest useful shared contract first, I would start with four read-mostly services:

1. `ProjectGraphService`
2. `SymbolIndexService`
3. `TypeQueryService`
4. `SemanticQueryService`

Where:

- `ProjectGraphService` is shared infrastructure
- `SymbolIndexService` and `TypeQueryService` are analyzer-built views
- `SemanticQueryService` is the narrow facade the generator consumes

This is intentionally service-shaped rather than class-hierarchy-heavy.

That keeps the first implementation flexible while still making the boundary explicit.

## Additional Requirements Added In Discussion

The current discussion adds three implementation-shaping requirements:

1. Per-source PHP cache reuse

- each source file should have its own reusable analysis cache
- unchanged files should not require full re-analysis
- cache invalidation must include source edits and relevant environment/config changes

2. Runtime library as analyzer-readable PHP++ shapes

- runtime-exposed callable/type surfaces should be available in a PHP++-shape-readable form
- analyzer and generator should read from that shared source where practical
- avoid manually re-encoding runtime declarations in a second analyzer-only semantic registry

3. Project-config-enforced strictness

- active project config should determine whether strict profile rules are enforced
- analyzer symbol visibility and diagnostics should follow the active profile
- the same source file may therefore analyze differently under different project/runtime profile selections

4. Full-pass plus incremental-pass model

- the system should support both a full pass and a dependency-aware incremental pass
- incremental results must converge to the same output as a full pass for identical inputs

5. Remembered semantic dependencies

- each source unit should record which other source units affect its semantic result
- `extends` is an initial concrete dependency kind and more should be identified deliberately

6. No overload/duplicate declaration sets in one semantic scope

- same-scope same-name declarations are errors, not overload groups
- this should simplify symbol resolution and keep the codebase cleaner

7. Dynamic carriers stay in the dynamic domain

- `mixed_t` and `dynamic_t` nested content should be treated as dynamic-carrier content
- this should simplify recursive dynamic analysis and avoid inventing hidden native-type trees inside dynamic carriers

## Discipline Rules

These rules are meant to keep the work disciplined as the subsystem grows.

### 1. Shared infrastructure, not shared shortcuts

- share project graph, source identity, symbol identity, type identity, and semantic query interfaces
- do not share ad hoc lowering hacks between analyzer and generator
- do not solve duplication by letting one subsystem reach into another subsystem's unstable internals

### 2. Analyzer produces facts, generator produces lowering

- analyzer owns semantic fact production
- generator owns deterministic lowering
- generator may consume analyzer facts through narrow read-only interfaces
- analyzer must not become a hidden code-generation pass

### 3. Unknown stays unknown

- `unknown` must remain distinct from `mixed`
- lack of proof must not silently degrade into dynamic semantics
- unresolved, ambiguous, inactive-under-profile, and not-applicable results must stay distinct

### 3a. Dynamic carriers stay explicit

- `mixed` and dynamic carriers represent a real explicit dynamic domain
- nested dynamic-carrier content should stay in that domain unless a typed boundary proves a narrower fact
- do not pretend dynamic recursion implies recovered native type structure

### 3b. Unknown should warn

- Simple C++ should aim for explicit type awareness
- if analysis still ends at `unknown`, the tool should surface a warning in advisory mode
- `unknown` is acceptable as an internal temporary state, not as a silent final answer for ordinary user-visible analysis

### 4. Advisory before enforcement

- v1 analysis is advisory by default
- enforcement should come only after result quality is stable and validated
- generator/runtime behavior must not become hostage to incomplete advisory analysis

### 5. Cache by source unit with explicit invalidation

- each source file should be able to reuse its own cached analysis state
- cache reuse must be invalidated by any analysis-relevant input change
- invalidation inputs include source content, active profile, active modules, dependency-visible surface, and schema/tool version

### 6. One semantic source of truth for runtime-visible surfaces

- runtime-visible callable/type surfaces should come from one maintained source
- analyzer and generator should consume the same runtime-surface description where practical
- do not create a second hand-maintained analyzer-only runtime semantic registry unless explicitly approved

### 7. Project config is part of the semantic environment

- project config is not just build plumbing
- active language profile and runtime modules affect symbol visibility and diagnostics
- analysis results are only valid relative to their project/runtime environment

### 8. Keep the shared core language-agnostic

- shared core abstractions must be reusable beyond PHP
- PHP-specific extraction, namespace rules, typed-comment rules, and profile mapping stay in the PHP adapter
- future language support should extend the shared core through adapters, not forks

### 9. Prefer small proven facts over broad speculative inference

- start with declaration facts, scoped visibility, and a narrow trusted type-fact set
- add broader flow reasoning only when it is stable and clearly valuable
- do not turn v1 into a whole-program speculative compiler

### 9a. Incremental must match full

- full-pass results are the semantic correctness baseline
- incremental reuse is an optimization, not a second semantic definition
- if incremental and full disagree for the same inputs, treat that as a correctness bug

### 9b. Dependencies must be remembered explicitly

- per-source semantic dependencies should be first-class cached facts
- do not rely only on coarse project-wide invalidation once source relationships are knowable

### 9c. Duplicate declarations are errors, not overload opportunities

- where Simple C++ forbids same-scope same-name declaration duplication, the analyzer should report it directly
- do not build overload-resolution machinery for declaration patterns the language intentionally rejects

### 10. Every new integration point must justify its boundary cost

- if the generator wants a new semantic query, define it explicitly
- prefer small read-only query additions over exposing whole analyzer internals
- every integration point should reduce duplication or improve correctness in a measurable way

## Proposed Responsibility Split

### Static analyzer owns

- scope construction
- symbol tables
- declaration indexing
- namespace/use resolution
- function/method/property lookup
- return-type lookup for declared symbols
- local type-flow facts within a supported analysis model
- diagnostics for missing/ambiguous/incompatible symbol and type usage
- stable analysis artifacts for future tooling

### Generator continues to own

- deterministic syntactic lowering
- runtime helper routing
- normalization/emission rules
- local structural checks explicitly required by generator specs
- final emitted C++ shape

### Shared front-end / analysis-core layer should own

- parsing
- AST nodes
- source spans
- comments/doc-type attachment extraction
- file/module discovery inputs
- project graph modeling
- dependency visibility modeling
- runtime/language/module surface modeling
- common symbol/type identities and query interfaces

## First-Phase Deliverables

The first milestone should aim for a useful, narrow vertical slice rather than full semantic completeness.

Recommended first-phase deliverables:

1. declaration indexing for:
   - functions
   - classes
   - interfaces
   - methods
   - properties
   - constants

2. scope and symbol table construction for:
   - global scope
   - namespace scope
   - function/method local scope

3. basic name resolution for:
   - direct function calls
   - method calls on declared object types when the receiver type is known
   - property reads/writes on declared object types when the receiver type is known
   - imported names covered by the current supported namespace rules

4. declared-type extraction for:
   - parameters
   - returns
   - properties
   - typed locals

5. minimal local flow facts for:
   - direct assignment
   - simple typed stabilization
   - explicit nullability checks where already well-defined

6. first diagnostics set for:
   - missing symbol
   - duplicate declaration where forbidden
   - invalid property access on known declared type
   - invalid method call on known declared type
   - obvious typed-boundary mismatch where both sides are known

7. an inspection-friendly CLI surface, likely something like:
   - `scpp analyze`
   - `scpp analyze --json`
   - `scpp inspect symbols`

These command names are provisional.

8. shared internal APIs that the generator can query directly without duplicating semantic reconstruction logic

## Type Awareness Scope

Type awareness should be staged.

Recommended initial type model:

- declared types are authoritative when explicit
- local inference is limited and flow-sensitive only where simple and stable
- unknown remains an explicit temporary analysis state when the tool cannot yet prove more
- unknown must not silently mean dynamic object/table semantics
- `mixed` remains distinct from unknown
- `null`, `false`, wrappers, and object handles must remain distinct in analysis results

This follows the same general caution already visible in recent generator planning:

- uncertainty should not automatically degrade into dynamic semantics

Simple C++ should still aim for type awareness.

So `unknown` should not become a comfortable steady-state semantic outcome for normal supported code paths.

Current direction:

- `unknown` may exist internally while analysis is still incomplete at a given site
- if `unknown` survives to a user-visible result, it should trigger at least an advisory warning
- repeated or structural `unknown` outcomes should push us to improve analysis coverage rather than normalize them as ordinary output

## Symbol Awareness Scope

Symbol awareness should likely begin with repository-owned declarations only.

Initial supported symbol domains:

- current project source declarations
- dependency project visible/exported declarations when available through project metadata and project-graph loading
- runtime/builtin registry symbols already modeled by authoritative metadata

Visibility should reflect the current project model:

- project-local declarations
- dependency-project declarations exposed through the project composition model
- language-profile-visible runtime/builtin symbols for the active build

Questions for later phases:

- how much of runtime helper space should be visible to the analyzer
- whether strict and legacy builtin surfaces should be represented as profile-specific symbol environments
- how library/dependency exports should be indexed across projects
- how non-PHP frontends will plug into the same shared symbol environment contract

## Diagnostics Philosophy

The analyzer should improve user-facing errors before C++ generation where it can do so reliably.

But it should not pretend certainty where the current model is incomplete.

Recommended rule:

- emit analyzer diagnostics when the analyzer can prove the issue
- otherwise preserve the current path where the generator and then the C++ compiler remain the later authorities

This suggests diagnostics classes such as:

- proven error
- likely issue
- inspection fact only
- unresolved type-awareness warning

Current direction for `unknown`:

- if a user-visible symbol, expression, member receiver, or local remains `unknown`, emit at least a warning in advisory mode unless a later explicit exemption says otherwise

Severity policy can be refined later.

## Questions To Resolve

These are the main open design questions before implementation gets too deep:

1. Should the analyzer run as a separate CLI command first, or also automatically during `scpp build` in advisory mode?
2. What exact shared interfaces should be extracted so analyzer and generator can call each other directly without semantic duplication?
3. How much local type inference is acceptable before we are effectively building a semantic compiler?
4. Should analyzer results be persisted in `.prism/cache/` for reuse?
5. How should profile-specific builtin/runtime symbol surfaces be modeled?
6. What is the minimal stable contract by which the generator may consume analyzer facts during advisory v1?
7. How should multi-file and dependency-project symbol visibility be represented in project mode?
8. Which parts of the analysis core are intentionally language-agnostic versus frontend-specific adapters?

## Suggested Implementation Order

1. define the analyzer subsystem boundary and folder ownership
2. define the shared analyzer/generator interface layer
3. define the minimal analysis artifact model
4. implement declaration indexing and symbol tables
5. implement name resolution for the simplest supported cases
6. implement declared-type extraction and minimal local type facts
7. add the first advisory diagnostics CLI surface
8. evaluate whether one narrowly-scoped generator integration point is justified after the analyzer proves stable value

That last step should be a separate decision, not assumed from the start.

## Validation Strategy

Validate at the smallest layer that proves the work.

Recommended proof layers:

- focused analyzer fixtures for symbol/type resolution
- JSON snapshot tests for analyzer output where stability matters
- targeted project samples for missing-symbol and typed-boundary diagnostics
- project-graph fixtures that include dependencies and runtime-module selections
- generator regression checks only when analyzer integration is intentionally added

## Likely Owning Areas

- analyzer subsystem: likely a new dedicated area rather than `generators/php/src/` unless a stronger reason appears
- shared analysis interfaces: likely a common non-PHP-specific area rather than a generator-only folder
- normative future CLI/tool contract: `specs/project_build_v1.md` or a dedicated top-level spec if the analyzer becomes a first-class command family
- future generator integration rules: `generators/php/specs/`

## Short Conclusion

The project should gain symbol awareness and type awareness through a dedicated static-analysis subsystem first, not by silently growing the current S2S generator into a semantic compiler.

Version 1 should be advisory.
The analyzer and generator should share common interfaces and direct code-level integration points rather than duplicating semantic models.
The subsystem should be designed as reusable Simple C++ infrastructure for future language frontends, not as a PHP-only side tool.
