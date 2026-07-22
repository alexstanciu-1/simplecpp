# PHP++ Quick Learn (Strict Mode)

Status: draft

PHS - PHP-Like Surface for Simple C++

Purpose: one compact document to learn the current PHP++ / PHS strict surface without importing full PHP expectations.

Primary repository:

- `https://github.com/alexstanciu-1/simplecpp`
- install/setup reference: `https://github.com/alexstanciu-1/simplecpp/blob/main/README.md`

## Golden Rule

Only the syntax is similar to PHP.

Do not assume PHP semantics.

If you are not sure, check this document first, then check the relevant PHP++ / PHS examples or the underlying Simple C++ specs before making a coding decision.

The normal source file extension for this surface is `.phs`.

## What It Is

PHP++ is the human-facing name of the PHS authoring surface for a narrower, more deliberate Simple C++ language model.

Use familiar PHP-like syntax, usually in `.phs` files, but do not assume full PHP semantics.

Think of it as:

- PHP-like syntax
- explicit typed boundaries
- wrapper-aware results
- selective runtime/library surface
- deterministic lowering to controlled C++

In this document, Strict Mode means the strict PHP++ / PHS profile and its visible library surface.
It is not full language enforcement yet, but it should shape how code is written.

## Strict-Mode Philosophy

Strict mode is not designed for the shortest possible code.
It is designed for explicit, readable code that holds up well in long-lived projects.

Most strict code should already be typed.
The important work is at a small number of boundaries where dynamic data, nullable values, or wrapper-shaped results enter the flow.

After those boundaries are handled intentionally, the rest of the program should read mostly like ordinary typed programming.

Helpers such as `take(...)` exist to keep success, failure, absence, and usable values explicit at those boundaries.

## Reading This Document

- `Guaranteed`: supported surface or behavior you can rely on
- `Preferred`: recommended strict-style usage
- `Current`: accepted or observed behavior that should not be treated as the main strict style

## Terminology

- `Simple C++`: the underlying system, generator, and runtime stack
- `PHP++`: the human-facing name used for this authoring model
- `PHS`: the PHP-like source surface used by PHP++
- `strict profile`: the selected runtime/library profile for strict projects
- `strict surface`: the visible builtin/library API exposed by the strict profile

## First 5 Minutes

### Start a PHP++ / PHS strict project

```bash
scpp init --php-profile=strict
```

This creates the basic project structure, including:

- `prism.json`
- `.prism/build/`
- `.prism/generated/`
- `.prism/cache/`

### Source file policy

- `Guaranteed`: new strict code should use `.phs`
- `Current`: compatible `.php` inputs may still be accepted
- `Preferred`: for new PHP++ / PHS strict code, write `.phs`

### Typical entry files

Common entrypoint names include:

- `main.phs`
- `src/main.phs`
- `app/main.phs`
- `index.phs`
- `main.php`
- `src/main.php`
- `index.php`

### Build

```bash
scpp build
scpp build --entry=tests/php/sample.phs
```

`scpp build` composes `.phs` files inside the same project. Do not use source-level `require`, `require_once`, `include`, or `include_once` for project composition, and do not name generated `.hpp` files from source.

By default, `scpp build` reuses existing runtime and dependency artifacts. Build those layers explicitly when needed:

```bash
scpp build --build-runtime
scpp build --build-dependencies
scpp build --build-runtime --build-dependencies
scpp build --force
```

`--build-runtime` refreshes the runtime for the current build/invocation. Shared reusable runtime maintenance is handled by `scpp update` and `scpp runtime-build`; explicit custom runtime rebuilds stay on the project-local side.

### Run

```bash
scpp run
scpp run --entry=tests/php/sample.phs
```

Pass program arguments after `--`:

```bash
scpp run -- arg1 arg2
```

The same explicit rebuild flags are also accepted on `scpp run`:

```bash
scpp run --build-runtime
scpp run --build-dependencies -- arg1 arg2
scpp run --force
```

### CLI maintenance

- `scpp --version`: show installed CLI version
- `scpp --doctor`: show install/toolchain/Git status
- `scpp docs`: list curated local documentation names
- `scpp docs strict`: print the local strict PHP++ quick-learn
- `scpp docs diagnostics`: print local strict validation and diagnostics guidance
- `scpp update`: fast-forward installed checkout from `origin/main` and rebuild the default reusable runtime cache when a real update lands
- `scpp update --force`: rebuild that default reusable runtime cache even when already current
- `scpp runtime-build [--debug|--release] [--force]`: rebuild the reusable runtime cache explicitly
- `scpp clean`: remove generated `.prism/` state for a cold rebuild

Use `scpp runtime-build` when you want to refresh the shared reusable runtime cache itself. Use `scpp build --build-runtime` or `scpp run --build-runtime` when you want the current project build to rebuild its runtime path now.

If an existing project behaves oddly right after `scpp update`, and the same problem does not reproduce in a fresh project, clear that project's `.prism/` state once and rebuild:

```bash
scpp clean
scpp build
```

Treat this as a troubleshooting step for stale generated/build state after an update, not as the normal workflow.

### Single-file transpile

```bash
scpp input.phs
```

This prints generated C++ to stdout.

### Minimal first workflow

1. Create `main.phs`
2. Run `scpp init --php-profile=strict`
3. Write a tiny PHP++ / PHS strict program
4. Run `scpp build`
5. Run `scpp run`

### Minimal strict example

```php
echo "hello\n";
```

For Prism++ source files:

