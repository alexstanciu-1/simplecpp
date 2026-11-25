#pragma once

#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"

namespace scpp {

class AccountingCheck;
class AccrualRecord;
class AdjustmentBook;
class GeneralLedger;
class PrepaymentSchedule;

class PrepaymentSchedule : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t reference = string_t("");
	string_t expense_account = string_t("");
	I first_period = static_cast<I >(1);
	I original_amount = static_cast<I >(0);
	I released_amount = static_cast<I >(0);
	V_I portions = V_I{};
	H_bool_t_I released_periods = [&]() -> H_bool_t_I {
	H_bool_t_I __scpp_hash_value{};
	return __scpp_hash_value;
}();
};

class AccrualRecord : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t reference = string_t("");
	string_t expense_account = string_t("");
	I period = static_cast<I >(1);
	I amount = static_cast<I >(0);
	bool_t reversed = bool_t(static_cast<bool_t>(false));
};

class AdjustmentBook : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
private:
	H_P_PrepaymentSchedule_string_t prepayments = [&]() -> H_P_PrepaymentSchedule_string_t {
	H_P_PrepaymentSchedule_string_t __scpp_hash_value{};
	return __scpp_hash_value;
}();
	V_string_t prepayment_order = V_string_t{};
	H_P_AccrualRecord_string_t accruals = [&]() -> H_P_AccrualRecord_string_t {
	H_P_AccrualRecord_string_t __scpp_hash_value{};
	return __scpp_hash_value;
}();
	V_string_t accrual_order = V_string_t{};
public:
	virtual P_AccountingCheck prepay(P_GeneralLedger ledger, const string_t& reference, const string_t& expense_account, I amount, I periods);
	virtual P_AccountingCheck release_prepayment(P_GeneralLedger ledger, const string_t& reference);
	virtual P_AccountingCheck accrue(P_GeneralLedger ledger, const string_t& reference, const string_t& expense_account, I amount);
	virtual P_AccountingCheck reverse_accrual(P_GeneralLedger ledger, const string_t& reference);
	virtual I prepayment_balance();
	virtual I accrual_balance();
	virtual P_AccountingCheck reconcile(P_GeneralLedger ledger);
	virtual P_AccountingCheck ready_to_close(P_GeneralLedger ledger);
};

}

