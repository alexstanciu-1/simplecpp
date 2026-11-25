#include "closing.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t PrepaymentSchedule::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == PrepaymentSchedule::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t AccrualRecord::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == AccrualRecord::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t AdjustmentBook::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == AdjustmentBook::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

P_AccountingCheck AdjustmentBook::prepay(P_GeneralLedger ledger, const string_t& reference, const string_t& expense_account, I amount, I periods) {
	SCPP_CALL_DEPTH_GUARD("AdjustmentBook::prepay", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/closing.phs", 25);
	if (static_cast<bool>((php::identical(reference, string_t("")) || php::isset(this->prepayments, reference)))) {
		return accounting_failure(string_t("prepayment.reference"), string_t("Prepayment reference is empty or already used"));
	}
	if (static_cast<bool>(((((amount <= static_cast<I >(0)) || (periods <= static_cast<I >(0))) || (periods > static_cast<I >(120))) || (((ledger->current_period() + periods) - static_cast<I >(1)) > static_cast<I >(1200))))) {
		return accounting_failure(string_t("prepayment.schedule"), string_t("Prepayment amount or schedule is invalid"));
	}
	if (static_cast<bool>((php::not_identical(expense_account, string_t("operating-expense")) && php::not_identical(expense_account, string_t("bank-fees"))))) {
		return accounting_failure(string_t("prepayment.account"), string_t("Prepayment must release to an allowed expense account"));
	}
	if (static_cast<bool>((amount > ledger->balance_of(string_t("bank"))))) {
		return accounting_failure(string_t("prepayment.funds"), string_t("Bank cannot fund prepayment"));
	}
	P_AllocationResult allocation = MoneyAllocation::equally(amount, periods);
	if (static_cast<bool>((!allocation->valid))) {
		return accounting_failure(allocation->reason, string_t("Prepayment allocation failed"));
	}
	P_JournalEntry entry = create_JournalEntry();
	entry->reference = (string_t("prepayment:") + convert_string_t(reference));
	entry->origin = string_t("period-adjustments");
	entry->period = ledger->current_period();
	entry->add_debit(string_t("prepayments"), amount, reference);
	entry->add_credit(string_t("bank"), amount, reference);
	P_AccountingCheck check = ledger->post(entry);
	if (static_cast<bool>((!check->accepted))) {
		return check;
	}
	P_PrepaymentSchedule schedule = create_PrepaymentSchedule();
	schedule->reference = reference;
	schedule->expense_account = expense_account;
	schedule->first_period = entry->period;
	schedule->original_amount = amount;
	schedule->portions = allocation->amounts;
	this->prepayments[convert_string_t(reference)] = schedule;
	(void) this->prepayment_order.push_back(reference);
	return accounting_success();
}

P_AccountingCheck AdjustmentBook::release_prepayment(P_GeneralLedger ledger, const string_t& reference) {
	SCPP_CALL_DEPTH_GUARD("AdjustmentBook::release_prepayment", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/closing.phs", 63);
	if (static_cast<bool>((!php::isset(this->prepayments, reference)))) {
		return accounting_failure(string_t("prepayment.missing"), reference);
	}
	P_PrepaymentSchedule schedule = this->prepayments.at(convert_string_t(reference));
	I period = convert_I(ledger->current_period());
	I index = convert_I((period - schedule->first_period));
	if (static_cast<bool>(((index < static_cast<I >(0)) || (index >= php::count(schedule->portions))))) {
		return accounting_failure(string_t("prepayment.period"), string_t("Current period is outside the release schedule"));
	}
	if (static_cast<bool>(php::condition_truthy(php::isset(schedule->released_periods, period)))) {
		return accounting_failure(string_t("prepayment.released"), string_t("Current period has already been released"));
	}
	if (static_cast<bool>(((index > static_cast<I >(0)) && (!php::isset(schedule->released_periods, (period - static_cast<I >(1))))))) {
		return accounting_failure(string_t("prepayment.sequence"), string_t("Previous scheduled release is missing"));
	}
	I amount = convert_I(schedule->portions.at(index));
	if (static_cast<bool>((amount > static_cast<I >(0)))) {
		P_JournalEntry entry = create_JournalEntry();
		entry->reference = (string_t("prepayment-release:") + convert_string_t(convert_string_t(period)) + string_t(":") + convert_string_t(reference));
		entry->origin = string_t("period-adjustments");
		entry->period = period;
		entry->add_debit(schedule->expense_account, amount, reference);
		entry->add_credit(string_t("prepayments"), amount, reference);
		P_AccountingCheck check = ledger->post(entry);
		if (static_cast<bool>((!check->accepted))) {
			return check;
		}
	}
	schedule->released_amount += amount;
	schedule->released_periods[convert_I(period)] = static_cast<bool_t>(true);
	return accounting_success();
}

P_AccountingCheck AdjustmentBook::accrue(P_GeneralLedger ledger, const string_t& reference, const string_t& expense_account, I amount) {
	SCPP_CALL_DEPTH_GUARD("AdjustmentBook::accrue", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/closing.phs", 97);
	if (static_cast<bool>((php::identical(reference, string_t("")) || php::isset(this->accruals, reference)))) {
		return accounting_failure(string_t("accrual.reference"), string_t("Accrual reference is empty or already used"));
	}
	if (static_cast<bool>(((amount <= static_cast<I >(0)) || (!MoneyRules::accepts(amount))))) {
		return accounting_failure(string_t("accrual.amount"), string_t("Accrual amount must be positive and bounded"));
	}
	if (static_cast<bool>((php::not_identical(expense_account, string_t("operating-expense")) && php::not_identical(expense_account, string_t("bank-fees"))))) {
		return accounting_failure(string_t("accrual.account"), string_t("Accrual must charge an allowed expense account"));
	}
	P_JournalEntry entry = create_JournalEntry();
	entry->reference = (string_t("accrual:") + convert_string_t(reference));
	entry->origin = string_t("period-adjustments");
	entry->period = ledger->current_period();
	entry->add_debit(expense_account, amount, reference);
	entry->add_credit(string_t("accruals"), amount, reference);
	P_AccountingCheck check = ledger->post(entry);
	if (static_cast<bool>((!check->accepted))) {
		return check;
	}
	P_AccrualRecord record = create_AccrualRecord();
	record->reference = reference;
	record->expense_account = expense_account;
	record->period = entry->period;
	record->amount = amount;
	this->accruals[convert_string_t(reference)] = record;
	(void) this->accrual_order.push_back(reference);
	return accounting_success();
}

P_AccountingCheck AdjustmentBook::reverse_accrual(P_GeneralLedger ledger, const string_t& reference) {
	SCPP_CALL_DEPTH_GUARD("AdjustmentBook::reverse_accrual", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/closing.phs", 127);
	if (static_cast<bool>((!php::isset(this->accruals, reference)))) {
		return accounting_failure(string_t("accrual.missing"), reference);
	}
	P_AccrualRecord record = this->accruals.at(convert_string_t(reference));
	if (static_cast<bool>(php::condition_truthy(record->reversed))) {
		return accounting_failure(string_t("accrual.reversed"), reference);
	}
	if (static_cast<bool>((ledger->current_period() <= record->period))) {
		return accounting_failure(string_t("accrual.period"), string_t("Accrual reverses in a later period"));
	}
	P_AccountingCheck check = ledger->reverse((string_t("accrual:") + convert_string_t(reference)), (string_t("accrual-reversal:") + convert_string_t(reference)));
	if (static_cast<bool>(php::condition_truthy(check->accepted))) {
		record->reversed = static_cast<bool_t>(true);
	}
	return check;
}

I AdjustmentBook::prepayment_balance() {
	SCPP_CALL_DEPTH_GUARD("AdjustmentBook::prepayment_balance", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/closing.phs", 145);
	I total = convert_I(static_cast<I >(0));
	auto& __scpp_foreach_source_147 = this->prepayment_order;
	for (auto __scpp_foreach_entry_147 : foreach_range(__scpp_foreach_source_147)) {
		auto reference = __scpp_foreach_entry_147.value_copy();
		P_PrepaymentSchedule schedule = this->prepayments.at(convert_string_t(reference));
		total += (schedule->original_amount - schedule->released_amount);
	}
	return total;
}

I AdjustmentBook::accrual_balance() {
	SCPP_CALL_DEPTH_GUARD("AdjustmentBook::accrual_balance", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/closing.phs", 154);
	I total = convert_I(static_cast<I >(0));
	auto& __scpp_foreach_source_156 = this->accrual_order;
	for (auto __scpp_foreach_entry_156 : foreach_range(__scpp_foreach_source_156)) {
		auto reference = __scpp_foreach_entry_156.value_copy();
		P_AccrualRecord record = this->accruals.at(convert_string_t(reference));
		if (static_cast<bool>((!record->reversed))) {
			total += record->amount;
		}
	}
	return total;
}

P_AccountingCheck AdjustmentBook::reconcile(P_GeneralLedger ledger) {
	SCPP_CALL_DEPTH_GUARD("AdjustmentBook::reconcile", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/closing.phs", 165);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(this->prepayment_balance(), ledger->balance_of(string_t("prepayments")))))) {
		return accounting_failure(string_t("prepayment.control"), string_t("Schedule balances differ from prepayment control"));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(this->accrual_balance(), (-ledger->balance_of(string_t("accruals"))))))) {
		return accounting_failure(string_t("accrual.control"), string_t("Accrual balances differ from liability control"));
	}
	return accounting_success();
}

P_AccountingCheck AdjustmentBook::ready_to_close(P_GeneralLedger ledger) {
	SCPP_CALL_DEPTH_GUARD("AdjustmentBook::ready_to_close", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/closing.phs", 175);
	I period = convert_I(ledger->current_period());
	auto& __scpp_foreach_source_177 = this->prepayment_order;
	for (auto __scpp_foreach_entry_177 : foreach_range(__scpp_foreach_source_177)) {
		auto reference = __scpp_foreach_entry_177.value_copy();
		P_PrepaymentSchedule schedule = this->prepayments.at(convert_string_t(reference));
		I last_period = convert_I(((schedule->first_period + php::count(schedule->portions)) - static_cast<I >(1)));
		for (auto due = schedule->first_period; static_cast<bool>(((due <= last_period) && (due <= period))); (due++)) {
			if (static_cast<bool>((!php::isset(schedule->released_periods, due)))) {
				return accounting_failure(string_t("close.prepayment-pending"), reference);
			}
		}
	}
	auto& __scpp_foreach_source_186 = this->accrual_order;
	for (auto __scpp_foreach_entry_186 : foreach_range(__scpp_foreach_source_186)) {
		auto reference = __scpp_foreach_entry_186.value_copy();
		P_AccrualRecord record = this->accruals.at(convert_string_t(reference));
		if (static_cast<bool>(((period > record->period) && (!record->reversed)))) {
			return accounting_failure(string_t("close.accrual-pending"), reference);
		}
	}
	return this->reconcile(ledger);
}

}

