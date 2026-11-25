# Prism++ Builtin Intake Procedure
Doc Status: planning
Status: Active
Purpose: define the mandatory repeatable procedure for adding new PHP-visible builtins to the project.

---

## 1. Why this file exists

Builtin work is product-surface work.
It affects language usability, runtime layering, compile times, configuration, test coverage, and long-term maintenance.

This document exists to prevent ad hoc builtin additions.
A builtin is not considered complete merely because code exists. A builtin is complete only when its contract, placement, compile plan, registration, tests, and documentation are all defined and validated.

---

## 2. Core rule

> New builtin work must follow one repeatable intake procedure.
> No builtin may skip contract definition, placement, compile planning, registration planning, sandbox testing, or reporting.

---

## 3. End-to-end procedure

Every new builtin request must follow this sequence.

### 3.1 Scope request
The request should identify, as far as currently known:
- source language reference surface, typically PHP or JavaScript
- requested function(s) or class(es)
- module/family when known, for example string, json, filesystem, env, process, array
- expected compatibility strictness when already decided

### 3.2 AI intake/spec draft
The assistant prepares the builtin intake draft using:
- current project specs
- existing runtime/generator structure
- prior language knowledge
- current project decisions about narrowing, compatibility, and layering

This draft must separate two things explicitly:
- **reference behavior**: what the source-language builtin is known to do
- **proposed Prism++ contract**: what this project will actually implement now

Each behavioral point must be labeled as one of:
- `kept`
- `modified`
- `dropped`

This separation is mandatory. Runtime and wrapper work must not silently inherit legacy language quirks just because the source language has them.

### 3.3 Joint gap/conflict review
The user and assistant resolve open questions, conflicts, exclusions, and compatibility decisions.

Typical mandatory decision areas:
- accepted argument types
- rejected argument types
- coercion policy
- boundary behavior
- return contract
- error behavior
- platform constraints
- runtime placement
- config visibility

### 3.4 Frozen contract
Implementation starts only after the contract is considered frozen for that builtin.

Implementation must not quietly change semantics during coding. If the implementation reveals a semantic problem, the spec must be updated first.

### 3.5 Folder organization and compile plan
Before coding, the builtin must receive:
- mandatory folder placement
- mandatory compile plan

These are part of the intake procedure, not afterthoughts.

### 3.6 Implementation
Implementation then proceeds according to the frozen spec.

### 3.7 Sandbox build and test
If the assistant implements functionality, the assistant must also be able to test it in the sandbox.

Mandatory completion requirement:
- relevant sandbox build and test execution by the assistant
- written report describing what passed, failed, or remained unvalidated

User-side local validation is recommended, not mandatory.

### 3.8 Report
The completion report must include:
- files changed
- compile/build actions performed
- tests run
- pass/fail status
- known limitations
- unvalidated host-specific risks when relevant

---

## 4. Builtin classification

Every builtin must be classified before design and placement.

### 4.1 Core Utility Wrapper
Use this when the builtin is primarily a PHP-facing wrapper over lightweight, broadly used core-type utilities.

Typical examples:
- `substr`
- `strpos`
- selected `array_*` wrappers over core table/vector/hash helpers

### 4.2 Feature Runtime + Wrapper
Use this when the builtin requires a non-core subsystem or heavier optional support.

Typical examples:
- `json_encode`
- `json_decode`
- filesystem families
- process/env families when not universally required

### 4.3 Pure Wrapper
Use this when the builtin is mostly a thin adapter over an already-defined runtime semantic hub and does not justify new reusable runtime machinery.

---

## 5. Compatibility levels

Each builtin must declare its intended compatibility level.

### 5.1 `narrow`
Implement only the useful/common subset.
Reject unsupported or legacy-heavy cases explicitly.

### 5.2 `practical`
Implement the common and important source-language behavior, including significant edge cases, while still excluding low-value legacy behavior when justified.

### 5.3 `high`
Aim for close source-language compatibility, including more edge-case behavior.

### Rule
Default to `narrow` or `practical` unless there is a specific reason to target `high`.

---

## 6. Status vocabulary

Builtin lifecycle status uses:
- `proposed`
- `experimental`
- `stable`
- `deprecated`

These statuses apply to the builtin contract, not merely to code existence.

---

## 7. Builtin contract template

Each builtin must have its own spec file.

Current placement rule:
- one file per builtin

Recommended path pattern:
- `specs/builtins/<module>/<name>.md`

