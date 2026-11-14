# Metadata-driven int64 arithmetic

Doc Status: supporting

The bounded slice implements binary `+ - * / %` on canonical int64 operands in all
three flavors. `int` and `int64` share one identity. Results are int64. The owning
contracts are [integer arithmetic](../../../specs/compact_layout_types.md#7-v02-integer-arithmetic-and-numeric-comparisons)
and [ordered evaluation](../../../specs/expression_evaluation.md).

Integer division truncates toward zero; nonzero remainder has the dividend's sign.
Zero divisors throw the existing runtime domain errors `division_by_zero` or
`modulo_by_zero`. Native signed-overflow limitations remain, including the
unrepresentable `INT64_MIN / -1` division and corresponding native remainder case.
There is no new checked-overflow, wrapping or saturation guarantee. Tests exercise
the defined domain and do not assign expected results to undefined native overflow.

Power, bitwise/shifts, mixed numeric arithmetic and other operand types remain deferred.
Same-type [float64 arithmetic](float_arithmetic.md) now reuses this binary path.
Int64 unary signs now have a separate [bounded slice](unary.md); integer literal
magnitudes retain their existing bound. Floating signed-literal syntax is unchanged.

## One binary path

`comparison_operator` is now `binary_operator`; its existing six IDs remain stable
and five arithmetic IDs are appended. `ast_binary_expression` still owns only its
left/right expressions and operator. Its inherited prepared type fact is enough to
identify the approved operation with the operand type: no new prepared record,
operand list, per-occurrence handler, or copied syntax is introduced.

The parser's existing precedence traversal now orders multiplication/division/
remainder above addition/subtraction, then relational comparisons, then equality.
Each level associates left. Parentheses override that tree shape.

Runtime metadata's `compiler.arithmetic.int64` defines same-type operands, int64
result, native overflow policy and each operator's lowering strategy. Existing
comparison metadata remains separate, but both feed one generated binary handler
on each canonical type definition. Unsupported policies fail generation.

`binary_result()` supplies generated admission/result identity shared by ordinary
expressions and [compound writes](compounds.md); generated `supports_compound($operator)`
separately controls whether a type admits compound syntax. `prepare_binary()` attaches
ordinary expression facts through that query.
`lower_cpp_binary()` consumes the approved operator. `binary_headers()` reports
only required public headers. These replace the comparison-specific methods;
`settle_binary()` replaces the comparison-specific AST setter. Inventory version
is 12. The compiler does not interpret metadata while preparing source expressions.

Preparation visits both children once, in source order, then makes one generated
handler call. There is no constant folding, fixed point, callee-body scan, extra
symbol table or dependency listener. Calls/global references retain their existing
enclosing-body watches. Recursive reset and collection already handle this AST.

Lowering retains ordered by-value operand snapshots where needed; [direct scalar lowering](direct_lowering.md) removes wrappers only for proven-safe expressions. `+ - *` use native C++
operators without a runtime header. `/ %` call `scpp::int64_divide` and
`scpp::int64_remainder`, declared in the generated `scpp/arithmetic.hpp`, whose only
include is `<stdint.h>`. The compiled runtime source delegates to existing private
scalar semantics, preserving their errors. Exception templates/headers do not enter
application includes. For integer division and remainder, the helper takes each prepared operand directly by value, including an admitted widening. Native C++ chooses the order of those arguments. The enclosing expression still treats the helper result as potentially throwing.

## Verification

- `tests/arithmetic.php`: all three flavors; all five operations; precedence and
  associativity; negative quotient/remainder; representable limits; function
  arguments/returns; assignments and loops; metadata-type rejection; one-visit
  preparation; reset/reprepare and lowering purity.
- Retained-session tests change a callee from int to float and back, rejecting then
  recovering without reparsing the caller; body-only and no-op changes reuse it.
  Incremental native emission remains deferred.
- `tests/arithmetic_native.py`: 16 executables, exact output and exit checks,
  including ordered calls, RHS reference mutation, zero-divisor errors, suppression
  of later calls after failure, and a mixed-flavor multifile build.
- `../tests/arithmetic_runtime.py`: standalone public include, template-free closure
  and linked runtime behavior. Generator tests remove capabilities and reject
  unsupported policies. Existing comparison tests remain regression coverage.

The `compiler_arithmetic_argument_order` CTest compiles emitted application units
with the configured compiler at `-O2`. To compare installed compilers and record
their traces, run `python3 -B v0.2/compiler/tests/arithmetic_argument_order.py`
from the repository root. Repeat `--cxx` to select drivers and `--flag` to set
compile flags. The probe links against the existing Clang/libc++ runtime archive.
Acceptance checks both producer calls, their results, and the runtime exceptions.

Native CMake builds run a standalone argument-order probe alongside the layout
probe. Panel Ninja builds run it before compiling application units with their
selected flags, including PCH flags where applicable. The probe can run before
runtime publication because it exercises the private integer divide and remainder
implementations directly. It checks results, zero-divisor exceptions and one read
of each operand. Either argument order qualifies.

Each successful configuration has an atomic JSON record under
`build/clang/native-qualification/` for the default build. Its identity includes
the selected driver path, version and binary digest, the driver-selected C++ frontend,
loaded Clang/LLVM implementation libraries, target, ordered flags, probe source,
preprocessed source and probe driver revision. Changing an identity field triggers
a fresh test. Standard build configurations use a per-configuration index of file
identities, sizes and nanosecond timestamps for the driver, frontend, loaded
compiler libraries, probe and its included headers, explicit PCH and linker.
An unchanged hit requires filesystem metadata checks only; it skips compiler
invocations, preprocessing and binary hashing. Changes trigger full qualification.
Unrecognized flags use the full fingerprint path. A matching record expires seven days after its successful run.
A failed refresh stops the build and retry runs the probe again. The recorded
traces describe these calls on that build configuration. The emitter consumes
the source evaluation contract.

The no-PCH panel and Python benchmark launchers check the same cache in process before each native
invocation. A verified hit skips the Ninja qualification edge for that invocation. On a miss,
expiry or busy cache lock, Ninja runs the regular checker. Standalone Ninja and PCH builds use
the saved, protected graph. Native wall timing includes the launcher check.

To qualify GCC independently on native Linux, run from the repository root:

```sh
python3 -B v0.2/tools/layout_probe/qualification.py --cxx g++ --flag=-std=c++23 --flag=-O2 --cache v0.2/build/clang/native-qualification
```

Commands:

```sh
XDEBUG_MODE=off php v0.2/compiler/tests/arithmetic.php
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/arithmetic_native.py --output v0.2/build/arithmetic-proof-2026-11-04
python3 -B v0.2/tests/arithmetic_runtime.py
```

Focused proof on 2026-11-04: PHP fixture generation, including semantic/incremental
assertions, took 79.824 ms. This is not a pure compiler benchmark.

| Fixture | Native wall ms | Clang frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 158.987 | 18.227 | 20.806 | 13.231 | 5.739 | 67 |
| php | 156.615 | 17.031 | 21.895 | 13.005 | 7.004 | 66 |
| js | 156.536 | 16.227 | 25.467 | 16.598 | 7.034 | 70 |
| multifile | 120.057 | 13.172 | 15.717 | 6.155 | 5.038 | 60 |

O2/g0, libc++, mold, Ninja -j8, no PCH/LTO, fresh instrumented app objects, prebuilt
runtime archive. Trace categories overlap and accumulate across units; they are not
additive wall time. Exception-observer fixtures take 534–925 ms because test-only
catch code includes `<stdexcept>` and `<string_view>`; production emitted includes
remain minimal. Raw fixtures, logs, traces and results are in the output directory.

Regression results: 42 selected compiler/generator CTest checks passed, followed by
the newly registered arithmetic/public-header checks. Runtime-family generation
passed 130 accepted and 247 rejected cases. Frozen runtime artifact expectations
were updated only for the added header and inventory membership; every other
artifact hash was preserved. Legacy G1 input hashes were already stale before this
slice (54/54 differ even after removing the new arithmetic metadata); those input
hashes were not recaptured, and no complete legacy parity-suite pass is claimed.
