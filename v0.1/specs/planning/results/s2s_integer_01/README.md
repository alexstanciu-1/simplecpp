# Canonical integer, scopes and first C++ S2S proof
Doc Status: supporting

Recorded 2026-09-25. Scope: `compiler/my-try`, first `LIT-INT-001` implementation.
See [slice contract and reproduction](../../../../compiler/my-try/docs/s2s_integer_slice.md).

## Results

- PHP regression run `/tmp/scpp-s2s-regression-03`: 59 PHP files linted, style passed,
  19 LLVM fixtures and 28 call fixtures executed, existing sample returned 9, and
  seven generated C++ cases executed. All passed.
- Follow-up `tests/s2s.php /tmp/scpp-s2s-php` added two independent C++ observations
  of signed-64-bit values and types. All nine cases compiled with Clang 18.1.3 and
  returned their expected status. See cpp_cases.json. The source is test-instrumented
  only in the two `_value` cases; the compiler's generated output is not patched.
- Native compiler: conversion, normal STAN-enabled build, PHP/native S2S output
  parity, C++ compilation/execution (exit 10), and all 142 existing comparisons
  passed (48 valid LLVM programs executed, 94 rejection/recovery cases).
- STAN: zero blocking compile errors, 294 advisory errors, 117 warnings. This is
  native build evidence, not implementation of the deferred validation milestone.
- Host CLI smoke: `s2s.php` generated a program from `$a = 10; return $a;`; Clang
  compilation succeeded and the program returned 10.

Native command:

```sh
python3 compiler/my-try/tools/native_validate.py \
  --target-checkout /tmp/scpp-native-244 \
  --candidate-revision unversioned-d8ddde93-overlay \
  --results /tmp/scpp-s2s-native-06 --resume
```

Omit `--resume` for a fresh results directory. Resume retains prior log directories
and reuses native objects; source conversion and the checks still rerun.

The candidate toolchain directory is an unversioned saved overlay, not a clean Git
checkout or new release pin. `candidate.json` records its actual file fingerprints;
the revision label describes provenance only. The verified release pin is unchanged.
All comparison sources and candidate fingerprints used in the successful build are
recorded in native_source_hashes.json and candidate.json. Temporary build directories
are not permanent artifacts. Reproduction requires a compatible prepared toolchain.

## Development/proof boundary

The first PHP behavioral success was `php compiler/my-try/tests/s2s.php
/tmp/scpp-s2s-php`, before the first native conversion attempt. The initial proof
included canonical identity, inference/reassignment, source purity, regeneration,
failure clearing and generated-program cases. first_php_checkpoint_hashes.json
records the conversion snapshot taken immediately afterward.

Subsequent portability corrections were source adaptations:

1. Use supported `uint32` instead of an unsupported `uint8` field annotation.
2. Keep test output on stdout rather than call an unavailable write helper.
3. End parent traversal with an explicit return recognized by STAN.
4. Use typed candidate snapshots instead of `?? []`, which lowered through mixed.
5. Keep test-driver output modes in branches; candidate top-level return lowering
   cannot consume bare return or the wrapped integer return used in attempted drivers.

Type definitions were consolidated into scope-owned Storage during these cycles.
No generated C++ was edited as a fix, and no STAN bypass was used. Attempts 01/02
failed conversion; 03 stopped at STAN; 04/05/06 reached native compilation and failed.
The final resumed build passed. One resume stopped at already-initialized project
setup; the harness now preserves that configuration. attempts.json records command
wall times and statuses, including these failed attempts. The successful native
build was followed by a passing incremental build and all behavior comparisons.

The PHP regression summary predates only the two additional host value probes and
test-driver/harness retry changes; final production sources are covered by the native
proof. Reserved-name enforcement, JSON imports, composite types, multi-file S2S and
additional literal forms are not claimed.
