# Accounting compiler workload
Doc Status: supporting

A headless, deterministic strict PHP++ application for exercising the legacy
PHP S2S pipeline and, later, the experimental compiler. All application source
is authored `.phs`. There is no PHP application implementation or native C++ shim.

This is the first working increment, **not the completed approximately 512 KiB
source workload**. Use `python3 verify.py --size-only` for the exact current size.
The source target is 524,288 bytes with a practical ten-percent tolerance. Source
size includes normal comments, but excludes generated C++, build state, this
documentation, Python tooling, and external fixtures. Do not add padding or
duplicated implementations to reach the target.

## Build and verify

From this directory:

```sh
# First build, or after a runtime ABI change:
python3 verify.py --build-runtime

# Subsequent native build and deterministic behavior check:
python3 verify.py

# Compile only, through this checkout's public CLI:
php8.5 ../../bin/scpp.php build

# Source-size acceptance is separate from behavior acceptance:
python3 verify.py --size-only --require-target
```

Pass `--php /path/to/php` if PHP 8.5 is not the desired host executable.
The runner deliberately selects `../../bin/scpp.php`: the installed `scpp`
launcher observed during development points to a different, older checkout.
It retains normal STAN checking; it does not pass `--no-stan`.

The project explicitly selects the strict PHP profile and no optional runtime
modules. Runtime build state remains inside this project. Verification evidence
is written to ignored `.prism/workload-verification/report.json` and `output.log`.
The JSON includes a per-source SHA-256 manifest and separates behavior success
from source-size acceptance.

The application prints one check summary and a PASS/FAIL marker. The runner
requires a successful process result, nonzero check count, zero failures, and
exactly one PASS marker. This matters because the legacy generator currently
lowers authored top-level integer returns to `int_t<>` despite the generated
entry function returning native `int`. The application leaves entry completion
to the generator and the runner converts failed checks into a nonzero exit.

## Implemented model

- General ledger: typed chart, balanced posting, active-account validation,
  bounded amounts, duplicate references, owned journal snapshots, reversals,
  period turnover, period closing, and trial balance.
- Sales: invoice posting, partial and final receipts, later-period settlement,
  owned invoice snapshots, duplicate receipt rejection, receivables control.
- Purchasing: supplier limits and holds, expense and received-goods bills,
  component-aware credits, payments, oldest-due payment proposals, payables control.
- Inventory: receipt lots, FIFO costing, reservations, releases, shipments,
  quantity/value controls and ledger reconciliation.
- Assets: acquisition, straight-line and declining-balance policies through an
  abstract base, per-period depreciation, residual value, disposal gain/loss,
  separate cost and accumulated-depreciation controls.
- Banking: validated statement imports, owned line snapshots, signed matching,
  unmatching, fee/interest adjustments, reconciliation and finalization.
- Period adjustments: exact prepayment allocation and releases, expense
  accruals, later-period reversals and close-readiness checks.
- Reporting: trial balance, profit and loss, financial position, working capital,
  approved budgets and period-filtered variance.
- Shared rules: integer minor units, half-away-from-zero rounding, largest
  remainder allocation, date values, deterministic calendar and payment terms.
- Scenarios: individual workflows, rejection/state-preservation checks, and an
  integrated fictional company spanning two periods.

The ledger owns posting validation. Subledgers prepare entries and update their
state only after posting succeeds. This is an in-memory single-process model;
there is no claim of crash recovery, database transactions, or production use.

Dates are bounded to 2000–2099. A journal entry and an account net balance are
bounded to one billion minor units. Period IDs run from 1 through 1200. All money
uses one fictional currency. Tax is supplied as explicit fictional amounts;
the application does not implement any jurisdiction's compliance rules.

The low-level ledger close only checks ledger invariants. Application workflows
must run the applicable subledger and adjustment readiness checks before closing.
Bank matching currently supports one statement line to one journal entry.
The adjustment book owns prepayments; purchasing does not create a second
prepayment subledger.

## Compiler coverage and limits

The native baseline exercises multiple files, explicit typed locals/parameters/
returns/properties, fixed-backed enums, a struct with independent copies, static
methods, visibility, abstract inheritance and shared base dispatch, a closure with
value capture, vector/hash operations, integer-keyed hashes, hash `unset`, nested
member access, nonmutating value iteration, arithmetic, casts and string assembly.

The closure uses an inferred local and an explicitly typed parameter/return
signature. The legacy preprocessor rejected the attempted inline
`function<bool(int)>` local spelling; that spelling is not required by this app.
Date fields use fixed-width `int64` with typed assignment boundaries; ordinary
PHP-style `(int32)` casts are not accepted by the legacy parser.

Two native `nodiscard` warnings currently arise from valid source-level hash
`unset` lowering in bank unmatching. No generated output has been patched and
no warning suppression has been added.

STAN also reports three advisory unknown-expression-root warnings for explicitly
typed integer locals (`portion` and `rounded`) in allocation/rounding functions.
The native arithmetic and conservation checks pass. These advisories are retained
and are not suppressed or represented as a clean analyzer result.

No experimental compiler build or compatibility claim is made. Its CH12 closure
work is treated as approaching availability, not a verified result. This task
does not edit `compiler/my-try/`, runtime code, or generator code.

See [the expansion plan](../../specs/planning/accounting_workload.md) for the
remaining source-size and capability work.
