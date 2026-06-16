# JSS Runtime And PHS Prototype Buckets
Doc Status: planning

Date: 2026-06-13

Purpose: define what runtime and PHS capabilities are required for the first useful JSS prototype, what would be good shortly after, and what should stay deferred until later semantic/runtime work lands.

This is a planning artifact, not semantic authority.

## Framing

JSS is following the path:

```text
JSS -> frontend summaries/classification -> PHS -> normal build/runtime flow
```

That means JSS should not lower into a fake private intermediate language.

If JSS emits a capability, then:

- PHS must be able to represent it honestly
- the runtime must be able to execute its meaning honestly
- STAN should eventually be the semantic truth for it in the normal pipeline

This does **not** mean every capability must immediately become blessed hand-written PHS surface syntax. It does mean the capability must exist in the real downstream language/runtime model.

## Bucket 1: Required For JSS Prototype v1

These are the capabilities that should be considered part of the minimum real prototype surface.

### 1. Strict typed core already represented in PHS

- typed locals
- typed functions and methods
- explicit returns including `void`
- public class fields and methods
- constructors
- static fields, static methods, class constants
- namespaces and `use`
- typed `vector<T>` and `hash<T>`
- direct index/keyed read and write
- explicit nullable types and explicit null flow
- explicit bool-driven control flow
- narrow ternary and narrow `??`

Why:

- this is already the healthy strict subset JSS can target without inventing a second semantic system

### 2. Stable dynamic `+` boundary through `js_plus(...)`

Required downstream support:

- PHS must allow emitted `js_plus(...)` helper usage cleanly
- runtime must define `js_plus(...)` behavior for the accepted `mixed` / `dynamic` boundary

Why:

- JSS `+` ergonomics need one explicit dynamic escape hatch
- this should remain a boundary helper, not broad JavaScript coercion emulation

### 3. Strict helper-family capability under JSS helper spellings

Required downstream support:

- strict helper contracts for:
  - `fs_*`
  - `io_*`
  - `json_*`
  - `dt_*`
- those contracts must remain authoritative under the JSS helper-family spellings:
  - `fs.*`
  - `io.*`
  - `json.*`
  - `dt.*`

Why:

- JSS helper-family syntax is only useful if it lands on real strict PHS/runtime capabilities

### 4. Stable wrapper/result capability

Required downstream support:

- `nullable<T>`
- `result<T>`
- `result_or_false<T>`
- `result_or_bool<T>`
- shared `take(...)` behavior

Why:

- this is what turns JSS from a syntax demo into something that can express real filesystem/IO/JSON flows

### 5. Normal project build/run path for `.jss`

Required downstream support:

- `.jss` files participate in the normal project path
- emitted PHS is fed into the ordinary build/runtime flow
- project validation can fail on real downstream constraints rather than frontend-local guesses

Why:

- a first prototype should already prove the normal pipeline shape, not only sample transpilation

## Bucket 2: Good For JSS v1.1

These are valuable next capabilities, but they should not block the first prototype if Bucket 1 is healthy.

### 1. Stronger helper-family and module diagnostics

Useful downstream work:

- better project/module-aware availability diagnostics
- clearer errors when helper families exist syntactically but are not active in the current project/profile

Why:

- this improves realism for app-like JSS projects without changing the core language shape

### 2. Broader runtime-heavy sample coverage

Useful downstream work:

- richer filesystem/IO/JSON/datetime project samples
- validation that helper-family and wrapper contracts remain stable across real project flows
- current proof now includes combined `.jss` project build/run lanes for filesystem + JSON + datetime, filesystem + IO + strict string helpers, wrapper error paths, opt-in strict regex helpers, and opt-in strict curl helpers

Why:

- this tests usefulness rather than only syntax coverage

### 3. Broader diagnostic consistency

Useful downstream work:

- wider source-range coverage
- more consistent downstream diagnostics on JSS-originated constructs once lowered to PHS
- current proof includes negative `.jss` project validation for missing symbols and strict assignment conversion failures; missing symbols fail through the classified frontend path, while assignment conversion failures are currently source-mapped compiler diagnostics

Why:

- the prototype becomes much easier to trust and iterate on

### 4. Deeper STAN ownership for helper and wrapper truth

Useful downstream work:

- continue retiring frontend-local bridges
- let STAN own more helper typing and wrapper diagnostics directly

Why:

- keeps JSS from hardening into a shadow semantic engine

### 5. Safe extension of existing strict subset features

Possible work:

- more safe helper-family sample shapes
- more public/static/class polish
- more narrow template-literal cases that lower mechanically

Why:

- these expand usefulness without dragging in new dynamic semantics

## Bucket 3: Decided Follow-Up Surfaces

These are not part of the first useful prototype, but the current direction is clear enough to guide future implementation.

### 1. Explicit references through `&`

Direction:

- use `&` / ampersand and mimic the current PHS reference model
- do not add a JSS-only `ref` keyword
- do not add hidden aliasing through ordinary assignment
- keep the first slice narrow and tied to the documented reduced Prism++ native-reference subset

Why:

- references are not a JavaScript-authored keyword feature
- direct PHS alignment is clearer than inventing a JS-looking spelling

### 2. `delete` lowering to `unset(...)`

Direction:

- accept JSS `delete expr`
- lower mechanically to PHS `unset(expr)`
- do not claim full JavaScript `delete` object semantics

Why:

- the syntax is familiar to JS users
- the behavior remains honest to the PHS container/member removal operation

### 3. Late static through `static::`

Direction:

- expose the PHS spelling directly where needed:
  - `static::make()`
  - `static::VALUE`
- do not invent a JS-looking replacement unless a better, honest proposal appears later

Why:

- late-static semantics are already an advanced typed-language feature
- direct PHS spelling avoids ambiguity

### 4. Optional chaining returns `null`

Direction:

- optional chaining should return `null` on failed access
- reuse/adapt guarded-path or `isset(...)` machinery where possible
- do not make optional chaining depend on future `undefined` behavior

Why:

- this keeps optional chaining aligned with the existing nullable model
- it avoids JS-style “test both null and undefined” ergonomics

### 5. Typed arrow functions over PHS callable support

Direction:

- allow only explicit typed shapes at first, for example `let f = (x: int): int => x + 1;`
- require parameter types and return type in the first slice
- do not promise broad JavaScript inference or capture behavior beyond what PHS supports

Why:

- JSS is typed
- the lambda shape is explicit when it is written inline
- lowering must reuse the PHS callable/lambda path rather than generate a second callable model

## Bucket 4: Defer Until Runtime/PHS Or Detailed Design Work

These should stay explicitly deferred so the first prototype remains honest and tractable.

### 1. `undefined` as a reserved comparison keyword

Deferred work:

- runtime/PHS-owned first implementation
- accept `undefined` as a reserved PHS keyword
- lower only explicit comparison forms such as `expr == undefined`, `expr != undefined`, `expr === undefined`, and `expr !== undefined` through S2S to a compiler/runtime intrinsic
- do not treat `undefined` as a general JSS value in the first slice
- do not make optional chaining return `undefined`

Why deferred:

- this needs a runtime-first and core-language-first design
- it should not be faked locally in JSS

### 2. Broad JavaScript-like dynamic behavior

Deferred work:

- truthiness everywhere
- loose coercion rules
- soft missing-property behavior
- ad hoc object-bag semantics

Why deferred:

- these would push JSS toward a second runtime model
- they do not match the current strict typed language direction

### 3. Destructuring, spread/rest, async world

Deferred work:

- destructuring
- spread/rest
- `import` / `export`
- promises
- `async` / `await`
- generators/iterators

Why deferred:

- these are not needed for the first strict typed prototype
- most of them depend on language/runtime semantics that do not exist cleanly downstream yet

### 4. Dynamic object-bag semantics

Deferred work:

- nested dynamic mutation
- dynamic object creation and missing-property behavior
- whether dynamic object/member access can be more than honest JSON/dynamic reads

Why deferred:

- this needs separate discussion with concrete examples
- it should not be settled accidentally while adding typed-surface syntax

## Practical Takeaway

The first JSS prototype should be judged by whether it can do real strict typed work through the normal downstream path, not by whether it already resembles a wide JavaScript dialect.

So the priority should be:

1. keep the strict typed core solid
2. make helper-family and wrapper/result flows truly trustworthy downstream
3. keep dynamic features explicit and narrow
4. defer absence-heavy and JS-runtime-heavy features until the downstream language/runtime model is ready
