# JSS Is Not JavaScript Compatibility

Use this note when translating expectations from JavaScript-like syntax into JSS.

## Positioning

JSS is a typed, script-looking compiled language surface.

It borrows ergonomic spellings where they map cleanly into Prism++ / PHS. It does not mimic JavaScript runtime semantics.

## Do Not Assume

Do not assume:

- loose truthiness everywhere
- `undefined`
- loose equality coercion
- prototype objects
- dynamic object bags by default
- automatic missing-property success
- browser or Node.js APIs
- JS `import` / `export`
- promises / async / await
- destructuring
- spread/rest
- all arrays/objects/strings behaving like JavaScript

## Prefer

Prefer:

- explicit types
- explicit nullable `?T`
- explicit dynamic boundaries
- typed `vector<T>` and `hash<T>`
- classes with known properties and methods
- `take(...)` for wrappers
- reserved helper families for runtime APIs
- STAN/PHS/runtime ownership for semantic truth

## Examples

JavaScript-shaped expectation:

```js
if (value) {
	doThing();
}
```

JSS direction:

```js
if (value !== null) {
	doThing();
}
```

JavaScript object-bag expectation:

```js
user.name = "alex";
```

JSS direction:

```js
class User {
	name: string = "";
}

let user: User = new User();
user.name = "alex";
```

Dynamic JSON boundary:

```js
let row: dynamic = json.decode(text);
let name: string = row["name"];
```
