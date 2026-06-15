# JSS Frontend Resolution Plan
Doc Status: planning

Date: 2026-06-08

Purpose: capture the current discussion direction for a JavaScript-like authoring surface for Simple C++ without making it a semantic authority.

Related planning:

- `specs/planning/language_surface_enrichment_audit_2026_06_12.md`

## Working Name

`JSS` means a JavaScript-like source surface for Simple C++.

It should not be described as full JavaScript support. The intended surface is closer to ES6-shaped syntax constrained to the capabilities and semantics already available through the current strict PHP++ / PHS surface.

## Core Constraint

JSS should have no independent runtime semantics beyond what can be resolved into current strict PHS and the existing Simple C++ runtime model.

JSS should feel like JavaScript in authoring ergonomics where that syntax can compile into explicit Simple C++ semantics. It should not recreate JavaScript features whose main behavior depends on a live JavaScript interpreter, JIT, or dynamic prototype/object runtime.

The preferred language direction is JS-flavored syntax with strict typed semantics. JSS should be pleasant for authors who like JavaScript/TypeScript shape, while staying a typed Simple C++ language where `int`, `float`, `string`, `bool`, `null`, and future absence states remain distinct unless a conversion is explicit.

Useful JS-like ergonomics include:

- `let` / `const`
- familiar control flow
- class and method syntax
- dot-style member access
- familiar call and expression shape

Dynamic behavior is allowed only at explicit dynamic boundaries such as `mixed` / `dynamic` values. Runtime helpers such as a future `js_plus(...)` should be boundary tools, not the foundation of a hidden JavaScript interpreter.

Initial non-goals include:

- prototype semantics
- JavaScript `this` binding rules
- JavaScript-compatible `undefined` behavior in the current subset
- loose equality/coercion
- async / await / Promise semantics
- dynamic imports or JavaScript module semantics
- object spread/destructuring unless a narrow PHS-equivalent rule is later specified

Future `undefined` note: a distinct absence value is desirable for both JSS and PHP++/PHS, especially for object/hash/member/index lookup where `null` can be a present value and `undefined` should mean absent or not initialized. That future feature should be designed as a real typed language value/state, not as JavaScript loose-runtime compatibility.

## Proposed Pipeline

```text
.jss
  -> JSS tokenizer
  -> JSS AST parser
  -> JSS frontend summary provider
  -> STAN semantic core over frontend summaries
     - classifies identifier roles
     - classifies member access as instance/static
     - classifies + as numeric addition or string concatenation
     - validates unsupported JS-shaped semantics
  -> JSS-to-PHS resolver/emitter
     - uses STAN flags/classifications
     - emits final .phs
  -> existing PHS build flow
```

This keeps the JSS frontend as a source adapter rather than a second native lowering pipeline, while avoiding a large provisional-PHS repair pass.

The earlier provisional-PHS model remains useful as a possible prototype path, but the current preferred direction is:

```text
frontend parsing first, frontend summary extraction second, STAN classification third, PHS emission fourth
```

The PHS lowering to C++ then follows the normal existing process.

## Frontend Summary Provider Direction

STAN should not be coupled permanently to one PHP/PHS extraction path, but it also should not be rewritten around a large universal AST abstraction.

The preferred direction is a small frontend summary provider shape.

Current PHP/PHS analysis already follows this rough shape:

```text
PHS source
  -> InputLoader
  -> IrBuilder
  -> FrontEndSymbolExtractor
  -> file summary arrays
  -> STAN semantic pass
```

For JSS, the analogous path should be:

```text
JSS source
  -> JSS tokenizer/parser
  -> JSS frontend summary provider
  -> file summary arrays plus JSS classification requests
  -> STAN semantic pass / classification services
```

The summary provider should expose the analysis facts STAN already needs, plus narrow JSS classification requests, such as:

- declarations
- lexical scopes
- function and method signatures
- class/interface declarations
- properties and methods
- expression nodes
- identifier references
- member access nodes
- call nodes
- operator nodes
- source ranges and original source text
- frontend language/profile metadata

Under this model:

- the existing PHP/PHS extractor can keep using `InputLoader -> IrBuilder -> FrontEndSymbolExtractor`
- a future JSS extractor can produce compatible summaries from its own tokenizer/parser
- STAN remains mostly summary-driven
- STAN returns classifications/flags rather than directly rewriting source or owning JSS emission
- a frontend-specific resolver/emitter turns those classifications into PHS code

This direction is intentionally lighter than:

```text
PHP AST + JSS AST -> universal AST -> rewritten STAN
```

The goal is incremental reuse of STAN's semantic core, not a full STAN rewrite.

Example classifications:

- identifier role: local variable, parameter, class, function, constant, unresolved
- member access: instance member, static member, invalid/ambiguous
- binary `+`: numeric addition, string concatenation, dynamic runtime helper, invalid/ambiguous
- call target: function, method, static method, callable variable, unresolved

## JSS `+` Resolution

JSS should preserve JavaScript-like `+` ergonomics, but resolution should be explicit:

- known numeric operands become PHS `+`
- `mixed` / `dynamic` operands lower to the runtime helper `js_plus(...)`
- a known string operand makes the expression become PHS string concatenation `.` only when no operand is `mixed` / `dynamic`
- unknown operand types produce a JSS/STAN diagnostic
- known nonnumeric/nonstring static operands produce a JSS/STAN diagnostic

The runtime helper path should only be used when STAN positively identifies a dynamic carrier. It should not hide unresolved static analysis.

Example:

```js
let name: string = "Ada";
let suffix: mixed = load_suffix();
name + suffix
```

May lower to:

```php
js_plus($name, $suffix)
```

or an equivalent helper once the JSS runtime boundary is specified.

This helper should resolve runtime dynamic cases without turning the whole generated executable into a JavaScript interpreter.

Accepted P0 decision: the current subset intentionally does not implement broad JavaScript coercion. `js_plus(...)` is the explicit boundary for `mixed` / `dynamic` operands; static operands must classify as numeric addition, string concatenation, or an invalid `+` site.

## JSS Equality Policy

Accepted P0 decision: strict equality and inequality are the supported JSS equality surface for the current subset.

- `===` lowers directly to PHS `===`
- `!==` lowers directly to PHS `!==`
- `==` and `!=` are temporarily allowed to pass through because the underlying PHS/PHP++ surface already supports them
- their long-term JSS policy remains under review and should be revisited once truthiness/coercion/`undefined` policy is settled

## Future `undefined` / Absence Semantics

`undefined` is not part of the current implemented JSS subset, but it is a desired future language feature for both JSS and PHP++/PHS.

The intended meaning is:

- `null` means a value is present and intentionally empty or absent-by-value
- `undefined` means no value is present, such as a missing hash key, missing object/member slot, or not-initialized state
- `undefined` must remain distinct from `null`, `false`, `0`, and `""`
- checks should use strict equality, such as `value === undefined` and `value !== undefined`
- loose checks such as `value == undefined` remain rejected
- truthiness checks such as `if (value)` should not be used to test presence

Possible future examples:

```js
if (row["label"] === undefined) {
    print("missing");
}

if (row["label"] === null) {
    print("present but null");
}
```

This likely needs explicit type forms such as `T | undefined` and `T | null | undefined`, plus STAN support for lookup-result typing and presence checks. It should be designed once in the core language model so JSS and PHP++/PHS do not grow separate absence semantics.

## JSS Strict Type Defaults

Accepted current defaults:

- `let` is the supported local declaration form.
- top-level and class `const` declarations are supported.
- local/block/loop `const` declarations are rejected until local immutability has an explicit PHS/STAN contract.
- `int` and `float` are distinct. Numeric `+` preserves `int + int -> int`; any `float` operand makes arithmetic float-like. Assigning a statically known `float` expression to `int` requires an explicit conversion.
- Static `string + value` is allowed only when the other operand has a known printable/cast path in the current subset: `string`, `int`, `float`, or `bool`.
- `string + null` is rejected until an explicit conversion/presence spelling exists.
- `mixed + value` and `dynamic + value` lower through `js_plus(...)`; this is an explicit runtime boundary, not general JS coercion.
- `==` and `!=` are rejected; use `===` and `!==`.
- `if`, `while`, `do while`, and classic `for` conditions require a `bool` expression. Bare truthiness for `string`, `int`, `float`, `null`, nullable, hash, vector, object, `mixed`, or unknown values is rejected. Use explicit comparisons or predicates.
- `null` requires an explicit nullable target type such as `?T` or `T | null`.
- Array literals require an explicit `vector<T>` target type.
- Object/hash literals require an explicit `hash<T>` target type. They are typed hash construction syntax, not JavaScript dynamic object bags.

## JSS Class Visibility

Accepted current subset:

- class fields, methods, constructors, and class constants are public by default
- explicit `public` is accepted as equivalent to the default public lowering
- `private` and `protected` keywords are rejected in JSS
- ES6-shaped private fields/methods using `#name` are a P1 candidate, not part of the current subset

If `#name` is added later, it should lower to the existing PHS/private visibility model and rely on STAN/S2S for visibility validation rather than adding a second access-control checker in JSS.

## JSS Constructors

Accepted current subset:

- constructors use ES6-shaped `constructor(...) { ... }`
- constructors lower to PHS `__construct(...)`
- constructors do not declare return types
- a class may declare only one constructor
- direct `__construct(...)` member spelling is rejected in JSS

STAN/S2S remain responsible for constructor call compatibility, inheritance behavior, and downstream class contract diagnostics.

## JSS Function Signatures

Accepted current subset:

- function parameters must have explicit types
- functions and methods must have explicit return types, including `void`
- constructors remain returnless
- variadics, references, callable parameters, and closure/arrow-function lowering are outside the current subset

JSS only enforces the signature syntax shape. STAN remains responsible for return-path checks, return-value compatibility, call-site compatibility, and project-wide function/method contract diagnostics.

## JSS Tokenizer / Shallow Parser

The first frontend pass should be intentionally small, but it now needs to produce a real JSS AST rather than only token rewrites.

For consistency with the current toolchain, the JSS tokenizer and AST parser should be written in PHP. The existing `scpp` tooling, PHS frontend, and STAN implementation are PHP-based, so a PHP-native JSS frontend can be called directly from the CLI/build/STAN flow without shelling out to Node or carrying a separate parser runtime dependency.

Babel, Tree-sitter, or other external parsers may remain useful references, but they are not the preferred implementation dependency for this plan.

Responsibilities:

- recognize enough statement structure to distinguish block braces from expression/object-literal braces
- parse supported declarations, statements, and expressions into JSS AST nodes
- preserve source ranges and original source text for diagnostics and later PHS emission
- represent ambiguous sites as AST nodes rather than as already-rewritten PHS tokens
- reject unsupported syntax early when it is clearly outside the JSS surface

The shallow parser may handle niched cases such as `{}` by context:

- after `if (...)`, `while (...)`, `for (...)`, `function ...`, or method/class declarations, braces are blocks or bodies
- in expression positions such as assignment, return, or call arguments, braces are object/hash literals if that feature is supported

## JSS AST Parser Difficulty

Writing a full JavaScript parser is out of scope and should not be attempted.

Writing a narrow JSS parser is realistic if the supported grammar is intentionally small and documented.

Estimated difficulty:

- tokenizer for narrow JSS: medium
- parser for declarations/control flow/classes/basic expressions: medium
- robust expression parser with precedence and good diagnostics: medium-high
- full JavaScript compatibility: not a goal

