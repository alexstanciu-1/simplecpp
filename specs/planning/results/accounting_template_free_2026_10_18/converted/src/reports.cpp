#include "reports.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t FinancialPosition::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == FinancialPosition::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t FinancialPosition::balances() {
	SCPP_CALL_DEPTH_GUARD("FinancialPosition::balances", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/reports.phs", 7);
	return bool_t(php::identical(this->assets, ((this->liabilities + this->equity) + this->current_profit)));
}

bool_t ProfitAndLoss::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == ProfitAndLoss::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t WorkingCapital::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == WorkingCapital::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t FinancialReports::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == FinancialReports::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

P_ProfitAndLoss FinancialReports::profit_and_loss(P_GeneralLedger ledger) {
	SCPP_CALL_DEPTH_GUARD("FinancialReports::profit_and_loss", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/reports.phs", 27);
	P_ProfitAndLoss report = create_ProfitAndLoss();
	report->revenue = (-ledger->category_balance(AccountCategory::Revenue));
	report->expenses = ledger->category_balance(AccountCategory::Expense);
	report->profit = (report->revenue - report->expenses);
	return report;
}

P_FinancialPosition FinancialReports::financial_position(P_GeneralLedger ledger) {
	SCPP_CALL_DEPTH_GUARD("FinancialReports::financial_position", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/reports.phs", 35);
	P_FinancialPosition report = create_FinancialPosition();
	P_ProfitAndLoss income = this->profit_and_loss(ledger);
	report->assets = ledger->category_balance(AccountCategory::Asset);
	report->liabilities = (-ledger->category_balance(AccountCategory::Liability));
	report->equity = (-ledger->category_balance(AccountCategory::Equity));
	report->current_profit = income->profit;
	return report;
}

P_WorkingCapital FinancialReports::working_capital(P_GeneralLedger ledger) {
	SCPP_CALL_DEPTH_GUARD("FinancialReports::working_capital", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/reports.phs", 45);
	P_WorkingCapital report = create_WorkingCapital();
	report->cash = ledger->balance_of(string_t("bank"));
	report->receivables = ledger->balance_of(string_t("receivables"));
	report->inventory = ledger->balance_of(string_t("inventory"));
	report->payables = (-ledger->balance_of(string_t("payables")));
	report->net_operating_capital = ((report->receivables + report->inventory) - report->payables);
	return report;
}

bool_t BudgetLine::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == BudgetLine::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t BudgetVarianceRow::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == BudgetVarianceRow::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t BudgetReport::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == BudgetReport::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t BudgetBook::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == BudgetBook::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

P_AccountingCheck BudgetBook::add(P_GeneralLedger ledger, const string_t& account_code, I period, I amount) {
	SCPP_CALL_DEPTH_GUARD("BudgetBook::add", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/reports.phs", 82);
	if (static_cast<bool>(php::condition_truthy(this->locked))) {
		return accounting_failure(string_t("budget.locked"), string_t("Approved budget cannot change"));
	}
	if (static_cast<bool>(((((!ledger->has_account(account_code)) || (period < static_cast<I >(1))) || (period > static_cast<I >(1200))) || (!MoneyRules::accepts(amount))))) {
		return accounting_failure(string_t("budget.line"), string_t("Budget line has an invalid account, period, or amount"));
	}
	string_t key = convert_string_t((convert_string_t(convert_string_t(period)) + string_t(":") + convert_string_t(account_code)));
	if (static_cast<bool>(php::condition_truthy(php::isset(this->keys, key)))) {
		return accounting_failure(string_t("budget.duplicate"), key);
	}
	P_BudgetLine line = create_BudgetLine();
	line->account_code = account_code;
	line->period = period;
	line->planned_amount = amount;
	(void) this->lines.push_back(line);
	this->keys[convert_string_t(key)] = static_cast<bool_t>(true);
	return accounting_success();
}

P_AccountingCheck BudgetBook::__scpp_return_approve(Tag_P_AccountingCheck) {
	SCPP_CALL_DEPTH_GUARD("BudgetBook::approve", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/reports.phs", 102);
	if (static_cast<bool>((this->locked || php::identical(php::count(this->lines), static_cast<I >(0))))) {
		return accounting_failure(string_t("budget.approval"), string_t("Budget is empty or already approved"));
	}
	this->locked = static_cast<bool_t>(true);
	return accounting_success();
}

P_BudgetReport BudgetBook::variance(P_GeneralLedger ledger, I first_period, I last_period) {
	SCPP_CALL_DEPTH_GUARD("BudgetBook::variance", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/reports.phs", 110);
	P_BudgetReport report = create_BudgetReport();
	auto& __scpp_foreach_source_112 = this->lines;
	for (auto __scpp_foreach_entry_112 : foreach_range(__scpp_foreach_source_112)) {
		auto line = __scpp_foreach_entry_112.value_copy();
		if (static_cast<bool>(((line->period < first_period) || (line->period > last_period)))) {
			continue;
		}
		P_BudgetVarianceRow row = create_BudgetVarianceRow();
		row->account_code = line->account_code;
		row->period = line->period;
		row->planned_amount = line->planned_amount;
		row->actual_amount = ledger->turnover(line->account_code, line->period, line->period);
		row->variance = (row->actual_amount - row->planned_amount);
		(void) report->rows.push_back(row);
		report->planned_total += row->planned_amount;
		report->actual_total += row->actual_amount;
		report->variance_total += row->variance;
	}
	return report;
}

}

