# JSS Module And Namespace Proposal
Doc Status: planning

Date: 2026-06-12

Purpose: define a concrete way to combine JSS user namespaces, reserved helper families, and project/runtime modules without importing JavaScript module-runtime semantics or creating a second symbol-resolution model beside STAN.

This is a planning artifact, not semantic authority.

## Recommendation

Use this split of responsibilities:

- `namespace` owns user declaration qualification and declaration ownership
- reserved helper families own ergonomic JSS capability syntax
- `prism.json` runtime module selection owns capability availability
- STAN resolves helper-family validity and module-gated availability

Short version:

```text
namespace = where user symbols live
helper    = ergonomic JSS capability surface such as fs.get(...)
module    = whether that capability is available in this project at all
```

This keeps JSS pleasant to read while avoiding fake JavaScript import/load behavior.

## Core Model

### 1. User namespaces stay source-level

JSS declarations may live in namespaces:

```js
namespace App.Http;

class Client {
    static version(): string {
        return "1.0";
    }
}
```

or block form:

```js
namespace App.Http {
    class Client {
        static version(): string {
            return "1.0";
        }
    }
}
```

Both forms mean the same thing:

- the symbol is declared in `App\Http`
- fully qualified references lower through the existing namespace/class symbol path
- STAN sees ordinary namespace-qualified declarations

### 2. Static `use` stays for ordinary symbols

JSS imports remain static compile-time imports for ordinary user/library symbols:

```js
use App.Http.Client;
use function App.Http.makeClient;
use const App.Http.VERSION;
```

This continues the existing JSS direction:

- no dynamic import
- no JS `export`
- no runtime module loader in source

### 3. Runtime modules stay project-level

Runtime modules are selected in project configuration, not source syntax.

Example direction:

```json
{
  "runtime": {
    "languages": {
      "php": { "profile": "strict" }
    },
    "modules": ["filesystem", "json", "io", "datetime"]
  }
}
```

The project config answers:

- is `filesystem` available?
- is `json` available?
- is `io` available?
- is `datetime` available?

Source does not answer this through `import fs from "..."`.

### 4. Modules expose reserved helper families in JSS

A runtime module contributes a documented helper surface in JSS and maps to existing strict PHS helper names.

Recommended first reserved helper families:

- `fs`
- `io`
- `json`
- `dt`

Possible later families:

- `http`
- `curl`

Example JSS-to-PHS mappings:

| JSS helper | PHS helper |
| --- | --- |
| `fs.get(path)` | `fs_get($path)` |
| `fs.scan(path)` | `fs_scan($path)` |
| `io.open(path, mode)` | `io_open($path, $mode)` |
| `io.read_line(handle)` | `io_read_line($handle)` |
| `io.read(handle, size)` | `io_read($handle, $size)` |
| `json.decode(text)` | `json_decode($text)` |
| `json.encode(value)` | `json_encode($value)` |
| `dt.parse(text)` | `dt_parse($text)` |
| `dt.format(fmt, value)` | `dt_format($fmt, $value)` |

So JSS can write:

```js
function loadConfig(path: string): mixed {
    let text: mixed = fs.get(path);
    return json.decode(text);
}
```

while emitted PHS can stay in the project’s existing helper style:

```php
function loadConfig(string $path): mixed {
	$text mixed = fs_get($path);
	return json_decode($text);
}
```

This gives JSS a modern surface without forcing PHS to adopt awkward helper namespaces.

### First-pass helper contract notes

The first JSS helper-family surface should stay tightly coupled to already-known strict helper contracts instead of inventing JS-like dynamic helper objects.

