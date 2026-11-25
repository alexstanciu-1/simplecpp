# Isolated whole-structure lifecycle probe
Doc Status: supporting

Run from the repository root:

```sh
python3 src-runtime-preparation/tests/lifecycle/run.py
```

Requires the configured runtime headers, PHP, Clang 18 and `ld.lld-18`. Pass
`--workspace /absolute/path` to choose an output folder. The default is the stable,
ignored `generated-runtime/proofs/lifecycle/` directory. Repeated runs
replace the same artifacts under an exclusive fixture lock. Four independent
mode jobs build in separate directories and their results join before reporting.

Read the [findings and limits](../../../docs/details/lifecycle_preparation_proof.md).
This is an executable investigation, not a source-language implementation or a
published runtime package. Concrete fixture identities and event expectations
remain test data; production preparation has no knowledge of them.

## File responsibilities

| File | Responsibility |
|---|---|
| `native.hpp` | Canonical native aggregate definitions, tracing fields, generic operation helpers, imported body declarations and measured scalar-offset facts |
| `reference.cpp` | Direct C++ lifecycle workload and independent C++ implementations of the three body/initializer hooks |
| `harness.cpp` | Event output, value assertions and executable entry |
| `prepare.php` | Prove normal package rejection; use existing preparation primitives to emit raw shell artifacts and measured metadata for this experiment |
| `run.py` | Generate fixture exposure JSON and LLVM bodies/caller, check narrow hook ABI, build four modes, compare both paths with an event oracle, replace one body and verify shell reuse |

## Artifacts and interpretation

`measured.json` contains raw measured contracts, not an accepted-package manifest.
`shell.cpp`/`shell.ll` and `abi.hpp` come from the existing bridge generator.
`body-1.ll`/`body-2.ll` are hand-authored test instructions emitted by the fixture;
their type sizes, alignments, scalar offsets and bridge signatures come from the
measured output. They do not demonstrate AST-to-LLVM lowering of lifecycle bodies.

Each mode directory keeps shell bitcode, both body revisions, direct and bridged
executables, commands, diagnostics and traces. `report.json` summarizes the run.
`package-rejection.log` and `missing-body.log` retain the two expected native link
failures. The first validates the current self-contained-package boundary; the
second supplies instrumentation and deliberately omits application hooks.

The fixture ABI check accepts only its simple default-C-calling-convention
pointer/int32 signatures. A production importer needs the full selected ABI and
semantic contract, including failure effects; this parser is not that importer.

The custom traced declarations are native witnesses, not claims that current
Simple C++ source structs allow custom methods or these field types. The shared
owner case is a correctness check of native ownership invoking an LLVM body; it
does not measure wrapper performance or implement compiler ownership handles.
