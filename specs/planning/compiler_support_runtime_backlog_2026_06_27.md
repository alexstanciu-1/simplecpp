# Compiler Support Runtime Backlog
Doc Status: planning

Date: 2026-06-27

Status: todo backlog

Purpose:
- order implementation work for the compiler-support runtime/language plan
- keep the first implementation passes small, measurable, and compatible

Related plan:

- `specs/planning/compiler_support_runtime_plan_2026_06_27.md`
- `specs/planning/compiler_support_runtime_implementation_audit_2026_06_27.md`

## Placement Rule

Before implementation, each task should keep to its first home:

- core `scpp/` only for scalar wrappers, ownership wrappers, core containers,
  result/null/error primitives, and generated-code traits
- existing modules for strings, datetime, tokenizer, JSON, and filesystem
- new `modules/source` for source buffers, byte spans, and line indexes
- new `modules/binary` for little-endian binary codec helpers
- compiler-side Simple C++ code first for row arenas, artifact builders,
  bitsets, work queues, dependency helpers, and watcher storage

Promotion from compiler-side code into the runtime should require either broad
reuse outside the compiler or benchmark evidence that a runtime primitive is
needed.

## Priority 1: Generic `hash_t<T_VALUE, T_KEY = string_t>`

Value:
- unlocks efficient compiler maps keyed by integer ids, enums, and strings
- fixes the current limitation observed when trying id-keyed compiler caches

First home:
- core `runtime/include/scpp/support/hash_t.hpp`
- generator/STAN changes only where source typing needs them

Tasks:

- [x] Audit current source syntax support for `hash<T_VALUE, T_KEY>`.
- [x] Audit STAN handling for typed hash key parameters.
- [x] Audit generated C++ for one- and two-parameter hash declarations.
- [x] Audit runtime `hash_t` template shape and specializations.
- [x] Audit existing operations: `[]`, `isset`, write, delete, count, foreach,
      append, and iteration.
- [x] Audit key families currently supported by implementation.
- [x] Write focused tests for `hash<int>` compatibility.
- [x] Write focused tests for `hash<int, int>`.
- [x] Write focused runtime tests for `hash_t<int, uint32>`.
- [x] Write focused runtime tests for `hash_t<int, byte>`.
- [x] Implement generic `key_ops<int_t<Rep>>` for fixed-width integer keys.
- [x] Decide typed-integer append return type for `hash<T, uint32>` and similar
      key families.
- [x] Implement append for accepted integer key families.
- [x] Write source-level tests for `hash<int, uint32>`.
- [x] Write source-level tests for `hash<int, byte>`.
- [x] Ensure source append assignment evaluates to assigned value, not generated
      key.
- [x] Preserve current `hash<T>` string-key default behavior.
- [x] Preserve current `hash<mixed>` dynamic compatibility behavior.
- [x] Add clear diagnostics for unsupported key types.
- [x] Update docs/specs after behavior is stable.

## Priority 2: Enums Backed By Fixed-Width Integers

Value:
- makes token/node/operator kinds type-safe without losing compact storage

First home:
- parser/AST, generator, and STAN
- runtime helper headers only for explicit enum metadata/conversion helpers

Tasks:

- [x] Audit existing enum parser/AST coverage.
- [x] Decide whether compiler-focused enums reuse PHP-style `case Name;`
      syntax or a stricter Simple C++ subset.
- [x] Define enum grammar with required backing type if the existing enum
      surface is not sufficient.
- [x] Add duplicate-name and duplicate-value diagnostics.
- [x] Lower simple enum declarations to C++ `enum class`.
- [x] Support enum member references such as `kind::value`.
- [x] Support equality for same enum type.
- [x] Reject implicit assignment from raw integer to enum.
- [x] Add explicit enum-to-backing conversion helper.
- [x] Add explicit backing-to-enum conversion helper.
- [x] Add STAN checks for enum assignment and comparison discipline.
- [x] Add tests for byte-backed enum.
- [x] Add tests for uint16-backed enum.
- [x] Add tests for enum as `hash_t` key after generic hash support lands.
- [x] Add optional enum name/value metadata helpers after the base feature is
      stable.

## Priority 3: `hash_t` Todo From Audit

Value:
- keeps generic hash implementation honest and prevents drift between syntax,
  STAN, generator, and runtime

Tasks:

