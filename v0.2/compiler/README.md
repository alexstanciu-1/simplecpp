# Compiler foundation

Doc Status: supporting

## Rust foundation — implemented 2026-10-29

The standalone Rust library and `scpp-compiler` executable now implement migration
slice 1. The [migration plan](../specs/planning/rust_compiler_migration.md) remains
the roadmap. Scanning, token-sequence publication, parsing, symbols, semantic
compilation, metadata integration and C++ emission are not implemented in Rust yet.
The existing C++ foundation remains in place and buildable, pending reviewed cutover.
The plan now also moves API generation/physical pre-lowering to a separate Rust
library package shared with the compiler. That workspace/library and its C++ layout
probe are not implemented yet; the current package still has no such dependency.

Cargo selects `src/*.rs` and `tests/*.rs`; CMake retains its explicit C++ source list.
There is no build script, FFI, third-party Rust dependency, runtime linkage or
runtime-publication prerequisite. Source files coexist in `compiler/` to avoid a
second eventual compiler directory. CMake does not invoke Cargo, and Cargo does not
invoke CMake or the API generator.

### Install and run

On Ubuntu/WSL, install host tools and the pinned stable Rust toolchain:

```sh
sudo apt-get update
sudo apt-get install -y curl ca-certificates build-essential mold python3
curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs \
  | sh -s -- -y --profile minimal --default-toolchain 1.99.1 \
      --component rustfmt --component clippy
. "$HOME/.cargo/env"
```

Run from this `compiler/` directory so Cargo discovers `.cargo/config.toml` and
the pinned `rust-toolchain.toml`:

```sh
cargo fmt --all --check
cargo clippy --locked --offline --all-targets -- -D warnings
cargo test --locked --offline
cargo test --locked --offline --release
cargo run --locked --offline -- --help
cargo run --locked --offline -- source Cargo.toml 0
python3 tests/measure_foundation.py
```

Cargo uses eight jobs. On x86_64-unknown-linux-gnu, the host `cc` linker driver uses
mold; rustc compiles the Rust code. The existing Ninja/Clang/libc++/mold C++ presets
are unchanged. Rust 1.99.1, rustfmt and Clippy are pinned; Cargo.lock is intentional
reproducibility metadata and is tracked. `target/`, binaries and timing output are
ignored. Offline builds require the pinned toolchain to be installed first.

### Implemented ownership and location contract

- `source.rs`: one append-only SourceMap owns immutable Arc<SourceFile> snapshots.
  Equal paths/bytes create distinct IDs and snapshots. IDs belong to one map and
  must not be exchanged between compilations; they are not persistent global IDs.
  Retained Arcs survive map destruction. Byte input is transferred as Vec<u8>;
  borrowed input requires an explicit copy. Missing sources use Option, not an
  allocated empty state wrapper. Empty bytes form a valid present source.
- Sources preserve arbitrary bytes, including NUL and invalid UTF-8. No decoding or
  newline normalization occurs. Length is limited to u32::MAX bytes. Half-open
  spans validate start <= end <= source length; zero-width EOF spans are legal.
  A Span contains coordinates only, not identity; users bind it to its source.
  Applying a span rechecks containment. `byte_at` returns Option<u8>.
- Locations are one-based lines and **byte columns**. LF starts the next line;
  CR, tabs and multibyte characters count as bytes. EOF after a trailing LF is
  column one of the next line. Unicode/display-width caret rendering is deferred.
- `token.rs`: eight-byte Copy token, with a 32-bit offset and packed 24-bit length /
  eight-bit tag. Length is 1..16777215; zero tags are unrepresentable through the
  public constructor. All nonzero tags remain representable. Named tags preserve
  the C++ vocabulary, without claiming those source constructs are implemented.
  Token construction checks representation; `span(source)` checks containment.
  Ordering and retained sequence ownership belong to the next scanner/publication
  slice. The native record layout is not a serialized wire format.
- `diagnostic.rs`: error/note records retain the source Arc and a validated span.
  Formatting gives path:line:byte-column, escapes control characters in paths and
  messages, and does not decode source bytes. Path display is lossy for invalid
  Unicode, but filesystem access uses the original PathBuf. No rich excerpt or
  machine-readable diagnostic protocol is claimed yet.
- `main.rs`: `source <path> [byte-offset]` loads bytes and prints a location note;
  it is explicitly a source-inspection command, not a compile/check claim. Invalid
  commands, offsets and I/O failures report errors and exit 1; successful commands
  exit 0. Ordinary stdout write failures return an error rather than panic.

Validation failures use typed Result errors in the library, not C++ exceptions.
Rust moves consume the binding; there is no usable moved-from empty handle protocol.
Borrowed byte slices have Rust lifetimes; the C++ no-interior-reference restriction
is not copied into this internal Rust API. Standard allocation-failure behavior
remains in effect; these Results do not promise recoverable out-of-memory errors.
Unsafe code is forbidden by the package lint configuration.

