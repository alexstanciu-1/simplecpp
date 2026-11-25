# `json_node_size`
Doc Status: normative
Status: experimental
Module: json. Classification: feature runtime + strict source binding.
Reference target: JSON schema reading, not a PHP builtin compatibility alias.

## Signature and behavior

`json_node_size(json_node node): result<int>`

Returns array element count or distinct object-member count. Scalars are errors; empty containers return zero.

All node access requires a present handle. Wrong statically typed arguments are
rejected by the typed surface; runtime wrong-kind/index/conversion failures use
`result` errors. No accessor mutates a document. Returned views retain storage.

## Shared contracts and compatibility

The [document family contract](document.md) owns Unicode, numeric, duplicate-key,
error-category/offset, depth and lifetime rules, and the kept/modified/dropped
compatibility decisions. These rules apply here without PHP implicit coercion.
No OS-specific APIs are used; validation evidence is Linux native execution.
Allocation failures retain ordinary exceptions rather than parse/access errors.

## Ownership, configuration and validation

Enable `json` in `runtime.modules`. Strict registration lowers directly to
`scpp::json::node_size` from `modules/json/document.hpp`; `scpp_json` owns
`document.cpp`. No PHP runtime object behavior or new external dependency is
required. The family contract specifies shared lexical reading and both CLI
runtime composition paths.

Native coverage: `tests/runtime/native/test_json_document.cpp`.
Strict/STAN build/run: `tests/tools/test_scpp_json_document.py`, with fixtures
under `tests/tools/fixtures/json_document/`. The runtime catalog and declaration
ownership tests cover module, return, reference and handle signatures.
