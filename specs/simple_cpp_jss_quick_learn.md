# JSS Quick Learn (v1 Alpha)

Status: draft

JSS - typed script-style frontend for Simple C++ / Prism++.

Purpose: one compact document to learn the current JSS alpha surface without importing JavaScript runtime expectations.

Primary repository:

- `https://github.com/alexstanciu-1/simplecpp`
- install/setup reference: `https://github.com/alexstanciu-1/simplecpp/blob/main/README.md`

Durable companion guide:

- `docs/jss/README.md`
- local docs alias: `scpp docs jss-guide`

## Golden Rule

JSS is not JavaScript compatibility.

It is a typed, script-looking compiled language surface that lowers to PHS and then follows the normal Simple C++ / Prism++ build pipeline.

If you are not sure, check this document first, then the JSS samples under `samples/jss/`, then the PHS strict quick-learn and owning specs.

The normal source file extension for this surface is `.jss`.

## What It Is

JSS borrows familiar script-shaped syntax where it maps cleanly to Prism++ semantics:

- `let`
- classes
- typed functions
- typed expression-body arrows
- `for`, `while`, `switch`
- `print(...)`
- dotted helper families such as `fs.get(...)` and `json.decode(...)`

JSS does not emulate JavaScript runtime behavior.

Think of it as:

- script-style syntax
- explicit typed boundaries
- deterministic lowering to PHS
- STAN-assisted symbol and operator classification
- strict runtime/helper contracts
- no hidden loose coercion or prototype object model

## First 5 Minutes

Use the strict PHP/PHS runtime profile. JSS currently lowers through that profile.

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

Minimal program:

```js
print("hello\n");
```

Build and run:

```bash
scpp build
scpp run
```

If a fresh checkout reports a missing reusable runtime artifact, rebuild it for this invocation:

```bash
scpp build --build-runtime
scpp run
```

Useful docs:

```bash
scpp docs jss
scpp docs strict
scpp docs diagnostics
```

Use `scpp docs strict` when a JSS question reaches PHS semantics, runtime helpers, wrappers, typed containers, or build behavior.

## Agent Smoke Test: Create And Run A JSS Project

This is the smallest useful end-to-end project an AI agent can create to prove the JSS path.

From an empty scratch directory:

```bash
mkdir jss-smoke
cd jss-smoke
```

Create `prism.json`:

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

Create `main.jss`:

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

Build and run:

```bash
scpp build
scpp run
```

Expected output:

```text
ready:10:10:2
```

What this proves:

- `.jss` project entrypoint
- class construction and property access
- references via `&`
- typed expression-body arrows
- hash literals, keyed reads, and `delete`
- JSS-to-PHS project build/run path

Useful inspection after build:

```bash
ls .prism/jss
cat .prism/jss/main.phs
```

The generated PHS intermediate should contain shapes such as:

```phs
$alias =& $value;
$addOne = fn($x int): int => $x + 1;
unset($scores["a"]);
```

If the build fails:

```bash
scpp error
scpp full-error
```

If the failure says the required runtime artifact is missing, rerun:

```bash
scpp build --build-runtime
scpp run --build-runtime
```

In v1 alpha, STAN may still print advisory JSS warnings during this smoke test. Treat `Static Analysis: 0 errors` plus successful build/run and expected stdout as the pass condition.

Do not patch `.prism/jss/main.phs` or generated C++. Fix the authored `.jss` source or the owning JSS/PHS/STAN/runtime layer.

### Optional Helper Smoke Test

Use this only when filesystem and JSON helper behavior is part of the task.

`main.jss`:

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

Expected behavior: the program builds, runs, prints JSON text, and removes `data.json`.

This proves:

- reserved helper families `fs.*` and `json.*`
- `take(...)` wrapper extraction
- `dynamic` JSON boundary
- normal `.jss -> PHS -> build/run` flow

## Source File Rules

JSS files:

- use `.jss`
- do not start with `<?php`
- do not use `declare(strict_types=1);`
- do not use JavaScript `import` / `export`
- are project-composed by `scpp build`, not by source-level includes

## Types

Use explicit types at meaningful boundaries.

```js
let count: int = 0;
let name: string = "alex";
let ok: bool = true;
let ratio: float = 1.5;
let maybeName: ?string = null;
```

Supported common types include:

- `int`
- `float`
- `bool`
- `string`
- `void`
- `mixed`
- `dynamic`
- `?T`
- `vector<T>`
- `hash<T>`
- `hash<T, T_KEY>`
- class names
- runtime/helper types such as `error` and `resource_handle` where the strict PHS surface supports them

## Variables And Operators

```js
let value: int = 5;
value = value + 1;
```

String concatenation uses JSS `+` when STAN can classify a string path:

```js
let label: string = "id:" + 42;
```

Dynamic plus is explicit at the boundary:

```js
let value: mixed = 4;
print(value + 2, "\n");
```

The classified path lowers dynamic/mixed `+` through `js_plus(...)`.

Equality and comparison follow the PHS/PHP++ operator matrix. `==` and `!=` are currently allowed to flow through for prototype compatibility and remain on the review list.

## Control Flow

```js
if (count > 0) {
	print("positive\n");
} else {
	print("zero\n");
}
```

```js
for (let i: int = 0; i < 3; i++) {
	print(i, "\n");
}
```

```js
for (let value: int of items) {
	print(value, "\n");
}
```

JSS v1 alpha prefers explicit boolean conditions. Do not rely on JavaScript truthiness.

## Functions And Arrows

```js
function add(left: int, right: int): int {
	return left + right;
}

print(add(2, 3), "\n");
```

First alpha arrow slice:

```js
let addOne = (x: int): int => x + 1;
print(addOne(4), "\n");
```

Rules:

- parameter types are required
- return type is required
- expression body only
- local concrete values only
- no broad JavaScript inference or callback-world promise

This lowers to the existing PHS `fn(...)` path.

## Classes

```js
class Box {
	name: string = "ready";

	constructor(name: string = "ready") {
		this.name = name;
	}

	label(): string {
		return this.name;
	}
}

let box: Box = new Box("ok");
print(box.label(), "\n");
```

Static members and constants:

```js
class BuildInfo {
	static version: string = "1";
	static const LABEL = "alpha";

	static current(): string {
		return BuildInfo.version;
	}
}
```

Direct late-static spelling has a narrow first slice:

```js
class A {
	static run(): string {
		return static::hello();
	}

	static hello(): string {
		return "A";
	}
}
```

Broader `static::$prop`, `new static(...)`, and inherited static classification remain pending.

## Containers And Mutation

Vectors:

```js
let items: vector<int> = [1, 2, 3];
items.push(4);
print(items[0], "\n");
```

Hashes:

```js
let scores: hash<int> = {"a": 1, "b": 2};
scores["c"] = 3;
delete scores["a"];
print(scores["b"], "\n");
```

`delete expr` lowers to PHS `unset(expr)`. It is not full JavaScript `delete` semantics.

Vector index-removal policy remains separate.

## Nullable, Coalescing, And Optional Chaining

```js
let name: ?string = null;
let out: string = name ?? "guest";
```

Current `??` slice is single-site only. Chained `a ?? b ?? c` is intentionally blocked for now.

Optional chaining has a syntax-lowering first slice:

```js
let maybe: ?Box = new Box();
print(maybe?.name, "\n");
```

This lowers to PHS `?->`, but project build/run support waits on proper PHS nullsafe result typing. See GitHub issue #207.

## References

First alpha slice:

```js
let value: int = 5;
let alias = &value;
alias = 9;
print(value, "\n");
```

Rules:

- explicit `&` only
- simple identifier targets only
- no hidden aliasing through ordinary assignment
- no JSS-only `ref` keyword
- maps directly to the PHS safe reference subset

## Namespaces And Use