Bounded donors at baseline 8e147257a: the existing C++ `include/token*.hpp`,
`src/token*.cpp`, and token/buffer/sequence tests in this directory. The token tag
vocabulary and packing limits are retained. Snapshot, byte-range and retention
invariants are adapted to Rust; state pointers, handwritten special members and
runtime scalar adapters are not ported. The sequence donor was inspected to keep
source containment separate from packing; its builder/publication remains slice 2.

### Validation and timings

Eight Rust tests pass in debug and release. Formatting and Clippy with warnings
as errors pass. Proofs cover byte retention/identity, empty sources, invalid spans,
CRLF and byte coordinates, diagnostic retention/escaping, all 255 nonzero token
tags, packing limits, source containment and CLI success/failure paths. The 4-GiB
limit uses the actual checked-length helper without allocating a 4-GiB fixture.
All 34 existing C++ CTest checks still pass with the existing compiler in place.

`tests/measure_foundation.py` copies only Rust sources, tests, manifest/lockfile and
Cargo/toolchain configuration into a temporary directory and builds/tests offline.
No C++ compiler source, runtime, generated API or repository build output is copied.
This proves the standalone build boundary and keeps timed edits out of working code.
The linked Linux executable requires libc/libgcc_s/loader, not a C++ or scpp runtime;
its linker comment identifies mold.

Measured on 2026-10-29: Rust/Cargo 1.99.1, mold 2.40.4, Linux WSL2 6.6.87.2,
glibc 2.39, AMD Ryzen 5 PRO 4650G, 12 logical CPUs, eight build jobs. Toolchain was
already installed; there were no package downloads. Each row is one wall-clock
observation, with fresh Cargo targets where stated and OS filesystem caches not
flushed. These numbers characterize this small foundation, not the future compiler
or a controlled C++/Rust comparison.

| Operation | Seconds |
| --- | ---: |
| Clean debug build in Rust-only export | 0.675 |
| Unchanged debug build | 0.058 |
| Incremental CLI help-text edit and relink | 0.247 |
| Cold cargo check in separate target | 0.352 |
| Warm cargo check | 0.059 |
| Test build and run after debug build | 0.950 |
| Warm foundation integration tests | 0.073 |
| Release build with no prior release outputs | 0.593 |

### Preserved C++ baseline

The following sections describe the existing C++ implementation and historical
validation. Its old next-step recommendations are superseded by the Rust migration;
no C++ compiler source, build wiring or runtime publication was removed in slice 1.
It contains compact tokens, immutable source snapshots and token sequence construction.

The active layout is `compiler/include/`, `compiler/src/` and `compiler/tests/`.
Headers use direct includes such as `#include "token.hpp"`; CMake exports the
compiler include directory. C++ namespaces remain `scpp::compiler` and
`scpp::compiler_support`. Legacy donor paths below retain their original names.

## Ownership and bounded reuse

`token` owns its eight-byte representation: a uint32 byte offset and a word with
24 length bits and eight tag bits. Length is 1..16777215, offset 0..4294967295,
and tag zero is reserved. Any nonzero byte tag is representable; the scanner owns
spelling classification. Source containment, ordering and total source limits
belong to the future token builder, not this representation.

Reviewed donors: the old `compiler/my-try-cpp/model/include/scpp_compiler/token.hpp`,
`token_kind.hpp`, and `support/src/token.cpp`. The tag vocabulary is retained
unchanged. Construction and packing now use v0.2 explicit checked constructors,
static casts, bool_t comparisons and unsigned bitwise operators. There is no
legacy scalar-access adapter or dependency on the old tree. The enum/native-value
conversion is a local representation boundary, not implicit scalar conversion.

`publication.json` owns this consumer's requested API, not numeric semantics.
The shared `runtime/metadata/runtime.json` and existing generator remain the sole
owners of those semantics. This slice publishes int32, uint32, int64, uint64,
bool_t and six numeric signatures needed by packing. It is not a complete
publication for the future scanner. New demands should follow real consumers.

Generated files live in `runtime/generated/compiler/`, are ignored by Git, and
are regenerated by the normal build. `scpp_compiler_model` links only
`scpp_compiler_runtime`. This is an alternative publication of the same scpp
runtime identities: do not link it together with the default `scpp_runtime`
publication in one executable. A future combined consumer must request a unified
publication, not combine independently generated definitions.

## Validation and next slice

Run the existing v0.2 presets (Ninja, eight jobs, Clang/libc++, mold).
`compiler_token` checks layout, copyability, every nonzero tag, maximum and minimum
spans, invalid spans, explicit construction and checked narrowing.
`compiler_model_boundary` preprocesses the public include closure and rejects
active template/typename tokens; it also proves implicit native construction fails.
`compiler_runtime_freshness` checks generated artifacts against metadata.