- [x] Convert the Priority 1 audit into a detailed implementation checklist.
- [x] Split checklist by owner: language, STAN, generator, runtime, tests, docs.
- [x] Mark compatibility blockers separately from performance improvements.
- [x] Add a narrow migration note for existing string-keyed code.
- [x] Add benchmark probes for string-key and int-key hash use.

Implementation checklist:

- Language:
  - [x] Keep `hash<T>` as the string-keyed default.
  - [x] Support explicit `hash<T,T_KEY>` source notation.
  - [x] Preserve append expression value semantics, so `$w = $x[] = VALUE`
        gives `$w` the assigned value, not the generated key.
  - [x] Allow fixed-width integer aliases as explicit keys.
  - [x] Allow enum types as explicit keys.
- Generator:
  - [x] Lower `hash<T>` to `hash_t<T>`.
  - [x] Lower `hash<T,T_KEY>` to `hash_t<T,T_KEY>`.
  - [x] Reject unsupported explicit key families with a clear diagnostic.
  - [x] Cast generated append keys to the declared fixed-width key type.
- STAN:
  - [x] Infer `foreach` key/value types for `hash<T,T_KEY>` as `T_KEY` and
        `T`.
  - [x] Keep `hash<T>` compatibility behavior unchanged for string-keyed
        code.
  - [x] Add dedicated diagnostics for unsupported explicit key families before
        generation when enough source facts are available.
- Runtime:
  - [x] Keep `hash_t<mixed_t,mixed_t>` as the dynamic compatibility path.
  - [x] Keep generic typed hashes on explicit key storage.
  - [x] Support `string_t`, `int_t<Rep>`, enum, `shared_p<T>`, `unique_p<T>`,
        and `weak_p<T>` key hashing/equality.
  - [x] Keep unsupported append on non-integer key modes loud.
- Tests:
  - [x] Cover default string-keyed `hash<T>`.
  - [x] Cover `hash<T,int>`, `hash<T,uint32>`, and `hash<T,byte>`.
  - [x] Cover enum keys.
  - [x] Preserve dynamic `hash_t<mixed_t,mixed_t>` runtime coverage.
- Docs:
  - [x] Document that existing `hash<T>` code remains string-keyed.
  - [x] Document that integer and enum append modes are explicit opt-ins.
  - [x] Document compatibility blockers separately from performance follow-ups.

Migration note:

- Existing `hash<T>` code does not need to change. It remains string-keyed.
- Code that wants append-style numeric keys should choose an explicit integer key
  family such as `hash<T,int>`, `hash<T,uint32>`, or `hash<T,byte>`.
- Code that wants compact semantic keys should use enum-backed keys such as
  `hash<T,token_kind>`.
- Dynamic compatibility tables continue to use `hash_t<mixed_t,mixed_t>` and
  are not affected by typed key-family additions.

Compatibility blockers:

- No known blocker remains for fixed-width integer or enum key families in the
  current compiler-support slice.
- Dedicated STAN diagnostics for unsupported key families can improve feedback
  timing, but generator diagnostics already block invalid output.

Performance follow-ups:

- Added `tools/runtime_benchmarks/run_hash_key_probe.sh` for string-key,
  `int`, and `uint32` insert/lookup timings.
- Informational local baseline on 2026-06-27 for 100k entries:
  `hash_string_key insert_us=32956 lookup_us=7132`,
  `hash_int_key insert_us=13996 lookup_us=1959`,
  `hash_uint32_key insert_us=10259 lookup_us=5713`.
- Add probes for enum-key lookup/insert.
- Compare memory footprint of explicit typed-key mode against dynamic
  compatibility mode.

## Priority 4: `vector_t` Capacity Operations

Value:
- lets compiler stages reserve known row counts and reuse resident storage

First home:
- core `runtime/include/scpp/vector_t.hpp`
- language/PHP wrapper helpers where needed for source calls

Tasks:

- [x] Add `vector_reserve`.
- [x] Add `vector_capacity`.
- [x] Add `vector_clear` that preserves capacity.
- [x] Add `vector_compact($v)`.
- [x] Add optional explicit capacity target for `vector_compact($v, $capacity)`.
- [x] Add typed-vector tests.
- [x] Add fixed-width vector tests.
- [x] Add docs for count vs capacity.

Count versus capacity:

- `count($vector)` remains the number of live elements.
- `vector_capacity($vector)` reports reserved storage slots and can be larger
  than `count($vector)`.
- `vector_clear($vector)` removes elements but preserves capacity for resident
  reuse.