- do not start `.phs` files with `<?php`
- do not use `declare(strict_types=1);` in `.phs` or compatibility `.php` source files
- the Prism++ type/runtime contract is defined by Prism++ rules, not by PHP `strict_types`

### Strict config hint

In `prism.json`, the important project setting is:

```json
{
  "runtime": {
    "languages": {
      "php": {
        "profile": "strict"
      }
    }
  }
}
```

Legacy and strict PHP++ / PHS library surfaces must not be mixed in the same project.

Default runtime modules include `json`, `filesystem`, and `datetime`.

### Optional runtime modules

Some strict library families are opt-in project modules. Regex support is one of them.

For strict regex code, enable the `regex` module explicitly and use the typed `regex_*` surface:

```json
{
  "runtime": {
    "modules": ["regex"]
  }
}
```

```php
$captures vector<string> = [];
if (take($captures, regex_match("/(ab+)-(cd+)/i", "xxAbb-cDDyy"))) {
	echo $captures[0], "\n";
}
```

Do not use legacy `preg_*` names in strict projects. Those names belong to the PHP legacy surface, where PHP-compatible arrays may use `mixed_t`.

Current regex support is PCRE2-backed and requires the host PCRE2 development files to be installed manually. For the full supported surface and deferred PHP-compatibility flags, see `specs/builtins/regex/first_pass.md` and `docs/regex_builtins.md`.

### Async / await alpha surface

Strict PHS supports a first-slice stackless async/await surface:

```php
async function compute_value(): int {
	await async_sleep_ms(1);
	return 42;
}

$value int = await compute_value();
echo $value, "\n";
```

Current rules:

- `async function` requires an explicit return type
- `return` inside an async function returns the async task result
- `await async_sleep_ms(ms)` is valid only inside an async function
- expression-level `await some_async_function()` is allowed from synchronous code and lowers through `async_wait(...)`
- async/await is cooperative and does not create an OS thread per awaited operation

This is the lightweight async core, not the thread-backed `tasks` module and not fibers. For the current semantic contract and alpha limits, see `specs/async_await.md`.

### Where build output goes

- generated C++: `.prism/generated/`
- build artifacts: `.prism/build/`
- cached generation state: `.prism/cache/`

### If something fails

Use this rough checklist:

- source-level issue: check your PHP++ / PHS code first
- runtime shape issue: add `dbg(...)` or a gated `dbg_if(...)` near the confusing value
- wrong builtin or unsupported feature: check this doc, then the strict PHP++ / PHS examples
- generation/lowering issue: inspect generated C++ under `.prism/generated/`
- compile issue: inspect the C++ compiler error after `scpp build`

### Strict debug helpers

Strict projects can use one debug primitive for runtime value inspection:

```php
dbg($value);
dbg("label", $value);
dbg("label", $value, DBG_SHAPE | DBG_DEPTH_3 | DBG_PTR);
```

If no `DBG_*` flag is provided, `dbg` uses a generous bounded default:

```php
DBG_SOURCE | DBG_CALLER | DBG_TYPE | DBG_VALUE | DBG_SHAPE | DBG_DEPTH_2
```

Useful flags:

- `DBG_TYPE`: static/runtime type and kind information where available
- `DBG_VALUE`: short scalar or container preview
- `DBG_SHAPE`: nested container/wrapper shape
- `DBG_FIELDS`: object fields where reflection exists; otherwise a safe not-inspectable note
- `DBG_KEYS`: hash/table keys
- `DBG_LEN`: string/container length
- `DBG_SOURCE`: source file and line
- `DBG_CALLER`: caller/function-boundary context where available
- `DBG_PTR`: compact hex identity for objects and containers
- `DBG_COMPACT`: smaller limits
- `DBG_DEPTH_0` through `DBG_DEPTH_5`: explicit shape depth

Flags can be mixed with `|`:

```php
dbg("row", $row, DBG_KEYS | DBG_DEPTH_1);
dbg("node", $node, DBG_SHAPE | DBG_DEPTH_3 | DBG_PTR);
```

Only one depth flag may be provided. Multiple depth flags are treated as a debug API error.

Use debug gates to enable lower-call debugging from a higher-level condition:

```php
dbg_set("debug_company_12", $company->Id == 12);

process_company($company);

dbg_unset("debug_company_12", $company->Id == 12);
```

Deep inside:

```php
dbg_if("debug_company_12", "row", $row, DBG_SHAPE | DBG_DEPTH_2);
```

`dbg_set($name, false)` and `dbg_unset($name, false)` are no-ops. With a true guard, duplicate `dbg_set` or missing `dbg_unset` throws so mismatched gates are caught immediately.

`dbg` inspection is best-effort and should not crash user code. Unsupported values print type/kind information plus a safe not-inspectable marker.

### Runtime-debug playbook for real project failures

Use this sequence when a strict project builds, runs, and then fails at runtime.

1. Run the program normally with `scpp run`.
2. Read `scpp error` first.
3. Inspect the saved runtime fields that matter most:
   - `original_file`
   - `original_line`
   - `expected_type`
   - `actual_runtime_kind`
   - `operation`
4. If the failing value shape is still unclear, add `dbg(...)` immediately before the typed boundary or failing operation.
5. If the noisy part happens deeper in helper calls, use `dbg_set(...)` and `dbg_if(...)` to focus the trace.
6. Inspect `.prism/last_error.json`, `.line.tsv`, or generated C++ only when the saved diagnostics are not enough.

Current CLI runtime-failure presentation is intentionally source-first:

