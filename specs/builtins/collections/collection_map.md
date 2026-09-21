# Builtin contract: collection_map
Doc Status: normative
Status: experimental

`collection_map(input, callback)`

Maps each value once into fresh storage, preserving keyed input keys and producing dense sequence results.

The [collections family contract](../collections.md) defines the carrier-dependent
parameter/result types, callback discipline, iteration order, boxing, errors
and STAN instantiation for this builtin.

Implementation and validation: `runtime/include/scpp/collections.hpp`,
`tests/runtime/native/test_collections.cpp`,
`tests/tools/test_scpp_collection_typing.php` and `tests/tools/test_scpp_collections.py`.