The contract entry must include at least the following sections.

### 7.1 Identity
- Name
- Module/family
- Category/classification
- Status
- Source-language reference target

### 7.2 Signature
- supported form(s)
- return type(s)
- accepted argument types
- rejected inputs
- default argument behavior

### 7.3 Behavior
- normal behavior
- boundary behavior
- invalid-input behavior
- determinism notes
- platform notes

### 7.4 Compatibility table
For each non-trivial behavior:
- source-language behavior summary
- proposed Prism++ behavior
- disposition: `kept`, `modified`, or `dropped`
- rationale

### 7.5 Error policy
Must state explicitly whether the builtin:
- throws
- returns a sentinel such as `false` or `null`
- narrows the contract and rejects unsupported inputs at generation/runtime boundaries

### 7.6 Runtime and wrapper split
Must state explicitly:
- reusable runtime responsibilities
- PHP wrapper responsibilities
- whether a feature runtime library is required

### 7.7 Configuration visibility
Must state whether the builtin is:
- implicitly available by project policy
- explicitly enabled through project configuration

### 7.8 Compile plan summary
Must summarize target ownership and linkage expectations.

### 7.9 Test matrix
Must list the required tests before the builtin can be considered complete.

---

## 8. Runtime naming rule

> Runtime internals must not inherit PHP naming clutter.

PHP compatibility belongs to the wrapper layer.
Reusable runtime APIs should use clear runtime-native naming and shape.

Example:
- PHP wrapper surface may expose `array_keys(...)`
- runtime internals may instead expose a native API such as `.keys()` when that is the better runtime design

This rule is mandatory. Internal runtime naming must not mimic PHP merely for familiarity.

---

## 9. Runtime placement rules

### 9.1 Core runtime
Core runtime placement is appropriate for:
- universally required execution/value infrastructure
- lightweight utilities tightly coupled to core runtime types
- broadly used helpers that would otherwise create unnecessary fragmentation

Current examples of builtin-backed families that may be implicitly available through core-backed policy when lightweight and broadly needed:
- string-related helpers
- array/hash/php-array helpers
- vector helpers

These are examples, not a forever-closed list.

### 9.2 Non-core feature runtime
Non-core placement is appropriate when the functionality is:
- optional
- dependency-heavy
- compile-time expensive
- platform-sensitive
- not broadly required by most generated programs

Typical examples:
- JSON subsystem
- filesystem subsystem
- process/env subsystem when heavy or host-sensitive

### 9.3 Wrapper rule
PHP-facing builtins are wrappers over runtime facilities.
They are not the naming standard for runtime internals.

---

## 10. Folder organization rules

Folder organization is mandatory for new builtin work.

Unless a more specific subsystem rule exists, use the following structure.

### 10.1 Procedure and shared policy docs
- `specs/builtin_intake_procedure.md`
- related authority/spec-map documents under `specs/`

### 10.2 Per-builtin spec docs
- `specs/builtins/<module>/<name>.md`

Examples:
- `specs/builtins/string/substr.md`
- `specs/builtins/json/json_encode.md`

### 10.3 Reusable runtime implementation
Core-backed reusable code:
- `runtime/include/scpp/...`
- `runtime/src/...`

Optional feature runtime code:
- `runtime/include/scpp/<feature>/...`
- `runtime/src/<feature>/...`

### 10.4 PHP wrapper implementation
PHP wrapper code should live in runtime-facing PHP wrapper areas such as:
- `runtime/include/scpp/php/<module>.hpp`
- `runtime/src/php/<module>.cpp`

Exact subpaths may evolve, but wrapper code must remain identifiable as wrapper-layer code.

### 10.5 Builtin registration metadata
Builtin metadata belongs under generator/spec metadata.
Current planned path:
- `generators/php/specs/php_builtins.json`

### 10.6 Tests
Runtime/unit tests:
- `tests/runtime/native/<module>/...`

Integration/transpiler tests:
- `tests/integration/builtins/<module>/...`

If the current repository layout uses a different established integration-test area, the compile plan must name it explicitly.

---

## 11. Compile plan rules

A compile plan is mandatory for each builtin.

The compile plan must answer all of the following before implementation:
- which target owns the reusable runtime logic
- which target owns the PHP wrapper
- which public headers are exposed
- which dependencies are required
- whether the builtin is implicitly available or explicitly enabled in project configuration
- what generated code symbol the lowering will call

