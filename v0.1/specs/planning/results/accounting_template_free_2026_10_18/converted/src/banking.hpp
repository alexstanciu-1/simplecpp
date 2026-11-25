#pragma once

#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"

namespace scpp {

class AccountingCheck;
class BankMatch;
class BankReconciliation;
class BankReconciliationReport;
class BankStatementLine;
class GeneralLedger;

class BankStatementLine : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t reference = string_t("");
	string_t description = string_t("");
	I day = static_cast<I >(0);
	I amount = static_cast<I >(0);
};

class BankMatch : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t statement_reference = string_t("");
	string_t journal_reference = string_t("");
	I amount = static_cast<I >(0);
};

class BankReconciliationReport : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	I opening_balance = static_cast<I >(0);
	I statement_movement = static_cast<I >(0);
	I statement_closing = static_cast<I >(0);
	I ledger_closing = static_cast<I >(0);
	I unmatched_count = static_cast<I >(0);
	I unmatched_amount = static_cast<I >(0);
	I difference = static_cast<I >(0);
	bool_t complete = bool_t(static_cast<bool_t>(false));
	V_string_t unmatched_references = V_string_t{};
};

class BankReconciliation : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
private:
	bool_t initialized = bool_t(static_cast<bool_t>(false));
	bool_t finalized = bool_t(static_cast<bool_t>(false));
	I opening_balance = static_cast<I >(0);
	I closing_balance = static_cast<I >(0);
	I first_day = static_cast<I >(0);
	I last_day = static_cast<I >(0);
	H_P_BankStatementLine_string_t lines = [&]() -> H_P_BankStatementLine_string_t {
	H_P_BankStatementLine_string_t __scpp_hash_value{};
	return __scpp_hash_value;
}();
	V_string_t line_order = V_string_t{};
	H_P_BankMatch_string_t matches = [&]() -> H_P_BankMatch_string_t {
	H_P_BankMatch_string_t __scpp_hash_value{};
	return __scpp_hash_value;
}();
	H_bool_t_string_t used_journals = [&]() -> H_bool_t_string_t {
	H_bool_t_string_t __scpp_hash_value{};
	return __scpp_hash_value;
}();
public:
	virtual P_AccountingCheck import_statement(I opening, I closing, I first_day, I last_day, const V_P_BankStatementLine& source);
	virtual P_AccountingCheck match_entry(P_GeneralLedger ledger, const string_t& statement_reference, const string_t& journal_reference);
	virtual P_AccountingCheck unmatch(const string_t& statement_reference);
	virtual P_AccountingCheck post_adjustment(P_GeneralLedger ledger, const string_t& statement_reference, const string_t& counter_account);
	virtual P_BankReconciliationReport report(P_GeneralLedger ledger);
	virtual P_AccountingCheck finalize(P_GeneralLedger ledger);
};

}

