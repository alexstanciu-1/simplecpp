# Rust compiler foundation

Doc Status: supporting

The compiler is now Rust-only. It implements byte sources, checked spans, compact
tokens, immutable token-sequence publication, flavor-aware scanning, a shared AST with C++/PHP/JS parser frontends, diagnostics
and source/token/parser inspection commands. Symbols, semantic compilation, runtime metadata integration
and C++ emission remain future slices in the
[migration plan](../specs/planning/rust_compiler_migration.md).

The v0.2 workspace owns the lockfile, Rust 1.99.1 pin and Cargo configuration
(eight jobs; mold on x86_64 Linux). The compiler uses only the Rust standard
library: no API-tooling crate dependency, C++ runtime linkage, generated-header
prerequisite, build script or FFI. Runtime work owns production API generation;
future compiler integration consumes its published contract.
Cargo never invokes CMake. Runtime CMake invokes Cargo for API generation;
standalone compiler builds remain independent, with outputs in ignored v0.2/target/.

## Build and validate

Install Rust 1.99.1 with rustfmt/Clippy and the host cc/mold tools. From v0.2/:

```sh
cargo fmt -p scpp-compiler --check
cargo clippy -p scpp-compiler --all-targets --locked --offline -- -D warnings
cargo test -p scpp-compiler --locked --offline
cargo test -p scpp-compiler --release --locked --offline
cargo run -p scpp-compiler --locked --offline -- --help
cargo run -p scpp-compiler --locked --offline -- source compiler/Cargo.toml 0
python3 -B compiler/tests/measure_foundation.py
```

Compiler builds require the installed toolchain, with no external crate packages.
The measurement script exports only compiler sources/tests and a minimal workspace,
without API tooling, runtime files or generated headers, then builds/tests offline. It measures clean/incremental
compiler builds with a fresh target and a warm filesystem cache; its results are
observations of this small foundation, not forecasts of the full compiler.

## Rebuild observations — scanner slice, 2026-10-29

Measured with Rust 1.99.1, Ryzen 5 PRO 4650G, Linux/WSL2, eight Cargo jobs and
mold. Compiler-only export, no external packages, initially fresh Cargo targets,
filesystem cache not flushed; one observation per case. Edits change diagnostic/help
text in temporary exported sources. Each edit starts with warmed check/build caches.

| Work | Wall time |
| --- | ---: |
| Clean compiler debug build | 0.917 s |
| Unchanged debug build | 0.067 s |
| CLI edit, debug rebuild | 0.307 s |
| Compiler model edit, check / debug rebuild | 0.234 / 0.470 s |
| Scanner edit, check / debug rebuild | 0.218 / 0.403 s |
| Cold / unchanged check | 0.428 / 0.064 s |
| Clean compiler release build | 1.067 s |

These describe this foundation and small implementation edits, not forecasts of
full compiler costs. Reproduce with the script above; full local output is ignored
at build/compiler-scanner-timings.json. Earlier shared-tooling measurements included
a dependency the compiler no longer has.

## Phase layout and dependency direction

The layout follows the reviewed `compiler/my-try/` phase structure. Numbered physical
folders map to ordinary Rust module names with `#[path]` in lib.rs; this remains one
crate, without root compatibility aliases for the old flat module paths.

```text
src/
  main.rs                       CLI arguments, presentation and process exit
  lib.rs                        module declarations
  diagnostic.rs                 source-associated diagnostic representation
  language.rs                   syntax flavor identity
  compiler/
    mod.rs
    driver.rs                   loading/phase sequencing and invocation-owned sources
  01_prepare_inputs/            Rust module: inputs
    mod.rs
    source.rs                   source identities, bytes, spans and locations
    load.rs                     filesystem acquisition into a supplied SourceMap
  02_tokenize/                  Rust module: tokenize
    mod.rs
    scanner.rs                  lexical processing and spelling classification
    token.rs                    compact representation
    token_sequence.rs           checked construction and immutable publication
  03_parse/                     Rust module: parse
    mod.rs
    ast.rs                      common source-level AST
    parser.rs                   shared parsing machinery
    cpp.rs, php.rs, js.rs        flavor-specific declaration grammars
```