| Helper | Current contract shape | Current intended JSS use |
| --- | --- | --- |
| `fs.get(path)` | `result<string>` | `take(text, err, fs.get(path))` |
| `fs.put(path, text)` | `result<int>` | `take(written, fs.put(path, text))` |
| `fs.scan(path)` | `result<vector<string>>` | `take(entries, fs.scan(path))` |
| `fs.realpath(path)` | `result<string>` | `take(fullPath, fs.realpath(path))` |
| `fs.exists(path)` | `bool` | direct predicate or explicit ternary branch |
| `fs.basename(path)` | `string` | direct string expression |
| `io.open(path, mode)` | `result_or_false<resource_handle>` | `take(handle, io.open(path, mode))` |
| `io.read_line(handle)` | `result_or_false<string>` | `take(line, io.read_line(handle))` |
| `io.read(handle, size)` | `result_or_false<string>` | `take(text, io.read(handle, size))` |
| `io.write(handle, text)` | `result_or_false<int>` | `take(written, io.write(handle, text))` |
| `io.flush(handle)` | `bool` | direct statement/predicate |
| `io.rewind(handle)` | `bool` | direct statement/predicate |
| `io.close(handle)` | `bool` | direct statement/predicate |
| `json.decode(text)` | `dynamic` | explicit dynamic boundary, stabilize later |
| `json.encode(value)` | `string` | direct string expression |
| `dt.parse(text)` | `result<int>` | `take(stamp, err, dt.parse(text))` |
| `dt.parse_iso_utc(text)` | `result<int>` | `take(stamp, err, dt.parse_iso_utc(text))` |
| `dt.format(fmt, stamp)` | `string` | direct string expression |
| `dt.format_iso_utc(stamp)` | `string` | direct string expression |
| `dt.format_now(fmt)` | `string` | direct string expression |

That contract table is intentionally boring. That is a good thing. It means the JSS helper surface is just an ergonomic spelling over existing strict helpers rather than a second behavior model.

## Recommended JSS Surface

### Preferred source syntax

1. Declaration ownership:

```js
namespace App.Tools;
namespace App.Tools { ... }
```

2. Reserved helper-family calls:

```js
fs.get(path)
json.decode(text)
io.open(path, "rb")
dt.format("Y-m-d", value)
```

### Preferred lowering/meaning

- user namespaces still lower through the existing backslash namespace model
- reserved helper-family calls lower to the current strict flat helper functions
- project module selection does not alter user namespace syntax, only helper availability

## Why This Shape Is Clean

### It avoids fake JS module imports

Bad direction:

```js
import fs from "scpp:fs";
```

Problems:

- looks like runtime JS module semantics
- encourages default-export/module-object thinking
- implies source-level module loading rather than project capability selection
- risks a second path for symbol resolution beside STAN

Recommended direction:

```js
let text: mixed = fs.get(path);
```

Benefits:

- modern-feeling JSS syntax
- no JavaScript module-runtime implication
- module availability remains a project concern

### It avoids awkward PHS helper namespaces

Forcing the target surface toward:

```php
\scpp\fs\get($path)
```

would look odd in this repository compared with the existing helper style.

Using JSS helper families but lowering to flat PHS helpers gives us:

- ergonomic JSS
- familiar PHS/runtime naming
- no need to restyle the existing runtime/helper surface

### It lets STAN own the right diagnostics

With this model, STAN can answer two different questions cleanly:

1. Is this a known helper family/member pair?

- unknown helper such as `fs.missing(...)`

2. Is the helper available under the active module/profile selection?

- `fs.get` exists, but `filesystem` is not enabled in `prism.json`

That is much better than folding both questions into a fake source-level import system.

## Alternatives Considered

### Option A: flat helper names in JSS

Source:

```js
fs_get(path)
json_decode(text)
```

Pros:

- matches existing strict PHP helper names exactly
- thin lowering path

Cons:

- less JS-friendly
- weaker grouping/discoverability as helper families grow

Assessment:

- good compatibility target
- not ideal as the primary JSS authoring surface

### Option B: reserved helper-family syntax

Source:

```js
let text: mixed = fs.get(path);
```

Pros:

- pleasant author ergonomics
- reads like modern JS/TS capability access
- keeps PascalCase free for real types/classes

Cons:

- needs a precise rule that `fs`, `io`, `json`, `dt` are reserved helper roots
- requires a documented lowering table

Assessment:

- preferred JSS direction
- should be treated as a narrow frontend helper-family surface, not a general runtime-object model

### Option C: namespaced helper symbols like `scpp.fs.get`

Pros:

- still static
- namespace-like visually

Cons:

- pushes odd backslash-helper expectations into PHS
- less natural than `fs.get(...)`
- blurs user namespaces with helper/capability roots

Assessment:

- worse than plain reserved helper roots for current goals

### Option D: JS-style `import ... from "scpp:fs"`

Pros:

- familiar to JS authors

Cons:

- wrong semantic association
- creates pressure for runtime module semantics
- duplicates the static capability model

Assessment:

- reject

## Proposed Rules

### Rule 1: modules do not create JS-style source imports

Enabling `filesystem` does not inject JavaScript-style module bindings into JSS source.

Instead it makes a documented helper surface available for JSS helper-family lowering and STAN validation.

### Rule 2: helper families and user namespaces are separate

- `App.Tools` is a user namespace
- `fs`, `io`, `json`, and `dt` are reserved helper roots
- helper roots are not ordinary user-declarable namespace names in JSS

### Rule 3: helper-family calls do not enable capabilities

Calling `fs.get(...)` does not mean “enable the filesystem module.”

It means “use the filesystem helper surface if the module is available.”

### Rule 4: project config enables capabilities, STAN validates them

If a project references `fs.get(...)` without the filesystem module enabled:

- parsing still succeeds
- helper-surface validation can still know the helper family/member pair
- STAN/project validation should report module-gated unavailability

### Rule 5: JSS should prefer helper families over flat helper globals

Preferred:

```js
let text: mixed = fs.get(path);
```

Avoid as the default JSS style:

```js
let text: mixed = fs_get(path);
```

That flat form may remain a compatibility spelling later, but it should not lead the JSS design.

## First Concrete Family Set

Recommended first reserved helper families and likely initial mappings:

- `fs`
  - `fs.get` -> `fs_get`
  - `fs.scan` -> `fs_scan`
- `io`
  - `io.open` -> `io_open`
  - `io.read_line` -> `io_read_line`
  - `io.read` -> `io_read`
  - `io.write` -> `io_write`
  - `io.close` -> `io_close`
- `json`
  - `json.decode` -> `json_decode`
  - `json.encode` -> `json_encode`
- `dt`
  - `dt.parse` -> `dt_parse`
  - `dt.format` -> `dt_format`
  - `dt.parse_iso_utc` -> `dt_parse_iso_utc`
  - `dt.format_iso_utc` -> `dt_format_iso_utc`

## Open Questions

1. How should STAN phrase diagnostics when a helper exists in a known module family but the module is not enabled?
2. Should helper families also expose class-like wrapper/result types under the same family root?
3. Should flat names like `fs_get`, `io_open`, `json_decode` remain accepted in JSS as compatibility aliases, or stay PHS-only?
4. Should `http` and `curl` both exist, or should one be the official JSS family once HTTP/curl policy settles?
5. Should reserved helper-family roots be blocked as user namespace names with an explicit parser/STAN diagnostic?

## Recommended Next Decision

Adopt this as the working policy:

- runtime modules are project-selected capabilities
- JSS accesses those capabilities through reserved helper families
- reject JS-style source module syntax
- prefer `fs.get(...)`, `json.decode(...)`, `io.open(...)`, and `dt.format(...)` as the reference direction for new JSS planning
- keep user namespaces separate from reserved helper roots

That decision would let the next planning work tackle:

- result-wrapper ergonomics
- filesystem/IO/JSS sample conversions
- module-gated STAN diagnostics

without reopening the helper-family surface each time.
