#include "banking.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t BankStatementLine::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == BankStatementLine::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t BankMatch::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == BankMatch::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t BankReconciliationReport::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == BankReconciliationReport::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t BankReconciliation::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == BankReconciliation::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

P_AccountingCheck BankReconciliation::import_statement(I opening, I closing, I first_day, I last_day, const V_P_BankStatementLine& source) {
	SCPP_CALL_DEPTH_GUARD("BankReconciliation::import_statement", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/banking.phs", 38);
	if (static_cast<bool>(php::condition_truthy(this->initialized))) {
		return accounting_failure(string_t("statement.imported"), string_t("Statement is already loaded"));
	}
	if (static_cast<bool>(((!MoneyRules::accepts(opening)) || (!MoneyRules::accepts(closing))))) {
		return accounting_failure(string_t("statement.balance"), string_t("Statement balances exceed workload limits"));
	}
	if (static_cast<bool>((((first_day < static_cast<I >(0)) || (last_day < first_day)) || (last_day > static_cast<I >(36524))))) {
		return accounting_failure(string_t("statement.dates"), string_t("Statement date range is invalid"));
	}
	if (static_cast<bool>((php::count(source) > static_cast<I >(10000)))) {
		return accounting_failure(string_t("statement.limit"), string_t("Statement contains too many lines"));
	}
	H_bool_t_string_t seen = {};
	I running = convert_I(opening);
	auto& __scpp_foreach_source_53 = source;
	for (auto __scpp_foreach_entry_53 : foreach_range(__scpp_foreach_source_53)) {
		auto line = __scpp_foreach_entry_53.value_copy();
		if (static_cast<bool>((php::identical(line->reference, string_t("")) || php::isset(seen, line->reference)))) {
			return accounting_failure(string_t("statement.reference"), string_t("Statement line reference is empty or repeated"));
		}
		if (static_cast<bool>((((line->day < first_day) || (line->day > last_day)) || php::identical(line->amount, static_cast<I >(0))))) {
			return accounting_failure(string_t("statement.line"), string_t("Statement line has an invalid day or zero movement"));
		}
		P_AmountResult next = MoneyRules::add(running, line->amount);
		if (static_cast<bool>((!next->valid))) {
			return accounting_failure(string_t("statement.range"), string_t("Statement running balance exceeds workload limits"));
		}
		running = next->amount;
		seen[convert_string_t(line->reference)] = static_cast<bool_t>(true);
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(running, closing)))) {
		return accounting_failure(string_t("statement.control"), string_t("Opening plus movements differs from statement closing"));
	}
	auto& __scpp_foreach_source_70 = source;
	for (auto __scpp_foreach_entry_70 : foreach_range(__scpp_foreach_source_70)) {
		auto line = __scpp_foreach_entry_70.value_copy();
		P_BankStatementLine copy = create_BankStatementLine();
		copy->reference = line->reference;
		copy->description = line->description;
		copy->day = line->day;
		copy->amount = line->amount;
		this->lines[convert_string_t(copy->reference)] = copy;
		{
		auto __append_value_1 = copy->reference;
		(void) this->line_order.push_back(__append_value_1);
		}
	}
	this->opening_balance = opening;
	this->closing_balance = closing;
	this->first_day = first_day;
	this->last_day = last_day;
	this->initialized = static_cast<bool_t>(true);
	return accounting_success();
}

P_AccountingCheck BankReconciliation::match_entry(P_GeneralLedger ledger, const string_t& statement_reference, const string_t& journal_reference) {
	SCPP_CALL_DEPTH_GUARD("BankReconciliation::match_entry", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/banking.phs", 87);
	if (static_cast<bool>(((!this->initialized) || this->finalized))) {
		return accounting_failure(string_t("statement.state"), string_t("Statement is not open for matching"));
	}
	if (static_cast<bool>((!php::isset(this->lines, statement_reference)))) {
		return accounting_failure(string_t("statement.missing-line"), statement_reference);
	}
	if (static_cast<bool>((php::isset(this->matches, statement_reference) || php::isset(this->used_journals, journal_reference)))) {
		return accounting_failure(string_t("statement.duplicate-match"), string_t("Statement line or ledger entry is already matched"));
	}
	if (static_cast<bool>((!ledger->has_reference(journal_reference)))) {
		return accounting_failure(string_t("statement.missing-journal"), journal_reference);
	}
	P_BankStatementLine line = this->lines.at(convert_string_t(statement_reference));
	I movement = convert_I(ledger->reference_movement(journal_reference, string_t("bank")));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(movement, line->amount)))) {
		return accounting_failure(string_t("statement.amount-mismatch"), string_t("Signed bank movement differs from statement line"));
	}
	P_BankMatch match = create_BankMatch();
	match->statement_reference = statement_reference;
	match->journal_reference = journal_reference;
	match->amount = movement;
	this->matches[convert_string_t(statement_reference)] = match;
	this->used_journals[convert_string_t(journal_reference)] = static_cast<bool_t>(true);
	return accounting_success();
}