The likely hard parts are:

- expression precedence and associativity
- string/template literal handling if template strings are supported
- distinguishing block braces from expression/object-literal braces
- class body syntax
- source ranges that survive later diagnostics
- rejecting unsupported JavaScript forms cleanly

Using Babel or Tree-sitter may still be useful later, but a handwritten parser for a narrow JSS subset may be simpler for the first implementation because JSS intentionally does not accept full JavaScript semantics.

## Ambiguity Metadata

This section describes the earlier provisional-PHS sidecar path. It remains useful as a prototype or fallback, but the current preferred direction is AST classification before PHS emission.

The provisional PHS should be accompanied by a `.jssmap.json` sidecar.

The sidecar should identify only sites that are eligible for STAN-JSS resolution. Normal PHS generated from unambiguous JSS should not be rewritten by STAN.

The current preferred model is variable-biased provisional PHS:

- JSS expression identifier `user` becomes provisional PHS `$user`
- JSS member access `user.name` becomes provisional PHS `$user->name`
- JSS member access `User.make()` becomes provisional PHS `$User->make()`
- JSS call `print(user.name)` may become provisional PHS `$print($user->name)` when the callee role is not known locally

STAN-JSS may then promote marked provisional variable-looking forms into non-variable PHS forms:

- `$User->make()` can become `User::make()` when `User` resolves to a class symbol and `make` resolves as a static method
- `$print(...)` can become `print(...)` when `print` resolves to a function symbol
- `$user->name` remains unchanged when `user` resolves as a local or otherwise valid variable

This lets the JSS tokenizer stay mostly mechanical while keeping role decisions in the analysis layer that can see project and dependency symbols.

Example site kinds:

- `identifier_role`
- `member_access_operator`
- `call_callee_role`
- `binary_plus_operator`
- future narrow syntax sites that require symbol/type information

Each site should carry enough original-source intent for STAN to answer a narrow question without rediscovering the JS form from provisional PHS alone.

Example shape:

```json
{
  "source": "main.jss",
  "generated": ".prism/jss/main.provisional.phs",
  "sites": [
    {
      "id": "site_1",
      "kind": "member_access_operator",
      "range": { "start": 5, "end": 7 },
      "sourceText": "User.make",
      "provisionalText": "$User->make",
      "left": "User",
      "member": "make",
      "call": true,
      "candidates": ["->", "::"]
    }
  ]
}
```

Ranges should point into the provisional PHS and should be stable enough for a replacer to verify the expected token before applying a directive.

## STAN Summary Interface Direction

Current STAN does not consume the raw PHP/PHS AST directly. The current PHP/PHS flow is:

```text
PHS source
  -> InputLoader
  -> IrBuilder
  -> PHP/PHS IR objects
  -> FrontEndSymbolExtractor
  -> file summaries
  -> STAN semantic pass
```

The agreed JSS direction is to make the file summary boundary the shared frontend interface:

```text
JSS AST
  -> JSS frontend summary builder
  -> STAN frontend summaries plus classification requests
  -> STAN semantic core builds symbol/type indexes
  -> STAN frontend classifier answers JSS requests
  -> JSS emitter consumes classification results
```

This means STAN should receive frontend metadata and answer frontend-specific classification requests using the same symbol index/type machinery it already uses for PHS. JSS should not grow a separate local semantic resolver for classes, constants, properties, methods, imports, or `+` type behavior.

The summary format should become an intentional contract rather than an informal collection of arrays. It is acceptable to replace or wrap the existing arrays with well-defined structures, as long as the migration is incremental and does not churn unrelated STAN behavior.

The preferred named concepts are:

- `StanFrontendSummary`
- `StanFrontendSummaryBuilder`
- `StanFrontendClassificationRequest`
- `StanFrontendClassificationResult`
- `StanFrontendClassifier`

