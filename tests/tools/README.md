Doc Status: supporting

PHP flow tests may declare optional runtime modules under `build.runtime_modules` in their `*.test-info.json`.
The run-tests harness always includes the default modules `json`, `filesystem`, and `datetime`,
then appends any explicitly requested opt-in modules such as `regex`, `mysqli`, or `curl`.

PHP flow tests may also declare a local HTTP server under `build.http_server`:
- `document_root`: path relative to the test source directory or absolute path
- `router`: optional router script path relative to the test source directory or absolute path
- `host`: optional host, default `127.0.0.1`

For `document_root` and `router`, a `source:` prefix means “resolve relative to the original test source directory in the repo”
rather than the temporary mirrored run-tests project. This is useful for normal PHP server fixtures that should not be transpiled as Prism++ source.

When `build.run_args` contains `{{http_base_url}}`, the harness replaces it with the started server base URL for the run stage.

PHP or runtime tests that require outbound network access should set `build.external_network = true`.
Those tests are skipped by default and only run when `tests/tools/run_tests.php` is invoked with `--include-network`.


php tests/tools/run_tests.php reset
php tests/tools/run_tests.php reset --level=level_01
php tests/tools/run_tests.php reset --suite=runtime --level=level_01
php tests/tools/run_tests.php run --jobs=12
php tests/tools/run_tests.php run --include-network --test=curl_003
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
