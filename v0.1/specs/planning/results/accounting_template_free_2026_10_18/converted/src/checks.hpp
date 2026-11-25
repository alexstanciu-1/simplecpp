#pragma once

#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"

namespace scpp {

class AccountingCheck;
class GeneralLedger;
class WorkloadChecks;

class WorkloadChecks : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	I passed = static_cast<I >(0);
	I failed = static_cast<I >(0);
	V_string_t failures = V_string_t{};
	virtual void expect(bool_t condition, const string_t& label);
	virtual void equal(I actual, I expected, const string_t& label);
	virtual void accepted(P_AccountingCheck check, const string_t& label);
	virtual void rejected(P_AccountingCheck check, const string_t& code, const string_t& label);
	virtual void report();
};

P_GeneralLedger setup_accounting_ledger(P_WorkloadChecks checks);

void check_money_rules(P_WorkloadChecks checks);

void check_calendar_rules(P_WorkloadChecks checks);

void check_ledger_rejections(P_WorkloadChecks checks);

void check_inventory_flow(P_WorkloadChecks checks);

}