Future phase owners are `04_analyze/` (name/type resolution and semantic preparation), `05_backend/cpp/`
(target bindings and rendering) and `06_native/` (native build/run). Add these only
with real implementations. A separate session module is unnecessary while Driver's
only retained state is its SourceMap; no empty scaffold or global model is added.

Workers may consume earlier-stage data and shared diagnostics, but must not invoke
later processing or depend on the driver. Input representation performs no I/O;
the loader does no scanning. The scanner remains an in-memory worker. Driver orders
loading/scanning/parsing and returns data; main owns text presentation. Driver normalizes
failures to command error text at this boundary; lexical workers retain their typed
diagnostics and loading exposes a typed LoadError.

Records preserve their own invariants and expose queries; they do not dispatch
phase algorithms or own other stages' scheduling state. Parsing collects syntax
and declarations; analysis resolves them. Backend emission must consume settled
semantic decisions rather than repeat source validation. Runtime generation remains
runtime-owned. Native tool invocation does not decide language semantics.

Invocation-owned source snapshots may outlive the driver through results. Each load
adds a fresh identity; this is not an incremental cache. Failed scans publish no token
sequence, though their loaded bytes remain in the source map for that invocation.
No worker, mutable global root, incremental scheduler or backend is introduced.

## Model ownership

- `01_prepare_inputs/source.rs`: an append-only SourceMap owns immutable Arc<SourceFile> snapshots.
  Equal paths/bytes still create distinct IDs and snapshots. IDs belong to one map,
  not a persistent global namespace. Retained Arcs survive map destruction. Input
  bytes transfer from Vec and preserve NUL, invalid UTF-8 and line endings.
- Sources are limited to u32::MAX bytes. Half-open spans check containment and
  allow zero-width EOF locations. Spans carry coordinates only; applying a span
  to a source rechecks containment. Locations use one-based lines and byte columns,
  not Unicode/display columns. LF starts a new line; CR remains an ordinary byte.
- `02_tokenize/token.rs`: an eight-byte Copy token stores a 32-bit offset, 24-bit nonzero length
  and nonzero eight-bit tag. All 255 nonzero tags are representable; named tags retain
  the donor vocabulary without claiming language support. Token::new checks
  representation; Token::span checks containment. Layout is not a wire format.
- `02_tokenize/token_sequence.rs`: TokenBuilder owns a source Arc and mutable Vec<Token>.
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
  compile/check command. `scan <cpp|php|js> <path>` prints offset, length and numeric tag per
  token, after complete successful scanning. Lexical/I/O errors return exit 1 with
  no partial token output; successful commands exit 0.

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


## Initial PHP scanning contract and donor review

The first scanner adopted PHP-like source-body syntax from the PHS donor;
this is not a separate strict-PHP mode or full PHP admission. The current
[flavor contract](../specs/compiler_frontends.md) supersedes the original single-flavor
framing and describes C++/PHP/JS selection. No PHP/HTML mode, BOM stripping or source normalization is
introduced. Source bytes and gaps remain exact. Recognizing constituent tokens
alone does not admit a construct, operation or identifier in a parser context.

Reviewed donors: `compiler/my-try-cpp/src/tokenize/scanner.cpp`, `spellings.cpp`,
`docs/tokenization.md` and `docs/token_inventory.md` at fab676e4f. These remain
reference material, never build inputs. The new `scanner.rs` owns scanning and
fixed-spelling classification together, using borrowed byte slices and Rust match
patterns. No middleware, source-revision coordinator, module discovery, batch/task
machinery or runtime-backed containers are ported.

Adopted lexical rules:

- ASCII letters/underscore start identifiers; digits may follow. Variables include
  their leading `$`. The existing 64 lowercase word tags classify exact spelling;
  `int`, `string` and `vector` remain identifiers. Classification is case-sensitive.
