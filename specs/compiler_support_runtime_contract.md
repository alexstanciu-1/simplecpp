# Compiler Support Runtime Contract

Status: implementation-backed contract

## Purpose

This document promotes the settled compiler-support runtime helpers from
planning notes into a stable contract surface for the Simple C++ compiler
prototype.

## Source Buffer And Byte Span

Simple C++ exposes `source_buffer` and `byte_span` as compiler-support runtime
handles.

`source_buffer` owns immutable source bytes. `byte_span` is a read-only view
into a `source_buffer`.

Supported helpers:

```text
source_buffer_empty(): source_buffer
source_buffer_take(string): source_buffer
source_buffer_release(source_buffer): string
source_buffer_byte_len(source_buffer): uint32
source_buffer_byte_at(source_buffer, int): uint8
source_buffer_span(source_buffer, int, int): byte_span
source_buffer_slice(source_buffer, int, int): string
byte_span_len(byte_span): uint32
byte_span_at(byte_span, int): uint8
byte_span_to_string(byte_span): string
```

Rules:

- offsets and lengths are byte based;
- `source_buffer_empty` returns an owned zero-length source buffer;
- source-language offsets and lengths accept normal `int` values and are
  range-checked to runtime storage width;
- `source_buffer_release` invalidates spans derived from that buffer;
- byte reads return `uint8` in compiler metadata, corresponding to strict
  source spelling `byte` where that alias is exposed.

## Source Line Index And Location

Simple C++ exposes `source_line_index` and `source_location` as
compiler-support runtime-backed source mapping values.

Supported helpers:

```text
source_line_index_build(source_buffer): source_line_index
source_line_index_line_count(source_line_index): uint32
source_line_index_offset_to_location(source_line_index, int): source_location
source_line_index_line_column_to_offset(source_line_index, int, int): uint32
source_location_offset(source_location): uint32
source_location_line(source_location): uint32
source_location_column(source_location): uint32
```

Rules:

- offsets and columns are byte based;
- lines and columns returned by `source_location_*` are 1-based;
- line/column-to-offset inputs use normal `int` values and are range-checked
  before conversion to runtime storage width;
- `source_location` stores offset, line, and column as `uint32` in the runtime;
- compiler metadata should treat both types as runtime-backed until a separate
  value-layout storage contract is accepted.

### v0.2 owning source-module admission

The opt-in `source` metadata module admits `source_buffer`,
`source_line_index`, and `source_location` as opaque, copyable runtime value
types, with the thirteen non-span helpers above. Copies are independent values,
not implicitly shared class handles. No public field layout, automatic Result
payload capability, or generic count/empty capability is exposed.

`source_buffer_take` takes a writable string reference and leaves it empty;
`source_buffer_release` takes a writable buffer reference and leaves it empty.
Slices return owning strings. Unary queries observe without retaining a reference;
multiargument reads use the ordinary readonly-reference boundary. These calls use
the existing stable-place and readonly admission rules.

A line index owns its mapping data, retaining no buffer borrow: it remains valid
after the source buffer is released or destroyed. CR, LF and CRLF delimit lines;
CRLF is one delimiter. EOF is an accepted offset. Reverse mapping preserves the
existing file-range check (it does not impose a separate same-line column bound);
arithmetic must not wrap to make an invalid column appear in range.

`byte_span`, span-producing/consuming calls, and builder span append remain
unavailable in this v0.2 catalog. The current runtime view retains a raw owner
pointer: generation checks cannot establish safety after owner destruction and
do not cover every replacement/move. Enabling these requires an agreed borrow
and invalidation model, not merely a copyable type row. This does not remove
the older provider API or assert that its existing lifetime limitations are solved.

## Stable Hash

Simple C++ exposes:

```text
hash_string(string): string
stable_hash_string_u64(string): uint64
stable_hash_bytes_u64(byte_span): uint64
```

`hash_string` remains the legacy/public 16-hex string helper.

