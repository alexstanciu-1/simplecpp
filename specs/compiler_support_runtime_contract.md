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
- source-language offsets and lengths accept normal `int` values and are
  range-checked to runtime storage width;
- `source_buffer_release` invalidates spans derived from that buffer;
- byte reads return `uint8` in compiler metadata, corresponding to strict
  source spelling `byte` where that alias is exposed.

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
