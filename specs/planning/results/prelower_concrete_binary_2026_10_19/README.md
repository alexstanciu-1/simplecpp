# Concrete binary-operation integration
Doc Status: planning

## Ownership and scope

Prelowering only: no changes to analysis, registry semantics, runtime operators or
`simple_cpp_02`. Concrete mode now forwards existing eager binary decisions through
`CPP_Runtime_Operations`, rather than the old integer-only accounting recipe.
The middleware invokes the same `CPP_Generator::render_eager_binary` used by ordinary
C++ emission, in a native context that cannot recursively request another bridge.
This preserves one owner for operation spelling/semantics and can consume future
operator work without maintaining a second operator table here.

The existing emitter still converts and snapshots operands in source order. The
bridge borrows their addresses; an opaque operand designates its native outer object
with the concrete physical arguments selected by `CPP_Types`. Native string/bool/enum
values retain their ordinary bindings. Concrete results use existing final-storage
construction callbacks. No additional heap box or argument-value copy is introduced.
The same immutable operation requests support fragment replay and live demand removal.
Other ABI operation kinds retain their existing transport until explicitly integrated.
Short-circuit logical and presence handling remain on their existing emitter paths.

## Proof

```sh
python3 compiler/my-try/tests/prelower_substitution.py --binary \
  --results /tmp/prelower-concrete-binary-FRESH
python3 compiler/my-try/tests/prelower_expressions.py \
  --results /tmp/prelower-binary-legacy-FRESH
```

The binary mode includes the established initializer, indexing, source-field and
member proofs. Added checks cover:

- Long-string concatenation and strict identity; left snapshots survive RHS mutation.
- Boolean strict identity and left-to-right operand effects; logical RHS suppression.
- Integer arithmetic, division/remainder, power, bitwise/shift and three-way results.
- Enum equality/inequality through the existing prepared enum operation.
- Division failure before a later operand, and RHS failure after an earlier effect.
- Binary helper demand removal/restoration without changing semantic preparation.

Direct/concrete output is compared at O0/O2 and with ASan/UBSan and optional elision
disabled. PCH/debug information are disabled. Long-string allocations are measured;
existing tracked payloads retain their copy/move counters. Exception-order probes run
outside the measurement window: runtime stack-trace capture may allocate differently
with an extra bridge frame and is not payload-boundary work.

This does not claim new string ordering, float operators, wrapper admission or
conversion semantics from `_02`, nor exhaustive operator-matrix coverage. Compiler
implementation PHP checks are not a native compiler rebuild. The shared runner falls
back to CLI because sandbox FPM sockets are unavailable.

## Accounting checkpoint

All 29 previously exposed binary-adapter failures disappear. Independent fragment
probes emit 278 of 285 fragments. Remaining first failures: three casts, two indexed
compound updates, one strlen call and one hash iteration. The second compound update
was previously hidden behind a binary failure. Whole-program emission now stops at
the known checked-cast adapter; no concrete accounting executable is claimed.

The first native test attempt failed because its C++ fixture concatenated an include
after a closing brace without a newline. The fixture was corrected; no production
operator behavior was changed in response.

## Final results

Concrete proof PASS (182.51 s); older opaque-expression regression PASS (165.89 s).
Both changed production PHP files pass the portability checker. Every matched
configuration has identical direct/concrete output. The binary workload records
13 allocations / 962 bytes / 13 frees and no tracked payload copy/move activity.
All inherited initializer counter/trace cases, nested-reference identities and
throwing-copy cases also match. Runtime division failures preserve the tested
operand-effect order.

Body edit rebuilt one application unit, reused five units and all three layout
batches. Layout edit rebuilt six units and one layout batch, reusing two batches.
Restore compiled/linked nothing and reused all three layout batches. Binary demand
removal drops the concatenation request; restoring source restores identical artifacts.

[Summary](summary.json) records build results, compact counters and full-output hashes.
Full traces are retained under `/tmp/prelower-concrete-binary-2/logs`; accounting and
portability logs are under `/tmp/prelower-concrete-binary-checks/logs`.