- `scpp run` shows the remapped source file/line, a tiny source snippet, the failing operation when available, and only source-mapped trace entries
- `scpp error` keeps that source-first summary and may include the saved raw runtime message too
- `scpp full-error` is the place to inspect full saved JSON, deeper generated C++ trace detail, and runtime-internal trace detail

End-to-end example:

```php
$row = json_decode($text);
echo "name=" . $row["name"] . "\n";
```

Possible failure:

```text
Runtime error in main.phs:2
Cannot convert value to required string_t.
Operation: scpp::cast<string_t>
```

Recommended next move:

```php
$row = json_decode($text);
dbg("name", $row["name"], DBG_SHAPE | DBG_TYPE);
echo "name=" . $row["name"] . "\n";
```

Then rewrite into an intentional stabilization pattern:

```php
$row = json_decode($text);
if (isset($row["name"])) {
	$name string = $row["name"];
	echo "name=" . $name . "\n";
}
```

Use each tool for the question it answers:

- `scpp error`: first compact runtime/build failure summary
- `scpp full-error`: full saved JSON report when you need all fields
- `scpp last-run`: compact build/run context and recent command outcome
- `scpp full-last-run`: full saved run metadata and command details
- `scpp explain-build`: why the tool rebuilt what it rebuilt
  Common focused views:
  `scpp explain-build files-transpiled`,
  `scpp explain-build files-reused`,
  `scpp explain-build outputs-rebuilt`,
  `scpp explain-build rebuild-fanout`,
  `scpp explain-build generated-artifacts`,
  `scpp explain-build grouping`,
  `scpp explain-build project-units` for force-include and dependency-summary diagnostics,
  `scpp explain-build project-unit <source>` for one source's project-unit dependency row,
  `scpp explain-build modules`,
  `scpp explain-build module <name>`,
  `scpp explain-build entrypoint`,
  `scpp explain-build final-output`,
  `scpp explain-build generated-files`,
  `scpp explain-build ninja-target`
- `dbg(...)`: inspect runtime shape and typed-boundary inputs
- `.line.tsv`: remap generated locations back to source when the saved report still points into generated artifacts
- generated C++: inspect lowering only after the source-level diagnostic suggests a generator/runtime-boundary problem

Keep build-debug and runtime-debug separate:

- use `scpp explain-build` and `last-run` when the problem is rebuild causality, entrypoint selection, or build orchestration
- use `scpp error`, `scpp full-error`, `dbg(...)`, and typed-boundary inspection when the problem is runtime shape or strict type stabilization

### When unsure

Use this order:

1. this quick-learn doc
2. strict examples under `simple_cpp/docs/examples/php/strict/`
3. relevant specs under `simple_cpp/specs/`

## Mental Model

- Write PHP++ / PHS source, not generated C++.
- Prefer explicit types at meaningful boundaries.
- Expect most strict code to stay typed after a small number of well-chosen entry boundaries.
- Avoid `mixed_t` when the shape is known.
- Prefer `vector<T>` for typed sequential data when possible.
- Prefer `hash<T>` for typed keyed data when string keys are the natural shape.
- Use `hash<T, T_KEY>` when the key family is intentionally typed and not the default string-key shape.
- Use `mixed` or `dynamic` only when the value is genuinely dynamic.
- Resolve wrappers near meaningful boundaries: `nullable<T>`, `result<T>`, `result_or_false<T>`, `result_or_bool<T>`.
- Keep `null`, `false`, and error as separate states.
- Prefer `===` over loose comparison.
- Treat the strict library surface as its own API, even when many general helpers keep familiar PHP-like names.
- When in doubt, prefer documented PHP++ / PHS behavior over familiar PHP intuition.

## What Strict Means Today

- `Guaranteed`: the strict profile selects the strict builtin/library surface
- `Guaranteed`: strict visible names use plain PHP-like names for general language-adjacent helpers and family prefixes for subsystem/domain helpers such as `fs_*`, `io_*`, `dt_*`, and `json_*`
- `Guaranteed`: wrapper-oriented result handling is part of the supported model
- `Guaranteed`: the current supported subset includes `throw`, `try/catch`, `try/finally`, and `try/catch/finally`
- `Preferred`: typed boundaries, early wrapper resolution, and explicit comparisons
- `Not implied`: full PHP compatibility
- `Not implied`: full enforcement of every preferred style rule

## Main Differences From Normal PHP

- The language surface is PHP-like, not PHP-complete.
- PHP++ / PHS strict projects use a different visible builtin surface:
  - `fs_get`, not `file_get_contents`
  - `fs_put`, not `file_put_contents`
  - `strlen`, not `str_strlen`
  - `io_open`, not `fopen`
- Dynamic values should be stabilized early.
- Wrapper-returning operations are common and intentional.
- Arrays/tables are narrower than full PHP arrays.
- Typed containers are preferred when the shape is known.
- Generated C++ is a debugging artifact, not the language definition.

## Learn By Example

