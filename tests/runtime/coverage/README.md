# Runtime test coverage target

Runtime-only coverage is considered sufficient only when all three layers exist:

1. per-feature correctness tests
2. sanitizer test runs
3. dedicated stress/churn tests

## What I would consider sufficient coverage

### A. Per-feature correctness tests
Small targeted tests proving expected behavior for:
- construction
- copy/move
- assignment
- reset/clear
- access
- comparison where relevant
- destruction/lifetime transitions

Target:
- the planned runtime suite, roughly 190 tests

### B. Sanitizer test runs
Run the runtime suite with at least:
- AddressSanitizer
- UndefinedBehaviorSanitizer
- LeakSanitizer where available

Purpose:
- detect use-after-free
- detect invalid memory access
- detect UB on exercised paths
- detect non-circular leaks

### C. Dedicated stress/churn tests
Repeated high-volume state transitions for the risky runtime areas:
- `shared_p`
- `unique_p`
- `weak_p`
- `value<T>`
- `nullable<T>`
- `vector_t` and other runtime containers

Target:
- about 20-30 stress tests

Purpose:
- expose leaks hidden by short runs
- expose lifetime corruption after repeated transitions
- expose container/storage instability
- expose ownership bugs that only show under churn


## Runtime gate

The runtime gate is now the required validation command:

```bash
php tests/tools/run_tests.php gate --suite=runtime --jobs=12
```

A runtime change is not considered ready unless both passes succeed:
- full runtime suite
- full runtime suite with `address,undefined,leak`

This gate includes positive runtime tests, stress tests, and the small compile-fail invariant bucket.
