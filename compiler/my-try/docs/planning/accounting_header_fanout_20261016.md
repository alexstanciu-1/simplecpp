# Accounting header fan-out and Clang-unit investigation
Doc Status: planning

Date: 2026-10-16. Investigation only: compiler, authored application, runtime,
Ninja graph and baseline artifacts unchanged. Evidence concerns the native compiler
used by the preceding [edit matrix](native_edit_matrix_20261016.md), not newer
compiler development elsewhere. Runtime modifications remain outside ordinary
application-build targets.

## Main finding

All **89** objects rebuilt by the AccountingCheck field addition directly include
`types/n4163636f756e74696e67436865636b.hpp`. There is no intermediate application
umbrella causing this fan-out. The remaining four generated units and three runtime
providers do not depend on that header. The type headers of its consumers already
use forward declarations for shared-handle signatures.

A diagnostic copy of the complete published tree replaces only the AccountingCheck
header with `struct record_AccountingCheck;`. Using the existing Clang flags and a
private runtime-only PCH, actual object compilation succeeds for **69/89** units.
The other **20** fail for real complete-type operations. This establishes excessive
complete-definition exposure in this fixture, beyond a lexical guess about includes.

The 69 compiled diagnostic objects link with the 27 unchanged baseline objects,
using the same Mold selection/threads. The resulting complete program passes
**998 + 857 = 1,855 self-tests**. All 295 baseline publication files/metadata retain
identical hashes to the retrieved pre-probe snapshot. This is a baseline dependency
feasibility proof, not an implemented emitter change or a new edit-latency result.
A future production slice still needs edited-layout and semantic boundary proofs.

## Necessary versus excessive exposure

| Classification | Objects | Evidence |
| --- | ---: | --- |
| Definition owner/construction | 1 | `core.phs` defines AccountingCheck methods and constructs instances. |
| Direct AccountingCheck member access | 19 | Clang reports member access into incomplete AccountingCheck. |
| Definition not needed for this generated unit | 69 | Full object code generation succeeds with only its forward declaration. |

The 19 member-access units are allocation_desk, assets, banking, calendar, checks,
cli, closing, credit_desk, expense_desk, ledger, payroll_desk, procurement_desk,
project_desk, purchasing, sales, self_test, stock, treasury_desk and warehouse_desk
(all under `src/`, `.phs`). They inspect fields such as `accepted`, `code`, or
`detail`. Their current generated operations require the real definition. Do not
remove those dependencies or change representation to reduce the count.

The 69 include ordinary validators returning `accounting_failure()`/`accounting_success()`,
check units passing validation results to WorkloadChecks methods, and
`workflow_checks`, `closing_checks`, `business_checks`, `document_rules` and
`reports`. In workflow_checks, for example, the generated call stores an
`scpp::shared_p<record_AccountingCheck>` result from a desk operation and passes it
to `WorkloadChecks::method_accepted`. It never accesses the AccountingCheck payload.
Member access to other classes still legitimately requires their definitions.

The 69 units account for about **30.0 seconds of summed compiler-job wall durations**
out of **46.4 seconds** in the original shared-declaration run; the remaining 20
account for about 16.4 seconds. These are overlapping job durations, not CPU time
or predicted wall-time savings. A future narrowed graph could reduce this case
from 89 to 20 compilation actions, but its elapsed time has not been measured.

## Existing lowering owner

The backend already distinguishes `declaration` and `complete` requirements:

- `CPP_Types::representation_for()` can emit a source-record forward declaration
  while preserving `scpp::shared_p<T>` spelling (`05_backend/cpp/types.php`).
- `CPP_Declarations::signature_requirement()` requests `complete` for ordinary
  non-reference implementation parameters/results. `selected_call()` uses that
  same choice for lambda results and argument temporaries (`declarations.php`).
- `CPP_Declarations::type()` defaults to complete. Local storage generation also
  requests complete unless it is an alias (`generate_storage_value`, `generate.php`).
- Prototype signatures and bare nominal-handle fields already use declaration-only
  requirements. Thus no new global header scheme or semantic model is needed.

The missing distinction is **completeness of the stored shared handle versus
completeness of its payload**. A copied/passed/returned handle can operate with an
incomplete payload. Constructing it, accessing members, defining its methods,
performing ancestry-sensitive conversions, or instantiating a layout-dependent
wrapper can require completeness. Keep existing conservative template/by-value
rules until their specific contracts are proven. Do not globally change the
complete-type default or identify exceptions by class names.

