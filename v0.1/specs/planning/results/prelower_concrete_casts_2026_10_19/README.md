# Concrete cast integration
Doc Status: planning

## Scope and ownership

Concrete explicit runtime casts, text conversion and integer value casts now consume
existing prepared conversion decisions through `CPP_Runtime_Operations`. The native
bridge invokes the ordinary conversion renderer. Its invocation-local implementation
context selects native outer types with concrete physical arguments through the
existing type-binding owner. Binary unpacking/result spelling now uses this same
context instead of its private spelling helper. No semantic, registry, runtime or
`simple_cpp_02` changes are involved.

Inputs bind once by const reference; bridges borrow their addresses. Opaque outputs
use existing final-storage construction. Identity, ownership transfers and wrapper
construction/extraction are not generalized by this slice.

## Reproduction and results

```sh
python3 compiler/my-try/tests/prelower_substitution.py --casts --results /tmp/prelower-casts-FRESH
python3 compiler/my-try/tests/prelower_expressions.py --initializers --results /tmp/prelower-casts-legacy-FRESH
```

Concrete proof PASS (165.73 s); legacy initializer proof PASS (147.50 s).
Four changed production PHP files pass the portability checker. Shared runner uses
CLI fallback because sandbox FPM sockets are unavailable. These are generated-program
proofs, not a native compiler rebuild or a compile-speed benchmark.

Matched direct/concrete O0, O2 and ASan/UBSan builds (optional elision disabled) have
identical output. No PCH or debug information is enabled. Cast workload counters are
10 allocations / 146 bytes / 10 frees, with zero tracked payload lifecycle activity
on both paths. Existing binary/initializer/nested-reference and throwing-copy proofs
also match; this establishes no extra boundary allocations/copies in these workloads.

Added cases cover string/integer conversions, numeric and boolean casts, narrowing
and widening, and implicit text conversion. Exact successful effects are checked.
Invalid text and narrowing overflow suppress the following binary operand, initializer
value, or later entry as appropriate. Exception diagnostics run outside allocation
measurement: runtime stack capture can allocate differently with a bridge frame.
This does not claim arbitrary ownership/provider cast coverage.

No-op replay, discovery-order stability, cast demand removal/restoration and option
switching pass. Body edit builds one unit, reuses five and all three layout batches.
Layout edit builds six units and one layout batch, reusing two. Restore compiles and
links nothing, reusing all three layout batches.

## Accounting checkpoint

All three cast failures disappear. Independent prepared-fragment probes emit 281/285;
two indexed compound updates, one strlen call and one hash iteration remain blocked.
Whole-program emission still stops before Clang. Existing nominal shared-template
exposure remains; no complete concrete accounting build or speedup is claimed.

[Summary](summary.json) records compact counters, full-output hashes, native cache
results and remaining fragment diagnostics. Full logs are in
`/tmp/prelower-concrete-casts-1/logs`, `/tmp/prelower-concrete-casts-legacy/logs` and
`/tmp/prelower-concrete-casts-checks/logs`. Accounting uses the existing
`accounting_concrete_coverage_2026_10_19/probe.php` and `fragments.php` scripts against
unchanged `examples/accounting_workload`.
