# Compiler Support Runtime Plan
Doc Status: planning

Date: 2026-06-27

Status: working design note

Purpose:
- capture the runtime/language improvements that would help the Simple C++
  compiler project without expanding the language surface unnecessarily
- keep the preferred direction small: strengthen core containers and add only
  primitives with distinct semantics

Authority note:
- this is planning material only
- if accepted, each item should be promoted into its owning language, runtime,
  generator, or STAN spec before becoming normative

## Design Principle

Prefer fewer, stronger building blocks:

- improve existing `hash_t` and `vector_t`
- add enums as a language feature because they carry type meaning
- add `source_buffer`/`byte_span` because source ownership and non-copying byte
  views are distinct from normal string operations
- build row arenas, builders, queues, and bitsets as library-level abstractions
  on top of the core containers where practical

Avoid creating many near-duplicate container types unless a later benchmark or
contract proves the need.

## Runtime Placement Policy

Keep core `runtime/include/scpp` small and reserved for:

- scalar wrappers
- ownership wrappers
- core containers
- result/null/error primitives
- traits needed by generated code

Use existing modules for clear runtime domains:

- `modules/strings` for string operations
- `modules/datetime` for clock and time APIs
- `modules/tokenizer` for PHS/JSS tokenization
- `modules/json` for JSON codecs
- `modules/filesystem` for filesystem APIs

Add new modules only when the concept is distinct from normal language
containers:

- `modules/source` for `source_buffer`, `byte_span`, and `source_line_index`
- `modules/binary` for little-endian binary artifact helpers

Start compiler-specific abstractions in compiler-side Simple C++ code:

- row arenas
- artifact builders
- dirty queues
- dependency graph helpers
- watcher storage helpers

These can later graduate into a runtime module only if they become broadly
useful outside the compiler.

## Placement Map

| Feature | First home | Reason |
| --- | --- | --- |
| fixed-width `hash_t` key support | core `scpp/support/hash_t.hpp` | core container behavior |
| `vector_t` capacity APIs | core `scpp/vector_t.hpp` and wrappers | core container behavior |
| fixed-width integer traits/helpers | core `scpp/int_t.hpp` / `scpp/detail.hpp` | type infrastructure |
| enums | generator/language/STAN, runtime helpers only where needed | syntax/lowering concern first |
| string byte/UTF-8/grapheme APIs | existing `modules/strings` | string domain |
| monotonic timers | existing `modules/datetime` | time domain |
| process memory usage | existing `operators/memory_usage` | process/runtime operator utility |
| container capacity/memory helpers | near owning containers | container-specific introspection |
| tokenizer and `token_buffer` | existing `modules/tokenizer` | tokenizer domain |
| `source_buffer`, `byte_span` | new `modules/source` | source ownership/view domain |
| `source_line_index` | new `modules/source` | source mapping domain |
| binary codec helpers | new `modules/binary` | binary artifact domain |
| row arena | compiler-side library first | compiler model/storage pattern |
| string parts builder | `modules/strings` if generic, compiler-side if artifact-specific | depends on final contract |
| bitset | compiler-side first, possible `modules/collections` later | prove shape before promotion |
| ring/work queue | compiler-side first | workload-specific |

## Agreed Direction

### 1. Generic `hash_t`

Make `hash_t<T_VALUE, T_KEY = string_t>` fully real.

Required compatibility:

- `hash<T>` continues to mean string-keyed `hash_t<T, string_t>`
- current `hash_t<mixed_t>` compatibility path remains supported
- existing string-key behavior must not regress

Target key families:

- `string_t`
- signed/unsigned integer aliases such as `int_t<>`, `uint32`, `byte`
- enum-backed keys once enums exist
- pointer/handle key families only after their identity rules are explicit

Related existing note:

- `specs/planning/hash_t_key_abstraction_task.md`

### 2. Enums Backed By Fixed-Width Integers

Enums should provide source-level type safety while lowering to compact integer
values.

Candidate surface:

```php
enum compact_node_kind: byte {
    source_unit = 1;
    param = 2;
    declaration_function = 10;
}
```

Development shape:

1. syntax and AST support
2. lowering to C++ `enum class`
3. explicit enum-to-backing and backing-to-enum conversions
4. equality and switch/match support
5. STAN checks for assignment, comparison, and conversion discipline
6. optional metadata helpers such as enum name/value lookup

Artifact rule:

- code may use enum types
- artifacts should store fixed-width numeric values

### 3. `hash_t` Audit Before Implementation

Before extending `hash_t`, audit:

- source syntax and type parser support for `hash<T_VALUE, T_KEY>`
- STAN generic handling
- generated C++ type output
- runtime `hash_t` templates and specialization paths
- supported operations: read/write `[]`, `isset`, delete, count, foreach,
  append behavior, and iteration
- key families currently working or missing
- compatibility assumptions around string-keyed hashes and `mixed_t`

The audit should produce the detailed implementation todo list.

### 4. `vector_t` Capacity Control

Add explicit capacity operations to the existing vector type.

Candidate surface:

```php
vector_reserve($items, 1024);
vector_capacity($items): int;
vector_clear($items): void;
vector_shrink_to_fit($items): void;
vector_compact($items): void;
```

Intent:

- reserve increases capacity without changing count
- clear resets count while retaining capacity
- shrink/compact releases unused capacity