The smallest proposed production direction is a backend-owned use-site requirement
policy reused by implementation signatures, call temporaries and locals, with
operation sites retaining their complete requirements. This is a proposal only;
compiler incremental/MT internals and automatic unit splitting remain out of scope.

## Slowest application units

Existing unprofiled dev002 runs: Clang 18.1.3, application O0, Ninja 8 jobs,
Mold 3.0.0 / 8 threads, runtime PCH reused for the shared-declaration edit.
Numbers are per-unit medians across the three prior trials, seconds.

| Unit | Full build | Shared-declaration edit | Full definition needed here? |
| --- | ---: | ---: | --- |
| workflow_checks | 2.869 | 2.853 | No |
| closing_checks | 1.818 | 1.807 | No |
| checks | 1.276 | 1.288 | Yes |
| business_checks | 1.228 | 1.229 | No |
| cli | 1.178 | 1.181 | Yes |
| purchasing | 1.026 | 1.030 | Yes |

Across 267 application compile observations in that edit (89 units × 3), p50 is
0.370 s, p80 **0.683 s**, p95 1.181 s and maximum 2.855 s. Full-build application
p80 is 0.684 s and maximum 2.872 s. This fixture meets the 3-second p80 target;
it is not a general workload guarantee. Runtime-provider compilations are excluded.

workflow_checks is the last compilation to finish in all three shared-declaration
runs. It also dominates the five-object signature/layout cases: 2.867/2.804-second
medians, whereas large_cli takes only 0.759/0.751 seconds. A small number of objects
can therefore still be limited by one large application unit.

## Isolated diagnostic Clang traces

Three additional serial, profiled compiles use unchanged baseline C++ and equivalent
flags with a private runtime PCH. These are single diagnostic observations; they
are not substitutes for the unprofiled parallel medians above.

| Unit | ExecuteCompiler | Clang frontend | LLVM backend |
| --- | ---: | ---: | ---: |
| workflow_checks | 3.076 | 2.258 | 0.798 |
| closing_checks | 1.782 | 1.324 | 0.446 |
| customer_profile control | 0.342 | 0.265 | 0.068 |

Frontend accounts for roughly 73–78% of ExecuteCompiler here. Template event
categories overlap; never sum their nested durations as independent costs.
workflow_checks reports 1.547 s total function instantiation and 0.958 s class
instantiation; closing_checks reports 0.869/0.573 s respectively.

Large nested events include `scpp::shared<record_WarehouseDesk>` (~0.229 s),
`scpp::shared<record_ProcurementDesk>` (~0.225 s), and their `std::make_shared`
implementation chains. closing_checks includes BankReconciliation construction
(~0.186 s) and InventoryBook construction (~0.118 s). Generated desk definitions
contain inline typed-hash member initializers; constructing those classes exposes
that template work at the construction site. Moving suitable construction work
out of line is a future hypothesis requiring ownership/lifetime and runtime-cost
proofs, not an established speedup or an instruction to alter the runtime.

workflow_checks contains 116,813 bytes / 2,486 generated lines, 366 immediately
invoked lambdas, eight direct `scpp::shared<T>()` sites and 33 direct type headers.
closing_checks contains 56,845 bytes / 1,168 lines and 161 such lambdas. These
counts describe output complexity; they do not attribute timing to lambdas alone.
The cheap customer_profile control still instantiates dynamic hash/initializer
helpers through runtime headers, suggesting a secondary header-exposure audit.
Keeping the runtime fixed does not imply every application unit needs its broadest
header surface. No runtime-header/PCH composition change is proposed yet.

## Recommended order and limits

1. Discuss the bounded shared-handle completeness policy first. It addresses proven
   unnecessary recompilation, including both slowest units, without splitting or
   adding accessor indirection or changing application behavior.
2. After an authorized implementation, repeat layout/signature/body cases and
   compare actual rebuilt units, full linking, behavior, timings and memory.
3. Separately evaluate repeated construction/template exposure in slow units.
   Automatic splitting remains deferred; no need to activate it for this proof.

No new headline latency, peak-memory improvement, or 89-to-20 build timing is
claimed. The diagnostic did not apply the layout edit to the candidate graph.
Its 20 negative compilations are intentional classification evidence. The initial
probe was invalid because trial-1's old PCH had a stale runtime-header mtime after
later trial restoration; all its results were discarded. A fresh private PCH fixed
that diagnostic setup, without changing baseline artifacts or disabling validation.

Evidence: [per-unit inventory, timings and trace summaries](accounting_header_fanout_20261016/analysis.json),
[full-program proof and baseline hashes](accounting_header_fanout_20261016/link-proof.json),
and [reproduction notes](accounting_header_fanout_20261016/README.md).
