#pragma once

#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"

namespace scpp {

class AccountingCheck;
class GeneralLedger;
class JournalEntry;
class LedgerAccount;
class TrialBalance;

class GeneralLedger : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
private:
	H_P_LedgerAccount_string_t accounts = [&]() -> H_P_LedgerAccount_string_t {
	H_P_LedgerAccount_string_t __scpp_hash_value{};
	return __scpp_hash_value;
}();
	V_string_t account_order = V_string_t{};
	V_P_JournalEntry entries = V_P_JournalEntry{};
	H_bool_t_string_t posted_references = [&]() -> H_bool_t_string_t {
	H_bool_t_string_t __scpp_hash_value{};
	return __scpp_hash_value;
}();
	H_bool_t_string_t reversed_references = [&]() -> H_bool_t_string_t {
	H_bool_t_string_t __scpp_hash_value{};
	return __scpp_hash_value;
}();
	I open_period = static_cast<I >(1);
public:
	virtual I current_period();
	virtual bool_t has_account(const string_t& code);
	virtual bool_t has_reference(const string_t& reference);
	virtual P_AccountingCheck close_period(I expected_period);
	virtual P_AccountingCheck set_account_active(const string_t& code, bool_t active);
	virtual P_AccountingCheck add_account(const string_t& code, const string_t& label, AccountCategory category);
	virtual P_AccountingCheck validate(P_JournalEntry entry);
	virtual P_AccountingCheck reverse(const string_t& original_reference, const string_t& reversal_reference);
	virtual I turnover(const string_t& code, I first_period, I last_period);
	virtual I reference_movement(const string_t& reference, const string_t& code);
	virtual I category_balance(AccountCategory category);
	virtual P_AccountingCheck post(P_JournalEntry entry);
	virtual I balance_of(const string_t& code);
	virtual I entry_count();
	P_TrialBalance trial_balance() { return __scpp_return_trial_balance(Tag_P_TrialBalance{}); }
	virtual P_TrialBalance __scpp_return_trial_balance(Tag_P_TrialBalance);
};

}

