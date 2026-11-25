# JSS JS-Flavor Gap Discussion List
Doc Status: planning

Date: 2026-06-12

Purpose: save the current discussion list of JavaScript-flavored surface areas that are still missing, intentionally narrowed, or out of scope in the current JSS frontend. This note is a discussion aid, not semantic authority.

## 1. Loose / Dynamic Runtime Behavior

- truthiness everywhere
- `undefined`
- `null` + `undefined` interplay
- loose equality coercions
- string/number coercions
- object property access that can quietly fail or yield `undefined`

Current direction:

- most of this is intentionally not covered yet
- strict typed behavior is preferred over JS-style ambient coercion
- truthiness/coercion should stay aligned with PHS/PHP++ for now
- `undefined` should be runtime/PHS-owned first; the current intended first slice is a reserved PHS keyword usable only in explicit comparison forms such as `expr === undefined` / `expr !== undefined`, lowered by S2S to a compiler/runtime intrinsic rather than treated as a general ordinary value in JSS
- temporary implementation note:
  - JSS currently allows `==` and `!=` to pass through because PHS/PHP++ already has them
  - the exact JSS policy for loose equality/coercion is still under review and should be revisited explicitly

## 2. Flexible Object Model

- ad hoc object bags
- dynamic property creation
- prototype-ish mental model
- open-ended object mutation
- `obj[key]` and `obj.prop` behaving like normal JS dynamic objects

Current direction:

- typed `hash<T>`, `vector<T>`, and typed classes are preferred
- dynamic open-ended object semantics are not the default model
- dynamic object-bag semantics remain deliberately unresolved and should be discussed separately with concrete examples

## 3. Functional JS Ergonomics

- arrow functions
- closures
- callbacks everywhere
- inline lambdas in collection operations

Current direction:

- JSS is typed, so arrow functions may be supported only where the callable shape is explicit and maps to current PHS callable/lambda support
- first-pass shape: `let f = (x: int): int => x + 1;`
- parameter and return types should be required in the first slice
- no broad JavaScript inference/capture promise beyond what PHS already supports
- implemented first slice: local explicit expression-body arrows lower to PHS `fn(...)`

## 4. Modern ES6+ Expression Sugar

- destructuring
- spread / rest
- optional chaining
- richer template expressions
- computed property patterns
- default object destructuring params

Current direction:

- most of this is still missing
- some items may fit later, but only where semantics remain explicit and do not require a second semantic engine in JSS
- optional chaining should return `null` on a failed chain, not `undefined`, and should reuse/adapt guarded-path or `isset(...)` machinery where possible
- implemented first slice: `object?.member` lowers to PHS nullsafe `?->`
- JSS `delete expr` is desired as source syntax, but should lower to PHS `unset(expr)` and should not imply full JavaScript `delete` object semantics

## 4b. PHS-Shaped Advanced Syntax

- references / aliasing
- late static `static::`

Current direction:

- references should use `&` / ampersand and mimic the current PHS reference model directly; do not introduce a JSS-only `ref` keyword
- implemented first slice: `let alias = &value;` and `alias = &value;` lower to PHS `=&` for simple identifier targets
- late static should use the PHS spelling directly, such as `static::make()` or `static::VALUE`
- these are advanced typed-language features, not places where JSS needs to invent a JS-looking spelling

## 5. JS Module / Async World

- `import` / `export`
- promises
- `async` / `await`
- generators / iterators

Current direction:

- basically not in scope yet
- current JSS direction is not trying to clone the JS runtime/module ecosystem

## 6. “It Just Works Dynamically” Expectations

- mix values freely and let runtime coercion sort it out
- arrays / objects / strings behaving with familiar JS permissiveness
- mutation and lookup patterns that do not require explicit typing or normalization

Current direction:

- intentionally not the default posture
- JSS is currently closer to a typed strict language with JS-shaped syntax than to full permissive JavaScript semantics

## Discussion Plan

Discuss one category at a time and decide:

1. reject by design
2. support later through runtime-first work
3. support later through STAN-first work
4. support as narrow explicit syntax sugar only
5. support as a core language goal
