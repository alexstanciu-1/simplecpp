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

## Struct Fields And Cross-File Assignments

Use string and ordinary class fields directly, with typed containers for known
collections:

`model.jss`:

```js
class Some_Custom_Class {
	name: string = "";
}

struct Row {
	my_string: string;
	my_property: Some_Custom_Class;
	names: vector<string>;
	by_name: hash<Some_Custom_Class>;
}
```

The struct itself remains an inline value. Strings copy independently; ordinary
class fields use existing shared handles, so copies still reference the same
object. Vectors and hashes copy their contents using each element's existing
semantics: a container of class handles still shares the referenced objects.
Strings default to empty, vectors/hashes to empty containers, and class fields
to absent handles; assign a class object before dereferencing an absent field.

Container element/value types follow the permitted struct-field types
recursively, including strings, ordinary classes, nested structs, and supported
container compositions. Existing fixed arrays also accept these element types;
existing size and hash-key restrictions remain in force. This does not enable
all source types as struct fields: numeric eligibility remains bool and the
existing fixed-width integer aliases, not plain `int` or `float`.

`mixed` and `dynamic` remain rejected, including inside nested containers.
Nullable fields and explicit ownership-wrapper fields are not added. Strings,
class handles, and containers remain forbidden in union payloads, including
through nested structs. See `specs/compact_layout_types.md` for the contract.

JSS lowers through PHS and shares its current cross-file field-metadata
limitation. A direct container-literal assignment such as
`row.names = ["Alice", "Bob"];` can fail when `Row` is declared in another file.
Make the container type explicit at a local first:

`main.jss` (same project):

```js
let row: Row;
row.my_string = "team";
row.my_property = new Some_Custom_Class();

let names: vector<string> = ["Alice", "Bob"];
row.names = names;

let byName: hash<Some_Custom_Class> = {"owner": row.my_property};
row.by_name = byName;
```

This is a compiler limitation, not a language restriction or a requirement to
introduce locals for all assignments. Use JSS's canonical `hash<T>` target for
hash literals. Declare a struct local and assign its fields; do not use a
JavaScript-style object literal such as `let row: Row = {};` as a struct
constructor. Let project composition discover the other file; do not add JS
imports or generated C++ includes.

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
