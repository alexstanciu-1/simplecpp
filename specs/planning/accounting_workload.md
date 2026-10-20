# Accounting workload expansion
Doc Status: planning

## Agreed target

Approximately 512 KiB of meaningful authored strict PHP++ `.phs` source, no UI,
with a native legacy S2S build as the initial acceptance criterion. Generated
output, external fixtures and docs do not count. Source comments should remain
natural and must not serve as size padding. Keep changes outside
`compiler/my-try/` while another agent works there.

The app owner is `examples/accounting_workload/`. The semantic authorities remain
the repository's normative language specs. This document is not language policy.

## First increment

Implemented and natively exercised: ledger, sales/receipts, purchasing/payments,
FIFO inventory, fixed assets, banking, period adjustments, reports and budgets,
money/calendar rules, and deterministic scenarios. This is a functional baseline,
not the completed size target. `verify.py` measures current source bytes and can
explicitly fail source-size acceptance independently of behavior checks.

The baseline exposes useful composition and ownership issues before expansion:
posted journals and documents retain snapshots, subledger mutations follow ledger
acceptance, tax components remain separate, and amount allocation conserves minor
units. It is deliberately single-threaded and in memory.

## Remaining meaningful expansion

1. **Sales documents and credit control:** customer master records, credit limits,
   invoice lines, discounts, configured fictional tax calculation, sales credits,
   returns, unapplied receipts, allocation/reallocation, statements, aging and
   collection rules. Replace the aggregate invoice draft with a truthful line
   owner; keep existing ledger posting and duplicate-reference paths.
2. **Purchasing documents and matching:** purchase orders, partial receipts,
   invoice-line matching against receipts, quantity/price tolerance policies,
   supplier returns, credit allocation, due-date terms, payment batches and
   remittance records. Preserve a single owner for each control balance.
3. **Inventory operations:** warehouse/location identities, transfers, stock
   counts, adjustments, returned goods, reorder decisions, reservation fulfilment,
   lot traceability, landed-cost allocation and richer valuation reports.
4. **Banking and cash:** payment instructions, deposit groups, split matching,
   outstanding-item reconciliation, petty cash, reversals and period-specific
   bank snapshots. Keep statement matching separate from journal authoring.
5. **Assets and close:** asset classes, useful-life revisions, impairment,
   transfers, complete depreciation schedules, durable application-level close
   readiness, income closing and year transition. Do not silently extend the
   current low-level ledger close to own unrelated subledger rules.
6. **Management reports:** account activity, customer/supplier aging, cash flow,
   cost centers, project/job costing, tax control summaries, budgets by department,
   comparative periods and variance explanations.
7. **Scenario breadth:** independently expected end-to-end outcomes, boundary and
   rejected-operation checks, mixed ordering, reference collisions, multiple
   periods, partial settlements and cross-module reconciliation. Prefer reusable
   checks and parameterized loops to repeated case bodies.
8. **Compiler baseline evidence:** compile-only invocation, source manifest,
   native behavior baseline, intentional language coverage map, focused clean and
   incremental timing measurements once the source target is reached. Timing must
   identify runtime reuse, cache state and parallel work in the shared checkout.

Add modules incrementally and retain the native build. Do not extend language,
runtime or compiler semantics just to meet this application's byte target. Record
actual blockers and their owners. Experimental compilation is a later action,
after the user lifts the current boundary or authorizes a separate comparison.

## Non-goals

No UI, web server, database service, external network, live tax tables, regulatory
compliance, concurrency, generated filler, source exceptions, traits, or dependency
on unfinished compiler integrations. Persistence adapters can be considered later;
they are not prerequisites for the compilation workload.

## Acceptance still outstanding

- Meaningful source expansion to approximately 524,288 bytes (ten-percent window).
- Native legacy S2S build of that full-size source set, with strict profile.
- Updated integrated scenarios and coverage inventory for the expanded behavior.

The first increment's successful build and behavior checks do not satisfy these
remaining full-workload criteria.
