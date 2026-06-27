# Compiler Support Runtime Implementation Audit
Doc Status: planning

Date: 2026-06-27

Status: implementation audit

Purpose:
- check the compiler-support runtime backlog against the current Simple C++
  implementation before starting feature work
- separate already-implemented foundations from real gaps
- avoid adding duplicate runtime concepts where a smaller extension is enough

Authority note:
- this document is a planning/audit note only
- accepted behavior still needs to be promoted into the owning runtime,
  language, generator, STAN, or module specs

## Summary

Several requested foundations already exist:

- `hash_t<T_VALUE, T_KEY = string_t>` is already declared as the generic shape.
- The generic `hash_t<T_VALUE, T_KEY>` stores explicit typed keys separately from
  the dynamic `hash_t<mixed_t, mixed_t>` compatibility path.
- PHS/JSS runtime tokenizers already return a typed `token_buffer`.
- Simple enums already lower to native C++ `enum class` for the current narrow
  PHP-compatible enum subset.
- Fixed-width integer aliases already lower to `int_t<Rep>`.
- Explicit string byte/UTF-8/grapheme APIs already exist.

The next work should therefore close gaps, not restart these systems.

Most valuable near-term gaps:

1. complete typed-hash key support for fixed-width integer keys and append
   behavior
2. add vector capacity APIs
3. document/promote the typed tokenizer buffer contract and compact its columns
4. design `source_buffer`/`byte_span` carefully because current strings do not
   expose move-out/lifetime primitives
5. add high-resolution monotonic timers and container memory-capacity helpers

## Placement Audit

The implementation should not place every compiler-support feature in the main
runtime core.

Core runtime placement is appropriate for:

- `hash_t` fixed-width key support
- `vector_t` capacity helpers
- fixed-width integer traits/helpers
- container-owned memory/capacity estimates

Existing module placement is appropriate for:

- string byte/UTF-8/grapheme helpers in `modules/strings`
- high-resolution timers in `modules/datetime`
- tokenizer and typed `token_buffer` in `modules/tokenizer`
- process memory probes in `operators/memory_usage`

New module placement is appropriate for:

- `modules/source`: `source_buffer`, `byte_span`, and `source_line_index`
- `modules/binary`: little-endian binary codec helpers

Compiler-side placement is appropriate first for:

- row arenas
- artifact builders
- bitsets
- ring/work queues
- dependency graph helpers
- watcher storage helpers

Reasoning:

- core `scpp/` should stay focused on types generated code needs everywhere
- modules should host domain services
- compiler-side libraries let us measure and revise shapes before promoting them
  into the public runtime

## 1. Generic `hash_t`

Current implementation:

- `runtime/include/scpp/detail.hpp` forward declares:
  `hash_t<T_VALUE = mixed_t, T_KEY = default_hash_key<T_VALUE>::type>`.
- `default_hash_key<T>` is `string_t`; `default_hash_key<mixed_t>` is `mixed_t`.
- `runtime/include/scpp/support/hash_t.hpp` implements generic
  `hash_t<T_VALUE, T_KEY>` separately from the dynamic
  `hash_t<mixed_t, mixed_t>` specialization.
- The generic implementation stores:
  - `std::vector<T_KEY> keys_`
  - `std::vector<T_VALUE> values_`
  - `std::vector<std::uint8_t> live_`
  - a compact flat hash index with `uint8`, `uint16`, or `uint32` bucket indexes
- Explicit key identity is therefore already distinct from dynamic mixed key
  compatibility.
- `hash<T>` currently lowers to `hash_t<T>` and uses the string-key default.
- `hash<T, T_KEY>` currently lowers to `hash_t<T, T_KEY>`.

Current key support:

- `string_t`
- default `int_t<>`
- `shared_p<T>`
- `unique_p<T>`
- `weak_p<T>`

Likely missing:

- generic `key_ops<int_t<Rep>>` for fixed-width aliases such as `uint32`,
  `uint16`, `byte`, and `int16`
- tests proving `hash<int, uint32>` and `hash<int, byte>` work end-to-end
- diagnostics for unsupported key families at source/STAN level

Append status:

- generic typed `append()` currently works only when `T_KEY` is exactly
  `int_t<>`
- `hash_t<mixed_t, mixed_t>` has its own dynamic append behavior
- `hash<T>` with default string keys does not support append, which is
  consistent with the current runtime error

