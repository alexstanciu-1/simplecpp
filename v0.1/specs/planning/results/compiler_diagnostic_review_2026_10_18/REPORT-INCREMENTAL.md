# Incremental compilation and error recovery review
Doc Status: supporting

2026-10-17. Review only; no compiler fixes. PHP-hosted compiler, CLI executor, generated C++ checked with Clang 18. Shared CommandRunner/run_tasks, 12 slots. Evidence is temporary, outside repository.

## 1. Scan-only compilation can publish stale C++ as ready (confirmed documented limitation)

Baseline main.phs:

```php
$x = 1; return $x;
```

Edit, preserving byte length and mtime:

```php
$x = 1; return $y;
```

On the SAME Compiler instance, call exec_cpp(new Output_Publication(output)) for baseline and edit; close each publication in finally. The edit passes preparation/emission, produces byte-identical artifact JSON, and Output_Publication::require_ready succeeds. update_cpp([], publication) also succeeds with identical stale output. A second reproduction replaces the baseline with `$x = 1; return )x;`, which is syntactically invalid and also gets stale ready output.

Probe sets mtime to 1700000000 before both builds and calls clearstatcache after each write, so PHP's cached stat results are not the cause. Both strings are 18 bytes. Each publication worker is created BEFORE compilation, per protocol.

Controls: update_cpp([absolute_main_path], publication) rejects the undeclared y / syntax error and marks output pending. Reset/fresh compilation rejects both. Clang accepts stale output because it still represents the valid baseline, not the invalid disk source.

Owner: compiler/my-try/01_prepare_inputs/module.php:106 compares only loaded mtime and size. compiler/my-try/compiler/compile.php:343 scan-only sync_live forces notifications only for non-disk sources. Stronger change detection is explicitly deferred in docs/planning/incremental_strategy.md:115. This is known detection debt, not a newly discovered preparation invalidation bug. Applies when a disk change has no explicit notification and metadata compares equal.

Evidence: stale.php; run-stale.py; batch19/{semantic,syntax}/result.json, stale-artifacts.json, cpp/, and batch19/logs/. Run a standalone reproduction with `php specs/planning/results/compiler_diagnostic_review_2026_10_18/stale.php FRESH_EXISTING_DIRECTORY semantic` (or syntax); it creates src/out under that directory.

## 2. Notified edit reaches Clang through the previously found hash-key gap

Baseline:

```php
$h hash<int, string>; return 0;
```

Edit:

```php
$h hash<int, float>; return 0;
```

Explicit notification: preparation, emission, and publication all succeed. Emitted C++ changes and instantiates hash_t<int_t<>, float_t>. Clang rejects: `static assertion failed: hash_t<T, T_KEY> requires a supported typed key family`.

Fresh compilation reproduces the same failure. This is the earlier type-capability preparation gap, not stale output. Baseline and repaired output pass Clang. Evidence: batch20/hash_key/result.json, clang-results.json, clang-logs/, cpp-edit/entry.cpp.

## 3. Duplicate declarations still pass preparation, but publication rejects

Baseline main.phs `return 0;`, a.phs `class A {}`. Add b.phs `class A {}`. Both incremental and fresh preparation pass. Disk publication rejects `Duplicate publication artifact` and readiness stays pending. A subsequent memory-only cpp() returns duplicate artifacts, which does not establish ready disk output. This confirms the previously reported duplicate-type boundary, not stale output reuse after failure. Repair succeeds.

Evidence: batch18b/duplicate_class/result.json.

## Recovery coverage and limits

30 valid-baseline, explicitly notified edit/retry/repair sequences: 26 reject at preparation, 2 reject during sync (syntax/tokenization), 1 rejects at publication (duplicate class), 1 emits and fails Clang (float hash key). All 30 repairs restore exactly the baseline artifact JSON digest and ready publication.

Covered changed/missing signatures, changed return/argument types, required arguments, symbol/file deletion, undefined locals, duplicate functions, method removal/access, abstract/final declarations, interface requirements, constructor signatures, field access/types, added invalid files, entry deletion, trait bodies/signatures, import target changes and simultaneous errors. Every parse/preparation failure withheld C++ output and ready status, blocked direct cpp(), and remained rejected on retry and fresh compilation.

Evidence: incremental.php, cases18.json, cases20.json, batch18b/summary.json, batch20/summary.json. An invalid-baseline constant-syntax probe in batch20 was excluded from the 30. An earlier batch18 used a wrong logical entry name and is also excluded. The initialized hash probe in batch18b rejects initialization shape and is not counted as the floating-key capability proof; batch20/hash_key supplies that proof.

No additional incremental-only semantic invalidation leak was found in this sample. No native compiler rebuild, comprehensive race matrix, or native object-cache review was performed. Old disk files remaining after a failure are not treated as reusable output when readiness correctly says pending.
