# Language Surface Enrichment Audit
Doc Status: planning

Date: 2026-06-12

Purpose: capture the current proof-of-concept JSS language surface as it exists now in code and tests, and contrast that with important blocked or intentionally-deferred areas.

This is a planning artifact, not semantic authority.

## Framing

This note answers a practical question:

- what JSS can do now
- what it cannot do yet
- which path is plain frontend lowering versus STAN-classified lowering
- where future work belongs when the answer is not "add more parser code"

Validation snapshot for this audit:

- `php tests/tools/test_scpp_jss_frontend_first_slice.php`
- `php tests/tools/test_scpp_jss_samples.php`
- `php tests/tools/test_scpp_jss_project_build_run.php`

All three passed on 2026-06-12 for this audit pass.

Status values:

- `works now`: implemented and covered in the current JSS/test surface
- `partial`: implemented with deliberate limits
- `blocked`: intentionally not supported yet
- `future`: desirable, but not part of the current proof of concept

Confidence values:

- `high`: directly covered by current tests/samples
- `medium`: implemented and exercised indirectly, but with less dedicated coverage
- `low`: design direction exists, but present support is thin or policy-sensitive

## Current POC Audit

Status markers:

- `🟩 works now`
- `🟨 partial`
- `🟥 blocked`
- `🟦 future`

