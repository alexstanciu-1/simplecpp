# Accounting C versus C++ compile experiment
Doc Status: planning

## Scope and ownership

This is an isolated native-output experiment, not a compiler backend change.
The slice is `MoneyRules` from `examples/accounting_workload/src/money.phs`:
bounded addition, multiplication, basis-point rounding and proration. The
`generated_money.*` snapshots retain the existing legacy S2S method bodies and
AmountResult/MoneyRules declarations from `.prism/generated/src/money.*`.
AllocationResult/MoneyAllocation definitions and bodies are omitted. No application
build files, runtime sources, or compiler sources are changed.

`money.c` manually implements the four operations using fixed-width integers and
caller-owned result storage. It is also compiled as C++23, from identical source,
to control for language mode independently of runtime representation.
`money_abi.h` owns this experiment's boundary; `bridge.cpp` maps the generated
C++ result to that boundary. Neither is a proposed public runtime API.

## Mixed-language build

The technique is a **mixed-language build**, with a **foreign function interface
(FFI)** using the **C ABI**. Separate compilers produce native object files for
the same platform; the linker combines them. Sharing LLVM is not required.

```text
C caller       -- clang   --> driver.o ----+
                                          |
C++ bridge     -- clang++ --> bridge.o ----+--> clang++ link --> executable
Generated C++  -- clang++ --> money.o -----+
Existing C++ runtime --> libruntime.so ---+
```

The executable actually built by the experiment follows this path. Its C caller
uses the same function declarations when linked instead to the all-C or lean-C++
implementation. `extern "C"` supplies C linkage on the C++ side. The link driver
is Clang++ so C++ standard-library dependencies are included. Runtime build cost
is excluded: the existing accounting runtime shared library is reused.

The proposed architectural direction suggested by this experiment is to keep
complex runtime implementation in C++, publish a narrow C-compatible boundary,
and let generated application units call it. That would need a proper runtime-owned
ABI and backend-owned lowering; this experiment does not implement either.

For such a boundary:

- Use fixed-width scalar fields, caller-owned buffers, or opaque handles with
  explicit create/retain/release functions. Keep C++ strings, containers,
  templates and shared-pointer layouts inside their owning implementation.
- Define ownership, nullability, errors, and target/layout agreement explicitly.
  This proof uses a non-null caller-owned result pointer and contains no owned
  pointers. It maps known reason strings to fixed integer codes.
- Catch C++ exceptions at the boundary. The proof bridge maps exceptions to
  reason `-1`; that is a bridge failure, not an accounting validation failure.
- Compile runtime and bridge when they change, then reuse their artifacts for
  application-only edits. A complete build still pays those compilation costs.
- Keep ordinary object/library linking as the first proof. Cross-language LTO
  and toolchain-version compatibility are separate questions.

## Reproduction and methodology

Run from the repository root:

```sh
python3 specs/planning/results/accounting_c_vs_cpp_2026_10_18/run.py
```

Requires Clang/Clang++ and the existing accounting runtime shared library at the
path recorded in the runner. Temporary PCHs, objects, binaries and trace files
are built in a private `/tmp` directory and removed afterward. All application
source and object compilations are serial. Seven measured repetitions per variant
per optimization level follow an untimed warm-up; order is deterministically
shuffled each round. The filesystem cache is warm, object outputs are rebuilt,
and neither ccache nor a build cache participates. Wall times include compiler
process startup, preprocessing, frontend, backend and object writing; they exclude
linking, PCH creation and process execution. PCH creation and bridge compilation
are reported separately. No CPU affinity or cold-filesystem-cache claim is made.

Flags: C17 or C++23, `-fPIC -g -pipe`, separately `-O0` and `-O2`. Runtime-backed
C++ selects the PHP language target. Call-depth instrumentation is disabled for
this isolated computation comparison; original guard invocations remain in the
snapshot. PCHs contain only `<scpp/lang/php.hpp>`, not application declarations,
and are freshly built with matching flags before each optimization group.
This is not the existing application's full Ninja invocation or app PCH.