The cache boundary may still serialize these structures as arrays at first, but cache entries should be versioned so old summaries invalidate cleanly.

## JSS Classification Requests

The first STAN-JSS classification milestone should answer only the cases that currently tempt the JSS emitter to perform local structural classification:

- identifier role: local variable, parameter, class, function, constant, imported alias, unresolved
- member access role: instance property/method, static property, static method, class constant, namespace path, invalid/ambiguous
- binary `+` role: numeric addition, string concatenation, dynamic helper candidate, invalid/ambiguous

The JSS summary should carry enough information for these requests without asking STAN to reconstruct JSS syntax from generated PHS. Example request shape:

```php
[
    'id' => 'jss_expr_12',
    'kind' => 'member_access',
    'path' => 'src/main.jss',
    'line' => 14,
    'column' => 9,
    'base' => 'Box',
    'member' => 'count',
    'is_call' => false,
]
```

Example result shape:

```php
[
    'id' => 'jss_expr_12',
    'kind' => 'static_property',
    'target' => 'Box::$count',
    'diagnostics' => [],
]
```

If STAN cannot decide safely, it should not guess. It should return an unresolved or ambiguous classification plus a JSS-facing diagnostic.

The JSS emitter should lower syntax from classification results. It may still handle purely syntactic rewriting such as `print(...)` to `echo`, but it should not decide ambiguous symbol roles on its own.

## Existing STAN Cache And API State

STAN already has useful caches:

- per-file summary cache under `.prism/cache/stan/files/`
- workspace state under `.prism/cache/stan_state.php`
- in-memory LSP snapshot cache for document diagnostics, symbols, hover, definition, and references

These caches store extracted summaries, symbol indexes, diagnostics, and resolved type information. They are not currently a first-class frontend classification API.

The JSS integration should add a small STAN classification service rather than bolting request handling directly into diagnostics-only paths. The service can reuse the existing symbol index and type resolver, and the resulting classifications can be included in the semantic result and persisted in the workspace state if useful.

## Roadmap

### Milestone 1: Freeze Local JSS Semantics

Goal: stop expanding local symbol/type decisions in the JSS emitter.

Tasks:

- identify current JSS emitter local classification sites
- keep purely syntactic emission local
- mark semantic decisions that must move to STAN
- keep the current sample suite passing while preparing the migration

Examples of decisions to move:

- `Box.count` as static property vs class constant
- `Box.value()` as static method vs namespaced function path
- bare `BASE` as constant vs variable
- `a + b` as numeric addition vs string concatenation

### Milestone 2: Define STAN Frontend Summary Structures

Goal: make the summary contract explicit.

Tasks:

- introduce typed summary/request/result structures or builders
- document the minimum summary fields used by STAN today
- let existing PHS extraction build the structured form or pass through a normalizer
- keep array serialization at cache boundaries if that avoids broad churn
- version the cache shape

Initial fields should cover:

- frontend metadata
- root and namespaced uses/constants/functions/classes
- class parent/interfaces/properties/methods
- function/method params, return types, locals, assignments, calls, property reads
- classification requests

### Milestone 3: Build JSS Summary Extraction

Goal: produce STAN-compatible summaries from JSS AST.

Status: started. A first `JssSummaryExtractor` now emits STAN-shaped root declarations plus `frontend_classification_requests` for ambiguous identifiers, member access, and binary `+`. Class summaries include properties, methods, and class constants. Binary `+` requests include simple operand type hints from literals, constants, typed locals, function-like parameters, typed foreach values, typed classic `for` initializers, and conservative literal/known-identifier local assignment hints.

Tasks:

- add a JSS summary builder under JSS-owned code
- extract declarations, imports, classes, properties, methods, locals, and calls from `JssNode`
- emit classification requests for ambiguous identifiers, member access, and binary `+`
- include source ranges for JSS-facing diagnostics
- avoid generating PHS as the source of semantic facts