| Topic | Example |
| --- | --- |
| `take` with error output | `if (take($text, $err, fs_get($path))) { ... }` |
| `take` with wrapper output | `if (take($fh, io_open($path, "rb"))) { ... }` |
| `take` with nullable | `if (take($name, $maybe_name)) { ... }` |
| typed local shorthand | `$count int = 0;` |
| typed vector local shorthand | `$items vector<int> = [1, 2, 3];` |
| typed vector local | `$items vector<int> = [1, 2, 3];` |
| typed hash local shorthand | `$by_name hash<int> = ["a" => 1, "b" => 2];` |
| typed two-arg hash shorthand | `$by_id hash<string, int> = [0 => "a", 1 => "b"];` |
| typed class property shorthand | `public $list vector<T> = [];` |
| typed param shorthand | `function build($items vector<string>) { ... }` |
| typed return shorthand | `function build(): vector<string> { ... }` |
| typed arrow return shorthand | `$make = fn($x int) function<function<int(int)>(int)> => fn($y int): int => $x + $y;` |
| vector literal needs explicit typed context | `$v vector<int> = [1, 2, 3];` |
| typed read from dynamic value | `$count int = $data["count"];` |
| typed hash slot from dynamic value | `$counts hash<int> = []; $counts["id"] = $row["id"];` |
| typed vector append from dynamic value | `$items vector<int> = []; $items[] = $row["count"];` |
| typed append on vector | `$items[] = 4;` |
| wrapper-friendly coalesce | `$name = $maybe_name ?? "guest";` |
| `foreach` value | `foreach ($data as $v) { ... }` |
| `foreach` key/value | `foreach ($data as $k => $v) { ... }` |
| `foreach` by reference | `foreach ($data as &$v) { ... }` |
| `foreach` key/value by reference | `foreach ($data as $k => &$v) { ... }` |
| `try/catch` | `try { work(); } catch (MyEx $e) { ... }` |
| `try/finally` | `try { work(); } finally { cleanup(); }` |
| `try/catch/finally` | `try { work(); } catch (MyEx $e) { ... } finally { cleanup(); }` |
| `throw` | `throw new MyEx("x");` |
| strict general builtin | `$n = strlen("hello");` |
| strict filesystem builtin | `take($data, $err, fs_get($file));` |
| strict IO builtin | `take($written, io_write($fh, "abc"));` |
| strict JSON builtin | `$data = json_decode($json);` |
| Prism++ file start | `echo "hello\n";` |
| nullable local | `$id ?int = null;` |
| strict comparison | `if ($value === 0) { ... }` |
| namespace + typed property example | `namespace demo\schema; class model { public $properties vector<model_property> = []; }` |
| helper semantics | `echo isset($map["a"]) ? "Y\n" : "N\n";` |

## Preferred Strict Patterns

| Prefer | Over |
| --- | --- |
| `$count int = $row["count"];` | `$count = $row["count"];` |
| `$counts["id"] = $row["id"];` where `$counts` is `hash<int>` | `$counts["id"] = (int) $row["id"];` |
| `$items[] = $row["count"];` where `$items` is `vector<int>` | `$items[] = (int) $row["count"];` |
| `$count int = $row["count"];` | `$count = $row["count"];` |
| `if ($status === "ready")` | `if ($status)` |
| `vector<T>` for typed lists | dynamic list/table when not needed |
| `hash<T>` for typed string-keyed data | dynamic keyed table when not needed |
| `hash<T, T_KEY>` for explicit typed keys | forcing everything through dynamic keyed tables |
| `take($out, $err, fs_get(...))` | carrying wrapper state further than needed |
| explicit success/failure checks | ambiguous truthiness |

## Strict-Mode Idioms Cookbook

Use this section as a short authoring guide for common Prism++ strict patterns.

The goal is not to catalog every language rule.
The goal is to answer the practical question:

> "What is the normal strict way to write this?"

### 1. Nullable guard chains

Preferred:

```php
if (!isset($root->child->name)) {
	echo "missing\n";
	return;
}

$name string = $root->child->name;
echo $name, "\n";
```

Less preferred:

```php
if ($root === null || $root->child === null || $root->child->name === null) {
	echo "missing\n";
	return;
}
```

Use explicit step-by-step guards when different missing states matter separately.
Otherwise, prefer `isset(...)` for compact nullable-path checks.

### 2. Required decoded JSON field

Preferred:

```php
$row = json_decode($text);
$name string = $row["name"];
```

This is a normal strict stabilization step.
The left side is already a visible typed boundary.

### 3. Optional decoded JSON field

Preferred when absence should default:

```php
$row = json_decode($text);
$nickname string = isset($row["nickname"]) ? $row["nickname"] : "";
```

Preferred when absence should remain distinct:

```php
$row = json_decode($text);
$nickname = null;
if (isset($row["nickname"])) {
	$nickname string = $row["nickname"];
}
```

### 4. Typed destination versus explicit cast

Preferred when the destination is already typed:

```php
$row = json_decode($text);
$count int = $row["count"];
```

Also preferred:

```php
$counts hash<int> = [];
$counts["id"] = $row["id"];
```

Current but usually unnecessary:

```php
$count int = (int) $row["count"];
$counts["id"] = (int) $row["id"];
```

Rule of thumb:

- if the receiving side already has a stable explicit type, that destination is usually enough
- keep an explicit cast only when it genuinely clarifies intent or is still required by a specific uncovered flow

### 5. Unqualified helpers in strict code

Normal strict code may use plain helper names when they are part of the strict surface:

```php
$value = trim("  Hello  ");
$lower = strtolower($value);
$head = substr($lower, 0, 2);
echo $head, "\n";
```

Do not assume every normal PHP helper exists.
Check the strict quick reference or builtin docs when the helper is unfamiliar.

### 6. Collection mutation

Normal typed-vector mutation:

```php
$items vector<string> = ["a", "b", "c"];
unset($items[1]);
echo count($items), "\n";
```

Normal typed-vector append:

```php
$items[] = "d";
```

Normal typed-hash write:

```php
$scores hash<int> = [];
$scores["alice"] = 10;
```

