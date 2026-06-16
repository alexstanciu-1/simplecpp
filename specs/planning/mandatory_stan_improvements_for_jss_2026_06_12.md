# Mandatory STAN Improvements For JSS
Doc Status: planning

Date: 2026-06-12

Purpose: list the semantic capabilities JSS needs from STAN so the JSS frontend can remain a source adapter that lowers to pre-PHS and does not grow a second semantic engine.

This is a planning artifact, not semantic authority.

## Core Rule

JSS should not become a second STAN.

Preferred layering:

```text
JSS source
  -> JSS parser / AST
  -> pre-PHS-oriented summary + classification requests
  -> STAN semantic services
  -> ready-PHS emission
```

That means:

- JSS owns syntax and narrow frontend shape rules
- STAN owns semantic classification, wrapper contracts, helper validity, and type-driven readiness
- the generator/runtime keep their own existing ownership boundaries

When JSS needs a semantic answer that STAN does not yet provide, the gap should be recorded here instead of being solved permanently inside JSS-local code.

## Current Temporary JSS Semantic Bridges

These exist today as pragmatic bridges, but they should not become the long-term authority:

1. JSS-local helper call typing
   - current file: [generators/php/src/Jss/JssSemanticValidator.php](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Jss/JssSemanticValidator.php)
   - examples:
     - `BUILTIN_CALL_RETURN_TYPES`
     - call-return lookup through the JSS-owned helper surface adapter
   - why temporary:
     - helper call typing still starts from a frontend-owned surface adapter
     - they can drift from real strict helper behavior
     - they already exposed shallow-runtime mismatch pressure during fs/io sample work

2. JSS-local `take(...)` semantic validation
   - current file: [generators/php/src/Jss/JssSemanticValidator.php](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Jss/JssSemanticValidator.php)
   - examples:
     - `validateCall(...)`
     - `assertTakeOutputType(...)`
   - why temporary:
     - generator already has stronger `take(...)` rules
     - JSS should not maintain a second contract for wrapper extraction
     - the same rule should serve PHS, JSS, and future PHP++ frontends

3. JSS-local expression typing for wrapper/helper calls
   - current file: [generators/php/src/Jss/JssSemanticValidator.php](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Jss/JssSemanticValidator.php)
   - examples:
     - `expressionType(...)` returning wrapper/helper shapes from local tables
   - why temporary:
     - this is a semantic typing service, not just syntax validation
     - STAN should be the place that answers helper call result types

4. JSS parser enforcement for reserved helper roots
   - current file: [generators/php/src/Jss/JssParser.php](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Jss/JssParser.php)
   - examples:
     - reserved roots `fs`, `io`, `json`, `dt`
     - blocked user namespace / `use` alias reuse
   - status:
     - acceptable as a frontend shape rule for now
   - follow-up:
     - STAN should still understand those roots as semantic helper families and report deeper misuse consistently

5. JSS-local fallback helper mappings for reserved family lowering
   - current files:
     - [generators/php/src/Jss/JssEmitter.php](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Jss/JssEmitter.php)
     - [generators/php/src/Jss/JssSummaryExtractor.php](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Jss/JssSummaryExtractor.php)
   - status:
     - acceptable as a narrow lowering map for the reserved helper-family surface
   - limitation:
     - semantic helper availability, wrapper contract truth, and project/module gating should still come from STAN/runtime truth, not JSS surface-adapter assumptions

## Bridge Ownership Audit

The current bridge set should be read with explicit ownership, so JSS work does not silently harden temporary logic into a second semantic path.

