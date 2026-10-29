# Runtime API tooling

Doc Status: supporting

The C++ `scpp_api_generator` remains the production generator. The Rust
`scpp-api-tooling` library/CLI implements G2 scalar preparation: bounded JSON loading, native target facts,
integer/bool/null/float conversions and resolved operators. It emits
inspection JSON only. It does not generate C++ or replace the G1 CLI contract.

## Shared ownership

The v0.2 Cargo workspace contains this package and `compiler/`. The compiler depends
on the tooling library; tooling has no compiler AST, symbol or diagnostic dependency.
`json.rs` owns metadata decoding, `target.rs` validates supplied target facts, and
`integer.rs` owns shared integer identities/range conversions and the original
construction projection. `scalar.rs` validates scalar policy and prepares resolved
conversions, operation forms, promotions, operand adaptations and write-back facts. `main.rs` is a thin I/O
adapter. No build script, C++ runtime linkage or generated headers are required.
Neither Cargo nor CMake invokes the other build system.

The workspace owns Cargo.lock, rust-toolchain.toml and .cargo/config.toml. Rust
1.99.1 is pinned, builds use eight jobs and the Linux linker uses mold. Output lives
in ignored `v0.2/target/`; generated probe files live under ignored `v0.2/build/`.

## JSON boundary

The production C++ generator still uses our custom JSON implementation under
`tools/support/json/`, through `value.*`. Rust uses serde 1.0.229 and serde_json
1.0.154, pinned by the workspace lockfile. Its adapter delegates JSON grammar to
serde_json rather than maintaining another handwritten parser.

The adapter preserves raw number tokens (including huge exponents and integers),
rejects duplicate decoded keys, invalid UTF-8/surrogates, trailing input and nesting
beyond 128 containers. Typed extraction separately rejects fractional/exponent
spellings for integer schema fields and checks bounds. Tests cover these distinctions.
Diagnostics carry an input label and byte offset; exact C++ diagnostic text is not
promised. The Rust tree is not a replacement serialization API for the runtime.

Active third-party packages are serde, serde_core, serde_json, itoa, memchr and zmij.
Serde derive is not enabled; optional proc-macro dependencies recorded in Cargo.lock
are not built by this configuration. Initial package fetching needs network access;
subsequent locked offline tests use the local cache.

## Native C++ layout probe

`../layout_probe/probe.cpp` depends only on standard C++ headers. It measures native
integer sizes/alignments/ranges, fixed-width integer facts, pointer/byte sizes,
binary64 properties, standard-library identity, int128 availability and shared,
weak and unique pointer layouts. The Python driver compiles/runs it independently
of the runtime and generator. The initial supported target is native x86_64 Linux;
explicit cross-compilation and other target triples fail before probe execution.

Schema-1 `facts.json` carries a context ID. `context.json` describes the target,
compiler path/version/binary hash, flags, probe/driver source hashes and the hash of
the preprocessed probe, including expanded standard-library headers. Every driver
invocation rechecks these inputs. Unchanged inputs preserve cached files; changed
inputs or damaged cache artifacts regenerate them. The cache also hashes facts and
the executable. The driver preserves the clang++ invocation name when it is a symlink.

Rust requires an independently supplied expected context, validates schema, required
fields, supported target, integer/layout constraints and binary64, then checks the
facts' context ID. The caller must run the trusted driver for the current build.
Supplying both stale facts and their stale context is not a freshness proof. This
is a trusted build-input protocol, not signed attestation or an ABI compatibility
certificate. Layout assertions in generated C++ remain necessary.

Publication is atomic per file, not transactional across all files. Do not run
concurrent writers into one probe output directory. Context checks detect mixed
facts/context; the next driver invocation repairs interrupted cache publication.

## Scalar preparation boundary

`prepare-scalars METADATA PUBLICATION FACTS CONTEXT` returns the full
`prepared_runtime_api` JSON document for scalar-only publications. It covers:

- All eight integer identities, explicit native/cross-integer construction, all
  33 integer operation bindings and domain-maximum promotion. Signed/uint64
  arithmetic is rejected; exact comparison preserves the original operands.
- Unary, binary, compound, prefix/postfix and shift forms. Compound calculations
  resolve their execution type before checked write-back; shifts retain the left
  type and use a checked count without promoting the right operand.
- Registered native integer adapters, exact range-to-identity matching and
  required public execution-overload dependencies. Native-only operators and
  native mutation receivers are rejected.
