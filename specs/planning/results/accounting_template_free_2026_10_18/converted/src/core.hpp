#pragma once

#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"

namespace scpp {

class AccountingCheck;
class JournalEntry;
class JournalLine;
class LedgerAccount;
class TrialBalance;
class TrialBalanceRow;

class AccountingCheck : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	bool_t accepted = bool_t(static_cast<bool_t>(true));
	string_t code = string_t("ok");
	string_t detail = string_t("");
	virtual void reject(const string_t& code, const string_t& detail);
};

enum class AccountCategory : int32_t {
	Asset = 1,
	Liability = 2,
	Equity = 3,
	Revenue = 4,
	Expense = 5
};

class LedgerAccount : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t code = string_t("");
	string_t label = string_t("");
	AccountCategory category = AccountCategory::Asset;
	bool_t active = bool_t(static_cast<bool_t>(true));
	I debits = static_cast<I >(0);
	I credits = static_cast<I >(0);
	virtual I balance();
};

class JournalLine : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t account_code = string_t("");
	string_t memo = string_t("");
	I debit = static_cast<I >(0);
	I credit = static_cast<I >(0);
};

class JournalEntry : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t reference = string_t("");
	string_t origin = string_t("");
	I period = static_cast<I >(1);
	V_P_JournalLine lines = V_P_JournalLine{};
	virtual void add_debit(const string_t& account_code, I amount, const string_t& memo);
	virtual void add_credit(const string_t& account_code, I amount, const string_t& memo);
};

class TrialBalanceRow : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t account_code = string_t("");
	string_t label = string_t("");
	I debit = static_cast<I >(0);
	I credit = static_cast<I >(0);
};

class TrialBalance : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	V_P_TrialBalanceRow rows = V_P_TrialBalanceRow{};
	I debit_total = static_cast<I >(0);
	I credit_total = static_cast<I >(0);
	virtual bool_t is_balanced();
};

P_AccountingCheck accounting_failure(const string_t& code, const string_t& detail);

P_AccountingCheck accounting_success();

}

