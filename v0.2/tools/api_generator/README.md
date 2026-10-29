# Runtime API tooling

Doc Status: supporting

`scpp-api-tooling` is the sole production runtime API generator and preparation
library. Rust prepares and emits scalars, strings, concrete vectors, nullable/result
carriers and ownership/value records. The runtime and generated consumers remain
C++. The superseded C++ generator, metadata value model and private JSON reader have
been removed; Git history retains the donor implementation at fab676e4f.

## Ownership

The Cargo workspace contains this package and `compiler/`. The compiler depends on
the tooling library, which has no compiler AST, symbol or diagnostic dependency.

- `json.rs`: bounded metadata decoding through Serde/serde_json, retaining raw
  numbers, rejecting decoded duplicate keys, invalid UTF-8 and depth beyond 128.
- `target.rs`: validation of explicitly supplied native C++ target facts/context.
- `integer.rs` and `scalar.rs`: integer identities, conversions, scalar operations,
  promotion, operand adaptations and checked write-back facts.
- `runtime.rs`: composed string/vector/carrier plans and family dependencies.
- `ownership.rs`: record families, qualification conversions and measured layouts.
- `emission.rs` and `emission/`: rendering prepared facts into concrete C++ artifacts.
  Emission does not repeat semantic admission or promotion resolution.
- `main.rs`: CLI input/output; `emission::publish` owns artifact freshness/publication.

Rust builds independently of generated headers, the C++ runtime and CMake. There is
no build script or FFI. CMake invokes Cargo for production generation; Cargo never
invokes CMake. The workspace pins Rust 1.99.1 and dependency versions in Cargo.lock.
CMake retains explicitly supplied CARGO_HOME/RUSTUP_HOME through optional
SCPP_CARGO_HOME/SCPP_RUSTUP_HOME cache entries for subsequent builds.
Eight build jobs and mold are configured. Private Rust/C++ implementation templates
and standard containers are permitted; generated public include closures remain
free of templates and standard-library storage types.

## Setup and production commands

Install the pinned Rust toolchain (including rustfmt/Clippy), Clang/libc++, Ninja,
mold and Python 3.10+. Cargo must be on PATH. Fetch packages once with network
access; normal builds are locked and offline. Run from v0.2/:

```sh
cargo fetch --locked
cmake --preset default
cmake --build --preset default
ctest --preset default
```

CMake builds the release tooling binary in `build/clang/cargo/`, validates native
facts in `build/clang/native-layout/`, and generates runtime artifacts under
`build/clang/generated/`. Other configurations have independent outputs.
`BUILD_TESTING=OFF` still builds the generator and probe: both are production inputs.

The configured launcher preserves the old positional invocation shape, but executes
only Rust with that build's explicit facts/context:

```sh
./build/clang/scpp_api_generator runtime/metadata/runtime.json \
  runtime/metadata/publication.json build/clang/generated --check
```

The direct CLI accepts explicit target inputs:

```sh
cargo build -p scpp-api-tooling --locked --offline
./target/debug/scpp-api-tooling emit-runtime METADATA PUBLICATION FACTS CONTEXT OUTPUT
./target/debug/scpp-api-tooling emit-runtime METADATA PUBLICATION FACTS CONTEXT OUTPUT --check
```

Successful generation/check prints JSON with `status` and `artifacts`; failures
return exit 1 and stderr without success output. Exact old C++ diagnostic wording
and its human-readable success summary are deliberately not preserved. Artifact
paths within OUTPUT, file bytes, inventory behavior and prepared JSON are preserved.
All inputs are prepared before writing. An obsolete/invalid old inventory rejects
before mutation. Check mode never writes. Ordinary publication is per-file, not a
transaction, and unlisted files are left alone. Do not run writers concurrently into
one output directory.

## Inspection and scalar projection

The same library also provides:

```text
validate-target FACTS CONTEXT
prepare-runtime METADATA PUBLICATION FACTS CONTEXT
prepare-scalars METADATA PUBLICATION FACTS CONTEXT
prepare-integer-construction METADATA PUBLICATION FACTS CONTEXT
emit-scalars METADATA PUBLICATION FACTS CONTEXT OUTPUT [--check]
```

These are projections/inspection of shared owners, not alternative production
implementations. Scalar emission delegates to the same renderer as full runtime
emission. The narrow integer command rejects operators/values/short aliases; scalar
commands reject non-scalar demands. Full preparation validates every current metadata
family, even optional unrequested metadata. Source admission, symbols, evaluation
order and compiler lowering remain outside this tooling library.

Native integer and floating behavior, string conversions, vector casts, carrier
states/extraction, inline value traits and ownership qualification retain their
existing family contracts. Ownership's historical `/./` header components remain
accepted; other family header validators reject them. Tightening that policy is
separate work.

## Native facts and rebuilds

`tools/layout_probe/probe.cpp` uses standard C++ headers only. The Python driver
measures integer ranges/layouts, binary64 and smart-pointer layouts; fingerprints
include compiler identity, flags, probe/driver source and preprocessed headers.
Every CMake build rechecks the context and asks Cargo to check its dependency graph.
Unchanged checks preserve output timestamps, so runtime generation/compilation does
not rerun. Metadata, publication, tooling binary and target-fact changes invalidate
generation. Target/compiler/header changes are re-probed.

Rust validates facts against an independently supplied expected context. Supplying
matching stale facts and stale context is not freshness verification: the current
build must run the trusted probe driver. Layout assertions remain in generated C++.
This is native x86_64 Linux generation, not cross-compilation, signed attestation or
a sealed portable ABI. Regenerate/rebuild when changing toolchains.

## Validation and retirement evidence

Before retirement, exact C++/Rust artifact parity passed for 128 accepted runtime
cases and 19 accepted scalar cases. Rejection checks covered 249 runtime and 537
scalar cases. The scalar matrix contains 2,692 operation signatures and was compiled;
existing scalar consumers ran against Rust output. Ownership variants include
mutable/const subsets and inline payload capabilities.

`tests/generator_parity/baseline.json`, `runtime_baseline.json` and
`scalar_baseline.json` retain independently captured C++ artifact hashes. They are
regression fixtures, not generated production inputs. No old binary is needed to
run them. Changes to the contract require reviewed fixture changes, not automatic
recapture. The fixtures describe the measured x86_64 Clang/libc++ target.

CTest exercises frozen artifacts, malformed inputs, CLI freshness/inventory behavior,
native-probe caching/invalidation, generated public headers, compile-fail cases and
all retained runtime consumers, including ownership layout/lifetime/allocation
proofs. C++ preparation/JSON implementation tests were retired with their owners;
Rust tests and the frozen corpora retain the relevant tooling contracts.

```sh
cargo fmt --all --check
cargo clippy --workspace --all-targets --locked --offline -- -D warnings
cargo test --workspace --locked --offline
cargo test --workspace --release --locked --offline
cmake --preset ubsan
cmake --build --preset ubsan
ctest --preset ubsan
python3 -B compiler/tests/measure_foundation.py
python3 -B tests/build_cutover.py
```

The isolated Rust export includes nested emission modules and needs no C++ files.
See the [migration record](../../specs/planning/rust_compiler_migration.md) for the
cutover validation and remaining compiler work. Runtime hash work resumes only after
this single-generator cutover.
