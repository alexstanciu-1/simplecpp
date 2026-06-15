# JSS Authoring Rules

Use these rules when writing or reviewing JSS v1-alpha source.

## Mental Model

JSS is a typed script-style frontend that lowers to PHS. It is not JavaScript compatibility.

Preferred project shape:

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

## File Shape

Preferred:

```js
print("hello\n");
```

Avoid:

```php
<?php
declare(strict_types=1);
```

Avoid:

```js
import fs from "fs";
export function main() {}
```

JSS project composition is owned by `scpp build` and `prism.json`, not JS modules.

## Types

Prefer explicit types:

```js
let count: int = 0;
let names: vector<string> = [];
let scores: hash<int> = {"a": 1};
let maybe: ?string = null;
```

Use `dynamic` / `mixed` only at explicit dynamic boundaries:

```js
let row: dynamic = json.decode(text);
let count: int = row["count"];
```

## Functions And Arrows

Functions require explicit parameter and return types:

```js
function add(left: int, right: int): int {
	return left + right;
}
```

Arrows are narrow local expression-body values:

```js
let addOne = (x: int): int => x + 1;
```

Do not promise broad JavaScript callback inference or closure ergonomics beyond the PHS target surface.

## Classes

Use public class surface by default:

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
```

`private`, `protected`, and ES `#private` are not part of the current implemented JSS class surface.

## Helpers

Use reserved helper families:

```js
fs.get(path)
fs.put(path, text)
io.open(path, "rb")
json.decode(text)
json.encode(value)
dt.format(ts, "Y-m-d")
```

Do not create user namespaces named `fs`, `io`, `json`, or `dt`.

## Wrappers

Use `take(...)` explicitly:

```js
let text: string = "";
let err: error;

if (!take(text, err, fs.get(path))) {
	print("read failed\n");
	return;
}
```

Do not treat wrappers as JavaScript-truthy values.

## Mutation

Vector append:

```js
items.push(value);
```

Hash update:

```js
scores["a"] = 1;
```

Key removal:

```js
delete scores["a"];
```

This lowers to PHS `unset(...)`; it is not full JavaScript `delete`.

## References

Use explicit `&` only:

```js
let alias = &value;
alias = &other;
```

Only simple identifier reference targets are in the first slice.

## Optional Chaining

`object?.member` currently lowers to PHS `?->`, but broad build/run waits on downstream PHS nullsafe result typing.

Do not add a JSS-local workaround for nullsafe result typing.