A separate `-ftime-trace` run per variant records Clang's aggregate frontend,
backend and template categories. These instrumented runs are excluded from the
seven wall-time samples. Categories can overlap; do not sum them as independent
costs. See [Clang's command reference](https://clang.llvm.org/docs/ClangCommandLineReference.html#cmdoption-clang-ftime-trace).

Behavior validation runs the same C caller against all three implementations at
both optimization levels. An independent Python integer oracle checks valid,
invalid, overflow-bounded, negative, tie-rounding and signed-64-bit-extreme inputs.
The test enumerates pairs for addition/multiplication/rate and triples for
proration over 27 selected boundary values. It compares every result's amount,
validity and reason, rather than just a checksum.

## Interpretation limits

The C implementation preserves the tested computational result, **not the full
source-language object model**. Generated C++ allocates a shared AmountResult
object and stores a runtime string; C writes a plain result record. Allocation
failure, shared identity, aliasing, mutation through shared handles, destruction,
reflection and general exceptions are not equivalent between those designs.
The numerical products are bounded before multiplication and fit signed 64 bits.
This is why replacing the full backend with this small C translation would not
be justified by a passing result test.

The identical-source C versus lean-C++ control answers the language-mode question.
The runtime-backed comparison answers a broader representation/header question.
A very large ratio against a tiny C translation unit is not a whole-application
speedup forecast. This sample does not measure container-heavy ledger operations,
full accounting builds, incremental rebuild fan-out, runtime execution speed,
peak memory or cold runtime builds.

## Initial design conclusion

The useful distinction is **generated application representation and runtime
header exposure**, not just C syntax versus C++ syntax. The lean-C++ control can
use the same narrow C ABI without requiring a C backend. A later experiment
could preserve existing runtime-owned object semantics behind opaque handles and
compare application-unit compile cost and runtime cost. This would test whether
the benefit survives reference counting, containers and error propagation.

The current mixed-language executable is a linkage/behavior proof. It does not
show that converting a complete accounting unit to C is semantics-preserving,
nor that a cold mixed-language build is cheaper after including runtime and
bridge construction. Keep the runtime reusable, and place bridge implementations
in a compiled runtime component rather than injecting their C++ definitions into
each generated C unit.

## Measured results — 2026-10-18

Clang 18.1.3; AMD Ryzen 5 PRO 4650G with Radeon Graphics. Linux-6.6.87.2-microsoft-standard-WSL2-x86_64-with-glibc2.39.

Seven warm-filesystem, serial object compilations; median milliseconds.

| Variant | `-O0` | `-O2` |
| --- | ---: | ---: |
| C | 51.15 | 65.77 |
| lean_CXX | 52.97 | 67.86 |
| generated_CXX | 4990.90 | 5175.75 |
| generated_CXX_PCH | 1885.70 | 2195.58 |
| bridge_PCH | 1784.98 | 1916.38 |

Runtime-only PCH creation: 5.738 s at O0; 4.243 s at O2 (one observation each, excluded above).

Separate instrumented trace, seconds:

| Variant / optimization | Frontend | Backend | ExecuteCompiler |
| --- | ---: | ---: | ---: |
| C / -O0 | 0.0058 | 0.0047 | 0.0127 |
| lean_CXX / -O0 | 0.0068 | 0.0043 | 0.0133 |
| generated_CXX / -O0 | 13.4108 | 0.1275 | 13.5880 |
| generated_CXX_PCH / -O0 | 1.8148 | 0.1094 | 1.9685 |
| bridge_PCH / -O0 | 1.7633 | 0.0485 | 1.8534 |
| C / -O2 | 0.0052 | 0.0191 | 0.0262 |
| lean_CXX / -O2 | 0.0064 | 0.0204 | 0.0288 |
| generated_CXX / -O2 | 4.9087 | 0.4410 | 5.3982 |
| generated_CXX_PCH / -O2 | 1.7673 | 0.4387 | 2.2479 |
| bridge_PCH / -O2 | 1.7874 | 0.2091 | 2.0352 |

All 21,870 cases passed for each of three implementations at each optimization level: 131,220 result comparisons total.

The reused-PCH generated C++ object took about 37× the C compile time at O0 and 33× at O2. Identical lean C++ was only about 3–4% slower than C in these observations; the absolute difference was about 2 ms and is too small to establish a robust language-mode advantage from this run alone.

The bridge cost about 1.8–1.9 seconds per compilation with PCH. This cost belongs to runtime/bridge construction and must be amortized, not hidden. Runtime compilation itself was not measured.

Host load averages at start: [2.728515625, 2.314453125, 1.95263671875]; at end: [2.2841796875, 2.544921875, 2.140625]. No host-load isolation was enforced. Results characterize this host and this slice only.

See [raw samples, exact commands, source hashes and trace aggregates](results.json). Temporary paths in recorded commands identify this run; the runner recreates them on reproduction.

Measured wall-time ranges (ms), retained to expose host variation:

| Variant | O0 min–max | O2 min–max |
| --- | ---: | ---: |
| C | 49.70–86.04 | 64.58–71.77 |
| lean_CXX | 51.32–241.72 | 66.84–71.55 |
| generated_CXX | 4771.35–12838.83 | 5079.79–7847.36 |
| generated_CXX_PCH | 1832.97–7617.22 | 2180.15–3405.60 |
| bridge_PCH | 1748.82–2829.98 | 1893.98–7932.26 |

The O0 no-PCH diagnostic trace (13.59 s) was much slower than the unprofiled median (4.99 s). Treat it as an instrumented observation under uncontrolled host load, not a representative latency or a basis for speedup calculations.

## Follow-up: preserve runtime shared objects

[The handle-boundary experiment](HANDLES.md) keeps actual runtime shared objects
and strings, and measures native application recompilation plus linking with
retained runtime adapters. Lowering and compiler-side caching costs are excluded.
