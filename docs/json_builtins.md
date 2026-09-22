# JSON builtins â€” first pass
Doc Status: supporting
This page summarizes the first-pass Prism++ / Simple C++ JSON builtin surface.

JSON is now runtime-owned and lives under `namespace scpp::json`. The PHP layer keeps only a thin wrapper in `namespace scpp::php`.

## Header split

JSON support is intentionally kept out of the generic `php.cpp` / `php.hpp` area.
Use the dedicated header instead:

- runtime module header: `runtime/include/modules/json/json.hpp`
- PHP wrapper header: `runtime/include/lang/php/php_json.hpp`

## First-pass contract shape

- `json_decode()` parses one full JSON document into `result<mixed>`
- decoded scalars stay inline in `mixed_t`
- decoded arrays and objects both become `dynamic_t`
- packed `hash_t` encodes as a JSON array
- non-packed `hash_t` encodes as a JSON object
- object-vs-array differentiation follows the same internal model as hand-written Prism++ / Simple C++ code
- invalid JSON returns an `error` with a byte position; `take($data, $err, json_decode($text))` returns `false` and allows the next request to continue
- `json_encode()` returns `result<string>`; use `take` to handle weak-table or non-finite-float errors without partial output

Strict-mode guidance:

- declare `$data mixed;` and `$err error;`, then unwrap `json_decode()` with `take` at the ingestion boundary
- decoded JSON is normal broad/dynamic data at that edge, not the preferred interior representation for strict business logic
- when the expected payload shape is known, document it locally near the decode boundary
- stabilize early into typed locals, typed properties, typed objects, or typed containers
- avoid carrying decoded dynamic state deep into the program unless the flexibility is intentionally needed

## Implemented JSON functions

- `json_decode`
- `json_encode`

## Migrating from 0.1.74

This is a breaking change for both strict and legacy PHP++ profiles, and for
JSS. `json_decode` / `json.decode` now return `result<mixed>` instead of a direct
dynamic value. `json_encode` / `json.encode` return `result<string>` instead of
a direct string. Unwrap each operation before reading fields, printing, or
passing its value to another helper.

PHP++ / PHS:

```php
function migrate(): void {
	$text string = "{\"name\":\"Ada\"}";
	$row mixed;
	$encoded string = "";
	$err error;
	if (!take($row, $err, json_decode($text))) {
		echo $err->get_message(), "\n";
		return;
	}
	if (!take($encoded, $err, json_encode($row))) {
		echo $err->get_message(), "\n";
		return;
	}
	echo $encoded, "\n";
}
migrate();
```

JSS:

```js
function migrate(): void {
	let text: string = "{\"name\":\"Ada\"}";
	let row: mixed;
	let encoded: string = "";
	let err: error;
	if (!take(row, err, json.decode(text))) {
		print(err.get_message(), "\n");
		return;
	}
	if (!take(encoded, err, json.encode(row))) {
		print(err.get_message(), "\n");
		return;
	}
	print(encoded, "\n");
}
migrate();
```

Malformed JSON and unsupported encoding values return the error branch;
`catch (Exception ...)` is not the recovery boundary for these failures.
Valid JSON `null` and `false` are successful decoded values, so `take` returns
`true` for both. Failed extraction leaves the output local unchanged and fills
the error. Typed field conversion after a successful decode retains its own
runtime failure contract; successful parsing does not validate a payload schema.

Rebuild runtime artifacts after updating the toolchain:

```bash
scpp run --force
```

Repeat the rebuild for each consuming project, profile, and build mode you
ship. For release-mode projects, use `scpp run --mode=release --force`.
Native and packaged deployments must rebuild and deploy matching runtime and
module artifacts. Native callers must rebuild against the checked `decode` /
`encode` symbols and extract their results. An old runtime artifact cannot
satisfy those new symbols with the former incompatible return ABI.

The normative details are in [json_decode](../specs/builtins/json/json_decode.md)
and [json_encode](../specs/builtins/json/json_encode.md).

## More detailed contracts

For one-file-per-builtin contracts, see `specs/builtins/json/`.

## Shape-preserving document reader (strict)

Use `json_document_parse` when a schema needs to distinguish objects from arrays
and preserve exact object keys before creating typed records. Enable the `json`
runtime module. The existing `json_decode` still uses its table-oriented mapping.

```php
$document json_document;
$diagnostic json_parse_error = null;
$error error;
if (!take($document, $error, json_document_parse($text, $diagnostic))) {
    echo $diagnostic->category, ":", $diagnostic->byte_offset, "\n";
    return;
}
$root json_node;
if (!take($root, $error, json_document_root($document))) { return; }
$kind string;
if (!take($kind, $error, json_node_kind($root))) { return; }
echo $kind, "\n";
```

`json_node_size` plus `json_node_at` traverses arrays; size plus `json_node_key`
and `json_node_member` traverses objects in first-insertion key order. Duplicate
keys replace the value at that original position. `json_node_has` distinguishes
a missing member from present null. Keys retain exact decoded bytes. Nodes keep
the document alive and provide no mutation API.

`json_node_number` returns the JSON token; `json_node_int` checks integer spelling
and signed 64-bit range. It rejects fractions, exponent spellings and overflow.
Default maximum nesting is 128 containers, configurable from 1 to 256. UTF-8 and
surrogates are validated; errors have categories and zero-based byte offsets.
See [the complete contract](../specs/builtins/json/document.md) and
`tests/tools/fixtures/json_document/` for manifest validation and lifetime examples.
