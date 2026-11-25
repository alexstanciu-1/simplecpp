# Stable local receivers for shared-state providers
Doc Status: planning

2026-10-25; baseline 1de274412 plus this slice.

Reuse: Key_Storage_List copies alias membership; Storage_Cursor copies alias its
shared cursor_state, including advancement. Bind scope query collections and enum
child cursor to typed locals before method calls. Preserve membership, record
identity, parse order and cursor progress. No new receiver or ownership semantics.
Do not generalize this adaptation to ordinary inline values or vector/hash copies.

Extended tests/portability/storage_cursor.py proves repeated field-to-local reads
and advancement, exhaustion, retained source and identity in PHP, v0.1 native,
v0.2 ordinary and v0.2 concrete output. All pass. Host ast, namespace_bodies,
namespaces and incremental_preparation pass.

Bootstrap: 175.38 seconds, 610504 KiB peak RSS (596.20 MiB), 578.5 MiB PHP heap.
Preparation still fails with the same 19 distinct messages. First now is object
narrowing requiring a polymorphic source. Other projected receivers remain; this
slice proves the audited scope and enum-child paths, not universal elimination.
Native compiler and second-generation proof remain incomplete.