- Decimal digit runs, optional decimal point and exponent form numeric tokens;
  `.5`, `12.`, `1e-3` are recognized. Signs are separate tokens except in exponents.
  Missing exponent digits and immediately attached letters/underscore/dot fail.
  No hex/binary forms, separators, numeric conversion or range checking is added.
- Single/double quoted strings remain whole tokens. Backslash protects the next
  byte. Interpolation, escapes and literal contents are not decoded. Arbitrary
  bytes inside strings/comments are retained; non-ASCII identifiers are not admitted.
- Space, tab, CR and LF are trivia. `//`, `#` (except `#[`) and non-nesting `/* */`
  comments are omitted. Unterminated strings/comments fail at their opening byte.
- Punctuation uses the longest registered spelling. `<<` and `>>` remain adjacent
  individual angles, preserving generic syntax; `<<=` and `>>=` are single tokens.
  There is no stored EOF token. Actual NUL outside a string/comment is an error.

`scan(Arc<SourceFile>, previous_count, flavor)` returns either a complete TokenSequence or
its first Diagnostic. The count is a bounded reserve hint, not an incremental-scan
request. Failure publishes no partial generation and leaves old generations usable.
No per-token string copy or allocation is introduced; the builder owns token storage.
Compact token-length limits are checked before publication.

Deliberate diagnostic difference from the donor: locations use SourceFile's existing
LF-based line table, with CR retained as a byte. This avoids a second location model;
the C++ scanner's independent bare-CR line counting is not carried over. Error spans
point to the first offending/opening byte. Recovery is deferred until parser needs
and synchronization rules are specified.

Validation: 22 compiler tests plus one compile-fail doctest pass in debug/release,
with strict Clippy and formatting. Tests cover all word tags/punctuation families,
exact independent spans, quotes/comments, malformed numbers, all single input bytes
with short suffixes, retained source identity, no partial CLI output, and actual
24-bit token-length acceptance/rejection. The compiler-only offline export also
builds/tests with no API-tooling sources or external packages.


Structural reorganization validation: the unchanged CLI/scanner proofs pass, plus
one driver recovery/lifetime proof (23 tests and one compile-fail doctest in total).
Debug/release, strict Clippy and formatting pass. The compiler-only export recursively
copies Rust modules and builds/tests without runtime/API-tooling inputs. Runtime
cutover 0c230e0bc is integrated; production generator files are unchanged by this
reorganization.


## Three-flavor parser slice

See the [flavor and AST contract](../specs/compiler_frontends.md) for the exact
subset, declaration/inference/write distinctions and project strictness boundary.
The compiler has one PHP flavor; there is no strict-PHP++/PHS mode switch. Parsing
preserves information needed by future common analysis without applying strictness
or choosing default numeric types.

From v0.2/, inspect a source with explicit flavor selection:

```sh
cargo run -p scpp-compiler --locked --offline -- scan php path/to/input.phs
cargo run -p scpp-compiler --locked --offline -- parse cpp path/to/input.cpp
cargo run -p scpp-compiler --locked --offline -- parse js path/to/input.js
```

Parse prints a statement/expression count after complete success; it is not a
compilation/type-check command. Flavor is retained with tokens; the parser cannot
accidentally receive a second, inconsistent flavor argument. Multiple flavors may
be parsed through the same Driver, each with an independent source identity.
Project configuration loading, profile enforcement and cross-file name resolution
remain later work. Lexical and grammatical errors are rejected now.

Validation: 30 compiler tests plus the compile-fail doctest, debug/release, strict
Clippy, formatting and compiler-only export. New proofs cover cross-flavor AST
intent, CLI/driver selection, nested type arguments and shifts, preserved spans,
malformed syntax, bounded nesting and long arithmetic expression chains.


Fresh parser-slice timing (same Rust 1.99.1/Ryzen 4650G/WSL2, eight jobs, mold;
one observation, compiler-only export): clean debug 1.148 s, clean release 1.194 s,
CLI edit rebuild 0.351 s, model edit check/rebuild 0.300/0.537 s, scanner edit
check/rebuild 0.304/0.450 s. Full ignored report: build/compiler-parser-timings.json.
These are small diagnostic-text edits, not estimates for a complete compiler.
