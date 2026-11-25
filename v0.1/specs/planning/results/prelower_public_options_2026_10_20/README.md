# Public prelowering option consolidation
Doc Status: supporting

Base: `b2d17914`, plus this slice.

`cpp_lowering_options::prelower_templates` now selects the general concrete path
without a second setting. CLI already delegates to that owner, so no independent
CLI dispatch or type/operation registry was introduced. The former
`runtime_template_abi` and `concrete_type_substitution` option fields remain deprecated
and inert for source compatibility. Historical internal implementations and comparison
harness cleanup remain separate work; this does not claim those old harnesses exercise
their original modes on the current checkout.

## Proofs

```sh
python3 compiler/my-try/tests/prelower_null_output.py --results FRESH
python3 compiler/my-try/tests/prelower_contracts.py --results FRESH
```

Public CLI and API publish byte-identical manifests for the same source using only
their single prelowering selection. The CLI publication rejects a runtime-PCH build,
then builds and executes at O0 without PCH, producing `[p1:p2]2`.
Its complete two-unit closure audit reports zero template declarations and zero
class/function instantiations. Warm build compiles and links nothing.

CLI unsupported callable input explicitly rejects and leaves source output non-ready.
Choosing ordinary mode explicitly succeeds with an `allowed` template policy.
Repair and re-enabling the flag restore the exact concrete manifest.

The migrated lifecycle proof checks unchanged semantic preparation, fragment reuse,
ordinary/concrete restoration, deprecated-field neutrality, body edits, demand
retirement, unsupported-input recovery and discovery-order stability.
The existing 21 backend rejection/recovery cases now use only the public API flag;
they and all 10 closure-coverage tests pass.

Logs: `/tmp/prelower-public-options-20261020` and
`/tmp/prelower-public-contracts-20261020`. Shared runner uses PHP CLI fallback after
FPM socket denial. No O2 run, fresh accounting run, new timing comparison or native
compiler rebuild was performed. Full runtime coverage and consolidated accounting
acceptance remain open.