### Milestone 4: Add STAN Frontend Classifier

Goal: answer JSS classification requests from STAN's symbol/type machinery.

Status: started. A first `StanFrontendClassifier` can consume `frontend_classification_requests` and classify basic identifier/member-access requests from the STAN symbol index, including static property, static method, and class constant access. Known-class member reads must resolve as a static property or class constant, otherwise STAN returns an invalid member diagnostic. It returns diagnostics for unresolved identifiers. It can also classify simple binary `+` requests as string concatenation or numeric addition when JSS summaries provide operand type hints, and it returns an unresolved diagnostic when the operands are not safe to classify. `StanSemanticPass` now exposes these results as `frontend_classifications`, and STAN state can persist them. Classification results now also carry request metadata such as request kind, member chain, expression key, and call/read shape so frontend emitters can consume them without knowing STAN request ids.

Tasks:

- add a small classifier service inside STAN
- run it after symbol index/type facts are available
- return classification results keyed by request id
- return JSS-facing diagnostics for unresolved or ambiguous requests
- preserve normal PHS behavior when no frontend requests are present

### Milestone 5: Make JSS Emission Classification-Aware

Goal: remove local semantic guessing from the JSS emitter.

Status: current sample mirror covered. `JssEmitter` can accept STAN frontend classifications and use member-access classifications to lower static properties, static methods, class constants, namespace-qualified class access, namespace aliases, and instance members. It can also consume root constant identifier classifications, root function callee classifications, builtin function/global classifications, and simple binary `+` classifications. `JssTranspiler` has an explicit STAN-classified emission path for proving this flow while the normal sample path remains unchanged. That classified path now keeps syntactically declared locals, parameters, foreach variables, and classic `for` initializer variables local, and rejects unresolved `+` sites, STAN-reported invalid member access, missing member-access classifications, unresolved non-local identifiers, and unresolved identifier function callees instead of falling back silently. The sample mirror test now verifies all current JSS samples through both the normal and STAN-classified emission paths.

Remaining hardening before making classified emission the only/default project build path:

- replace informal arrays with structured summary/request/result helpers or normalizers: started with JSS-owned file summary builder, request factory, and classification normalizer; summary version 3 now has the builder own declaration, class member, parameter, and typed-local summary shapes
- add source ranges for JSS-facing diagnostics: started for declaration summaries, statement/expression AST nodes, and classification requests/results; results now carry JSS path/line/column, and classification-origin emitter failures include source locations when available
- carry project-wide JSS summaries into the normal build STAN session: started; `.jss` files are now STAN source units and use `JssSummaryExtractor` in `StanFilePass`
- define dynamic-helper `+` policy for `mixed` / `dynamic`: implemented; STAN classifies `mixed`/`dynamic` JSS `+` as `dynamic_plus`, and JSS emission lowers it to `js_plus(left, right)`
- implement the actual `js_plus(...)` runtime/helper surface: implemented in the PHP runtime support layer and registered in strict/legacy runtime symbol registries
- expand project-level build/run validation: completed for a tiny strict `.jss` project using `compile_runtime`; build succeeded and the generated binary printed `6`; this is now covered by a dedicated focused project build/run test

Tasks:

- pass classification results into JSS emission
- lower member access, identifiers, calls, and `+` according to classifications
- fail with JSS diagnostics when required classifications are missing
- keep syntactic-only emission paths local and simple
- rerun the current JSS sample suite and update expected output only when behavior intentionally changes

### Milestone 6: Resume Syntax Expansion

Goal: continue manual conversions only after STAN is in the loop.

Status: unblocked for the current sample mirror. New syntax/sample expansion may resume as long as each new sample also passes the STAN-classified emission path, not only the normal local fallback path. Safe cast-path mirror expansion resumed with float/bool string concatenation, direct numeric print, nullable assignment, explicit-null nullable return samples, explicit void returns, inner-block assignment, multiple `use` imports, and namespace-scoped class cross-reference samples. Namespace constant access now has direct JSS sample coverage using the existing semicolon-style namespace surface.