Prefer typed containers when the collection shape is already known.

### 7. Wrapper extraction

Preferred:

```php
$text string = "";
$err error;

if (!take($text, $err, fs_get($path))) {
	echo "read failed\n";
	return;
}
```

Do not carry wrapper-shaped results forward as if they were ordinary dynamic PHP values.

### 8. PHP habit to strict habit

| PHP habit | Strict habit |
| --- | --- |
| `$name = $row["name"];` | `$name string = $row["name"];` when the shape is intentionally assumed |
| `if (!$result) { ... }` | split `=== false`, `=== null`, or error paths explicitly |
| PHP file inclusion for project composition | let `scpp build` compose `.phs` files and use `dependencies` for cross-project work |
| broad dynamic arrays everywhere | use `vector<T>` or `hash<T>` when the shape is known |
| pushing wrapper states deeper into the program | unwrap near the boundary with `take(...)` |

### 9. Historical workaround versus current style

Some older strict code carries explicit casts or extra manual guards because earlier versions needed them.

Current guidance:

- `Preferred`: keep the visible typed destination and remove noise that no longer adds meaning
- `Current`: keep historical carry-over code when it is still clearer for the local team
- `Avoid`: treating every older workaround as if it were still the canonical strict style

When cleanup feels risky, validate with `scpp build`, `scpp run`, or focused tests instead of guessing.

## Container Guidance

- Use `vector<T>` when the data is sequential and typed.
- Use `hash<T>` when the data is keyed, typed, and naturally string-keyed.
- Use `hash<T, T_KEY>` when the key family itself is intentionally typed.
- Use `mixed_t` only when the shape is intentionally dynamic or unknown.
- This is guidance, not a forced rule.

In practice:

- `vector<string>` is a better fit than a dynamic packed container when all elements are strings.
- `hash<int>` is a better fit than a dynamic object-like table when keys are known strings and values are all ints.
- `hash<string, int>` is the supported two-argument generic form when the key family itself is intentionally typed.
- `dynamic` remains appropriate for decoded JSON and truly dynamic payloads.

Stable-left-side rule in strict code:

- if the receiving side already has a stable explicit destination type, that destination is enough
- this includes locals, properties, typed hash slots, typed vector appends, typed arguments, and typed returns
- if the destination is explicitly `mixed`, no cast is needed and the value remains `mixed`

Examples:

```php
$name string = $row["name"];
```

```php
$counts hash<int> = [];
$counts["id"] = $row["id"];
```

```php
$items vector<int> = [];
$items[] = $row["count"];
```

```php
$meta hash<mixed> = [];
$meta["title"] = $row["title"];
```

## Typed shorthand note

Use shorthand surface syntax in strict code, for example `$count int = 0;`.

PHP-compatible inline annotation syntax such as `$count /** int */ = 0;` is legacy compatibility syntax.
Do not use it in new strict code.
Plan for it to become a hard error in a future version.

The same strict-mode rule applies to all types, including:

- user-defined types
- `error`
- `resource_handle`
- `curl_handle`
- `curl_response`

The pre-tokenizer currently normalizes shorthand into a PHP-compatible annotated form before `php-ast` parsing, while separately preserving site ownership metadata for:

- locals
- properties
- parameters
- function returns
- method returns
- closure and arrow-function returns

This scanner-owned metadata is what keeps nested closure return annotations attached to the correct outer site instead of relying on raw `php-ast` doc-comment attachment quirks.

## Wrapper Pattern

Strict code should expect wrapper-shaped operations.

Common pattern:

```php
$text string = "";
$err error;

if (!take($text, $err, fs_get($path))) {
	echo "read failed\n";
} else {
	echo $text, "\n";
}
```

Rule of thumb:

- unwrap near the boundary
- move forward with concrete typed values

`take(...)` is the normal bridge from wrapper-carrying operations into ordinary typed locals.

- `take($out, nullable<T>)` writes the present value and returns `true`
- `take($out, result_or_false<T>)` writes the success value and returns `true`
- `take($out, $err, result<T>)` writes either the success value or the `error`
- `take($out, $flag, result_or_bool<T>)` keeps the bool sentinel branch separate

For PHP++ / PHS code, that is the main mental model you need. You usually do not need to think about the generated C++ lowering to use it correctly.

## Typed Reads From Dynamic Data

Typed reads such as:

```php
$count int = $row["count"];
```

are stabilization steps.

- `Preferred`: use them when you expect the shape and want to move into typed code quickly
- `Important`: if the key is missing or the value shape is not what you expect, do not assume this is a harmless PHP-style read
- `When unsure`: guard first, or keep the value dynamic a little longer, or use a wrapper-producing boundary before stabilizing

Decoded JSON is still dynamic after `json_decode(...)`.
Treat typed extraction from it as an assumption about shape, not as free PHP flexibility.

## Dynamic-Data Stabilization Cookbook

Use this section when incoming data is dynamic but the next step in the code wants a predictable typed value.

### 1. Required string field from decoded JSON

Use a direct typed read when the field is required and the program genuinely expects that shape:

```php
$row = json_decode($text);
$name string = $row["name"];
echo $name, "\n";
```

Why this is a good fit:

- the left side is a visible typed boundary
- the code is making a deliberate shape assumption
- later code can treat `$name` as ordinary typed data

What assumption it makes:

- `$row["name"]` exists
- the runtime value is convertible to `string`

If the key is missing, the read produces `null` before typed-boundary conversion is attempted.
If the value exists but has the wrong runtime kind, expect a strict runtime type failure at that boundary.