| Bridge | Current location | Real owner | Status | Retirement note |
| --- | --- | --- | --- | --- |
| reserved helper surface spelling and lowering (`fs.get` -> `fs_get`) | JSS frontend adapter | frontend language adapter | isolated | now lives behind [FrontendCallSurfaceInterface](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Frontend/FrontendCallSurfaceInterface.php) with the JSS implementation in [JssCallSurface](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Jss/JssCallSurface.php) |
| helper call existence in transient STAN analysis without workspace shallow symbols | `StanFrontendClassifier` fallback | STAN / PHS semantic baseline | isolated | now handled by [StanPhpRuntimeFunctionCatalog](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Stan/StanPhpRuntimeFunctionCatalog.php), which is PHS-facing rather than JSS-facing |
| `take(...)` wrapper family arity/output contract | `JssSemanticValidator` | STAN | first pass retired | moved into STAN-owned [StanTakeContractResolver](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Stan/StanTakeContractResolver.php); JSS now consumes shared wrapper-family output truth instead of local regex families |
| normalized semantic call target handoff (`normalized_call_target`) | `JssSummaryExtractor` -> `StanFrontendClassifier` | shared frontend/STAN interface | first pass isolated | STAN no longer knows JSS helper families; it consumes normalized PHS-facing call targets from the frontend request |
| local `take(...)` output-variable shape check | `JssSemanticValidator` | JSS frontend shape | keep | simple-local-variable AST shape is still a frontend subset rule before deeper semantic handling |
| local `take(...)` output type mismatch messaging | classified `JssTranspiler` path | STAN | retired for classified path | classified JSS emission now defers wrapper/source/output contract mismatches to STAN `take_contract` classifications; JSS keeps only simple output-variable shape checks |
| helper-family/module availability truth | mixed JSS + STAN gap | project validation / STAN | open | still not module-aware; JSS can parse helper syntax, but STAN should decide whether it is valid in the active project/profile |
| helper-family lowering rewrite in emitter | `JssEmitter` | JSS lowering using STAN-approved helper identity | temporary but acceptable | still a narrow frontend lowering map; ownership is acceptable while meaning stays STAN-owned |
| parser reserved-root blocking (`fs`, `io`, `json`, `dt`) | `JssParser` | JSS frontend shape | keep | syntax/subset guardrail; STAN still needs deeper semantic misuse diagnostics |
| local expression typing for non-helper/non-wrapper subset checks | `JssSemanticValidator` | mixed: JSS subset + STAN | partial bridge remains | numeric/bool/frontend subset checks remain local; helper/wrapper truth should continue to be pushed out of JSS |

## First Retirement Slice Completed

Implemented in this pass:

1. Added shared frontend call-surface interface:
   - [FrontendCallSurfaceInterface](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Frontend/FrontendCallSurfaceInterface.php)
2. Added JSS-owned helper surface adapter:
   - [JssCallSurface](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Jss/JssCallSurface.php)
3. Added STAN-owned PHS runtime-function baseline for transient classification:
   - [StanPhpRuntimeFunctionCatalog](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Stan/StanPhpRuntimeFunctionCatalog.php)
4. Added shared STAN-owned `take(...)` wrapper contract resolver:
   - [StanTakeContractResolver](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Stan/StanTakeContractResolver.php)
5. Shrunk the JSS bridge:
   - removed JSS-local reserved helper return tables from [JssSemanticValidator](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Jss/JssSemanticValidator.php)
   - replaced JSS-local wrapper-family `take(...)` regex ownership with STAN-owned contract lookup
   - moved helper-family normalization behind the JSS call-surface adapter
6. Shrunk STAN-local duplication:
   - [StanFrontendClassifier](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Stan/StanFrontendClassifier.php) no longer knows JSS helper families and instead consumes normalized PHS-facing call targets

This is intentionally a first slice, not the end state. The most important remaining semantic bridge is that JSS still emits some direct validation messages for `take(...)` output typing rather than consuming a canonical STAN diagnostic result.

## STAN Implementation Slice Started

Started on 2026-06-15 in `codex/stan-jss-improvements`.

Implemented so far:

1. JSS summaries now emit explicit `take_contract` frontend classification requests for `take(...)` calls when the source type can be described from the current frontend summary facts.
2. `StanFrontendClassifier` now classifies `take_contract` requests through the STAN-owned `StanTakeContractResolver`.
3. STAN classification results now expose:
   - wrapper family
   - canonical output types
   - source type
   - canonical diagnostics for arity/output type mismatches
4. Focused JSS frontend tests now prove valid `take(text, err, fs.get(path))` classification and invalid output type diagnostics.

