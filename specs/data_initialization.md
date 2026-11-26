# Typed data initialization

Doc Status: normative

## Construction targets

A data initializer requires a known construction target. An ordered list supplies positional entries. A keyed initializer supplies key/value pairs. Type checking applies to each entry and recursively uses the declared target at nested construction sites. A keyed input is evaluated in source order, with the key and its conversion completed before its value. Duplicate detection for a materialized hash input happens after input evaluation. A failed construction cannot publish a partial destination.

Record construction follows declared fields, constructor inputs and defaults. Readonly stored values retain their initialization and subsequent mutation restrictions. Empty notation and nested literals require admission for their destination type. Mixed and dynamic have distinct storage and do not acquire an implicit cross-family conversion through nesting.

See [keyed local initialization](../compiler/docs/keyed_initialization.md), [list initialization](../compiler/docs/list_initialization.md), and [compact records](compact_layout_types.md) for the admitted operations.

## Named construction of data records

A `data class` or `data struct` accepts a keyed literal wherever preparation supplies that record as the destination type. Literal keys name constructor inputs. Their values retain their source types and can themselves receive a record or collection type from the corresponding input.

```php
data struct Packet {
    public vector<int> $data;
    public int $version = 1;
}

$packet Packet = ["data" => $items];
$other Packet = {version: 2, data: $items};
```

An authored constructor supplies the parameter names, types, visibility and defaults, including when inherited. Its body runs once. Otherwise the compiler derives a named signature from public instance fields in declaration order, starting with the data base's inputs. Each omitted input uses its field's declared or ordinary type default. A readonly field without a declared default, an enum without a case default, a required owner, or an embedded record that requires constructor inputs must be supplied. Inline fixed arrays cannot be passed as generated constructor inputs. A non-data base requires an authored constructor for data initialization.

The ordinary named-call binder diagnoses unknown, duplicate and missing required inputs. Keys must be literal names. Explicit values evaluate once in written order, even when it differs from parameter order. Construction completes before the destination changes. A generated field signature admits keyed initialization only. Ordinary class ownership and struct value semantics apply to the result.

## Native codec adapters for field-derived signatures

The generated payload publishes the same ordered public fields to native input and output adapters. An output visitor borrows each field for one synchronous call. An input reader consumes fields in wire order into typed local slots, checks required fields, supplies defaults in declaration order, and invokes the selected payload or owner factory once. The transport owns document completion and publication. An output sink can receive a prefix before a later field fails.

The internal JSON bridge uses the structural pull reader and a synchronous byte sink. The codec supplies typed value decoding and encoding, including format-specific validation. For a vector field, the schema publishes its count, checked element read and typed append operations. The JSON adapter borrows the vector on output, writes its elements in order, and delegates element encoding to the codec. On input it appends decoded elements to a private vector and moves the completed value into the record constructor. A failed element read abandons the private input, preserving the previous destination. This native adapter applies to field-derived signatures. With the JSON module selected, source code can pass a field-derived data struct or class to `json_encode`. Preparation checks each public input field, including inherited inputs, against the active JSON encoders. For a vector it checks the element type. An unsupported field produces a diagnostic naming the record and field. Encoding borrows the record, emits fields in schema order into one writer, and returns the ordinary `result<string>`. An absent owner encodes as JSON null. Authored constructor mappings and source-level typed decoding remain open.
