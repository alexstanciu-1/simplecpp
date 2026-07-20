# Compiler Support Runtime Contract

Status: implementation-backed contract

## Purpose

This document promotes the settled compiler-support runtime helpers from
planning notes into a stable contract surface for the Simple C++ compiler
prototype.

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
