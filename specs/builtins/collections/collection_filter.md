# Builtin contract: collection_filter
Doc Status: normative
Status: experimental

`collection_filter(input, predicate)`

Copies selected values into fresh storage using an exactly bool-returning predicate.

The [collections family contract](../collections.md) defines the carrier-dependent
parameter/result types, callback discipline, iteration order, boxing, errors
and STAN instantiation for this builtin.

Implementation and validation: `runtime/include/scpp/collections.hpp`,
`tests/runtime/native/test_collections.cpp`,
`tests/tools/test_scpp_collection_typing.php` and `tests/tools/test_scpp_collections.py`.
