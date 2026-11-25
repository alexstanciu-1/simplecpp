#include "business_checks.hpp"

namespace scpp {
	using namespace ::scpp;

void fund_company(P_GeneralLedger ledger, P_WorkloadChecks checks, I amount) {
	SCPP_CALL_DEPTH_GUARD("fund_company", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/business_checks.phs", 1);
	P_JournalEntry entry = create_JournalEntry();
	entry->reference = string_t("opening-capital");
	entry->origin = string_t("capital");
	entry->period = ledger->current_period();
	entry->add_debit(string_t("bank"), amount, string_t("Owner contribution"));
	entry->add_credit(string_t("capital"), amount, string_t("Owner contribution"));
	checks->accepted(ledger->post(entry), string_t("owner contribution posted"));
}

void check_purchasing_flow(P_WorkloadChecks checks) {
	SCPP_CALL_DEPTH_GUARD("check_purchasing_flow", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/business_checks.phs", 11);
	P_GeneralLedger ledger = setup_accounting_ledger(checks);
	fund_company(ledger, checks, static_cast<I >(50000));
	P_PurchasingBook purchases = create_PurchasingBook();
	checks->accepted(purchases->register_supplier(string_t("SUP-001"), string_t("Fictional wholesale company"), static_cast<I >(50000)), string_t("supplier registered"));
	P_SupplierBill draft = create_SupplierBill();
	draft->supplier_code = string_t("SUP-001");
	draft->number = string_t("BILL-001");
	draft->net_amount = static_cast<I >(10000);
	draft->tax_amount = static_cast<I >(2000);
	draft->due_day = static_cast<I >(100);
	checks->accepted(purchases->record_bill(ledger, draft), string_t("expense bill posted"));
	checks->equal(purchases->supplier_balance(string_t("SUP-001")), static_cast<I >(12000), string_t("supplier gross balance"));
	checks->equal(ledger->balance_of(string_t("operating-expense")), static_cast<I >(10000), string_t("expense bill debit"));
	checks->equal(ledger->balance_of(string_t("input-tax")), static_cast<I >(2000), string_t("input tax separated"));
	string_t key = convert_string_t(purchases->bill_key(string_t("SUP-001"), string_t("BILL-001")));
	draft->net_amount = static_cast<I >(999);
	checks->equal(purchases->total_balance(), static_cast<I >(12000), string_t("posted bill owns amount snapshot"));
	checks->rejected(purchases->record_bill(ledger, draft), string_t("bill.duplicate"), string_t("supplier bill duplicate rejected"));
	checks->accepted(purchases->credit(ledger, key, string_t("CR-001"), static_cast<I >(1000), static_cast<I >(200)), string_t("supplier credit posted"));
	checks->equal(purchases->total_balance(), static_cast<I >(10800), string_t("credit reduces payable"));
	checks->equal(ledger->balance_of(string_t("operating-expense")), static_cast<I >(9000), string_t("credit reverses expense"));
	checks->equal(ledger->balance_of(string_t("input-tax")), static_cast<I >(1800), string_t("credit reverses input tax"));
	P_PaymentProposal proposal = purchases->propose_payments(static_cast<I >(100), static_cast<I >(5000));
	checks->equal(php::count(proposal->rows), static_cast<I >(1), string_t("payment proposal selects due bill"));
	checks->equal(proposal->total, static_cast<I >(5000), string_t("payment proposal obeys budget"));
	checks->equal(purchases->total_balance(), static_cast<I >(10800), string_t("proposal leaves payables unchanged"));
	checks->accepted(purchases->pay(ledger, key, string_t("PAY-001"), static_cast<I >(5000)), string_t("supplier partial payment"));
	checks->equal(purchases->total_balance(), static_cast<I >(5800), string_t("remaining supplier balance"));
	checks->equal(ledger->balance_of(string_t("bank")), static_cast<I >(45000), string_t("payment reduces bank"));
	checks->rejected(purchases->pay(ledger, key, string_t("PAY-001"), static_cast<I >(1000)), string_t("journal.duplicate"), string_t("duplicate supplier payment rejected"));
	checks->equal(purchases->total_balance(), static_cast<I >(5800), string_t("duplicate payment is atomic"));
	checks->accepted(purchases->hold_supplier(string_t("SUP-001"), static_cast<bool_t>(true)), string_t("supplier placed on hold"));
	P_PaymentProposal held = purchases->propose_payments(static_cast<I >(100), static_cast<I >(10000));
	checks->equal(php::count(held->rows), static_cast<I >(0), string_t("held supplier excluded from proposal"));
	checks->rejected(purchases->pay(ledger, key, string_t("PAY-002"), static_cast<I >(5800)), string_t("supplier.hold"), string_t("held supplier cannot be paid"));
	checks->accepted(purchases->hold_supplier(string_t("SUP-001"), static_cast<bool_t>(false)), string_t("supplier released from hold"));
	checks->accepted(purchases->pay(ledger, key, string_t("PAY-002"), static_cast<I >(5800)), string_t("supplier final payment"));
	checks->equal(purchases->total_balance(), static_cast<I >(0), string_t("supplier settled"));
	checks->accepted(purchases->reconcile(ledger), string_t("payables reconcile"));
	checks->rejected(purchases->credit(ledger, key, string_t("CR-002"), static_cast<I >(1), static_cast<I >(0)), string_t("credit.balance"), string_t("no credit beyond settled balance"));
	P_FinancialReports reports = create_FinancialReports();
	P_FinancialPosition position = reports->financial_position(ledger);
	checks->expect(position->balances(), string_t("purchase scenario financial position balances"));
	checks->equal(position->current_profit, (-static_cast<I >(9000)), string_t("purchase scenario expense loss"));
}

void check_asset_flow(P_WorkloadChecks checks) {
	SCPP_CALL_DEPTH_GUARD("check_asset_flow", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/business_checks.phs", 58);
	P_GeneralLedger ledger = setup_accounting_ledger(checks);
	fund_company(ledger, checks, static_cast<I >(100000));
	P_AssetRegister assets = create_AssetRegister();
	P_StraightLineDepreciation straight = create_StraightLineDepreciation();
	checks->accepted(assets->acquire(ledger, string_t("MACHINE-01"), string_t("Fictional machine"), static_cast<I >(10001), static_cast<I >(1001), static_cast<I >(3), straight), string_t("asset acquired"));
	checks->accepted(assets->depreciate(ledger, string_t("MACHINE-01")), string_t("first straight-line charge"));
	checks->equal(assets->total_depreciation(), static_cast<I >(3000), string_t("straight-line first charge"));
	checks->rejected(assets->depreciate(ledger, string_t("MACHINE-01")), string_t("asset.period-duplicate"), string_t("duplicate depreciation rejected"));
	checks->accepted(assets->reconcile(ledger), string_t("asset first period controls"));
	checks->accepted(ledger->close_period(static_cast<I >(1)), string_t("asset period one closed"));
	checks->accepted(assets->depreciate(ledger, string_t("MACHINE-01")), string_t("second straight-line charge"));
	checks->equal(assets->total_depreciation(), static_cast<I >(6000), string_t("straight-line cumulative charge"));
	checks->accepted(assets->dispose(ledger, string_t("MACHINE-01"), string_t("DISP-001"), static_cast<I >(5000)), string_t("asset disposed at gain"));
	checks->equal(ledger->balance_of(string_t("asset-gain")), (-static_cast<I >(999)), string_t("asset gain measured against carrying amount"));
	checks->equal(assets->total_cost(), static_cast<I >(0), string_t("disposed asset removed from cost control"));
	checks->equal(assets->total_depreciation(), static_cast<I >(0), string_t("disposed asset removed from accumulated control"));
	checks->accepted(assets->reconcile(ledger), string_t("disposal clears asset controls"));
	checks->rejected(assets->dispose(ledger, string_t("MACHINE-01"), string_t("DISP-002"), static_cast<I >(1)), string_t("asset.disposed"), string_t("double disposal rejected"));
	P_DecliningBalanceDepreciation declining = create_DecliningBalanceDepreciation();
	declining->basis_points = static_cast<I >(2500);
	checks->accepted(assets->acquire(ledger, string_t("DEVICE-01"), string_t("Fictional device"), static_cast<I >(10000), static_cast<I >(1000), static_cast<I >(2), declining), string_t("declining asset acquired"));
	checks->accepted(assets->depreciate(ledger, string_t("DEVICE-01")), string_t("declining policy dispatched through base"));
	checks->equal(assets->total_depreciation(), static_cast<I >(2500), string_t("declining first charge differs from straight line"));
	checks->accepted(ledger->close_period(static_cast<I >(2)), string_t("asset period two closed"));
	checks->accepted(assets->depreciate(ledger, string_t("DEVICE-01")), string_t("declining final life charge"));
	checks->equal(assets->total_depreciation(), static_cast<I >(9000), string_t("final charge stops at residual value"));
	checks->rejected(assets->depreciate(ledger, string_t("DEVICE-01")), string_t("asset.finished"), string_t("fully depreciated asset rejects more charges"));
	checks->accepted(assets->dispose(ledger, string_t("DEVICE-01"), string_t("DISP-003"), static_cast<I >(0)), string_t("asset scrapped"));
	checks->equal(ledger->balance_of(string_t("asset-loss")), static_cast<I >(1000), string_t("scrap recognizes residual loss"));
	checks->accepted(assets->reconcile(ledger), string_t("scrap clears controls"));
}

void check_budget_flow(P_WorkloadChecks checks) {
	SCPP_CALL_DEPTH_GUARD("check_budget_flow", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/business_checks.phs", 91);
	P_GeneralLedger ledger = setup_accounting_ledger(checks);
	fund_company(ledger, checks, static_cast<I >(10000));
	P_BudgetBook budget = create_BudgetBook();
	checks->accepted(budget->add(ledger, string_t("operating-expense"), static_cast<I >(1), static_cast<I >(2000)), string_t("expense budget line"));
	checks->accepted(budget->add(ledger, string_t("revenue"), static_cast<I >(1), (-static_cast<I >(5000))), string_t("revenue budget uses credit sign"));
	checks->rejected(budget->add(ledger, string_t("revenue"), static_cast<I >(1), (-static_cast<I >(1000))), string_t("budget.duplicate"), string_t("duplicate budget cell rejected"));
	checks->accepted(budget->approve(), string_t("budget approved"));
	checks->rejected(budget->add(ledger, string_t("bank"), static_cast<I >(1), static_cast<I >(1)), string_t("budget.locked"), string_t("approved budget immutable"));
	P_JournalEntry entry = create_JournalEntry();
	entry->reference = string_t("budget-actual");
	entry->add_debit(string_t("operating-expense"), static_cast<I >(2500), string_t("Actual operating expense"));
	entry->add_credit(string_t("bank"), static_cast<I >(2500), string_t("Actual operating expense"));
	checks->accepted(ledger->post(entry), string_t("budget actual posted"));
	P_BudgetReport report = budget->variance(ledger, static_cast<I >(1), static_cast<I >(1));
	checks->equal(php::count(report->rows), static_cast<I >(2), string_t("budget report row count"));
	checks->equal(report->rows[static_cast<I >(0)]->variance, static_cast<I >(500), string_t("expense adverse variance"));
	checks->equal(report->rows[static_cast<I >(1)]->variance, static_cast<I >(5000), string_t("missing revenue variance"));
	checks->equal(report->variance_total, static_cast<I >(5500), string_t("budget aggregate variance"));
	P_BudgetReport empty = budget->variance(ledger, static_cast<I >(2), static_cast<I >(2));
	checks->equal(php::count(empty->rows), static_cast<I >(0), string_t("budget period filter"));
	P_FinancialReports reports = create_FinancialReports();
	P_ProfitAndLoss profit = reports->profit_and_loss(ledger);
	checks->equal(profit->profit, (-static_cast<I >(2500)), string_t("profit and loss actual expense"));
	P_FinancialPosition position = reports->financial_position(ledger);
	checks->expect(position->balances(), string_t("budget scenario financial position"));
	P_WorkingCapital working = reports->working_capital(ledger);
	checks->equal(working->cash, static_cast<I >(7500), string_t("working capital cash"));
}

void check_sales_snapshot(P_WorkloadChecks checks) {
	SCPP_CALL_DEPTH_GUARD("check_sales_snapshot", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/business_checks.phs", 121);
	P_GeneralLedger ledger = setup_accounting_ledger(checks);
	P_SalesService sales = create_SalesService();
	P_SalesInvoice invoice = create_SalesInvoice();
	invoice->number = string_t("SNAPSHOT-001");
	invoice->net_amount = static_cast<I >(10000);
	invoice->tax_amount = static_cast<I >(2000);
	checks->accepted(sales->issue(ledger, invoice), string_t("snapshot invoice issued"));
	invoice->net_amount = static_cast<I >(100000);
	checks->equal(sales->total_outstanding(), static_cast<I >(12000), string_t("caller mutation cannot alter issued invoice"));
	checks->rejected(sales->receive(ledger, invoice, string_t("TOO-MUCH"), static_cast<I >(13000)), string_t("receipt.invalid"), string_t("receipt uses stored amount"));
	checks->accepted(ledger->close_period(static_cast<I >(1)), string_t("invoice period closed before settlement"));
	checks->accepted(sales->receive(ledger, invoice, string_t("LATER-PAYMENT"), static_cast<I >(12000)), string_t("invoice settled in later open period"));
	checks->equal(ledger->turnover(string_t("bank"), static_cast<I >(2), static_cast<I >(2)), static_cast<I >(12000), string_t("receipt belongs to payment period"));
	checks->equal(invoice->outstanding(), static_cast<I >(0), string_t("returned invoice view refreshed after settlement"));
	checks->accepted(sales->reconcile(ledger), string_t("sales control reconciles after later settlement"));
}

}

