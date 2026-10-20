# Concrete runtime free-call integration
Doc Status: planning

## Scope and ownership

The current accounting slice routes runtime free calls through the registered C++
binding and existing prepared call contracts. It does not name strlen, count, vector
or hash in the adapter. Single observations preserve one full expression, including
temporary-owner lifetime. Value/reference calls reuse `CPP_Declarations::selected_call`
and existing native argument transport, preserving snapshot/consume/default ordering.
Receiver calls retain their existing member path. Physical argument identity now has
one shared callable-binding owner for members and free calls.

Middleware uses native outer implementations with concrete physical arguments. A
provider result can be that native outer object or an already-concrete payload (for
example unpacking a parcel of a concrete vector). A middleware-only generic result
adapter checks the physical C++ result and either returns it directly or constructs
the native result in final concrete storage. This adds no family-specific engine
code, semantic pass, AST mutation or parallel registry. Application interfaces remain
concrete; the result-selection template is compiled only in middleware support.

Reference results, variadics, multiple observations, mixed observation/value parameter
lists, and provider conventions requiring different physical template deduction are
not established here. Exact native/concrete result matching is checked by Clang;
arbitrary runtime functions are not claimed supported merely because they are registered.

Full runtime coverage is deferred. Later work will expand the runtime's existing
metadata and registry contracts; this slice does not undertake that project. `_02`
and accounting sources remain unchanged. The user permits accounting workarounds for
unrelated issues, but the remaining failures belong to prelowering.

## Proof and reproduction

```sh
python3 compiler/my-try/tests/prelower_substitution.py --observations --results /tmp/prelower-runtime-calls-FRESH
python3 compiler/my-try/tests/prelower_expressions.py --initializers --results /tmp/prelower-runtime-calls-legacy-FRESH
```

The new mode includes cast/binary/initializer/indexing/field/member proofs. Added cases
cover empty vector, hash, nested vector and tracked-payload vector counts; byte-length
strlen on multibyte text; temporary source-owner fields; temporary container/string
results; and an observed generic provider returning a concrete payload. Exact effect
order is checked. Passing-mode decisions are unchanged: strlen retains its catalogued
value boundary; count observes. Sanitizers check temporary-owner lifetime. Removed
calls drop both observation and runtime-call requests; restoration reproduces output.

The full proof passes (186.11 s). Legacy initializer/opaque-expression regression
passes (148.52 s). Six changed production PHP files pass portability checking.
Generated-program execution is not a native compiler rebuild. Shared runner uses CLI
fallback because sandbox FPM sockets are unavailable. Both paths use no PCH/debug;
sanitizer builds disable optional elision.

## Allocation results and limitation

O0, ASan/UBSan and O2 with `-fno-assume-sane-operator-new` match at 26 allocations /
475 bytes / 26 frees, with zero tracked payload lifecycle activity. The controlled
O2 build also disables optional elision. This establishes boundary parity for the
tested calls; inherited payload-copy/nested-reference checks pass too.

Stock O2 direct code uses 25 allocations / 467 bytes / 25 frees, while concrete code
uses 26 / 475 / 26. The ordinary inline path retains an allocation-elision advantage:
one allocation / eight bytes in this workload. This is explicitly measured and not
claimed fixed. The earlier compound workload's three-allocation limitation remains
open as well. Test flags are not changes to production compilation settings.

Body edit rebuilds one unit, reuses five and all three layout batches. Layout edit
rebuilds six units and one layout batch, reusing two. Restore compiles/links nothing
and reuses all three batches. No-op replay, discovery-order stability and lowering
option switching also pass.

## Accounting checkpoint and development corrections

Accounting emits 283/285 independent prepared fragments. The strlen failure is gone;
GeneralLedger::validate and SalesService::total_outstanding still fail at iteration.
Whole accounting still stops before Clang; existing nominal shared-template exposure
remains. This is not a whole-application compilation or timing result.

The first fixture attempt used an incorrect native test namespace; its multibyte
length expectation was also corrected to byte length. Early integration intercepted
member calls and assumed strlen was an observation. Inspection of the existing
prepared/catalog contracts corrected both: member dispatch stays separate, and value
calls use the ordinary sequencing owner. No runtime semantics were changed to pass
these probes.

[Summary](summary.json) retains full-output hashes, compact counters, native-cache
results and accounting failures. Logs are in `/tmp/prelower-concrete-observations-4/logs`,
`/tmp/prelower-observations-legacy/logs` and `/tmp/prelower-concrete-observations-checks/logs`.
