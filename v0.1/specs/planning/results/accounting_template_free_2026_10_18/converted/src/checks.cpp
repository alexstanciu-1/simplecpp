#include "checks.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t WorkloadChecks::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == WorkloadChecks::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

void WorkloadChecks::expect(bool_t condition, const string_t& label) {
	SCPP_CALL_DEPTH_GUARD("WorkloadChecks::expect", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/checks.phs", 6);
	if (static_cast<bool>(php::condition_truthy(condition))) {
		this->passed += static_cast<I >(1);
	}
	else {
		this->failed += static_cast<I >(1);
		(void) this->failures.push_back(label);
	}
}

void WorkloadChecks::equal(I actual, I expected, const string_t& label) {
	SCPP_CALL_DEPTH_GUARD("WorkloadChecks::equal", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/checks.phs", 15);
	this->expect(php::identical(actual, expected), label);
}

void WorkloadChecks::accepted(P_AccountingCheck check, const string_t& label) {
	SCPP_CALL_DEPTH_GUARD("WorkloadChecks::accepted", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/checks.phs", 19);
	this->expect(check->accepted, (convert_string_t(label) + string_t(":") + convert_string_t(check->code)));
}

void WorkloadChecks::rejected(P_AccountingCheck check, const string_t& code, const string_t& label) {
	SCPP_CALL_DEPTH_GUARD("WorkloadChecks::rejected", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/checks.phs", 23);
	this->expect(((!check->accepted) && php::identical(check->code, code)), (convert_string_t(label) + string_t(":") + convert_string_t(check->code)));
}

void WorkloadChecks::report() {
	SCPP_CALL_DEPTH_GUARD("WorkloadChecks::report", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/checks.phs", 27);
	auto& __scpp_foreach_source_28 = this->failures;
	for (auto __scpp_foreach_entry_28 : foreach_range(__scpp_foreach_source_28)) {
		auto failure = __scpp_foreach_entry_28.value_copy();
		php::echo_one(string_t("FAIL "));
		php::echo_one(failure);
		php::echo_one(string_t("\n"));
	}
	php::echo_one(string_t("CHECKS passed="));
	php::echo_one(this->passed);
	php::echo_one(string_t(" failed="));
	php::echo_one(this->failed);
	php::echo_one(string_t("\n"));
	if (static_cast<bool>(php::identical(this->failed, static_cast<I >(0)))) {
		php::echo_one(string_t("ACCOUNTING_WORKLOAD_PASS\n"));
	}
	else {
		php::echo_one(string_t("ACCOUNTING_WORKLOAD_FAIL\n"));
	}
}

P_GeneralLedger setup_accounting_ledger(P_WorkloadChecks checks) {
	SCPP_CALL_DEPTH_GUARD("setup_accounting_ledger", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/checks.phs", 40);
	P_GeneralLedger ledger = create_GeneralLedger();
	checks->accepted(ledger->add_account(string_t("bank"), string_t("Operating bank"), AccountCategory::Asset), string_t("chart bank"));
	checks->accepted(ledger->add_account(string_t("receivables"), string_t("Trade receivables"), AccountCategory::Asset), string_t("chart receivables"));
	checks->accepted(ledger->add_account(string_t("inventory"), string_t("Stock at cost"), AccountCategory::Asset), string_t("chart inventory"));
	checks->accepted(ledger->add_account(string_t("input-tax"), string_t("Recoverable input tax"), AccountCategory::Asset), string_t("chart input tax"));
	checks->accepted(ledger->add_account(string_t("fixed-assets"), string_t("Fixed assets at cost"), AccountCategory::Asset), string_t("chart assets"));
	checks->accepted(ledger->add_account(string_t("accumulated-depreciation"), string_t("Accumulated depreciation"), AccountCategory::Asset), string_t("chart depreciation"));
	checks->accepted(ledger->add_account(string_t("prepayments"), string_t("Prepaid expenses"), AccountCategory::Asset), string_t("chart prepayments"));
	checks->accepted(ledger->add_account(string_t("payables"), string_t("Trade payables"), AccountCategory::Liability), string_t("chart payables"));
	checks->accepted(ledger->add_account(string_t("goods-received"), string_t("Goods received not invoiced"), AccountCategory::Liability), string_t("chart receipt accrual"));
	checks->accepted(ledger->add_account(string_t("tax-payable"), string_t("Output tax"), AccountCategory::Liability), string_t("chart output tax"));
	checks->accepted(ledger->add_account(string_t("accruals"), string_t("Accrued expenses"), AccountCategory::Liability), string_t("chart accruals"));
	checks->accepted(ledger->add_account(string_t("capital"), string_t("Owner capital"), AccountCategory::Equity), string_t("chart capital"));
	checks->accepted(ledger->add_account(string_t("retained-earnings"), string_t("Retained earnings"), AccountCategory::Equity), string_t("chart retained earnings"));
	checks->accepted(ledger->add_account(string_t("revenue"), string_t("Sales revenue"), AccountCategory::Revenue), string_t("chart revenue"));
	checks->accepted(ledger->add_account(string_t("interest-income"), string_t("Bank interest"), AccountCategory::Revenue), string_t("chart interest"));
	checks->accepted(ledger->add_account(string_t("asset-gain"), string_t("Asset disposal gain"), AccountCategory::Revenue), string_t("chart asset gain"));
	checks->accepted(ledger->add_account(string_t("cost-of-sales"), string_t("Cost of sales"), AccountCategory::Expense), string_t("chart cost of sales"));
	checks->accepted(ledger->add_account(string_t("operating-expense"), string_t("Operating expenses"), AccountCategory::Expense), string_t("chart expenses"));
	checks->accepted(ledger->add_account(string_t("bank-fees"), string_t("Bank fees"), AccountCategory::Expense), string_t("chart fees"));
	checks->accepted(ledger->add_account(string_t("depreciation-expense"), string_t("Depreciation expense"), AccountCategory::Expense), string_t("chart depreciation expense"));
	checks->accepted(ledger->add_account(string_t("asset-loss"), string_t("Asset disposal loss"), AccountCategory::Expense), string_t("chart asset loss"));
	return ledger;
}

void check_money_rules(P_WorkloadChecks checks) {
	SCPP_CALL_DEPTH_GUARD("check_money_rules", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/checks.phs", 66);
	P_AmountResult half = MoneyRules::rate(static_cast<I >(5), static_cast<I >(1000));
	checks->expect(half->valid, string_t("positive half minor unit accepted"));
	checks->equal(half->amount, static_cast<I >(1), string_t("positive half rounds away from zero"));
	P_AmountResult negative = MoneyRules::rate((-static_cast<I >(5)), static_cast<I >(1000));
	checks->equal(negative->amount, (-static_cast<I >(1)), string_t("negative half rounds away from zero"));
	P_AmountResult below = MoneyRules::rate(static_cast<I >(4), static_cast<I >(1000));
	checks->equal(below->amount, static_cast<I >(0), string_t("below half rounds down"));
	P_AmountResult maximum = MoneyRules::rate(static_cast<I >(1000000000), static_cast<I >(10000));
	checks->equal(maximum->amount, static_cast<I >(1000000000), string_t("maximum rate product remains exact"));
	P_AmountResult overflow = MoneyRules::add(static_cast<I >(1000000000), static_cast<I >(1));
	checks->expect((!overflow->valid), string_t("addition rejects workload overflow"));
	P_AmountResult bad_rate = MoneyRules::rate(static_cast<I >(100), (-static_cast<I >(1)));
	checks->expect((!bad_rate->valid), string_t("negative tax rate rejected"));
	P_AmountResult product = MoneyRules::multiply(static_cast<I >(150), static_cast<I >(7));
	checks->equal(product->amount, static_cast<I >(1050), string_t("quantity times price"));
	P_AmountResult bad_product = MoneyRules::multiply(static_cast<I >(1000000000), static_cast<I >(2));
	checks->expect((!bad_product->valid), string_t("line extension overflow rejected"));
	P_AllocationResult split = MoneyAllocation::equally(static_cast<I >(100), static_cast<I >(3));
	checks->expect(split->valid, string_t("equal allocation accepted"));
	checks->equal(split->amounts[static_cast<I >(0)], static_cast<I >(34), string_t("first equal allocation receives remainder"));
	checks->equal(split->amounts[static_cast<I >(1)], static_cast<I >(33), string_t("second equal allocation"));
	checks->equal(split->amounts[static_cast<I >(2)], static_cast<I >(33), string_t("third equal allocation"));
	V_I weights = convert_V_I(([&]() { V_I init; init.push_back(static_cast<I >(1)); init.push_back(static_cast<I >(2)); init.push_back(static_cast<I >(3)); return init; }()));
	P_AllocationResult weighted = MoneyAllocation::weighted(static_cast<I >(100), weights);
	checks->expect(weighted->valid, string_t("weighted allocation accepted"));
	checks->equal(weighted->amounts[static_cast<I >(0)], static_cast<I >(17), string_t("largest remainder gets minor unit"));
	checks->equal(weighted->amounts[static_cast<I >(1)], static_cast<I >(33), string_t("weighted second share"));
	checks->equal(weighted->amounts[static_cast<I >(2)], static_cast<I >(50), string_t("weighted third share"));
	V_I zero_weights = convert_V_I(([&]() { V_I init; init.push_back(static_cast<I >(0)); init.push_back(static_cast<I >(0)); return init; }()));
	P_AllocationResult invalid = MoneyAllocation::weighted(static_cast<I >(10), zero_weights);
	checks->expect((!invalid->valid), string_t("zero weight total rejected"));
	for (auto amount = (-static_cast<I >(101)); static_cast<bool>((amount <= static_cast<I >(101))); (amount++)) {
		P_AllocationResult allocation = MoneyAllocation::weighted(amount, weights);
		I sum = convert_I(static_cast<I >(0));
		auto __scpp_foreach_source_102 = allocation->amounts;
		for (auto __scpp_foreach_entry_102 : foreach_range(__scpp_foreach_source_102)) {
			auto portion = __scpp_foreach_entry_102.value_copy();
			sum += portion;
		}
		checks->expect((allocation->valid && php::identical(sum, amount)), string_t("weighted allocation conserves signed amount"));
	}
}

void check_calendar_rules(P_WorkloadChecks checks) {
	SCPP_CALL_DEPTH_GUARD("check_calendar_rules", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/checks.phs", 109);
	AccountingDate date = AccountingDate{};
	date->year = static_cast<I >(2024);
	date->month = static_cast<I >(2);
	date->day = static_cast<I >(28);
	AccountingDate next = CalendarRules::add_days(date, static_cast<I >(1));
	checks->equal(next->day, static_cast<I >(29), string_t("leap day exists"));
	checks->equal(date->day, static_cast<I >(28), string_t("date struct copy is independent"));
	AccountingDate march = CalendarRules::add_days(date, static_cast<I >(2));
	checks->equal(march->month, static_cast<I >(3), string_t("leap day rollover month"));
	checks->equal(march->day, static_cast<I >(1), string_t("leap day rollover day"));
	AccountingDate bad = AccountingDate{};
	bad->year = static_cast<I >(2023);
	bad->month = static_cast<I >(2);
	bad->day = static_cast<I >(29);
	checks->expect((!CalendarRules::valid(bad)), string_t("non-leap February 29 rejected"));
	P_PaymentTerms terms = create_PaymentTerms();
	terms->kind = PaymentTermKind::DaysAfterMonthEnd;
	terms->days = static_cast<I >(30);
	AccountingDate due = terms->due_date(date);
	checks->equal(due->month, static_cast<I >(3), string_t("month-end terms month"));
	checks->equal(due->day, static_cast<I >(30), string_t("month-end terms day"));
	terms->discount_days = static_cast<I >(10);
	terms->discount_basis_points = static_cast<I >(200);
	P_AmountResult discount = terms->settlement_discount(static_cast<I >(10000), date, next);
	checks->equal(discount->amount, static_cast<I >(200), string_t("early settlement discount"));
	P_AmountResult late = terms->settlement_discount(static_cast<I >(10000), date, due);
	checks->equal(late->amount, static_cast<I >(0), string_t("late settlement no discount"));
	for (auto ordinal = static_cast<I >(0); static_cast<bool>((ordinal <= static_cast<I >(36524))); (ordinal += static_cast<I >(97))) {
		AccountingDate round_trip = CalendarRules::from_ordinal(ordinal);
		checks->equal(CalendarRules::ordinal(round_trip), ordinal, string_t("date ordinal round trip"));
	}
	AccountingDate last = CalendarRules::from_ordinal(static_cast<I >(36524));
	checks->equal(last->year, static_cast<I >(2099), string_t("calendar final year"));
	checks->equal(last->month, static_cast<I >(12), string_t("calendar final month"));
	checks->equal(last->day, static_cast<I >(31), string_t("calendar final day"));
}

void check_ledger_rejections(P_WorkloadChecks checks) {
	SCPP_CALL_DEPTH_GUARD("check_ledger_rejections", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/checks.phs", 147);
	P_GeneralLedger ledger = setup_accounting_ledger(checks);
	P_JournalEntry entry = create_JournalEntry();
	entry->reference = string_t("unbalanced");
	entry->add_debit(string_t("bank"), static_cast<I >(100), string_t("test"));
	entry->add_credit(string_t("capital"), static_cast<I >(99), string_t("test"));
	checks->rejected(ledger->post(entry), string_t("journal.unbalanced"), string_t("unbalanced journal rejected"));
	checks->equal(ledger->entry_count(), static_cast<I >(0), string_t("rejected journal not recorded"));
	checks->equal(ledger->balance_of(string_t("bank")), static_cast<I >(0), string_t("rejected journal leaves bank unchanged"));
	entry->lines[static_cast<I >(1)]->credit = static_cast<I >(100);
	checks->accepted(ledger->post(entry), string_t("corrected draft posted"));
	entry->lines[static_cast<I >(0)]->debit = static_cast<I >(999);
	checks->equal(ledger->turnover(string_t("bank"), static_cast<I >(1), static_cast<I >(1)), static_cast<I >(100), string_t("posted journal owns line snapshot"));
	checks->rejected(ledger->post(entry), string_t("journal.duplicate"), string_t("duplicate reference rejected"));
	checks->rejected(ledger->set_account_active(string_t("bank"), static_cast<bool_t>(false)), string_t("account.nonzero"), string_t("nonzero account cannot deactivate"));
	checks->accepted(ledger->reverse(string_t("unbalanced"), string_t("reverse-001")), string_t("reverse immutable journal"));
	checks->equal(ledger->balance_of(string_t("bank")), static_cast<I >(0), string_t("reversal clears bank"));
	checks->rejected(ledger->reverse(string_t("unbalanced"), string_t("reverse-002")), string_t("reversal.duplicate"), string_t("journal cannot reverse twice"));
	checks->equal(ledger->entry_count(), static_cast<I >(2), string_t("duplicate reversal leaves journal unchanged"));
	checks->accepted(ledger->set_account_active(string_t("bank"), static_cast<bool_t>(false)), string_t("zero account deactivated"));
	entry->reference = string_t("inactive");
	entry->lines[static_cast<I >(0)]->debit = static_cast<I >(100);
	checks->rejected(ledger->post(entry), string_t("journal.inactive"), string_t("inactive account rejects posting"));
	checks->accepted(ledger->set_account_active(string_t("bank"), static_cast<bool_t>(true)), string_t("bank reactivated"));
	checks->accepted(ledger->close_period(static_cast<I >(1)), string_t("close first period"));
	checks->rejected(ledger->post(entry), string_t("journal.period"), string_t("closed period rejects posting"));
	checks->rejected(ledger->close_period(static_cast<I >(1)), string_t("period.sequence"), string_t("stale close rejected"));
	checks->equal(ledger->current_period(), static_cast<I >(2), string_t("period advances once"));
	P_TrialBalance report = ledger->trial_balance();
	checks->expect(report->is_balanced(), string_t("rejection and reversal trial balance"));
}

void check_inventory_flow(P_WorkloadChecks checks) {
	SCPP_CALL_DEPTH_GUARD("check_inventory_flow", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/checks.phs", 179);
	P_GeneralLedger ledger = setup_accounting_ledger(checks);
	P_InventoryBook inventory = create_InventoryBook();
	checks->accepted(inventory->register_item(string_t("PART-01"), string_t("Fictional replacement part")), string_t("register stock item"));
	checks->accepted(inventory->receive(ledger, string_t("GRN-001"), string_t("PART-01"), static_cast<I >(10), static_cast<I >(100)), string_t("first stock lot"));
	checks->accepted(inventory->receive(ledger, string_t("GRN-002"), string_t("PART-01"), static_cast<I >(5), static_cast<I >(120)), string_t("second stock lot"));
	checks->equal(inventory->total_value(), static_cast<I >(1600), string_t("stock receipt valuation"));
	checks->accepted(inventory->reserve(string_t("ORDER-001"), string_t("PART-01"), static_cast<I >(4)), string_t("reserve stock"));
	checks->equal(inventory->available_of(string_t("PART-01")), static_cast<I >(11), string_t("reservation reduces availability"));
	P_StockIssueResult blocked = inventory->issue(ledger, string_t("SHIP-BLOCKED"), string_t("PART-01"), static_cast<I >(12));
	checks->expect((!blocked->accepted), string_t("cannot ship reserved stock"));
	checks->equal(inventory->quantity_of(string_t("PART-01")), static_cast<I >(15), string_t("rejected shipment leaves quantity"));
	checks->accepted(inventory->release(string_t("ORDER-001")), string_t("release stock reservation"));
	P_StockIssueResult shipped = inventory->issue(ledger, string_t("SHIP-001"), string_t("PART-01"), static_cast<I >(12));
	checks->expect(shipped->accepted, string_t("ship across FIFO lots"));
	checks->equal(shipped->cost, static_cast<I >(1240), string_t("FIFO consumes oldest ten then two newer units"));
	checks->equal(inventory->quantity_of(string_t("PART-01")), static_cast<I >(3), string_t("remaining stock quantity"));
	checks->equal(inventory->total_value(), static_cast<I >(360), string_t("remaining stock value"));
	checks->equal(ledger->balance_of(string_t("cost-of-sales")), static_cast<I >(1240), string_t("shipment posts cost of sales"));
	checks->accepted(inventory->reconcile(ledger), string_t("inventory control reconciles"));
	P_StockIssueResult duplicate = inventory->issue(ledger, string_t("SHIP-001"), string_t("PART-01"), static_cast<I >(1));
	checks->expect((!duplicate->accepted), string_t("duplicate shipment rejected"));
	checks->equal(inventory->total_value(), static_cast<I >(360), string_t("duplicate shipment leaves value"));
	checks->rejected(inventory->release(string_t("ORDER-001")), string_t("reservation.released"), string_t("reservation cannot release twice"));
	checks->accepted(ledger->set_account_active(string_t("bank-fees"), static_cast<bool_t>(false)), string_t("deactivate unused expense account"));
}

}