Completed follow-up in this branch:

1. Classified JSS emission now defers `take(...)` wrapper/source/output contract checks to STAN classification results.
2. STAN frontend diagnostics from invalid `take_contract` classifications are exposed through normal semantic results.
3. JSS still keeps the frontend-only shape guard that `take(...)` output slots must be simple local identifiers.
4. STAN runtime-helper return-type truth now reads the generated runtime shallow symbol surfaces. The strict IO shallow contracts were aligned with the real runtime (`io.open`, `io.read*`, `io.write`, `io.tell`, `io.seek`, `io.rewind`, `io.flush`, `io.close`), and strict fs mutator contracts were aligned as plain `bool` (`fs.mkdir`, `fs.touch`, `fs.rmdir`, `fs.remove`, `fs.copy`, `fs.rename`).
5. STAN frontend helper classifications now receive the active project runtime module list in normal workspace runs and can reject known helper calls whose required module is inactive.

Remaining bridge:

- Success-path narrowing after `if (take(...))` is not needed for the current JSS-to-PHS conversion prototype. It remains a later safety/ergonomics item, not a prerequisite for useful lowering.
- Module-gated helper availability has a first pass for current reserved helper families; broader profile/module edge cases should remain STAN/project-validation owned.

## Mandatory STAN Improvements

### 1. Shared wrapper contract service for `take(...)`

STAN should expose one reusable contract for:

- allowed source wrapper families:
  - `nullable<T>`
  - `result<T>`
  - `result_or_false<T>`
  - `result_or_bool<T>`
- required arity
- output slot shape
- output slot types
- basic success/failure interpretation

This should become the semantic authority used by:

- JSS
- PHS/PHP diagnostics
- future PHP++ frontends

It should replace duplicated frontend-local wrapper checks where practical.

### 2. Helper return-contract truth from STAN/runtime symbol truth

STAN should provide helper-call typing based on the real active runtime/profile surface, not frontend-local tables.

This includes:

- reserved helper-family calls such as `fs.get(...)`, `io.open(...)`, `json.decode(...)`, `dt.parse(...)`
- wrapper-vs-plain-value distinctions
- differences between:
  - `result<T>`
  - `result_or_false<T>`
  - `nullable<T>`
  - plain `bool`
  - plain scalar/string values

This avoids drift between:

- runtime shallow sources
- generator knowledge
- JSS semantic assumptions

Concrete observed pressure:

- the current JSS fs/io work exposed cases where shallow/runtime-symbol assumptions and real strict runtime behavior were not aligned
- examples included helpers that looked `result<T>`-shaped in one analysis surface but are plain `bool` or `result_or_false<T>` in the real runtime contract

So this item is not only about JSS convenience. It is also about making the STAN/runtime-symbol truth itself authoritative and internally consistent.

### 3. JSS-to-PHS member/operator classification

For the current prototype, the most important STAN contribution is answering semantic classification requests that let JSS lower to ready-PHS without building a second semantic engine.

STAN should keep answering:

- whether a dotted/member access should lower as instance access, static access, namespace/class access, constant access, or helper/function access
- whether a root identifier is local, imported, global, builtin, function, constant, class, or unresolved
- whether a JSS `+` expression is numeric addition, string concatenation, or an explicit dynamic boundary
- whether a normalized helper target exists and is callable in the current PHS/runtime/project context

This is conversion-critical because it decides the emitted PHS spelling (`->`, `::`, namespace-qualified names, helper function calls, and operator lowering).

Wrapper-aware success/failure path narrowing after `take(...)` is intentionally not part of this prototype-critical slice. The output variables are already declared and typed; later STAN work may add path-sensitive diagnostics such as warning about reading a success payload only on the failure path, but JSS-to-PHS conversion should not depend on that analysis.

### 4. Module-gated helper availability diagnostics

STAN should own diagnostics such as:

- helper family exists, but the required runtime module is not active
- helper name exists in another profile/module shape, but not in the current project
- reserved helper-family root is syntactically correct but semantically unavailable

This is already the agreed architecture direction in:

