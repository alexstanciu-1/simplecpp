# Builtin contract: keyed_filter
Doc Status: normative
Status: experimental

`keyed_filter(input, predicate)`

Accepts only hashes and table-valued mixed; preserves keys and order.
Dynamic handles are excluded. Native entry point: `scpp::collections::keyed_filter`.

The [collection family contract](../collections.md) owns the exact carrier,
callback, result, boxing, ownership and error rules. This adapter delegates to the
existing `filter` algorithm and adds a carrier constraint in native C++ and STAN;
it performs no conversion between carrier families.

Validation: `tests/runtime/native/test_collection_adapters.cpp`,
`tests/tools/test_scpp_collection_typing.php`, and `tests/tools/test_scpp_collections.py`.