JSS uses dotted namespace spelling in source and lowers to PHS namespace syntax.

```js
namespace Demo;

class Box {
	static value(): string {
		return "ok";
	}
}

print(Demo.Box.value(), "\n");
```

Imports:

```js
use Demo.Box;
use function Demo.makeName;
use const Demo.CODE;
```

Reserved helper-family roots cannot be used as user namespaces.

## Helper Families

JSS prefers reserved helper families instead of PHS backslash-heavy names:

- `fs.*`
- `io.*`
- `json.*`
- `dt.*`

Examples:

```js
let text: string = "";
let err: error;

if (take(text, err, fs.get("data.txt"))) {
	print(text, "\n");
}
```

```js
let data: dynamic = json.decode("{\"name\":\"alex\"}");
let out: string = json.encode(data);
print(out, "\n");
```

Runtime modules are project-selected capabilities. JSS source does not use JavaScript module semantics.

Regex and curl use their current strict PHS helper surfaces where enabled by project configuration. Do not import legacy PHP curl behavior into JSS.

## Result Wrappers And `take(...)`

Use `take(...)` to extract wrapper-shaped results explicitly:

```js
let text: string = "";
let err: error;

if (!take(text, err, fs.get("missing.txt"))) {
	print("read failed\n");
	return;
}

print(text, "\n");
```

Do not treat result wrappers as JavaScript-truthy objects.

## Dynamic Data

`json.decode(...)` returns dynamic by default. Stabilize dynamic values at typed boundaries:

```js
let row: dynamic = json.decode("{\"count\":2}");
let count: int = row["count"];
print(count + 1, "\n");
```

Use `dynamic` / `mixed` intentionally. They are explicit boundaries, not the default model for all values.

## Async / Await

JSS supports the current Simple C++ stackless async/await alpha surface:

```js
async function computeValue(): int {
    await async_sleep_ms(1);
    return 42;
}

let value: int = await computeValue();
print(value, "\n");
```

Current rules:

- `async function` requires an explicit return type
- `await async_sleep_ms(ms)` is valid only inside an async function
- expression-level `await someAsyncFunction()` lowers through the shared PHS async surface
- this is not JavaScript `Promise` compatibility and does not provide a JavaScript event loop

See `specs/async_await.md` for the current alpha contract.

## Unsupported Or Deferred

Current v1-alpha boundaries:

- no JavaScript prototype model
- no JavaScript `import` / `export`
- no JavaScript promises / generators
- no broad JS truthiness
- no loose object-bag-by-default model
- no `undefined` until runtime/PHS own it
- no destructuring
- no spread/rest
- optional chaining is partial until PHS nullsafe result typing is complete
- arrow functions are explicit local expression-body only
- references are simple identifier aliasing only

## Validation

For focused transpilation:

```bash
scpp main.jss
```

For real projects:

```bash
scpp build
scpp run
```

For diagnostics:

```bash
scpp error
scpp full-error
scpp last-run
scpp full-last-run
```

JSS is healthiest when the same code passes:

- JSS transpilation
- STAN-classified emission
- project build
- project run

For repository validation of the current JSS implementation, run:

```bash
php tests/tools/test_scpp_jss_samples.php
php tests/tools/test_scpp_jss_frontend_first_slice.php
php tests/tools/test_scpp_jss_project_build_run.php
```

Use the project build/run test as the strongest proof because it exercises the normal `.jss -> PHS -> native build -> binary run` lane.

## Good First Example

```js
function main(): void {
	let path: string = "data.json";
	let err: error;
	let written: int = 0;

	if (!take(written, err, fs.put(path, "{\"name\":\"alex\"}\n"))) {
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

## Authority Order

When unsure:

1. `specs/simple_cpp_jss_quick_learn.md`
2. `samples/jss/`
3. `specs/simple_cpp_php_strict_quick_learn.md`
4. owning top-level specs under `specs/`
5. implementation/tests as evidence, not semantic authority
