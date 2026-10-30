# Core byte and optional numeric module

Doc Status: normative
Date: 2026-10-30
Scope: v0.2 runtime publication and the agreed language direction. PRJ profile
loading and compiler enforcement remain future compiler work.

## Core and explicit demand

The default numeric surface is `int64`, `float64` and `byte`. Boolean and null
remain separate core scalar families. `int_t` continues to alias the configured
integer default, normally int64; `float_t` aliases float64.

Additional integer types (`int8`, `int16`, `int32`, `uint8`, `uint16`, `uint32`,
`uint64`) require the optional `numeric` module. The API publication declares
`"modules": ["numeric"]` and names the types and operations it needs. Activating
the module permits those types; it does not emit the entire family. Unsupported
float widths remain rejected. Float32 may be added by a later reviewed slice.

Core and optional types are emitted in one consistent runtime publication. They
must not be linked as independent competing definitions of the same core types.
The existing numeric headers remain the public include surface; module selection
controls their contents rather than introducing a second scalar implementation.

`SCPP_ENABLE_NUMERIC=ON` composes the checked-in `numeric_publication.json` demand
with core. That sample demand adds int32 and its reviewed operations/conversions;
other widths can be requested explicitly through publication metadata. Datetime
selects numeric as a dependency and requests uint64 only. JSON alone needs no
additional integer widths. All three options compose with tests disabled.

## Distinct byte

Byte is an unsigned eight-bit value in 0..255, zero-initialized and distinct from
uint8. This supersedes the older byte-as-uint8-alias rule for the v0.2 rebuild.
It represents a raw byte, not a Unicode character, code point or encoding promise.
There is no char8 public type in this slice.

Conversions to/from published integer types and the configured native integer
inputs are explicit and range-checked where needed. Failure leaves an existing
destination unchanged. There is no implicit arithmetic promotion, and no direct
float, boolean, string, mixed or dynamic conversion for byte in this slice.
A consumer must explicitly obtain an integer before converting such values to byte.

Admitted operations, when requested:

- `&`, `|`, `^`, `~` and their binary compound-assignment forms, on byte operands.
- `<<`, `>>`, `<<=`, `>>=` with byte on the left and int64 as the count.
- Equality/inequality between two bytes, returning bool_t.

Bitwise results retain byte. Shift counts must be 0..7; invalid counts throw
`std::domain_error`. Left shift discards bits beyond the eight-bit result; right
shift inserts zero bits. Arithmetic, increment/decrement, ordering and mixed-type
operators are rejected. Explicit integer conversion is required for arithmetic.

The API generator owns admission in scalar preparation and reuses the checked
integer conversion/bit-operation helpers for storage mechanics. Reusing unsigned
storage does not make byte semantically interchangeable with uint8. Source compiler
integration, byte string helpers and direct boxed-byte conversions are future work.

## Publication cleanup

The output directory supplied to the generator is dedicated generated storage
(for example `compiler/generated/` when used by a compiler consumer). Normal
emission prepares/renders the entire new publication before deleting anything,
then recursively removes files with exactly `.hpp`, `.cpp` and `.json` extensions
and rewrites the complete output. Other extensions remain untouched. Empty output
subdirectories are removed. Symlink directories are never traversed.

`inventory.json` is a manifest with `generator`, integer `version` and `files`.
The internal `OUTPUT_VERSION` identifies the generated-output contract. Bump it
when that contract/layout changes. Normal generation always uses full cleanup,
so an old version, old list inventory, missing inventory or malformed inventory
needs no special migration path. The manifest is written last.

`--check` never cleans or writes. It rejects missing/changed expected files and
extra disposable-extension files. A version mismatch is a stale manifest.
Filesystem write failure can leave incomplete output; regeneration repairs it.
Atomic directory replacement and concurrent writers are not promised.

CMake uses `runtime/generated/` for production, cleaning disabled module artifacts
on regeneration. Each build checks this shared publication against its own demand
and target facts, regenerating only when stale. Run configurations sequentially
within a checkout; the directory reflects the latest build. Separate proof
publications stay build-local, such as `build/clang/generated-ownership-proof/`,
outside the production cleanup root. Old output
roots outside the selected directory require an explicit clean; the generator does
not search the repository for abandoned builds.
