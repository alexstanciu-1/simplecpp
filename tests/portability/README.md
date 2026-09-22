# PHP / PHP++ portability proofs
Doc Status: planning

Home for tests of the [portable profile](../../specs/portability/README.md).
`python3 tests/portability/check.py` verifies read-only checking, PHP lint,
cross-file trait lookup, shared rejection behavior and existing-cache discovery.
`collection_snapshots.py --results FRESH_RESULTS_DIRECTORY` proves owned collection
and explicit snapshot-update behavior; add `--target-checkout TARGET_CHECKOUT` for
the pinned strict native proof. See the [contract](../../specs/portability/collection_snapshots.md).
The first fixture and runner are implemented. Run `python3 tests/portability/run.py`
for local/runtime/incremental checks; add `--native` for a real strict PHP++ build
and comparison. See the [implemented subset](../../specs/portability/first_slice.md).

Use expected-result tests for the intended algorithm and PHP/native comparison
fixtures wherever PHP provides a useful approximation. Compare relevant values,
ordered effects, mutation and failures; exact PHP runtime equivalence is not the
goal. Agreement alone does not prove correctness, and intentional implementation
differences must not become false test failures.

Run tests that cannot be made in PHP after conversion: actual multithreading,
synchronization and other native-only behavior. PHP sequential workers can test
work decomposition and result handling but cannot prove thread behavior. Include
target numeric boundaries and lifetime tests when relevant to the intended code.

Converter checks cover local annotation/syntax diagnostics, file-to-file output,
no-op reuse, isolated file edits, source removal/rename, rule/configuration changes
and failed conversion. Do not introduce cross-file symbol resolution to test them.

The [update-context proof](compiler_context/README.md) exercises the first actual
compiler component through PHP, conversion and the selected v0.1.76 target,
including shared identity and independent per-update state.
Keep PHP checks fast for ordinary edits and native proofs focused.