`stable_hash_string_u64` and `stable_hash_bytes_u64` are the preferred helpers
for compiler-owned cache/model keys when the compiler can version the key
format itself. Byte-span and string hashing must agree for the same byte
sequence.

Consumers should prefix persisted key formats, for example:

```text
stable_hash_u64:v1:<uint64>
```

This helper is not a cryptographic security hash.

## String Builder Helpers

Simple C++ exposes `string_parts_builder` and `text_builder` as runtime-backed
builder handles for compiler and runtime text assembly.

`string_parts_builder` stores string parts and materializes a joined string on
demand. `text_builder` stores one contiguous byte buffer and can either
materialize a copy or move/take the accumulated string.

Supported helpers:

```text
string_parts_builder_create(): string_parts_builder
string_parts_builder_reserve(string_parts_builder, int): void
string_parts_builder_count(string_parts_builder): int
string_parts_builder_capacity(string_parts_builder): int
string_parts_builder_byte_len(string_parts_builder): int
string_parts_builder_append_string(string_parts_builder, string): void
string_parts_builder_append_int(string_parts_builder, int): void
string_parts_builder_append_bool(string_parts_builder, bool): void
string_parts_builder_to_string(string_parts_builder): string
string_parts_builder_clear(string_parts_builder): void
text_builder_create(): text_builder
text_builder_reserve_bytes(text_builder, int): void
text_builder_capacity_bytes(text_builder): int
text_builder_byte_len(text_builder): int
text_builder_append_string(text_builder, string): void
text_builder_append_int(text_builder, int): void
text_builder_append_bool(text_builder, bool): void
text_builder_append_byte_span(text_builder, byte_span): void
text_builder_to_string(text_builder): string
text_builder_take_string(text_builder): string
text_builder_clear(text_builder): void
```

Rules:

- reserve capacities accept normal `int` values and reject negatives at
  runtime;
- append helpers mutate the builder handle;
- boolean append follows PHP-style text output: true appends `"1"` and false
  appends an empty string;
- `text_builder_to_string` materializes a copy, while
  `text_builder_take_string` moves the current buffer into the returned string
  and clears the builder;
- compiler metadata should keep source consumption blocked until builder
  storage, mutation, lifetime, and source-call consumers are accepted.

### v0.2 metadata-backed builder calls

The v0.2 strings catalog admits both builder value types and the helper surface
above except `text_builder_append_byte_span`, whose source-view type remains a
separate prerequisite. These are runtime carrier values, not implicitly shared
source-class objects. Ordinary assignment and value parameters copy builder state;
later mutation of a copy does not change the original. Explicit source reference
aliases and reference parameters operate on the original storage.

Mutating helpers (reserve, append, clear and take-string) receive the builder by
writable reference. The remaining input, when present, uses the ordinary ordered
value-argument boundary. Queries observe the builder without mutation, copying it,
or retaining a reference; temporary query inputs live through the call. Returned
strings own their bytes independently. Mutation requires an admitted stable writable
place; temporary builders and read-only references cannot be mutation destinations.
The existing general reference/lifetime rules apply, with no builder-specific escape
policy. Capacity is a lower-bound reservation, not an exact allocation promise.

This admission uses module type/callable metadata only. It adds no public properties,
member-method syntax, data-literal initialization, automatic Result payload opt-in,
generic `count` or `empty` capability. Use the explicit builder queries. Existing
byte behavior and negative-capacity runtime errors remain unchanged. The older S2S
metadata's source-consumption flags are not a v0.2 registry support matrix.

## Native Compiler Helpers

The following native helpers are implementation-backed and may be used by
runtime/compiler internals before they become language-surface APIs:

- string parts builder;
- bitset helper;
- work queue helper;
- little-endian binary codec helpers;
- memory accounting helpers.

Language-surface exposure should be added only when a compiler-side use case
needs it and a strict-PHS test can lock the API.

## Memory Accounting

`memory_get_usage()` and `memory_get_peak_usage()` report process-level RSS
where the platform supports it. Native memory accounting helpers estimate
runtime-owned structures and are informational unless a caller adds an explicit
budget gate.