### 2. Optional string field

When a field is optional, choose between defaulting and nullable handling on purpose.

Use a default when the rest of the code wants a normal string either way:

```php
$row = json_decode($text);
$nickname string = isset($row["nickname"]) ? $row["nickname"] : "";
```

Use nullable handling when absence is meaningful and should stay distinct:

```php
$row = json_decode($text);
$nickname = null;
if (isset($row["nickname"])) {
	$nickname string = $row["nickname"];
}
```

Rule of thumb:

- default when absence should collapse into a normal fallback value
- keep the nullable or absent state distinct when later logic cares about that difference

For nullable path guards, prefer the helper form:

```php
if (!isset($node->child->name)) { ... }
```

instead of a long manual chain like:

```php
$node === null || $node->child === null || $node->child->name === null
```

### 3. Stabilizing into typed containers

Typed container writes are ordinary typed boundaries under the stable-left-side rule.

Typed hash slot:

```php
$row = json_decode($text);
$counts hash<int> = [];
$counts["id"] = $row["id"];
```

Typed vector append:

```php
$row = json_decode($text);
$items vector<int> = [];
$items[] = $row["count"];
```

Why this pattern is useful:

- the destination type is explicit at the point where data enters the container
- the container stays typed after the boundary
- the code avoids ad hoc manual casts scattered around later reads

Prefer this when the program already knows the target container shape.

### 3A. Decoded JSON / fat-variable boundary

`json_decode(...)` returns fat-variable data.

In practice, that means a `mixed` / dynamic-shaped boundary value, not a preferred interior representation for strict business logic.

Strict-mode posture:

- use `json_decode(...)` freely at the ingestion boundary
- describe the expected payload shape locally when it is known
- stabilize early into typed locals, typed properties, typed objects, or typed containers
- avoid carrying fat-variable state deep into the rest of the program unless the flexibility is intentionally needed

When the source shape is known from a schema, API contract, or file format, a short local shape comment is encouraged:

```php
/** decoded property_data shape:
 *  - name: string
 *  - type.name: string
 *  - type.list: bool
 *  - required: bool
 */
$property_data = json_decode($text);

if (isset($property_data["name"])) {
	$out->name = $property_data["name"];
}
if (isset($property_data["type.name"])) {
	$out->type_name = $property_data["type.name"];
}
if (isset($property_data["type.list"])) {
	$out->type_list = $property_data["type.list"];
}
if (isset($property_data["required"])) {
	$out->required = $property_data["required"];
}
```

In that style:

- the decoded payload stays broad only at the edge
- each typed write becomes an explicit stabilization point
- extra casts are often unnecessary when the receiving side is already typed

Use explicit casts only when they still add meaning or a specific flow still needs them:

```php
$out->name = (string) $property_data["name"];
```

Preferred when the left side is already typed and the shape assumption is intentional:

```php
$out->name = $property_data["name"];
```

### 4. When to delay stabilization

Delay the typed boundary when the incoming shape is still genuinely uncertain.

```php
$row = json_decode($text);
$value = $row["name"];
dbg("name", $value, DBG_SHAPE | DBG_TYPE);
```

After you understand the shape, follow with a guarded typed step:

```php
if (isset($row["name"])) {
	$name string = $row["name"];
	echo $name, "\n";
}
```

This is usually the better move when:

- you are onboarding an unfamiliar payload
- the field may be one of several runtime kinds
- the saved runtime diagnostic already told you a typed boundary is failing but not why the payload has that shape

### 5. Nullable and mixed boundary guidance

When a field may be absent, `null`, a string, or the wrong kind entirely, keep those states explicit.

```php
$row = json_decode($text);

if (!isset($row["path"])) {
	echo "path missing\n";
	return;
}

$raw = $row["path"];

if ($raw === null) {
	echo "path is null\n";
	return;
}

$path string = $raw;
echo $path, "\n";
```

Mental model:

- absent key: guard with `isset(...)` first when absence is expected
- explicit `null`: keep it distinct from missing and from wrong-kind values
- expected string: stabilize at a visible typed boundary
- wrong-kind container/hash/object: expect strict runtime failure if you force a typed boundary too early, then inspect with `dbg(...)` and adjust the guard pattern

The goal is not to avoid typed boundaries.
The goal is to place them where the shape assumption is intentional and easy to reason about.

## `error` In Practice

`error` is the source-facing structured error payload used by `result<T>`-style wrappers.

- `Guaranteed`: it can carry error information such as message, line, and file
- `Preferred`: in quick strict code, treat it as a payload you capture when `take(..., $err, ...)` fails
- `Current`: detailed PHP++ / PHS user-facing accessor patterns for reading `error` are not fully documented in this quick-learn yet

If your code depends heavily on inspecting error details, check the deeper runtime/spec docs before standardizing a pattern.

## Small Failure Patterns

```php
if (!take($text, $err, fs_get($path))) {
	echo "read failed\n";
}
```

```php
$pos = strpos("banana", "zz") ?? -1;
```

```php
if (!take($fh, io_open($path, "rb"))) {
	echo "open failed\n";
}
```

## Exceptions

The current supported subset includes:

- `throw new MyEx("x");`
- `try { ... } catch (MyEx $e) { ... }`
- `try { ... } finally { ... }`
- `try { ... } catch (MyEx $e) { ... } finally { ... }`

Current limits still matter:

- treat this as a supported subset, not a blanket claim of full PHP exception parity
- some control-flow combinations around `finally` are still restricted
- when exception behavior is central to the code, check the deeper generator specs/examples

