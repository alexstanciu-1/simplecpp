# Compiler Support Runtime Backlog
Doc Status: planning

Date: 2026-06-27

Status: todo backlog

Purpose:
- order implementation work for the compiler-support runtime/language plan
- keep the first implementation passes small, measurable, and compatible

Related plan:

- `specs/planning/compiler_support_runtime_plan_2026_06_27.md`

## Priority 1: Generic `hash_t<T_VALUE, T_KEY = string_t>`

Value:
- unlocks efficient compiler maps keyed by integer ids, enums, and strings
- fixes the current limitation observed when trying id-keyed compiler caches

Tasks:

- [ ] Audit current source syntax support for `hash<T_VALUE, T_KEY>`.
- [ ] Audit STAN handling for typed hash key parameters.
- [ ] Audit generated C++ for one- and two-parameter hash declarations.
- [ ] Audit runtime `hash_t` template shape and specializations.
- [ ] Audit existing operations: `[]`, `isset`, write, delete, count, foreach,
      append, and iteration.
- [ ] Audit key families currently supported by implementation.
- [ ] Write focused tests for `hash<int>` compatibility.
- [ ] Write focused tests for `hash<int, int>`.
- [ ] Write focused tests for `hash<int, uint32>`.
- [ ] Write focused tests for `hash<int, byte>`.
- [ ] Implement explicit key hashing/equality policy.
- [ ] Preserve current `hash<T>` string-key default behavior.
- [ ] Preserve current `hash<mixed>` dynamic compatibility behavior.
- [ ] Add clear diagnostics for unsupported key types.
- [ ] Update docs/specs after behavior is stable.

## Priority 2: Enums Backed By Fixed-Width Integers

Value:
- makes token/node/operator kinds type-safe without losing compact storage

Tasks:

- [ ] Define enum grammar with required backing type.
- [ ] Add parser/AST support for enum declarations.
- [ ] Add duplicate-name and duplicate-value diagnostics.
- [ ] Lower enum declarations to C++ `enum class`.
- [ ] Support enum member references such as `kind::value`.
- [ ] Support equality for same enum type.
- [ ] Reject implicit assignment from raw integer to enum.
- [ ] Add explicit enum-to-backing conversion helper.
- [ ] Add explicit backing-to-enum conversion helper.
- [ ] Add STAN checks for enum assignment and comparison discipline.
- [ ] Add tests for byte-backed enum.
- [ ] Add tests for uint16-backed enum.
- [ ] Add tests for enum as `hash_t` key after generic hash support lands.
- [ ] Add optional enum name/value metadata helpers after the base feature is
      stable.

## Priority 3: `hash_t` Todo From Audit

Value:
- keeps generic hash implementation honest and prevents drift between syntax,
  STAN, generator, and runtime

Tasks:

- [ ] Convert the Priority 1 audit into a detailed implementation checklist.
- [ ] Split checklist by owner: language, STAN, generator, runtime, tests, docs.
- [ ] Mark compatibility blockers separately from performance improvements.
- [ ] Add a narrow migration note for existing string-keyed code.
- [ ] Add benchmark probes for string-key and int-key hash use.

## Priority 4: `vector_t` Capacity Operations

Value:
- lets compiler stages reserve known row counts and reuse resident storage

Tasks:

- [ ] Add `vector_reserve`.
- [ ] Add `vector_capacity`.
- [ ] Add `vector_clear` that preserves capacity.
- [ ] Add `vector_shrink_to_fit` or `vector_compact`.
- [ ] Decide whether both `shrink_to_fit` and `compact` are needed or one alias
      is enough.
- [ ] Add typed-vector tests.
- [ ] Add fixed-width vector tests.
- [ ] Add docs for count vs capacity.

## Priority 5: `source_buffer` And `byte_span`

Value:
- avoids duplicate source copies and enables safe non-copying tokenizer/parser
  views

Tasks:

- [ ] Define `source_buffer` ownership contract.
- [ ] Define `byte_span` view contract.
- [ ] Implement `source_buffer_take($text): source_buffer`.
- [ ] Ensure `source_buffer_take` leaves `$text` as `""`.
- [ ] Implement `source_buffer_release($buffer): string`.
- [ ] Ensure release empties the buffer.
- [ ] Document that release invalidates all spans.
- [ ] Add `source_buffer_byte_len`.
- [ ] Add `source_buffer_byte_at`.
- [ ] Add `source_buffer_span`.
- [ ] Add `source_buffer_slice`.
- [ ] Add `byte_span_len`.
- [ ] Add `byte_span_at`.
- [ ] Add `byte_span_to_string`.
- [ ] Add `hash_bytes(byte_span)`.
- [ ] Add lifetime and mutation tests.

## Priority 6: High-Resolution Monotonic Timers

