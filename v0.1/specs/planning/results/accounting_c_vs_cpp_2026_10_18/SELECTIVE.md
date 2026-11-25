# Selective C-like lowering comparison
Doc Status: planning

2026-10-18. Hand-written native representation experiment, not a compiler implementation.

## Question and controls

Can we retain concrete C++ classes, methods, constructors/destructors, automatic
cleanup, and sequencing lambdas while keeping application rebuilds close to C?
All simplified variants use the same `money.c` bounded arithmetic and the same
precompiled `handle_runtime.cpp` adapter, preserving actual shared AmountResult
objects and runtime strings. Original generated C++ is the prior legacy S2S
accounting extraction, not current my-try output.

- C_handles: existing explicit create/release control.
- CXX_handles_control: identical C source explicitly compiled in C++ mode.
- CXX_RAII_statements: concrete non-template owning class; ordinary ordered statements.
- CXX_RAII_lambdas: same class and algorithm, eight immediately invoked lambdas
  sequencing kernel calls and runtime writes. This is a small proxy for emitter
  expression sequencing, not a transformed my-try program or large lambda stress test.
- CXX_RAII_exposed_PCH: identical statement variant with generated_money.hpp included
  and the runtime PCH loaded. Runtime declarations/template definitions are visible
  even though the algorithm still uses the concrete adapter.
- generated_CXX_PCH: original generated C++ body and runtime PCH.

No PCH for the four narrow-interface variants. Clang 18, C17/C++23, PIC, debug
information, seven randomized serial warm repetitions per optimization level;
no LTO, object cache, or lowering timing. PCH/adapter/driver construction is
outside application timing, and hashes/mtimes verify retained artifacts unchanged.
The reusable C++ support still has template compilation cost on its own cache miss.

## Median milliseconds

| Variant | O0 compile | O0 compile + link | O2 compile | O2 compile + link |
|---|---:|---:|---:|---:|
| C_handles | 56.46 | 200.56 | 104.40 | 252.16 |
| CXX_handles_control | 57.67 | 198.44 | 105.31 | 253.78 |
| CXX_RAII_statements | 65.04 | 203.85 | 109.65 | 261.21 |
| CXX_RAII_lambdas | 67.37 | 209.59 | 113.65 | 267.86 |
| CXX_RAII_exposed_PCH | 1689.51 | 1834.63 | 1702.21 | 1848.46 |
| generated_CXX_PCH | 1842.60 | 1984.68 | 2196.18 | 2342.36 |

All six variants passed 21,870 numerical oracle cases at each optimization level
(262,440 comparisons). All five handle variants passed existing alias, mutation,
retention and reason checks. `profile_selective.py` separately verifies concrete
copy/move and survivor validity after exception unwinding; it does not prove absence
of all leaks or allocation-failure semantics. Runtime execution performance was not
benchmarked. Existing bounded-input/ABI-failure limitations in HANDLES.md still apply.

## Interpretation

Concrete C++ ownership and the small number of sequencing lambdas retain nearly all
of the application compilation benefit. Removing ordinary C++ language constructs
is not the first priority supported by this experiment. The exposure control restores
most of the O0 compilation cost without changing the application algorithm.

This control bundles PCH loading, generated declarations and any triggered template,
inline-code and debug work; it does not isolate parsing or template instantiation by
itself. Separate traces in selective_profile.json describe frontend activity without
contaminating timed runs. Trace categories may overlap and must not be summed.

Owning source-level generic resolution/specialization in our compiler is compatible
with keeping ordinary C++ output. Emitting concrete types and selected operations
would let application units avoid generic runtime headers. Existing C++ runtime
implementations can remain behind those concrete operations as an incremental step;
this experiment does not implement complete runtime template/metaprogram lowering.
No vector/hash/callable specialization, broad inheritance, or large application
workload is covered, so this does not establish that templates are the only expensive
feature across the language.

## Reproduce

`python3 specs/planning/results/accounting_c_vs_cpp_2026_10_18/run_selective.py`

`python3 specs/planning/results/accounting_c_vs_cpp_2026_10_18/profile_selective.py`

Sources: concrete_amount.hpp, concrete_money.cpp, concrete_checks.cpp and the prior
handle experiment. Raw timings/commands/environment: selective_results.json.
The timing harness gained the standalone concrete ownership check after the recorded
run started; those checks were executed separately by profile_selective.py. Measured
compile commands and bodies did not change.

## Separate O0 frontend trace

| Variant | Frontend ms | Pending instantiations ms | Backend ms |
|---|---:|---:|---:|
| Concrete RAII statements | 11.39 | 0.01 | 11.46 |
| Concrete RAII lambdas | 16.96 | 0.01 | 14.58 |
| Concrete RAII + exposed runtime PCH | 1697.63 | 1645.18 | 16.07 |
| Original generated C++ + PCH | 1915.69 | 1698.21 | 107.37 |

These are single instrumented compilations, separate from the seven-round timing
medians. Pending instantiation is nested within frontend time, not additional time.
The exposed control reports 1620.41 ms Total InstantiateFunction and 396.11 ms
Total InstantiateClass; these categories overlap. Large individual events include
std::vformat_to and scpp::hash_detail::packed_table<scpp::mixed_t>::set. Thus the
runtime umbrella/PCH exposes substantial work unrelated to the four money operations.
Total ReadAST is 3.08 ms for this control, but this category is not a complete account
of all lazy PCH deserialization costs. Do not interpret it as total PCH overhead.

For this sample, pending template work is the dominant observed frontend cost.
The evidence supports prioritizing concrete specialization/operation boundaries
before replacing ordinary C++ lifetime machinery or expression sequencing. It does
not prove that our compiler can already lower all runtime templates, or establish
their one-time specialization-build cost.
