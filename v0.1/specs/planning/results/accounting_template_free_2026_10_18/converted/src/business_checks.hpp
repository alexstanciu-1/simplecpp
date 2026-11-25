#pragma once

#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"

namespace scpp {

class GeneralLedger;
class WorkloadChecks;
void fund_company(P_GeneralLedger ledger, P_WorkloadChecks checks, I amount);

void check_purchasing_flow(P_WorkloadChecks checks);

void check_asset_flow(P_WorkloadChecks checks);

void check_budget_flow(P_WorkloadChecks checks);

void check_sales_snapshot(P_WorkloadChecks checks);

}