Value:
- improves compiler profiling fidelity without coarse millisecond noise

Tasks:

- [ ] Add `dt_monotonic_us(): uint64`.
- [ ] Add `dt_monotonic_ns(): uint64`.
- [ ] Document host clock caveats.
- [ ] Add monotonicity tests.
- [ ] Add basic elapsed-time smoke tests.

## Priority 7: Runtime Tokenizer Typed Buffer Contract

Value:
- makes PHS/JSS tokenizers efficient and stable for compiler stages

Tasks:

- [ ] Promote typed token-buffer columns into a runtime contract.
- [ ] Standardize kind, offset, length, line, column, and flags types.
- [ ] Decide token kind representation before/after enum support.
- [ ] Add accessor API docs.
- [ ] Add memory/cpu benchmark fixture.
- [ ] Add parity tests against existing readable token output.

## Priority 8: `source_line_index`

Value:
- centralizes offset/line/column conversion for diagnostics, source maps, and
  debugger queries

Tasks:

- [ ] Define `source_location` record.
- [ ] Define `source_line_index` record.
- [ ] Build line-start offsets from `source_buffer`.
- [ ] Implement offset-to-line/column.
- [ ] Implement line/column-to-offset.
- [ ] Use byte columns for compiler-internal mapping.
- [ ] Add tests for empty source.
- [ ] Add tests for Unix and Windows newlines.
- [ ] Add tests for UTF-8 byte-column behavior.

## Priority 9: Row Arena Library

Value:
- gives compiler models stable row ids without introducing a new core container

Tasks:

- [ ] Implement `row_arena<T>` over `vector<T>`.
- [ ] Add reserve/append/get/set/count/clear helpers.
- [ ] Use 1-based ids.
- [ ] Reject id `0` reads.
- [ ] Add tests for stable ids.
- [ ] Add tests for clear-and-reuse.
- [ ] Revisit chunked/fixed-array backing only after benchmarks.

## Priority 10: Builder Library On Top Of `vector_t`

Value:
- improves artifact/string construction without adding a broad builder primitive

Tasks:

- [ ] Implement `string_parts_builder` over `vector<string_t>`.
- [ ] Add reserve helper after vector capacity API exists.
- [ ] Add append string.
- [ ] Add append integer.
- [ ] Add append boolean.
- [ ] Add `string_parts_builder_to_string`.
- [ ] Add JSON-artifact benchmark before replacing existing writers.

## Priority 11: Bitset Library

Value:
- compact dirty/visited state for dependency and incremental flows

Tasks:

- [ ] Implement bitset over `vector<uint64>`.
- [ ] Add set/clear/test.
- [ ] Add count or any-set helper if cheap enough.
- [ ] Add resize/clear behavior.
- [ ] Add tests for boundary bit positions.

## Priority 12: Deque / Ring Work Queue

Value:
- efficient incremental work queues without vector front-removal

Tasks:

- [ ] Implement `work_queue<T>` over vector/ring indexes.
- [ ] Add push back.
- [ ] Add pop front.
- [ ] Add empty/count.
- [ ] Add clear with capacity retention.
- [ ] Add wraparound tests.

## Priority 13: Typed Binary Codec Helpers

Value:
- future replacement path for large JSON caches and artifacts

Tasks:

- [ ] Add write/read helpers for uint8/uint16/uint32/uint64.
- [ ] Add length-prefixed string helpers.
- [ ] Add byte-span write helper after `byte_span` exists.
- [ ] Define endianness.
- [ ] Add roundtrip tests.
- [ ] Add truncated-input diagnostics.

## Priority 14: Memory Accounting Helpers

Value:
- supports compiler memory-per-KB budgeting and regression tracking

Tasks:

- [ ] Add vector count/capacity/estimated-byte helpers.
- [ ] Add string byte length/capacity helper where available.
- [ ] Add hash count/capacity/estimated-byte helpers where available.
- [ ] Add process RSS/peak RSS helpers if not already covered.
- [ ] Document approximation limits.
- [ ] Add trend-oriented tests/probes.

## Priority 15: Stable Hash Helpers

Value:
- stable fingerprints for source text, spans, model facts, and cache keys

Tasks:

- [ ] Keep `hash_string(string): uint64` as a stable public helper.
- [ ] Add `hash_bytes(byte_span): uint64`.
- [ ] Document algorithm stability expectations.
- [ ] Add cross-run stability tests.
- [ ] Add empty input tests.
- [ ] Add UTF-8 byte-sequence tests.

## Later Review Gates

After each priority lands:

- [ ] Check whether the compiler can delete a local workaround.
- [ ] Add one compiler-side probe that benefits from the new helper.
- [ ] Record performance and memory impact.
- [ ] Promote planning text into normative specs only after implementation and
      tests settle.
