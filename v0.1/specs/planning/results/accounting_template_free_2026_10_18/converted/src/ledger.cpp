#include "ledger.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t GeneralLedger::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == GeneralLedger::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

I GeneralLedger::current_period() {
	SCPP_CALL_DEPTH_GUARD("GeneralLedger::current_period", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/ledger.phs", 9);
	return this->open_period;
}

bool_t GeneralLedger::has_account(const string_t& code) {
	SCPP_CALL_DEPTH_GUARD("GeneralLedger::has_account", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/ledger.phs", 13);
	return php::isset(this->accounts, code);
}

bool_t GeneralLedger::has_reference(const string_t& reference) {
	SCPP_CALL_DEPTH_GUARD("GeneralLedger::has_reference", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/ledger.phs", 17);
	return php::isset(this->posted_references, reference);
}

P_AccountingCheck GeneralLedger::close_period(I expected_period) {
	SCPP_CALL_DEPTH_GUARD("GeneralLedger::close_period", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/ledger.phs", 21);
	if (static_cast<bool>((php::not_identical(expected_period, this->open_period) || (expected_period >= static_cast<I >(1200))))) {
		return accounting_failure(string_t("period.sequence"), string_t("Close must name the current period"));
	}
	P_TrialBalance report = this->trial_balance();
	if (static_cast<bool>((!report->is_balanced()))) {
		return accounting_failure(string_t("period.unbalanced"), string_t("Ledger is not balanced"));
	}
	this->open_period += static_cast<I >(1);
	return accounting_success();
}

P_AccountingCheck GeneralLedger::set_account_active(const string_t& code, bool_t active) {
	SCPP_CALL_DEPTH_GUARD("GeneralLedger::set_account_active", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/ledger.phs", 33);
	if (static_cast<bool>((!php::isset(this->accounts, code)))) {
		return accounting_failure(string_t("account.missing"), code);
	}
	P_LedgerAccount account = this->accounts.at(convert_string_t(code));
	if (static_cast<bool>(((!active) && php::not_identical(account->balance(), static_cast<I >(0))))) {
		return accounting_failure(string_t("account.nonzero"), string_t("Account must be cleared before deactivation"));
	}
	account->active = active;
	return accounting_success();
}

P_AccountingCheck GeneralLedger::add_account(const string_t& code, const string_t& label, AccountCategory category) {
	SCPP_CALL_DEPTH_GUARD("GeneralLedger::add_account", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/ledger.phs", 45);
	if (static_cast<bool>((php::identical(code, string_t("")) || php::identical(label, string_t(""))))) {
		return accounting_failure(string_t("account.empty"), string_t("Account code and label are required"));
	}
	if (static_cast<bool>(php::condition_truthy(php::isset(this->accounts, code)))) {
		return accounting_failure(string_t("account.duplicate"), code);
	}
	P_LedgerAccount record = create_LedgerAccount();
	record->code = code;
	record->label = label;
	record->category = category;
	this->accounts[convert_string_t(code)] = record;
	(void) this->account_order.push_back(code);
	return accounting_success();
}

P_AccountingCheck GeneralLedger::validate(P_JournalEntry entry) {
	SCPP_CALL_DEPTH_GUARD("GeneralLedger::validate", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/ledger.phs", 61);
	if (static_cast<bool>(php::identical(entry->reference, string_t("")))) {
		return accounting_failure(string_t("journal.reference"), string_t("Reference is required"));
	}
	if (static_cast<bool>(php::condition_truthy(php::isset(this->posted_references, entry->reference)))) {
		return accounting_failure(string_t("journal.duplicate"), entry->reference);
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(entry->period, this->open_period)))) {
		return accounting_failure(string_t("journal.period"), string_t("Posting period is not open"));
	}
	if (static_cast<bool>(((php::count(entry->lines) < static_cast<I >(2)) || (php::count(entry->lines) > static_cast<I >(10000))))) {
		return accounting_failure(string_t("journal.lines"), string_t("At least two lines are required"));
	}
	I debits = convert_I(static_cast<I >(0));
	I credits = convert_I(static_cast<I >(0));
	auto __scpp_foreach_source_76 = entry->lines;
	for (auto __scpp_foreach_entry_76 : foreach_range(__scpp_foreach_source_76)) {
		auto line = __scpp_foreach_entry_76.value_copy();
		if (static_cast<bool>((!php::isset(this->accounts, line->account_code)))) {
			return accounting_failure(string_t("journal.account"), line->account_code);
		}
		P_LedgerAccount account = this->accounts.at(convert_string_t(line->account_code));
		if (static_cast<bool>((!account->active))) {
			return accounting_failure(string_t("journal.inactive"), line->account_code);
		}
		if (static_cast<bool>(((line->debit < static_cast<I >(0)) || (line->credit < static_cast<I >(0))))) {
			return accounting_failure(string_t("journal.negative"), string_t("Amounts must be nonnegative"));
		}
		if (static_cast<bool>(php::identical(php::identical(line->debit, static_cast<I >(0)), php::identical(line->credit, static_cast<I >(0))))) {
			return accounting_failure(string_t("journal.side"), string_t("Exactly one posting side must be positive"));
		}
		if (static_cast<bool>(((line->debit > static_cast<I >(1000000000)) || (line->credit > static_cast<I >(1000000000))))) {
			return accounting_failure(string_t("journal.limit"), string_t("Line exceeds workload amount limit"));
		}
		debits += line->debit;
		credits += line->credit;
		if (static_cast<bool>(((debits > static_cast<I >(1000000000)) || (credits > static_cast<I >(1000000000))))) {
			return accounting_failure(string_t("journal.total-limit"), string_t("Entry exceeds workload amount limit"));
		}
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(debits, credits)))) {
		return accounting_failure(string_t("journal.unbalanced"), string_t("Debits differ from credits"));
	}
	H_I_string_t changes = {};
	auto __scpp_foreach_source_103 = entry->lines;
	for (auto __scpp_foreach_entry_103 : foreach_range(__scpp_foreach_source_103)) {
		auto line = __scpp_foreach_entry_103.value_copy();
		if (static_cast<bool>((!php::isset(changes, line->account_code)))) {
			changes[convert_string_t(line->account_code)] = static_cast<I >(0);
		}
		changes[convert_string_t(line->account_code)] += (line->debit - line->credit);
	}
	auto& __scpp_foreach_source_109 = changes;
	for (auto __scpp_foreach_entry_109 : foreach_range(__scpp_foreach_source_109)) {
		auto&& code = __scpp_foreach_entry_109.key();
		auto change = __scpp_foreach_entry_109.value_copy();
		I ending = convert_I((this->balance_of(code) + change));
		if (static_cast<bool>((!MoneyRules::accepts(ending)))) {
			return accounting_failure(string_t("journal.balance-limit"), code);
		}
	}
	return accounting_success();
}

P_AccountingCheck GeneralLedger::reverse(const string_t& original_reference, const string_t& reversal_reference) {
	SCPP_CALL_DEPTH_GUARD("GeneralLedger::reverse", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/ledger.phs", 118);
	if (static_cast<bool>((php::identical(reversal_reference, string_t("")) || (!php::isset(this->posted_references, original_reference))))) {
		return accounting_failure(string_t("reversal.reference"), string_t("Original entry is missing or reversal reference is empty"));
	}
	if (static_cast<bool>(php::condition_truthy(php::isset(this->reversed_references, original_reference)))) {
		return accounting_failure(string_t("reversal.duplicate"), original_reference);
	}
	P_JournalEntry reversal = create_JournalEntry();
	reversal->reference = reversal_reference;
	reversal->origin = (string_t("reversal:") + convert_string_t(original_reference));
	reversal->period = this->open_period;
	auto& __scpp_foreach_source_130 = this->entries;
	for (auto __scpp_foreach_entry_130 : foreach_range(__scpp_foreach_source_130)) {
		auto entry = __scpp_foreach_entry_130.value_copy();
		if (static_cast<bool>(php::identical(entry->reference, original_reference))) {
			auto __scpp_foreach_source_132 = entry->lines;
			for (auto __scpp_foreach_entry_132 : foreach_range(__scpp_foreach_source_132)) {
				auto line = __scpp_foreach_entry_132.value_copy();
				if (static_cast<bool>((line->debit > static_cast<I >(0)))) {
					reversal->add_credit(line->account_code, line->debit, line->memo);
				}
				else {
					reversal->add_debit(line->account_code, line->credit, line->memo);
				}
			}
			break;
		}
	}
	P_AccountingCheck check = this->post(reversal);
	if (static_cast<bool>(php::condition_truthy(check->accepted))) {
		this->reversed_references[convert_string_t(original_reference)] = static_cast<bool_t>(true);
	}
	return check;
}

I GeneralLedger::turnover(const string_t& code, I first_period, I last_period) {
	SCPP_CALL_DEPTH_GUARD("GeneralLedger::turnover", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/ledger.phs", 149);
	I total = convert_I(static_cast<I >(0));
	auto& __scpp_foreach_source_151 = this->entries;
	for (auto __scpp_foreach_entry_151 : foreach_range(__scpp_foreach_source_151)) {
		auto entry = __scpp_foreach_entry_151.value_copy();
		if (static_cast<bool>(((entry->period < first_period) || (entry->period > last_period)))) {
			continue;
		}
		auto __scpp_foreach_source_155 = entry->lines;
		for (auto __scpp_foreach_entry_155 : foreach_range(__scpp_foreach_source_155)) {
			auto line = __scpp_foreach_entry_155.value_copy();
			if (static_cast<bool>(php::identical(line->account_code, code))) {
				total += (line->debit - line->credit);
			}
		}
	}
	return total;
}

I GeneralLedger::reference_movement(const string_t& reference, const string_t& code) {
	SCPP_CALL_DEPTH_GUARD("GeneralLedger::reference_movement", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/ledger.phs", 164);
	auto& __scpp_foreach_source_165 = this->entries;
	for (auto __scpp_foreach_entry_165 : foreach_range(__scpp_foreach_source_165)) {
		auto entry = __scpp_foreach_entry_165.value_copy();
		if (static_cast<bool>(php::condition_truthy(php::not_identical(entry->reference, reference)))) {
			continue;
		}
		I total = convert_I(static_cast<I >(0));
		auto __scpp_foreach_source_170 = entry->lines;
		for (auto __scpp_foreach_entry_170 : foreach_range(__scpp_foreach_source_170)) {
			auto line = __scpp_foreach_entry_170.value_copy();
			if (static_cast<bool>(php::identical(line->account_code, code))) {
				total += (line->debit - line->credit);
			}
		}
		return total;
	}
	return static_cast<I >(0);
}

I GeneralLedger::category_balance(AccountCategory category) {
	SCPP_CALL_DEPTH_GUARD("GeneralLedger::category_balance", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/ledger.phs", 180);
	I total = convert_I(static_cast<I >(0));
	auto& __scpp_foreach_source_182 = this->account_order;
	for (auto __scpp_foreach_entry_182 : foreach_range(__scpp_foreach_source_182)) {
		auto code = __scpp_foreach_entry_182.value_copy();
		P_LedgerAccount account = this->accounts.at(convert_string_t(code));
		if (static_cast<bool>(php::identical(account->category, category))) {
			total += account->balance();
		}
	}
	return total;
}

P_AccountingCheck GeneralLedger::post(P_JournalEntry entry) {
	SCPP_CALL_DEPTH_GUARD("GeneralLedger::post", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/ledger.phs", 191);
	P_AccountingCheck check = this->validate(entry);
	if (static_cast<bool>((!check->accepted))) {
		return check;
	}
	P_JournalEntry snapshot = create_JournalEntry();
	snapshot->reference = entry->reference;
	snapshot->origin = entry->origin;
	snapshot->period = entry->period;
	auto __scpp_foreach_source_201 = entry->lines;
	for (auto __scpp_foreach_entry_201 : foreach_range(__scpp_foreach_source_201)) {
		auto line = __scpp_foreach_entry_201.value_copy();
		P_JournalLine copy = create_JournalLine();
		copy->account_code = line->account_code;
		copy->memo = line->memo;
		copy->debit = line->debit;
		copy->credit = line->credit;
		(void) snapshot->lines.append(copy);
		P_LedgerAccount account = this->accounts.at(convert_string_t(line->account_code));
		account->debits += line->debit;
		account->credits += line->credit;
	}
	(void) this->entries.push_back(snapshot);
	this->posted_references[convert_string_t(entry->reference)] = static_cast<bool_t>(true);
	return accounting_success();
}

I GeneralLedger::balance_of(const string_t& code) {
	SCPP_CALL_DEPTH_GUARD("GeneralLedger::balance_of", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/ledger.phs", 217);
	if (static_cast<bool>((!php::isset(this->accounts, code)))) {
		return static_cast<I >(0);
	}
	P_LedgerAccount account = this->accounts.at(convert_string_t(code));
	return account->balance();
}

I GeneralLedger::entry_count() {
	SCPP_CALL_DEPTH_GUARD("GeneralLedger::entry_count", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/ledger.phs", 225);
	return php::count(this->entries);
}

P_TrialBalance GeneralLedger::__scpp_return_trial_balance(Tag_P_TrialBalance) {
	SCPP_CALL_DEPTH_GUARD("GeneralLedger::trial_balance", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/ledger.phs", 229);
	P_TrialBalance report = create_TrialBalance();
	auto& __scpp_foreach_source_231 = this->account_order;
	for (auto __scpp_foreach_entry_231 : foreach_range(__scpp_foreach_source_231)) {
		auto code = __scpp_foreach_entry_231.value_copy();
		P_LedgerAccount account = this->accounts.at(convert_string_t(code));
		P_TrialBalanceRow row = create_TrialBalanceRow();
		row->account_code = code;
		row->label = account->label;
		I balance = convert_I(account->balance());
		if (static_cast<bool>((balance > static_cast<I >(0)))) {
			row->debit = balance;
		}
		else {
			row->credit = (-balance);
		}
		report->debit_total += row->debit;
		report->credit_total += row->credit;
		(void) report->rows.append(row);
	}
	return report;
}

}

