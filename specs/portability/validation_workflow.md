# Portable-PHP validation workflow
Doc Status: supporting

## Active rewrite proofs

The ready set contains twenty-five input/tokenization/parser production files. The default driver runs their
PHP outcome proof plus independent framework checks. Add `--native compiler
--target-checkout TARGET` to convert and prove all registered rewrite stages natively.
`compiler/tests/run.py` requires the ready set to match registered stage sources.
See [manifest reading](project_manifest_reading.md) for 35 outcomes and
[source discovery](source_discovery.md) for 31 initial + 31 refreshed outcomes, and
[verified reads](verified_source_reads.md) for 20 initial + 20 refreshed outcomes,
[tokenizer](tokenizer.md) for 304 outcomes including retained unit cases, and
[parser foundation](parser_foundation.md) for 414 outcomes plus the reused 5,000-stream unit, and
[expressions](expression_parser.md) for 132 outcomes, and
[file grammar](file_parser.md) for 82 outcomes plus nine host lifecycle assertions.

The old compiler harness/oracles are preserved under
`tests/portability/reference/pre-rewrite/`; replay them from Git branch
`v0.2/pre-rewrite-reference`, not relocated paths. Old oracle totals below are
historical, not active rewrite coverage.

The host driver `tools/php_portability/validate.py` consolidates existing checks
for the current compiler ready set. It adds no conversion rules and does not select
or migrate new compiler files. Python is host test orchestration; the converter
and executable framework remain PHP.

## Fast authoring loop

After editing an authorized portable component, run from repository root:

```bash
python3 tools/php_portability/validate.py --results /tmp/scpp-validation-NEW
```

The results directory must be fresh and outside compiler/tools/tests/.agents source
trees. Nothing is fixed automatically. The driver stages exactly the files in
`compiler/portability.json`, retaining their relative paths, plus the existing
behavioral harness. The stage is a disposable copy, not a second implementation.

It runs:

1. The shared read-only checker: PHP lint, import-free prologues, declarations/traits and conversion.
2. Registered compiler stage proofs under `compiler/tests/`, comparing independently
   specified meaningful outcomes. Currently these cover input preparation, tokenization, parser storage, expressions and file grammar.
3. Scalar-record framework capability checks.
4. The host collection helper tests; native parity has its own runner.
5. Foundation, check-command, prologue and native-framework-installation regressions.

The framework-installation test is a host test; it does not compile native code.
A fast pass is explicitly reported as having no native proofs requested. This is
not the full adopted compiler test suite or proof that all prototype PHP converts.
For arbitrary new source folders, use `check.php SOURCE` and their own behavioral
harness; this driver is deliberately scoped to the ready compiler manifest.

## Selected native proofs

```bash
python3 tools/php_portability/validate.py \
  --results /tmp/scpp-validation-native-NEW \
  --native compiler \
  --target-checkout /tmp/scpp-json-240-probe
```

Both native flags are required together. The target must be a clean checkout at
the exact revision in `compiler/tools/portability_target.json`; no global CLI or
newer workspace target is substituted. After fast checks, the existing native
runner owns conversion, framework assembly, strict build/run and expected-result
comparison. The driver verifies target cleanliness again afterwards.

Select proofs relevant to the change; repeat `--native` to request several:

| Selection | Existing owner |
| --- | --- |
| `os` | Process/lock facade parity; requires the alias-signature fix in `2f0d667f` or a proved successor |
| `compiler` | Registered active rewrite stages: twenty-five production files; manifest 35, discovery 62, reads 40, tokenizer 304, parser foundation 414, expressions 132, file grammar 82 (+9 host lifecycle) |
| `methods` | Named/scalar method boundaries, void, identity and mutation |
| `returns` | Container return copying and nonpublic scalar state |
| `iteration` | Typed map presence checks and by-value iteration |
| `containers` | Explicit nested vectors/maps, typed keys and copying |
| `snapshots` | Owned membership, explicit changed-row copies and shared-row limits |
| `utf8` | Text/code-point versus byte behavior and malformed inputs |
| `records` | Scalar value records, local aliases, independent copies, vector replacement and native layout |
| `traits` | Direct trait expansion, restrictions and incremental cache behavior |

The choices are not aliases for a complete compiler migration test. Native-only
concurrency, lifecycle or API tests still need their own owners as those capabilities
are added. The #231 target features are supplied; full compiler adoption of them remains component work.

## Evidence and failure handling

`summary.json` records running/passed/failed state, selected native proofs, target
configuration, staged source hashes, command exits and durations. Per-stage stdout
and stderr remain alongside staged sources, expected PHP output and a copy of the
driver. Native runners retain their detailed results in `proof-NAME/`.

The first failed gate stops the workflow and returns nonzero. An interrupted process
may leave a running step; only a final passed summary denotes success. Existing
results are never overwritten. Keep build outputs under the disposable evidence
tree or the existing runners' scratch directories, and do not commit binaries.

There is no concurrent-edit snapshot guarantee, automatic import repair, general
test-discovery framework or performance threshold in this driver. Use a stable
working tree while validating; timings are observations, not benchmark claims.

## Historical pre-reset evidence

The [recorded fast run](../planning/compiler_migration/results/validation-workflow-01/fast/summary.json)
and [cumulative native run](../planning/compiler_migration/results/validation-workflow-01/native/summary.json)
passed against the current eleven-file ready set. The native run also passed the
sixteen retained compiler fixtures. Failure-path checks confirmed that an invalid
target stops downstream work and existing evidence cannot be overwritten.

Candidate target adoption can be proved without editing the selected pin:
`tests/portability/compiler_context/run.py --candidate-revision FULL_COMMIT`
accepts an explicit immutable candidate alongside the usual checkout/results flags.
The focused `tests/portability/collections.py` runner requires checkout/results
and defaults to its historical #231/#232 combined candidate. Its optional
`--candidate-revision FULL_COMMIT` selects an exact immutable candidate without
relaxing checkout revision or cleanliness checks.

The fast compiler loop also compares quoted-byte decoding with a frozen original
on 8,593 deterministic inputs (`tests/portability/byte_literals_oracle.php`). The
cumulative native witness checks all byte values and numeric escape truncation.

The cumulative compiler runner now creates isolated filesystem fixtures and selects
the `--filesystem` native support artifact/module for the production source scanner.
Its fast loop also checks the frozen scanner oracle, including same-process metadata
refresh. Host fixture creation remains outside the converted implementation tree.

The fast loop also runs `syntax_access_oracle.php`: 27,560 result/error comparisons
for the adapted structural query owner/trait, including input purity. This is host
evidence for a pending component; it does not add files to the native-ready manifest.
