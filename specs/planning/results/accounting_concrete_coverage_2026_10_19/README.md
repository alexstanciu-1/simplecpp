# Accounting concrete-substitution coverage audit
Doc Status: planning

Tested revision: `40d0ba8278e0bb2f5969e608c7d748f5c6269cf1`.
Source: unchanged `examples/accounting_workload/`, 15 PHS files; source hashes are
in `summary.json`. This is the small repository app, not the historical expanded
515 KiB fixture or the hand-converted accounting experiment.

## Build result

- Ordinary backend: preparation, publication, Clang compilation/link and execution
  succeed; `CHECKS passed=998 failed=0`, `ACCOUNTING_WORKLOAD_PASS`.
- General concrete mode: `prelower_templates=true`,
  `concrete_type_substitution=true`, `runtime_template_abi=false`. Preparation
  succeeds; C++ emission rejects the `AssetRegister` hash property initializer:
  `prelower_templates: container operation requires an admitted family adapter`.
  No concrete application is published or submitted to Clang.
- Baseline uses the existing native builder, `--no-pch -O0`, default debug disabled.
  Initial build compiled 19 units; rerun reused those units. This is a coverage
  check, not a comparative performance benchmark or a native compiler rebuild.
- CLI `--prelower-templates` alone still selects historical accounting recipes;
  using that flag would not test the general concrete-substitution implementation.

## Independent fragment audit

The host-only probe first emits the ordinary program, then reuses its 285 existing
prepared fragment owners. It invokes the existing renderer with a fresh concrete
engine/program for each owner. Host reflection accesses the private renderer for
this diagnostic only; there is no new AST traversal, semantic registry, prepared
fact owner, or production recovery/fallback behavior.

241 fragments emit; 44 reject. Each fragment stops at its first error, so the
counts are a lower bound on failures, not a count of all unsupported expressions.
The 241 are **emission-only**, not independently compiled or linked proofs.

| First failure | Fragments | Evidence / owning path |
| --- | ---: | --- |
| Container family adapter | 12 | Eleven hash initializers (including property defaults); one hash foreach in `SalesService::total_outstanding`. Construction/iteration still select `CPP_Concrete_Operations`, the old vector-only recipe path. |
| Binary family adapter | 27 | E.g. string comparison in `AssetRegister::acquire`; binary dispatch still calls the old integer-only adapter for noninteger operations. |
| Noninteger checked cast | 3 | `AssetRegister::depreciate`, `AdjustmentBook::release_prepayment`, `BudgetBook::add`; integer-to-string formatting is exercised by this app. |
| Indexed compound assignment | 1 | `MoneyAllocation::weighted`; explicit rejection remains in `CPP_Index_Write`. |
| Runtime call adapter | 1 | `PurchasingBook::bill_key`, selected runtime binding `core.strlen`; old recipe callable routing only admits vector count. |

`summary.json` lists every failing owner and its first stack frame. Full probe
outputs and generated baseline artifacts are under `/tmp/accounting-concrete-coverage`.
The script's PASS means the diagnostic ran, not that concrete compilation passed.

## Explicit remaining template exposure

133 of the 241 emitted fragments contain direct runtime template spellings.
Observed spellings are `scpp::shared_p<T>` and `scpp::shared<T>` (ordinary source-class
storage and allocation). Source class handles currently pass through the native
wrapper path even while their integer/container fields use concrete bindings.
This is partial substitution, and must not be reported as template-free output.

This lexical count is not a preprocessed-template census. Successful fragments
also retain runtime headers such as `scpp/shared_p.hpp`, `scpp/memory.hpp`,
`scpp/string_t.hpp`, `scpp/bool_t.hpp`, and `scpp/keyed_path.hpp`. Header exposure
and helper instantiation must be re-audited after whole-program emission succeeds.
No silent fallback was introduced or enabled to obtain a successful concrete build.

## Next work, ordered by the observed blockers

1. Connect construction and iteration to the general concrete operation/binding
   contracts, reusing prepared initialization and iteration facts. Do not add hash
   recipes to the old vector-specific worker.
2. Route noninteger operators, conversions and runtime calls through their existing
   selected decisions/bindings. String operations on unchanged native string types
   should not be rejected merely because concrete substitution is enabled elsewhere.
3. Extend indexed compound updates through existing prepared place/evaluation-order
   handling and concrete operation interfaces.
4. Complete nominal source-class ownership substitution/access/allocation, then
   audit native helper/header exposure. A class representation is not fully lowered
   while its application handle remains `shared_p<T>`.
5. Retry whole-program emission and native compilation; generated interfaces,
   provider interoperability and source/layout cycles may expose further failures.
   Preserve focused allocation/copy and nested-reference proofs during repairs.

These are integration gaps, not evidence that a new AST or parallel type registry
is needed. No backend/application behavior changed during this audit.

## Reproduce

```sh
python3 specs/planning/results/accounting_concrete_coverage_2026_10_19/audit.py \
  --results /tmp/accounting-concrete-coverage-new
```

Uses the shared 12-slot runner with FPM/OPcache preference. FPM could not bind its
local socket in this sandbox, so the permitted CLI fallback was used. The native
builder runs as a CLI process. Generated results include full fragment traces;
committed evidence retains the compact summary rather than duplicate generated C++.