- [specs/planning/jss_module_namespace_proposal_2026_06_12.md](/home/alexv/__AI/simple_cpp/simple_cpp_01/specs/planning/jss_module_namespace_proposal_2026_06_12.md)

### 5. Shared helper-family classification service

STAN should be the semantic authority for:

- which reserved helper-family calls are valid
- what target/helper they lower to conceptually
- what type/wrapper contract they expose
- whether they are callable in the current environment

JSS emitter can still perform the final surface rewrite, but the meaning should come from STAN.

### 5a. Runtime shallow-source contract alignment

The STAN-visible runtime symbol catalog and shallow-source generation should be checked against the real strict runtime/helper contract so frontends do not consume inaccurate helper signatures.

This includes:

- wrapper family shape
- plain-value vs wrapper return distinctions
- helper parameter types
- profile-specific differences where they are real

Without this, even a perfectly layered JSS frontend would still be forced into local repair logic just to compensate for inaccurate STAN/runtime metadata.

### 6. Deeper mutation legality

STAN should own the hard cases around:

- nested index/keyed writes
- writes through dynamic carriers
- future alias/reference-sensitive mutation rules

The JSS frontend should not solve those with local structural heuristics.

### 7. Future nullable / undefined / optional-flow integration

When future work lands for:

- `undefined`
- broader `??`
- optional chaining
- richer lookup semantics

STAN should own the semantic integration rather than having JSS bolt on separate rules.

See also:

- [specs/planning/undefined_runtime_first_plan_2026_06_12.md](/home/alexv/__AI/simple_cpp/simple_cpp_01/specs/planning/undefined_runtime_first_plan_2026_06_12.md)

### 8. Source-first semantic diagnostics before runtime

STAN should improve diagnostics for failures that can be known before the built program executes.

This is distinct from runtime exception attribution. Runtime owns malformed data, invalid resource state, dynamic runtime shape, and missing generated/source location on thrown exceptions. STAN owns static semantic truth and should stop invalid code before runtime when the answer is knowable from source, symbols, runtime contracts, and project configuration.

See also:

- [specs/planning/runtime_source_diagnostic_exception_catalog_2026_06_13.md](/home/alexv/__AI/simple_cpp/simple_cpp_01/specs/planning/runtime_source_diagnostic_exception_catalog_2026_06_13.md)

## STAN Improvement Ownership List

| Area | Example | STAN-owned improvement | Not STAN-owned | Priority |
| --- | --- | --- | --- | --- |
| Helper contract truth | `json.decode(...)`, `fs.get(...)`, `io.open(...)`, `dt.parse(...)` | derive return/wrapper contracts from the active PHS/runtime symbol truth and expose them to frontend classification | runtime parse failure of malformed JSON contents | P0 |
| Module-gated helper availability | using `json.decode(...)` without the active `json` module, if/when modules are gated | diagnose unavailable helper/module/profile combinations before build/run | filesystem state or malformed runtime data | P0 |
| `take(...)` contract diagnostics | `take(text, err, fs.get(path))` with wrong output type or arity | canonical diagnostics for arity, wrapper family, output slot type, and error slot compatibility | runtime result value being success/failure for a particular file path | P0 |
| `take(...)` success-path narrowing | `if (take(value, err, source)) { ... }` | later safety diagnostics for path-sensitive payload use | whether the runtime branch actually succeeds for a particular input | Later |
| Frontend classification requests | JSS identifier/member access/`+` requests | answer from symbol/type indexes and return source-ranged diagnostics for unresolved or ambiguous sites; especially dot/member lowering decisions | executing lowered code or recovering runtime stack/source locations | P0 |
| Binary/operator type matrix | JSS/PHS `+`, `==`, `!=`, comparison, numeric/string operations | use the PHS/PHP++ operator matrix and type resolver rather than frontend-local guesses | runtime `mixed` value contents unless statically known | P0 |
| Dynamic boundary discipline | assigning `dynamic`/`mixed`, using decoded JSON fields | diagnose obvious invalid static boundary use and require explicit stabilization when the contract demands it | actual runtime JSON shape, missing runtime key, malformed JSON text | P1 |
| Typed container/index mutation legality | `hash<T>`, `vector<T>`, nested writes, keyed mutation | validate known type/key/write legality from source and symbol information | runtime out-of-range/dynamic-shape failures that cannot be known statically | P1 |
| Nullable and optional-flow rules | `?T`, future `undefined`, future optional chaining | shared cross-frontend narrowing and absence semantics | runtime absence value implementation and runtime exception formatting | P1 |
| Class/member visibility and contracts | public/static/constructor/member access from JSS lowering | reuse normal PHS visibility, static/member, inheritance, and constructor diagnostics | runtime null pointer/member access once statically permitted | P1 |
| Project-wide symbol resolution | namespaces/imports/classes/functions/constants across `.jss` and `.phs` | keep one project symbol index and classify JSS through it | frontend parsing of unsupported JS syntax | P0 |
| Unsupported-but-parseable semantic forms | syntax that parses but requires unsupported semantic truth | produce canonical source-ranged diagnostics where STAN has enough context | syntax-only rejection such as unsupported tokens/operators | P2 |

