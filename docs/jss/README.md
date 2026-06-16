# JSS Alpha Guide

JSS is a typed, script-style compiled frontend for Simple C++ / Prism++.

It is not JavaScript compatibility. JSS borrows familiar script-shaped syntax where that syntax maps cleanly to Prism++ semantics, then lowers through PHS and the normal build/runtime pipeline.

Use this guide when you need durable JSS know-how rather than planning history.

## Mental Model

The intended pipeline is:

```text
.jss source
  -> JSS tokenizer/parser/validator
  -> JSS file summaries + frontend classification requests
  -> STAN frontend classification
  -> classified PHS intermediate under .prism/jss/
  -> normal PHS generator/runtime/build path
  -> native binary
```

JSS should not grow a second semantic engine.

Layer ownership:

- JSS owns syntax, narrow frontend shape rules, helper-family spelling, and lowering into honest PHS.
- STAN owns project-wide symbol/classification truth such as identifier roles, member roles, helper availability, and operator classification where local syntax is insufficient.
- PHS owns the intermediate language representation that JSS emits.
- Runtime owns helper behavior, wrappers, dynamic carriers, and runtime exception behavior.
- Project/build owns module activation, generated artifacts, diagnostics, and binary build/run orchestration.

If emitted PHS is correct but build/run fails, fix or document the PHS/STAN/runtime/project layer. Do not hide the problem with a JSS-only workaround.

## Positioning

Say:

> JSS is a typed script-style compiled frontend.

Do not say:

> JSS is JavaScript-compatible.

JSS intentionally does not emulate:

- loose JavaScript truthiness everywhere
- prototype objects
- browser or Node.js APIs
- JavaScript `import` / `export`
- promises / async / await
- broad object bags by default
- automatic `undefined` behavior
- implicit missing-property success
- loose coercion as the default programming model

Dynamic behavior exists only at explicit boundaries such as `dynamic`, `mixed`, JSON decoding, or documented helper/runtime surfaces.

## Project Shape

JSS currently lowers through the strict PHS profile. A minimal project uses:

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

Build and run:

```bash
scpp build
scpp run
```

On a fresh checkout, the reusable runtime artifact may not exist yet. In that case use:

```bash
scpp build --build-runtime
scpp run --build-runtime
```

The classified PHS intermediate for a project build is written under:

```text
.prism/jss/
```

Inspect it when debugging lowering, but do not patch generated PHS as the final fix.

## Verified Smoke Project

Use this project when an agent needs to prove that JSS can create, build, and run a real project.

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

Commands:

```bash
scpp build --build-runtime
scpp run --build-runtime
```

Expected stdout:

```text
ready:10:10:2
```

This proves:

- `.jss` project entrypoint
- class construction and property access
- typed locals
- references via `&`
- typed expression-body arrows
- hash literals and keyed reads
- `delete` lowering to PHS `unset(...)`
- STAN-classified JSS emission
- PHS generation
- native build and binary run

In v1 alpha, STAN may still print advisory JSS warnings. Treat `Static Analysis: 0 errors` plus successful build/run and expected stdout as the pass condition.

Useful generated-PHS fragments:

```phs
$alias =& $value;
$addOne = fn($x int): int => $x + 1;
unset($scores["a"]);
```

## Useful Prototype Flow

The main “useful prototype” lane is filesystem + JSON + `take(...)`.

Representative JSS:

```js
function main(): void {
	let path: string = "data.json";
	let err: error;
	let written: int = 0;

	if (!take(written, err, fs.put(path, "{\"name\":\"alex\",\"count\":2}\n"))) {
		print("write failed\n");
		return;
	}

	let text: string = "";
	if (!take(text, err, fs.get(path))) {
		print("read failed\n");
		return;
	}

	let data: dynamic = json.decode(text);
	print(json.encode(data), "\n");
	fs.remove(path);
}

main();
```

This lane proves the pieces that make JSS useful rather than only syntactic:

- reserved helper families such as `fs.*` and `json.*`
- wrapper extraction through `take(...)`
- JSON decoding into `dynamic`
- explicit dynamic boundary use
- normal `.jss -> PHS -> build/run` flow

## Alpha Surface

