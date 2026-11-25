# Compiler-owned source operations in native templates
Doc Status: supporting

```sh
python3 src-runtime-preparation/tests/source_operations/run.py
```

Run from the repository root using the configured runtime/Clang 18 and `ld.lld-18`.
`--workspace /absolute/path` overrides the stable default
`generated-runtime/proofs/source-operations/`. Builds use separate mode
directories, fixed inputs and four parallel jobs. An exclusive fixture lock prevents
overlapping runs; the same artifact names are replaced on rerun.

Read the [results and limitations](../../../docs/details/source_operation_adapter_proof.md).
The fixture implements no production compiler/preparation feature. Concrete types,
operation names and expected observations are test data only.

| File | Responsibility |
|---|---|
| `native.hpp` | Imported managed-field witness, layout-only source description, forwarding native element adapter, actual `scpp::vector_t` workload and explicit payload copy-in/copy-out bridge |
| `harness.cpp` | Independent field liveness/order assertions, operation counts and executable entry |
| `prepare.php` | Existing `Definitions`/`Bridge`/`Clang_Toolchain`/`Metadata` primitives emit native LLVM and measured facts; no package is published |
| `run.py` | Fixture JSON, complete source operations in LLVM, four build modes, one operation-body edit, capability/link rejection checks |

`native.ll` contains declarations for the six source operations. Only
`compiler-1.ll` / `compiler-2.ll` define them. These are generated test instructions,
not output of our compiler's AST/type/lifecycle processes. Their field offsets and
imported operation ABI come from `measured.json`.

The layout witness is never instantiated. An actual native adapter contains aligned
byte storage and forwards its special members; it does not declare native managed
members. LLVM composes the source field operations. The raw-payload roundtrip also
tests LLVM-owned stack values, without treating their addresses as live adapter
references. These distinct representation contracts are not yet expressible through
the production preparation request schema.

Mode directories retain native/compiler bitcode, executables, command logs and
observations. `copy-forbidden.log`, `missing.log` and `duplicate.log` retain expected
rejections. `report.json` is written only after all checks pass. The run does not
benchmark execution overhead, prove arbitrary native type compatibility, or extend
source-language eligibility. Ordinary self-contained runtime packages are unchanged.
