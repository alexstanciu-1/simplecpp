# Typed data initialization

Doc Status: normative

## Construction targets

A data initializer requires a known construction target. An ordered list supplies positional entries. A keyed initializer supplies key/value pairs. Type checking applies to each entry and recursively uses the declared target at nested construction sites. A keyed input is evaluated in source order, with the key and its conversion completed before its value. Duplicate detection for a materialized hash input happens after input evaluation. A failed construction cannot publish a partial destination.

Record construction follows declared fields, constructor inputs and defaults. Readonly stored values retain their initialization and subsequent mutation restrictions. Empty notation and nested literals require admission for their destination type. Mixed and dynamic have distinct storage and do not acquire an implicit cross-family conversion through nesting.

See [keyed local initialization](../compiler/docs/keyed_initialization.md), [list initialization](../compiler/docs/list_initialization.md), and [compact records](compact_layout_types.md) for the admitted operations.
