# Checked canonical integer power

Doc Status: supporting

compiler.power owns exact operand/result identity, checked runtime binding and
compound availability. The generator emits admission and lowering through the
existing binary plan; no promotion rows admit other representations. The canonical
int64 runtime helper uses checked unsigned magnitudes and exponentiation by squaring.
It represents abs(INT64_MIN) without signed negation and avoids squaring an unused
factor. This mines the donor algorithm, not its compiler structures or wrapper ABI.

Power adds one binary enum identity and uses existing AST nodes/facts. Its precedence
is above prefix operators and it associates rightward. Prefix operands parse through
the power level; parentheses still require no retained grouping node. Preparation
visits operands once and does not constant-fold power.

Compound admission now receives the operator. Generated per-operator permissions
allow power and ordinary arithmetic to be enabled independently. Existing compound
snapshots and write-back perform calculation before storage; reference RHS effects
survive failures. No new writable locations or value-producing assignments exist.

## Structured runtime-error debt

The user approved the current runtime convention for this slice: std::runtime_error
messages power_negative_exponent and power_overflow. The normative donor contract's
structured code, component = scpp::pow, and operator = ** fields require a
separately designed runtime error API. Do not imply those fields currently exist.
Keep exact code strings and calculation behavior when that API is introduced.

## Verification

tests/power.php and tests/power_native.py cover all flavors, precedence/grouping,
same-type-only admission, compound/self/reference writes and for headers. The native
observer independently computes bounded results with wider arithmetic, checks both
signed boundaries, huge exponents for -1/0/1, exact error messages, unchanged failed
destinations and preserved RHS writes.

Retained sessions test provider body edits, signature errors, repeated failure,
restoration, deletion/re-addition and no-op reuse; emitted artifacts are compared
against fresh compilation. Lowering purity and one-pass preparation are checked.

Raw proof: build/power-proof-2026-11-06/results.json; 12 native fixtures pass.
PHP fixture generation and incremental verification: 120.11 ms.
O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime, fresh instrumented app
objects. Single main-fixture observations:

| Flavor | Native wall ms | Frontend ms | LLVM ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 150.59 | 14.37 | 14.10 | 70 |
| php | 124.99 | 10.07 | 10.26 | 59 |
| js | 137.70 | 12.79 | 10.69 | 62 |
