# JSON builtin contracts
Doc Status: normative
This folder contains one contract file per JSON builtin in the first-pass Prism++ / Simple C++ JSON surface.

## Current first-pass coverage

- `json_decode`
- `json_encode`

See also: `specs/builtins/json/first_pass.md`.

## Shape-preserving schema reader

The strict [document API](document.md) preserves object/array kinds, exact keys,
raw number tokens and immutable node lifetimes independently of `json_decode`.

- [json_document_parse](json_document_parse.md)
- [json_document_root](json_document_root.md)
- [json_node_kind](json_node_kind.md)
- [json_node_size](json_node_size.md)
- [json_node_at](json_node_at.md)
- [json_node_key](json_node_key.md)
- [json_node_has](json_node_has.md)
- [json_node_member](json_node_member.md)
- [json_node_string](json_node_string.md)
- [json_node_boolean](json_node_boolean.md)
- [json_node_number](json_node_number.md)
- [json_node_int](json_node_int.md)
