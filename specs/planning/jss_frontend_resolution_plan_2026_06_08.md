# JSS Frontend Resolution Plan
Doc Status: planning

Date: 2026-06-08

Purpose: capture the current discussion direction for a JavaScript-like authoring surface for Simple C++ without making it a semantic authority.

## Working Name

`JSS` means a JavaScript-like source surface for Simple C++.

It should not be described as full JavaScript support. The intended surface is closer to ES6-shaped syntax constrained to the capabilities and semantics already available through the current strict PHP++ / PHS surface.

## Core Constraint

JSS should have no independent runtime semantics beyond what can be resolved into current strict PHS and the existing Simple C++ runtime model.

JSS should feel like JavaScript in authoring ergonomics where that syntax can compile into explicit Simple C++ semantics. It should not recreate JavaScript features whose main behavior depends on a live JavaScript interpreter, JIT, or dynamic prototype/object runtime.

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
- `undefined`
- loose equality/coercion
- async / await / Promise semantics
- dynamic imports or JavaScript module semantics
- object spread/destructuring unless a narrow PHS-equivalent rule is later specified

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
- a known string operand makes the expression become PHS string concatenation `.`
- unknown operand types produce a JSS/STAN diagnostic
- `mixed` / `dynamic` operands may lower to a runtime helper such as `js_plus(...)`

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

## STAN-JSS Resolution

STAN should not become a general source-repair engine.

Instead, when invoked with a JSS map, STAN should:

- run enough normal symbol/type analysis over the provisional PHS to answer mapped ambiguity questions
- treat marked undeclared provisional variables from JSS as unresolved symbol-role candidates before reporting normal undeclared-variable diagnostics
- resolve only sites explicitly marked in `.jssmap.json`
- return replace directives for resolvable sites
- return JSS-facing diagnostics for unresolved or invalid sites
- avoid changing normal PHS behavior when no JSS map is present

For example:

```json
{
  "replacements": [
    {
      "siteId": "site_1",
      "replacement": "User::make",
      "reason": "left side resolves to class symbol User"
    }
  ],
  "diagnostics": []
}
```

If STAN cannot decide safely, it should not guess.

Example diagnostic:

```text
Ambiguous JSS member access `Foo.bar`: cannot decide static class access vs instance access.
```

For variable-biased sites, the directive may replace a larger range than one token. For example, provisional `$User->make` may be replaced by `User::make`, and provisional `$print` may be replaced by `print`.

## JSS Replacer

The replacer should be deliberately dumb.

Inputs:

- provisional PHS
- `.jssmap.json`
- STAN replacement directives

Responsibilities:

- find each site by id
- verify the current provisional PHS still matches the expected range/token
- apply the replacement
- emit final PHS
- report replacement conflicts as frontend errors

The replacer should not perform semantic reasoning.

## Two-Pass Validation Model

Preferred flow:

```text
Pass 1: STAN-JSS resolution on provisional PHS + JSS map
Apply replacements
Pass 2: normal STAN on final PHS
Then build/generate as usual
```

The first pass is a resolution/advisory pass. The second pass remains the real build gate.

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