| Surface Area | Status | Code Sample | Confidence | Plain Path | Classified Path | Needs STAN | Priority | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Typed local declarations with `let` | 🟩 works now | `let total: int = 1;` | high | yes | no | no | P0 | Explicit typed locals are the core JSS declaration form. |
| Top-level and class `const` | 🟩 works now | `const LIMIT: int = 10;` | high | yes | sometimes | only for symbol resolution | P0 | Top-level and class constants work; local/block/loop `const` remains rejected. |
| Typed function declarations | 🟩 works now | `function add(a: int, b: int): int {}` | high | yes | no | no | P0 | Explicit param and return types are required. |
| Default parameters | 🟩 works now | `function greet(name: string = "x"): void {}` | high | yes | no | no | P0 | Current safe subset is covered in samples. |
| `void` functions and bare `return;` | 🟩 works now | `function log(): void { return; }` | high | yes | no | no | P0 | Explicit `void` is supported. |
| Classes with `constructor(...)` | 🟩 works now | `class Box { constructor(name: string) {} }` | high | yes | no | no | P0 | Lowers to PHS `__construct`. Direct `__construct` spelling is rejected in JSS. |
| Public instance fields/methods | 🟩 works now | `class Box { name: string = ""; open(): void {} }` | high | yes | sometimes | only for member/static role truth | P0 | Default/public surface is healthy. |
| Static fields, static methods, class constants | 🟩 works now | `class Box { static count: int = 0; }` | high | limited | yes | yes | P0 | STAN-classified path is the trusted path for role resolution. |
| `this` inside instance methods | 🟩 works now | `this.name = "ok";` | medium | yes | no | no | P1 | Supported within the class subset; dynamic JS `this` behavior is not supported. |
| `extends` basic inheritance | 🟩 works now | `class UserBox extends Box {}` | medium | yes | yes | yes | P1 | Current simple inheritance examples work; late-static semantics are still outside the subset. |
| Namespaces | 🟩 works now | `namespace app.core;` | high | yes | yes | yes | P0 | Semicolon-style and block namespace samples both exist. |
| `use` imports for class/function/const | 🟩 works now | `use app.core.Box;` | high | yes | yes | yes | P0 | Static import surface works; JS `import`/`export` does not. |
| Dotted namespace/class/const access | 🟩 works now | `app.core.Box.VERSION` | high | limited | yes | yes | P0 | Cross-namespace static/class/const access relies on classification. |
| Scalar literals (`int`, `float`, `bool`, `string`, `null`) | 🟩 works now | `let name: string = "hi";` | high | yes | no | no | P0 | `null` is allowed only through explicit nullable-typed flows. |
| Arithmetic `+ - * / %` on typed numerics | 🟩 works now | `let total: int = a + b * 2;` | high | yes | partial | only for ambiguous `+` | P0 | Plain numeric operators are solid; `+` becomes classified only when ambiguity matters. |
| Unary minus on numeric expressions | 🟩 works now | `let n: int = -count;` | high | yes | no | no | P2 | Numeric-only slice is implemented. |
| String concatenation via JSS `+` | 🟩 works now | `let msg: string = "hi " + name;` | high | partial | yes | yes | P0 | Lowers mechanically when one side is statically string and the other has a known printable path. |
| Mixed/dynamic `+` through `js_plus(...)` | 🟩 works now | `let out: mixed = left + right;` | high | no | yes | yes | P0 | This is an explicit boundary helper path, not frontend-local truthiness/coercion emulation. |
| Comparisons `< <= > >=` | 🟩 works now | `if (count >= 10) {}` | high | yes | no | no | P0 | Standard typed comparison slice is covered. |
| Equality `===` and `!==` | 🟩 works now | `if (name === "ok") {}` | high | yes | no | no | P0 | Still the preferred strict equality path. |
| Equality `==` and `!=` | 🟩 works now | `if (value != 0) {}` | medium | yes | no | no | P1 | Temporarily allowed to flow through for the proof of concept; review still needed later. |
| Boolean operators `&&`, `||`, `!` | 🟩 works now | `if (ok && !done) {}` | high | yes | no | no | P0 | Conditions must still be explicitly `bool`. |
| `if` / `else` / `else if` | 🟩 works now | `if (ok) {} else if (done) {} else {}` | high | yes | no | no | P0 | Condition must validate as `bool`; no broad truthiness. |
| `while`, `do while`, classic `for` | 🟩 works now | `for (let i: int = 0; i < 3; i++) {}` | high | yes | no | no | P0 | Current loop subset is healthy. |
| `break` / `continue` | 🟩 works now | `if (stop) { break; }` | high | yes | no | no | P1 | One-level basic control flow is covered. |
| `switch` / `case` / `default` | 🟩 works now | `switch (kind) { case 1: break; default: break; }` | high | yes | no | no | P1 | Current scalar subset is implemented. |
| Vector literals and typed `vector<T>` flows | 🟩 works now | `let ids: vector<int> = [1, 2];` | high | yes | no | no | P0 | Untyped array behavior is not supported; explicit typed container intent is required. |
| Hash literals and typed `hash<T>` flows | 🟩 works now | `let names: hash<string> = {"a": "b"};` | high | yes | no | no | P0 | Object literals mean typed hash construction, not JS object bags. |
| Vector read/write by index | 🟩 works now | `items[0] = 3;` | high | yes | no | no | P0 | Direct indexed reads and writes work. |
| Hash keyed read/update | 🟩 works now | `names["a"] = "b";` | high | yes | no | no | P0 | Direct keyed updates work in the current subset. |
| Vector append via `push(...)` | 🟩 works now | `items.push(value);` | high | yes | no | no | P1 | Statement-form append is implemented. |
| `for (... of ...)` over `vector<T>` | 🟩 works now | `for (let value: int of items) {}` | high | yes | no | no | P0 | Value iteration is supported. |
| `for (... of ...)` over `hash<T>` key/value | 🟩 works now | `for (let k: string, v: int of items) {}` | high | yes | no | no | P0 | Key/value and value-only hash iteration are covered. |
| Nullable types `?T` with explicit null flow | 🟩 works now | `let name: ?string = null;` | high | yes | no | no | P0 | Explicit nullable typing is one of the strong parts of the current subset. |
| Single-site `??` null coalescing | 🟩 works now | `let out: string = name ?? "guest";` | high | yes | partial | only for broader future unification | P1 | Narrow first slice works for explicit nullable or explicit mixed/dynamic boundary operands. |
| Ternary `cond ? a : b` | 🟩 works now | `let label: string = ok ? "y" : "n";` | high | yes | partial | only for broader future branch typing | P1 | Current slice is strict bool-only, with same-type or `T`/`null` branch pairing. |
| Template literals without interpolation | 🟩 works now | `` `hello\n` `` | high | yes | no | no | P2 | Clean literal surface. |
| Template literals with simple interpolation | 🟨 partial | `` `hi ${name}` `` | high | yes | partial | only when member/static role classification is needed | P2 | Current subset covers `${identifier}` and dotted member/static/class-constant chains, lowered to explicit concat. |
| Reserved helper families `fs.*`, `io.*`, `json.*`, `dt.*` | 🟩 works now | `fs.get(path)` | high | partial | yes | yes | P1 | JSS owns the source spelling; STAN only sees normalized PHS-facing call targets. |
| `take(...)` first-pass result-wrapper flow | 🟩 works now | `let text: string = take(fs.get(path));` | medium | no | yes | yes | P1 | Intentionally reuses PHS/STAN wrapper truth instead of inventing a JSS-only semantic engine. |
| Project-wide `.jss` build/run through normal path | 🟩 works now | `scpp run` on a `.jss` project | high | n/a | yes | yes | P0 | Project STAN sessions include `.jss` summaries and classified emission in the normal pipeline. |
| Real source ranges in many JSS diagnostics | 🟨 partial | `error at line:col for expr` | medium | yes | yes | partial | P1 | Better than before, but not yet uniformly broad for every diagnostic family. |
| Local/block/loop `const` | 🟥 blocked | `for (const x: int of items) {}` | high | no | no | likely later | P1 | Rejected until immutability has a clean PHS/STAN contract. |
| Truthiness in conditions (`if (value)`) | 🟥 blocked | `if (name) {}` | high | no | no | yes for any future expansion | P1 | Stay aligned with PHS/PHP++; current direction is explicit bool-only control flow. |
| JS-style loose runtime coercion | 🟥 blocked | `"x" + maybeNull` | high | no | no | yes | P1 | We are not emulating broad JavaScript coercion semantics; use the PHS operator matrix. |
| `undefined` comparison keyword | 🟦 future | `if (value === undefined) {}` | high | no | no | yes | P1 | Runtime/PHS-owned first; initial direction is reserved PHS keyword only in explicit comparison forms lowered by S2S to an intrinsic. |
| `null` + `undefined` interplay | 🟦 future | `value ?? undefined` | high | no | no | yes | P1 | Depends on the separate undefined design. |
| Soft missing-property access yielding `undefined` | 🟦 future | `row["missing"] === undefined` | high | no | no | yes | P1 | Not part of the current strict typed object/hash surface. |
| Dynamic JS object bags / ad hoc property creation | 🟥 blocked | `obj.newField = 1;` | high | no | no | yes | P1 | Current direction prefers typed classes, `hash<T>`, and `vector<T>`. |
| Prototype model / dynamic `this` binding | 🟥 blocked | `Thing.prototype.run = ...` | high | no | no | no | Reject | Out of scope for JSS. |
| Optional chaining `?.` | 🟨 partial | `user?.name` | high | yes | partial | yes | P1 | First slice lowers `object?.member` to PHS nullsafe `?->`; downstream PHS nullsafe scalar result unification still needs work before broad project build/run coverage. |
| Chained `??` | 🟥 blocked | `a ?? b ?? c` | high | no | no | yes | P1 | Single-site only for now. |
| Nested ternary | 🟥 blocked | `a ? b : c ? d : e` | high | no | no | yes | P1 | Deliberately kept out of the first slice. |
| Destructuring | 🟥 blocked | `let [a, b] = items;` | high | no | no | yes | P2 | Not implemented. |
| Spread/rest | 🟥 blocked | `fn(...args)` | high | no | no | yes | P2 | Not implemented. |
| Arrow functions / closures | 🟩 works | `let f = (x: int): int => x + 1;` | high | yes | mostly | no | P2 | First slice supports local explicit expression-body arrows with typed params and return; lowers to existing PHS `fn(...)`. |
| Async / await / promises / generators | 🟥 blocked | `await fetch()` | high | no | no | yes | P3 | Not in current scope. |
| References / aliasing | 🟩 works | `let b = &a;` | high | yes | mostly | no | P1 | First slice supports explicit simple-identifier reference aliases only; lowers to PHS `=&` and does not infer hidden aliasing. |
| Unset/delete mutation | 🟩 works | `delete names["a"];` | high | yes | mostly | no | P1 | JSS `delete expr` lowers to PHS `unset(expr)` and does not imply full JS delete semantics; vector index-removal policy remains separate. |
| Late static `static::` semantics | 🟨 partial | `static::make()` | medium | yes | partial | yes | P1 | Narrow `static::method()` / `static::CONST` spelling lowers directly to PHS; inherited static dispatch, `static::$prop`, `new static(...)`, and richer validation remain pending. |
| ES6 `#private` fields | 🟦 future | `class Box { #count: int = 0; }` | high | no | no | yes | P1 | Narrow design note exists; not implemented. |
| `private` / `protected` keywords | 🟥 blocked | `private name: string = "";` | high | no | no | yes | P1 | Rejected in current JSS class surface. |
| JS module syntax `import` / `export` | 🟥 blocked | `import { Box } from "./box";` | high | no | no | no | P2 | JSS uses namespaces/use plus project module config, not JS module semantics. |
| Richer runtime-heavy project samples | 🟨 partial | `let data = take(json.decode(text));` | medium | no | yes | yes | P1 | Helper-family and first-pass `take(...)` flows exist, but broader project coverage still depends on STAN/runtime maturity. |

