#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentDirtyBudgetRow;
int_t<std::uint32_t> __latency_fn_resident_dirty_propagation_append_budget(shared_p<CompilerProjectRunReport>& report, ResidentDirtyBudgetRow row);
}
