#pragma once

#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"

namespace scpp {

class AccountingCheck;
class BudgetBook;
class BudgetLine;
class BudgetReport;
class BudgetVarianceRow;
class FinancialPosition;
class FinancialReports;
class GeneralLedger;
class ProfitAndLoss;
class WorkingCapital;

class FinancialPosition : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	I assets = static_cast<I >(0);
	I liabilities = static_cast<I >(0);
	I equity = static_cast<I >(0);
	I current_profit = static_cast<I >(0);
	virtual bool_t balances();
};

class ProfitAndLoss : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	I revenue = static_cast<I >(0);
	I expenses = static_cast<I >(0);
	I profit = static_cast<I >(0);
};

class WorkingCapital : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	I cash = static_cast<I >(0);
	I receivables = static_cast<I >(0);
	I inventory = static_cast<I >(0);
	I payables = static_cast<I >(0);
	I net_operating_capital = static_cast<I >(0);
};

class FinancialReports : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	virtual P_ProfitAndLoss profit_and_loss(P_GeneralLedger ledger);
	virtual P_FinancialPosition financial_position(P_GeneralLedger ledger);
	virtual P_WorkingCapital working_capital(P_GeneralLedger ledger);
};

class BudgetLine : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t account_code = string_t("");
	I period = static_cast<I >(1);
	I planned_amount = static_cast<I >(0);
};

class BudgetVarianceRow : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t account_code = string_t("");
	I period = static_cast<I >(1);
	I planned_amount = static_cast<I >(0);
	I actual_amount = static_cast<I >(0);
	I variance = static_cast<I >(0);
};

class BudgetReport : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	V_P_BudgetVarianceRow rows = V_P_BudgetVarianceRow{};
	I planned_total = static_cast<I >(0);
	I actual_total = static_cast<I >(0);
	I variance_total = static_cast<I >(0);
};

class BudgetBook : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
private:
	V_P_BudgetLine lines = V_P_BudgetLine{};
	H_bool_t_string_t keys = [&]() -> H_bool_t_string_t {
	H_bool_t_string_t __scpp_hash_value{};
	return __scpp_hash_value;
}();
	bool_t locked = bool_t(static_cast<bool_t>(false));
public:
	virtual P_AccountingCheck add(P_GeneralLedger ledger, const string_t& account_code, I period, I amount);
	P_AccountingCheck approve() { return __scpp_return_approve(Tag_P_AccountingCheck{}); }
	virtual P_AccountingCheck __scpp_return_approve(Tag_P_AccountingCheck);
	virtual P_BudgetReport variance(P_GeneralLedger ledger, I first_period, I last_period);
};

}

