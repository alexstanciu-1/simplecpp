# Portability status and evidence
Doc Status: supporting

PHP behavior, conversion to PHS, native compiler execution and generated-program
execution are separate claims. The current bounded native checkpoint passes all four.
This does not establish exhaustive language, lifetime, incremental or performance coverage.

## Current checkpoint — 2026-09-30

Native source/toolchain: `8ec5f924`. This is a revision-specific checkpoint, not
certification of later changes. The compiler converted, built and ran successfully
with Clang 18 and `--no-stan`.

The harness compares PHP-host and native-compiler C++ bytes for every one of the 95
valid authored S2S fixtures, then compiles and executes every resulting program.
All pass with their independent expected exit codes. The 15 float-form fixtures also
assert exact emitted spelling, `scpp::float_t` type and native value. All 31 S2S
rejection/recovery cases pass. The separate parked LLVM/sample sweep passes all 142
comparisons: 48 valid programs execute and 94 invalid programs reject. The initial
S2S smoke proof, repeated compilation/recovery and incremental native rebuild pass.

Evidence under
`/tmp/my-try-catalog-native-20260930-eZs6tj/native-8ec5f924-all-valid/`:
`summary.json`, `commands.json`, `logs/` and the per-fixture `s2s-programs/` outputs.
Temporary paths may disappear; reproducible standard harness:

```bash
python3 compiler/my-try/tools/native_validate.py \
  --target-checkout /absolute/path/to/simplecpp \
  --candidate-revision FULL_COMMIT \
  --results /tmp/FRESH_DIRECTORY --no-stan
```

Native checks remain explicitly opt-in. By user decision, bypass v0.1 STAN for this
compiler work; its diagnostic catalog is parked in [review debt](REVIEW.md).
The harness retains its general default; use the explicit flag here. No verified
portability target pin has been changed. The earlier full-run style-gate failure
remains separate from this native pass; style/full PHP suites remain on demand.

## Current authoring boundaries

- Use the [portable PHP guide](../../../../specs/portability/authoring_guide.md).
  Scope locals correctly, preserve one meaning/type per name, and avoid type/member collisions.
- Keep explicit Storage element types and typed nested receivers; value containers
  are not aliases simply because PHP records share identity.
- Acquire actual weak fields through their supported binding; documentary annotations
  alone do not implement expiration. Required staged fields remain required.
- Required nullable extraction and shared derived/base upcasts are supported native
  boundaries. Keep actual narrowing casts distinct; do not add dummy initialization
  or casts to satisfy parked STAN diagnostics.
- Legacy lowering now handles the compiler's public inherited constructors, accessor
  bridges and file-local class imports. Shared identity works across compatible
  derived/base handles. Non-public constructor policy and unrelated-interface identity
  remain outside those bounded repairs; see [runtime rules](../../../../runtime/specs/spec.md).

The [incremental plan](../planning/incremental_strategy.md) records remaining model
and validation debt. Earlier failures and intermediate fixes are retained in the
[historical checkpoint ledger](../archive/conversion_review_checkpoints.md), not current blockers.