### 5. Row Arena As A Library Abstraction

Do not start with a new core arena primitive.

Build `row_arena<T>` on top of `vector<T>` first.

Candidate shape:

```php
class row_arena<T> {
    public vector<T> $rows;
}
```

Candidate API:

```php
row_arena_reserve<T>(row_arena<T> $arena, int $capacity): void
row_arena_append<T>(row_arena<T> $arena, T $row): uint32
row_arena_get<T>(row_arena<T> $arena, uint32 $id): T
row_arena_set<T>(row_arena<T> $arena, uint32 $id, T $row): void
row_arena_count<T>(row_arena<T> $arena): uint32
row_arena_clear<T>(row_arena<T> $arena): void
```

Initial rules:

- ids are 1-based for artifact compatibility
- no delete/free-list/generation in v1
- later implementations may use `fixed_array`, chunks, or a custom allocator if
  measurements justify it

### 6. General Builders On Top Of `vector_t`

Avoid adding many builder primitives.

First improve `vector_t`, then add focused library builders:

- `string_parts_builder` over `vector<string_t>`
- later `byte_builder` over `vector<byte>` if byte appends become efficient

Candidate string helper:

```php
string_parts_builder_append($builder, "text");
string_parts_builder_append_int($builder, 123);
string_parts_builder_to_string($builder): string;
```

Each builder family can define its own `*_to_string` output.

### 7. High-Resolution Monotonic Timers

Add:

```php
dt_monotonic_us(): uint64
dt_monotonic_ns(): uint64
```

Microseconds are likely enough for compiler stage buckets. Nanoseconds are useful
as a low-level primitive but should not imply benchmark accuracy beyond the host
clock.

### 8. `source_buffer` And `byte_span`

`source_buffer` owns immutable source bytes.

`byte_span` is a read-only view into a `source_buffer`.

The source must remain read-only while spans exist.

Move helpers:

```php
source_buffer_take($text): source_buffer
source_buffer_release($buffer): string
```

Rules:

- `source_buffer_take($text)` moves string storage into the buffer when possible
- after take, `$text` becomes `""`
- `source_buffer_release($buffer)` moves storage back into a string when possible
- after release, the buffer is empty
- all spans from that buffer become invalid after release

Candidate API:

```php
source_buffer_byte_len($buffer): uint32
source_buffer_byte_at($buffer, uint32 $offset): byte
source_buffer_span($buffer, uint32 $offset, uint32 $length): byte_span
source_buffer_slice($buffer, uint32 $offset, uint32 $length): string
byte_span_len($span): uint32
byte_span_at($span, uint32 $offset): byte
byte_span_hash($span): uint64
byte_span_to_string($span): string
```

### 9. Runtime Tokenizer Typed Buffer

Make tokenizer output a stable typed buffer contract.

Target columns:

- kind: `byte` or enum-backed byte
- offset: `uint32`
- length: `uint32`
- line: `uint32`
- column: `uint32`
- flags: `uint16`

### 10. `source_line_index`

Build once per source buffer.

Shape:

```php
class source_line_index {
    public uint32 $source_byte_len = 0;
    public vector<uint32> $line_start_offsets = [];
}
```

Candidate API:

```php
source_line_index_build($buffer): source_line_index
source_line_index_line_count($index): uint32
source_line_index_offset_to_line_column($index, uint32 $offset): source_location
source_line_index_line_column_to_offset($index, uint32 $line, uint32 $column): uint32
```

Compiler-internal columns should be byte columns. User-visible grapheme columns
can be layered separately later.

### 11. Bitset

Implement as a library abstraction over `vector<uint64>`.

Use cases:

- dirty flags
- visited sets
- dependency traversal
- compact boolean state

### 12. Deque / Ring Queue

Implement a work queue abstraction without requiring front-removal from vectors.

Use cases:

- incremental dirty propagation
- dependency graph traversal
- resident task queues

### 13. Typed Binary Codec Helpers

Add low-level helpers for explicit binary artifact IO:

- write/read `uint8`, `uint16`, `uint32`, `uint64`
- write/read byte spans
- write/read length-prefixed strings

JSON remains useful for readable artifacts, but large compiler caches will need
a binary path.

### 14. Memory Accounting Helpers

Expose approximate memory accounting for core runtime structures:

- string byte capacity
- vector count/capacity/estimated bytes
- hash count/capacity/estimated bytes
- optional process allocator stats where available

These do not need perfect accounting in v1. They need stable trend visibility.

### 15. Stable Hash Helpers

Keep and extend:

```php
hash_string(string $value): uint64
hash_bytes(byte_span $span): uint64
```

Use cases:

- source fingerprints
- incremental model diffs
- byte-span interning
- artifact cache keys

## Reduced Surface Summary

Core runtime/language improvements:

1. `hash_t<T_VALUE, T_KEY = string_t>`
2. enum backed by fixed-width integer types
3. `vector_t` capacity operations
4. `source_buffer` and `byte_span`
5. high-resolution monotonic timers
6. binary/hash/byte helpers

Library-level abstractions:

1. `row_arena<T>` over `vector<T>`
2. `string_parts_builder` over `vector<string_t>`
3. `work_queue<T>` over vector/ring logic
4. `bitset` over `vector<uint64>`
5. `source_line_index` over `vector<uint32>`
