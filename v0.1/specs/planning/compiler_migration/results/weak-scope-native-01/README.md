# Weak-scope native regression proof
Doc Status: planning

The local `$scope` in LLVM_Preparation_Run::prepare_instance hid the C++ `scope`
type in checked_object_cast template arguments, including its own initializer.
Renaming it to `$function_scope` fixes all three errors without changing casting,
weak locking, or ownership semantics. No generator/runtime changes were needed.

Reproduce from the repository root:

```sh
python3 compiler/my-try/tools/native_validate.py \
  --target-checkout /tmp/scpp-native-244 \
  --candidate-revision d8ddde93b04d0e23d295e30f662c3a81b0d50fd1 \
  --results compiler/my-try/build/native-weak-scopes-01
```

Use a fresh results directory. The candidate is PR #244 plus the existing
portability fixes and weakref_get STAN registration; the verified pin is unchanged.
Full target hashes and build logs are retained in the local results directory.
Source hashes, commands/timings and outcomes are saved here.

The normal STAN-enabled native build and incremental rebuild passed. All 142 exact
PHP/native comparisons passed: 48 valid programs compiled/executed with expected
results and 94 rejections. The driver also checks repeated compilation and recovery.
STAN has zero blocking diagnostics, 159 advisory errors and 47 warnings.
Style checks, weak_fields.php and ast_invariants.php passed separately.

This proves the active compiler's weak access paths with their owners alive. It
is not a standalone expiration test or proof that all model cycles are removed.