Allowed next areas:

- namespace block and fully qualified constant support
- function and const imports
- late static syntax only after STAN policy is clear
- append/update syntax only after the append/update policy backlog is resolved

Still blocked until policy:

- references
- wrapper/result flow
- ternary
- runtime modules
- JavaScript dynamic semantics

### ES6-Shaped Clean Mapping Candidates

These are JSS syntax additions where ES6-style authoring appears to map cleanly into existing strict PHS, and where support is not yet complete in the current JSS frontend. This list is intentionally limited to features that do not require JavaScript runtime semantics, hidden coercion, prototype behavior, or broad new STAN policy.

High-confidence candidates now covered by first implementation samples:

- arithmetic operators `-`, `/`, `%`: map directly to PHS operators
- comparison operators `<=`, `>=`: map directly to PHS comparisons
- logical `||`: maps directly to PHS `||` with explicit bool-oriented operands
- pre-increment, pre-decrement, and post-decrement: map directly to PHS `++$x`, `--$x`, and `$x--`
- compound assignment for simple targets: `+=`, `-=`, `*=`, `/=`, `%=` map to PHS compound assignment where the target shape is already supported
- `else if`: parses as nested `if` in the `else` branch
- `do { ... } while (condition);`: maps directly to PHS `do`/`while`
- `switch` with literal/scalar `case` labels and `default`: maps to PHS `switch` without fallthrough-sensitive transformations
- nullable literal and type spelling: `null`, `?T`, and `T | null` map to nullable/null PHS spellings
- nullable assignment and typed nullable returns: covered for explicit null/value flow; null coalescing remains a separate policy question
- void returns: `function f(): void { ... }` maps directly to PHS `void`
- multi-parameter functions and methods in the sample mirror: first coverage added
- explicit `return;` in void functions: covered without adding callable/reference behavior
- `use const`: maps to PHS `use const ...;` and participates in STAN identifier classification
- multiple imports: class, function, and const imports can coexist in one JSS namespace scope
- namespace-qualified static/member access: cross-namespace static call coverage added on the existing STAN path
- namespace-qualified constants: cross-namespace and namespace-local constant access now lower through the classified path
- object/hash literal with static scalar keys: read-only hash literal/access coverage added before mutation samples
- typed vector/hash property declarations from ES6 class fields: first typed hash property coverage added
- namespace-scoped class cross-references: nullable properties can refer to peer classes in the same namespace
- `for (... of ...)` shadowing cases: value/key-value foreach support already exists; remaining shadowing sample expansion can proceed as ordinary sample work

Second-wave candidates:

- template literals without interpolation: implemented as ordinary PHS strings
- template literals with simple `${identifier}` and dotted `${a.b}` interpolation: implemented as explicit PHS string concatenation; that dotted subset also covers already-classified static/class-constant chains such as `${BuildInfo.version}` and `${BuildInfo.LABEL}`; broader JavaScript interpolation remains unsupported
- static class field polish: covered by `static name: T = value;` lowering to PHS static properties
- vector append: implemented as statement-form `items.push(value)` lowering to `$items[] = value;`
- index assignment and keyed hash update: implemented for existing target shapes as direct PHS `target[index] = value;`
- cast-path print/concat samples: int, float, and bool values now have clean JSS coverage for direct `print(...)` and string-operand `+` lowering
- explicit nullable return from hash slots: covered where control flow uses `=== null` rather than truthiness
- arrow functions: still blocked because PHS callable/closure support is not a stable target surface for JSS lowering yet

Still not clean ES6-to-PHS mappings:

- destructuring assignment or parameters
- spread/rest for arrays, objects, or calls
- default object/array parameter mutation semantics beyond existing PHS defaults
- optional chaining and nullish coalescing unless mapped to explicit strict nullable rules
- loose equality, truthiness-driven conditions, `NaN`, `Infinity`, or JS number edge behavior
- JavaScript-compatible `undefined`; a future strict typed absence value remains desirable
- async/await, promises, generators, iterators, imports/exports, prototypes, closures over JavaScript `this`, or dynamic object shape semantics

## Implementation Isolation For Mergeability

JSS implementation work should keep new code separate from existing PHP/PHS, STAN, generator, and runtime paths as much as practical.

The preferred shape is:

- new JSS tokenizer, parser, AST, summary provider, resolver, emitter, and mapping/replacement code live in JSS-owned modules or directories
- existing STAN/PHS/generator code receives only narrow adapter hooks or explicit service boundaries needed to consume JSS summaries and classification requests
- broad edits to shared code should be avoided unless they define a reusable interface that also benefits the existing PHS flow
- experimental JSS prototype code should not be mixed into mature PHS lowering logic
- generated/intermediate JSS artifacts should stay under clearly named JSS paths such as `.prism/jss/`

This separation should make future Git merges easier, reduce conflict risk with ongoing STAN/PHS work, and keep JSS behavior reviewable as a frontend-specific addition rather than a hidden rewrite of existing subsystems.

## Current Allowed JSS Subset Examples

The currently healthy direction is to expand only where lowering stays explicit and semantic truth still comes from normal PHS/STAN paths.

Examples that fit the current subset:

```js
function show(path: string): void {
    let text: string = "";
    let err: string = "";
    if (!take(text, err, fs.get(path))) {
        return;
    }
    print(`file ${text}`, "\n");
}

class Greeting {
    public prefix: string = "Hello";
    public name: string = "Ada";

    constructor(name: string = "Ada") {
        this.name = name;
    }

    public render(): string {
        return this.prefix + " " + this.name;
    }
}

print(dt.format_now("Y-m-d"), "\n");
print(`v${BuildInfo.version}:${BuildInfo.LABEL}`, "\n");
```

These examples stay within the current rules:

- helper families use reserved roots such as `fs.*`, `io.*`, `json.*`, and `dt.*`
- wrapper extraction uses the existing `take(...)` flow
- classes use public/default members, explicit constructor syntax, and explicit return types
- template literals stay at literal text plus identifier or dotted-member interpolation
- dynamic truthiness, optional chaining, loose equality, and arbitrary interpolation expressions remain outside the supported subset

## Current Open Questions

- How much of the JSS tokenizer/shallow parser should be handwritten before moving to Babel, Tree-sitter, or another parser?
- Should the first prototype require `$` in user-written JSS, or should it immediately use the variable-biased no-dollar model?
- Should `+` in JSS be allowed to mean string concatenation when STAN can prove a string operand, or should JSS require an explicit concat helper/operator?
- How should JSS object/hash literals map onto current PHS array/hash semantics?
- How should dependency-visible declarations and `@lib-export` be represented in JSS syntax?
- How should source diagnostics chain from final PHS back to original JSS once both the JSS map and existing PHS line maps are involved?

## Design Guardrails

- Only marked JSS ambiguity sites are eligible for replacement.
- Variable-biased provisional PHS is allowed only for JSS-origin code with sidecar metadata.
- Marked JSS provisional variables may be held as unresolved symbol-role candidates during STAN-JSS resolution; unmarked normal PHS variables keep normal PHS diagnostics.
- STAN-JSS resolution returns directives; the replacer applies them.
- Unsupported JS semantics should fail as JSS diagnostics, not as confusing downstream PHS errors when possible.
- Generated PHS is an intermediate artifact, not the user-authored source of truth for JSS projects.
- The existing PHS generator/runtime flow remains the downstream authority for final lowering.
- JSS implementation code should remain isolated behind JSS-owned modules and narrow adapter boundaries where practical, to ease future Git merges.