Next: bring in in-memory scanning in bounded slices.
Do not import the old filesystem/task/JSON aggregate to make tokenization compile.

Validation recorded for this slice: all 27 release tests passed; the three
compiler-focused checks passed under UBSan and in a fresh source-only export.
The fresh build regenerated its runtime and its Ninja dependency records contained
no paths into the original repository. Generated code remains untracked.

## Immutable source snapshots

`token_buffer` owns source bytes, with private `std::shared_ptr<const std::string>`
storage behind the template-free API. This bounded reuse preserves the old
`support/src/token_buffer.cpp` ownership, byte lookahead and spelling comparison
contracts; it replaces the legacy shared/string/scalar adapters with private
standard-library storage. Each copied handle allocates a small private state;
it does not copy the byte payload. This is not a zero-allocation handle claim.

`from_bytes(pointer, uint64 length)` is an explicit host ingress that copies the
readable range. Null pointers are rejected even for zero length; `from_bytes("", 0)`
(with an explicitly constructed uint64) creates a present empty source. Capacity
is checked before dereferencing input. Arbitrary invalid non-null pointers remain
a caller error. No source buffer pointer escapes. Source size constraints imposed
by compact tokenization remain a future builder responsibility.

Copies retain immutable identity after the original is replaced or destroyed.
Moves empty the source, and self-copy/self-move preserve it. Distinct snapshots
with equal bytes do not have the same storage identity. Missing snapshots have no
identity, even with themselves. Distinct retained handles may read shared bytes;
mutation/destruction of the same handle requires external synchronization.

`peek` returns -1 outside the source, including for huge offsets; NUL is zero and
all high bytes are unsigned. `byte_at` throws out_of_range outside the source.
`matches` compares a bounded span against a non-null zero-terminated spelling;
it checks spans without overflowing offset+length. Empty spelling matches at EOF
of a present snapshot, but not in a missing snapshot. It does not allocate.

Filesystem reading, runtime string ingress, general string equality and scheduling
are intentionally deferred to their consumer slices. No runtime publication or
operator matrix expansion was needed. The support implementation may use private
standard-library/native operations; the model header remains template-free.

The buffer proof covers caller-memory independence, embedded NUL/high bytes,
missing/empty sources, overflow-resistant bounds, copy/move/self-assignment,
replacement/destruction and rejected null/capacity inputs. All four compiler
checks pass in release and UBSan builds.
The focused buffer executable also passes ASan+UBSan with libstdc++; leak detection
was disabled for this sandbox run, so this is not a LeakSanitizer proof.

## Token sequence construction

Reviewed donors: the legacy `support/include/scpp_compiler/token_sequence.hpp`
and `support/src/token_sequence.cpp`. The v0.2 builder retains their bounded
reserve hint, source ownership, ordered-span checks and single-use publication.
It uses private standard-library contiguous storage and shared ownership, without
importing legacy vector/memory implementations or exposing public templates.
This compiler-specific storage does not add arbitrary record types to the generated
runtime vector API.

A builder requires a present source of at most 4294967295 bytes and a nonnegative
previous-token-count hint. Reserve is bounded by source bytes, defaulting to at
most 256 slots; it is a hint, not a token count or language rule. Each append
checks containment by subtraction and checks ordering/non-overlap; the token
constructor owns length and tag validity. Gaps and adjacent spans are allowed.
Rejected appends preserve prior tokens and allow continued construction.

`finish()` publishes a present immutable generation, including for zero tokens,
transferring the token array without copying it. Further append/finish calls fail.
The handle is allocated before moving builder data; the data move is statically
required to be nonthrowing. No fallible step follows consumption. Publication
failure before consumption leaves the builder available for retry.

Sequence copies share both token storage and their retained source. Moves leave
an absent sequence; self-copy/move preserve identity. `source()` returns an owning
snapshot, `at()` returns a token value, and negative/out-of-range indexes throw.
An absent sequence has zero size/capacity but no readable source. Capacity is
allocated token payload capacity, not a measurement of total memory overhead.
No mutable reference escapes. As with source snapshots, concurrent changes to
the same handle or builder require external synchronization.

The focused proof covers absent/empty generations, invalid hints and spans,
recovery after rejected append, contiguous growth, single-use publication,
copy/move/self-assignment, and retained source lifetime after generation release.
The 4-GiB source-size rejection is checked in code; the tests do not allocate a
4-GiB fixture. Allocation-failure injection and concurrent lifecycle testing are
not claimed by this slice.

Validation for sequence construction: all five compiler checks pass in release
and UBSan configurations. The focused sequence executable also passes ASan+UBSan
with libstdc++ and leak detection disabled; no LeakSanitizer proof is claimed.