## Memory Expectations

Normal PHP++ / PHS authored code should think in terms of typed values, wrappers, and documented runtime behavior.

You generally do not write manual memory-management code, reason about raw pointers, or manage leaks directly at the authoring-surface level.

## Strict Library Surface

Visible PHP++ / PHS strict names use plain PHP-like names for general language-adjacent helpers and family prefixes for subsystem/domain helpers.

### Helpers

| Name | Compact signature | Notes |
| --- | --- | --- |
| `take` | `take($out, nullable<T>) -> bool` | extracts success value |
| `take` | `take($out, result_or_false<T>) -> bool` | false/error branch returns `false` |
| `take` | `take($out, $err, result<T>) -> bool` | fills value or error |
| `take` | `take($out, $flag, result_or_bool<T>) -> bool` | bool branch kept separate |
| `count` | `count(mixed) -> int` | PHP-owned helper |
| `empty` | `empty(mixed) -> bool` | PHP-owned helper |
| `isset` | `isset(mixed) -> bool` | PHP-owned helper |
| `cli_argc` | `cli_argc() -> int` | CLI helper |
| `cli_argv` | `cli_argv() -> mixed` | dynamic argv payload |
| `cli_args` | `cli_args() -> mixed` | alias of `cli_argv()` |
| `shell_exec` | `shell_exec(string $command)` | `result_or_false<string>` |

### Strings

| Name | Compact signature | Return shape / note |
| --- | --- | --- |
| `substr` | `substr(string $s, int $offset, ?int $len = null)` | `string` |
| `substr_compare` | `substr_compare(string $main, string $part, int $offset, ?int $len = null, ?bool $ci = null)` | `int` |
| `substr_replace` | `substr_replace(string $subject, string $replacement, int $offset, ?int $len = null)` | `string` |
| `str_pad` | `str_pad(string $input, int $pad_len, ?string $pad = " ", ?int $type = STR_PAD_RIGHT)` | `string` |
| `str_replace` | `str_replace(string $search, string $replace, string $subject)` | `string` |
| `explode` | `explode(string $sep, string $s, ?int $limit = null)` | `vector<string>` |
| `implode` | `implode(string $sep, vector<string>|hash_t<string>|mixed $parts)` | `string`; `mixed` must be array-like |
| `hex2bin` | `hex2bin(string $hex)` | `result_or_false<string>`; usually `take($out, ...)` |
| `bin2hex` | `bin2hex(string $bytes)` | `string` |
| `number_format` | `number_format(int|float $n, ?int $dec = null, ?string $dot = null, ?string $group = null)` | `string` |
| `strlen` | `strlen(string $s)` | `int` byte length |
| `strpos` | `strpos(string $haystack, string $needle, ?int $offset = null)` | `result_or_false<int>` |
| `strrpos` | `strrpos(string $haystack, string $needle, ?int $offset = null)` | `result_or_false<int>` |
| `strtolower` | `strtolower(string $s)` | `string`, byte/ASCII-oriented |
| `strtoupper` | `strtoupper(string $s)` | `string`, byte/ASCII-oriented |
| `lcfirst` | `lcfirst(string $s)` | `string` |
| `ucfirst` | `ucfirst(string $s)` | `string` |
| `str_starts_with` | `str_starts_with(string $haystack, string $needle)` | `bool` |
| `str_ends_with` | `str_ends_with(string $haystack, string $needle)` | `bool` |
| `trim` | `trim(string $s, ?string $mask = null)` | `string` |
| `ltrim` | `ltrim(string $s, ?string $mask = null)` | `string` |
| `rtrim` | `rtrim(string $s, ?string $mask = null)` | `string` |

### IO

| Name | Compact signature | Return shape / note |
| --- | --- | --- |
| `io_open` | `io_open(string $path, string $mode)` | wrapper result to `resource_handle`; use `take($fh, ...)` |
| `io_seek` | `io_seek(resource_handle $fh, int $offset)` | current result is seek-status style; see examples/specs if it matters |
| `io_tell` | `io_tell(resource_handle $fh)` | wrapper result to `int`; use `take($pos, ...)` |
| `io_read_line` | `io_read_line(resource_handle $fh)` | wrapper result to `string`; use `take($line, ...)` |
| `io_read` | `io_read(resource_handle $fh, int $len)` | wrapper result to `string`; use `take($data, ...)` |
| `io_write` | `io_write(resource_handle $fh, string $data)` | wrapper result to `int`; use `take($written, ...)` |
| `io_rewind` | `io_rewind(resource_handle $fh)` | `bool` |
| `io_flush` | `io_flush(resource_handle $fh)` | `bool` |
| `io_eof` | `io_eof(resource_handle $fh)` | `bool` |
| `io_close` | `io_close(resource_handle $fh)` | `bool` |

### Filesystem

