# Rust compiler foundation

Doc Status: supporting

The compiler is now Rust-only. It implements byte sources, checked spans, compact
tokens, immutable token-sequence publication, diagnostics and a source-inspection
CLI. Scanning, parsing, symbols, semantic compilation, runtime metadata integration
and C++ emission remain future slices in the
[migration plan](../specs/planning/rust_compiler_migration.md).

The v0.2 workspace owns the lockfile, Rust 1.99.1 pin and Cargo configuration
(eight jobs; mold on x86_64 Linux). The compiler depends on the shared Rust
[API tooling library](../tools/api_generator/README.md), which uses Serde. It has
no C++ runtime linkage, generated-header prerequisite, build script or FFI.
Cargo and CMake do not invoke each other. Build outputs live in ignored v0.2/target/.

## Build and validate

Install Rust 1.99.1 with rustfmt/Clippy and the host cc/mold tools. From v0.2/:

```sh
cargo fetch --locked
cargo fmt --all --check
cargo clippy --workspace --all-targets --locked --offline -- -D warnings
cargo test --workspace --locked --offline
cargo test --workspace --release --locked --offline
cargo run -p scpp-compiler --locked --offline -- --help
cargo run -p scpp-compiler --locked --offline -- source compiler/Cargo.toml 0
python3 -B compiler/tests/measure_foundation.py
```

Offline builds require cached packages and the installed toolchain. The measurement
script exports both Rust packages without C++ sources, runtime files or generated
headers, then builds/tests the workspace offline. It measures clean/incremental
compiler builds with a fresh target and a warm filesystem cache; its results are
observations of this small foundation, not forecasts of the full compiler.

## Model ownership

- `source.rs`: an append-only SourceMap owns immutable Arc<SourceFile> snapshots.
  Equal paths/bytes still create distinct IDs and snapshots. IDs belong to one map,
  not a persistent global namespace. Retained Arcs survive map destruction. Input
  bytes transfer from Vec and preserve NUL, invalid UTF-8 and line endings.
- Sources are limited to u32::MAX bytes. Half-open spans check containment and
  allow zero-width EOF locations. Spans carry coordinates only; applying a span
  to a source rechecks containment. Locations use one-based lines and byte columns,
  not Unicode/display columns. LF starts a new line; CR remains an ordinary byte.
- `token.rs`: an eight-byte Copy token stores a 32-bit offset, 24-bit nonzero length
  and nonzero eight-bit tag. All 255 nonzero tags are representable; named tags retain
  the donor vocabulary without claiming language support. Token::new checks
  representation; Token::span checks containment. Layout is not a wire format.
- `token_sequence.rs`: TokenBuilder owns a source Arc and mutable Vec<Token>.
  `push(Token)` binds valid token coordinates to that source and rejects overlap,
  backward order and out-of-source spans before mutation. Gaps and adjacent spans
  are legal. A failed push leaves the builder usable. It does not classify tokens
  or verify their spellings; those are scanner responsibilities.
- The builder's unsigned previous-count hint is capped at the source byte length;
  zero defaults to at most 256 slots. SourceFile already enforces the 32-bit size
  limit. Invalid token length/tag/offset is rejected by the token owner, not
  duplicated in sequence construction.
- `finish(self)` consumes the builder and moves its Vec into Arc<Generation>.
  Publication allocates the shared generation but does not copy token/source bytes
  or shrink/reallocate the Vec. Clone shares that generation. The immutable token
  slice borrows from the sequence; `get` returns an optional copied token.
  `source()` returns a borrowed Arc that callers may clone for independent retention.
  A sequence always has a source; absence uses Option<TokenSequence>. An empty
  sequence is valid. There is no runtime finished flag or moved-from handle state.
- `diagnostic.rs`: error/note records retain the source Arc and checked span. Display
  escapes control characters in paths/messages and prints byte coordinates. Path
  display is lossy for invalid Unicode, while file access retains the original path.
- `main.rs`: `source <path> [byte-offset]` prints a location note. This is not a
  compile/check command. Input/I/O errors return exit 1; successful commands exit 0.

Unsafe code is forbidden. Model errors use typed Result values. Allocation failure
follows Rust's standard behavior; no recoverable out-of-memory guarantee is made.

## Replacement and validation

The token/source donor was the v0.2 C++ foundation at 8e147257a; the sequence donor
was compiler/include/token_sequence.hpp, src/token_sequence.cpp and its test at
parent revision a3ad4e463. These are history references, not current build inputs.
The Rust version retains ordering, containment, retention and publication invariants
while using Rust moves/borrows instead of C++ state wrappers and sentinel handles.

The 12-file C++ compiler foundation and its root CMake hook have been retired.
Its publication metadata is now an intentional fixture at
[tests/generator_parity/compiler_publication.json](../tests/generator_parity/compiler_publication.json).
G1 parity and native-probe integration use that unchanged fixture. There is no
compiler-specific C++ runtime publication in the active build. The C++ API generator,
runtime and ownership proofs remain active.

Sequence proofs cover empty generations, invalid/overlapping spans, recovery,
adjacency/gaps, retained ownership, growth and preservation of the token allocation
across publication and cloning. A compile-fail doctest checks single-use publication.
The existing token proofs cover invalid lengths/tags and representation boundaries;
the source-size proof checks the limit without allocating a 4-GiB fixture.

Validation for this replacement: 20 Rust unit/integration tests and one compile-fail
doctest pass in debug/release, formatting and strict Clippy pass, and the isolated
Rust-only workspace export passes. All 31 retained C++ tests pass in release and
UBSan. G1 frozen parity and G2 probe integration pass with the relocated fixture.
A fresh source-only C++ export also builds and passes all 31 checks, without
retired compiler sources or pre-generated runtime files.
