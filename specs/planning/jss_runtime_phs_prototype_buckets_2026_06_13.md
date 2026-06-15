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

Why:

- this tests usefulness rather than only syntax coverage

### 3. Broader diagnostic consistency

Useful downstream work:

- wider source-range coverage
- more consistent downstream diagnostics on JSS-originated constructs once lowered to PHS

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

## Bucket 3: Defer Until Post-`undefined` Or Post-STAN Work

These should stay explicitly deferred so the first prototype remains honest and tractable.

### 1. `undefined` as a real value/state

Deferred work:

- distinct runtime/language absence state
- presence-aware typing such as future `T | undefined`
- missing-key / missing-member distinction from present `null`

Why deferred:

- this needs a runtime-first and core-language-first design
- it should not be faked locally in JSS

### 2. Optional chaining and richer absence-aware access

Deferred work:

- `?.`
- richer lookup semantics
- broader `??` interaction once `undefined` exists

Why deferred:

- these depend on real absence-flow truth, not just parser support

### 3. Broad JavaScript-like dynamic behavior

Deferred work:

- truthiness everywhere
- loose coercion rules
- soft missing-property behavior
- ad hoc object-bag semantics

Why deferred:

- these would push JSS toward a second runtime model
- they do not match the current strict typed language direction

### 4. Callable/closure/arrow-function surface

Deferred work:

- arrow functions
- closures
- broader callable expression lowering

Why deferred:

- only worth doing once PHS callable lowering is a stable real target

### 5. Destructuring, spread/rest, async world

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

### 6. Deeper mutation and alias/reference semantics

Deferred work:

- unset/delete policy
- nested dynamic mutation
- references / aliasing
- late-static semantics beyond the current safe subset

Why deferred:

- these need stronger STAN and/or explicit PHS/runtime design

## Practical Takeaway

The first JSS prototype should be judged by whether it can do real strict typed work through the normal downstream path, not by whether it already resembles a wide JavaScript dialect.

So the priority should be:

1. keep the strict typed core solid
2. make helper-family and wrapper/result flows truly trustworthy downstream
3. keep dynamic features explicit and narrow
4. defer absence-heavy and JS-runtime-heavy features until the downstream language/runtime model is ready