### 11.1 Core implicit availability
Core-backed builtin families may be implicitly available by project policy when they are lightweight and broadly needed.

### 11.2 Explicit config enablement
Optional builtin libraries should be explicitly enabled in project configuration.

The spec intentionally keeps the exact project-config naming generic until the project freezes that naming.

### 11.3 Linkage intent
Optional builtin support should be controlled explicitly rather than pulled into the core by default.

---

## 12. Registration metadata rules

Builtin registration metadata must have one authoritative source.

There is already project metadata used for PHP runtime symbol prefix/prepend behavior, including `php::`-style runtime-relative symbol handling.
That existing metadata must be reused, expanded, or derived from rather than duplicated in parallel.

### Mandatory rule
- avoid redundant registries that can drift
- optional library-provided symbols must participate in the same authoritative registration flow

### Current planning direction
A richer builtin manifest may exist at:
- `generators/php/specs/php_builtins.json`

But it must expand or derive from existing authoritative metadata, avoiding duplication.
The procedure does not prematurely force one migration shape as long as the no-redundancy rule is preserved.

---

## 13. Functions first, classes by justification

Builtin work defaults to functions.

Builtin classes are allowed only when they clearly model a real semantic object/value and are better than a function-based surface.

A builtin class must justify all of the following:
- why a function-based API is insufficient
- what value/object semantics it represents
- ownership and lifetime model
- method surface and stability expectations

Classes must not be introduced merely because the runtime is written in C++.

---

## 14. Mandatory implementation checklist

A builtin is not complete unless all items below are satisfied.

### 14.1 Spec
- builtin spec file exists
- compatibility level exists
- kept/modified/dropped table exists for non-trivial behaviors
- runtime placement is defined
- config visibility is defined
- compile plan is defined

### 14.2 Implementation
- reusable runtime logic is placed correctly
- wrapper logic is placed correctly
- runtime internals use runtime-native naming
- wrapper surface provides source-language naming only where intended

### 14.3 Registration
- authoritative metadata updated or derived path updated
- lowering target identified
- optional-library symbol participation accounted for

### 14.4 Tests
- unit/runtime tests added where appropriate
- integration/transpiler tests added where appropriate
- invalid-input coverage added
- boundary coverage added
- assistant runs relevant sandbox tests for completion

### 14.5 Report
- assistant provides test/build report
- remaining risks are called out honestly

---

## 15. Minimum test matrix

Each builtin must define a minimum test matrix covering, where relevant:
- happy-path behavior
- boundary conditions
- invalid inputs
- empty inputs
- nesting/structural cases
- determinism-sensitive cases
- integration via transpiled source usage

For heavier subsystems such as JSON/filesystem/process, the matrix must also call out host-sensitive or environment-sensitive cases separately.

---

## 16. Example application shapes

### 16.1 `substr`
Likely classification:
- Core Utility Wrapper

Likely placement shape:
- lightweight reusable string slicing support in core-backed runtime facilities
- PHP wrapper surface for PHP-compatible naming and parameter behavior

### 16.2 `json_encode`
Likely classification:
- Feature Runtime + Wrapper

Likely placement shape:
- reusable JSON serializer subsystem outside core when justified by compile/dependency policy
- PHP wrapper surface for `json_encode` naming and compatibility behavior
- explicit project-config enablement when treated as optional builtin-library support

---

## 17. Relationship to authority documents

This document is normative for builtin intake workflow, folder/compile planning requirements, registration discipline, and completion criteria for new builtin work.

It must be read together with:
- `specs/spec_map.md`
- subsystem generator/runtime specs
- the current authoritative runtime symbol metadata and related config files

---

## 18. Final rule

> Builtins are part of the product surface.
> They must be designed, placed, registered, built, tested, and reported â€” not just coded.

## Runtime Naming Recommendation

Runtime APIs should favor idiomatic C++ naming and structure, aligning with the style and intent of the C++ standard library (`std::`) whenever reasonable.

Guidelines:
- Prefer concise, descriptive method/function names (e.g., `size()`, `empty()`, `keys()`, `find()`).
- Avoid mirroring PHP naming patterns in runtime code (e.g., avoid `array_keys`, `substr`, etc.).
- Treat the runtime as a native C++ library first; PHP compatibility exists only at the wrapper layer.
- Consistency with common C++ conventions improves readability, maintainability, and contributor onboarding.

This is a recommendation, not a strict requirement, but deviations should be justified.
