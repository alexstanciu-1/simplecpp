#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentDirtyBudgetRow;
struct ResidentDirtyLimitCheckRow;
void __latency_fn_resident_dirty_propagation_append_bailout_from_limit_check(shared_p<CompilerProjectRunReport>& report, ResidentDirtyBudgetRow budget, ResidentDirtyLimitCheckRow check);
}