- `vector_compact($vector)` asks the runtime to release spare capacity where the
  C++ standard library can do so.
- `vector_compact($vector, $capacity)` preserves all live elements and treats
  `$capacity` as a target lower bound, not as a truncation request.

## Priority 5: `source_buffer` And `byte_span`

Value:
- avoids duplicate source copies and enables safe non-copying tokenizer/parser
  views

First home:
- new `runtime/include/modules/source`
- thin `runtime/include/scpp/source.hpp` include wrapper only if consistent with
  other module facades

Tasks:

- [x] Define `source_buffer` ownership contract.
- [x] Define `byte_span` view contract.
- [x] Decide conservative owning-buffer v1 versus immediate move-out
      optimization from `string_t`.
- [x] Implement `source_buffer_take($text): source_buffer`.
- [x] Ensure `source_buffer_take` leaves `$text` as `""`.
- [x] Implement `source_buffer_release($buffer): string`.
- [x] Ensure release empties the buffer.
- [x] Document that release invalidates all spans.
- [x] Add `source_buffer_byte_len`.
- [x] Add `source_buffer_byte_at`.
- [x] Add `source_buffer_span`.
- [x] Add `source_buffer_slice`.
- [x] Add `byte_span_len`.
- [x] Add `byte_span_at`.
- [x] Add `byte_span_to_string`.
- [x] Add `hash_bytes(byte_span)`.
- [x] Add lifetime and mutation tests.

V1 notes:

- `source_buffer_take($text)` copies the current `string_t` bytes into an owning
  read-only source buffer, then clears `$text`. This preserves the accepted
  language contract today without exposing unsafe mutable string internals.
- A later optimization should add explicit `string_t` attach/detach storage
  primitives so `source_buffer_take` and `source_buffer_release` can move
  storage instead of copying where ownership allows it.
- `byte_span` captures the source buffer generation and throws a runtime error
  if read after `source_buffer_release`.
- Language-facing offset/length arguments accept normal `int` values and
  range-check to `uint32`; byte lengths are returned as `uint32`.
- `hash_bytes(byte_span)` currently returns the same stable hex-string shape as
  `hash_string(string)` for API consistency.

## Priority 6: High-Resolution Monotonic Timers

Value:
- improves compiler profiling fidelity without coarse millisecond noise

First home:
- existing `runtime/include/modules/datetime`
- PHP support wrappers for source-level calls

Tasks:

- [x] Add `dt_monotonic_us(): uint64`.
- [x] Add `dt_monotonic_ns(): uint64`.
- [x] Document host clock caveats.
- [x] Add monotonicity tests.
- [x] Add basic elapsed-time smoke tests.

Clock caveats:

- `dt_monotonic_ms/us/ns` are based on the host C++ `std::chrono::steady_clock`.
- Values are process-local monotonic ticks, not Unix timestamps, and should only
  be compared with other monotonic values from the same run.
- The returned unit is explicit; the actual precision still depends on the host
  clock and operating system.

## Priority 7: Runtime Tokenizer Typed Buffer Contract

Value:
- makes PHS/JSS tokenizers efficient and stable for compiler stages

First home:
- existing `runtime/include/modules/tokenizer`

Tasks:

- [x] Provide typed `phs_tokenize_buffer` and `jss_tokenize_buffer` runtime
      entry points.
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

First home:
- new `runtime/include/modules/source`
- share the same source ownership/lifetime contract as `source_buffer`

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

First home:
- compiler-side Simple C++ library
- do not add a core runtime arena until measurements justify it

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

First home:
- generic string builder: existing `modules/strings`
- artifact-specific builders: compiler-side Simple C++ library

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

First home:
- compiler-side Simple C++ library
- possible future `modules/collections` only after shape stabilizes

Tasks:

- [ ] Implement bitset over `vector<uint64>`.
- [ ] Add set/clear/test.
- [ ] Add count or any-set helper if cheap enough.
- [ ] Add resize/clear behavior.
- [ ] Add tests for boundary bit positions.

## Priority 12: Deque / Ring Work Queue

Value:
- efficient incremental work queues without vector front-removal

First home:
- compiler-side Simple C++ library
- possible future `modules/collections` only after shape stabilizes

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

First home:
- new `runtime/include/modules/binary`

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

First home:
- process memory: existing `runtime/include/operators/memory_usage`
- container capacity estimates: near `vector_t`, `hash_t`, and `string_t`

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