P_AccountingCheck BankReconciliation::unmatch(const string_t& statement_reference) {
	SCPP_CALL_DEPTH_GUARD("BankReconciliation::unmatch", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/banking.phs", 114);
	if (static_cast<bool>(((!this->initialized) || this->finalized))) {
		return accounting_failure(string_t("statement.state"), string_t("Statement is not open for matching"));
	}
	if (static_cast<bool>((!php::isset(this->matches, statement_reference)))) {
		return accounting_failure(string_t("statement.unmatched"), statement_reference);
	}
	P_BankMatch match = this->matches.at(convert_string_t(statement_reference));
	this->used_journals.remove(match->journal_reference);
	this->matches.remove(statement_reference);
	return accounting_success();
}

P_AccountingCheck BankReconciliation::post_adjustment(P_GeneralLedger ledger, const string_t& statement_reference, const string_t& counter_account) {
	SCPP_CALL_DEPTH_GUARD("BankReconciliation::post_adjustment", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/banking.phs", 127);
	if (static_cast<bool>((((!this->initialized) || this->finalized) || (!php::isset(this->lines, statement_reference))))) {
		return accounting_failure(string_t("statement.state"), string_t("Adjustment needs an open statement line"));
	}
	if (static_cast<bool>(php::condition_truthy(php::isset(this->matches, statement_reference)))) {
		return accounting_failure(string_t("statement.duplicate-match"), statement_reference);
	}
	if (static_cast<bool>((php::not_identical(counter_account, string_t("bank-fees")) && php::not_identical(counter_account, string_t("interest-income"))))) {
		return accounting_failure(string_t("statement.adjustment-account"), string_t("Only bank fees and interest use this adjustment workflow"));
	}
	P_BankStatementLine line = this->lines.at(convert_string_t(statement_reference));
	if (static_cast<bool>(((php::identical(counter_account, string_t("bank-fees")) && (line->amount >= static_cast<I >(0))) || (php::identical(counter_account, string_t("interest-income")) && (line->amount <= static_cast<I >(0)))))) {
		return accounting_failure(string_t("statement.adjustment-sign"), string_t("Fee or interest has an unexpected sign"));
	}
	P_JournalEntry entry = create_JournalEntry();
	entry->reference = (string_t("bank-adjustment:") + convert_string_t(statement_reference));
	entry->origin = string_t("banking");
	entry->period = ledger->current_period();
	if (static_cast<bool>((line->amount < static_cast<I >(0)))) {
		entry->add_debit(counter_account, (-line->amount), line->description);
		entry->add_credit(string_t("bank"), (-line->amount), line->description);
	}
	else {
		entry->add_debit(string_t("bank"), line->amount, line->description);
		entry->add_credit(counter_account, line->amount, line->description);
	}
	P_AccountingCheck check = ledger->post(entry);
	if (static_cast<bool>((!check->accepted))) {
		return check;
	}
	return this->match_entry(ledger, statement_reference, entry->reference);
}

P_BankReconciliationReport BankReconciliation::report(P_GeneralLedger ledger) {
	SCPP_CALL_DEPTH_GUARD("BankReconciliation::report", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/banking.phs", 159);
	P_BankReconciliationReport report = create_BankReconciliationReport();
	report->opening_balance = this->opening_balance;
	report->statement_closing = this->closing_balance;
	report->ledger_closing = ledger->balance_of(string_t("bank"));
	auto& __scpp_foreach_source_164 = this->line_order;
	for (auto __scpp_foreach_entry_164 : foreach_range(__scpp_foreach_source_164)) {
		auto reference = __scpp_foreach_entry_164.value_copy();
		P_BankStatementLine line = this->lines.at(convert_string_t(reference));
		report->statement_movement += line->amount;
		if (static_cast<bool>((!php::isset(this->matches, reference)))) {
			report->unmatched_count += static_cast<I >(1);
			report->unmatched_amount += line->amount;
			(void) report->unmatched_references.push_back(reference);
		}
	}
	report->difference = (report->ledger_closing - report->statement_closing);
	report->complete = ((this->initialized && php::identical(report->unmatched_count, static_cast<I >(0))) && php::identical(report->difference, static_cast<I >(0)));
	return report;
}

P_AccountingCheck BankReconciliation::finalize(P_GeneralLedger ledger) {
	SCPP_CALL_DEPTH_GUARD("BankReconciliation::finalize", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/banking.phs", 178);
	if (static_cast<bool>(php::condition_truthy(this->finalized))) {
		return accounting_failure(string_t("statement.finalized"), string_t("Statement was already finalized"));
	}
	P_BankReconciliationReport report = this->report(ledger);
	if (static_cast<bool>((!report->complete))) {
		return accounting_failure(string_t("statement.incomplete"), string_t("Statement contains unmatched lines or a closing difference"));
	}
	this->finalized = static_cast<bool_t>(true);
	return accounting_success();
}

}

