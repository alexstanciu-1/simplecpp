# Replaced-code removal inventory

Doc Status: planning
Date: 2026-10-29
Baseline: a3ad4e463. Scope: v0.2 only. The inventory below records the pre-removal
assessment. The user subsequently authorized the recommended Rust sequence and
12-file C++ foundation retirement slice. That slice is now implemented; the
publication fixture was relocated without changing its content. No local output
cache was removed. The remaining generator/runtime retention decisions still apply.

## Findings at the inventory baseline

There is no self-contained tracked implementation ready for deletion solely on the
basis of complete Rust replacement. Tokens and source snapshots have Rust successors,
but the still-unported C++ token-sequence builder depends on their C++ implementations.
The production API generator and its JSON reader remain necessary.

| Area | Paths relative to v0.2 | Status and removal condition |
| --- | --- | --- |
| Token representation and tags | compiler/include/token.hpp, compiler/include/token_kind.hpp, compiler/src/token.cpp, compiler/tests/token.cpp | Responsibility replaced by compiler/src/token.rs and Rust foundation tests. Retain while C++ token_sequence depends on it. Rust keeps compact representation and nonzero tag vocabulary; it intentionally uses Rust ownership/errors rather than C++ API parity. |
| Immutable source snapshots | compiler/include/token_buffer.hpp, compiler/src/token_buffer.cpp, compiler/tests/token_buffer.cpp | Responsibility replaced by compiler/src/source.rs and Rust foundation tests. Rust has source IDs, retained Arc snapshots and checked byte spans. This is an adapted contract, not an identical interface. C++ token_sequence still consumes token_buffer. |
| Sequence construction/publication | compiler/include/token_sequence.hpp, compiler/src/token_sequence.cpp, compiler/tests/token_sequence.cpp | Not replaced. Ordered, nonoverlapping source-contained tokens, retained source ownership and single-use publication still need a Rust owner and proofs. |
| C++ compiler build/boundary proof | compiler/CMakeLists.txt, compiler/tests/boundary.cmake; add_subdirectory(compiler) in root CMakeLists.txt | Retire together with the C++ compiler model after sequence replacement. These currently build the model and its separate runtime publication and register five CTests. The Rust compiler does not require this build. |
| Compiler runtime publication metadata | compiler/publication.json | Still used by tests/generator_parity/corpus.py and tools/layout_probe/test_integration.py, as well as compiler CMake. Move to an intentional fixture location and update both harnesses when retiring the compiler publication target. Preserve its bytes/meaning and G1 baseline expectations. |
| C++ API generator and JSON support | tools/api_generator/*.cpp, *.hpp; tools/support/json/*.hpp | Nine files, 133,862 bytes; still the production generator. Rust covers JSON loading, target facts and integer construction only. Keep until remaining preparation, emission, full parity and build cutover pass. |
| Generator preparation/consumer tests | tests/*preparation.cpp, publication CMake scripts, generator_parity/ | Keep. They test the production engine or generated consumer contract, not disposable duplicate implementations. Review preparation-only C++ tests at the generator cutover; retain behavioral coverage in Rust. |
| Runtime, ownership proof, native probe | runtime/src/, runtime/metadata/, tests/ownership/, tools/layout_probe/ | Active responsibilities retained in C++. Rust compiler independence does not supersede the public runtime ownership API. The native probe is deliberate C++ measurement, not migration residue. |
| Workspace configuration | Cargo.toml, Cargo.lock, rust-toolchain.toml, .cargo/config.toml; package manifests | Active, not redundant. Former compiler-local lockfile/toolchain/config were already removed or moved in G2. |

The C++ compiler implementation and its C++ tests comprise ten files / 25,180 bytes.
Including compiler/CMakeLists.txt and compiler/tests/boundary.cmake gives a future
12-file deletion batch, plus one metadata relocation and root build/documentation
edits. Do not delete seven superseded token/source files independently while keeping
a broken C++ sequence target.

## Disposable local outputs

These are already ignored and are not tracked source. Measurements are local disk
usage at inventory time, not repository size or a promise of reclaimed Git history.

| Directory | Approximate disk use | Treatment |
| --- | ---: | --- |
| compiler/target/ | 63 MiB | Obsolete default output location after workspace consolidation; removable now if no process uses it. |
| target/ | 228 MiB | Current Cargo cache, binaries and rustdoc outputs; regenerable, but useful for incremental work. |
| build/ | 190 MiB | Regenerable CMake/Ninja builds, tests and probe facts; useful for current validation. |
| runtime/generated/ | 472 KiB | Regenerable runtime publications; keep available during development, never commit. |

No outputs were removed. Retaining current caches is independent of retaining
superseded source code. Unknown files in ignored output trees should be checked
before a future destructive cleanup.

## Recommended deletion slice at the inventory baseline

1. Implement and validate Rust token-sequence construction/publication against the
   adopted invariants; do not copy C++ state wrappers or moved-from protocols.
2. Retire the complete C++ compiler foundation in one coherent batch (12 files).
   Relocate compiler/publication.json as an intentional parity fixture and update
   its two Python consumers. Remove the root CMake compiler subdirectory hook.
3. Update the compiler README and migration checkpoint so C++ reference sections
   no longer describe an active build. Git history retains the removed code.
4. Prove Rust workspace tests and isolated export, all retained C++ runtime/generator
   checks, G1 parity and G2 native-probe integration. The five compiler-specific
   CTests should disappear deliberately, with sequence behavior covered in Rust.
5. Keep the C++ generator/JSON removal as a separate post-G3/G4 cutover batch.

Evidence: inspected the tracked file inventory, CMake dependency wiring, Rust
module exports and foundation proofs, C++ sequence implementation, and publication
consumers. This inventory does not claim a fresh test run or new semantic parity.
