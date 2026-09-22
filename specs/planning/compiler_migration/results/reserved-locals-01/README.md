# Reserved local-name preflight
Doc Status: planning

The shared checker/converter rejects C++ keyword locals with source attribution.
Nine rejection cases cover assignments, annotations, loops, catch bindings, traits
and callable-scope isolation; a positive case preserves fields, promoted/ordinary
parameters, uppercase identifiers and string contents. Failure checks assert no
output/cache changes. Existing conversion version hashing includes converter bytes.

Cumulative PHP/tool validation passes for 107 production files. Focused strict
clang++-18 native regressions pass: package syntax 66, provider families 51, scalar
catalog 116 outcomes. Each required one build and zero correction cycles on clean
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`. Command durations and logs are saved.
The package syntax harness also compares 520 retained attribute inputs.

An additional in-memory comparison against the converter at `57d19e35` found
identical emitted output for all 107 current source files after normal trait
expansion. The restriction changes rejection, not successful lowering. Only local
variable spelling changed in three compiler files and sixteen behavioral probes.
No prototype, runtime-preparation or target-side implementation was changed.

Run: `python3 tools/php_portability/validate.py --results FRESH` and
`python3 compiler/tests/STAGE/run.py --results FRESH --target-checkout /tmp/scpp-json-240-probe`
for `package_syntax`, `provider_families`, and `scalar_catalog`.

This checkpoint does not add migrated files or establish complete package ingestion.
