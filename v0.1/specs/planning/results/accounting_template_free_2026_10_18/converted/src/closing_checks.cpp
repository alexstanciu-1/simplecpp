#include "closing_checks.hpp"

namespace scpp {
	using namespace ::scpp;

void check_bank_flow(P_WorkloadChecks checks) {
	SCPP_CALL_DEPTH_GUARD("check_bank_flow", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/closing_checks.phs", 1);
	P_GeneralLedger ledger = setup_accounting_ledger(checks);
	fund_company(ledger, checks, static_cast<I >(10000));
	P_BankStatementLine capital_line = create_BankStatementLine();
	capital_line->reference = string_t("STMT-CAPITAL");
	capital_line->description = string_t("Owner transfer");
	capital_line->day = static_cast<I >(1);
	capital_line->amount = static_cast<I >(10000);
	P_BankStatementLine fee_line = create_BankStatementLine();
	fee_line->reference = string_t("STMT-FEE");
	fee_line->description = string_t("Account maintenance fee");
	fee_line->day = static_cast<I >(10);
	fee_line->amount = (-static_cast<I >(150));
	P_BankStatementLine interest_line = create_BankStatementLine();
	interest_line->reference = string_t("STMT-INTEREST");
	interest_line->description = string_t("Deposit interest");
	interest_line->day = static_cast<I >(20);
	interest_line->amount = static_cast<I >(25);
	V_P_BankStatementLine lines = convert_V_P_BankStatementLine(([&]() { V_P_BankStatementLine init; init.push_back(capital_line); init.push_back(fee_line); init.push_back(interest_line); return init; }()));
	P_BankReconciliation reconciliation = create_BankReconciliation();
	checks->rejected(reconciliation->import_statement(static_cast<I >(0), static_cast<I >(9876), static_cast<I >(0), static_cast<I >(30), lines), string_t("statement.control"), string_t("statement control total checked"));
	checks->accepted(reconciliation->import_statement(static_cast<I >(0), static_cast<I >(9875), static_cast<I >(0), static_cast<I >(30), lines), string_t("corrected statement imported after rejection"));
	fee_line->amount = (-static_cast<I >(999));
	P_BankReconciliationReport initial = reconciliation->report(ledger);
	checks->equal(initial->statement_movement, static_cast<I >(9875), string_t("statement owns copied input lines"));
	checks->equal(initial->unmatched_count, static_cast<I >(3), string_t("statement starts unmatched"));
	checks->equal(initial->difference, static_cast<I >(125), string_t("unposted bank adjustments visible"));
	checks->rejected(reconciliation->match_entry(ledger, string_t("STMT-FEE"), string_t("opening-capital")), string_t("statement.amount-mismatch"), string_t("matching checks signed amount"));
	checks->accepted(reconciliation->match_entry(ledger, string_t("STMT-CAPITAL"), string_t("opening-capital")), string_t("capital matched"));
	checks->rejected(reconciliation->match_entry(ledger, string_t("STMT-CAPITAL"), string_t("opening-capital")), string_t("statement.duplicate-match"), string_t("duplicate match rejected"));
	checks->rejected(reconciliation->finalize(ledger), string_t("statement.incomplete"), string_t("unmatched statement cannot finalize"));
	checks->accepted(reconciliation->unmatch(string_t("STMT-CAPITAL")), string_t("match can be corrected"));
	checks->accepted(reconciliation->match_entry(ledger, string_t("STMT-CAPITAL"), string_t("opening-capital")), string_t("unmatched journal becomes available"));
	checks->rejected(reconciliation->post_adjustment(ledger, string_t("STMT-FEE"), string_t("interest-income")), string_t("statement.adjustment-sign"), string_t("negative interest adjustment rejected"));
	checks->accepted(reconciliation->post_adjustment(ledger, string_t("STMT-FEE"), string_t("bank-fees")), string_t("bank fee posted and matched"));
	checks->accepted(reconciliation->post_adjustment(ledger, string_t("STMT-INTEREST"), string_t("interest-income")), string_t("bank interest posted and matched"));
	P_BankReconciliationReport report = reconciliation->report(ledger);
	checks->expect(report->complete, string_t("all bank lines reconcile"));
	checks->equal(report->ledger_closing, static_cast<I >(9875), string_t("bank ending balance"));
	checks->equal(ledger->balance_of(string_t("bank-fees")), static_cast<I >(150), string_t("bank fee expense"));
	checks->equal(ledger->balance_of(string_t("interest-income")), (-static_cast<I >(25)), string_t("bank interest revenue"));
	checks->accepted(reconciliation->finalize(ledger), string_t("bank reconciliation finalized"));
	checks->rejected(reconciliation->unmatch(string_t("STMT-FEE")), string_t("statement.state"), string_t("finalized statement cannot change"));
}

void check_adjustment_flow(P_WorkloadChecks checks) {
	SCPP_CALL_DEPTH_GUARD("check_adjustment_flow", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/closing_checks.phs", 46);
	P_GeneralLedger ledger = setup_accounting_ledger(checks);
	fund_company(ledger, checks, static_cast<I >(10000));
	P_AdjustmentBook adjustments = create_AdjustmentBook();
	checks->accepted(adjustments->prepay(ledger, string_t("INSURANCE"), string_t("operating-expense"), static_cast<I >(1000), static_cast<I >(3)), string_t("insurance prepaid over three periods"));
	checks->equal(adjustments->prepayment_balance(), static_cast<I >(1000), string_t("prepaid original balance"));
	checks->rejected(adjustments->ready_to_close(ledger), string_t("close.prepayment-pending"), string_t("close requires scheduled release"));
	checks->accepted(adjustments->release_prepayment(ledger, string_t("INSURANCE")), string_t("first prepayment release"));
	checks->equal(adjustments->prepayment_balance(), static_cast<I >(666), string_t("first release takes residual minor unit"));
	checks->rejected(adjustments->release_prepayment(ledger, string_t("INSURANCE")), string_t("prepayment.released"), string_t("duplicate release rejected"));
	checks->accepted(adjustments->accrue(ledger, string_t("UTILITIES"), string_t("operating-expense"), static_cast<I >(450)), string_t("utilities accrued"));
	checks->equal(adjustments->accrual_balance(), static_cast<I >(450), string_t("accrual control balance"));
	checks->rejected(adjustments->reverse_accrual(ledger, string_t("UTILITIES")), string_t("accrual.period"), string_t("same-period automatic reversal rejected"));
	checks->accepted(adjustments->ready_to_close(ledger), string_t("first period adjustments complete"));
	checks->accepted(ledger->close_period(static_cast<I >(1)), string_t("close first adjustment period"));
	checks->accepted(adjustments->release_prepayment(ledger, string_t("INSURANCE")), string_t("second prepayment release"));
	checks->rejected(adjustments->ready_to_close(ledger), string_t("close.accrual-pending"), string_t("prior accrual must reverse"));
	checks->accepted(adjustments->reverse_accrual(ledger, string_t("UTILITIES")), string_t("accrual reversed next period"));
	checks->rejected(adjustments->reverse_accrual(ledger, string_t("UTILITIES")), string_t("accrual.reversed"), string_t("accrual reverses once"));
	checks->equal(adjustments->accrual_balance(), static_cast<I >(0), string_t("accrual control clears"));
	checks->equal(ledger->turnover(string_t("operating-expense"), static_cast<I >(2), static_cast<I >(2)), (-static_cast<I >(117)), string_t("period two release less reversed accrual"));
	checks->accepted(adjustments->ready_to_close(ledger), string_t("second period adjustments complete"));
	checks->accepted(ledger->close_period(static_cast<I >(2)), string_t("close second adjustment period"));
	checks->accepted(adjustments->release_prepayment(ledger, string_t("INSURANCE")), string_t("final prepayment release"));
	checks->equal(adjustments->prepayment_balance(), static_cast<I >(0), string_t("prepayment exhausts exactly"));
	checks->equal(ledger->balance_of(string_t("operating-expense")), static_cast<I >(1000), string_t("total expense equals original prepaid amount"));
	checks->accepted(adjustments->reconcile(ledger), string_t("adjustment subledgers reconcile"));
}

void check_integrated_company(P_WorkloadChecks checks) {
	SCPP_CALL_DEPTH_GUARD("check_integrated_company", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/closing_checks.phs", 75);
	P_GeneralLedger ledger = setup_accounting_ledger(checks);
	fund_company(ledger, checks, static_cast<I >(200000));
	P_InventoryBook inventory = create_InventoryBook();
	P_PurchasingBook purchases = create_PurchasingBook();
	P_SalesService sales = create_SalesService();
	P_AssetRegister assets = create_AssetRegister();
	P_AdjustmentBook adjustments = create_AdjustmentBook();
	checks->accepted(inventory->register_item(string_t("WIDGET"), string_t("Fictional traded widget")), string_t("integrated SKU"));
	checks->accepted(purchases->register_supplier(string_t("TRADE"), string_t("Fictional trade supplier"), static_cast<I >(100000)), string_t("integrated supplier"));
	checks->accepted(inventory->receive(ledger, string_t("WAREHOUSE-001"), string_t("WIDGET"), static_cast<I >(100), static_cast<I >(200)), string_t("integrated warehouse receipt"));
	P_SupplierBill bill = create_SupplierBill();
	bill->supplier_code = string_t("TRADE");
	bill->number = string_t("SUPPLIER-001");
	bill->charge_kind = PurchaseChargeKind::ReceivedGoods;
	bill->net_amount = static_cast<I >(20000);
	bill->tax_amount = static_cast<I >(4000);
	bill->due_day = static_cast<I >(30);
	checks->accepted(purchases->record_bill(ledger, bill), string_t("integrated received-goods invoice"));
	checks->equal(ledger->balance_of(string_t("goods-received")), static_cast<I >(0), string_t("invoice clears goods-received accrual"));
	P_StockIssueResult shipment = inventory->issue(ledger, string_t("DELIVERY-001"), string_t("WIDGET"), static_cast<I >(60));
	checks->expect(shipment->accepted, string_t("integrated customer delivery"));
	P_SalesInvoice invoice = create_SalesInvoice();
	invoice->number = string_t("CUSTOMER-001");
	invoice->net_amount = static_cast<I >(30000);
	invoice->tax_amount = static_cast<I >(6000);
	checks->accepted(sales->issue(ledger, invoice), string_t("integrated sales invoice"));
	checks->accepted(sales->receive(ledger, invoice, string_t("CUSTOMER-PAY-001"), static_cast<I >(18000)), string_t("integrated customer partial payment"));
	string_t bill_key = convert_string_t(purchases->bill_key(string_t("TRADE"), string_t("SUPPLIER-001")));
	checks->accepted(purchases->pay(ledger, bill_key, string_t("TRADE-PAY-001"), static_cast<I >(24000)), string_t("integrated supplier settlement"));
	P_StraightLineDepreciation policy = create_StraightLineDepreciation();
	checks->accepted(assets->acquire(ledger, string_t("DESK"), string_t("Office equipment"), static_cast<I >(12000), static_cast<I >(0), static_cast<I >(12), policy), string_t("integrated fixed asset"));
	checks->accepted(assets->depreciate(ledger, string_t("DESK")), string_t("integrated depreciation"));
	checks->accepted(adjustments->prepay(ledger, string_t("RENT"), string_t("operating-expense"), static_cast<I >(6000), static_cast<I >(3)), string_t("integrated prepaid rent"));
	checks->accepted(adjustments->release_prepayment(ledger, string_t("RENT")), string_t("integrated rent release"));
	checks->accepted(adjustments->accrue(ledger, string_t("POWER"), string_t("operating-expense"), static_cast<I >(500)), string_t("integrated utilities accrual"));
	checks->accepted(sales->reconcile(ledger), string_t("integrated receivables control"));
	checks->accepted(purchases->reconcile(ledger), string_t("integrated payables control"));
	checks->accepted(inventory->reconcile(ledger), string_t("integrated inventory control"));
	checks->accepted(assets->reconcile(ledger), string_t("integrated fixed assets control"));
	checks->accepted(adjustments->ready_to_close(ledger), string_t("integrated adjustments ready to close"));
	P_FinancialReports reports = create_FinancialReports();
	P_ProfitAndLoss income = reports->profit_and_loss(ledger);
	checks->equal(income->revenue, static_cast<I >(30000), string_t("integrated revenue"));
	checks->equal(income->expenses, static_cast<I >(15500), string_t("integrated cost of sales and period expenses"));
	checks->equal(income->profit, static_cast<I >(14500), string_t("integrated profit"));
	P_FinancialPosition position = reports->financial_position(ledger);
	checks->equal(position->assets, static_cast<I >(221000), string_t("integrated assets"));
	checks->equal(position->liabilities, static_cast<I >(6500), string_t("integrated liabilities"));
	checks->equal(position->equity, static_cast<I >(200000), string_t("integrated contributed equity"));
	checks->expect(position->balances(), string_t("integrated accounting equation"));
	P_TrialBalance trial = ledger->trial_balance();
	checks->expect(trial->is_balanced(), string_t("integrated trial balance"));
	checks->accepted(ledger->close_period(static_cast<I >(1)), string_t("integrated first period closed"));
	checks->accepted(sales->receive(ledger, invoice, string_t("CUSTOMER-PAY-002"), static_cast<I >(18000)), string_t("integrated next-period collection"));
	checks->accepted(adjustments->release_prepayment(ledger, string_t("RENT")), string_t("integrated next-period rent"));
	checks->accepted(adjustments->reverse_accrual(ledger, string_t("POWER")), string_t("integrated next-period accrual reversal"));
	checks->accepted(assets->depreciate(ledger, string_t("DESK")), string_t("integrated next-period depreciation"));
	checks->equal(sales->total_outstanding(), static_cast<I >(0), string_t("integrated customer fully settled"));
	checks->equal(ledger->balance_of(string_t("bank")), static_cast<I >(194000), string_t("integrated ending cash"));
	checks->equal(ledger->turnover(string_t("operating-expense"), static_cast<I >(2), static_cast<I >(2)), static_cast<I >(1500), string_t("integrated second-period expense"));
	P_FinancialPosition second = reports->financial_position(ledger);
	checks->expect(second->balances(), string_t("integrated second-period accounting equation"));
}

}

