# my-try ownership refactor proof
Doc Status: supporting

Recorded 2026-09-25. Implements the current S2S ownership findings in
[the inventory](../../my_try_ownership_inventory_2026_09_25.md), preserving existing
source, synchronization and generation behavior. No LLVM or validation feature
work is included.

## Results

- Full PHP regression: 69 files linted; style passed (70 sources, including the
  native driver template); 19 LLVM and 28 call executions, sample exit 9, and all
  nine generated-C++ S2S executions passed.
- Focused AST, synchronization, pipeline, publication and Model tests passed.
  The collector test now deliberately supplies a canonical name differing from
  the PHS source token, proving that normalization is frontend-owned.
- Native conversion, normal STAN-enabled build, S2S PHP/native output comparison,
  emitted C++ compilation/execution and all 142 regression comparisons passed:
  48 valid executions and 94 rejection/recovery cases. Incremental native build
  also passed.
- STAN reports zero blocking compile errors, 294 advisory errors and 120 warnings.
  The previous integer slice had 117 warnings. Comparing diagnostics after method
  renaming shows additional unknown expression-chain type warnings around extracted
  object casts/token locals; generated catch-variable warnings also move/remove.
  The net increase is three warnings. These remain advisory limitations of the
  candidate analyzer, not a claim of clean comprehensive validation.
- `git diff --check` passed.

## Reproduction and provenance

```sh
python3 compiler/my-try/tests/run.py --results /tmp/scpp-ownership-php-NEW
python3 compiler/my-try/tools/native_validate.py \
  --target-checkout /tmp/scpp-native-244 \
  --candidate-revision unversioned-d8ddde93-overlay \
  --results /tmp/scpp-ownership-native-NEW
```

The same fingerprinted unversioned candidate used by
[the integer slice](../s2s_integer_01/README.md) was used here. It is not a new
verified release pin; reproduction needs a compatible prepared toolchain.
Temporary full logs are `/tmp/scpp-ownership-php-02` and
`/tmp/scpp-ownership-native-01`.

`native_source_hashes.json` records the converted snapshot, including its generated
host-independent test driver `main.php` (not the repository host entrypoint).
After the snapshot, an extra EOF blank line was removed from
`04_analyze/prepare.php`; comparison of PHP tokens excluding whitespace confirmed
no token change. No other production-source differences from the native snapshot
were present at consolidation. Documentation edits happened while tests ran.

## Checkpoints and attempts

The first focused PHP behavior checkpoint comprised `tests/ast.php`,
`tests/incremental.php`, `tests/pipeline.php`, `tests/publication.php`,
`tests/model.php`, then `tests/s2s.php /tmp/scpp-ownership-s2s-01` after creating its
output directory. Source hashes are saved in `first_php_checkpoint_hashes.json`.
An initial S2S invocation omitted that directory and emitted file-write warnings;
it is not counted as a successful output proof. The corrected invocation and full
runner completed successfully.

The first full PHP runner stopped at the style gate during extraction; formatting
was corrected before the successful second run. One native conversion/build attempt
reached full success, with no native source corrections needed. Recorded conversion
was 0.610 seconds; the clean native build was 61.356 seconds. Raw selected commands
and durations are in `build_commands.json`; overall authoring-phase wall time was
not measured. These durations are verification observations, not benchmarks.