Open design point before implementation:

- For `hash<T, uint32>` or `hash<T, byte>`, append can be extended to all
  `int_t<Rep>` key families, but the return type should be chosen deliberately.
  Returning `T_KEY` preserves the explicit key family. Returning `int_t<>`
  preserves the current generic append signature. The compiler project likely
  benefits from returning `T_KEY`, but this is a language-surface compatibility
  decision.

Recommended first slice:

- add a generic `key_ops<int_t<Rep>>`
- add compile/runtime tests for `hash<int>`, `hash<int, int>`,
  `hash<int, uint32>`, and `hash<int, byte>`
- keep dynamic `hash_t<mixed_t, mixed_t>` untouched

## 2. Enums

Current implementation:

- simple unit enums lower to native C++ `enum class`
- int-backed enums with literal integer case values lower to native C++
  `enum class` with explicit values
- storage is compacted to the smallest fitting integer type
- enum case name helpers exist through `scpp::php::enum_name`

Current documented limits:

- string-backed enums are not supported
- enum methods/interfaces are not supported
- `cases()`, `from()`, and `tryFrom()` are not supported
- enum pseudo-properties other than the current name helper are not complete

Syntax note:

- current enum support follows the PHP-style case surface, for example
  `case Hearts;`
- the proposed compact compiler enum shape must respect semicolon-style cases
  unless a separate Simple C++ enum syntax is accepted

Recommended first slice:

- audit existing enum tests and parser coverage
- decide whether compiler-focused fixed-width enums reuse PHP enum syntax or get
  a stricter Simple C++ subset
- add explicit enum-to-backing and backing-to-enum helper design before using
  enums as artifact numeric fields

## 3. Fixed-Width Integers

Current implementation:

- `int_t<Rep>` is the core integer wrapper
- fixed-width aliases lower to `int_t<std::uint8_t>`,
  `int_t<std::uint16_t>`, `int_t<std::uint32_t>`,
  `int_t<std::int16_t>`, and related reps
- STAN and the type mapper already know several fixed-width aliases

Likely missing:

- full container/operator coverage for every fixed-width alias
- generic hash key support for all `int_t<Rep>` families
- focused tests for fixed-width types as compiler artifact ids and column values

Recommended first slice:

- do not add new integer wrapper types
- extend generic traits/helpers around `int_t<Rep>` where the runtime currently
  special-cases only `int_t<>`

## 4. `vector_t` Capacity Control

Current implementation:

- `vector_t<T>` supports size, empty, clear, checked access, remove, append,
  push_back, and native access
- `clear()` preserves capacity because it delegates to `std::vector::clear()`
- there is no public reserve/capacity/compact API

Missing:

- `vector_reserve`
- `vector_capacity`
- `vector_compact`
- optional explicit compact target/threshold argument

Accepted direction:

- `vector_compact($v)` should shrink unused capacity
- `vector_compact($v, $capacity)` should allow an explicit compact target when
  that target is valid for the current size

Recommended first slice:

- add runtime helpers over existing `vector_t<T>::native_value()`
- add typed-vector tests for capacity behavior

## 5. Row Arena

Current implementation:

- no dedicated row arena runtime type was found
- `vector_t<T>` is the right base once capacity helpers exist

Recommended first slice:

- implement `row_arena<T>` as a library-level Simple C++ abstraction over
  `vector<T>`
- keep ids compact and explicit, likely `uint32`, after fixed-width coverage is
  tested
- avoid a custom allocator until measurements prove it is needed

## 6. Builders

Current implementation:

- no general builder abstraction was found
- strings already have explicit byte APIs, but repeated string construction
  still needs a compiler-friendly pattern

Recommended first slice:

- build `string_parts_builder` over `vector<string_t>`
- add reserve support after vector capacity APIs land
- avoid a new core data type until benchmark evidence requires it

## 7. High-Resolution Monotonic Timers

Current implementation:

- datetime module exposes `now_unix_seconds`, `now_unix_millis`,
  `monotonic_millis`, and `sleep_millis`
- PHP layer has millisecond monotonic helpers

Missing:

- `dt_monotonic_us(): uint64`
- `dt_monotonic_ns(): uint64`

Recommended first slice:

- add microsecond timer first
- add nanosecond timer as a raw primitive with documented host-clock caveats

## 8. `source_buffer` And `byte_span`

Current implementation:

- no public `source_buffer` or `byte_span` runtime type was found
- `string_t` owns a `std::string` and exposes read-only native access
- tokenizer internals use `std::string_view` over `string_t.native_value()`
- there is no public take/release API for moving string storage out of
  `string_t`

Accepted direction:

- `source_buffer_take($text): source_buffer`
- `source_buffer_release($buffer): string`
- if the source string is read-only, take may reference instead of moving
- if the source string is mutable/owned, take should move data and leave the
  source string empty
- source buffers are read-only after creation
- no mutation API is exposed except release
- using spans after release is a runtime error

Risks:

- Simple C++ currently needs clearer by-reference/read-only/move semantics for a
  helper that mutates `$text` to `""`
- span invalidation needs a generation/owner validity check, not a raw pointer
  view

Recommended first slice:

- write the source ownership contract before implementation
- implement a conservative owning `source_buffer` first
- add move/take optimization only after the source-level by-reference contract is
  proven

## 9. Runtime Tokenizer Typed Buffer

Current implementation:

- `runtime/include/modules/tokenizer/tokenizer.hpp` already defines
  `token_buffer`
- `phs_tokenize_buffer` and `jss_tokenize_buffer` return `shared_p<token_buffer>`
- `phs_tokenize` and `jss_tokenize` now return typed buffers
- `token_buffer_to_mixed` remains as an adapter for readable/legacy output

Current columns:

- `kind_ids`: `int64`
- `start_offsets`: `size_t`
- `lengths`: `size_t`
- `line_numbers`: `int64`
- `columns`: `int64`
- `flags`: `int64`
- `line_start_offsets`: `size_t`

Missing:

- runtime contract/spec for typed token-buffer columns
- fixed-width column choices such as `byte`/`uint32`/`uint16` where safe
- richer accessor API if parser stages should avoid direct mixed conversion
- tokenizer diagnostics contract

Recommended first slice:

- document the typed buffer as the production API
- keep `mixed` conversion strictly as an adapter
- compact column types after deciding maximum file-size/token-count assumptions

## 10. `source_line_index`

Current implementation:

- tokenizer stores line-start offsets internally
- no reusable `source_line_index` runtime type was found

Recommended first slice:

- build `source_line_index` from `source_buffer`
- use byte offsets/columns internally
- add conversion helpers for diagnostics and future debugger/editor queries

## 11. Bitset

Current implementation:

- no dedicated bitset library was found

Recommended first slice:

- implement over `vector<uint64>` first
- add set/clear/test/count only as needed by dirty queues and dependency walks

## 12. Deque / Ring Work Queue

Current implementation:

- no dedicated work-queue abstraction was found

Recommended first slice:

- implement a ring queue over vector storage
- keep clear-with-capacity-retention behavior explicit

## 13. Binary Codec

Current implementation:

- no compiler-facing binary artifact codec was found in the runtime audit

Accepted direction:

- little-endian only
- explicitly documented

Recommended first slice:

- add narrow helpers for writing/reading unsigned fixed-width integers
- keep schema evolution outside the low-level codec

## 14. Memory Accounting

Current implementation:

- `memory_get_usage()` and `memory_get_peak_usage()` report process-level RSS
  and peak RSS when available

Missing:

- container-level capacity/memory approximation
- string/vector/hash capacity helpers for compiler memory probes

Recommended first slice:

- add approximate capacity-byte helpers for `vector_t`, `hash_t`, and `string_t`
- keep process RSS helpers as benchmark-level measurements

## 15. Hashing

Current implementation:

- `hash_string($s)` exists in the PHP support layer
- it returns a hex `string_t` generated from the runtime string key hash

Missing:

- numeric hash helper for compiler ids, likely `uint64`
- `hash_bytes(byte_span)` once `byte_span` exists
- explicit stability contract for hashes stored in persistent compiler artifacts

Recommended first slice:

- decide whether persistent compiler fingerprints need stable algorithm naming
- avoid using an implementation-detail hash for on-disk cache identity without a
  versioned contract

## Implementation Order Adjustment

Start with `hash_t`, but keep the task scoped:

1. fixed-width key support
2. typed hash tests
3. append semantics decision and implementation
4. vector capacity helpers
5. tokenizer typed-buffer contract/column compaction
6. source buffer contract

This keeps the branch moving while avoiding risky lifetime work before the
source-level ownership contract is explicit.
