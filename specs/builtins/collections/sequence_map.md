# Builtin contract: sequence_map
Doc Status: normative
Status: experimental

`sequence_map(input, callback)`

Accepts only vectors and fixed arrays; returns a dense vector.
Dynamic handles are excluded. Native entry point: `scpp::collections::sequence_map`.

The [collection family contract](../collections.md) owns the exact carrier,
callback, result, boxing, ownership and error rules. This adapter delegates to the
existing `map` algorithm and adds a carrier constraint in native C++ and STAN;
it performs no conversion between carrier families.

Validation: `tests/runtime/native/test_collection_adapters.cpp`,
`tests/tools/test_scpp_collection_typing.php`, and `tests/tools/test_scpp_collections.py`.
