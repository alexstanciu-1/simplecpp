# Accounting application header exposure experiment
Doc Status: planning

Diagnostic experiment at commit `3b358f7e`; no production backend changes.

## Results

Three alternating-order sequential rounds, Clang 18.1.3, C++23, O0,
debug disabled, existing eager runtime PCH. All 16 application units are freshly
compiled each round; existing runtime, layouts and middleware objects are reused.

| Application input | Median app compilation | Reduction from matched baseline |
| --- | ---: | ---: |
| Current prepared output | 10.892 s | — |
| Narrow indexing header | 8.462 s | 22.3% |
| Narrow string-output header | 9.288 s | 14.7% |
| Both narrow headers | 7.409 s | 32.0% |

Combined savings: 3.483 seconds (1.47x compilation speedup). Individual reductions
must not be added. Median linking is 0.517 / 0.496 / 0.493 / 0.486 seconds respectively.
The string-output bridge costs **0.570 seconds once** to compile, excluded from the
reusable application figures. All 12 executables pass **998 checks, zero failures**.

## Controlled changes

- Index: replace `scpp/keyed_path.hpp` in eight application units with the existing
  non-template `require_write_key(const string_t&)` no-op definition from
  `scpp/index_write.hpp`, plus its string type include. Existing concrete operation
  declarations and compiled implementations remain in use.
- Output: replace `lang/php/support/php_string.hpp` in three units with a concrete
  string-output declaration. Redirect ordinary string echo calls to an out-of-line
  `const string_t&` forwarder calling the original runtime implementation. Integer
  echo already uses its generated concrete bridge.
- Both: apply both changes. Keep generated headers, PCH, flags, runtime and existing
  middleware objects unchanged. Original generated output is untouched; copies live
  under `/tmp/accounting-header-impact`.

These small shims are diagnostic artifacts, not proposed family-specific engine
implementations. Production work belongs in existing runtime binding/declaration
selection and backend emission owners.

## Interpretation and limits

The two broad headers alone have a material cost despite compiled middleware and
PCH reuse. This measures their combined parsing, instantiation and downstream code
compilation effects, not pure template-instantiation time. The remaining output is
not template-free: source-class shared handles and string/runtime internals remain.
The experiment does not explain the entire gap to the handmade accounting output.

Timings wrap `CommandRunner.run`, including process-launch/runner overhead. Compare
variants within this experiment; do not subtract them directly from the earlier
10.033-second benchmark measured through its internal compiler proxy. Baseline
samples range 10.263–11.156 seconds; combined samples range 7.374–7.547 seconds.
No O2 work was performed. Accounting checks prove this workload, not every possible
runtime behavior or a comprehensive allocation/copy-counter proof.

## Reproduction and evidence

`summary.json` preserves samples and medians. `run.py` preserves the exact experiment;
it depends on the earlier `/tmp/accounting-timing/results.json` command manifest and
its published output and cached objects. The accompanying headers/forwarder record
the diagnostic replacements. Raw per-unit timings and command logs are retained in
`/tmp/accounting-header-impact/{results.json,logs}`. The script uses one sequential
task through the shared test runner; measured task duration was 115.75 seconds.

See the [preceding profile](../prelower_accounting_gap_2026_10_19/README.md)
and [original timing comparison](../prelower_accounting_timing_2026_10_19/README.md).
