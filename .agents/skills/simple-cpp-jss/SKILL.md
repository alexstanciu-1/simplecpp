---
name: simple-cpp-jss
description: Write, review, debug, and fix JSS v1-alpha Simple C++ applications. Use for .jss authoring, typed script-style compiled frontend work, JSS-to-PHS lowering, reserved helper families fs/io/json/dt, take(...) wrapper flows, STAN-classified JSS emission, and JSS project build/run diagnostics. Do not use as JavaScript compatibility guidance.
---

# Simple C++ JSS

Use this skill for JSS v1-alpha app work. JSS is a typed script-style compiled frontend for Simple C++ / Prism++ that lowers to PHS. It is not JavaScript compatibility mode.

## Scope Guard

Confirm the task is really JSS:

- source files should be `.jss`
- project entrypoint may be `main.jss`
- project runtime currently uses `runtime.languages.php.profile = "strict"` because JSS lowers through PHS
- do not apply browser JavaScript, Node.js, or general scripting-language assumptions

If the task is pure PHS/PHP++ instead, use `$simple-cpp-php-strict`.

## First Moves

For nontrivial JSS decisions, read:

- `specs/simple_cpp_jss_quick_learn.md`

If local docs are wired in, prefer:

```bash
scpp docs jss
```

Then read only what is needed:

- `references/authoring-rules.md` for writing/reviewing JSS source
- `references/validation-and-diagnostics.md` for build/run/debug workflow
- `references/not-javascript.md` when code or a request smells like JavaScript compatibility expectations
- `specs/simple_cpp_php_strict_quick_learn.md` when a question reaches PHS/runtime semantics

Prefer local repo docs and samples over web docs.

## Authoring Posture

Write JSS source, not generated PHS or C++.

JSS should feel script-like at the surface but remain typed and compiled underneath.

- Use `.jss`.
- Do not add `<?php`.
- Do not add `declare(strict_types=1);`.
- Use explicit types at meaningful boundaries.
- Use `let name: T = value;`.
- Use `vector<T>` and `hash<T>` for known containers.
- Use `struct Name { field: uint32 = 0; }` for compact inline value-layout records that should lower to PHS `struct` and generated C++ value storage.
- Use `union Name { payload: uint32; nested: PayloadStruct; }` only for restricted mutually exclusive fixed-layout payloads; current union payloads may contain fixed-width integers, fixed-backed enums, and recursively trivial structs, but not strings, vectors, hashes, object/reference types, defaults, methods, constants, inheritance, or nested unions.
- Use fixed-backed enums through the PHS-compatible compact-layout path when discriminators need exact storage width; JSS should emit clean PHS rather than internal parser carrier comments.
- Use `dynamic` / `mixed` intentionally, especially around JSON or other dynamic input.
- Stabilize dynamic values into typed locals, properties, returns, or containers quickly.
- Use `take(...)` for wrapper-shaped result extraction.
- Keep `null`, failure, and error states distinct.
- Prefer explicit boolean conditions; do not rely on JavaScript truthiness.
- Treat `==` / `!=` as currently allowed but review-listed; prefer strict comparisons when possible.
- Use reserved helper families such as `fs.get(...)`, `io.open(...)`, `json.decode(...)`, and `dt.format(...)`.
- Do not use JavaScript `import` / `export`; project modules are selected in `prism.json`.
- Do not invent local JSS semantic workarounds when PHS/STAN/runtime should own the truth.

## Clear Alpha Surface

Generally usable in v1 alpha:

- `print(...)`
- typed `let`
- scalar types and nullable `?T`
- `vector<T>` and `hash<T>`
- functions with explicit parameter and return types
- classes, constructors, public properties/methods, static members, class constants
- namespaces and `use`
- `if`, `while`, `do while`, `for`, `for (... of ...)`, `switch`
- single-site `??`
- simple ternary
- template literals with narrow interpolation
- `delete expr` -> PHS `unset(expr)`
- `let alias = &value;` / `alias = &value;` for simple identifier references
- explicit local typed arrows like `let f = (x: int): int => x + 1;`
- stackless async/await alpha surface, lowering through PHS and `scpp::async_core`
- compact-layout structs and restricted unions, lowering through clean PHS compact-layout syntax
- reserved helper families `fs`, `io`, `json`, `dt`

Partial or blocked:

- optional chaining lowers to PHS `?->`, but broad build/run waits on PHS nullsafe result typing
- `undefined` is runtime/PHS-first future work
- vector index removal policy is not settled
- inherited static classification needs STAN improvement
- no prototype model, JavaScript promises/event-loop compatibility, destructuring, spread/rest, or JS module semantics

## Project Workflow

Use public `scpp` commands:

```bash
scpp build
scpp run
scpp docs jss
```

For runtime/module-heavy projects, inspect `prism.json`.

After failures:

```bash
scpp error
scpp full-error
scpp last-run
scpp full-last-run
```

Use generated `.prism/jss/*.phs`, `.prism/generated/*.cpp`, and line maps as evidence. Do not patch generated artifacts as the real fix.

## Create A Smoke Project

When asked to prove JSS works, create a fresh scratch project with this exact shape unless the user gave a different target.

`prism.json`:

```json
{
  "runtime": {
    "languages": {
      "php": {
        "profile": "strict"
      }
    }
  },
  "entrypoint": "main.jss"
}
```

`main.jss`:

```js
class Box {
	name: string = "ready";
}

let box: Box = new Box();
let value: int = 4;
let alias = &value;
alias = 9;
let addOne = (x: int): int => x + 1;
let scores: hash<int> = {"a": 1, "b": 2};
delete scores["a"];

print(box.name, ":", value + 1, ":", addOne(value), ":", scores["b"], "\n");
```

Run:

```bash
scpp build
scpp run
```

If the runtime artifact is missing in a fresh checkout, use:

```bash
scpp build --build-runtime
scpp run --build-runtime
```

Expected output:

```text
ready:10:10:2
```

After build, inspect `.prism/jss/main.phs` when needed. It should show reference assignment, PHS `fn(...)`, and `unset(...)` lowering.

In v1 alpha, STAN may still print advisory JSS warnings. Treat `Static Analysis: 0 errors` plus successful build/run and expected stdout as the pass condition.

## Validation

Use the smallest proof that covers the change:

- `scpp file.jss` for focused transpilation
- JSS sample tests for frontend lowering changes
- STAN-classified sample path when symbol/operator classification matters
- `scpp build` for project compile checks
- `scpp run` for behavior
- `scpp error` / `scpp full-error` for source-first diagnostics

For repo work, useful focused tests include:

```bash
php tests/tools/test_scpp_jss_samples.php
php tests/tools/test_scpp_jss_frontend_first_slice.php
php tests/tools/test_scpp_jss_project_build_run.php
```

## Before Finishing

Make sure the response is honest about alpha limits.

Say “typed script-style compiled frontend,” not “JavaScript-compatible.”

If a feature wants semantic truth from STAN, PHS, or runtime, document or raise that downstream work instead of creating a second semantic engine in JSS.
