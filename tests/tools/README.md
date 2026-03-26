php tests/tools/run_tests.php reset
php tests/tools/run_tests.php reset --level=level_01
php tests/tools/run_tests.php reset --suite=runtime --level=level_01
php tests/tools/run_tests.php run --jobs=12
php tests/tools/run_tests.php run --level=level_02 --jobs=12
php tests/tools/run_tests.php run --test=functions_005_reference_param_basic
php tests/tools/run_tests.php run --include-disabled

php tests/tools/run_tests.php run --suite=runtime --jobs=12
php tests/tools/run_tests.php run --suite=runtime --test=runtime_ownership_001_shared_unique_weak
php tests/tools/run_tests.php reset --suite=runtime
php tests/tools/run_tests.php run --include-disabled


## Runtime sanitizer runs

```bash
php tests/tools/run_tests.php run --suite=runtime --san=address,undefined --jobs=12
php tests/tools/run_tests.php run --suite=runtime --san=address,undefined,leak --test=stress
```


## Runtime gate

```bash
php tests/tools/run_tests.php gate --suite=runtime --jobs=12
```

This is the locked runtime gate: baseline runtime suite, then the full `address,undefined,leak` runtime suite.