## Explicit Role Split

Use this split when deciding where to add code:

| Question | Owner |
| --- | --- |
| Is the JSS syntax allowed and mechanically lowerable? | JSS frontend |
| What PHS helper/function does this frontend spelling mean? | frontend adapter produces normalized target; STAN validates semantic truth |
| Is the helper available in this project/profile/module set? | STAN/project validation |
| What wrapper/result type does a call return? | STAN from runtime/PHS symbol truth |
| Did a runtime input file exist? | runtime/application logic |
| Was JSON text malformed at runtime? | runtime |
| Can the runtime failure be shown at `main.jss:<line>`? | runtime exception details plus project diagnostic remapping |
| Should `take(...)` output typing be accepted? | STAN |
| Should a JSS local variable be syntactically accepted as a `take(...)` output slot? | JSS frontend shape rule |
| Should dynamic/mixed runtime value shape be accepted at execution time? | runtime, with STAN only handling statically provable cases |

## What JSS May Still Validate Locally

These are acceptable frontend-local checks because they are syntax/shape oriented:

- parser rejection of unsupported JS syntax
- reserved helper-root spellings in namespace/import syntax
- narrow bool/truthiness guardrails when they are purely frontend subset rules
- local AST-shape checks required before STAN can even interpret a request

These should stay small and explicit.

## What JSS Should Avoid Adding Locally

Do not add new JSS-local semantic systems for:

- wrapper extraction semantics beyond the current temporary bridge
- helper return-contract truth beyond the current temporary bridge
- module availability decisions
- deeper mutation legality
- future absence/undefined semantics
- richer control-flow narrowing

If a new JSS feature needs those, add the dependency here first and treat the JSS side as limited until STAN support exists.

## Current Continuation Rule

JSS work may continue when it fits one of these categories:

1. Pure syntax/frontend surface work
2. Emission/lowering that consumes existing STAN answers
3. Narrow sample expansion that stays within already-known semantic contracts
4. Temporary small guardrails that are explicitly listed here as bridges

JSS work should pause for STAN follow-up when it would otherwise require:

1. new wrapper semantics
2. new helper contract truth
3. module-gated semantic decisions
4. deeper mutation legality
5. new cross-frontend type/narrowing behavior
6. project-wide symbol/type decisions

## Immediate Follow-Ups

1. Keep improving JSS-to-PHS classification requests and results for identifiers, dotted/member access, helper calls, and operators.
2. Broaden module-gated helper-family diagnostics beyond the current reserved-helper first pass only where project/profile truth is already explicit.
3. Continue auditing runtime shallow-source / STAN-visible helper signatures outside the current reserved JSS helper families.
4. Extend canonical STAN `take(...)` diagnostics into broader PHS/editor-facing flows where practical.
5. Add source-ranged STAN diagnostics for frontend classification failures that are currently surfaced as JSS-local fallback errors.
6. Treat wrapper-aware `take(...)` success-path narrowing as later STAN safety work, not a JSS prototype blocker.
7. Keep new JSS feature work constrained to frontend-only progress until those items land.
