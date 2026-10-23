# JSON builtin contracts
Doc Status: normative
This folder contains one contract file per JSON builtin in the first-pass Prism++ / Simple C++ JSON surface.

## Current first-pass coverage

The [v0.2 unique-name rule](json_decode.md#unique-object-names--2026-10-06) applies
to all JSON parsing, not only compiler metadata: duplicate object names reject.

- `json_decode`
- `json_encode`

See also: `specs/builtins/json/first_pass.md`.

## Shape-preserving document API

The [immutable document contract](document.md) provides checked schema reading
without changing `json_decode`'s dynamic-family return. Documents preserve array
versus object shape, exact keys and number tokens. Retained node views share
immutable storage and outlive their originating document handle.

`json_document_parse`, `json_document_root` and the `json_node_*` accessors
belong to the JSON module. Duplicate decoded keys fail with `duplicate_key`,
not last-value-wins. There is no mutation or serialization API in this slice.

## Typed pull reading

The [pull-reader protocol](pull_reader.md) supports forward-only typed consumers.
[json_pull_reader_create](json_pull_reader_create.md) allocates its shared handle;
`start` initializes owned input once. Handle copies share cursor state.