## Short Read

Today’s JSS proof of concept is strongest when it is treated as:

- explicit typed JavaScript-shaped syntax
- lowering into pre-PHS
- with STAN providing the semantic truth where role classification matters

That gives us a healthy current surface for:

- typed locals/functions
- strict control flow
- typed vectors/hashes
- classes/public/static basics
- namespaces and imports
- helper-family calls
- narrow nullable, ternary, and template-literal slices

It is still intentionally weak or blocked around:

- dynamic JavaScript behavior
- truthiness/coercion-heavy semantics
- undefined/absence semantics
- closures/arrow functions
- destructuring/spread
- references
- advanced nullable or mutation policy

## Recommended Reading Order For Next Decisions

1. `specs/planning/jss_js_flavor_gap_discussion_list_2026_06_12.md`
2. `specs/planning/mandatory_stan_improvements_for_jss_2026_06_12.md`
3. `samples/jss/POLICY_BACKLOG.md`
4. `samples/jss/TODO.md`

## Practical Takeaway

If we ignore helper libraries and wrapper-heavy flows, the current JSS surface already covers a meaningful strict typed subset:

- declarations
- functions
- classes
- namespaces
- imports
- scalar/container operations
- explicit control flow
- narrow nullable and expression sugar

If we include helper families and the current classified path, the proof of concept is substantially stronger, but those areas depend on the normal PHS/STAN pipeline rather than a frontend-only story.
