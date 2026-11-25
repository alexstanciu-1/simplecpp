#include "core.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t AccountingCheck::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == AccountingCheck::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

void AccountingCheck::reject(const string_t& code, const string_t& detail) {
	SCPP_CALL_DEPTH_GUARD("AccountingCheck::reject", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/core.phs", 6);
	this->accepted = static_cast<bool_t>(false);
	this->code = code;
	this->detail = detail;
}

bool_t LedgerAccount::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == LedgerAccount::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

I LedgerAccount::balance() {
	SCPP_CALL_DEPTH_GUARD("LedgerAccount::balance", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/core.phs", 39);
	return (this->debits - this->credits);
}

bool_t JournalLine::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == JournalLine::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t JournalEntry::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == JournalEntry::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

void JournalEntry::add_debit(const string_t& account_code, I amount, const string_t& memo) {
	SCPP_CALL_DEPTH_GUARD("JournalEntry::add_debit", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/core.phs", 57);
	P_JournalLine line = create_JournalLine();
	line->account_code = account_code;
	line->debit = amount;
	line->memo = memo;
	(void) this->lines.push_back(line);
}

void JournalEntry::add_credit(const string_t& account_code, I amount, const string_t& memo) {
	SCPP_CALL_DEPTH_GUARD("JournalEntry::add_credit", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/core.phs", 65);
	P_JournalLine line = create_JournalLine();
	line->account_code = account_code;
	line->credit = amount;
	line->memo = memo;
	(void) this->lines.push_back(line);
}

bool_t TrialBalanceRow::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == TrialBalanceRow::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t TrialBalance::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == TrialBalance::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t TrialBalance::is_balanced() {
	SCPP_CALL_DEPTH_GUARD("TrialBalance::is_balanced", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/core.phs", 86);
	return bool_t(php::identical(this->debit_total, this->credit_total));
}

P_AccountingCheck accounting_failure(const string_t& code, const string_t& detail) {
	SCPP_CALL_DEPTH_GUARD("accounting_failure", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/core.phs", 13);
	P_AccountingCheck check = create_AccountingCheck();
	check->reject(code, detail);
	return check;
}

P_AccountingCheck accounting_success() {
	SCPP_CALL_DEPTH_GUARD("accounting_success", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/core.phs", 19);
	return create_AccountingCheck();
}

}