Generally usable in v1 alpha:

- `print(...)`
- typed `let`
- scalar types: `int`, `float`, `bool`, `string`
- `void`
- nullable `?T`
- `mixed` and `dynamic` at explicit boundaries
- `vector<T>` and `hash<T>`
- functions with explicit parameter and return types
- classes, public properties, constructors, methods
- static properties, static methods, class constants
- namespaces and `use`
- `if`, `while`, `do while`, `for`, `for (... of ...)`, `switch`
- single-site `??`
- simple ternary
- template literals with narrow interpolation
- `delete expr` lowering to `unset(expr)`
- `let alias = &value;` and `alias = &value;` for simple identifier references
- explicit local typed arrows such as `let f = (x: int): int => x + 1;`
- reserved helper families `fs`, `io`, `json`, `dt`
- strict regex/curl helper flows when project/runtime modules support them

Current partials and boundaries:

- optional chaining lowers `object?.member` to PHS `?->`, but full build/run waits on PHS nullsafe result typing
- `undefined` is deferred until runtime/PHS own it
- vector index removal policy remains separate
- inherited static classification needs STAN improvement
- broader arrow/callable ergonomics remain outside the first slice
- private/protected and ES `#private` fields are not implemented in the current JSS class surface
- destructuring and spread/rest are not implemented
- JavaScript module/async semantics are not in scope

## Helper Families

JSS reserves these helper roots:

- `fs`
- `io`
- `json`
- `dt`

Examples:

```js
fs.get(path)
fs.put(path, text)
io.open(path, "rb")
json.decode(text)
json.encode(value)
dt.format(ts, "Y-m-d")
```

These are frontend spellings for real strict PHS/runtime helper contracts. User namespaces should not reuse these roots.

Runtime modules are project-selected capabilities, not JS imports.

## Wrapper Results

Use `take(...)` for wrapper-shaped results:

```js
let text: string = "";
let err: error;

if (!take(text, err, fs.get(path))) {
	print("read failed\n");
	return;
}
```

Do not treat wrappers as JavaScript-truthy objects. Success/failure/error state should remain explicit.

## Dynamic Data

`json.decode(...)` returns `dynamic` by default.

Stabilize dynamic data at typed boundaries:

```js
let row: dynamic = json.decode(text);
let count: int = row["count"];
```

Prefer typed objects, `vector<T>`, and `hash<T>` once the expected shape is known.

## References

JSS references are explicit and narrow:

```js
let alias = &value;
alias = &other;
```

Rules:

- no hidden aliasing through ordinary assignment
- no JSS-only `ref` keyword
- first slice targets simple identifiers
- maps directly to the PHS safe reference subset

## Arrows

First alpha slice:

```js
let addOne = (x: int): int => x + 1;
```

Rules:

- parameter types required
- return type required
- expression body only
- local concrete values only
- lowers through the existing PHS `fn(...)` path
- no broad JavaScript inference/capture promise

## Optional Chaining

JSS can lower:

```js
maybe?.name
```

to:

```phs
$maybe?->name
```

This is intentionally delegated to PHS. Full project build/run support depends on proper PHS nullsafe result typing. GitHub issue #207 tracks that work.

## Validation

For repo development:

```bash
php tests/tools/test_scpp_jss_samples.php
php tests/tools/test_scpp_jss_frontend_first_slice.php
php tests/tools/test_scpp_jss_project_build_run.php
```

The project build/run test is the strongest proof because it exercises:

```text
.jss -> classified PHS -> generated C++ -> native build -> binary run
```

For user projects:

```bash
scpp build
scpp run
scpp error
scpp full-error
```

## Related Docs

- `specs/simple_cpp_jss_quick_learn.md`
- `specs/simple_cpp_php_strict_quick_learn.md`
- `.agents/skills/simple-cpp-jss/SKILL.md`
- `.agents/skills/simple-cpp-jss/references/authoring-rules.md`
- `.agents/skills/simple-cpp-jss/references/validation-and-diagnostics.md`
- `.agents/skills/simple-cpp-jss/references/not-javascript.md`
- `samples/jss/TODO.md`
- `samples/jss/POLICY_BACKLOG.md`