| Name | Compact signature | Return shape / note |
| --- | --- | --- |
| `fs_is_file` | `fs_is_file(string $path)` | `bool` |
| `fs_is_dir` | `fs_is_dir(string $path)` | `bool` |
| `fs_is_link` | `fs_is_link(string $path)` | `bool` |
| `fs_exists` | `fs_exists(string $path)` | `bool` |
| `fs_get` | `fs_get(string $path)` | wrapper result to `string`; use `take($text, $err, ...)` |
| `fs_put` | `fs_put(string $path, string $data)` | wrapper result to `int`; use `take($written, $err, ...)` |
| `fs_mkdir` | `fs_mkdir(string $path)` | `bool` |
| `fs_scan` | `fs_scan(string $path)` | wrapper result; usually stabilize into `vector<string>` in strict code |
| `fs_size` | `fs_size(string $path)` | wrapper result to `int`; use `take($size, $err, ...)` |
| `fs_mtime` | `fs_mtime(string $path)` | wrapper result to `int`; use `take($mtime, $err, ...)` |
| `fs_touch` | `fs_touch(string $path)` | `bool` |
| `fs_rmdir` | `fs_rmdir(string $path)` | `bool` |
| `fs_remove` | `fs_remove(string $path)` | `bool` |
| `fs_copy` | `fs_copy(string $src, string $dst)` | `bool` |
| `fs_rename` | `fs_rename(string $src, string $dst)` | `bool` |
| `fs_realpath` | `fs_realpath(string $path)` | wrapper result to `string`; use `take($real, $err, ...)` |
| `fs_dirname` | `fs_dirname(string $path)` | `string` |
| `fs_basename` | `fs_basename(string $path)` | `string` |

### JSON

| Name | Compact signature | Return shape / note |
| --- | --- | --- |
| `json_decode` | `json_decode(string $json)` | `dynamic`; arrays/objects become dynamic/hash-backed |
| `json_encode` | `json_encode(mixed $value)` | `string` |

### Datetime

Strict datetime code should use the family-prefixed `dt_*` surface. The PHP-shaped `date()` and `strtotime()` names are legacy wrappers; do not use them as the default style in strict projects.

| Name | Compact signature | Return shape / note |
| --- | --- | --- |
| `dt_now` | `dt_now()` | `int`, current Unix wall-clock seconds |
| `dt_now_ms` | `dt_now_ms()` | `int`, current Unix wall-clock milliseconds |
| `dt_monotonic_ms` | `dt_monotonic_ms()` | `int`, elapsed-time counter; not a Unix timestamp |
| `dt_sleep_ms` | `dt_sleep_ms(int $ms)` | `void`; host scheduler precision applies |
| `dt_format_iso_utc` | `dt_format_iso_utc(int $timestamp)` | `string`, fixed `YYYY-MM-DDTHH:MM:SSZ` UTC format |
| `dt_parse_iso_utc` | `dt_parse_iso_utc(string $value)` | wrapper result to `int`; accepts only fixed UTC ISO form |
| `dt_format` | `dt_format(string $format, int $timestamp)` | `string`, common local-time formatter |
| `dt_format_now` | `dt_format_now(string $format)` | `string`, common local-time formatter using current time |
| `dt_parse` | `dt_parse(string $value)` | wrapper result to `int`; accepts common local/ISO forms |

Common formatter tokens currently supported: `Y`, `y`, `m`, `n`, `d`, `j`, `H`, `G`, `i`, `s`, and `U`.

Common parser forms currently supported:

- `YYYY-MM-DD`
- `YYYY-MM-DD HH:MM:SS`
- `YYYY-MM-DDTHH:MM:SS`
- `YYYY-MM-DDTHH:MM:SSZ`

Unsupported in the current strict datetime surface: named timezone conversion, locale-aware month/day names, calendar arithmetic, and PHP `strtotime` natural-language expressions such as `next Tuesday` or `+1 day`.

```php
$stamp int = 0;
$err error;

if (take($stamp, $err, dt_parse("2024-02-29 12:34:56"))) {
	echo dt_format("Y-m-d H:i:s", $stamp), "\n";
}
```

## Tiny Canonical Example

```php
$file = "sample.txt";
$err error;
$written int = 0;

if (take($written, $err, fs_put($file, "{\"name\":\"alex\",\"count\":2}\n"))) {
	$data string = "";
	if (take($data, $err, fs_get($file))) {
		$row = json_decode($data);
		$name string = $row["name"];
		$count int = $row["count"];
		echo strlen($name), "\n";
		echo $count, "\n";
	}
}
```

This example shows:

- PHP-like syntax
- plain helper names for general language-adjacent builtins
- typed locals at meaningful boundaries
- wrapper extraction with `take(...)`
- dynamic JSON decoded first, then stabilized into typed locals

This example assumes the decoded JSON has the expected keys and compatible value shapes.

## Common PHP Misreadings

- strict builtin names are not just cosmetic aliases for old PHP names
- dynamic decoded JSON is not the same as a fully trusted typed structure
- `null`, `false`, and error should not be collapsed into one “falsy” state
- `vector<T>` and `hash<T>` / `hash<T, T_KEY>` are deliberate typed choices, not just PHP arrays with extra decoration
- supported `try/catch/finally` does not mean full PHP exception parity in every shape

## Do Not Assume

- Do not assume full PHP support.
- Do not assume every old PHP builtin name exists in strict mode.
- Do not assume `mixed_t` should flow through the whole program.
- Do not assume dynamic containers are the best default.
- Do not assume ambiguous truthiness is good strict style.
- Do not assume generated C++ defines the language.

## Best Starting References

- git repo: `https://github.com/alexstanciu-1/simplecpp`
- install/setup: `https://github.com/alexstanciu-1/simplecpp/blob/main/README.md`
- Simple C++ overview: `simple_cpp/README.md`
- project build model: `simple_cpp/specs/project_build_v1.md`
- strict-mode guidance: `simple_cpp/specs/strict_mode.md`
- strict profile split: `simple_cpp/specs/php/library_profiles.md`
- AI language model: `simple_cpp/docs/ai_onboarding/language_model.md`
- strict examples: `simple_cpp/docs/examples/php/strict/project_samples/`