- bool_t/null_t value capabilities, configured source defaults and aliases.
  Null has no numeric casts; short aliases follow explicit publication demands.
- float64 operations and native construction, explicit integer/float/bool casts,
  rounding and checked truncation facts. Mixed operators require the complete
  integer type range to fit binary64 exactly; int64/uint64 require an explicit cast.
  Mixed normalization and compound write-back require admitted cast policies.

Typed enums distinguish form, promotion, cast implementation, value kind and
operand adaptation. Preparation computes these facts once. Description serializes
resolved facts; it does not redo promotion or cast admission. Runtime binding
capabilities are code-owned; metadata selects policies/enabled bindings, and
publication selects concrete demands. Integer construction and scalar preparation
share their integer registry and range-conversion implementation.

Unknown scalar fields, unsupported binding changes, duplicate requests, missing
publication dependencies and invalid headers fail. Publications requesting strings,
vectors, carriers or ownership are rejected. Known optional non-scalar metadata
sections may be present but are not semantically validated by this scalar entrypoint.
This is not yet a full-runtime metadata validator. Tests strip those sections when
claiming scalar admission parity; an additional case checks the full default
metadata with scalar-only demands.

`prepare-integer-construction` remains as the narrower first-G2 inspection command,
requiring empty operators, values and short_aliases. Its existing projection is
unchanged. Neither command emits C++; production CLI/artifact compatibility and
runtime emission remain G3/G4 work. No compiler lowering integration is added.

## Exact scalar parity proof

`tests/generator_parity/scalar_parity.py` compares complete prepared JSON documents
with the current C++ generator. No fields are dropped or normalized; JSON object
ordering/formatting is irrelevant, while array order and every resolved fact must
match. A single accepted matrix contains 2,692 signatures, covering every integer
and floating operation form, all admitted ordered runtime pairs, and registered
native adapters. Rejected matrix entries and policy/publication mutations exercise
failure gates. Reordering metadata/publication lists must preserve the same plan.

The comparison includes conversions, losslessness/failure facts, aliases, headers,
operand adaptations, execution/result types, result categories and write-back.
Native widths come from the supplied C++ probe. Pure Rust tests use synthetic facts
and exercise typed policy resolution without C++ or repository metadata files.
Exact diagnostic wording, C++ emission parity and non-scalar preparation are not
claimed by this slice. The frozen G1 baseline is unchanged.

## Reproduce validation

Run from `v0.2/`:

```sh
cargo fetch --locked
cargo fmt --all --check
cargo clippy --workspace --all-targets --locked --offline -- -D warnings
cargo test --workspace --locked --offline
cargo test --workspace --release --locked --offline
cargo build --workspace --locked --offline
cmake --preset default
cmake --build --preset default
cmake --build --preset default --target scpp_layout_facts
cargo run -p scpp-api-tooling --locked --offline -- validate-target \
  build/clang/native-layout/facts.json build/clang/native-layout/context.json
python3 -B tools/layout_probe/test_integration.py \
  --rust target/debug/scpp-api-tooling --cpp build/clang/scpp_api_generator \
  --cxx clang++ --flag=-stdlib=libc++ --flag=-fuse-ld=mold \
  --work build/g2-integration
python3 -B tests/generator_parity/scalar_parity.py \
  --rust target/debug/scpp-api-tooling --cpp build/clang/scpp_api_generator \
  --facts build/clang/native-layout/facts.json \
  --context build/clang/native-layout/context.json --work build/scalar-parity
ctest --preset default
python3 -B compiler/tests/measure_foundation.py
```

`scpp_layout_facts` is an explicit target available with BUILD_TESTING enabled; it
is not a prerequisite of production generation. The integration proof creates its
own narrowed publication and temporary probe copies. It compares all eight integer
types and 104 conversions with C++ prepared facts, compares ownership/native layouts,
checks cache no-op behavior and invalidation by flags/source/headers, and rejects
stale context, missing context, unsupported requests and cross-target execution.
Pure Rust tests use synthetic facts and need no C++ toolchain. The isolated export
copies both Rust packages and verifies workspace tests without any C++ files.

Earlier G2 foundation validation remains recorded in the migration plan. Current
scalar-slice results are recorded there as well; production generation remains C++.
See the [migration plan](../../specs/planning/rust_compiler_migration.md) for the
remaining batches and [G1 inventory](../../specs/planning/api_generator_contract_inventory.md)
for the unchanged production contract and full parity gate.
